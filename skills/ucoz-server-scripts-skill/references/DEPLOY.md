# Deploy, back up, roll back

## 1. New module (nothing to lose)

1. `files_list domains/{domain}/public_html` — make sure `{name}` is free (and not an existing `app_uri`).
2. `files_mkdir` the folders (a missing parent makes `files_write` fail with HTTP 409) → `files_write overwrite:false` for each file.
3. Secrets file → `files_write overwrite:false` → `files_chmod perm:"384"` (= 0600; the tool takes decimal).
4. Node: write code (stub `app.js` + `lib/main.js`) → `node_create … env_vars={"APP_NAME":"{name}"}` →
   `node_install_modules` if needed. Python: `python_create … env_vars={"APP_NAME":"{name}"}` → **then** write
   `passenger_wsgi.py` (stub) + `app.py` → packages via the cron `pip` job if needed (PYTHON.md §5).
   Both: reload (`*_restart` or `tmp/restart.txt`).
5. Verify with [TESTING.md](TESTING.md).

## 2. Changing a working module

1. **Read first:** `files_read` the files you will change. Never rebuild a file from memory.
2. **Back up** (both verified):
   - one file → `files_copy source=<f> destination=domains/{domain}/_backups/<name>-<file>.bak-YYYYMMDD-HHMM overwrite:false`;
   - the module → `files_archive sources=[domains/{domain}/public_html/{name}] destination=domains/{domain}/_backups/{name}-YYYYMMDD-HHMM.zip`
     (members are stored as `{name}/…`).
   For Node/Python, back up the app root (excluding `node_modules`).
3. **Edit:** small change → `files_patch` with an `old_string` that spans the insertion point, so a retry
   after an MCP 502 cannot apply it twice. Rewrite → `files_write overwrite:true` and `files_read` it back.
   (`files_move` would allow write-then-rename, but it needs `confirm`, so use it only when the atomic swap matters.)
4. Node/Python: bump `BUILD`, then `*_restart` (or `files_write tmp/restart.txt`) — a running app keeps the old code.
5. **Verify** ([TESTING.md](TESTING.md)). On failure, roll back (§3) first and debug after.
6. Tell the user where the backup is. Offer to remove backups older than the last good one later
   (they cost quota and inodes, and deletion needs **C**).

Keep backups **outside** `public_html` (`domains/{domain}/_backups/`). A `.bak` copy of a `.php` file
inside the docroot is served as plain text by many servers — `index.php.bak` can leak code. If you copy
next to the original for speed, delete or move it before finishing, or make sure `.htaccess` denies `*.bak`
(the PHP template does).

## 3. Roll back

- One file: `files_copy source=<f>.bak-… destination=<f> overwrite:true` (+ restart for Node/Python).
- Folder: `files_extract path=<archive> destination_dir=domains/{domain}/public_html merge:true confirm:true`
  (+ restart). Confirm the paths inside the archive with `files_archive_list` first. Verified live: a module
  broken by a syntax error was restored this way and answered 200 again.
- Database: there is no MCP SQL. Use the module's down-migration endpoint if you wrote one, or a backup
  restore planned in advance. Keep migrations additive (new tables/columns), so the previous code still runs.

## 4. Verification

See [TESTING.md](TESTING.md) — per-runtime smoke checklist.

## 5. Working with the user's local project

If the user keeps the code in a local folder or repo:
- Treat the local copy as the source of truth. Deploy file by file with `files_write`, and keep the
  server-only secrets file out of the repo (`.gitignore`).
- After deploying, `files_read` one changed file back and compare it with the local file to catch encoding/CRLF issues.
- Never overwrite server-side secrets with local placeholders.

## 6. Removing a module (only on request)

1. Inventory: files, app registration, cron jobs, database tables, wiring in templates.
2. Show the list and get approval.
3. `cron_delete` (**C**) → `*_destroy` (**C**) → `files_delete trash:true` (**C**) → remove template
   wiring (`templates_tool patch_template`) → optionally `db_delete` + `db_delete_user` (**C**).
4. Verify the URLs now return 404 and the site pages no longer reference them.
