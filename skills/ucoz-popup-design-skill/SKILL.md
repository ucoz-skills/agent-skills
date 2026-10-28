---
name: ucoz-popup-design-skill
description: >-
  Restyle the native popups of a uCoz site through the official uCoz MCP tools: system windows
  (`_uWnd`), confirmations, forms inside windows (such as login and the mail form result) and `uToast`
  notifications. Five ready presets (Light, Dark, Lavender, Mint, Peach), a palette matched to the
  live site, or a custom palette with contrast checks. Publishes one versioned CSS file linked from a
  marked template insertion, with a backup, exact read-back and one-step rollback. Use for "make the
  popups match my site", "dark notifications", "restyle the login window", "remove the popup styles".
  Not for a full site redesign (ucoz-design-editor-skill), new templates (ucoz-template-creator-skill)
  or site content (ucoz-content-manager-skill).
version: "1.0"
author: uCoz
language: en
tags:
  - ucoz
  - mcp
  - design
  - css
  - popups
  - notifications
entrypoint: SKILL.md
references:
  - references/COMPONENTS.md
  - references/THEMES.md
  - references/PUBLISHING.md
  - references/VERIFICATION.md
---

# uCoz Popup Design Skill

Version: 1.0

## Usage accounting

After the stylesheet is published and verified, record the usage:

```text
skills_tool(action="register_usage", skill_id="ucoz-popup-design-skill")
```

If usage accounting is unavailable or returns an error, show a brief warning and continue the skill's main workflow. Do not ask the user for a UUID, token, site URL, or uAPI key.

## Purpose

You restyle the popups that uCoz itself draws on a live site: `_uWnd` windows, confirmations, forms inside windows and `uToast` notifications. They are not in templates — platform JavaScript appends them to `<body>` — so the only lever is CSS loaded after the system styles.

Three outcomes define success:

1. **The popups look like the site** (or like the chosen preset), in light and dark modes, on desktop and at 375px.
2. **Nothing stops working**: windows still open, close, drag, resize and submit; fields keep their native behaviour and keyboard access.
3. **Nothing else changed**: menus, lightbox, Control Panel dialogs and the rest of the page look exactly as before, and the change can be removed in one step.

## Connection model

Everything goes through the MCP tools on the site chosen with `select_site`. No API key, token or site URL is needed in the client.

1. `list_sites` → match the user's domain → `select_site(site_id)` → check that the returned host is that domain. Ask which site if there are several and the user did not name one.
2. Work with the tool family for the job (see the map below).

A permission error means the signed-in account cannot do that operation. Say so; do not look for workarounds.

## Tool map

| Job | Read | Write | Delete / destructive |
|---|---|---|---|
| Template ids, template code, patch check | `templates_read` `list_modules`, `read_template`, `validate_template` | — | — |
| Template backups | `templates_read` `get_backup_guide`, `list_backups` | `templates_write` `create_backup` | — |
| CSS link in templates | `templates_read` `read_template` | `templates_write` `patch_template` | rollback = `patch_template` with `code_paste: ""` |
| The CSS file | `files_read` `quota`, `list`, `content_get` | `files_write` `mkdir`, `content_put` | `files_delete` (`confirm: true`, only after the user approves) |

Not this skill: `update_template`, `tmaker_publish`, `templates_delete`, `settings_write`, and the content, users and shop tools.

## Non-negotiables

1. **Separate file, one link.** The styles live in one versioned file in the file manager (`uwnd-design-<version>.css`), linked once between `<!-- uwnd-design:start v<version> -->` and `<!-- uwnd-design:end -->`. Never inline the styles into templates, never edit the stock CSS template, never touch system files under `/.s/`. The file and marker names stay `uwnd-design` so that sites styled by earlier versions are updated in place, not duplicated.
2. **Patch, don't rewrite.** Only `patch_template` with a byte-exact `code_search` from a fresh `read_template`, checked first with `validate_template`. Never `update_template` or a full skeleton. Keep `$AJAX_JS$`, `$POWERED_BY$`, template conditions and functional form fields.
3. **Scope every ordinary-window rule** to `body [id^="_uwndTop"]:not(.x-lb,.cp-modal,.cp-alert,.uwnd-toast)`. Toasts are styled separately under `.uwnd-toast`. Do not restyle `.xw-shadow` globally (menus use it too), icon fonts or `--layer-*` variables outside the toast.
4. **Native geometry stays.** Keep inline sizes, hidden controls, drag/resize regions, close/minimize/maximize buttons and their handlers. Never monkeypatch the `_uWnd` / `uToast` library.
5. **Fields stay native.** Keep input semantics, labels, keyboard behaviour; inset focus outlines so they are not clipped; room for exactly one select arrow; multiple selects untouched; never `width:100%` on every field.
6. **Contrast 4.5:1** for key text pairs, presets and custom palettes alike. `scripts/build_theme.py` refuses palettes below it.
7. **No personal data.** The audit reads DOM structure and computed styles only — never field values, cookies or tokens. Never submit real forms for a visual check.
8. **Honest accessibility.** CSS cannot add focus traps, ARIA roles or tabindex. Never claim full accessibility.

## Safety rules

- Installing the skill is not permission to publish. Publish only what the user approved; a request for a preview stays local.
- Do not repeat questions the user already answered and do not ask again for an approval already given.
- Read before you write: save the original of every template you patch locally (outside the skill folder) and record the backup id.
- Never overwrite a file you did not create. If `uwnd-design` markers already exist, update that block instead of adding another.
- Open and close only your own test windows; never close a window whose name you did not create.
- Do not add a second jQuery or any other library to existing pages.
- `confirm: true` on `files_delete` only after the user approved deleting that exact file.

## Workflow

### 1. Connect

`list_sites` → `select_site` → verify the host.

### 2. Choose the appearance

If the user has not chosen, offer three options in their language:

- **Preset** — Light, Dark, Lavender, Mint or Peach (`assets/themes.json`).
- **Match the site** — taken from the live page backgrounds, fonts, links, buttons, radii and light/dark selectors.
- **Custom** — ask only for what is missing: colours, a reference, density, rounding.

→ `references/THEMES.md`

### 3. Audit

Open representative windows on the live site and run `scripts/inspect-windows.js` in the browser (read-only). Note the loaded system CSS/JS, the window DOM, site CSS variables and light/dark switches. → `references/COMPONENTS.md`

### 4. Build

Generate the stylesheet locally (`python scripts/build_theme.py --theme <name> --output <file>`, optional `--accent` / `--hover`). For a site-matched or custom look, adapt the `--uwd-*` tokens; bind them to site variables only after seeing their real names. Show a preview with the site's own library and harmless demo windows.

### 5. Publish

Backup → CSS file → validated marked patch → exact read-back. → `references/PUBLISHING.md`

### 6. Verify

Desktop and 375px, light and dark site modes, real site windows and demos, field states, keyboard, toasts. → `references/VERIFICATION.md`

### 7. Report

Use the output format below. Then call `skills_tool register_usage`.

## Output format

### Plan

| # | Object | Tool / action | Change | Backup | Risk |
|---|---|---|---|---|---|

### Final report

- Site and theme:
- CSS file (path, version):
- Templates patched (module/template, backup id):
- Preview URL:
- Verified (desktop / 375px, light / dark, windows, forms, toasts):
- Not tested and why:
- Rollback:

## Reference map

| Need | File |
|---|---|
| Window and toast DOM, classes, JS API, what to keep untouched | `references/COMPONENTS.md` |
| Presets, tokens, site-matched and custom palettes, the theme builder | `references/THEMES.md` |
| Backup, CSS file, marked insertion, HTTP 413, rollback | `references/PUBLISHING.md` |
| Test matrix and acceptance criteria | `references/VERIFICATION.md` |
| Base stylesheet / preset palettes | `assets/uwnd-overrides.css`, `assets/themes.json` |
| Theme builder / read-only DOM audit | `scripts/build_theme.py`, `scripts/inspect-windows.js` |
| Example requests | `examples/PROMPTS.md` |
