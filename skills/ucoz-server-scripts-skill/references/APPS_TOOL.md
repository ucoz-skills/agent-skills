# `apps_tool` reference

Every action of `apps_tool`, grouped by job, with the parameters that matter, gates, and the quirks
seen on a live account. The tool schema in your MCP client is the source of truth for parameter
names. If it differs from this page, follow the schema and `get_guide`.

Conventions:
- `site` = `select_site` must already be done.
- **V** column: ✅ = used successfully on a live account (October 2026); ❌ = does **not** work through MCP —
  use the alternative in Notes. Other actions in the schema are not needed for the workflows in this skill.
- **C** = needs `confirm: true` after explicit user approval.
- **S** = response contains secrets. Do not echo them.
- Paths are **home-relative** (`domains/{domain}/…`). No leading `/home`.
- There is **no** action to read Passenger/PHP logs, to change `env_vars` after creation, or to run SQL or
  shell commands.

## 1. Orientation and lifecycle

| Action | V | Params | Notes |
|---|---|---|---|
| `get_guide` | ✅ | — | Server-side call order. Read once per session. |
| `status` | ✅ | — | `{available, activated, ready, expired, delete_limit_reached, key_present, domain, username, host, name}`. Start here. |
| `ensure_ready` | ✅ | `confirm` (**C**) | Creates/activates the environment account if needed. Ask first. |
| `session` | ✅ | — | `selected_domain`, `homeDir`, `allowedCommands`, `configFeatures`, `directadminConfig`. Big; call once. |
| `usage` | ✅ | — | Disk, inode, bandwidth, DB, FTP, domain usage and limits. Counters are **not real-time** — approximate. |
| `user_config` | ✅ | — | Package and limits (`ssh`, `cgi`, `cron`, `php`, `*Lim`). **S**: contains the account e-mail. |
| `plugins` | ✅ | — | Observed: `nodejs_selector`, `phpselector`, `python_selector`. |
| `selector` | ❌ | `plugin`, `interpreter`, `selector_method`, `params` | Raw CloudLinux selector. `set` with `env_vars` and `install-modules` were rejected ("Invalid parameter passed"). Do not use. |

Interpreting `status`:

| Field | Meaning | Do |
|---|---|---|
| `available: false` | Service not available for the site | Stop. Tell the user to enable Server Scripts in the Control Panel. |
| `activated: false` / `ready: false` | Account not created yet | Ask, then `ensure_ready confirm:true`. |
| `expired: true` | Subscription ended | Stop and tell the user. |
| `delete_limit_reached: true` | Account re-creation/deletion allowance used up | Never destroy the environment. Warn the user. |

## 2. Files

| Action | V | Params | Notes |
|---|---|---|---|
| `files_list` | ✅ | `path` (**required**), `limit`, `offset` | `path: ""` → "path is required". Start at `domains`. Returns `type`, `sizeBytes`, `mode`, `modifyTime`. |
| `files_tree` | ✅ | `path` | Shallow; deep levels come back `incomplete: true` → use `files_list` per folder. |
| `files_read` | ✅ | `path` | Text → `content`. Large or binary → a **public URL** (`https://{site}/apps/_mcp_dl/…`) — treat as public. |
| `files_write` | ✅ | `path`, `content`, `overwrite` | **`overwrite` defaults to true.** `overwrite:false` for files that must be new. 409 = parent missing → `files_mkdir`. |
| `files_patch` | ✅ | `path`, `old_string`, `new_string` | Exact replace; `old_string` must occur **once**. Re-read after. Resets file mode to 0644. |
| `files_mkdir` | ✅ | `path` | Creates missing parents. |
| `files_copy` | ✅ | `source`, `destination`, `overwrite` | One file or a whole folder. Backups and module cloning. |
| `files_move` | ✅ | `source`, `destination`, `overwrite`, `confirm` (**C**) | Requires `confirm: true`. |
| `files_delete` | ✅ | `paths[]`, `trash`, `confirm` (**C**) | Use `trash:true` (moves to the account trash). Only inside modules you created. |
| `files_chmod` | ✅ | `paths[]`, `perm` | **`perm` is read as DECIMAL.** `0600` → `"384"`, `0640` → `"416"`, `0644` → `"420"`, `0755` → `"493"`. `"600"` made a secrets file unreadable. Check `mode` with `files_list` afterwards. |
| `files_archive` | ✅ | `sources[]`, `destination` | `.zip`; members keep the folder name, so extract into the parent folder. |
| `files_archive_list` | ✅ | `path` | List archive members. |
| `files_extract` | ✅ | `path`, `destination_dir`, `members[]`, `merge`, `confirm` (**C**) | Restore (rollback verified). |

## 3. PHP

| Action | V | Params | Notes |
|---|---|---|---|
| `php_get` | ✅ | — | **~100 KB**: versions, options, extensions. Call only when needed; summarize. |
| `php_set_version` | ✅ | `version` | Account-wide. Ask first; test every PHP module afterwards. |

There is no PHP "app" to create — any `.php` under `public_html` runs.

## 4. Node.js

| Action | V | Params | Notes |
|---|---|---|---|
| `node_list` | ✅ | — | Apps with `app_uri`, `app_status`, `startup_file`, `app_mode`, `env_vars` (**S**); `passenger_log_file` is `null`. |
| `node_create` | ✅ | `app_root`, `app_uri`, `version`, `app_mode`, `startup_file`, `env_vars` | `app_root=domains/{domain}/app_node/{name}`, `app_uri=/{name}`, `version=22`, `startup_file=app.js`, `env_vars={"APP_NAME":"{name}"}`. Creates `public/`, `tmp/` and `public_html/{name}/.htaccess`; keeps an existing `app.js`. |
| `node_install_modules` | ✅ | `app_root` | npm install from `package.json` (`ms`, `mysql2` verified). Fails with `'NoneType' object has no attribute 'lower'` if any app response lacks a `Content-Type`. |
| `node_restart` | ✅ | `app_root` | Needed after every code change (or write `tmp/restart.txt`). |
| `node_destroy` | ✅ | `app_root`, `confirm` (**C**) | Unregisters the app. Then remove `app_node/{name}` and `public_html/{name}` with `files_delete trash:true`. |

## 5. Python

| Action | V | Params | Notes |
|---|---|---|---|
| `python_list` | ✅ | — | Same shape as `node_list`. |
| `python_create` | ✅ | `app_root`, `app_uri`, `version`, `startup_file`, `entry_point`, `env_vars` | `app_root=domains/{domain}/app_python/{name}`, `version=3.11`, `startup_file=passenger_wsgi.py`, `entry_point=application`, `env_vars={"APP_NAME":"{name}"}`. **Replaces `passenger_wsgi.py`** — write yours after. The MCP call can time out while the app is still created: `python_list` before retrying. |
| `python_install_modules` | ❌ | `app_root` | 503 "Please, specify modules or requirements file with modules" with every params variant. Use the cron pip job (PYTHON.md §5). |
| `python_restart` | ✅ | `app_root` | Needed after every code change (or write `tmp/restart.txt`). May return MCP 502 although it happened — check the URL. |
| `python_destroy` | ✅ | `app_root`, `confirm` (**C**) | As for Node (`app_python/{name}`, `public_html/{name}`). |

## 6. MySQL

Names are auto-prefixed with `{username}_` when you pass a short name.

| Action | V | Params | Notes |
|---|---|---|---|
| `db_list` | ✅ | — | Inventory. |
| `db_create_with_user` | ✅ | `name`, `password?` | **DB + user + full privileges in one call.** Omit `password` to let the server generate one. **S**: write the password to the secrets file at once. |
| `db_create_user` | ✅ | see schema | Creates the user **without privileges** on the database, even when a database is passed (`ER_DBACCESS_DENIED_ERROR`). Follow with `db_change_privs`. |
| `db_change_privs` | ✅ | `database`, `dbuser`, `confirm` (**C**) | "Database privileges will be replaced"; grants the full set. |
| `db_delete` | ✅ | `database`, `confirm` (**C**) | Irreversible. **Leaves the user behind** — also `db_delete_user`. |
| `db_delete_user` | ✅ | `dbuser`, `confirm` (**C**) | — |
| `db_list` / `db_users` | ✅ | — / `database` | Databases with sizes and table counts / users of a database with their privileges. |

## 7. Cron

| Action | V | Params | Notes |
|---|---|---|---|
| `cron_list` | ✅ | — | `jobs[]` (`id`, full `line`) and `settings` (`MAILTO`, `PATH`). **S**: lines contain keys — never echo them. |
| `cron_create` | ✅ | `minute`, `hour`, `dayofmonth`, `month`, `dayofweek`, `command` | **Defaults: minute `0`, others `*` = hourly.** Always pass all five. `command` uses absolute `/home/{username}/…` paths. First run within ~1 minute. |
| `cron_delete` | ✅ | `cron_ids[]`, `confirm` (**C**) | Ids from `cron_list`. |

## 8. Keys

| Action | V | Notes |
|---|---|---|
| `visitor_key_get` | ✅ | `{present, key}` — **S**. Read only `present`. Without a visitor key, `ucoz_user()` is always a guest. |
| `visitor_key_create` | ✅ | Enables identity forwarding. Ask first. |
| `login_key_list` | ✅ | The MCP's own key to the environment (`main`). |
| `login_key_rotate` | ✅ | **C**. Only on the user's request (fixes the fresh-account 500, MIGRATION.md §6). |

## 9. Error shapes and verified quirks

- Whole-request failure: `{"ok":false,"error":"…"}` — the message is usually precise.
- **HTTP 409** from `files_write` / `files_list` = the path (or its parent) does not exist.
- Successful writes return `status_code: 204` and an empty body. Read back when content matters.
- `node_create` / `python_create` return `public_url` (`https://{site}/apps/{name}`). Give users the trailing-slash form.
- **Timeouts and 502s.** Slow actions (`python_create`, restarts) and **parallel write calls** (four parallel
  `files_patch` → three 502s) can fail at the MCP proxy although the action ran. Send writes **one at a time**;
  after a timeout or 502 check the real state (`*_list`, `files_read`, an HTTP request) before repeating.
- **Retry-safe patches:** let `old_string` span the insertion point, so a repeated patch fails with
  "old_string was not found" instead of inserting twice.
- **The selected site is shared state of the uCoz authorization.** Another client can switch it (verified: a
  `db_create_with_user` landed on another site). `select_site` before each batch of writes, check
  `host`/`site_id` in **every** write response, and stop if they changed.
