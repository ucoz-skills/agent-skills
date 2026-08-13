---
name: ucoz-design-editor-skill
version: "1.0"
description: >
  Redesign and restyle uCoz sites via MCP: colors, typography, CSS, header/footer,
  global blocks, menus, forms, and module templates (blog, shop, forum, photos, users,
  search, subscriptions). Use when the user asks to redesign, restyle, change the design
  or CSS, or make a uCoz site look better.
author: uCoz
license: MIT
requires:
  - official ucoz-mcp for templates, FTP, modules, global blocks, menus, and forms
metadata:
  hermes:
    tags: [ucoz, mcp, design, design-system, templates, css, cms]
  compatible_agents: [Hermes, Claude Desktop, Cursor, Codex, OpenAI Agents, OpenWebUI, generic MCP clients]
  required_mcp_servers: [ucoz-mcp]
---

# uCoz Design Editor Skill

This skill teaches how to redesign and restyle **uCoz sites** through `ucoz-mcp`, with platform-safe template edits.
It does not replace the MCP tools — it supplies domain knowledge the MCP does not: design-system
architecture, system markup, CSS placement, safety rules, and which reference to open for a task.

## Rule 0 — Usage accounting

After the skill's main work completes successfully, record usage:

```text
skills_tool(action="register_usage", skill_id="ucoz-design-editor-skill")
```

If the usage API is unavailable or returns an error, show a brief warning and continue the skill's main workflow. Do not ask the user for a UUID, token, or site URL — the site and token come from the active `ucoz-mcp` connection.

## Scope

- Site-wide design / redesign (chrome, tokens, shared CSS)
- Global blocks, menus, mail forms, site pages
- Content modules (blog, news, articles, photo albums, site directory, …)
- Shop, users, search, subscriptions modules
- FTP assets and module install/enable

## MCP tools

| Tool | Purpose |
|---|---|
| `templates_tool` | Templates, backups, global blocks, menus, mail forms, pages |
| `ftp_tool` | File upload/download; FTP password management |
| `modules_tool` | Install/uninstall modules, quarantine; needs Control Panel API access |
| `skills_tool` | Usage accounting (`register_usage`) after successful work |

## Safety checklist (before `patch_template` / `update_template`)

- [ ] Production header only in `1/AHEADER`; production footer (single visible `$POWERED_BY$`) only in `1/BFOOTER`
- [ ] Public full-page shells render `$GLOBAL_AHEADER$` and `$GLOBAL_BFOOTER$` — never copy header/footer into module templates
- [ ] Navigation from menu variables (e.g. `$NMENU_1$`), not hardcoded menu HTML
- [ ] Multi-section redesign CSS: one canonical external stylesheet via FTP (or module CSS / `3/3` per [ARCHITECTURE.md](references/ARCHITECTURE.md)) — no scattered `<style>` blocks
- [ ] `read_template` first; variables only from that output or `get_variables` — never invent names
- [ ] Partial edit → `patch_template` with byte-exact `code_search`; full rewrite → `update_template` only after user confirmation
- [ ] Before `update_template`, `read_template` again in the same turn (do not reconstruct from memory — especially third-party scripts)
- [ ] Never hide `$POWERED_BY$` (including CSS tricks)
- [ ] After save: re-`read_template` or fetch live page — `patch_template` can report success without persisting
- [ ] Risky bulk change: check backup headroom (`list_backups`)

**Always confirm with the user first:** `update_template` on an existing template, `restore_backup`, `delete_backup`, `page_delete` / `menu_delete` / `mail_delete_form`, `module_uninstall`, `quarantine_unlock`, `ftp_password_reset` / `ftp_password_change`.

Details: [EDITING.md](references/EDITING.md), [ARCHITECTURE.md](references/ARCHITECTURE.md).

## Anti-patterns

| Wrong | Correct |
|---|---|
| Guess variable names by analogy | Confirm via `read_template` / `get_variables` |
| `update_template` for a small change | `patch_template` with exact `code_search` |
| Reformat whitespace in `code_search` | Copy byte-for-byte from `read_template` |
| Inline `<style>` in page/global-block templates | CSS template `3/3`, module CSS, or one FTP asset |
| Hand-coded menu / mail form HTML | `menu_*` / `mail_*` + `$NMENU_*$` / `$MFORM_*$` |
| Hide `$POWERED_BY$` | Keep visible in `BFOOTER` |
| Grid on wrapper around `$BODY$` instead of `#allEntries` | See [SYSTEM-MARKUP.md](references/SYSTEM-MARKUP.md) |
| Assume template markup is what the live page renders | Fetch the live page first (`#uf-register`, widgets, …) |

## Quick decision helper

| Task | Open |
|---|---|
| Find module/template IDs | [ARCHITECTURE.md](references/ARCHITECTURE.md) → `list_modules` |
| Header / footer / shared chrome | [GBLOCKS.md](references/GBLOCKS.md) |
| Custom global block / `$GLOBAL_*$` | [GBLOCKS.md](references/GBLOCKS.md) |
| Where to put CSS / verify live CSS | [ARCHITECTURE.md](references/ARCHITECTURE.md) |
| Patch vs rewrite / backups / encoding | [EDITING.md](references/EDITING.md) |
| `$VAR$` / `<?if?>` expressions | [LANGUAGE.md](references/LANGUAGE.md) |
| Menus, mail forms, pages, FTP, modules | [TOOLS.md](references/TOOLS.md) |
| `eMessage` / `manTable` / `#allEntries` / `.goods-list` | [SYSTEM-MARKUP.md](references/SYSTEM-MARKUP.md) |
| System `_uWnd` / `xw-*` toasts, alerts, mail success modals | [UWND.md](references/UWND.md) → [EXAMPLES.md#uwnd-glass](EXAMPLES.md#uwnd-glass) |
| Blog / news / articles / photo / other content modules | [BLOG.md](references/BLOG.md) → [SYSTEM-MARKUP.md](references/SYSTEM-MARKUP.md) |
| Photo albums specifically | [PHOTO.md](references/PHOTO.md) |
| Forum (rules) | [FORUM.md](references/FORUM.md) |
| Forum glass CSS pattern | [EXAMPLES.md#forum-glass-apple](EXAMPLES.md#forum-glass-apple) |
| Shop | [SHOP.md](references/SHOP.md) |
| Users / cabinet | [USERS.md](references/USERS.md) |
| Search overlay / results | [SEARCH.md](references/SEARCH.md) |
| Subscriptions & plans | [SUBSCRIPTIONS.md](references/SUBSCRIPTIONS.md) |
| Auth modal / other UI patterns | [EXAMPLES.md](EXAMPLES.md) |

**Rules vs examples:** `references/*.md` = hard platform rules. `EXAMPLES.md` = optional copy-paste UI patterns. Forum rules → `references/FORUM.md`; forum glass sample → `EXAMPLES.md#forum-glass-apple`. System `_uWnd` popups/modals → `references/UWND.md` (not FORUM.md).

## Core workflow

1. **Locate** — `list_modules` if needed → `read_template`.
2. **Diagnose** — `$GLOBAL_XXX$` → edit that global block; list card → Entry view template; else page template. Fetch live DOM when layout/CSS is involved.
3. **Verify variables** — [LANGUAGE.md](references/LANGUAGE.md).
4. **Edit** — `patch_template` (byte-exact) or confirmed `update_template`; menus/mail/pages via their APIs ([TOOLS.md](references/TOOLS.md)).
5. **Validate** — `validate_template` for non-trivial changes.
6. **Confirm** — re-read template or live page after save.
7. **Report** — what changed (module/template/block) and any manual CP step.

## Site-wide redesign order

Prefer cheapest/safest first:

1. Design tokens in the CSS template / canonical stylesheet
2. Component CSS (hardcoded values → tokens)
3. Structural HTML last — and only where needed; keep JS-wired DOM shapes intact ([SYSTEM-MARKUP.md](references/SYSTEM-MARKUP.md))

## References

| File | Contents |
|---|---|
| [EDITING.md](references/EDITING.md) | Patch/update, validation, backups, troubleshooting |
| [LANGUAGE.md](references/LANGUAGE.md) | Variables and expressions |
| [ARCHITECTURE.md](references/ARCHITECTURE.md) | Template structure, CSS strategy |
| [SYSTEM-MARKUP.md](references/SYSTEM-MARKUP.md) | System forms, `#allEntries`, shop grids |
| [UWND.md](references/UWND.md) | `_uWnd` / `xw-*` toasts, alerts, mail success modals |
| [TOOLS.md](references/TOOLS.md) | FTP, modules, mail, menus, pages |
| [GBLOCKS.md](references/GBLOCKS.md) | Global blocks |
| [BLOG.md](references/BLOG.md) | Content-module playbook (blog + peers) |
| [PHOTO.md](references/PHOTO.md) | Photo albums (`9`) |
| [FORUM.md](references/FORUM.md) | Forum (`8`) |
| [SHOP.md](references/SHOP.md) | Online store (`20`) |
| [USERS.md](references/USERS.md) | Users module (`4`) |
| [SEARCH.md](references/SEARCH.md) | Search (`19`) |
| [SUBSCRIPTIONS.md](references/SUBSCRIPTIONS.md) | Subscriptions (`25`) |
| [EXAMPLES.md](EXAMPLES.md) | Optional implementation patterns (auth modal, cards, shop UI, …) |

## Setup

Requires MCP env: `UCOZ_API_TOKEN`, `UCOZ_SITE_URL`, `UCOZ_FTP_HOST`, `UCOZ_FTP_USER`, `UCOZ_FTP_PASS`.
`modules_tool` and most admin actions need Control Panel settings access on the API key.
This skill assumes `ucoz-mcp` is already connected.
