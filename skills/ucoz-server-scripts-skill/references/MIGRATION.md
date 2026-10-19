# Migrating legacy uCoz PHP scripts to Server Scripts

Many uCoz sites still run backend code written years ago for the **legacy uCoz PHP server** (PHP 5.x,
the `$___notjson` / `ucoz_getinfo()` wrapper) or talking to uAPI through the old **OAuth 1.0a**
`uAPImodule.php`. This code is often slow, insecure, half-broken, and nobody on the team remembers who wrote it.
This guide moves such a feature to Server Scripts **without a visible break**: rebuild next to the old
code, verify, switch one line of site wiring, then retire the old code and its credentials.

You do not edit or run code inside the legacy wrapper — that stays out of scope. You **replace** it.

## 1. Recognise legacy code

| Sign | Where you see it |
|---|---|
| `<?php $___notjson=1;` at the top, `ucoz_getinfo()` calls | the script source |
| Responses like `({"SCRIPTDATA":"…","ERROR":""})` | HTTP response of the endpoint |
| URLs under `/php/…` on the site domain (e.g. `/php/search/search.php`) | site JS, template code, forms |
| `uAPImodule.php`, `class Request`, `oauth_consumer_key` / `oauth_consumer_secret` / `oauth_token` / `oauth_token_secret`, `HMAC-SHA1` | the script or a file next to it |
| `$myWebsite = 'http://'.$_SERVER['HTTP_HOST'].'/'` | `uAPImodule.php` |
| jQuery `$.post('/php/…')` on every `keyup`, HTML fragments inserted with `.html(response)` | site storage JS (`/js/*.js`) |

The agent usually cannot read files on the legacy PHP server. Ask the user for the source files, and treat
any credentials in them as compromised once they have been shared (chat, messengers, other AI tools).

## 2. Inventory (read-only)

Before writing anything, write down — and show the user — what the old feature really does:

1. **Caller.** Which page, form, or script calls it? Find the include in templates
   (`templates_read read_template`, usually a global block such as the footer) and the JS in site storage.
2. **Contract.** Input parameters, output format (HTML fragment or JSON), how the caller renders it.
3. **Data sources.** Which uAPI endpoints (or external services) it reads or writes, and how often.
4. **Secrets.** Keys and tokens in the code. List them by kind — never repeat their values.
5. **Schedules.** Cron jobs or anything that runs without a visitor.
6. **Domains.** Every domain the feature is served on. A site can also be published under another
   domain's path through a reverse proxy (e.g. `www.example.com/help/` → `help-site.ucoz.net`).
7. **Live behaviour.** Call the old endpoint yourself: response time, results for a few typical inputs,
   behaviour on errors. Legacy code often turns any failure into an "empty result", and its behaviour can
   change between two checks (verified: a search that returned "nothing found" for every query in 0.5 s
   later answered correctly in ~15 s per request). Record numbers; they become the before/after comparison.

Read the code for defects worth fixing during the move, and tell the user what you found. Typical ones:

| Legacy pattern | Problem | Server Scripts replacement |
|---|---|---|
| OAuth 1.0a keys hardcoded in the script | Leaked credentials, archived auth scheme | uAPI Bearer key from a dedicated low-privilege user, in `{name}_secrets.php` (UAPI.md) |
| Reading every page of `/uapi/<module>` on each request and filtering in PHP | Seconds per request, dozens of uAPI calls per keystroke | Cron builds a local cache or index (CRON.md); requests read only the cache |
| Request on every keystroke, no debounce, no abort | Out-of-order answers overwrite fresh ones; load spikes | Client debounce (~250 ms) + `AbortController` + a sequence check |
| Input echoed into an HTML fragment without escaping | Reflected XSS (also via cross-site POST to the endpoint) | JSON API; client builds DOM with `textContent` |
| `mb_strpos` positions used with `substr`/`strlen` | Broken Cyrillic (byte vs character offsets) | `mb_*` functions throughout |
| Fixed offsets into URLs (`substr($url, 26)`) | Breaks when the domain or scheme changes | `parse_url()` |
| `json_decode()` result used without checks | API errors look like "no data" | Bootstrap error handling + explicit uAPI error codes |
| `ucoz_getinfo()` for the visitor | Legacy-only identity | `ucoz_user()` + visitor key (IDENTITY.md) |
| `$___notjson` / `SCRIPTDATA` output | Wrapper-specific format | Plain JSON with correct status codes (PHP.md §2) |

## 3. Build next to the old code

1. Session start protocol from SKILL.md, then a **new** module name. Never reuse a legacy path.
2. Secrets file with `overwrite:false` → `files_chmod perm:"384"`. Ask the owner for a **new** uAPI key
   (Control Panel → uAPI → create key → dedicated user with read-only rights, only the modules needed).
   Do not reuse the legacy OAuth credentials.
3. Port the logic, not the code: start from `templates/php` and the matching recipe.
4. Inspect real data shapes with MCP before coding (`content_read list per_page=5`). Keep MCP pages
   small: answers above roughly 200 KB come back as `invalid JSON response` — a limit of the MCP proxy,
   not of uAPI. The script itself read `per_page=50` from uAPI without problems (verified).
5. Check data against the source: item count equals `total` from uAPI minus items you deliberately skip
   (verified on a live migration). Links come from `entry_url` via `parse_url()` — they may
   use `https` and custom paths, not the `/publ/…` form the old code expected.
6. Verify on the live URLs (DEPLOY.md §4), on **every** domain from the inventory, including the
   reverse-proxied path (`https://www.example.com/help/apps/{name}/…`).

### Sites behind a reverse proxy (verified)

- `/apps/{name}/` works through the proxy path, so the new endpoint is same-origin with the pages: no CORS.
- The visitor IP still arrives as the first `X-Forwarded-For` entry: an IP hash returned by a health
  endpoint was identical for direct and proxied requests, so per-visitor rate limits work.
- Client code must build URLs relative to where the page is shown, e.g.
  `var PREFIX = location.hostname === '{site}' ? '' : '/help';` for API calls and result links.

### Try it on the real site before switching

Add a temporary page inside the module (`public_html/{name}/demo.html`, `noindex`) that copies the site's
markup and stylesheet and loads the new client script. Test it in a browser on each domain: typing, fast
typing and deleting (exactly one request for the final text), keyboard navigation, links (200 on every
domain). Delete the page after the switch (`files_delete trash:true`, approval).

**Keep the behaviour users rely on.** Test what the old UI did besides the part you replace. Example
(verified): the search form's own submit opened a working results page on the site domain but not on the
proxied domain — so the new script must not intercept submit, or it would break the working case.

## 4. Switch (approval gate)

The switch is the only change visitors see, so show the user the exact diff and get an explicit yes.

1. **Back up the template first:** `templates_write create_backup`. Do not rely on the automatic backup of
   `patch_template` — in a live run none was created (`list_backups` showed `mcp_count: 0`).
2. **Point the include to a new URL instead of overwriting the old file.** Site storage files such as
   `/js/search.js` are served with `Cache-Control: max-age=1728000` (20 days, verified); an overwritten file
   keeps reaching returning visitors for weeks. Change one line in the template, e.g.
   `<script src="…/js/search.js">` → `<script src="https://{site}/apps/{name}/client.js">`
   (`patch_template` with the exact old line as `code_search`). Files under `/apps/` are served without a
   long cache, so later fixes reach everyone at once.
3. Leave the old file and the legacy script untouched: rollback is the same one-line patch in reverse.
4. Verify: fetch the pages and confirm they include the new script; in a browser, check that the feature
   works and that `performance.getEntriesByType('resource')` shows **zero** requests to the legacy endpoint.

## 5. Retire the old code

After the user confirms the new version works:

1. **Revoke the legacy credentials** — the OAuth application/tokens in the site's uAPI settings. Do it
   after the switch: the old code may still be serving visitors until then.
2. Ask whoever has access to the legacy PHP server to delete the old scripts (`search.php`,
   `uAPImodule.php`, …). The agent cannot do it through MCP.
3. Remind the user to delete copies of the old credentials and of the new key from chats, downloads, and
   notes once they are stored in the secrets file.
4. Keep module backups in `domains/{domain}/_backups/` until the user is confident; remove them later with
   approval.

## 6. Pitfalls met during a migration (verified)

| Symptom | Cause | Action |
|---|---|---|
| Every DirectAdmin-backed `apps_read` / `apps_write` / `apps_delete` action (`session`, `files_list`, `db_list`, `cron_list`, `usage`) returns `500 SERVER_ERROR`, while `status` and `login_key_list` work | Fresh Server Scripts account: the `main` login key was auto-created seconds after the account folder, before DirectAdmin accepted it | Explain it and, **with the user's explicit request**, `login_key_rotate key_id=main confirm:true`; the next `files_list` worked |
| Secrets file became `-rw-r--r--` | `files_patch` (and rewrites) reset the mode to 0644 | `files_chmod perm:"384"` after **every** write to a secrets file; check with `files_list` |
| Old and new feature both active | The new client is wired but the template still includes the old script too | Replace the include, do not add a second one |

## 7. Report

Use the final report template from SKILL.md and add a before/after table (response time, what is searched
or processed, uAPI calls per action, security fixes) plus the retirement steps left for the user.

### Reference case: site search migration (October 2026)

A site called `/php/search/search.php` on the legacy server for search hints. It read
every page of `/uapi/publ` (~750 articles) through OAuth 1.0a on every keystroke and matched titles only
(~15 s per request, race between answers, reflected XSS, hardcoded credentials). Replaced by a PHP module with a SQLite FTS5
index rebuilt by cron from uAPI (Bearer key), a JSON query endpoint, and a new client script switched in
with one template line. Result: ~0.7 s per query including network, full-text search with Russian
morphology, keyboard-layout and typo correction, synonyms; about 15 uAPI requests per hourly rebuild
instead of a full scan per keystroke.
