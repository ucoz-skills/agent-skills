# PHP modules

PHP is the default runtime: no process to manage, nothing to restart, and opcache is off, so a
saved file is live on the next request. Starter code: [templates/php/](../templates/php/).

## 1. Module layout

One module = one folder under the docroot + one secrets file outside it.

```text
domains/{domain}/
├─ {name}_secrets.php            secrets (outside docroot), files_chmod perm "384" (= 0600)
├─ {name}_data/                  created by bootstrap (0700): app.log, rate-limit files, caches, uploads
└─ public_html/{name}/
   ├─ index.php                  STUB: require lib/bootstrap.php, then lib/app.php — nothing else
   ├─ .htaccess                  deny lib/, no listings, clean URLs
   ├─ lib/bootstrap.php          error handling, own log, secrets, DB, HTTP client, keys, identity
   ├─ lib/app.php                the module: actions, page, router
   └─ assets/                    small CSS/JS (text). Binary images → files_tool site storage
```

**Why the stub (verified):** by default this hosting writes no PHP error log you can read through MCP; only
the bootstrap's `ini_set('error_log', …)` creates one. A syntax error in the entry file happens before that
and gives an empty `500 text/html` with no trace anywhere. Because the stub loads the
bootstrap first, a `ParseError` in `lib/app.php` is caught by its exception handler, logged as
`syntax error, … at app.php:N`, and answered as JSON 500. Keep the stub and the bootstrap stable, and put all
changes into `lib/app.php`.

Public URL: `https://{site}/apps/{name}/`. Keep **everything that is not an endpoint** either in
`lib/` (denied by `.htaccess`) or outside `public_html`.

## 2. Front controller contract

- `GET /apps/{name}/` → HTML (UI or widget page) or JSON for read endpoints.
- `POST /apps/{name}/` with JSON `{"action": "...", "payload": {...}}` → JSON
  `{"ok": true, "data": ...}` or `{"ok": false, "error": "code", "message": "..."}`.
- Use the right HTTP status: 400 for invalid input, 401/403 for auth, 404 for an unknown action,
  405 for the wrong method, 429 for a rate limit, 500 only for real faults.
- Always `Content-Type: application/json; charset=utf-8` and `Cache-Control: no-store` for API answers.
- Never let PHP print an HTML error into a JSON endpoint. The bootstrap's handlers turn every
  uncaught error into JSON and log it.

`templates/php/lib/app.php` implements this with an action table, so each new feature is one
function and one line in the table.

## 3. Error handling (mandatory)

```php
set_error_handler(static function (int $no, string $str, string $file, int $line): bool {
    if (!(error_reporting() & $no)) {
        return false;                      // suppressed with @ — PHP 8 masks error_reporting inside @
    }
    if ($no & (E_DEPRECATED | E_USER_DEPRECATED | E_NOTICE | E_USER_NOTICE)) {
        return true;                       // log-only noise: never turn it into an exception
    }
    throw new ErrorException($str, 0, $no, $file, $line);
});
```

Why: the strict "every warning is an exception" pattern turns harmless deprecations into 500s. Without
the `error_reporting()` check it also ignores `@`. Verified: `@mkdir()` on an existing folder became a fatal
"mkdir(): File exists". Prefer `is_dir($d) || mkdir($d, 0700, true)` over `@mkdir` anyway.
PHP 8.4/8.5 deprecate many standard functions (for example `curl_close()` in 8.5), so code that
worked on 8.3 starts failing after a version switch. Do not use `E_STRICT` in new code — it is
deprecated in PHP 8.4.

Also in bootstrap:

```php
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', dirname(__DIR__, 3) . '/{name}_data/php-error.log'); // outside docroot
```

By default PHP writes no error log you can read through MCP. With the `ini_set('error_log', …)` above it
does (verified): `error_log()` calls, warnings, and fatals such as "Allowed memory size exhausted" land in
`{name}_data/php-error.log`. Fatals (memory, time limit) bypass the exception handler, so this file is the
only place they show up. The bootstrap's own `app.log` holds handled errors and events. Read both with
`files_read` when debugging; the template rotates `app.log` at ~1 MB.

Constants: declare every `const`/`define` **before** any early `exit` branch. A constant
declared below the POST handler that exits is never defined for that request ("Undefined
constant").

## 4. Secrets

`domains/{domain}/{name}_secrets.php`:

```php
<?php
return [
    'db' => [
        'host' => 'localhost',
        'name' => '{username}_{db}',   // prefix is mandatory
        'user' => '{username}_{db}',
        'pass' => '…',
    ],
    'admin_code'  => '…',             // top-level keys — do not nest unrelated secrets inside 'db'
    'uapi_token'  => '…',
    'cron_key'    => '…',
];
```

Loader (in `lib/bootstrap.php`): walk up from `__DIR__` until `{name}_secrets.php` is found, at most
6 levels. Fail closed with a 500 JSON `{"error":"config"}` (no path, no details) if it is missing.

Write it with `files_write overwrite:false`, then `files_chmod perm:"384"` (decimal for 0600 — the tool
reads `perm` as a decimal number, and `"600"` makes the file unreadable: verified "Permission denied").
Check `mode` with `files_list` (`-rw-------`) and call the health endpoint.

## 4a. Keys for cron, webhooks, admin actions

Use `require_key('cron_key', 'X-Cron-Key')` from the bootstrap. It fails closed: a missing or short secret
never matches. A naive `hash_equals($secrets['cron_key'] ?? '', $header ?? '')` is **open** when the key
is absent from config, because `hash_equals('', '')` is true. Verified on a live endpoint.

## 4b. Outbound HTTP

`http_request()` / `http_post_json()` in the bootstrap: short timeouts, no redirects, http(s) only, and they
never throw (`code: 0` on network errors). Verified against Telegram, CBR, GitHub raw, and the site's own `/apps/` URLs.

## 5. What is available

| Available | Not available |
|---|---|
| PDO MySQL / mysqli (`nd_*` drivers), PDO SQLite, curl, openssl, sodium, intl, mbstring, gd, imagick, zip, soap, sockets, dom/xml/xsl, fileinfo | `disable_functions` (verified, PHP 8.3): `exec`, `system`, `passthru`, `shell_exec`, `proc_open`, `proc_close`, `popen`, `dl`, `show_source`, `posix_kill`, `posix_mkfifo`, `posix_getpwuid`, `posix_setpgid`, `posix_setsid`, `posix_setuid`, `posix_setgid`, `posix_seteuid`, `posix_setegid`, `posix_uname` |
| Sessions (`session_start()`), cookies | Background workers or long-running loops (use cron); requests longer than `max_execution_time` 30 s (and ~60 s at the gateway) |
| Outbound HTTP (curl, `allow_url_fopen`) | Paths outside `open_basedir` (verified: `/home/{username}/`, `/tmp`, `/var/tmp`, PEAR, `/opt/ucoz/lib/`) — keep files under the home dir |
| `ucoz_user()` — preloaded via `auto_prepend_file` (IDENTITY.md) | Your own `auto_prepend_file` (it would replace the platform helper) |
| `ini_set` for `memory_limit`, `error_log`, `display_errors`, `max_execution_time` (within limits) | apcu, redis, memcached, opcache |

Check what is really enabled on the target with `templates/probe/probe.php` when anything is uncertain.

## 6. Uploads

Defaults are `upload_max_filesize 2M` and `post_max_size 8M`. Too-large multipart uploads fail
**before** your code runs, and the client gets an HTML error, which shows up as
"JSON.parse: unexpected character".

Do this:
1. Resize/compress in the browser (`canvas.toBlob('image/webp', 0.85)`) and send a small blob.
2. Server: check `$_FILES[...]['error']`, size, and `finfo` MIME type. Re-encode images through GD/Imagick
   (this strips payloads). Store under `{name}_data/uploads/` with a random name and serve through a
   PHP endpoint, or push the file to CMS storage.

## 7. Sessions and admin pages

- Admin area: a password or code from secrets + `session_start()` + a CSRF token on every
  state-changing request + login rate limiting. Mark the page `noindex`.
- Session cookie: `session_set_cookie_params(['path' => '/apps/{name}/', 'secure' => true, 'httponly' => true, 'samesite' => 'Lax'])`
  and a module-specific `session_name('{name}_sid')` so it never collides with uCoz CMS cookies on the same domain.
- For site members, use `ucoz_user()` instead of your own login (IDENTITY.md).

## 8. `.htaccess` for a module

Use [templates/php/.htaccess](../templates/php/.htaccess) unchanged:

```apache
Options -Indexes
DirectoryIndex index.php
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteRule ^lib/ - [F,L]
  RewriteRule (^|/)\.(?!well-known/) - [F,L]
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteRule ^ index.php [L,QSA]
</IfModule>
<FilesMatch "\.(log|sql|json|jsonl|bak|ini|sh|md|lock)$|\.bak-">
  Require all denied
</FilesMatch>
```

The last rewrite sends every non-file path (`/apps/{name}/api/health`) to `index.php`; without it clean
routes return 404. Verified: the proxy strips `/apps`, so PHP sees `/{name}/admin` and
`HTTP_HOST = {tech}` for both the site URL and the technical URL. The template's `route_path()` strips
`/{name}` (and `/apps/{name}` for safety).

**Trailing slash:** `https://{site}/apps/{name}` (no slash) is answered by the web server with a
**301 to `https://{tech}/{name}/`** — the visitor leaves the site domain and loses identity (verified).
Always link to `/apps/{name}/`. Build absolute URLs from config (`APP_PUBLIC_BASE`), never from `HTTP_HOST`.

## 9. Calling other services

- Outbound HTTP with curl: set `CURLOPT_TIMEOUT` (≤ 15 s) and `CURLOPT_CONNECTTIMEOUT` (≤ 5 s), and check
  the HTTP code. Never disable TLS verification.
- Calling your own Node/Python app from PHP: request `https://{site}/apps/{app_uri}/…` (calls to the site's
  own `/apps/` URLs work) with a key header checked by the app's `requireKey()` / `require_key()`. The call
  carries no visitor identity.
- Calling the site uAPI: UAPI.md.

## 10. Checklist before calling it done

- [ ] `index.php` is the 2-line stub; logic in `lib/app.php`; `lib/` denied (403 verified)
- [ ] Error handler respects `@`; own log file outside the docroot
- [ ] Secrets file outside the docroot, `files_chmod perm:"384"`, mode checked, not echoed anywhere
- [ ] Every key check goes through `require_key()` (fails closed)
- [ ] Every input validated (type, length, allowed values). Prepared statements only
- [ ] State-changing endpoints: POST only + auth + CSRF/Origin check
- [ ] JSON errors with proper status codes. No stack traces to clients
- [ ] Verified live on `https://{site}/apps/{name}/`
