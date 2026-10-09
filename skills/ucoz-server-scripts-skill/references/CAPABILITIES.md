# Beyond built-in features: what Server Scripts + uAPI unlock

Users often ask for things the uCoz Control Panel cannot do: "a Telegram bot that answers with our
articles", "a dashboard of the most read materials", "a better search", "send new posts to our channel",
"a personal cabinet with saved items". If the data or the action exists in the site **uAPI**, or the
feature only needs code, storage, a schedule, or an external service, it can be built in **Server
Scripts**. Never answer "uCoz cannot do this" before checking that route.

## 1. The rule

1. Prefer what uCoz already does: a module setting, a built-in feature, a template change. Do not script
   polls, comments, ratings, mail forms, or shop stock.
2. When the built-in tools stop short, **do not refuse and do not fake it in template JavaScript** (no API
   keys or tokens in pages, no heavy client-side scraping). Build it in Server Scripts.
3. Check availability first: `apps_tool status`. `available: false` → tell the user the feature is built
   with the Server Scripts service, which they enable in the Control Panel (MCP cannot buy it).
4. Name the trade-offs in one short block before building: what runs where, what key is needed, what it
   stores, how fresh the data is, who can see it.

## 2. Decision ladder

| Step | Use when | Tool |
|---|---|---|
| 1. Built-in feature or module setting | uCoz already does it | Control Panel / `modules_tool` |
| 2. Template / design change | presentation only, data already on the page | `templates_tool` (design skills) |
| 3. One-off operation by the agent now | import, bulk edit, a report for the user today | MCP tools (`content_tool`, `users_tool`, `shop_tool`) — no script |
| 4. **Server script** | must run **without the agent**: on a visitor request, on a schedule, on an external event; needs storage, secrets, aggregation, or an external API | `apps_tool` + uAPI key + `ucoz_user()` |
| 5. External service | needs something the environment cannot run (no shell, no background workers, nothing longer than ~60 s per request) | the script calls it over HTTPS |

Step 3 vs 4 is the same distinction as [UAPI.md](UAPI.md) §0: the agent's MCP access ends with the session;
a script keeps working.

## 3. What becomes possible

| Pattern | Examples users ask for | Building blocks | Start from |
|---|---|---|---|
| **Cron → cache → widget / API** | top or trending materials, "popular this week", related items, counters, a full-text search | uAPI key, cron, JSON/SQLite/MySQL cache, JSON endpoint, small client script wired by one template line | R7 (search), [MIGRATION.md](MIGRATION.md) |
| **Telegram bot** | bot that finds articles or posts new materials, notifications to a chat | cron + key-protected poll endpoint (`getUpdates`), uAPI reads, outbound `sendMessage` — **no webhook** (§5) | R5, R1 notifications |
| **Inbound webhook** | payment or CRM callbacks, form builders, another site | endpoint with HMAC signature + queue + cron worker | R3 |
| **Scheduled job** | digest of new materials to a channel or mail service, nightly export, cleanup, sync with an external catalog | cron + key-protected endpoint or CLI script, cursor + lock | R5 (new materials), R6 (Excel report), [CRON.md](CRON.md) |
| **Members-only app** | personal cabinet, saved items, progress, member-only downloads | `ucoz_user()` (visitor key), MySQL per-user rows | R2, [IDENTITY.md](IDENTITY.md) |
| **Staff tool / dashboard** | content statistics, moderation queue, lead lists, CSV export | `require_staff()` or an admin key, MySQL, read-only uAPI | R8 (dashboard), R1 staff list, §5 below |
| **Integration** | push orders or leads to a CRM, pull prices from a supplier, mirror materials to another platform | uAPI read/write with a scoped key, signed webhooks, queue + worker | R3 |

uAPI covers the site's content modules (materials, comments, categories, catalog/shop data, users,
forums, polls, and more — the MCP `content_tool`, `users_tool`, `shop_tool` are thin wrappers over it).
What a given site exposes depends on its modules and on the rights of the key's user. Confirm every
endpoint and field with `docs_tool` and a live MCP call before designing around it; never assume one.

## 4. Data facts that shape the design (verified, October 2026)

- Catalog materials (`/uapi/publ`) carry `reads` (cumulative views), `rating`, `rate_num`, `rate_sum`,
  `comments_num`, `add_date_ts`, `mod_date_ts`, `category`, `pending`, `entry_url`. The same holds for other
  content modules only after you check them live.
- **No popularity sort and no per-period statistics.** Lists come newest first. `reads` is a running
  total, so "most read this week" needs your own **daily snapshots** of `reads` and a difference between
  them. Start collecting snapshots as soon as the feature is agreed — history cannot be recovered later.
- Unpublished items have `pending: "yes"`; exclude them from anything public.
- A full read of a 750-item catalog takes ~15 requests at `per_page=50` and ~15 s; do it from cron, never
  on a visitor request.
- **Every request is cut at ~60 s** (nginx 504; PHP stops at 30 s by default). Long jobs run from cron in
  batches with a cursor and a lock, and resume on the next run.
- MCP answers above ~200 KB fail with `invalid JSON response`; inspect data with small `per_page`.

## 5. Two designs to start from

### Telegram bot (verified rule: polling, not webhooks)

- **Webhooks do not work.** On 2026-10-08 Telegram could not deliver to Server Scripts
  (`getWebhookInfo`: "Connection timed out") on both `https://{site}/apps/…` and `https://{tech}/…`, while
  another external client reached the same site's `/apps/…` URL. Outbound calls to `api.telegram.org` work.
- **Short polling from cron:** a key-protected endpoint (`require_key('cron_key','X-Cron-Key')`) calls
  `getUpdates` with `offset` and `timeout=0`; cron hits it every minute (or every 5). Latency = the cron
  interval. No long polling: there are no background workers and requests end at ~60 s.
- **Once per token:** `deleteWebhook` — while a webhook is set, `getUpdates` answers 409.
- **One poller at a time:** two parallel `getUpdates` calls for the same token make Telegram answer
  "Conflict: terminated by other getUpdates request" (verified: cron + the admin page polling together).
  Guard the poll with a non-blocking `flock`, and never let two modules or bots poll one token — each update
  goes to whoever polls first.
- **Offset:** store it and advance it **before** handling each update, so a crash never replays a command.
- **Chat binding:** `/start <one-time code>` (random, short-lived) links a chat to the site; `/stop` unlinks.
  Never trust a chat id the user types.
- **Token:** in the secrets file, or entered by an admin in a staff-only, same-origin, rate-limited form,
  checked with `getMe`, stored in `{name}_data/` with mode 0600. Never echoed in pages, logs, or chat.
- **Watermark:** keep a "notify since" id or timestamp, so linking a chat never sends old events.
- **Data:** answers read a local cache or index built by cron from uAPI (the MIGRATION search module can
  serve as the backend), not live uAPI per message. Posting new materials: compare the newest `id` with the
  last one sent, send only new items, log each run.
- **Ask first:** which chat or channel, who may use the bot, what it may reveal (public materials only).
- Recipe: R5.

### Dashboard of top materials (pattern, used in a live blog dashboard)

- **Collect:** cron once a day (or hourly) reads all published materials and stores
  `(material_id, date, reads, comments_num, rating, rate_num)` in MySQL (or SQLite for a small site).
  Idempotent per date: re-running the same day updates, never duplicates.
- **Compute:** top by views for 1/7/30 days = difference of snapshots; all-time top = latest `reads`;
  also top by comments and rating, new materials gaining fastest, materials with zero growth.
- **Show:** a staff-only page at `/apps/{name}/` (`require_staff()`, which needs the visitor key and staff
  group ids — IDENTITY.md), JSON endpoints for charts, CSV export. A public "popular this week" widget can
  read the same table through a cached endpoint and be wired with one template line.
- **Say clearly** that period statistics start from the first snapshot.

## 6. Limits to state up front

- No shell, no SSH, no background workers; code runs on HTTP requests (≤ ~60 s) and cron only. Python
  packages need a one-off cron `pip` job (PYTHON.md §5).
- Inbound webhooks: generic senders reach the site (verified with an external fetcher and a same-account
  sender); **Telegram does not** — use polling.
- Quotas per account (disk, inodes, 3 MySQL databases on the observed plan) — read `usage` first.
- uAPI keys inherit their user's rights: create a dedicated low-privilege user per integration.
- Server Scripts cannot change how uCoz renders its own pages; they add endpoints, widgets, and jobs that
  the site uses through templates.
- Personal data (members, leads, orders) stays server-side, behind auth, out of logs and chat.
