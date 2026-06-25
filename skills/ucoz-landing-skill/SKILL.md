---
name: ucoz-landing-skill
version: 1.3
description: >
  Playbook for creating and editing uCoz landing pages via MCP tools
  (`templates_tool`, `ftp_tool`, `modules_tool`).
  Use for tasks such as: "build a landing page", "update the homepage as a landing page",
  "create a promo page on the homepage", "add a lead form / menu / SEO to the homepage".
  Homepage: `page_list`, `page_get`; first publish — `page_update` with full `page_tmpl`;
  HTML edits after generation — `patch_template` (module_id=2, template_id=1), not `update_template`.
  Activate the mail forms module via `modules_tool` when needed; do not ask the user to enable it manually.
  If FTP credentials are missing — obtain/create via `ftp_tool` (`ftp_password_*`).
  Final step: confirm indexing preference and, if agreed, remove quarantine (`quarantine_unlock`).
  Do not ask the user to enable a custom homepage template in the control panel.
metadata:
  hermes:
    tags: [ucoz, mcp, landing-page, cms, website-builder]
---

# uCoz Landing Skill

This skill applies to creating and editing landing pages on uCoz.

## Scope

- "build a landing page" / "update the homepage as a landing page"
- "create a promo page on the homepage"
- "add a lead form / menu / SEO / responsive layout to the homepage"

Landing page = custom template for the homepage (`page_id=1`, `page_owntmpl=1`).

## MCP tools

| Tool | Purpose |
|---|---|
| `templates_tool` | Pages (`page_*`), menus (`menu_*`), mail forms (`mail_*`), `patch_template` / `update_template` |
| `ftp_tool` | FTP files; `ftp_password_get` / `ftp_password_set` / `ftp_password_change` / `ftp_password_reset` |
| `modules_tool` | Module catalog, mail forms install (`module_install`); quarantine unlock for indexing (`quarantine_unlock`) |

`modules_tool` and `ftp_password_*` require an API key with "API access to control panel settings" permission.

## Instructions

Before starting, read the full playbook:

```
references/PLAYBOOK.md
```

The playbook covers:
- core principles (tools, CSS/JS, responsive layout, burger menu, forms, SEO, Schema.org)
- step-by-step workflow (steps 0–11): requirements → publish → final step: quarantine/indexing
- `patch_template` rule for editing existing landing HTML (module_id=2, template_id=1)
- mail forms module activation and FTP credentials via MCP
- final step: quarantine and indexing (with user confirmation)
- checklist, reference action order, prohibitions, report format
