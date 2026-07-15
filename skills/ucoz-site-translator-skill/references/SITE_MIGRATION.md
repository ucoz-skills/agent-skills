# Copy and migrate between uCoz sites

Use this workflow to copy selected pages, templates/global blocks, menus, categories, or module materials from a source uCoz site to a different target uCoz site, optionally translating during transfer.

## Establish two safe connections

Require two unambiguous contexts:

- **source** — site URL and authenticated uCoz MCP/uAPI access; treat as read-only;
- **target** — site URL and a separately authenticated MCP/uAPI connection; use for writes.

Ask the user to configure target credentials through the client’s protected MCP/secret settings. Required values are `UCOZ_API_TOKEN` and `UCOZ_SITE_URL`; add `UCOZ_FTP_HOST`, `UCOZ_FTP_USER`, and `UCOZ_FTP_PASS` when static files or FTP-hosted template assets must move. Never request live credentials in ordinary chat, include them in reports, or store them in the skill/project.

Verify the site host before every write. Stop if source and target contexts cannot be distinguished reliably.

## Gather migration choices

Ask for:

- copy-only or copy-and-translate mode;
- exact objects/range and target locale;
- optional fields, SEO, categories, comments, tags, dates, authorship, and slugs;
- conflict policy: skip (default), create another object, or update a specifically identified target object;
- publication policy: hidden/pending (default for batches) or published;
- media policy from [IMAGE_LOCALIZATION.md](IMAGE_LOCALIZATION.md).

Do not transfer users, permissions, prices, stock, orders, comments, counters, publication dates, or authorship unless explicitly requested and supported by the current target schema.

## Build the mapping plan

Discover source and target independently and build a table:

| Source | Target decision | Dependencies | Conflict | Result |
|---|---|---|---|---|
| type + ID + URL | create/update/skip | parent/category/media | none/match | target ID + URL |

Never assume numeric IDs match. Match modules by documented module code, templates by target module/template inventory, global blocks by stable block identity, and pages/categories by explicit user-approved mapping. Title similarity is evidence for a conflict prompt, not authorization to overwrite.

Identify internal source URLs in HTML and menu trees. Rewrite only those that have a confirmed target mapping; preserve anchors, query parameters, and external URLs. Report unresolved source links.

## Order operations

1. Verify target modules and uAPI permissions.
2. Create/map categories, page parents, and required global blocks.
3. Process and upload media; record target URLs/file slots.
4. Create materials/pages with translated fields, source format controls, target relationship IDs, and selected publication state.
5. Validate and update templates/global blocks after their referenced assets exist.
6. Create/update menus last, using target URLs and IDs.
7. Re-read every target object and verify mappings, formats, links, media, slugs, and protected fields.

Stop the batch on the first write/verification mismatch. Keep already completed source→target mappings and report the exact stopping point.

## Object-specific rules

### Materials and categories

Read the current OpenAPI schema for both sites. Use source GET for inventory and target POST for creation. Send only target-accepted creation fields, explicit content-format controls, mapped category IDs, and approved media. Omit source ID, counters, ratings, comments, generated URLs, and response-only fields.

Create target categories first when approved. If a category is not migrated, require an explicit existing target category mapping.

### Page Editor pages

Create parent pages before children. Preserve HTML and map internal links/media. Do not reuse source page IDs. Keep the target slug empty or localized according to the approved preview. Verify the resulting target URL.

### Templates and global blocks

Inventory target template IDs with `list_modules`; do not assume source IDs exist on the target. Validate complete target HTML before saving. Create a backup before updating an existing target template/block. For missing global blocks, create through the target MCP when supported, then update insertion references only after the target block ID is known.

### Menus

Create/map referenced pages/categories first. Rebuild the same tree shape with target URLs and IDs. Preserve item order, types, targets, anchors, and nesting. Verify with a target `menu_get`.

## Rollback and idempotency

- Source: never mutate.
- Existing target templates/blocks: record automatic backup IDs.
- Existing target pages/materials/menus: retain original editable values.
- Newly created objects: record IDs and URLs; deletion requires separate explicit approval.
- Re-running: rediscover target state and use the prior mapping. Never create duplicates automatically.

