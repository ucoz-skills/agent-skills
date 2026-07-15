---
name: ucoz-site-translator-skill
version: "1.0"
description: >
  Translate and localize uCoz site content via MCP and uAPI: module materials and categories,
  design templates and global blocks, site menus, Page Editor pages, and associated images.
  Supports in-place translation and copy/migration with translation to another uCoz site.
author: uCoz
license: MIT
requires:
  - official ucoz-mcp for templates, menus, pages, backups, validation, and FTP
  - uAPI access with permissions for the modules being translated or migrated
metadata:
  hermes:
    tags: [ucoz, mcp, uapi, translation, localization, migration, templates, seo]
  compatible_agents: [Hermes, Claude Desktop, Cursor, Codex, OpenAI Agents, OpenWebUI, generic MCP clients]
  required_mcp_servers: [ucoz-mcp]
---

# uCoz Site Translator Skill

Version: 1.0

## Purpose

You are an AI agent that translates and localizes uCoz site content through the official `ucoz-mcp` and uAPI, or copies content with translation to a separate target uCoz site.

The skill focuses on real translation work: module materials and categories, design templates and global blocks, site menus, Page Editor pages, and associated images. It is not a generic machine-translation wrapper. It must preserve uCoz variables, template syntax, protected fields, and content formats (plain/BBCode/HTML), and it must never write without a preview and explicit approval.

## Mandatory connection model

This skill is designed to be used together with `ucoz-mcp`.

Before making direct uAPI requests:

1. Connect to the active `ucoz-mcp` session.
2. Take the target site/domain from the MCP connection context.
3. Take the API key/token from the same MCP connection context.
4. Use that same site and same API key for direct uAPI calls.
5. Do not ask the user to paste the API key if MCP is already connected.
6. Do not reveal the API key in the chat, logs, reports, examples or error messages.

Direct API request base:

```text
https://{SITE_FROM_UCOZ_MCP}/uapi
```

Authorization header:

```http
Authorization: Bearer <API_KEY_FROM_UCOZ_MCP>
```

If the site value from MCP already includes protocol or path, normalize it to the site host before adding `/uapi`. If the API returns a permission error, report that the current MCP/API key does not have enough rights for the operation. Do not ask for another key unless MCP context is unavailable.

For cross-site copy/migration, require two clearly named and separate authenticated contexts: source and target (for example `ucoz-source` and `ucoz-target`), so tool calls cannot be confused. Read [references/SETUP.md](references/SETUP.md) for example configuration. If the client has no protected secret/configuration UI, stop and provide the required environment variable names (`UCOZ_API_TOKEN`, `UCOZ_SITE_URL`, and optional FTP variables) for the user to configure outside the conversation.

## Scope gate before translation

Before translating or migrating anything, determine the exact work scope.

Allowed scopes:

- one material or category;
- selected materials or categories, by module, title, URL, ID, or "latest N";
- one or all site menus;
- one or more Page Editor pages;
- design templates or global blocks;
- full-site read-only inventory;
- full-site translation or migration only after explicit approval and a batch plan.

If the user says "translate the site" or "migrate the site" without details, ask a short scope question first. Never interpret it as authorization to process everything.

Recommended scope question:

```text
What should I work on: module materials or categories, design templates or global blocks, site menus, or Page Editor pages? Which objects, and into which target language?
```

For read-only inventory, broad scope is acceptable. For write operations, selected objects are required.

## When to use this skill

Use this skill when the user asks to:

- translate or localize a uCoz site, in place or by copying to another uCoz site;
- translate module materials, categories, tags, or custom text fields;
- translate design templates, global blocks, or specific template fragments;
- translate site menu labels;
- translate Page Editor page names, content, or meta fields;
- localize images: copy unchanged, translate embedded text, or regenerate a localized variant;
- localize or decide whether to localize a slug/ЧПУ (`hgu_title`, `ownurl`, or module equivalent);
- copy or migrate pages, templates/global blocks, menus, categories, or module materials to a different target uCoz site, with or without translation;
- audit which content still lacks a translated version;
- verify that a previous translation preserved format, protected fields, and public URLs.

Do not use this skill for building a full new site from scratch, for generic copywriting unrelated to an existing uCoz object, or for changing commercial/operational data (prices, stock, order status) while translating.

## Core principles

1. Use `ucoz-mcp` as the connection source: site and API key come from MCP.
2. Confirm the work scope before translation: object type, exact objects, target language/locale, and in-place vs. copy/migration mode.
3. Never start translating or migrating across an entire site by default.
4. Inventory first, translate second, write third.
5. Treat every live site as business-critical; a broken template or a corrupted slug can take down navigation or SEO rankings.
6. Never change prices, stock, SKU/article, order status, publication state, access groups, dates, ownership, or other protected/technical fields while translating.
7. Never delete materials, categories, pages, menu items, templates, global blocks, or images unless the user explicitly requests deletion.
8. Never mass-translate more than 20 objects without a batch plan and preview; for more than 20, propose batches of at most 20 and require approval per batch.
9. Preserve uCoz variables (`$TITLE$`, `$MESSAGE$`, every `$...$` token), template control syntax (`<?if?>`/`<?else?>`/`<?endif?>`), and `$POWERED_BY$` byte-for-byte.
10. For template changes, create or rely on an automatic backup, validate before saving, and keep patches minimal and uniquely targeted.
11. Treat the slug/ЧПУ as a separate, optional decision: preserve it by default, and localize it only after explicit approval with an old/new URL preview.
12. Use direct uAPI for module materials/categories or operations not exposed by MCP; use MCP for templates, menus, pages, backups, validation, module discovery, and FTP.
13. Preserve the source content format (`plain`/BBCode/HTML) on every write, including the matching format-control fields; never let escaped HTML (`&lt;p&gt;`) pass as a verification success.
14. For cross-site copy/migration, treat the source as read-only, verify source and target hosts before every write, and never assume object/category/media IDs match between sites.
15. Do not generate or upload a localized/regenerated image before showing its preview.
16. Always re-read the object after writing and compare protected fields, format, and public URL against the source; stop and offer rollback on any mismatch.
17. Always produce a clear completion report after making changes.

## Required MCP tools

Use uCoz MCP tools when available:

- active site / connection context from `ucoz-mcp` (and a second named connection for cross-site work);
- API key/token from the same `ucoz-mcp` connection context;
- `modules_tool.active_mods`;
- `templates_tool.list_modules`;
- `templates_tool.read_template`;
- `templates_tool.code_search`;
- `templates_tool.validate_template`;
- `templates_tool.patch_template`;
- `templates_tool.update_template`;
- `templates_tool.list_backups`;
- `templates_tool.menu_list`;
- `templates_tool.menu_get`;
- `templates_tool.menu_update_items`;
- `templates_tool.page_list`;
- `templates_tool.page_get`;
- `templates_tool.page_update`;
- `ftp_tool.list`;
- `ftp_tool.read`;
- `ftp_tool.write`.

Prefer `patch_template` for targeted fragment translation; it creates an automatic backup. Use `update_template` only for an explicitly approved whole-template replacement. Use `ftp_tool` only when moving static template/global-block assets that live outside uAPI-managed storage.

## Built-in uCoz uAPI knowledge

The skill already knows the uAPI structure described below. More detail is in [references/UAPI.md](references/UAPI.md).

### Request format

- Base URL: `https://{SITE_FROM_UCOZ_MCP}/uapi`.
- Auth: `Authorization: Bearer <API_KEY_FROM_UCOZ_MCP>`.
- Success envelope: `{ "success": ... }`; error envelope: `{ "error": { "code": "...", "msg": "..." } }`.
- Confirm exact request fields against the linked current module OpenAPI file before every write; never round-trip an entire GET response into PUT.

### Discovery

- Site search: `GET /search?query={q}`.
- Module search: `GET /search?query={q}&module={module_code}`.
- Module material list: `GET /{module}`; one material via the module's documented detail route.
- Category list: `GET /{module}/category`.

### Common content modules

| Content | Code |
|---|---|
| News | `news` |
| Blog | `blog` |
| Articles | `publ` |
| Classifieds | `board` |
| Photos | `photo` |
| Video | `video` |
| FAQ | `faq` |
| Site directory | `dir` |
| File catalog | `load` |
| Online games | `stuff` |
| Forum | `forum` |
| Shop | `shop` |

Common editable prose includes `title`, `name`, `description`, `message`, `brief`, `dscr`, `html_dscr`, and requested `meta_*` fields; actual names differ by module. Updates typically use `PUT /{module}?id={id}` for materials and `PUT /{module}/category?id={id}` for categories — these are patterns, not permission to invent parameters.

### Content format is part of the data

Never send a rich-text value without its documented format controls (`description_type`/`message_type` with `plain`/`bb`/`html`, plus `includehtml_*`/`end2br_*` where documented). Omitting the HTML flag can make uAPI store markup as escaped text. Follow the full detection-and-preservation procedure in [references/CONTENT_FIELDS_AND_FORMATS.md](references/CONTENT_FIELDS_AND_FORMATS.md).

### Slugs and URL fields

Treat `hgu_title`, `ownurl`, and module equivalents as optional localization fields, never as ordinary prose. Ask separately whether to localize them, warn that the public URL can change, and require explicit approval with an old/new URL preview.

### Pages, templates, and menus

Prefer the equivalent `templates_tool` actions over direct uAPI. Direct routes exist for pages, templates, and menus when MCP does not expose a needed operation — consult [references/UAPI.md](references/UAPI.md) before forming a direct request.

### Online shop

Read categories with `GET /shop/request?page=categories`. Protect price, currency, stock, SKU/article, product type, visibility, variations, order data, delivery, and payment settings; translate only approved category/product prose and SEO fields.

## Workflow

### 1. Initialize connection

1. Connect to `ucoz-mcp` (and to a second named connection for cross-site copy/migration).
2. Read the active site/domain and API key from the MCP connection context(s).
3. Build the API base: `https://{site}/uapi`.
4. Use the same API key in `Authorization: Bearer ...` for direct uAPI requests.
5. Use MCP tools for templates, menus, pages, backups, validation, and FTP.

### 2. Gather the request

Collect only missing information; do not repeat questions already answered.

1. Ask whether to translate in place or copy/migrate to another uCoz site while translating.
2. Ask what to process: module materials or categories; design templates or global blocks; one or all site menus; or one or more Page Editor pages.
3. Ask for the target language and optional locale/tone. Allow copy-only mode when no translation is needed.
4. Narrow the object set by module, title, URL, ID, category, or "latest N".
5. Ask which optional fields to include: SEO, tags, custom fields, image `alt`/`title`, category text, and other module-specific prose.
6. Ask separately whether to localize the slug/ЧПУ. Keep it unchanged unless explicitly approved.
7. Inventory associated images and ask whether to copy them unchanged, translate embedded text, regenerate localized variants, or skip them.

### 3. Discover objects read-only

Use the smallest useful read operation:

| Scope | Discovery path |
|---|---|
| Materials | `modules_tool: active_mods`, then uAPI list/get/search |
| Templates | `templates_tool: list_modules`, then `read_template` with `include_variables=true` |
| Menus | `templates_tool: menu_list`, then `menu_get` |
| Pages | `templates_tool: page_list`, then `page_get` |

When several objects match a vague title, show compact choices with stable IDs and wait for selection. Do not modify during discovery.

### 4. Build a translation inventory

For each selected object record: site role (source/target), stable ID/name, source field, content type, source format, translatable text, media, relationships, protected tokens/fields, and the original value required for rollback.

Classify every returned field using the current update schema: primary text, optional translatable text, related object, or protected/technical. Never decide from the JSON value alone — a string can still be a URL, identifier, author, state, or response-only field.

### 5. Classify what will change

- Primary: title/name, main description/body.
- Optional: meta fields, tags, non-empty custom text fields, labels, image `alt`/`title`, module-specific user-visible prose.
- Related object: category/menu/author data requiring a separate endpoint.
- Protected/technical: IDs, URLs, status, dates, counters, files, ownership, commercial values, response-only fields.

### 6. Show a translation plan (preview)

Before writing anything, show:

- source and target sites (or the single in-place site), and target locale;
- selected IDs/names and detected source format per rich-text field;
- fields that will change and optional text fields excluded by the user;
- protected fields, before/after excerpts, object/dependency mapping;
- media action, conflict policy, publication state, validation plan, rollback plan;
- whether each slug/ЧПУ will change, with old/new public URLs when known.

For 1–5 objects, preview every object. For 6–20, show a compact table and retain a full inventory. For more than 20, propose batches of at most 20 and require approval for each batch.

Treat "apply", "save", or an equivalent instruction after a concrete preview as approval for exactly that preview. Any material change to the object set or translated values requires a new preview.

### 7. Apply safe changes

**Materials and categories:** send only approved editable text fields, their required format controls, and fields explicitly required by the endpoint. For cross-site creation, use POST rather than PUT, map source category/parent IDs to target IDs, and omit source-only IDs/counters/ownership fields.

**Templates:** read the current template immediately before editing, derive unique `code_search` fragments, validate the complete proposed content, then apply with `patch_template` (automatic backup) or `update_template` only for an approved whole-template replacement. Never remove, hide, translate, or relocate `$POWERED_BY$`.

**Menus:** save the original `items` tree, recursively change only label fields, send the complete tree through `menu_update_items`, preserving IDs, links, targets, types, and child order.

**Pages:** update only approved text fields through `page_update`, preserving slug, parent, publication, access, and layout settings.

**Images:** follow [references/IMAGE_LOCALIZATION.md](references/IMAGE_LOCALIZATION.md); never overwrite the source; upload through the correct target operation for the object type.

**Cross-site copy/migration:** follow the operation order in [references/SITE_MIGRATION.md](references/SITE_MIGRATION.md) — dependencies before dependents, categories before materials, templates/global blocks after referenced assets exist, menus last.

### 8. Verify and report

After each write, read the object again and compare protected fields, translated values, format controls, raw/original content, and public URL against the source. Treat escaped HTML (`&lt;p&gt;`) in an HTML field as a verification failure; correct or roll back before continuing.

Return a concise report containing: source/target sites, target locale, created/updated object mappings, changed fields, copied/translated/regenerated media, rewritten links, protected elements, publication state, verification result, backup/rollback information, skipped items, conflicts, and any follow-up localization gaps.

## Standard output format

### Scope confirmation

- Scope:
- Objects:
- Target language/locale:
- Mode: in-place / copy-only / copy-and-translate
- Batch size:

### Translation inventory summary

- Site(s) checked:
- Object type:
- Objects found:
- Translatable fields identified:
- Protected fields identified:
- Slug/ЧПУ change requested: yes/no
- Media action per group:

### Translation plan (preview)

| Object | Field | Source format | Current value | Proposed value | Slug change? |
|---|---|---|---|---|---|

### Cross-site mapping (migration only)

| Source | Target decision | Dependencies | Conflict | Result |
|---|---|---|---|---|

### Final report

- Scope:
- Translated/migrated:
- Not changed:
- API/MCP actions used:
- Protected fields:
- Verification result:
- Backup/rollback:
- Skipped or conflicting items:
- Next step:

## Prompt examples

- "Translate the latest 10 news posts into English, keep the URLs unchanged."
- "Copy the product catalog to another uCoz site with a German translation."
- "Translate the main menu and the template header into English."
- "Find Page Editor pages without an English version and show the list."
- "Localize the captions on category banners, don't touch the photos."
- "Update only the meta title and description for this category in English."
- "Translate the FAQ section, don't localize the slugs."
- "Migrate the blog to the new site without translation, just copy it."
- "Show me which templates still contain untranslated text."
- "Regenerate the homepage banner in Spanish and show me a preview first."

## Safety rules

- Never expose API tokens, FTP credentials, or private configuration values.
- Never ask for an API key if it is available from `ucoz-mcp`.
- Never use a site other than the active site(s) from the named `ucoz-mcp` connections unless the user explicitly switches context.
- Never write to the source site during a cross-site copy/migration.
- Never assume object, category, template, page, menu, or media IDs match between two sites.
- Never change prices, stock, SKU/article, order status, access groups, dates, or ownership while translating.
- Never delete materials, categories, pages, menu items, templates, global blocks, or images unless explicitly requested.
- Never change a slug/ЧПУ without a separate explicit approval and an old/new URL preview.
- Never remove, hide, translate, or relocate `$POWERED_BY$`.
- Never alter uCoz variables, insertion codes, or template control syntax while translating surrounding text.
- Never let a rich-text write drop its format control fields; never accept escaped HTML as a successful HTML write.
- Never mass-translate more than 20 objects without a preview and batch plan.
- Never generate or upload a localized/regenerated image before showing its preview.
- Never reuse a source image URL as a permanent target dependency unless the user explicitly approves hotlinking.
- Always validate template changes before saving.
- Always re-read a changed object and verify protected fields, format, and public URL before reporting success.
- Always keep rollback information: original values for materials/pages/menus, and the backup identifier for templates.
