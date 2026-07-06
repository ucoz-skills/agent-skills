# uCoz Shop Optimizer Skill

Version: 0.6

AI agent skill for working with uCoz Online Shop through `ucoz-mcp` and uAPI.

## What's new in v0.6

The archive no longer includes `shop-openapi.yaml`. The skill must not search for a YAML schema. All required Online Shop API capabilities are built into the instructions:

- `SKILL.md`;
- `references/API_REFERENCE.md`;
- `references/MEDIA_WORKFLOW.md`.

## How the skill connects to a site

The skill connects to `ucoz-mcp` and reads from it:

- the current site/domain;
- the API key/token for that site.

It then makes direct requests to the shop API for the same site:

```text
https://{SITE_FROM_UCOZ_MCP}/uapi
```

With the same key:

```http
Authorization: Bearer <API_KEY_FROM_UCOZ_MCP>
```

The user should not manually paste an API key if MCP is already connected.

## Capabilities

- read-only shop audit;
- mandatory scope selection before optimization;
- optimization of selected categories;
- preparation and update of selected product cards;
- product image generation;
- product image upload and binding through the API:
  - `POST /shop/editgoods/img_add`;
  - `POST /shop/editgoods/img_edit`;
  - `POST /shop/editgoods/img_del`;
- category image operations via `POST /shop/edit_cat` + `mode=img`;
- safe category and product creation;
- improvements to product, category, cart and checkout templates;
- Product/Offer Schema checks;
- order reports without status changes;
- currency operations in read-only mode unless explicit approval is given.

## Core principle

Before optimization starts, the skill must confirm the work scope:

- one product;
- selected products;
- one category;
- selected categories;
- products inside a category;
- read-only audit of the whole shop.

By default, the skill does not process all products and categories.

## Important: images

Generated product images must be uploaded and bound through the Online Shop API, not via FTP or manual Control Panel upload.

For products, use dedicated operations:

- add via `file_add_cnt`, `file_add_1`, `file_add_2`, ...;
- replace by `img` via `img_edit`;
- delete via `img` or `del_all` via `img_del`.

Do not use `del_img1`, `del_img2`, ... as the general product-image mechanism.

## Files

- `SKILL.md` — main agent instructions;
- `references/API_REFERENCE.md` — built-in shop API reference;
- `references/PLAYBOOK.md` — workflow scenarios;
- `references/SHOP_OPTIMIZATION_CHECKLIST.md` — audit checklist;
- `references/COPY_GUIDE.md` — product and category copy rules;
- `references/IMAGE_GENERATION_GUIDE.md` — image generation rules;
- `references/SCOPE_RULES.md` — scope selection rules;
- `references/MEDIA_WORKFLOW.md` — image generation, upload and binding workflow;
- `examples/PROMPTS.md` — example user prompts.
