# Recipes

End-to-end call sequences. Each recipe states its own test status and where its code lives:
`examples/<recipe>/lib/app.php` (R1, R3, R4, R5), `examples/r6-python-report/` (R6, Python),
`templates/node/` (R2), or no code — procedure only (R9, R10). Each recipe assumes the session start protocol from SKILL.md is done (`status` ok, `{site}`, `{username}`,
`{domain}` known) and that you send write calls one at a time while checking `host` in every response.
**[ask]** marks approval points.

Only build what uCoz does not already do natively. Polls, comments, ratings, mail forms, shop stock and
similar features are built in — configure them instead of scripting them. The reverse also holds: what
the panel cannot do but uAPI data allows (bots, dashboards, top materials, search, digests) is built
here — see [CAPABILITIES.md](../references/CAPABILITIES.md) for the patterns these recipes fit into.

| # | Recipe | Runtime | Code | Status |
|---|---|---|---|---|
| R1 | Lead form → MySQL, staff list + CSV export, Telegram / CRM notifications | PHP | `examples/r1-leads` | ✅ live (guest, member, staff list + CSV) |
| R2 | Members-only per-user data (notes, progress, saved items) | Node | `templates/node` | ✅ live. Stub + `lib/main.js` verified on Server Scripts (health BUILD, guest 401, foreign Origin 403, cron key incl. non-ASCII, private files 404, startup-crash JSON); member POST verified with the earlier single-file app |
| R3 | Signed webhooks → MySQL queue → cron worker | PHP | `examples/r3-webhook-queue` | ✅ live (HMAC + timestamp check, duplicate protection, queue → cron worker) |
| R4 | Open Graph preview images for social networks | PHP + GD | `examples/r4-og-images` | ✅ live |
| R5 | Telegram bot via short polling from cron (+ posting new site materials) | PHP | `examples/r5-telegram-poll` | ✅ live (admin-page link, /start, /latest, new-material post, /stop; 6 parallel polls without Conflict) |
| R6 | Weekly Excel report from site data (uAPI → openpyxl), admin downloads, Telegram delivery | Python | `examples/r6-python-report` | ✅ live (~540 posts, 4 sheets with charts, 8.2 s from cron, Telegram sendDocument) |
| R7 | Smart site search on uAPI data: hints while typing, typos, keyboard layout, synonyms | PHP + SQLite FTS5 | procedure (live module) | ✅ live (~750 articles, ~0.7 s per query) |
| R8 | Dashboard of site materials: top, trends, discussions, users, rhythm, categories | PHP + MySQL | procedure (live module) | ✅ live (~540 posts, tens of thousands of comments, read through uAPI) |
| R9 | Fix a broken module | any | procedure only | ✅ live (PHP rollback; Node/Python startup-crash diagnosis via the stub) |
| R10 | Migrate a script from the legacy uCoz PHP server / another host ([MIGRATION.md](../references/MIGRATION.md)) | any | procedure ([MIGRATION.md](../references/MIGRATION.md)) | ✅ live (legacy OAuth 1.0a search → PHP + SQLite FTS5, switched via template) |

Live tests: October 2026, PHP 8.3 / Node 22 / Python 3.11, on production Server Scripts accounts.

## Common PHP module setup (R1, R3, R4, R5)

```text
1. files_mkdir   domains/{domain}/public_html/{name}/lib
2. files_write   …/{name}/index.php            ← templates/php/index.php (stub)
   files_write   …/{name}/lib/bootstrap.php    ← templates/php/lib/bootstrap.php, APP_NAME='{name}'
   files_write   …/{name}/.htaccess            ← templates/php/.htaccess
   files_write   …/{name}/lib/app.php          ← examples/<recipe>/lib/app.php
   (second module on the same account: files_copy the first module's folder, files_patch APP_NAME,
    then files_write lib/app.php with overwrite:true)
3. files_write   domains/{domain}/{name}_secrets.php  overwrite:false   (keys listed in the recipe header)
   files_chmod   paths=[…secrets.php] perm="384"      (= 0600; the tool takes DECIMAL)
4. Verify: GET https://{site}/apps/{name}/lib/bootstrap.php → 403; the health/main URL → 200
```

Generate every secret randomly (32+ hex chars). `require_key()` refuses keys shorter than 16 chars.

---

## R1. Lead form → MySQL (+ notifications) ✅

**Code:** `examples/r1-leads/lib/app.php`. **Secrets:** `db`, `origins`, `hash_salt`, `staff_gids`,
optional `tg_token` + `tg_chat`, optional `notify_webhook_url` + `notify_secret`.

```text
1. usage → a database slot is free (3 per account); db_list → reuse an app database if one exists
2. db_create_with_user name=main → put the password straight into the secrets file (never echo it)
3. Common PHP module setup (name e.g. "leads")
4. users_read / users_write groups → pick staff group ids → staff_gids
5. Verify (all verified live; every JSON answer is wrapped as {"ok":true,"data":{…}} or {"ok":false,"error":…}):
   GET  /apps/leads/api/health                 → 200 data {"db":true,"leads":N}   (the migration created the table)
   GET  /apps/leads/                           → 200 form page
   POST /apps/leads/api/submit  valid JSON      → 200 data {"saved":true,"id":N}
   POST bad e-mail → 400 "email" · honeypot "website" → 200 (silently dropped) · Origin evil → 403
   POST non-JSON → 400 · GET on submit → 405 · 6th submit in 10 min from one IP → 429
   GET  /apps/leads/api/list, /api/export as guest → 401; as staff → JSON / CSV (UTF-8 BOM, ';')
6. [ask] Wire into the site: a link/button to /apps/leads/ (with the slash) or the form markup in a
   template that POSTs to /apps/leads/api/submit (same origin).
```

**Notifications** (`notify_lead()`): Telegram Bot API message and/or a signed JSON webhook in the R3
format. They are best effort, so a failed notification never fails the submission (verified: fake Telegram
token → logged 401, lead saved). Two synchronous notifications added ~1.5 s to the response. If that
matters, queue them (R3 pattern) and send them from cron. Outbound calls to `api.telegram.org` work; for
a bot that also receives commands and links chats itself, use R5.

How the owner gets Telegram values: create a bot with @BotFather → `tg_token`. Send the bot a message, then
`https://api.telegram.org/bot<token>/getUpdates` → `chat.id` → `tg_chat`.

---

## R2. Members-only per-user data (Node) ✅

**Code:** `templates/node/` — `app.js` (stub), `lib/main.js`, `package.json` (`"type": "commonjs"`),
`config.json` (from `config.example.json`: `origins`, `cron_key`), `index.html` (replace `{name}`).

```text
1. visitor_key_get → present? (read only `present`) · [ask] visitor_key_create if absent
2. files_list domains/{domain}/public_html → "notes" must not exist
3. files_mkdir domains/{domain}/app_node/notes/lib; files_write app.js, lib/main.js, package.json,
   config.json (files_chmod "384"), index.html
4. node_create app_root=domains/{domain}/app_node/notes app_uri=/notes version=22 startup_file=app.js
               env_vars={"APP_NAME":"notes"}                       ← required
5. Verify: /health 200 with the BUILD from lib/main.js · / and /apps/notes (no slash) serve index.html
   GET /api/items 200 · POST as guest → 401 · foreign Origin → 403
   /config.json, /package.json, /logs/app.log → 404
   POST as a logged-in member → 200 (the user opens https://{site}/apps/notes/ while logged in ON THE SITE)
```

The template stores data in a JSON file (one process). For more data or concurrent writers use MySQL
(`mysql2` via `node_install_modules`, verified with code — MYSQL.md §5) and key rows by `ucoz_user().id`.

---

## R3. Signed webhooks → queue → cron worker ✅

**Code:** `examples/r3-webhook-queue/lib/app.php`. **Secrets:** `db`, `webhook_secret`, `cron_key`,
`staff_gids`, `hash_salt`.

Sender contract (give it to whoever sends events):

```text
POST https://{site}/apps/{name}/api/hook
Content-Type: application/json
X-Event-Id:  <unique id; retries reuse it>
X-Signature: t=<unix time>,v1=<hex HMAC-SHA256(webhook_secret, "<t>.<raw body>")>
```

```text
1. Common PHP module setup (name e.g. "inbox"); same database as other modules is fine (tables queue_*)
2. Verify (verified live):
   valid signed event → 202 {"queued":true} · same X-Event-Id again → 200 {"duplicate":true}
   wrong secret / body changed after signing / timestamp older than 5 min / no signature → 401
   missing X-Event-Id → 400
   GET /api/work without X-Cron-Key → 404 · with key → {"done":N} · again → {"done":0}
3. [ask] cron_create minute="*" hour="*" dayofmonth="*" month="*" dayofweek="*"
      command="curl -fsS --max-time 50 -H 'X-Cron-Key: <cron_key>' https://{site}/apps/inbox/api/work >/dev/null 2>&1"
4. Put the business logic into process_event(); throw to retry (up to 5 attempts, then "failed").
```

Worker safety: `GET_LOCK` prevents overlapping runs, `locked_until` releases events stuck in "working",
and each run is capped at 20 events. Chain tested end to end: R1 → signed webhook → R3 queue → worker.

Inbound reachability: the receiver was verified with a sender on the same account; other external clients
reach `/apps/…` URLs (verified with an external fetcher), but **Telegram cannot** — use R5 for Telegram.
Before calling an integration done, have the real provider deliver one event. To test without a shell, see
[TESTING.md](../references/TESTING.md) §3 (temporary signed sender).

---

## R4. Open Graph preview images ✅

**Code:** `examples/r4-og-images/lib/app.php`. **Secrets:** `admin_key`, `hash_salt`, `site`, optional `og`
(colors `bg1`, `bg2`, `fg`, `muted`, `accent`, `site_name`). No uAPI key.

**How it works:** the image URL is the material's own path plus `.jpg`. The module opens that public page,
takes its `og:title`, draws a 1200×630 JPEG once and caches it for a day. Only material paths of the site are
accepted and the text comes from the site's own page, so nobody can render arbitrary text on your domain.
Never put the title into the URL (`?t=$ENTRY_NAME$`): uCoz templates do not evaluate functions such as
`str_replace` / `htmlspecialchars` (verified: they are printed as text), so the title cannot be URL-encoded
and quotes or `&` break the link — and a text parameter lets anyone draw any text on your domain.

```text
1. Common PHP module setup (name e.g. "og"); secrets admin_key, hash_salt, site.
2. GET /apps/og/api/setup with X-Admin-Key → downloads PT Sans (Cyrillic) into og_data/fonts
   (apps_read / apps_write / apps_delete cannot upload binaries — the module fetches them itself). Response shows gd.freetype=true.
3. Verify the image on a real material path:
     /apps/og/blog/<slug>/<yyyy-mm-dd>-<id>.jpg → image/jpeg 1200×630 (~75 KB); download and look at it
     /apps/og/blog/nope/<date>-999.jpg → 404; anything that is not a material path → 404
4. [ask] templates_write create_backup → patch_template the module's material page template
   (Blog: «Страница материала и комментариев к нему», module_id 7, template_id 3), inside <head>:
     <meta property="og:type" content="article">
     <meta property="og:title" content="$ENTRY_NAME$">
     <meta property="og:image" content="https://{site}/apps/og$REQUEST_URI$.jpg">
     <meta property="og:image:width" content="1200"><meta property="og:image:height" content="630">
   In the blog the title variable is $ENTRY_NAME$ (check the variable list of the template you edit:
   read_template returns it). $REQUEST_URI$ is the page path, e.g. /blog/my-first-post/2026-01-15-12.
5. Open a material page, check the rendered og:image URL, and open it: 200 image/jpeg.
   A shared link with a query (?utm_source=…) renders as …/2026-10-04-8?utm_source=tg.jpg — the module
   handles that form too.
```

After changing the design, bump `DESIGN_V`: every cached image is redrawn on its next request.

Verified live (October 2026, on a blog): images for real posts, 404 for unknown paths and for the
removed `?t=` route, `..` → 403, the query-string form, the template tags rendered on the post page.

---

## R5. Telegram bot via short polling (PHP) ✅

**Code:** `examples/r5-telegram-poll/lib/app.php` — its header lists the exact routes and secrets.
**Secrets:** `tg_token`, `cron_key`, `staff_gids`, `origins`; optional `site` + `uapi_token` + `uapi_module`
(posting new site materials and `/latest`). **State:** `{name}_data/state.json` (no database).

**Why polling:** Telegram's servers cannot reach Server Scripts (webhook delivery timed out on the site and
technical domains, verified 2026-10-08), while outbound calls to `api.telegram.org` work. The rule set is in
[CAPABILITIES.md](../references/CAPABILITIES.md) §5.

```text
1. The owner creates a bot with @BotFather. The token goes straight into the secrets file (0600) and is never
   echoed. One bot token = one consumer: getUpdates hands each update to whoever polls first, so never point
   two modules (or a module and another bot framework) at the same token.
2. Common PHP module setup (name e.g. "tgbot") with examples/r5-telegram-poll/lib/app.php. Secrets as above;
   staff_gids from users_read / users_write groups; origins = ["https://{site}"]; uapi_token from a dedicated read-only
   uAPI user (UAPI.md §1) if materials are wanted.
3. Verify: GET /apps/tgbot/api/health → bot username; GET /apps/tgbot/api/cron without X-Cron-Key → 404,
   with the key → 200 {"updates":0,"announced":0}; GET /apps/tgbot/ as a guest → 401; lib/ → 403.
   The first cron call runs deleteWebhook once per token (getUpdates answers 409 while a webhook is set)
   and sets the materials watermark (nothing is sent).
4. [ask] cron_create minute="*" (or "*/5")
     command="curl -fsS -m 50 -H 'X-Cron-Key: <cron_key>' https://{site}/apps/tgbot/api/cron >/dev/null 2>&1"
5. Linking: an admin, logged in on the site, opens https://{site}/apps/tgbot/ → "Link Telegram" → opens the
   t.me link → Start. The page polls every 4 s, so the reply "Done: this chat is linked" comes within seconds.
6. Each cron run: getUpdates(offset, timeout=0) → store offset+1 BEFORE handling each update → /latest,
   /stop, unknown chats get a refusal → new materials via uAPI (id > watermark, oldest first, ≤5 per run;
   with no linked chat the watermark just moves, so a later link gets no backlog) → sendMessage (HTML, escaped).
7. Verify with a real chat: /latest (reply after ≤ one cron interval), one "New: …" message after a new
   material, /stop → "Unlinked". Latency for commands = the cron interval.
```

---

## R6. Weekly Excel report in Python (cron → uAPI → openpyxl → Telegram) ✅

**Code:** `examples/r6-python-report/` — `report.py` (the cron job), `app.py` (admin page + downloads),
`requirements.txt`; entry stub `templates/python/passenger_wsgi.py`.
**Config:** `config.json` (0600): `site`, `uapi_token` (read-only), `staff_gids`; optional `tg_token` + `tg_chat`.

**Why Python:** openpyxl builds a real `.xlsx` — styled header, number and date formats, filters, frozen
header row, links, data bars and charts. The job runs from cron as a plain script, so the ~60 s HTTP
limit does not apply. Use the same shape for any library-driven job: PDF, charts, feed import, data crunching.

```text
1. python_create app_root=domains/{domain}/app_python/{name} app_uri=/{name} version=3.11
                 startup_file=passenger_wsgi.py entry_point=application env_vars={"APP_NAME":"{name}"}
   (on an MCP timeout: python_list before retrying)
2. files_mkdir …/{name}/logs; files_write passenger_wsgi.py (stub), app.py, report.py, requirements.txt;
   config.json overwrite:false → files_chmod "384".
3. [ask] One-off cron (minute="*", all five fields):
     cd /home/{user}/domains/{domain}/app_python/{name} &&
     /home/{user}/virtualenv/domains/{domain}/app_python/{name}/3.11/bin/pip install -r requirements.txt > logs/pip.log 2>&1;
     /home/{user}/virtualenv/domains/{domain}/app_python/{name}/3.11/bin/python report.py --no-send >> logs/cron.log 2>&1
   → after ~1 minute read logs/pip.log and logs/cron.log → cron_delete (C).
4. Reload the web part (files_write tmp/restart.txt) → /apps/{name}/health shows "last" (file, posts, seconds).
5. Check access: guest → 401 on /, /api/reports, /report/<file>; config.json, report.py, logs/ → 404.
   An admin logged in on the site opens /apps/{name}/ and downloads the file.
6. [ask] Telegram: add tg_token/tg_chat to config.json, run report.py once (without --no-send) → the bot
   sends the file with a caption (sendDocument).
7. [ask] Schedule: cron minute="0" hour="6" dayofmonth="*" month="*" dayofweek="1" (Monday 06:00) with
   the command `cd … && …/3.11/bin/python report.py >> logs/cron.log 2>&1`.
```

Verified live: ~540 blog posts read through uAPI (6 pages of 100) and written to a 65 KB workbook with four
sheets (Сводка, Записи, Рубрики, По годам) in 8.2 s from cron; packages installed by the cron pip job;
guest 401, private files 404; the file arrived in Telegram via sendDocument (9.1 s including the upload).

---

## R7. Smart site search on uAPI data (search-as-you-type) ✅

**What it gives:** hints under the site's search field while the visitor types: whole text of the
materials, typos, the wrong keyboard layout, word forms and synonyms, matches highlighted. The built-in
search of the site stays as it is (Enter still opens its results page).
**Shape:** PHP module (`templates/php`) + SQLite FTS5 index in `{name}_data/` + cron + one client script
wired into a global block. **Secrets:** `site`, `uapi_token` (read-only, the content module only), `cron_key`.
Live module: a site with ~750 articles (`publ`), hints in ~0.7 s per query including the network.

```text
1. Inspect the module through MCP (content_read list module=<publ|blog|news…> per_page=2): list key
   (publs / blogs / …), entry_url, title, category, message/description, reads, pending.
2. Index build (GET /api/reindex, X-Cron-Key, cron every 10 min):
   - rebuild when the live index is older than ~55 min; otherwise answer "fresh";
   - write a NEW file index.build.sqlite page by page within ~20 s per run (PHP stops at 30 s), keep a
     state file, continue on the next run; per_page 50, shrinking to 25 / 5 if uAPI returns broken JSON;
   - skip pending entries; text = description + message without tags, entities decoded, $VARS$ removed;
   - refuse the swap if the new index has < 80% of the current documents; otherwise rename() it over
     index.sqlite (readers never see a half-built index);
   - tables: docs (id, path, title, category, reads, boost), fts5(title, keywords, category, body,
     tokenize "unicode61 remove_diacritics 2"), fts5vocab → words (term, df) as the spelling dictionary.
3. Query (GET /api/query?q=…, rate limit per IP hash, Cache-Control 60 s):
   - words: lower case, ё → е, stop words dropped, a light Russian stemmer → prefix search ("подключ"*);
   - unknown words → try the other keyboard layout ("ajhev" → "форум"), then the closest dictionary word
     (same first letter, 1–2 edits); show "Показаны результаты по запросу …" when the query was changed;
   - synonym groups (бэкап ↔ резервная копия, ссл ↔ SSL) as OR alternatives;
   - order: titles containing more query words first, then bm25 (title 10, keywords 5, category 3, body 1)
     minus a popularity bonus 0.3·ln(1 + reads); no hit for all words → fall back to any word;
   - answer: title, path, category, snippet with match markers.
4. Client script (/apps/{name}/client.js): debounce ~250 ms, AbortController + a sequence number (only the
   answer for the latest text is shown), results built with DOM nodes (no innerHTML), arrow keys + Enter,
   the list takes the form width on phones, the form's own submit untouched.
5. Phase C (TESTING.md §3): a noindex demo page inside the module with the site's markup → [ask] switch:
   template backup → one <script src="https://{site}/apps/{name}/client.js"> line in a global block.
```

Verified limits: a typo in the first letter of a word is not corrected (the dictionary lookup keys on it).
Migrating an old search script to this shape: R10 and [MIGRATION.md](../references/MIGRATION.md).

---

## R8. Dashboard of site materials (top, trends, discussions, users) ✅

**What it gives:** a staff-only page with what the Control Panel does not show: the most read materials
all-time and for the day / week / month, rising and declining materials, where readers discuss, whether the
team answers, who registers and comes back, when materials are published, how categories perform, and a
card for every material. **Shape:** PHP module + MySQL + cron + the module's own page and JSON API.
**Secrets:** `site` (the source site), `uapi_token` (read-only), `db`, `cron_key`, `staff_gids`.
Live dashboard: a large blog (hundreds of posts, tens of thousands of comments and users), running on a
separate site and reading the blog only through uAPI (the blog itself is never written to).

```text
1. Data (cron every 10 min → GET /api/cron, X-Cron-Key, non-blocking lock, ~22 s of uAPI work per run):
   - materials: all pages (per_page 100) about once an hour; categories with names;
   - hourly snapshot table (taken_at, item_id, views, comments) — uAPI gives only all-time counters, so
     every "per day / week / month" figure is a difference of snapshots; keep hourly rows ~14 days, then
     one row per day;
   - comments: full history once, then incremental per material; "team" = authors of the materials plus
     extra nicks from secrets, so team replies can be counted;
   - users: the list runs oldest → newest, so read the last ~10 pages (per_page 100) hourly;
   - never store e-mails, IPs or phones; migrations as numbered steps in a meta table (MYSQL.md).
2. Metrics (one JSON action per screen, staff only via ucoz_user() gid in staff_gids):
   - summary: all-time views, comments, users, views by year of publication, the most read materials;
   - top: all-time, gain for 24 h / 7 d / 30 d, trending (last 24 h vs the daily average of the 7 days
     before), evergreen (older than a year and still gaining), declining (popular, no gain);
   - discussions: discussed this year, most active commenters with the last date, share of threads with a
     team reply and the median reply time;
   - users: registrations by month, share who came back after registering, share with an avatar;
   - rhythm: publications by weekday × hour; categories: average views per material, last publication;
   - material card: rank in the site and in its category, comparison with the category, comments by month.
3. Windows by data: on a quiet site "last 30 days" is empty — show the year or all time instead, and show
   gains only once the snapshot window is complete ("counting since …").
4. Page: the module renders it at /apps/{name}/ with versioned assets (?v=ASSET_VERSION), light/dark theme,
   phone layout; outbound alerts to Telegram follow R5 (polling, never webhooks).
```

The dashboard can live on the same site or on another one; the source only needs a read-only uAPI key.
Design notes: [CAPABILITIES.md](../references/CAPABILITIES.md) §5, storage choices: [ARCHITECTURE.md](../references/ARCHITECTURE.md).

---

## R9. Fix a broken module ✅

```text
1. Reproduce: HTTP request → status, Content-Type, first bytes.
   PHP blank 500 text/html = error before bootstrap (entry file) → check index.php is the stub.
   Node/Python Passenger HTML "Web application could not be started … Error ID" = startup crash without
   the entry stub → install the stub (templates/node/app.js, templates/python/passenger_wsgi.py), reload.
   JSON {"error":"startup","detail":…} = the stub caught a startup crash → logs/startup.log.
   JSON {"error":"server"} = caught in a handler → the module log has the reason.
   504 at ~60 s = request too long → cron batches.
2. Logs (files_read):
   PHP:          domains/{domain}/{name}_data/app.log  ("syntax error … app.php:N") and php-error.log (fatals)
   Node/Python:  …/app_node|app_python/{name}/logs/startup.log, logs/app.log  + *_list app_status
3. Match the symptom in references/TROUBLESHOOTING.md
4. Back up (files_copy / files_archive) → files_patch the fix → Node/Python: bump BUILD + reload
   → re-run the failing request + a happy path; /health shows the new BUILD
5. If the fix fails: files_extract the archive (confirm) and verify again — rollback verified live.
```

Verified: PHP rollback via archive; Node and Python syntax errors in `lib/main.js` / `app.py` reported by the
stub as JSON `detail` plus the full stack with line in `logs/startup.log`. Also verified: wrong `files_chmod` value → "Permission denied" on the secrets file. A `@mkdir` turned into a fatal
error by a strict error handler (fixed in the template). An empty key check left an endpoint open (fixed by `require_key`).

---

## R10. Migrate a script from the legacy uCoz PHP server / another host ✅

Full procedure, legacy patterns and verified pitfalls: [MIGRATION.md](../references/MIGRATION.md). In short:

1. **Inventory (read-only):** the caller (template include, site JS), contract, data sources, secrets,
   cron, every domain incl. reverse-proxied paths; measure the old endpoint live. Legacy-wrapper calls
   (`ucoz_getinfo`, `$___notjson`, `SCRIPTDATA`) and OAuth 1.0a `uAPImodule.php` are rewritten, not ported:
   identity → `ucoz_user()`, uAPI → Bearer key, output → plain JSON.
2. **Build next to it** under a **new** `{name}`: secrets (new uAPI key, never the old credentials),
   storage (`db_create_with_user` + migration in code, or a SQLite/JSON cache built by cron), code from
   `templates/php`. Verify the API and a temporary `noindex` demo page on every domain.
3. **[ask] Switch:** `templates_write create_backup`, then `patch_template` the one include line to the new
   `/apps/{name}/…` URL — do not overwrite the old site-storage file (20-day browser cache). Verify zero
   requests to the legacy endpoint.
4. **Retire:** the user revokes the old credentials and deletes the legacy files; keep backups until the
   user is confident; remove the demo page (**C**).

Live run (October 2026, PHP 8.4): a site's search hints moved from `/php/search/search.php`
(OAuth 1.0a, full `/uapi/publ` scan per keystroke, ~15 s) to `/apps/search/` (SQLite FTS5 index rebuilt by
cron, ~0.7 s per query incl. network, typo/layout correction), switched with one template line, no downtime.
Build the module from `templates/php` following MIGRATION.md.
