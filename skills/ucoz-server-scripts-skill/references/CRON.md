# Cron

**Verified live:** every form in this file ran — `curl` with a key header to `https://{site}/apps/…`
(arrives with `Host: {tech}`), `php /home/{username}/…/job.php` (PHP 8.3 CLI), and Node/Python scripts run
with absolute interpreter paths (§3). `minute="*"` runs every minute; the first run comes within ~1 minute.

Cron `PATH` is `/usr/local/php83/bin:/home/{username}/.local/bin:…:/usr/bin` — `php` is PHP 8.3, but
**node and python are not on PATH**: use absolute paths.

## 1. Create, list, delete

```text
apps_read / apps_write / apps_delete cron_create minute="*/15" hour="*" dayofmonth="*" month="*" dayofweek="*"
          command="curl -fsS --max-time 50 -H 'X-Cron-Key: <key>' https://{site}/apps/{name}/api/cron >/dev/null 2>&1"
```

- **Always pass all five schedule fields.** Defaults are minute `0` and `*` elsewhere, which means hourly.
- `cron_list` → `jobs[]` (ids `000`, `001`, …) with the **full command line — including keys**. Never repeat
  those lines in chat or reports; describe the job without the key.
- `cron_delete cron_ids=[…] confirm:true` (**C**).
- Times follow the server clock (observed UTC+2). Ask the user what timezone they mean, and convert.
- There is no "edit" action. Create the new job, verify it, then delete the old one (with approval).

## 2. Pattern: cron calls your own HTTP endpoint (recommended, any runtime)

- `-f` makes HTTP errors fail the command, `-sS` keeps it quiet but shows errors, `--max-time 50` stays
  under the ~60 s gateway limit (504).
- Protect the endpoint with a dedicated key (`X-Cron-Key`) checked by `require_key()` (PHP) /
  `requireKey()` (Node) / `require_key()` (Python). They fail closed and answer 404. Never compare with a
  bare `hash_equals($cfg ?? '', $hdr ?? '')` — empty equals empty.
- Make the job **idempotent and resumable**: at most N items per run, store a cursor, exit. Keep each run
  under ~30 s for PHP (`max_execution_time`) and well under 60 s for Node/Python.
- Use a lock so overlapping runs do not double-process: `GET_LOCK('{name}_cron', 0)` in MySQL, or `flock`
  on a file in `{name}_data/`.
- Log one line per run (started, processed N, errors) to the module log.

## 3. Alternative: run a script directly

No HTTP, no public URL, no 60 s gateway limit. Put the script **outside** `public_html` and redirect output
to a log file (nobody sees stdout).

```text
PHP:     php /home/{username}/domains/{domain}/{name}_jobs/nightly.php >/dev/null 2>&1
Node:    cd /home/{username}/domains/{domain}/app_node/{name} && /opt/alt/alt-nodejs22/root/usr/bin/node cron.js >> logs/cron.log 2>&1
Python:  cd /home/{username}/domains/{domain}/app_python/{name} && /home/{username}/virtualenv/domains/{domain}/app_python/{name}/3.11/bin/python cron_task.py >> logs/cron.log 2>&1
```

Environment differences (verified):

| Runtime | In cron |
|---|---|
| PHP CLI | cwd = home; no `REQUEST_URI`; `ucoz_user()` defined but always empty. Use `__DIR__` paths. |
| Node | `require('mysql2')` resolves via the app's `node_modules` when cwd is the app root. **`APP_NAME` is not set**, `require('ucoz')` fails. (The nodevenv binary `~/nodevenv/domains/{domain}/app_node/{name}/22/bin/node` also works and sets `NODE_PATH`.) |
| Python (venv python) | `APP_NAME` **is** set (the venv wrapper applies `env_vars`); `PYTHONPATH` is not, so `import ucoz` fails. Installed packages are available. |

There is no visitor in cron, so identity is irrelevant there. A one-off cron job is also how Python
packages get installed (PYTHON.md §5) — delete it after it ran.

**Verify once:** create the job for every minute, wait ~1 minute, `files_read` its log, then set the real
schedule (create new → verify → delete old).

## 4. Typical jobs

| Job | Schedule | Notes |
|---|---|---|
| Telegram bot polling (`getUpdates`) and posting new materials | `* * * * *` or `*/5 * * * *` | R5. Latency = interval. |
| Clean temp files / old rows | `30 3 * * *` | Delete in batches with `LIMIT`. |
| Recompute "popular this week" | `0 * * * *` | uAPI has no popularity sort. Snapshot `reads`, cache the result. |
| Sync with an external API | `*/30 * * * *` | Back off on errors, respect rate limits. |

## 5. When cron "does nothing"

1. `cron_list`: does the job exist, and is the schedule what you think it is? (The hourly default trap.)
2. Does `command` contain the real full command (absolute paths), not a placeholder?
3. Call the same URL yourself with the header and read the response and the module log; for direct
   scripts read the log the command redirects to.
4. 404 from the endpoint = wrong or short key.
5. 504 or a cut-off run = the job ran past ~60 s / `max_execution_time`. Make batches smaller.
