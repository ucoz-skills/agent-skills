# uCoz Shop Optimizer Skill

Version: 0.7

A skill for an AI agent that works with a uCoz Online Shop through the current uCoz MCP tools.

## Key change in v0.7

The shop is called only through MCP. `list_sites`, then `select_site(site_id)`, then `shop_read`, `shop_write`, and `shop_delete`. The current MCP does not provide a uAPI key, so handwritten HTTP to `https://{host}/uapi` and `Authorization: Bearer` is not the way to edit products.

The skill still must not look for `shop-openapi.yaml`. The actions live in:

- `SKILL.md`;
- `references/API_REFERENCE.md`;
- `references/MEDIA_WORKFLOW.md`.

## How the skill connects to a site

1. `list_sites`.
2. `select_site(site_id)`.
3. Shop tools use that selected site.

There is no base URL and no bearer token. Do not take or ask for an API key.

## What it can do

- audit the store without making changes;
- mandatory scope selection before optimization;
- optimization of selected categories;
- preparing and updating selected product cards;
- generating product images;
- uploading and binding product images:
  - `files_write` `upload_direct` with `filename` and exact `size` (1..16777216); the reply has `upload_url`, `upload_id`, `expires_in` 900, and the file is not attached yet;
  - POST the raw body to `upload_url` as `application/octet-stream` with that length; the reply is `ok`, `upload_id`, `filename`, `size`, `sha256`;
  - one `shop_write` `img_add` with comma-separated `image_upload_ids` (do not send `image_base64`);
  - `shop_write` `img_edit` (`img` 0 = main, `confirm: true`, one `image_upload_id`) to replace a slot;
  - `shop_delete` `img_del` with `confirm: true` (`img` is required unless `del_all=1`);
- category images: `shop_write` `category_image` with one `image_upload_id` (jpg, png, or gif; `.jpeg` is renamed to jpg); remove with `shop_delete` `category_image_delete` and `confirm: true`;
- if the product already has a main photo, `img_add` appends slots and leaves it (checked on product 85: 295602, 320024, 290687; `num_photos` 11→14; slots 11, 12, 13);
- if the product has no photos, the first image of that `img_add` becomes the main photo (checked 2026-10-19 on product 87, betatest-0007);
- the public product image URL is webp; `Content-Length` there is the webp size, not the uploaded size;
- safe creation of categories and products (`shop_write` `category_add` and `add`; `cat_id` is required by the shop; there is no category-delete action);
- improving product, category, cart, and checkout templates;
- checking Product/Offer schema;
- order reports without changing statuses;
- read-only work with currencies unless a change is explicitly confirmed.

## Core principle

Before starting any optimization, the skill must confirm the work scope:

- one product;
- selected products;
- one category;
- selected categories;
- products inside a category;
- read-only audit of the whole store.

By default the skill never processes all products and categories.

## Important note on images

Generated product images are uploaded with `files_write` `upload_direct` and bound with `shop_write`, not via a manual Control Panel upload.

- Several photos are one `img_add` with `image_upload_ids`. Existing photos are not replaced. A product that already has a main photo keeps it.
- A product with no photos gets its main photo from the first image of that `img_add`.
- Replacing a slot is `img_edit` with `confirm: true`.
- Deleting is `img_del` with `confirm: true`.
- File-manager files (not product photos) are a separate `files_write` `upload` with `path` and `upload_ids` (at most 20). Those keep the original bytes.

Do not use the `del_img1`, `del_img2`, ... flags as a general mechanism for product images. Do not POST multipart to `/shop/editgoods` or `/uapi`.

## Files

- `SKILL.md` — the main instruction for the agent;
- `references/API_REFERENCE.md` — MCP action reference for the shop;
- `references/PLAYBOOK.md` — working scenarios;
- `references/SHOP_OPTIMIZATION_CHECKLIST.md` — audit checklist;
- `references/COPY_GUIDE.md` — rules for product and category copy;
- `references/IMAGE_GENERATION_GUIDE.md` — rules for image generation;
- `references/SCOPE_RULES.md` — work scope selection rules;
- `references/MEDIA_WORKFLOW.md` — image generation, upload, and binding workflow;
- `examples/PROMPTS.md` — example user prompts.
