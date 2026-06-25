---
name: ucoz-provisioning-skill
description: >
  Agent skill for preparing a uCoz site for work via MCP: create a new site or configure an existing one, ask clarifying questions for the scenario,
  choose available domain zones and site theme presets from the live uCoz interface, enable required modules, create/reuse uAPI and FTP credentials,
  assemble the official ucoz-mcp configuration, verify access, and hand off control to downstream task skills.
version: "1.1"
author: uCoz / Hermes Agent
license: MIT
requires:
  - browser automation capability for site provisioning via the uCoz web interface
  - recommended: node >= 18
  - recommended: playwright
  - official ucoz-mcp for MCP operations
metadata:
  tags: [ucoz, mcp, provisioning, helper, onboarding, portable-skill]
  hermes:
    tags: [ucoz, mcp, provisioning, onboarding, website-builder]
  compatible_agents: [Hermes, Claude Desktop, Cursor, Codex, OpenAI Agents, OpenWebUI, generic MCP clients]
  required_mcp_servers: [ucoz-mcp]
  downstream_skills: []
---

# uCoz Site Launch and MCP Connection Assistant (uCoz MCP Launch Assistant)

## Purpose

This is a portable agent skill for preparing a uCoz site so that any MCP-capable agent can work with it through the official `ucoz-mcp` server.

The skill is intentionally a **provisioning/preparation helper layer**, not a content or page layout skill. It finishes when the site is ready, MCP credentials are collected, and the agent can hand the task off to the next task skill.


## Runtime Requirements

The provisioning scenario requires an agent environment with **browser automation capability**, because the agent must interact with the live uCoz web interface: site creation, domain zone discovery, theme/configuration cards, module enablement, uAPI and FTP setup.

Recommended stack:

```bash
node >= 18
playwright
```

Similar browser automation tools also work if they can read the live DOM, click elements, submit forms, and read page state.

The official `ucoz-mcp` server remains the required MCP execution layer after credentials are prepared. Browser automation is for onboarding/provisioning; MCP is for operations on the connected site.

## Mandatory Execution: New Site via Browser

**Order without exceptions:** first **chat dialog** (clarify and confirm agreement on each parameter), then **write to environment variables** and run the runner. Do not silently substitute a preset, address, or arbitrary `UCOZ_ORIGIN` based on guesswork alone.

### What the agent must clarify in chat (new site)

1. uCoz account login email (can be provided as plain text — it is not a password secret).
2. Account password: the agent **does not** replay the secret from history unless necessary; then one of two paths — the user sets the password locally in ENV/.env **or** explicitly passes it to the agent for local write to `.env` (without publishing the secret in package files/commits).
3. New site address: full `prefix.zone` string **or** prefix and desired zone separately — and **confirmation** before launch.
4. Site theme/configuration card is chosen from the **live uCoz interface**. `browser-runner/preset-catalog.json` is advisory/fallback only for aliases and debugging. Do not choose "Landing" or any other preset by default; if the user asks for a theme not in the live UI, say so explicitly and ask them to choose from the visible list.
5. Site name in the modal: `UCOZ_SITE_TITLE`, if the prefix alone is not enough.
6. If needed: non-default `UCOZ_ORIGIN`, `UCOZ_HEADLESS` mode.

After answers, the agent briefly **summarizes the agreed configuration in chat** (without password or tokens).

### Moving agreed values to ENV/.env

The agent **offers 2 options**:
1. The user sets ENV/`.env` themselves (`$env:...` commands or filling `browser-runner/.env`).
2. The user passes values in chat, and the agent fills `.env` itself.

Passwords and tokens are not printed in user summaries and are not committed to the repository.

After that: working directory `browser-runner/`, then `npm run provision`.

---

Once parameters are agreed in chat and reflected in ENV, the agent runs the **built-in Playwright runner** from this package and completes the web chain end to end (login → `/createsite` → presets → panel) per `references/browser-runner.md` and `references/NEW_SITE_WORKFLOW.md`.

1. **Working directory**: `browser-runner/` inside this skill package.
2. **Secrets**: values for `UCOZ_PASSWORD`, uAPI tokens, FTP passwords — only in the user's local environment or MCP secrets; **do not** paste into chat messages or commit to git.
3. **Commands**:
   ```bash
   cd browser-runner && npm install && npx playwright install chromium
   # Set secrets locally in .env or via environment variables, then:
   npm run provision
   ```
4. **Runner result**: site URL (`UCOZ_SITE_URL=`) and file `browser-runner/out/provision-site-created.json`; then skill steps: modules (with explicit user confirmation), uAPI, FTP, MCP config, verification.

If the platform changed markup and the runner fails, the agent **first** fixes `browser-runner/ucoz-provision.cjs`/selectors or opens a ticket to update the package, **then** if needed offers a manual workaround only as an exception.

## Scope

The skill covers:

1. Creating a new uCoz site.
2. Connecting/configuring an existing uCoz site.
3. Asking necessary clarifying questions.
4. Reading available domain zones from the live uCoz interface.
5. Reading available site theme/configuration cards from the live uCoz interface.
6. Asking which modules to enable before uAPI/FTP setup.
7. Creating or reusing a uAPI key.
8. Setting up or reusing FTP credentials.
9. Assembling configuration for the official `ucoz-mcp` server.
10. Verifying readiness and handing off to downstream skills.

The skill **does not** include downstream scenarios: site design, page content, shop, blog, user management, form builder. Those belong to separate skills.

## Execution Stack

```text
User request
  ↓
ucoz-site-provision-helper
  ↓
official ucoz-mcp server
  ↓
downstream skills: site design, shop, blog, users, forms, etc.
```

Use the official MCP server:

```bash
npx -y ucoz-mcp@latest
```

Required environment variables for `ucoz-mcp`:

```text
UCOZ_API_TOKEN
UCOZ_SITE_URL
UCOZ_FTP_HOST
UCOZ_FTP_USER
UCOZ_FTP_PASS
```

## Downstream Skill Registry

Agents can maintain a registry in `manifest.json`, agent configuration, or this skill's frontmatter.

Recommended structure:

```json
{
  "downstream_skills": [
    {
      "name": "ucoz-task-skill-name",
      "trigger": "Which user request should hand off control to this skill",
      "requires_modules": ["module_key_or_human_name"],
      "handoff_after": "MCP credentials configured and verified"
    }
  ]
}
```

When provisioning is complete, assess the user's next goal and connect or invoke the appropriate downstream skill. If none exists — stop and tell the user the site is ready for MCP work.

## Optional downstream handoff

After MCP provisioning, you may suggest the next step only based on the user's request context: landing, news, shop, users, forms, or another downstream skill. The helper itself does not require or include a downstream skill by default; the registry is empty until explicitly configured.

## Scenario Routing

At the start, classify the request:

| Scenario | Typical user request | Required clarifications |
|---|---|---|
| New site, address not set | "Create a new uCoz site" | Ask for desired address prefix, then fetch domain zones from uCoz and offer the user a choice |
| New site, address set | "Create example.ucoz.net" | Confirm recognized prefix and zone; verify in the live interface that the zone is available |
| Existing site | "Connect my existing uCoz site" | Clarify/confirm site URL and account credentials |
| Credentials only | "Prepare MCP credentials" | Clarify whether a site already exists; if "yes" — existing site scenario |
| Downstream task first | "Build me a shop/blog/company site" | Explain that site provisioning comes first; then hand off to the next skill |

If ambiguous, ask one routing question:

```text
Create a new uCoz site or connect an existing one?
```

## Mandatory Questions

### Common first step

Before protected actions, request in **chat** everything needed for the scenario (see "What the agent must clarify" above for a new site, and similarly for existing — site URL). After agreement, **translate decisions into environment variables** (commands for the user's PowerShell/shell) before running the runner or manual panel login.

```text
To prepare the site, access to the uCoz account is required:
1. uCoz account email — clarify and confirm in chat
2. Password — do not duplicate in logs; agree on one of two paths: user sets `UCOZ_PASSWORD` locally (ENV/`.env`) or passes it to the agent for local write to `.env` (without exposing the secret in a public response)
```

For an existing site, also ask:

```text
Send the URL of the existing uCoz site to connect.
```

### New site without address

If the user asks for a new site but did not specify an address:

```text
What site address do you want?
Specify the desired prefix, for example `my-project`.
I will check the currently available uCoz domain zones and offer you a choice.
```

Then log into the account, open the site creation interface, read all available domain zones from the live selector, and show them to the user.

Do not assume there is only the `.ucoz.net` zone. Do not generate a random "production" address unless the user explicitly said this is test/development and a random address is acceptable.

### Domain zone selection

Read domain zones from the live uCoz interface, usually from `select#domains[name="dom"]` or the current equivalent.

Ask:

```text
Available uCoz domain zones: <zones>. Which to use for `<prefix>`?
```

If prefix+zone is taken — ask for another prefix or zone.

### Site theme/configuration card

For the **runner** `browser-runner/ucoz-provision.cjs` (and the `provision-new-site.mjs` layer), the fixed preset list and synonyms live in **`browser-runner/preset-catalog.json`** — the agent matches the user's request against this file **before** launch and on mismatch **explicitly reports** that the theme is not in the list (the runner also prints the list when `UCOZ_PRESET_STRICT=1`). For a purely manual panel walkthrough, still rely on live cards on screen.

After the address is accepted, when the interface shows site setup cards:

1. Read all visible cards/presets in the current interface.
2. Extract visible title, internal key/id if present, description, and listed modules if shown.
3. Ask the user to choose from the current list.

Template:

```text
I see these site configurations: <cards>. Which one to choose?
```

Do not hardcode card order. Do not choose automatically unless the user's goal unambiguously matches one visible card.

### Site name

If the chosen card requires a site name:

```text
What site name should be set in the control panel?
```

If the user already named the project or company — suggest it, but ask for confirmation.

### Module enablement before uAPI/FTP

After the site is created and before uAPI/FTP:

```text
Which modules to enable before uAPI and FTP setup?
I can review available modules and suggest a minimal set for your next task.
```

Rules:

- Get the module list via MCP: `modules_tool` → `modules_list` (fields `active`, `api_enabled`). This replaces manual browser scraping of `/panel/?a=allmod`.
- If the next skill is known — read its `requires_modules` and suggest those modules.
- If the next skill is unknown — ask the user to choose or pick a minimal configuration.
- Do not enable modules silently.
- Confirm enablement before changing site state.
- Activate via MCP: `modules_tool` → `module_install`. Check status via `module_status`.

### uAPI setup

After the module decision:

1. Open the uAPI page in the site panel.
2. Enable uAPI access for modules via MCP: `modules_tool` → `module_enable_uapi` (with user confirmation). **Fallback:** if MCP returns an error — open `/panel/?a=uapi` in the browser and enable checkboxes manually on that page only.
3. Create a uAPI key only if no suitable one exists or the user asks for a new one.
4. Use a human-readable key name tied to the task, e.g. "MCP site connection" or "Agent integration".
5. **REQUIRED** when creating a key: enable the "API access to control panel settings" switch (`input#adv_switcher[name="allow_cp_settings"]`). Without it the key will not get access to panel settings and modules. In the POST form this field is `allow_cp_settings=on`. Via browser: ensure the checkbox is checked (`background-color: var(--panel7-blue-550)`) before clicking "Add".
6. Prefer extracting the full token from raw DOM/HTML, e.g. `data-copy="sk_live_..."` attributes, when the interface visually masks the value.
7. Do not delete or reissue existing keys without explicit confirmation.

> **CRITICAL — "User" field when creating a uAPI key:**
> The uAPI key creation form has a `user` field. It must be filled with the login of an **already existing** site user with ID=1 (owner/primary administrator).
> The login is read from `/panel/?a=users&l=find` — first table row where the first cell contains `1`.
> **NEVER create a new user in the control panel just to obtain a login for uAPI.**
> Creating a new user for this purpose is an error: such a user is not an administrator and the key will have insufficient permissions.

### FTP setup

After uAPI:

1. Get FTP connection parameters via MCP: `ftp_tool` → `ftp_password_get`. Returns host and login; password is not exposed.
2. If password is not set yet — set via `ftp_tool` → `ftp_password_set`. Follow uCoz limits: 6–15 characters, Latin letters, digits, `_`, `-`.
3. If password is already set — do not overwrite by default. Ask whether to use existing credentials or change via `ftp_password_change`.
4. If a full reset is needed — `ftp_password_reset` (only with explicit confirmation).
5. Warn that changing the FTP password may break existing FTP clients/integrations.

### Additional module activation after keys/FTP

After `uAPI` and FTP setup, ask a control question:

```text
Do you need to additionally activate any modules (e.g. "Mail forms", "Video")?
```

If yes:
1. Activate via MCP: `modules_tool` → `module_install` (only after explicit confirmation).
2. Verify via `module_status` — module must have `active: true`.
3. Enable uAPI access for activated modules: `modules_tool` → `module_enable_uapi` (only with confirmation). **Fallback:** if MCP returns an error — open `/panel/?a=uapi` in the browser and enable checkboxes manually on that page.

### MCP configuration and mandatory server switch

Assemble configuration for the official MCP server and **immediately update** the server entry in the agent config (`mcp.json` / `cursor-mcp.json` / MCP environment variables).

> **CRITICAL:** After obtaining the uAPI key — before any call to `ftp_tool`, `modules_tool`, or any other MCP tool — update the MCP server config, otherwise tools will use credentials from the **previous** site. Calling MCP tools without switching config is a common mistake that returns data from the wrong site.

Action order:

1. After obtaining `sk_live_...` from the runner or panel — **immediately** update `mcp.json`:
   - `UCOZ_API_TOKEN` = new token
   - `UCOZ_SITE_URL` = new site URL
   - `UCOZ_FTP_HOST`, `UCOZ_FTP_USER`, `UCOZ_FTP_PASS` — fill on the next step
2. Restart the MCP server (if the agent keeps it running).
3. Only after that call `ftp_tool` → `ftp_password_get` / `ftp_password_set` — they will return data for **the new site**.

Example `mcp.json`:

```json
{
  "mcpServers": {
    "ucoz": {
      "command": "npx",
      "args": ["-y", "ucoz-mcp@latest"],
      "env": {
        "UCOZ_API_TOKEN": "<new site sk_live_token>",
        "UCOZ_SITE_URL": "https://example.ucoz.net/",
        "UCOZ_FTP_HOST": "<from ftp_password_get>",
        "UCOZ_FTP_USER": "<from ftp_password_get>",
        "UCOZ_FTP_PASS": "<set via ftp_password_set>"
      }
    }
  }
}
```

Agent-specific templates are in the `templates/` directory.

### Verification

Verify in layers:

1. Public site URL opens.
2. Admin panel opens.
3. Requested modules are active — check via `modules_tool` → `module_status`.
4. uAPI key exists, token saved.
5. FTP parameters obtained via `ftp_tool` → `ftp_password_get`; password set/confirmed.
6. MCP config assembled.
7. If MCP is already connected to the current agent — call a safe read/list method.
8. **Final step — quarantine:** call `modules_tool` → `quarantine_status`. If the site is under quarantine, offer the user to remove it via `quarantine_unlock`, explaining: "The site is currently closed to search engine indexing. Remove quarantine so it starts indexing?" Execute only with explicit consent.

## Final Response Format

Return a brief masked summary:

```text
Done: uCoz site prepared for MCP.

Site: https://example.ucoz.net/
Scenario: new | existing
Site configuration: <chosen card>
Enabled modules: <list>
uAPI: created/reused existing, sk_live_***abcd
FTP: configured/reused existing, user=<ftp user>, host=<host>
MCP config: assembled/written
Verification: <passed checks>
Next: <downstream skill or question to user>
```

Never include in chat, screenshots, examples, or public package files: raw passwords, full API tokens, browser auth artifacts.

## Common Mistakes

1. Assuming the only domain zone is `.ucoz.net`.
2. Assuming theme/configuration card order is fixed.
3. Enabling modules without explicit consent.
4. Setting up uAPI/FTP before the module decision.
5. Reissuing uAPI keys by default.
6. Overwriting FTP password without confirmation.
7. Mixing site provisioning with downstream "site building" tasks.
8. Printing secrets in user output or public examples.
9. Treating this skill as the MCP server; it is a workflow layer over official `ucoz-mcp`.
10. Suggesting a theme like "Blog" without checking `preset-catalog.json` / live screen — if the card is missing, **say so explicitly**, do not stay silent or substitute another preset without discussion.
11. Trying to "create a site" again at an address already in "My sites" for the same account — need a **different free prefix** or log into the existing site from the list.
12. **Creating a new user in the control panel to bind a uAPI key.** The uAPI form `user` field must contain the login of existing user ID=1 — read from `/panel/?a=users&l=find`, do not register a new user.
13. **Creating a uAPI key without enabling the `allow_cp_settings` switch.** Without this flag the key has no access to panel settings and modules — MCP integration will not work.
14. **Calling MCP tools (`ftp_tool`, `modules_tool`, etc.) before updating the MCP server config.** After obtaining a new uAPI key, write it to `mcp.json` and restart the server first — otherwise tools return data from the previous site, not the new one.
15. **Not updating `UCOZ_FTP_HOST` / `UCOZ_FTP_USER` in config after `ftp_password_get`.** The real FTP host and login come from `ftp_password_get` (they differ from the site domain name), so the config must include these values before handing off to downstream skills.
16. **Using `waitForNavigation` after clicking "Create site" on `/createsite`.** uCoz does not do a full redirect — content updates via JavaScript. After click wait for `[data-preset-id]` via `waitForSelector`, not `waitForNavigation`. Click the submit button with `{ force: true }` to bypass possible overlays.

## Panel Auth Rule

- Before entering a newly created or existing site panel, inspect the live admin login page.
- If the page shows a regular password form, log in through that form first.
  - **Separate panel password:** the `/admin/` page may require a password different from the uCoz account password — older sites may have a separate control panel password set in site settings. If after submitting the form the page stays on the gate or shows an error ("Wrong password", etc.) — **do not retry** the same password. Immediately ask the user: "Login to panel `/admin/` did not accept the uCoz account password. This site may have a separate panel password. Provide the control panel password." Obtain it as `PANEL_PASSWORD` and try again.
- If the page shows a `uID` button/link instead of a regular form, follow the `uID` flow and sign in there with the same confirmed credentials.
- Do not hardcode only one panel-login method; choose the branch from the page state.

## Phone Popup Rule

- If the site panel shows the phone-confirmation popup (`#ucoz_phone_popup`), close it before clicking module actions, save buttons, or setup controls.
- Prefer the page's own close handler such as `closePhonePopup()` when it exists; fall back to removing the popup only if needed.
- Re-check for this popup after panel navigation, because it can reappear and block pointer events.
