# Testing: before deploy, after deploy, after switching the site

"Saved" is not "working". Tests run in four places, in this order:

| Phase | Where | What it catches |
|---|---|---|
| **A. Before deploy** | the agent's machine, if it has a shell and the runtime | syntax errors, logic bugs on real data |
| **B. After deploy, before the site uses it** | the module's own URL `https://{site}/apps/{name}/…` | environment, routing, secrets, DB, access rules |
| **C. Before switching the site** | a demo page inside the module | the real look and behaviour next to the site's markup |
| **D. After switching** | the site pages | wiring, caching, every domain the site is served on |

Visitors see nothing until phase D: a new feature lives at its own `/apps/{name}/` URL and only the switch
(a template line, approval gate) exposes it.

## 1. Before deploy (phase A)

Only when the agent has a shell with the runtime. Without one, go straight to phase B — it is enough,
because nothing is wired to the site yet.

- **Syntax of every changed file:** `php -l lib/app.php` (catches what would otherwise be a 500 on the
  server); for Node/Python, start the app locally — a syntax error stops the start.
- **Run Node/Python locally** with the same entry stub: `APP_NAME={name} node app.js` (the fallback port
  3000 is used locally; on the server Passenger takes over `listen()`), or serve `application` with
  `wsgiref.simple_server` and `APP_NAME` set. Request `/health`, the main route, a bad input, a wrong key.
  `require('ucoz')` / `import ucoz` are absent locally: the templates treat that as a guest.
- **Logic on real data:** take a small real sample through MCP (`content_tool action=list per_page=5`,
  never more than ~200 KB per call) and run the processing code on it locally. In a live site
  search this found three bugs before the first deploy.
- **No secrets locally:** use placeholders; real keys exist only in the server-side secrets file.

## 2. After deploy (phase B)

Run real HTTP requests against `https://{site}/apps/{name}/…` (the agent's fetch/HTTP tool, or curl when a
shell exists), read the module's own logs with `files_read`, and report what was checked. On the server
there is no shell: every test is an HTTP request, a log read, or a cron run.

### 2.1 Smoke checklist (all runtimes)

| # | Check | Expect |
|---|---|---|
| 1 | `GET /apps/{name}/health` | 200 JSON with the right `Content-Type`. Node/Python: the new `BUILD` and a small `uptime` (proves the reload; Python pids vary between requests) |
| 2 | Happy path (POST a valid form / call the main API) | 200 `{"ok":true…}`; the data shows up in the list/admin endpoint or log |
| 3 | Bad input (missing field, non-JSON, wrong method) | 400 / 405 JSON with an error code — not 500, not HTML |
| 4 | Protected route without key / login / allowed Origin | 404 (key routes) or 401/403 JSON |
| 5 | Private files: PHP `lib/bootstrap.php`, `*.log`, `*.json`; Node/Python `config.json`, `package.json`, `logs/app.log`, `app.py` | 403 or 404 — never the content |
| 6 | Logs: PHP `{name}_data/app.log` + `php-error.log`; Node/Python `logs/app.log` + `logs/startup.log` | No new errors |
| 7 | Node/Python `*_list` | `app_status: "started"` |
| 8 | Long work | Every request well under 60 s (504 at ~60 s; PHP stops at 30 s). Anything slower → cron batches |

Expect ~2.5 s on the first Node/Python request after a reload; warm requests ~0.6 s.

### 2.2 Per runtime

**PHP** — no reload needed (opcache off). Check that `/apps/{name}` without a slash 301-redirects and always
link with the slash. A parse error in `lib/app.php` must come back as JSON 500 with `app.php:N` in
`app.log`; a fatal (memory, time) appears in `php-error.log`. Unknown environment details: deploy
`templates/probe/probe.php` temporarily (IDENTITY.md §9), then delete it.

**Node** — after a change: bump `BUILD` → `node_restart` (or write `tmp/restart.txt`) → `/health`.
Startup-crash check: if the response is JSON `{"error":"startup"}` or the Passenger "could not be started"
page, read `logs/startup.log`. After `node_install_modules`, request a route that uses the package.

**Python** — same as Node with `python_restart`. Never trust the pid; use `BUILD` + `uptime`. After the cron
`pip` job: read `logs/pip.log`, delete the job, reload, call a route that imports the package.

### 2.3 Feature checks

- **MySQL round trip:** health or a test action that connects, runs the migration, inserts and reads one
  row (R1 `/api/health` returns the row count). Duplicate insert on a UNIQUE key → handled, not 500.
- **Cron:** create the job with `minute="*"` (all five fields), wait ~1 minute, `files_read` the log the job
  writes, then set the real schedule (create new → verify → delete old, **C**). Also call a cron URL
  without the key → 404.
- **Identity (`ucoz_user()`):** guest = request the URL without a site login → 401 on member routes.
  Member = someone logged in **on the site** (`/index/1`) opens `https://{site}/apps/{name}/…` — ask the user
  to do it and tell you the result, or use a browser tool on the user's machine where they are logged in.
  The agent's own fetch is always a guest. Requests to `{tech}` are always guests.
- **Signed webhooks without a shell:** deploy a temporary key-protected PHP sender in the same module
  (e.g. `/api/test_send`) that builds the body, signs it with `hash_hmac('sha256', "$t.$body", secret)` and
  POSTs it to the receiver; check 202, duplicate → 200, tampered body → 401. Delete the sender afterwards.
  A real third-party sender should deliver one event before you call the integration done — Telegram
  cannot (use polling).

## 3. Before switching the site (phase C)

For anything that changes what visitors see (a widget, a form, a replaced script): add a temporary
`public_html/{name}/demo.html` (`noindex`) that copies the site's markup and stylesheet and loads the new
client script; test it in a browser on every domain the site is served on (MIGRATION.md §3). Show it to
the user, then switch with one template line after approval, and delete the demo page.

## 4. After switching (phase D)

- Fetch the site page and confirm it references `https://{site}/apps/{name}/…`.
- Open it in a browser (or ask the user) and use the feature as a visitor; on a reverse-proxied domain too.
- Watch the module log for the first real requests; keep the template backup until the user confirms.

## 5. Report

List what was checked with status codes, which checks were left to the user (member login, real
provider), and where logs live. Remove temporary probes, senders, and demo pages (with approval).
