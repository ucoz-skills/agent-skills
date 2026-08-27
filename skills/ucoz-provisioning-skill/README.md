# uCoz Site Launch & MCP Connection Assistant skill package

A portable skill package for AI agents that need to prepare a uCoz site for use with the official `ucoz-mcp` server.

## Changes in 1.2

**Runner** (`browser-runner/ucoz-provision.cjs`)

- **Fixed site creation.** With `PRESET_MATCH` / `PRESET_ID` set, the runner took the resume branch and skipped `/createsite`, failing with `site_configuration_cards_not_found`. Site existence is now decided by an HTTP probe (uCoz answers 404 for an unregistered subdomain) and reported as `siteExistedBeforeRun`. The site is created and the card applied in a single run.
- Dedicated `address_unavailable_or_existing` error when the site exists but its panel shows no cards: already configured, or owned by another account.
- **Anti-bot handling.** `/createsite` is guarded by CleanTalk Bot Detector, which scores time on page and pointer/keyboard activity. Added a pre-submit pause (`UCOZ_HUMAN_DELAY_MS`, default 8000 ms), pointer movement, real key events instead of `fill()`, and a wait for `ct_bot_detector_event_token`. On rejection the runner returns `rate_limited_or_bot_check` with advice to raise the delay.
- `enterPanelViaAccount()` — enters the panel through the owner SSO link from the account dashboard, so `MODE=existing` works on an owned site without its dedicated panel password.
- `safeUrl()` — redacts the password carried in SSO links (`password=`) before any URL is surfaced.
- `dumpDebug()` — on a stop, saves a screenshot and HTML into `out/` with secrets scrubbed first.
- `dotenv` is called with `quiet: true`: v17 prints tips to stdout and corrupts the JSON result contract.

**Security**

- Restored the `UCOZ_ALLOWED_ORIGINS` allowlist: credentials go only to `ucoz.ru`, `ucoz.com`, `umi.ru`. Without it `UCOZ_ORIGIN` accepts any host. This package lineage had no allowlist.
- Restored the environment filter in `provision-new-site.mjs`: the child process receives only the variables it needs, not the whole `process.env`. `HUMAN_DELAY_MS` added to that allowlist.
- Restored the SECURITY NOTE header block in the runner.

**Preset catalog** (v3) — `tgshop` / "Бот-магазин в Telegram" is now `botshop` / "Бот-магазин", verified against the live uCoz UI on 2026-08-27. The old id is kept as an alias for compatibility.

**Docs** — the runner defaults to `MODE=discover`, not `MODE=new`.


## What the package does

The package gives the agent a structured provisioning flow for:

- creating a new uCoz site or connecting an existing one;
- asking the right clarifying questions;
- safely requesting uCoz account access;
- reading available domain zones from the live uCoz interface;
- reading available site theme/configuration cards from the live uCoz interface;
- clarifying which modules to enable before uAPI/FTP setup;
- creating or reusing uAPI and FTP credentials;
- assembling MCP configuration for `ucoz-mcp`;
- verifying readiness;
- handing off to downstream skills.

This is **not** a site design/content skill and does not include “site building” scenarios.

## Package contents

```text
skills/ucoz-provisioning-skill/
├── SKILL.md
├── README.md
├── manifest.json
├── browser-runner/
│   ├── package.json
│   ├── preset-catalog.json
│   ├── ucoz-provision.cjs
│   └── provision-new-site.mjs
├── templates/
│   ├── hermes-config.yaml
│   ├── claude-desktop-config.json
│   ├── cursor-mcp.json
│   ├── generic-mcp-env.sh
│   └── generic-system-prompt.md
├── references/
│   ├── workflow.md
│   ├── downstream-skills.md
│   └── browser-runner.md
└── examples/
    ├── new-site-flow.md
    └── existing-site-flow.md
```

## Compatible agents

Canonical instructions are in `SKILL.md`; they are not tied to a specific agent. The package can be adapted for:

- Hermes Agent;
- Claude Desktop;
- Cursor;
- Codex / OpenAI-based agents;
- OpenWebUI or custom MCP clients;
- any agent with text instructions and MCP server connectivity.


## Environment requirements

The skill needs **browser automation capability** on provisioning steps that require the uCoz control panel in a browser.

Recommended stack:

```bash
node >= 18
playwright
```

Equivalent tools work if they can:

- log into the uCoz web interface;
- inspect live DOM/selectors;
- read available domain zones from the creation form;
- read site configuration/theme cards;
- submit forms and capture panel state.

The official `ucoz-mcp` server is required for MCP work after credentials are prepared; initial site creation and panel setup often need Playwright or similar.

## Required MCP server

Official uCoz MCP server:

```bash
npx -y ucoz-mcp@latest
```

Required environment variables:

```text
UCOZ_API_TOKEN
UCOZ_SITE_URL
UCOZ_FTP_HOST
UCOZ_FTP_USER
UCOZ_FTP_PASS
```

## Connection order

1. Connect or load this skill package in your agent.
2. Run the flow from `SKILL.md`.
3. Install Playwright dependencies once on your machine (local setup, not at agent runtime): `cd browser-runner && npm install && npx playwright install chromium`.
4. Before running, set credentials in one of two ways:
   - fill local `browser-runner/.env` on your machine (not included in the public archive) (`UCOZ_EMAIL`, `UCOZ_PASSWORD`, etc.);
   - or send data to the agent so it fills `.env` for you.
   Template in the public archive: `browser-runner/.env.example`.
   If `UCOZ_EMAIL` / `UCOZ_PASSWORD` are missing, the agent must explicitly offer these two options.
5. After obtaining email, password, and address, create the site via **`browser-runner`** (see `references/browser-runner.md`), then MCP steps.
6. Choose modules before uAPI/FTP with user consent.
7. Configure uAPI and FTP.
8. Add `ucoz-mcp` to the agent MCP configuration.
9. Verify with a safe read/list action.
10. If needed, hand off to the next task skill.

## Security

The public package intentionally omits real credentials, domains, tokens, and browser auth artifacts. Templates use placeholders only.

Do not paste full secrets into public chats and logs. Mask secrets in user summaries, for example:

```text
uAPI: sk_live_***abcd
FTP password: set, hidden
```

## Downstream skills

The package reserves a registry slot in `manifest.json`:

```json
"downstream_skills": []
```

Add task skills there: shop, blog, users, forms, etc. This helper should finish provisioning and then hand off control.


## Public package hygiene

The public ZIP excludes `node_modules/`, `.env`, `.env.local`, `browser-runner/out/`, browser auth state, cookies, raw uAPI tokens, FTP passwords, account passwords, and live provisioning results. Install dependencies locally with `npm install` (one-time setup before first use).
