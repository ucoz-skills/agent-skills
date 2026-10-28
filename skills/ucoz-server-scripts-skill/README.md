# uCoz Server Scripts Skill

Version 1.2. Agent skill for building and running backend code for a uCoz site in its **Server Scripts**
environment through the official `ucoz-mcp` **`apps_read` / `apps_write` / `apps_delete`**.

It covers PHP endpoints, Node.js and Python apps under Passenger, MySQL, cron, visitor identity
(`ucoz_user()`), calling the site uAPI, security, deploy/rollback, testing, and troubleshooting. Each
recipe states its own test status.

## What makes it more than the MCP tools

The tools give an agent the hands — write a file, create an app, create a database. The skill adds what
the tools do not say, found on live accounts:

- **Platform behaviour.** The `/apps` proxy strips the prefix and sends `Host: {tech}`. A PHP folder without a trailing slash redirects to the technical domain and loses identity. `ucoz_user()` comes from `/opt/ucoz/lib` in PHP, Node, and Python, and needs a login on the site itself. Requests are cut at ~60 s. Node/Python apps keep old code until a restart, do not serve `public/`, and their console output and Passenger logs are invisible. Telegram cannot deliver webhooks to Server Scripts — bots poll from cron.
- **Tool traps.** `files_chmod` reads `perm` as decimal. `python_create` replaces `passenger_wsgi.py` and can time out while still succeeding. `db_create_user` grants nothing. `db_delete` keeps the user. `python_install_modules` fails — a one-off cron `pip` job works. npm install fails if any response lacks a Content-Type. `cron_list` shows keys. The selected site is shared state. Parallel writes trigger 502s.
- **Safe process:** guard the target site, back up, use retry-safe patches, verify on the live URL with a smoke checklist, follow confirmation gates, never echo secrets.
- **Code:** entry stubs for all three runtimes that make startup and parse errors visible, fail-closed key checks, signed webhooks, rate limits, migrations, caching.

## Layout

```text
ucoz-server-scripts-skill/
├─ SKILL.md                 entry point: mental model, session protocol, non-negotiables, gates, router
├─ references/
│  ├─ ENVIRONMENT.md        what the environment is, URL mapping, versions, limits, timeouts
│  ├─ APPS_TOOL.md          every apps_read / apps_write / apps_delete action: params, gates, verified marks, quirks
│  ├─ PHP.md · NODE.md · PYTHON.md
│  ├─ MYSQL.md · CRON.md · IDENTITY.md · UAPI.md
│  ├─ ARCHITECTURE.md · SECURITY.md · DEPLOY.md · TESTING.md · TROUBLESHOOTING.md
│  ├─ CAPABILITIES.md       what the panel cannot do but Server Scripts + uAPI can: bots, dashboards, search
│  └─ MIGRATION.md          replacing old scripts on the legacy uCoz PHP server (OAuth 1.0a, $___notjson)
├─ examples/
│  ├─ RECIPES.md            recipes R1–R10 with call sequences, code location and test status
│  ├─ r1-leads/             lead form → MySQL, staff list/CSV, Telegram/CRM notifications
│  ├─ r3-webhook-queue/     signed webhooks → MySQL queue → cron worker
│  ├─ r4-og-images/         Open Graph preview images (GD, Cyrillic fonts)
│  ├─ r5-telegram-poll/     Telegram bot via cron short polling
│  └─ r6-python-report/    weekly Excel report in Python (openpyxl), Telegram delivery
└─ templates/
   ├─ php/                  index.php (stub), lib/bootstrap.php, lib/app.php, .htaccess, secrets.example.php
   ├─ node/                 app.js (stub), lib/main.js, index.html, package.json, config.example.json
   ├─ python/               passenger_wsgi.py (stub), app.py, config.example.json, requirements.txt
   └─ probe/probe.php       temporary, token-protected PHP environment probe
```

## Test status (October 2026, PHP 8.3 / Node 22 / Python 3.11)

| Area | Status |
|---|---|
| `ucoz_user()` in PHP, Node, Python — guest and logged-in member | ✅ live |
| URL mapping, proxy behaviour, trailing-slash redirect, ~60 s gateway timeout | ✅ live |
| MySQL from PHP (PDO), Node (`mysql2`), Python (PyMySQL); cron (curl, php, node, python); rollback via archive | ✅ live |
| npm dependencies (`node_install_modules`) · Python packages via a cron `pip` job | ✅ live · `python_install_modules` ❌ |
| Entry stubs: startup errors reported as JSON + `logs/startup.log` (Node, Python); PHP parse errors and fatals logged | ✅ live |
| Telegram: outbound `sendMessage` ✅ · inbound webhook ❌ (Telegram cannot connect) · polling bot R5 ✅ (link, /latest, new-material post, /stop, parallel polls) | |
| Recipes R1–R10 ✅ live (R7 search and R8 dashboard as procedures from live modules); Node/Python templates verified live — details in RECIPES.md | |

## Requirements

- Official uCoz MCP (`https://www.ucoz.com/mcp`) with `apps_read` / `apps_write` / `apps_delete`.
- Server Scripts available for the site (`apps_read` / `apps_write` / `apps_delete` status → available: true`).

## Install

```bash
npx skills add ucoz-skills/agent-skills --skill ucoz-server-scripts-skill
```

Or copy this folder into your agent's skills directory (e.g. `~/.claude/skills/ucoz-server-scripts-skill/`).

## Credits

Incorporates field notes from the community skill
[AndreyBgdnv/Ucoz-Server-Scripts-Skill](https://github.com/AndreyBgdnv/Ucoz-Server-Scripts-Skill) (MIT),
updated for the MCP `apps_read` / `apps_write` / `apps_delete` workflow and verified against the current platform.

## License

MIT
