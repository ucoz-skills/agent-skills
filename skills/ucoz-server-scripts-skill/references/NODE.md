# Node.js apps under Passenger

Use Node when you need an npm library, in-memory state, or the team writes JavaScript. Otherwise
PHP is simpler. Starter code: [templates/node/](../templates/node/) — `app.js` (entry stub),
`lib/main.js` (logic), `index.html`, `package.json`, `config.example.json`.

Everything below was verified live (October 2026, Passenger 6.0.26, Node 22) unless marked otherwise.

## 1. Lifecycle via apps_read / apps_write / apps_delete

```text
1. files_list    domains/{domain}/public_html → {name} must not exist (app_uri collision)
2. files_mkdir   path=domains/{domain}/app_node/{name}/lib
3. files_write   …/{name}/app.js, lib/main.js, package.json, config.json, index.html
                 (writing before or after node_create both work; node_create keeps an existing app.js)
4. files_chmod   paths=[…/{name}/config.json] perm="384"            (0600, readable by the app)
5. node_create   app_root=domains/{domain}/app_node/{name}  app_uri=/{name}
                 version=22  app_mode=production  startup_file=app.js
                 env_vars={"APP_NAME":"{name}"}                     ← required by the template
6. node_install_modules app_root=…      (only with package.json dependencies; check inodes first)
7. node_restart  app_root=…             (or files_write …/{name}/tmp/restart.txt with any content)
8. node_list     → app_status "started"
9. GET https://{site}/apps/{name}/health → 200 JSON with the new BUILD (TESTING.md)
```

`node_create` also creates `public/` and `tmp/` in the app root and writes `public_html/{name}/.htaccess`
(`PassengerAppRoot`, `PassengerBaseURI`, …, and your `env_vars` as `SetEnv`). Do not edit that file, do not
put PHP files in `public_html/{name}/`, and do not move `app_root` (destroy and create again instead;
`node_destroy` is **C**). `env_vars` cannot be changed later through MCP — keep changeable settings in
`config.json`.

**Reload.** A running process keeps the old code. Every change is **write → `node_restart` (or write
`tmp/restart.txt`) → check `/health` shows the new `BUILD`**. An idle or stopped app starts with the new
code on the next request. Cold start is ~2.5 s; warm requests ~0.6 s from the client.

## 2. Entry stub (the Node equivalent of the PHP stub)

`console.log`/`console.error` output and Passenger logs are **not readable through MCP**
(`passenger_log_file` is `null`). A crash at startup shows only Passenger's HTML page "Web application could
not be started … Error ID" (HTTP 500) with no detail. So `app.js` stays tiny: it `require`s `lib/main.js`
inside `try/catch`, appends the stack to `logs/startup.log`, and answers every request with JSON
`{"ok":false,"error":"startup","detail":"…"}`. Verified: a syntax error in `lib/main.js` came back as
`detail: "Unexpected token ';'"` with file:line in `logs/startup.log`. Put all changes into `lib/`.

`lib/main.js` throws at load when `APP_NAME` is missing or still `{name}`, so a forgotten `env_vars`
shows up as a startup error instead of silent 404s.

## 3. Server rules

- `process.env.PORT` is **not set**; Passenger hooks `http.Server.listen()`, so `listen(process.env.PORT || 3000)` works.
- `NODE_ENV=production`; cwd is the app root; Node 20+ has global `fetch` (outbound HTTPS verified).
- Use the built-in `http` module unless a framework is really needed. No build step on the server.
- Ship `package.json` with `"type": "commonjs"` (or `"module"` for ESM) — otherwise a parent
  `package.json` may switch the module type. `name` must be a valid npm name (lowercase, no braces).
- An uncaught exception kills the process; Passenger respawns it on the next request (~4.7 s). The template
  logs `uncaughtException`/`unhandledRejection` to `logs/app.log`.
- Limit request bodies (100 KB for JSON → 413). Keep every request well under **60 s** — nginx answers
  504 at ~60 s. Long work goes to cron batches.
- Log to `logs/app.log` with a size cap (the template does).

## 4. URL handling

For `https://{site}/apps/{name}/x` the app receives `req.url = /{name}/x` and `Host: {tech}` (the proxy
strips `/apps`). `routePath()` in `lib/main.js` strips `/{name}` using `APP_NAME` (and `/apps/{name}`
defensively). `x-forwarded-for` carries the client IP.

`/apps/{name}` (no slash) returns 200 with no redirect, so relative asset URLs resolve against `/apps/`.
Use **absolute** URLs (`/apps/{name}/ui.js`) and link to the trailing-slash URL. Never build links from `Host`.

## 5. Static files

`public/` is **not** served by the platform: `/apps/{name}/public/x.txt` reaches your app. Serve static
files from the app with an explicit whitelist (`STATIC` in `lib/main.js`), or put them in `public_html`
under another folder. Never map a URL path to a file path — the app root holds `config.json`, data, logs.

## 6. Config and secrets

| Option | How | Notes |
|---|---|---|
| `config.json` in the app root | `files_write` + `files_chmod "384"`; read at startup | Preferred for secrets. Change = write + reload. Never whitelist it as static. |
| `env_vars` at `node_create` | `process.env.X` | Fixed after creation. Stored as `SetEnv` in `public_html/{name}/.htaccess` and returned by `node_list` (**S**). Use for `APP_NAME` and other stable, non-secret values. |

## 7. Data and MySQL

- Small data: a JSON file in the app root (`data/`) with atomic write (tmp → rename). Files persist.
- Concurrent writers or more data: MySQL with `mysql2` — add it to `package.json` → `node_install_modules`
  → reload. Verified: `mysql2/promise` pool, host `localhost`, prefixed names, connect + query ~40 ms.
  Snippet: [MYSQL.md](MYSQL.md) §5.

## 8. Dependencies

`package.json` with pinned versions → `node_install_modules app_root=…` → reload. Verified with `ms` and
`mysql2`. `node_modules` in the app root is a symlink into `~/nodevenv/…` (`broken_symlink` in
`files_list` — normal). CloudLinux requests the app before and after the install and compares the
`Content-Type`: if any response (even a 404) lacks one, the install fails with
`'NoneType' object has no attribute 'lower'`. The template always sends one. Each dependency tree costs
inodes (50 000 per account observed) — check `usage` first.

## 9. Cron, identity, calling from the site

- Cron: hit a key-protected route with curl, or run a Node script directly (absolute node path, cwd = app
  root; no `APP_NAME`, no `ucoz` module in cron) — [CRON.md](CRON.md) §3.
- Identity: `require('ucoz')` → `ucoz_user(req)` under Passenger ([IDENTITY.md](IDENTITY.md)).
- From uCoz templates: `fetch('/apps/{name}/api/…', {credentials: 'same-origin'})` — same origin, no CORS.

## 10. Checklist

- [ ] `env_vars={"APP_NAME":"{name}"}` passed; `app_uri` was free
- [ ] `app.js` is the stub; logic in `lib/main.js`; `BUILD` bumped
- [ ] Static whitelist; `config.json` 0600 and never served
- [ ] Every response has a Content-Type; body limit; requests < 60 s
- [ ] Reloaded; `/health` shows the new `BUILD`; smoke checklist in [TESTING.md](TESTING.md) passed
