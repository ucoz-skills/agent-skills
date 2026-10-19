# Troubleshooting

Start with the cheapest signal: `apps_read` `status` → the HTTP response of the URL → the module's
own log (`files_read`) → `*_list` for Node/Python.

## Environment and tool

| Symptom | Cause / fix |
|---|---|
| `status.available: false` | Service not available for the site. MCP cannot buy it. The owner enables it in the Control Panel. |
| `activated/ready: false` | Ask, then `ensure_ready confirm:true`. |
| `files_list` → "path is required" | `path` is mandatory. Use `domains`, then `domains/{domain}/public_html`. |
| `files_write` / `files_list` → HTTP 409 | The path or its parent folder does not exist. `files_mkdir` the parent, then retry. |
| MCP call → HTTP 502 (Cloudflare "Bad gateway") | Proxy overload, typical for parallel writes or slow actions. Wait, check the real state, then retry **one** call. Never blindly repeat a non-idempotent patch. |
| A write landed on another site (`host` in the response differs) | The shared site selection was changed by another client. Stop, tell the user, undo on the wrong site, `select_site` back, continue. |
| `files_move` / `files_extract` → "confirm is required" | Both need `confirm: true` after approval. |
| Files written but the URL 404s | Wrong `{domain}` folder (built from `status.domain` instead of `session.selected_domain`), or a path outside `public_html`. `files_list` the folder and compare. |
| A path like `/home/u…/domains/…` is rejected | `apps_read` / `apps_write` / `apps_delete` paths are home-relative. Drop `/home/{username}/` (except in `cron_create command` and `app_root`). |
| `files_patch` fails | `old_string` not found or not unique. `files_read`, copy the exact fragment, and add surrounding lines to make it unique. |
| `files_read` returns a URL instead of content | Large or binary file — the URL is **public**. Do not share it. Read smaller text files instead. |
| A response is enormous (`php_get`) | Expected (~100 KB). Summarize only the fields you need. |

## URLs and routing

| Symptom | Cause / fix |
|---|---|
| `https://{site}/apps/` → 404 | Normal. There is no index at `/apps/`. Open `/apps/{name}/`. |
| Page loads at `/apps/{name}` but CSS/JS 404 (Node/Python) | No trailing slash, so relative URLs resolve to `/apps/…`. Use absolute `/apps/{name}/…` URLs and link with the slash. |
| Visitor ends up on `{tech}` / becomes a guest after clicking a link | A PHP folder linked without the trailing slash → the server 301s to `https://{tech}/{name}/`. Link to `/apps/{name}/`. Never build URLs from `HTTP_HOST` (always `{tech}`). |
| Route not found although the URL looks right | Code sees `/{name}/…` (the proxy strips `/apps`). Strip `/{name}` (`route_path()` in the templates). |
| Node/Python app URL 404 / shows PHP | `app_uri` collides with a PHP folder, or the generated `public_html/{name}/.htaccess` was edited/removed. Recreate the app (destroy is **C**). |

## PHP

| Symptom | Cause / fix |
|---|---|
| `Permission denied` on `{name}_secrets.php` right after `files_chmod` | `perm` was passed as octal digits (`"600"`) — the tool reads decimal. Use `"384"` (0600) and check `mode` in `files_list`. |
| `mkdir(): File exists` (or another warning) kills a request despite `@` | The error handler ignores `@`. Add `if (!(error_reporting() & $no)) return false;` (template has it). |
| Cron/admin endpoint answers without a key | Key missing in config + naive `hash_equals('', '')`. Use `require_key()` (fails closed). |
| Empty `500 text/html` from PHP, nothing in the module log | Syntax error in the entry file, before the bootstrap loaded. Keep `index.php` as the stub so errors land in `lib/app.php` and get logged. |
| Blank page / generic 500 | Read `{name}_data/php-error.log` (the bootstrap sets `error_log`; fatals such as memory exhaustion are written there, verified) and `{name}_data/app.log`. If both are empty, the error happened before the bootstrap loaded (syntax error in `index.php`): `files_read` the file and check it. |
| 500 with "Allowed memory size exhausted" / "Maximum execution time" in `php-error.log` | Process less per request (batches, cursor, cron). These fatals bypass the JSON handler. |
| 500 after switching PHP version | Deprecations thrown as exceptions. Use the error handler from PHP.md §3. Remove calls like `curl_close()` (deprecated in 8.5). |
| `Undefined constant` | Constant declared after an early `exit`. Move declarations to the top. |
| `Access denied for user 'x'@'localhost'` | Missing `{username}_` prefix in DB name/user, or the user has no privileges on that database (`db_users`) → `db_change_privs` (**C**). |
| `ER_DBACCESS_DENIED_ERROR` / access denied to the database for a user made with `db_create_user` | The user has no privileges. `db_change_privs database=… dbuser=… confirm:true`, or use `db_create_with_user`. |
| Client sees "JSON.parse: unexpected character" | The server returned HTML: a PHP fatal before the JSON handler, or an upload over `post_max_size`. Check the log. Shrink uploads in the browser. |
| Secrets value "missing" | The key is nested inside `db` (or another array) instead of top level, or the loader found a different `{name}_secrets.php`. |
| `Call to undefined function exec()` / similar | Shell functions are disabled. Redesign: HTTP calls, cron, or a PHP library. |
| `open_basedir restriction` | A path outside `/home/{username}`. Use paths under the home dir only (`sys_get_temp_dir()` may also be restricted — use `{name}_data/tmp`). |
| Upload rejected before your code runs | Over `upload_max_filesize` 2M / `post_max_size` 8M. Compress in the browser (PHP.md §6). |

## Node / Python

| Symptom | Cause / fix |
|---|---|
| Passenger HTML page "Web application could not be started … Error ID" (HTTP 500, ~4 KB) | Startup crash and no entry stub in place. Install the stub (`app.js` → `lib/main.js`, `passenger_wsgi.py` → `app.py`), reload, request again, then `files_read logs/startup.log`. Python right after `python_create`: the CloudLinux `passenger_wsgi.py` is still there — write yours. |
| JSON `{"error":"startup","detail":…}` | The stub caught a crash in `lib/main.js` / `app.py`: syntax error, missing package, or missing `APP_NAME` (recreate with `env_vars={"APP_NAME":"{name}"}` — env vars cannot be added later). Full stack in `logs/startup.log`. |
| JSON `{"error":"server"}` | A handler threw. `files_read logs/app.log`. |
| 502 "Incomplete response received from application" | The process died mid-request (crash, `os._exit`, memory). The next request respawns it (~4.5 s). Check `logs/app.log`. |
| 504 Gateway Time-out at ~60 s | The request ran too long. Move the work into cron batches. |
| `node_install_modules` → `'NoneType' object has no attribute 'lower'` | The app answered CloudLinux's probe without a `Content-Type`. Send one on every response (404 too), reload, run again. |
| `python_install_modules` → 503 "Please, specify modules or requirements file" | Does not work through MCP. Use the one-off cron `pip` job (PYTHON.md §5). |
| Changes not visible / `/health` shows the old `BUILD` | No reload: `*_restart` or `files_write tmp/restart.txt`. Or files written to the wrong `app_root` (`*_list`). |
| `Cannot find module 'x'` (Node) | `package.json` dependency → `node_install_modules` → reload. Check `usage` inodes. |
| `ModuleNotFoundError` (Python) | Install via the cron `pip` job (PYTHON.md §5); reload if the import is at top level. |
| `require is not defined in ES module scope` | No `package.json` (or no `"type"`) in the app root, so a parent one with `"type": "module"` applies. Add `"type": "commonjs"`, reload. |
| Static file 404 from `public/…` | `public/` is not served. Whitelist the file in the app (`STATIC`) or put it in `public_html`. |
| Nothing in any log, `console.log`/`print` output missing | That output is not visible through MCP. Write to `logs/app.log`. |
| App state resets | Passenger restarts crashed or idle processes, and Python runs several processes. Keep state in files or a DB. |
| Slow first request (~2.5 s) | Cold start after idle or reload. Expected. Keep startup light (lazy imports). |
| `python_create` timed out in MCP | It may have succeeded (~30 s later). `python_list` before retrying. |

## Identity

| Symptom | Cause / fix |
|---|---|
| `ucoz_user()` always empty | The visitor is logged in to the Control Panel/uID but not on the site itself (log in at `/index/1`), no visitor key (`visitor_key_get.present`), testing on `{tech}`, a slash-less link redirected the visitor to `{tech}`, or the visitor is not logged in to this site. |
| `ucoz_user` undefined (PHP) | Your own `auto_prepend_file` replaced the platform one (`/opt/ucoz/lib/ucoz.php`), or the code runs outside the account. Remove the override. |
| `Cannot find module 'ucoz'` / `No module named 'ucoz'` | Not running under the account's Passenger: a cron script (expected — no visitor there), a local run, or a custom `NODE_PATH`/`PYTHONPATH` that dropped `/opt/ucoz/lib`. |

## uAPI

| Symptom | Cause / fix |
|---|---|
| `INVALID_API_KEY` | Key revoked/wrong. The owner issues a new one → secrets. |
| `API_ACCESS_DENY` | uAPI disabled for that module. `modules_write module_enable_uapi` with approval. |
| Field names differ from docs | Trust live responses. Inspect with `content_read` / `content_write`/`users_read`/`shop_read` / `shop_write` first. |
| `X&#124;S` in names | HTML entities. Decode before comparing, and escape on output. |
| `({"SCRIPTDATA":…})` | The legacy PHP wrapper — wrong URL/stack. If the site still depends on that script, migrate it: [MIGRATION.md](MIGRATION.md). |

## Cron

| Symptom | Cause / fix |
|---|---|
| Runs hourly instead of the intended schedule | Fields omitted. The defaults are minute `0`, others `*`. Recreate with all five fields. |
| Never seems to run | Placeholder instead of a full command, wrong key (endpoint returns 404), a timeout, or `node`/`python` called without an absolute path (not on cron `PATH`). Call the URL manually; read the log the command writes. |
| Runs overlap / double-process | Add a lock (`GET_LOCK` / `flock`) and a cursor. |

## Telegram

| Symptom | Cause / fix |
|---|---|
| `getWebhookInfo` shows "Connection timed out", the bot never receives updates | Telegram cannot reach Server Scripts (verified on site and technical domains). Use cron polling (CAPABILITIES.md §5, R5). |
| `getUpdates` → 409 "can't use getUpdates method while webhook is active" | A webhook is still set for the token. Call `deleteWebhook` once. |
| `getUpdates` → "Conflict: terminated by other getUpdates request" | Two polls ran at once (cron and an admin page, or two modules on one token). Wrap the poll in a non-blocking `flock`; one consumer per token. |
| A command sometimes gets no answer | Another consumer polled the same token first (a second module, an old bot script). Give each module its own bot. |
| Old messages sent after linking a chat | No watermark. Store "notify since" and send only newer events. |
