# Security

Server scripts run on the site's own origin (`https://{site}/apps/…`). A bug here is a bug in the
site. Treat every endpoint as public and hostile until proven otherwise.

## 1. Secrets

| Where | OK? |
|---|---|
| `domains/{domain}/{name}_secrets.php` (PHP), `files_chmod perm:"384"` (= 0600; decimal!) | ✅ |
| `config.json` in a Node/Python app root, never whitelisted as static, `perm:"384"` | ✅ |
| `env_vars` at `*_create` | ✅ for stable values (stored in `public_html/{app_uri}/.htaccess` as `SetEnv`, shown by `*_list`) |
| Anywhere under `public_html` except generated `.htaccess` | ❌ |
| Code, comments, HTML, JS sent to the browser, URLs (except a dedicated webhook/cron key) | ❌ |
| Chat replies, reports, commit messages, logs, agent memory | ❌ |

Generate secrets randomly (32+ chars): `bin2hex(random_bytes(16))`, `crypto.randomBytes(16).toString('hex')`,
`secrets.token_hex(16)`. Write them straight into the file. In reports, say "stored in <path>".

### Tool outputs that contain secrets (never echo)

`db_create_with_user` (password) · `visitor_key_get` (key) · `node_list` / `python_list` (`env_vars`) ·
`files_read` of `.htaccess` in app folders (`SetEnv`) and of secrets files · `phpmyadmin`, `db_export`,
`db_import` (SSO links) · `login_key_*`, `da_login_key_*` · `ftp_create` / `ftp_passwd` · `user_config` (account e-mail) ·
`cron_list` (full command lines with keys) · bot tokens (Telegram and others).

## 2. Public links

`files_publish`, `files_download_archive`, `backup_publish`, and large/binary `files_read` produce
public URLs under `https://{site}/apps/_mcp_dl/…`. A published backup contains **all code, secrets,
and the database**. Only publish on explicit request, say that the link is public, and offer to remove
the published copy after the user downloads it.

## 3. Authentication models

| Caller | Model |
|---|---|
| Site members | `ucoz_user()` (IDENTITY.md). Authorize by `id`/`gid`. |
| Site staff without CMS accounts / a private tool | Access code from secrets → HMAC-signed HttpOnly cookie. Rate-limit login attempts (e.g. 10 per 10 min per IP). Compare with constant-time functions. |
| External service (webhook) | Secret in path or header + provider signature check (HMAC of body) when available. |
| Cron | Dedicated cron key in a header. 404 on mismatch. |
| Server-to-server (PHP → your Node/Python app) | Dedicated key header checked with `requireKey()` / `require_key()`. |

Never accept identity from request parameters (`?uid=`), custom headers the browser can set, or
`localStorage`.

**Shared secrets must fail closed.** `hash_equals($config['key'] ?? '', $header ?? '')` returns **true**
when the key is missing from config and the header is absent. Verified: a copied module without
`cron_key` answered `{"ok":true}` to an anonymous request. Use `require_key()` from the PHP bootstrap
(rejects secrets shorter than 16 chars) and `requireKey()` / `require_key()` from the Node/Python templates,
which also compare SHA-256 digests so unequal lengths or non-ASCII input cannot throw.

**Webhook signatures:** HMAC-SHA256 over `"<timestamp>.<raw body>"`, a ±5-minute window against replay, and an
event id with a UNIQUE key against duplicates. Reference implementation: `examples/r3-webhook-queue`.

## 4. Browser-facing endpoints

- **State changes only via POST/PUT/PATCH/DELETE**, never GET.
- **CSRF:** cookie `SameSite=Lax` + a CSRF token for session-based admin UIs, or verify `Origin` equals
  `https://{site}` (and `https://{tech}` if you use it) on every state-changing request.
- **Cookies:** unique names (`{name}_sid`), `Path=/apps/{name}/`, `Secure`, `HttpOnly`, `SameSite=Lax`.
  They share the domain with the CMS — never touch CMS cookies.
- **Output encoding:** escape everything rendered into HTML (`htmlspecialchars($s, ENT_QUOTES, 'UTF-8')`).
  In JS, prefer `textContent` to `innerHTML`.
- **Headers on HTML responses:** `X-Content-Type-Options: nosniff`, `Referrer-Policy: same-origin`,
  `X-Frame-Options: SAMEORIGIN` (unless the page is meant to be embedded), and a CSP when practical.
- **CORS:** not needed for calls from the site itself (same origin). Do not add `Access-Control-Allow-Origin: *`
  to authenticated endpoints. If a third-party origin must call you, allow-list it explicitly.

## 5. Input handling

- Validate type, length, and allowed values for every field. Reject unknown actions.
- Cap request size (JSON ≤ 100 KB unless the feature needs more).
- SQL: prepared statements only. Whitelist dynamic identifiers.
- Files: never build paths from input. Use random names and check MIME with `finfo`. Re-encode images.
- Outbound HTTP to user-supplied URLs (SSRF): allow-list hosts, `https` only, no redirects to private IPs.
- E-mail/notifications: strip CR/LF from header values.

## 6. Abuse controls

- Rate-limit public write endpoints (forms, votes, comments) per IP hash and per uid: a MySQL table or
  a JSON counter with a TTL.
- Add a honeypot field and a minimum fill time to public forms. Add CAPTCHA only if spam continues.
- Store IPs hashed (HMAC with a secret) unless the owner needs raw IPs.

## 7. Logging hygiene

Log events, ids, and error codes — not request bodies with personal data, tokens, `X-Ucoz-User`,
cookies, or passwords. Cap log size. Logs live outside the docroot.

## 8. Before you finish

- [ ] `files_list public_html/{name}` shows only intended public files
- [ ] Try to fetch `lib/bootstrap.php`, `config.json`, `.htaccess`, `*.log` through the URL → 403/404
- [ ] Unauthenticated call to a protected endpoint → 401/403 JSON
- [ ] No secrets in any response, file under `public_html`, or your final message
- [ ] Temporary debug/probe endpoints removed (with approval for `files_delete`)
