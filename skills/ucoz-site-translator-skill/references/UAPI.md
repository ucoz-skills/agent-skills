# uCoz field reference for localization

Use this reference as field documentation for materials and categories, pages, templates, menus, and the shop. Call the MCP tools. Do not request the OpenAPI URLs or `https://{host}/uapi` yourself. Confirm field names against the linked OpenAPI file before every write.

## Connection

Calls go through MCP tools on the site selected with `select_site`. The MCP server does not give the agent a uAPI key.

- Materials and categories: `content_read` / `content_write` / `content_delete` (`content_delete` needs `confirm: true`)
- Forum: `forum_read` / `forum_write` / `forum_delete` (not `content_*`)
- Pages, templates, menus, and forms: `templates_read` / `templates_write` / `templates_delete` (deletes and `restore_backup` need `confirm: true`)
- Shop: `shop_read` / `shop_write` / `shop_delete` (deletes and `img_edit` need `confirm: true`)
- Files: `files_read` / `files_write` / `files_delete` (`files_delete` needs `confirm: true`).
- Modules: `modules_read` / `modules_write` / `modules_uninstall` (there is no `modules_delete`)

A tool error looks like `{ "error": { "code": "...", "msg": "..." } }`.

Do not emit the host or a key.

## Discovery and search

- Active modules: `modules_read` `active_mods`.
- Module material list: `content_read(action="list", module)` (field hint: `/{module}`).
- One material: `content_read(action="get", module, id)` (field hint: `/{module}?id={id}`). Confirm the id field against the module OpenAPI contract.
- Category list: `content_read(action="category_list", module)` (field hint: `/{module}/category`).
- Forum: `forum_read` / `forum_write` / `forum_delete`, not `content_*`.

Use the pagination parameter names declared by the specific OpenAPI schema. Do not assume `per_page` for every module.

## Common content modules

| Content | Code | OpenAPI |
|---|---|---|
| News | `news` | `https://api.ucoz.net/news-openapi.yaml` |
| Blog | `blog` | `https://api.ucoz.net/blog-openapi.yaml` |
| Articles | `publ` | `https://api.ucoz.net/publ-openapi.yaml` |
| Classifieds | `board` | `https://api.ucoz.net/board-openapi.yaml` |
| Photos | `photo` | `https://api.ucoz.net/photo-openapi.yaml` |
| Video | `video` | `https://api.ucoz.net/video-openapi.yaml` |
| FAQ | `faq` | `https://api.ucoz.net/faq-openapi.yaml` |
| Site directory | `dir` | `https://api.ucoz.net/dir-openapi.yaml` |
| File catalog | `load` | `https://api.ucoz.net/load-openapi.yaml` |
| Online games | `stuff` | `https://api.ucoz.net/stuff-openapi.yaml` |
| Forum | `forum` | `https://api.ucoz.net/forum-openapi.yaml` |
| Shop | `shop` | `https://api.ucoz.net/shop-openapi.yaml` |

The OpenAPI links are field documentation. Call the MCP tools and do not request those URLs. The forum row uses `forum_read` / `forum_write` / `forum_delete`, not `content_*`.

Common editable prose includes `title`, `name`, `description`, `message`, `brief`, `dscr`, `html_dscr`, and requested `meta_*` fields. Actual names differ by module. Preserve category IDs, status flags, dates, files, ownership, URLs, and operational fields unless the user explicitly scopes them.

Before presenting scope, inspect all fields returned by `content_read` (or `forum_read` / `shop_read` for those areas) and the update field list. Offer every non-empty editable user-facing text property, including module-specific fields, SEO, tags, custom text fields, and editable image labels. Do not offer response-only strings, URLs, authors, states, or nested related objects as if they were fields of the material.

Common updates:

- Material: call `content_write(action="update", module, id, …)` (field hint: `PUT /{module}?id={id}`); creation — `content_write(action="add", module, …)`
- Category: call `content_write(action="category_update", module, id, …)` (field hint: `PUT /{module}/category?id={id}`); creation — `category_add`
- Deletion: `content_delete` with `confirm: true`, only after the user approves that deletion

These are patterns, not permission to invent parameters. Inspect the module OpenAPI schema and send only accepted fields through the MCP tool.

## Content format is part of the data

Never send a rich-text value without its documented format controls. Preserve the source format rather than allowing the stored default to reinterpret it.

- Common content modules use `description_type` / `message_type` with `plain`, `bb`, or `html`.
- The same modules may expose `includehtml_desc`, `includehtml_msg`, `end2br_desc`, and `end2br_msg`.
- Forum messages use module-specific controls such as `includehtml` and `end2br` on `forum_write`.
- Other modules differ; read their current update schema instead of copying News parameters.

When HTML is detected, send the corresponding `*_type=html` and documented HTML-enable flag. Preserve the line-break behavior. Omitting `message_type=html` can store markup as escaped text (`&lt;p&gt;...`). See [CONTENT_FIELDS_AND_FORMATS.md](CONTENT_FIELDS_AND_FORMATS.md) for the complete procedure.

## Slugs and other URL fields

Treat `hgu_title`, `ownurl`, and module equivalents as optional localization fields, not ordinary prose. After discovery, ask whether to update them and warn that the public URL can change. Require explicit approval and show old/new URLs. Never infer approval from a request to translate the title or body.

## Pages, templates, and menus

Call `templates_read` / `templates_write`. Deletes and `restore_backup` need `confirm: true` (`templates_delete` for deletes).

- Pages: `templates_read` `page_list` / `page_get`, `templates_write` `page_update` (field hints: `/pages`, `/pages/{id}`, `page_id`)
- Templates: `templates_read` `list_modules` / `read_template`, `templates_write` `patch_template` / `update_template` (field hints: `/templates`, `/templates/{module_id}/{template_id}`)
- Menus: `templates_read` `menu_list` / `menu_get`, `templates_write` `menu_update` / `menu_update_items` (field hints: `/menus`, `/menus/{id}`, `/menus/{id}/items`)

Schemas (field documentation; do not request these URLs): `https://api.ucoz.net/pages-openapi.yaml`, `https://api.ucoz.net/templates-openapi.yaml`, `https://api.ucoz.net/menus-openapi.yaml`.

## Online shop

Call `shop_read` `category_list` / `get` / `list` and `shop_write` `category_update` / `update`. Images: `files_write` `upload_direct`, then `shop_write` `img_add` with `image_upload_ids` (a category image is `shop_write` `category_image` with one `image_upload_id`). Shop deletes and `img_edit` need `confirm: true`. Category field hint: `/shop/request?page=categories`. The shop OpenAPI is field documentation; call the MCP tools and do not request that URL yourself.

Protect price, currency, stock, SKU/article, product type, visibility, variations, order data, delivery, and payment settings. Translate only approved category/product prose and SEO fields.

## Canonical documentation

The links below are field documentation. Call MCP tools and do not request these URLs yourself.

- Compact index: `https://api.ucoz.net/llms.txt`
- Full index: `https://api.ucoz.net/llms-full.txt`
- API home: `https://api.ucoz.net/`
- uCoz MCP: `https://www.ucoz.com/ai/mcp`
- Errors: `https://api.ucoz.net/errors.html`
