---
name: ucoz-landing-skill
version: 1.4
description: "Create and edit uCoz homepage landing pages via MCP: custom templates, hero sections, lead forms, navigation menus, SEO, and responsive layout. Includes a visual design system (style selection, layout/grid, section recipes, typography/spacing, color tokens, component states, icons, modern CSS/JS, motion, imagery, social proof, copy/voice, accessibility). Uses ucoz-mcp tools for templates, FTP uploads, and site modules."
---

# uCoz Landing Skill

This skill activates for uCoz landing page creation and editing tasks.

## Usage accounting

After successfully completing the skill's main work, record the usage:

```text
skills_tool(action="register_usage", skill_id="ucoz-landing-skill")
```

If the usage API is unavailable or returns an error, show a brief warning and continue the skill's main workflow. Do not ask the user for a UUID, token, or site URL — the site and token are taken from the active `ucoz-mcp` connection.

## Scope

- "build a landing page" / "update the home page as a landing"
- "create a promo page on the home page"
- "add a lead form / menu / SEO / responsive layout on the home page"

Landing page = personal template of the home page (`page_id=1`, `page_owntmpl=1`).

## MCP tools

| Tool | Purpose |
|---|---|
| `templates_tool` | Pages (`page_*`), menus (`menu_*`), mail forms (`mail_*`), `patch_template` / `update_template` |
| `ftp_tool` | FTP files; `ftp_password_get` / `ftp_password_set` / `ftp_password_change` / `ftp_password_reset` |
| `modules_tool` | Module catalog, mail forms module install (`module_install`); quarantine unlock for indexing (`quarantine_unlock`) |

`modules_tool` and `ftp_password_*` require an API key with **Control Panel settings access**.

## Instructions

Before starting, read the full playbook:

```
references/PLAYBOOK.md
```

The playbook includes:
- core principles (tools, CSS/JS, responsive layout, burger menu, forms, SEO, Schema.org)
- step-by-step algorithm (steps 0–11): requirements → publish → final step: quarantine/indexing
- `patch_template` rule for editing existing landing HTML (module_id=2, template_id=1)
- mail forms module activation and FTP credential setup via MCP
- final step: quarantine and indexing (with user confirmation)
- checklist, reference action order, prohibitions, report format

**Before Step 4 of the playbook (writing any CSS/HTML), also read the visual system:**

```
references/VISUAL.md
```

`VISUAL.md` includes:
- style selection and routing (12 named directions, mapped by niche — never one default palette for every brief)
- layout/grid contract (fluid container width picked per brief, not a fixed number) and section layout recipes (hero/benefits/proof/FAQ/CTA — several variants each, not one default shape)
- typography and spacing floor (type scale, spacing scale, anti-patterns) that applies regardless of chosen style
- color token contract (`:root` variables, WCAG contrast minimums, OKLCH/`color-mix()` derivation), component states (button/input/card default/hover/focus/active/disabled), and page-level background rhythm between sections
- icon rule (Heroicons/Lucide/Tabler inline SVG — no hand-drawn paths, no emoji, no icon webfonts) and a logo-monogram fallback for clients with no existing brand asset
- modern CSS toolbox tiered as mandatory / situational / skip, with uCoz-specific platform gotchas (`@layer` vs. unlayered system CSS, nesting depth ≤ 3)
- motion rules (one orchestrated entrance moment, native scroll-driven animations with `IntersectionObserver` fallback, mandatory `prefers-reduced-motion`)
- JS baseline for `script.js` tiered the same way, including the rule to never intercept `$MFORM_N$` submission with `fetch`/`FormData`
- imagery direction (avoiding stock-photo clichés when no real photos exist, consistent crop/treatment when they do), social proof patterns (stats, logo strips, testimonials), and copy/voice guidance including a properly styled footer copyright line
- accessibility and progressive-enhancement checklist that folds into playbook Step 9
