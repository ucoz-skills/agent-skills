# New Site Workflow Reference

Canonical rules are in `SKILL.md`. This file is an advisory reference for public packages.

## Source of truth

- Domain zones are read from the live uCoz UI.
- Site configuration cards/presets are read from the live uCoz UI.
- `browser-runner/preset-catalog.json` is advisory/fallback only.
- Do not choose Landing or any other preset silently.
- Use real Playwright `.click()` for UI submit; do not rely on synthetic DOM events for uCoz panel actions.

## Safe order

1. Account access.
2. New/existing site scenario.
3. Address/prefix and live domain zone.
4. Live configuration card choice.
5. Modules to activate.
6. uAPI setup.
7. FTP helper extraction and FTP password setup.
8. MCP config assembly.
9. Verification.
10. Optional downstream handoff only if the user requests a downstream task.

## FTP notes

Extract FTP host/login from File Manager helper first: `/panel/?a=fm` + `uCoz.utils.helper(1)`. Then open `/panel/?a=ftppass` for password setup/change. If a password gate or security-question answer is required, ask the user; do not guess.
