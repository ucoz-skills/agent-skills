# Workflow reference

Details on browser selectors and runner variables: `references/browser-runner.md`. Form and preset selectors: `references/NEW_SITE_WORKFLOW.md`.

## New site

1. Clarify in **chat** everything needed: email, account access, site address (`prefix.zone` or in parts), preset (`UCOZ_PRESET_SUBSTR`), title (`UCOZ_SITE_TITLE`), and if needed `UCOZ_ORIGIN` / headless / slowMo. Do **not** repeat the password in responses; agree on one of two paths: the user fills ENV/`.env` themselves or sends data to the agent to write `.env`.
2. Briefly **summarize in chat** agreed non-confidential parameters.
3. Give the user two options: an **ENV** command block (`$env:UCOZ_EMAIL=`, …) or filling `browser-runner/.env`; if variables are not set, explicitly offer these two paths.
4. After installing dependencies, run `browser-runner`: `npm install`, `npx playwright install chromium`, `npm run provision` with variables from step 3.
5. Take `UCOZ_SITE_URL` / `browser-runner/out/provision-site-created.json` as the canonical URL of the new site.
6. Get the module list via MCP (`modules_tool` → `modules_list`), clarify in **chat** which to enable, activate via `module_install` (only with consent), verify via `module_status`.
7. Configure the uAPI key via the browser (runner) or reuse an existing one.
8. Configure FTP via MCP: `ftp_password_get` → `ftp_password_set` (or `ftp_password_change`).
9. After keys and FTP, separately ask which additional modules to enable; `module_install` on confirmation.
10. Enable uAPI access for activated modules: `module_enable_uapi`. **Fallback:** if MCP returns an error — `/panel/?a=uapi` in the browser, checkboxes manually.
11. **Final step — quarantine:** `quarantine_status`. If the site is quarantined — offer `quarantine_unlock`, explaining that otherwise the site is not indexed. Only with consent.
12. Assemble `ucoz-mcp` config, verify readiness, hand off to the next skill.

## Existing site

1. Request uCoz email/password.
2. Request site URL.
3. Log in to uCoz and open the site panel (browser runner `MODE=existing`).
4. Get the module list via MCP (`modules_tool` → `modules_list`), clarify which to enable; `module_install` with consent.
5. Configure the uAPI key via the browser or reuse an existing one.
6. Configure FTP via MCP: `ftp_password_get` → `ftp_password_set` / `ftp_password_change` / `ftp_password_reset`.
7. After keys and FTP, clarify additional modules; `module_install` on confirmation.
8. Enable uAPI access: `module_enable_uapi`. **Fallback:** `/panel/?a=uapi` in the browser on MCP error.
9. **Final step — quarantine:** `quarantine_status`. If active — offer `quarantine_unlock` with an explanation about indexing. Only with consent.
10. Assemble MCP config.
11. Verify readiness.
12. Hand off to the next skill.
