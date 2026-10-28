# uCoz Popup Design Skill

Version: 1.0

An agent skill that restyles the popups uCoz draws on a live site — system windows (`_uWnd`), confirmations, forms inside windows and `uToast` notifications — through the official uCoz MCP. It ships one scoped stylesheet, five presets and a theme builder with contrast checks, and publishes the CSS as one versioned file linked from a marked template insertion.

## What it does

- Offers three ways to pick the look: a preset (Light, Dark, Lavender, Mint, Peach), a palette matched to the live site, or a custom palette.
- Audits the real popup DOM and computed styles read-only (`scripts/inspect-windows.js`); field values and personal data are never collected.
- Builds the theme locally (`scripts/build_theme.py`) and refuses palettes below 4.5:1 contrast for key text pairs.
- Styles text, headings, links, fields, selects, checkboxes and radios, buttons, close icons, error/success states and the four toast types, while native geometry, drag/resize and keyboard behaviour stay intact.
- Publishes one CSS file and one `<link>` between `uwnd-design:start/end` markers, with a template backup and exact read-back; removal is one patch.
- Verifies desktop and 375px, light/dark site modes, long messages and field states, and reports what was not tested.

## Rules it relies on

| Rule | Why |
|---|---|
| Popups are appended to `<body>` by platform JS, not rendered from templates | The only lever is CSS loaded after the system styles |
| Ordinary-window rules are scoped to `[id^="_uwndTop"]` without `.x-lb`, `.cp-modal`, `.cp-alert`, `.uwnd-toast` | Lightbox, Control Panel dialogs and toasts stay untouched |
| `.xw-shadow` is never restyled globally | Menus use it too |
| `patch_template` only, never `update_template` | A full rewrite can drop `$POWERED_BY$`, conditions or form fields |
| `uToast.show(title, message)` vs `_uWnd.alert(message, title)` | Argument order differs; demos must not mix them up |
| File and marker names stay `uwnd-design` | Sites styled by earlier versions are updated in place |

## MCP tools

| Step | Tool |
|---|---|
| Site | `list_sites`, `select_site` |
| Templates, backups, validation | `templates_read` (`list_modules`, `read_template`, `validate_template`, `get_backup_guide`, `list_backups`) |
| Template edit | `templates_write` (`patch_template`, `create_backup`) |
| CSS file | `files_read` (`quota`, `list`, `content_get`), `files_write` (`mkdir`, `content_put`), `files_delete` only with user confirmation |
| Usage accounting | `skills_tool` (`register_usage`) |

## Files

| Path | Purpose |
|---|---|
| `SKILL.md` | Entry point: purpose, tool map, non-negotiables, workflow, report format |
| `references/COMPONENTS.md` | Window and toast DOM, classes, JS API |
| `references/THEMES.md` | Presets, tokens, site-matched and custom palettes, the builder |
| `references/PUBLISHING.md` | Backup, CSS file, marked insertion, HTTP 413, rollback |
| `references/VERIFICATION.md` | Test matrix and acceptance criteria |
| `assets/uwnd-overrides.css`, `assets/themes.json` | Base stylesheet and preset palettes |
| `scripts/build_theme.py`, `scripts/inspect-windows.js` | Theme builder and read-only audit |
| `examples/PROMPTS.md` | Example requests |

## Limits

CSS does not add focus traps, ARIA roles or tabindex, so the skill never claims full accessibility. For a full site redesign use `ucoz-design-editor-skill`.
