# Python apps under Passenger (WSGI)

Use Python when the job needs Python libraries (data processing, AI SDKs). Otherwise PHP is simpler.
Starter code: [templates/python/](../templates/python/) — `passenger_wsgi.py` (entry stub), `app.py`
(logic), `config.example.json`, `requirements.txt`.

Everything below was verified live (October 2026, Passenger 6.0.26, Python 3.11) unless marked otherwise.

## 1. Lifecycle via apps_read / apps_write / apps_delete

```text
1. files_list     domains/{domain}/public_html → {name} must not exist (app_uri collision)
2. python_create  app_root=domains/{domain}/app_python/{name}  app_uri=/{name}
                  version=3.11  startup_file=passenger_wsgi.py  entry_point=application
                  env_vars={"APP_NAME":"{name}"}            ← required by the template
   If the call times out at MCP, the app may still be created (~30 s later): python_list before retrying.
3. files_write    …/{name}/passenger_wsgi.py overwrite:true   ← AFTER python_create (it replaces this file)
   files_write    …/{name}/app.py, config.json (+ files_chmod "384"), requirements.txt if needed
4. packages, if any: §5
5. python_restart app_root=…          (or files_write …/{name}/tmp/restart.txt with any content)
6. python_list    → app_status "started"
7. GET https://{site}/apps/{name}/health → 200 JSON with the new BUILD (not the Passenger HTML page)
```

`python_create` writes its own `passenger_wsgi.py` (a CloudLinux stub that does not serve your app), so
always write yours afterwards. Same rules as Node for the generated `public_html/{name}/.htaccess`,
`app_uri` collisions, and never moving `app_root`. `env_vars` cannot be changed later through MCP.

**Reload.** Running processes keep the old code. Every change is **write → `python_restart` (or write
`tmp/restart.txt`) → check `/health` shows the new `BUILD`**. Passenger runs **several processes**
(`wsgi.multiprocess=True`), so the pid can differ between requests — prove a reload by `BUILD` and a small
`uptime`, not by the pid. A process killed mid-request returns 502 "Incomplete response received from
application" for that request; the next one respawns it (~4.4 s).

## 2. Entry stub (the Python equivalent of the PHP stub)

`print()`/stderr output and Passenger logs are **not readable through MCP**. A crash at import time shows
only Passenger's "Web application could not be started … Error ID" page. So `passenger_wsgi.py` stays tiny:
`from app import application` inside `try/except`; on failure it appends the traceback to
`logs/startup.log` and serves JSON `{"ok":false,"error":"startup","detail":"…"}`. Verified: a syntax error
in `app.py` came back as `detail: "SyntaxError: invalid syntax"` with the line in `logs/startup.log`.
`app.py` raises at import when `APP_NAME` is missing or still `{name}`.

## 3. WSGI rules

- Route on `PATH_INFO`: for `https://{site}/apps/{name}/x` the app gets `SCRIPT_NAME=/{name}`,
  `PATH_INFO=/x` (prefix already stripped), `REQUEST_URI=/{name}/x`, `HTTP_HOST={tech}`. Build public links
  from config (`https://{site}/apps/{name}/`), never from `HTTP_HOST`.
- Import optional packages lazily inside handlers, so a missing package returns a JSON error instead of a
  startup crash. Lazy imports see newly installed packages without a reload; top-level imports need one.
- Wrap the dispatcher in `try/except` → log + JSON 500. Read the body with `CONTENT_LENGTH` and cap it.
- Keep every request well under **60 s** — nginx answers 504 at ~60 s. Long work goes to cron batches.
- Log to `logs/app.log` yourself (the template appends one line per event with a size cap and the pid).
- `PYTHONPATH=/opt/ucoz/lib` (the platform `ucoz` helper). `public/` is **not** served as static — serve
  files from the app or put them in `public_html` under another folder.

## 4. Config and secrets

`config.json` in the app root (`files_chmod "384"` — readable by the app), loaded once at import; or
`env_vars` at creation (`os.environ`, fixed afterwards, returned by `python_list` — **S**). Prefer
`config.json` for secrets.

## 5. Packages

`python_install_modules` does **not** work through MCP: it answers 503 "Please, specify modules or
requirements file with modules", with or without `params` (`requirements-file`, `requirements_file`,
`modules`); a raw `selector` install answers "Invalid parameter passed".

**Working route — a one-off cron job that runs the app's virtualenv pip:**

```text
1. files_write  …/{name}/requirements.txt          (pinned versions)
2. [ask] cron_create minute="*" hour="*" dayofmonth="*" month="*" dayofweek="*"
     command="cd /home/{username}/domains/{domain}/app_python/{name} && /home/{username}/virtualenv/domains/{domain}/app_python/{name}/3.11/bin/pip install -r requirements.txt > logs/pip.log 2>&1"
3. wait ~1 minute → files_read …/{name}/logs/pip.log → "Successfully installed …"
4. cron_list → cron_delete cron_ids=[<id>] confirm:true   (do not leave it running every minute)
5. reload (top-level imports) → /health
```

Verified: PyMySQL 1.1.1 and requests 2.32.3 (with dependencies) installed from PyPI this way. Replace
`3.11` with the app's version. Packages cost inodes — check `usage` first.

## 6. Data

`sqlite3` (with FTS5) is in the standard library and works. MySQL: PyMySQL installed via §5, host
`localhost`, prefixed names ([MYSQL.md](MYSQL.md) §5). Outbound HTTPS with `urllib.request` works.

## 7. Cron and identity

- Cron: hit a key-protected route with curl, or run a script with the venv python (absolute path;
  `APP_NAME` is set there, `ucoz` is not importable) — [CRON.md](CRON.md) §3.
- Identity: `from ucoz import ucoz_user` → `ucoz_user(environ)` under Passenger ([IDENTITY.md](IDENTITY.md)).
- Webhooks from external services: secret in the path or header plus the provider's signature; answer
  fast and queue heavy work. Telegram cannot reach Server Scripts — use polling
  ([CAPABILITIES.md](CAPABILITIES.md) §5).

## 8. Checklist

- [ ] `env_vars={"APP_NAME":"{name}"}` passed; `python_list` checked before any retry of `python_create`
- [ ] `passenger_wsgi.py` (stub) written **after** `python_create`; logic in `app.py`; `BUILD` bumped
- [ ] Optional imports lazy; routes on `PATH_INFO`; requests < 60 s
- [ ] `config.json` 0600, not served; the pip cron job deleted
- [ ] Reloaded; `/health` shows the new `BUILD`; smoke checklist in [TESTING.md](TESTING.md) passed
