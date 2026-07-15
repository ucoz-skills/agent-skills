# uCoz uAPI routes for localization

Use this reference only for direct material/category operations or when an MCP action is unavailable. Confirm request fields against the linked current OpenAPI file before every write.

## Connection

- Base URL: `https://{configured-site-host}/uapi`
- Header: `Authorization: Bearer {configured-uAPI-token}`
- Success envelope: `{ "success": ... }`
- Error envelope: `{ "error": { "code": "...", "msg": "..." } }`

Obtain the host and token from the secure active MCP/client configuration. Do not emit them. The MCP server is the preferred path for templates, menus, pages, forms, and FTP.

## Discovery and search

- Site search: `GET /search?query={q}`
- Module search: `GET /search?query={q}&module={module_code}`
- Module material list: `GET /{module}`
- One material: use the module OpenAPI contract; common modules accept `GET /{module}?id={id}`
- Category list: `GET /{module}/category`

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

Common editable prose includes `title`, `name`, `description`, `message`, `brief`, `dscr`, `html_dscr`, and requested `meta_*` fields. Actual names differ by module. Preserve category IDs, status flags, dates, files, ownership, URLs, and operational fields unless the user explicitly scopes them.

Before presenting scope, inspect all fields returned by GET and the update request schema. Offer every non-empty editable user-facing text property, including module-specific fields, SEO, tags, custom text fields, and editable image labels. Do not offer response-only strings, URLs, authors, states, or nested related objects as if they were fields of the material.

Common updates:

- Material: `PUT /{module}?id={id}`
- Category: `PUT /{module}/category?id={id}`

These are patterns, not permission to invent parameters. Inspect the module OpenAPI schema and send only accepted fields.

## Content format is part of the data

Never send a rich-text value without its documented format controls. Preserve the source format rather than allowing the API default to reinterpret it.

- Common content modules use `description_type` / `message_type` with `plain`, `bb`, or `html`.
- The same modules may expose `includehtml_desc`, `includehtml_msg`, `end2br_desc`, and `end2br_msg`.
- Forum messages use module-specific controls such as `includehtml` and `end2br`.
- Other modules differ; read their current update schema instead of copying News parameters.

When HTML is detected, send the corresponding `*_type=html` and documented HTML-enable flag. Preserve the line-break behavior. Omitting `message_type=html` can make uAPI store markup as escaped text (`&lt;p&gt;...`). See [CONTENT_FIELDS_AND_FORMATS.md](CONTENT_FIELDS_AND_FORMATS.md) for the complete procedure.

## Slugs and other URL fields

Treat `hgu_title`, `ownurl`, and module equivalents as optional localization fields, not ordinary prose. After discovery, ask whether to update them and warn that the public URL can change. Require explicit approval and show old/new URLs. Never infer approval from a request to translate the title or body.

## Pages, templates, and menus

Prefer the equivalent `templates_tool` actions.

- Pages: `GET /pages`, `GET /pages/{id}`, `PUT /pages` with `page_id`
- Templates: `GET /templates`, `GET /templates/{module_id}/{template_id}`, `PUT /templates/{module_id}/{template_id}`
- Menus: `GET /menus`, `GET /menus/{id}`, `PUT /menus/{id}`, `PUT /menus/{id}/items`

Schemas: `https://api.ucoz.net/pages-openapi.yaml`, `https://api.ucoz.net/templates-openapi.yaml`, `https://api.ucoz.net/menus-openapi.yaml`.

## Online shop

Read categories with `GET /shop/request?page=categories`. Consult the shop OpenAPI for product discovery and all edit operations; do not assume a legacy route that is absent from the current schema.

Protect price, currency, stock, SKU/article, product type, visibility, variations, order data, delivery, and payment settings. Translate only approved category/product prose and SEO fields.

## Canonical documentation

- Compact index: `https://api.ucoz.net/llms.txt`
- Full index: `https://api.ucoz.net/llms-full.txt`
- API home: `https://api.ucoz.net/`
- MCP actions: `https://api.ucoz.net/mcp.html`
- Errors: `https://api.ucoz.net/errors.html`

