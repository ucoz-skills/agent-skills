# Architecture: how to structure a Server Scripts app

How the pieces fit together: runtime, files, request flow, where data lives, how background work runs,
how the app talks to the site. Everything here is used in live modules: the recipes, a site search
(PHP + SQLite FTS5, ~750 articles) and a blog dashboard (PHP + MySQL, tens of thousands of comments, a separate
site reading the blog through uAPI).

## 1. Pick the runtime

| Need | Runtime | Why |
|---|---|---|
| Forms, JSON APIs, admin pages, cron targets, uAPI sync, dashboards, search | **PHP** (default) | No process to manage: a saved file is live on the next request. `ucoz_user()` is preloaded. |
| A library that exists only for Node (or a team that writes JS) | **Node.js** | npm via `node_install_modules`. Reload after every change. |
| A Python library: Excel/PDF reports, charts, feed import, data processing (R6) | **Python** | Packages via a one-off cron `pip` job ([PYTHON.md](PYTHON.md) §5). Reload after every change. |

One feature = one module. Several features of one site = several modules side by side
(`/apps/search/`, `/apps/dashboard/`, `/apps/tgbot/`), each with its own data folder and secrets file.

## 2. Files of a module

```text
PHP     domains/{domain}/public_html/{name}/index.php        stub: loads bootstrap + app
                                       /{name}/lib/            code (denied by .htaccess)
                                       /{name}/.htaccess       front controller + denies
                                       /{name}/assets/         css/js served as files (optional)
        domains/{domain}/{name}_data/                         data, logs, locks, SQLite — outside the docroot
        domains/{domain}/{name}_secrets.php                   keys, DB password — mode 0600
Node    domains/{domain}/app_node/{name}/app.js               stub → lib/main.js
                                       /config.json            secrets, 0600 (never served)
                                       /data/ /logs/           state and logs
Python  domains/{domain}/app_python/{name}/passenger_wsgi.py  stub → app.py
                                       /config.json /data/ /logs/
```

Rules that keep this safe: code and data never in the same public folder; secrets only in the 0600 file;
the entry stub stays tiny so a syntax error in the real code is still logged.

## 3. Request flow

```text
https://{site}/apps/{name}/api/<action>
  → proxy strips "/apps" → front controller (index.php / app.js / passenger_wsgi.py)
  → route_path() → action table: [method, handler]
  → checks in this order: method → same-origin (POST) → identity/staff → key (cron/admin) → rate limit → input
  → handler returns data → JSON envelope
```

- **Envelope:** `{"ok":true,"data":{…}}` or `{"ok":false,"error":"code","message":"text for people"}` with a real
  status code (400 input, 401 login, 403 forbidden/origin, 404 unknown or hidden route, 405, 413, 429, 500).
- **Hidden routes:** cron and admin endpoints answer 404 to a wrong key, so they do not reveal themselves.
- **Pages:** a module can render its own HTML page (admin page, dashboard) at `GET /apps/{name}/`; the page calls
  the same module's `api/` with absolute `/apps/{name}/…` URLs.
- **Assets:** serve CSS/JS from the module and add a version: `assets/app.js?v=<ASSET_VERSION>`; bump the
  constant on every asset change, otherwise browsers keep the old file.

## 4. Where data lives

| Data | Store | Pattern |
|---|---|---|
| Small state: bot offset, linked chats, settings, rate-limit buckets | **JSON file + `flock`** in `{name}_data/` | Read-modify-write under `LOCK_EX`; write to `.tmp` + `rename()` for anything bigger than a few KB. |
| Read-mostly index: search, catalogue mirror | **SQLite** (FTS5 available, sqlite 3.53) in `{name}_data/` | Build a **new file** (`index.build.sqlite`) from cron, page by page, resumable across runs; `rename()` it over the live file only when complete and plausible. Readers never see a half-built index. |
| Many writers, relations, history, reports | **MySQL** (MariaDB 10.6) | `db()` + `migrate()` from the bootstrap: a meta table holds the schema version, numbered steps run once. One database per project; names carry the `{username}_` prefix. |
| Time series of a counter uAPI only gives as a total (views) | **MySQL snapshot table** | Cron stores `(taken_at, item_id, value)` hourly; deltas give "per day / week"; show a window only when enough history exists, otherwise show the all-time value and "counting since …". |
| Files uploaded by visitors | `{name}_data/uploads/` + a DB row | Never inside the module folder; serve through a handler that checks access. |

MySQL is not required for small things: the Telegram bot (R5) keeps all state in one JSON file, the
search keeps its whole index in SQLite.

## 5. Site data comes from uAPI, served from a local copy

- **Never call uAPI on a visitor request** for lists, search or stats. Cron copies what is needed into the local
  store; visitor requests read the local copy (fast, no uAPI limits, works if uAPI is slow).
- **Full pass** when the set is small (all posts of a blog, all knowledge-base articles).
  **Incremental** when it is large: e.g. the blog users list is ordered oldest → newest, so read the last pages
  (`total` → last page → backwards) instead of all 2 500+ pages.
- **Another site as the source:** a module on site A can read site B through B's uAPI with a read-only key from
  a dedicated user on B. B is never written to. (The blog dashboard runs this way.)
- **Do not trust list order;** sort by id or date yourself. Decode HTML entities in titles before use.

## 6. Background work = cron

```text
cron (every 1–10 min) → curl -H 'X-Cron-Key: …' https://{site}/apps/{name}/api/cron
  → require_key → non-blocking lock (flock / GET_LOCK) → skip if another run is active
  → do one bounded batch (cursor in state/DB) → log one line → release lock
```

- **No background processes:** every job is a short request. PHP stops at 30 s (`max_execution_time`), any
  HTTP request at ~60 s (gateway). Split work into batches and keep a cursor.
- **Idempotent:** running twice must not duplicate (UNIQUE keys, `INSERT … ON DUPLICATE KEY UPDATE`,
  "id > last processed").
- **Watermark on first run:** a new feed/notifier records "now" and starts from there, so connecting it never
  floods anyone with history.
- **Heartbeat in `/api/health`:** last cron time, items count, build time — the first thing to look at.

## 7. Talking to the outside

- **Outbound** HTTPS works from all runtimes (curl / `fetch` / `urllib`); keep timeouts short (≤ 8–15 s).
- **Cache external data** server-side with a TTL and a lock; on failure keep serving the last good copy.
- **Telegram:** outbound `sendMessage`, inbound by polling `getUpdates` from cron (R5, CAPABILITIES.md §5).
- **Signed webhooks** (R3): verify HMAC + timestamp, store the event in a queue table, answer 200 at once,
  process from cron.

## 8. The site page ↔ the module

- A widget on the site is a `<script src="https://{site}/apps/{name}/client.js">` in a global block (one line,
  easy rollback) that calls `/apps/{name}/api/…`. The site search (R7) is wired this way.
- Visitor identity comes for free on `https://{site}/apps/…` (`ucoz_user()`), never on the technical domain.
  Links to the module end with a slash (`/apps/{name}/`).

## 9. Observability built in

`/api/health` with a BUILD marker and uptime (proves a reload), the module's own `app.log` (rotated at ~1 MB),
`php-error.log` for PHP fatals, `logs/startup.log` from the Node/Python stub. Never log secrets or URLs that
contain tokens.
