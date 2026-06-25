# Execution: browser runner (Playwright)

The skill **must** be executed by the agent not only in words but also through the **officially documented** runner in this package when a new site needs to be created via the web interface.

## Location

```
skills/ucoz-provisioning-skill/browser-runner/
├── package.json
├── preset-catalog.json     # all uCoz presets with data-preset-id and aliases
├── ucoz-provision.cjs      # unified runner: MODE discover|new|existing, UCOZ_* and alternate variables
└── provision-new-site.mjs  # thin wrapper → calls ucoz-provision.cjs (backward compatibility)
```

**`ucoz-provision.cjs`** is the main script: defaults to `MODE=new` (unless `BASE_URL` is set without a site address). Supports the address as **`UCOZ_SITE_ADDRESS`** or **`SITE_PREFIX` + `DOMAIN`**, preset as **`UCOZ_PRESET_SUBSTR`** or **`PRESET_MATCH`**, `UCOZ_HEADLESS` / **`UCOZ_SLOW_MO_MS`** / **`UCOZ_USER_AGENT`**, writes **`out/provision-result.json`**, machine-readable responses (`needs: …`), and optionally **`SETUP_UAPI`**. Mode **`MODE=discover`** lists zones and presets from the catalog (must be set explicitly). FTP is configured via MCP (`ftp_tool`), not through the runner.

**`preset-catalog.json`** is the current uCoz preset reference with `title`, `presetId` (`data-preset-id` in the DOM), `substr` (substring for matching), and `aliases`. The runner validates against it before launching the browser and uses `presetId` as the primary click key.

Selectors and navigation align with `references/NEW_SITE_WORKFLOW.md`. When uCoz markup changes, update **`ucoz-provision.cjs`**.

## Installation (one-time)

From the `browser-runner/` directory:

```bash
npm install
npx playwright install chromium
```

The runner reads `browser-runner/.env` and `browser-runner/.env.local` automatically (shell variables take precedence). `dotenv` is already included in `browser-runner/package.json` dependencies.
Quick start: copy `browser-runner/.env.example` to `browser-runner/.env` and fill in the values.

## Environment variables

| Variable | Required | Description |
|----------|----------|-------------|
| `UCOZ_EMAIL` | yes | uCoz account email |
| `UCOZ_PASSWORD` | yes | Account password (do not log) |
| `UCOZ_SITE_ADDRESS` | yes* | Full address like `prefix.zone`, e.g. `cursor-agent-007.ucoz.org` |
| `UCOZ_SITE_PREFIX` | yes* | If `UCOZ_SITE_ADDRESS` is not set — prefix only |
| `UCOZ_DOMAIN_SUFFIX` | yes* | If `UCOZ_SITE_ADDRESS` is not set — zone suffix without a leading dot (`ucoz.org`) |
| `UCOZ_ORIGIN` | no | Default `https://www.ucoz.ru` |
| `UCOZ_PRESET_SUBSTR` | no | Substring for the preset card; no safe default; chosen by the user from the live UI. Validated against `preset-catalog.json` before the browser starts (unless disabled) |
| `UCOZ_PRESET_STRICT` | no | Default `1` — unknown themes exit the runner with a clear list from the catalog. `0` / `false` / `no` — skip validation (stale catalog or new uCoz card) |
| `UCOZ_SITE_TITLE` | no | Site name field in the preset modal; default = prefix |
| `UCOZ_HEADLESS` | no | `1` / `true` — no browser window; **headless is on by default** |
| `UCOZ_SLOW_MO_MS` | no | Playwright action delay in ms (debugging) |
| `UCOZ_USER_AGENT` | no | Custom User-Agent |
| `MODE` | no | `discover` \| `new` \| `existing`. Default `new`, or `existing` if only `BASE_URL` is set |
| `SITE_PREFIX` | yes† | Together with `DOMAIN` — alternative to `UCOZ_SITE_ADDRESS` |
| `DOMAIN` | yes† | Zone (`ucoz.org`). If missing — on `new`, exits with `needs: domain_zone_choice` |
| `BASE_URL` | yes‡ | For `MODE=existing` |
| `PRESET_MATCH` | no | Synonym for `UCOZ_PRESET_SUBSTR` |
| `PRESET_INDEX` | no | Card number after agreement with the list |
| `PRESET_LOOSE_REGEX` | no | `true` — treat `PRESET_MATCH` as RegExp without the catalog |
| `SITE_TITLE` | no | Synonym for `UCOZ_SITE_TITLE` |
| `HEADLESS` | no | `false` / `0` — show the browser window (along with `UCOZ_HEADLESS`) |
| `PANEL_PASSWORD` / `UCOZ_PANEL_PASSWORD` | no | Separate site control panel password (`/admin/`) if it differs from the uCoz account password. On login failure with `UCOZ_PASSWORD`, the runner emits `needs: panel_password_separate` — set this variable and rerun. |
| `SETUP_UAPI` | no | `true` — create or retrieve a uAPI key in the panel |
| `OUTPUT_SECRETS` | no | `true` — full tokens in output |

\* Either `UCOZ_SITE_ADDRESS`, or the pair `UCOZ_SITE_PREFIX` + `UCOZ_DOMAIN_SUFFIX`, or **`SITE_PREFIX` + `DOMAIN`**.

† For `MODE=new`, an address must be specified: see \*.

‡ For `MODE=existing`.

## Agent protocol: chat → ENV/.env → run

1. **Chat:** using the table above and `SKILL.md`, ask the user for all parameters; get explicit answers or confirmation of defaults (preset, title, address, options).
2. **Chat summary:** list agreed non-secret parameters (email, address, preset, title, options).
3. **ENV or `.env`:** offer two options:
   - the user fills `browser-runner/.env` themselves (`UCOZ_EMAIL`, `UCOZ_PASSWORD`, …);
   - or the user sends values in chat and the agent fills `.env`.
   If `UCOZ_EMAIL` / `UCOZ_PASSWORD` are not set, the agent must explicitly offer these two paths instead of running blindly.
4. **Run:** `npm run provision` from `browser-runner/` only after the user has set ENV on their machine.

The agent **must not** skip clarification steps for “speed” and **must not** substitute preset/address on behalf of the user without a chat response.

## Running

PowerShell (example):

```powershell
cd "…\skills\ucoz-provisioning-skill\browser-runner"
$env:UCOZ_EMAIL="<email from chat>"
$env:UCOZ_SITE_ADDRESS="cursor-agent-007.ucoz.org"
$env:UCOZ_PRESET_SUBSTR="<card chosen from live UI>"
# Password only manually in the terminal, not from agent chat:
$env:UCOZ_PASSWORD="<paste locally>"
npm run provision
```

`.env` (example):

```dotenv
UCOZ_EMAIL=you@example.com
UCOZ_PASSWORD=your_password
SECRET_ANSWER=your_secret_answer
```

Result: **stdout** — final JSON; file `out/provision-result.json` (no secrets, `out/` in `.gitignore`). With `SETUP_UAPI=true`, see `uapi`, `mcp` fields in JSON. FTP is configured separately via MCP `ftp_tool`.

Important: check module status via MCP (`modules_tool` → `module_status`), not by scraping the `?a=allmod` page.

## What the runner does

1. Login at `UCOZ_ORIGIN/login`.
2. Mode **`MODE=discover`**: zones only and reference `catalogPresets` from JSON.
3. Mode **`MODE=new`**: `/createsite` → address + zone + consent → submit → wait for the “Module set” screen → click the preset card (by `data-preset-id` / substring) → “Site name” modal → real Playwright `.click()` on `#submit_btn_auto_preset` → first panel login (account password if needed).
4. Mode **`MODE=existing`**: navigate to the panel via `BASE_URL`.
5. Optionally (`SETUP_UAPI`): uAPI key via the panel; MCP env stub in JSON. FTP — via MCP `ftp_tool` after updating the MCP config.
6. After keys/FTP, separately ask the user which modules to enable, turn them on, then verify uAPI access checkboxes for those modules.

### Known uCoz markup quirks

- Preset cards are `<a data-preset-id="…" class="preset-btn fancy-modal">` (priority selector). Title inside `.preset-title` of the parent `.preset-card`.
- After clicking a card, a fancybox overlay `#auto_preset_modal` opens. The Create site button (`#submit_btn_auto_preset`; Russian UI label) is clicked with a real Playwright `.click()`; do not use synthetic DOM events as the primary path because the uCoz UI may ignore or distrust synthetic events.
- On first login to a new site panel, a redirect to `/admin/` with a password field is possible — the runner handles it automatically.

## What the runner does **not** do yet

- Does not enable arbitrary modules by scenario without separate panel steps.
- Does not bypass captcha/hard anti-bot: on failure — log and manual pass-through.

## Security

- Do not paste real passwords into agent chat; pass them only via env on the user’s machine (after clarifying the scenario in chat — see protocol at the top of this file).
- Artifact `out/provision-site-created.json` does not contain passwords; add `out/` to the project `.gitignore` if needed.
