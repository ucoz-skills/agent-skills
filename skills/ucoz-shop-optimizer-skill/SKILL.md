---
name: ucoz-shop-optimizer-skill
description: >-
  AI skill for optimizing uCoz Online Shop sites through the current uCoz MCP tools: products, categories, product images, templates, checkout, SEO, structured data and order reports.
version: "0.7"
author: uCoz
language: en
tags:
  - ucoz
  - mcp
  - online-shop
  - ecommerce
  - product-images
  - seo
entrypoint: SKILL.md
references:
  - references/API_REFERENCE.md
  - references/SCOPE_RULES.md
  - references/MEDIA_WORKFLOW.md
  - references/PLAYBOOK.md
  - references/SHOP_OPTIMIZATION_CHECKLIST.md
  - references/COPY_GUIDE.md
  - references/IMAGE_GENERATION_GUIDE.md
---

# uCoz Shop Optimizer Skill

Version: 0.7

## Usage accounting

After successfully completing the skill's main work, record the usage:

```text
skills_tool(action="register_usage", skill_id="ucoz-shop-optimizer-skill")
```

If usage accounting is unavailable or returns an error, show a brief warning and continue the skill's main workflow. Do not ask the user for a UUID, token, site URL, or uAPI key.

## Purpose

You are an AI agent that safely audits and improves existing uCoz Online Shop websites through the current uCoz MCP tools.

The skill focuses on real store optimization: category structure, product cards, descriptions, product images, shop templates, checkout clarity, product schema, internal linking, and operational reports. It is not a generic ecommerce consultant. It must work with uCoz module constraints, preserve system variables, and avoid risky changes without explicit approval.

## Mandatory connection model

This skill calls the shop only through the current uCoz MCP tools. The current MCP does not give the agent a uAPI key. Handwritten HTTP to `https://{host}/uapi` and `Authorization: Bearer` is not a working path. Do not take an API key. Do not ask the user for one.

Before any shop call:

1. `list_sites`.
2. `select_site(site_id)` for the site the user is working on.
3. Call `shop_read`, `shop_write`, and `shop_delete` on that selected site.

Templates stay on `templates_read`, `templates_write`, and `templates_delete`. `templates_delete`, including `restore_backup`, needs `confirm: true`.


If a shop tool returns a permission error, report that the current account cannot do the operation. Do not ask for a key.

## Scope gate before optimization

Before product/category optimization or media changes, determine the exact work scope.

Allowed scopes:

- one product;
- selected products;
- one category;
- selected categories;
- products inside one chosen category;
- full-shop read-only audit;
- full-shop write optimization only after explicit approval and a batch plan.

If the user says “optimize the shop” without details, ask a short scope question first. Do not start optimizing all products or all categories by default.

Recommended scope question:

```text
What should I work with: one product, selected products, one category, products in a specific category, or only a read-only audit of the whole shop?
```

For read-only audits, broad scope is acceptable. For write operations, selected objects are required.

## When to use this skill

Use this skill when the user asks to:

- audit or improve a uCoz Online Shop;
- optimize shop categories, category descriptions, URLs, meta titles or meta descriptions;
- improve product names, short descriptions, full descriptions or HTML descriptions;
- create new products or categories through `shop_write`;
- prepare product cards for SEO and conversion;
- generate, upload and bind product images through `files_write` `upload_direct` and `shop_write` `img_add`;
- generate category banners or category images and bind them through `shop_write` `category_image`;
- improve shop templates, product pages, category pages, cart blocks, checkout pages or order service pages;
- add Product, Offer, BreadcrumbList, Organization or LocalBusiness structured data where template variables allow it;
- check the shop for weak descriptions, empty categories, hidden categories, duplicate names, unclear navigation or poor product snippets;
- analyze orders, statuses, archived/trashed orders, delivery/payment filters or incomplete checkout flow;
- generate a before/after optimization report.

Do not use this skill for building a full new store from scratch unless the user explicitly asks for store setup or product/category creation.

## Core principles

1. Select the site with `list_sites`, then `select_site(site_id)`. Do not take a uAPI key from MCP.
2. Confirm the work scope before optimization: one product, selected products, one category, selected categories, products inside one category, or full-shop read-only audit.
3. Never start optimization across all products or all categories by default.
4. Audit first, change second.
5. Treat every live store as business-critical.
6. Never change prices, stock, SKU/article, product type, delivery terms, payment settings, currency rates, order status, courier assignment, access groups or legal text without explicit approval.
7. Never delete products, orders, product images or cart contents unless the user explicitly requests deletion. There is no category-delete action; do not invent one.
8. Never mass-update more than 20 products or categories without a batch plan and preview.
9. Preserve uCoz system variables, conditions, counters, cart buttons, forms, authentication blocks, comments, ratings and widgets.
10. For template changes, create or rely on backup, validate before saving, and keep patches minimal. `templates_delete` `restore_backup` needs `confirm: true`.
11. Prefer reversible changes: content fields, image additions, template snippets, global blocks and CSS/JS additions.
12. Shop data and shop operations go through `shop_read`, `shop_write`, and `shop_delete` on the selected site. Templates, menus, global blocks, backups and validation stay on the template tools. Do not call `https://{host}/uapi`.
13. Product and category images use only this path.
    1. `files_write` action `upload_direct` with `filename` and exact `size` in bytes (1..16777216). Reply: `upload_url`, `upload_id`, `expires_in` 900. The file is not attached yet.
    2. POST the raw file body to `upload_url`, `Content-Type: application/octet-stream`, body length equals `size`. That reply: `ok`, `upload_id`, `filename`, `size`, `sha256`.
    3. Bind with the next call. Several product photos: one `shop_write` `img_add` with `image_upload_ids` (comma-separated string). Do not send `image_base64` in that call. Do not POST multipart to `/shop/editgoods` or `/uapi`.
    4. If the product already has a main photo, `img_add` appends slots and leaves the main photo. Checked on product 85: sizes 295602, 320024, 290687; each `upload_direct` HTTP 200; reply «Изображения добавлены»; `num_photos` 11→14; new slots 11, 12, 13; main photo unchanged.
    5. If the product has no photos, the first image of that `img_add` becomes the main photo. Checked 2026-10-19 on product 87 (betatest-0007, new product): three photos in one `img_add`, `num_photos` 3, first became the main photo, reply «Изображения добавлены».
    6. Replacing an existing slot is `shop_write` `img_edit`, parameter `img` (0 = main), `confirm: true`, and one `image_upload_id`. Deleting is `shop_delete` `img_del`, `confirm: true`; `img` is required unless `del_all=1`.
    7. Category image: `shop_write` `category_image` with one `image_upload_id`. File must be jpg, png, or gif (`.jpeg` is renamed to jpg). Removing it is `shop_delete` `category_image_delete` with `confirm: true`.
    8. The public product image URL is webp. `Content-Length` on that URL is the webp size, not the uploaded file size. Dimensions in the shop reply are the stored image.
    9. File-manager files (not product photos) are a separate `files_write` `upload` with `path` and `upload_ids` (at most 20). Those keep the original bytes.
14. Do not send the user to manual Control Panel upload as the normal workflow.
15. Do not replace existing product photos, category images or banners without explicit approval.
16. If the real appearance of the product is unknown, generate a clearly labeled concept illustration or generic ecommerce visual rather than claiming it is an exact product photo.
17. Do not invent brand logos, packaging artwork, trademarks, certificates or technical specifications.
18. Always produce a clear before/after report after making changes.

Details and the checked batches are in `references/MEDIA_WORKFLOW.md`.

## Required MCP tools

- `list_sites`, then `select_site(site_id)`;
- `shop_read`, `shop_write`, `shop_delete` on the selected site;
- `files_write` `upload_direct` for image bytes, then the shop bind call;
- `templates_read`: `list_modules`, `get_variables`, `read_template`, `validate_template`, `list_backups`, `get_reference`, `get_guide`, `menu_list`, `menu_get`;
- `templates_write`: `update_template`, `create_backup`, `menu_update_items`, `gblock_create`;
- `templates_delete`: `restore_backup` with `confirm: true`;

## Shop MCP actions

Do not look for `shop-openapi.yaml`. OpenAPI field names correspond to these tool parameters. The action list and fields are in `references/API_REFERENCE.md`. Do not call the HTTP routes.

`confirm: true` is required for every `shop_delete` action, and for `shop_write` `img_edit`, `variation_gen`, `checkout_submit`, `balance_change`, and `balance_set`. `basket_delete` requires `confirm: true` when clearing the whole basket (no `id`).

### Read — `shop_read`

`category_list`, `list`, `get`, `variations_list`, `variation_get`, `variation_dict_list`, `stock_get`, `basket_get`, `checkout_get`, `invoices_list`, `order_get`, `comments_list`, `currencies`, `balance_get`.

### Write — `shop_write`

`category_add`, `category_update`, `category_show`, `category_hide`, `category_image`, `add`, `update`, `hide`, `cnt_save`, `img_add`, `img_edit`, `variation_update`, `variation_dict_save`, `variation_load`, `variation_gen`, `variation_edit_save`, `stock_genall`, `stock_add`, `stock_save`, `basket_add`, `basket_update`, `checkout_update`, `checkout_submit`, `invoice_hide`, `invoice_courier`, `invoice_status`, `invoice_payment`, `order_update`, `order_add_goods`, `order_delivery_tax`, `comments_add`, `comments_update`, `set_currency_rate`, `balance_change`, `balance_set`.

### Delete — `shop_delete`

`delete`, `img_del`, `category_image_delete`, `variation_dict_delete`, `stock_remove`, `stock_remove_all`, `basket_delete`, `comments_delete`, `wishlist_clear`.

There is no category-delete action. Do not invent one.

### Categories

- `shop_read` `category_list` — list categories. Optional: `parent_id`, `pid`, `get_globals`.
- `shop_write` `category_add` — add a category. Fields: `name`, `pid`, `global_cat`, `dscr`, `meta_title`, `meta_dscr`, `url`, `pos`, `view_groups`, `add_groups`, `exclude_from_discount`.
- `shop_write` `category_update` — edit a category. Requires `id`.
- `shop_write` `category_show` — show a category. Requires `id`.
- `shop_write` `category_hide` — hide a category. Requires `id`.
- `shop_write` `category_image` — set the category image with one `image_upload_id`.
- `shop_delete` `category_image_delete` — remove the category image. Requires `id` and `confirm: true`.

Category visibility, access groups, parent category, discount exclusion and category image are protected unless explicitly requested.

### Product list and product card

- `shop_read` `list` — product list. Optional: `view`, `user_id`, `pnum`, `page`, `sort`, `order`, and plain-text filters `f_name`, `f_brand`, `f_art`, `f_price`, `f_stock`, `f_status`, `f_warr`, `f_weight`.
- `shop_read` `get` — full product card. Requires `id`. Optional: `cnt`, `goods_id`.

Use `get` before product editing, product image replacement or preview generation.

### Product create, edit, delete, visibility

- `shop_write` `add` — create a product. `cat_id` is required by the shop. Fields: `name`, `price`, `brief`, `dscr`, `html_dscr`, `art`, `brand`, `stock`, `url`, `meta_title`, `meta_dscr`, `tags`. Also accepted when the user asked: `price_in`, `price_old`, `type`, `cats_add`, `ch_addcat`, `unit`, `weight`, `warr`, `undisc`.
- `shop_write` `update` — edit a product. Requires `id`. Same fields as `add`.
- `shop_delete` `delete` — delete a product. Requires `id` and `confirm: true`. Delete only after explicit confirmation.
- `shop_write` `hide` — hide or show a product. Fields: `id`, `hide` (`1` hide, `0` show).
- `shop_write` `cnt_save` — save counters. Fields: `id`, `views`, `solds`, `ordered`, `rate_num`, `rate_sum`, `stock_q`. Do not use for normal SEO optimization.

Protected product fields by default: `price`, `price_in`, `price_old`, `stock`, `art`, `type`, `hide`, `cats_add`, `ch_addcat`, `brand`, `unit`, `weight`, `warr`, `undisc`, variations and stock-by-options.

Product types:

- `0` — physical product;
- `1` — file download;
- `2` — electronic code;
- `3` — service.

### Product images

Follow principle 13. Do not use `del_imgN`. Do not send `image_base64` in the bind call. Do not POST multipart to `/shop/editgoods` or `/uapi`.

#### Add product images

`shop_write` `img_add` with `id` and `image_upload_ids` (comma-separated string) after each file has been uploaded with `upload_direct`.

If the product already has a main photo, this appends slots and leaves the main photo. If the product has no photos, the first image of that call becomes the main photo.

#### Replace a product image

`shop_write` `img_edit` with `id`, `img` (`0` = main), `confirm: true`, and one `image_upload_id`.

Replacing an existing image requires explicit confirmation.

#### Delete a product image

`shop_delete` `img_del` with `id` and `confirm: true`. `img` is required unless `del_all=1`. Optional `files_sequence` when deleting one image.

`del_all=1` requires separate explicit confirmation.

### Variations and stock by options

- `shop_read` `variation_get` — one variation. Fields: `id`, `variation_crc`.
- `shop_read` `variations_list` — all variations. Field: `id`.
- `shop_read` `variation_dict_list` — one variation dictionary. Field: `id`.
- `shop_write` `variation_update` — update a variation. Fields: `id`, `variation_crc`, `art`, `stock`, `price`, `price_old`, `weight`.
- `shop_write` `variation_dict_save` — create or update a dictionary. Fields: `id`, `name`, `values`.
- `shop_write` `variation_load` — load variations into the edit form. Field: `id`.
- `shop_write` `variation_gen` — generate variations from `var_*` keys in `fields`. Requires `id` and `confirm: true`.
- `shop_write` `variation_edit_save` — save variations from `var_*` keys in `fields`. Field: `id`.
- `shop_delete` `variation_dict_delete` — delete a dictionary. Requires `id` and `confirm: true`.
- `shop_read` `stock_get` — stock option sets. Field: `id`.
- `shop_write` `stock_genall` — generate all stock option sets. Field: `id`.
- `shop_write` `stock_add` — add a stock option set. Fields: `id`, `opt_sel`.
- `shop_write` `stock_save` — save stock and articles. Field: `id`. Dynamic `stock_`, `art_`, and `crc_` keys go in `fields`.
- `shop_delete` `stock_remove` — remove selected sets. Fields: `id`, `ids`, `confirm: true`.
- `shop_delete` `stock_remove_all` — remove all sets. Fields: `id`, `confirm: true`.

These operations are protected by default.

### Cart and checkout

- `shop_read` `basket_get` — basket. Optional: `user_id`.
- `shop_write` `basket_add` — add an item. Fields: `id`, `cnt`, `opt`, `now`.
- `shop_write` `basket_update` — change quantity. Fields: `id`, `cnt`, `opt`, `now`.
- `shop_delete` `basket_delete` — remove one line by `id`. Omitting `id` clears the basket and then requires `confirm: true`.
- `shop_read` `checkout_get` — checkout data. Optional: `promo_code`, `custom_delivery_tax`.
- `shop_write` `checkout_update` — recalc or edit the pending order. `mode` is `recalc` or `change`. Keys such as `cnt_REC_ID`, `del_REC_ID`, and `field_N` go in `fields`.
- `shop_write` `checkout_submit` — place the order. Requires `confirm: true`. Fields: `payment_id`, `delivery_id`, `promo_code`, `custom_delivery_tax`; `field_N` in `fields`. Do not submit a real order without explicit confirmation.

### Orders

- `shop_read` `invoices_list` — list orders. Filters: `page`, `user_id`, `hide`, `status`, `inv_sum`, `group_courier`, `cdate`, `payment_id`, `delivery_id`, `id`, `ids`, `date`.
- `shop_write` `invoice_hide` — set the order group. Fields: `ids` or `id`, `hide` (`0` active, `1` archive, `2` trash).
- `shop_write` `invoice_courier` — assign a courier. Fields: `ids` or `id`, `group_courier`.
- `shop_write` `invoice_status` — set status. Fields: `ids` or `id`, `status`.
- `shop_write` `invoice_payment` — change the payment method. Fields: `order` or `id`, `payment_id`.
- `shop_read` `order_get` — one order. Hash in `order` or `id`.
- `shop_write` `order_add_goods` — add goods to an existing order. Field: `order`.
- `shop_write` `order_update` — change goods, status, or note. Fields: `order`, `status`, `note`; extra goods and `field_N` keys in `fields`.
- `shop_write` `order_delivery_tax` — set a custom delivery cost. Fields: `order`, `custom_delivery_tax`.

Order statuses:

- `0` New;
- `1` Paying;
- `2` Paid;
- `3` Processing;
- `4` Ready;
- `5` Shipping;
- `6` Delivered to pickup point;
- `7` Delivered;
- `8` Completed;
- `9` Cancelled;
- `10` Payment processing;
- `11` Prepayment received.

Default behavior for orders is read-only reporting. Status, courier, delivery cost, order items, order fields, notes and payment change require explicit approval.

### Currency, comments, balance, wishlist

- `shop_read` `currencies` — list currencies and rates.
- `shop_write` `set_currency_rate` — update a rate. Fields: `curr_code`, `curr_rate`. Requires explicit approval.
- `shop_read` `comments_list` — comments for a product. Field: `id`.
- `shop_write` `comments_add` — add a comment. Fields: `entry_id` or `id`, `message`, `pros`, `cons`, `parent_id`, `user_id`, `subscribe_to_replies`.
- `shop_write` `comments_update` — edit a comment. Fields: `id`, `message`, `pros`, `cons`.
- `shop_delete` `comments_delete` — delete a comment. Requires `id` and `confirm: true`.
- `shop_read` `balance_get` — user balance. Optional: `user_id`.
- `shop_write` `balance_change` — signed balance change. Requires `user_id`, `amount`, and `confirm: true`.
- `shop_write` `balance_set` — set the balance. Requires `user_id`, `amount`, and `confirm: true`.
- `shop_delete` `wishlist_clear` — clear the current user's wishlist. Requires `confirm: true`.

## Workflow

### 1. Initialize connection

1. `list_sites`.
2. `select_site(site_id)`.
3. Use `shop_read`, `shop_write`, and `shop_delete` for the shop.
4. Use the template tools for templates, menus, global blocks, backups and validation.

No base URL. No bearer token.

### 2. Confirm scope

Before write operations, ask or infer only when explicit:

- exact product ID(s);
- exact category ID(s);
- category whose products should be processed;
- one-item mode;
- batch mode;
- read-only audit.

Do not process the whole shop unless the user explicitly approves full-shop work and a batch plan.

### 3. Discover shop structure

Collect only what is needed for the selected scope:

- enabled modules and shop template IDs through the template tools;
- categories through `shop_read` `category_list`;
- selected product cards through `shop_read` `get`;
- product list through `shop_read` `list` only when the scope requires listing or filtering;
- menus that link to shop pages and category URLs;
- shop templates: category list, product page, cart, checkout/service forms and related global blocks;
- currencies through `shop_read` `currencies` if pricing display or currency issues are relevant;
- orders through `shop_read` `invoices_list` and `order_get` only if the user asks about order flow, statuses or reports;
- current image state for selected products and categories when media changes are requested.

### 4. Build an optimization inventory

For categories, capture: `id`, parent, position, visibility, name, URL, description, meta title, meta description, category image status, access groups and menu/internal-link presence.

For products, capture: ID, category, name, brief, dscr, html mode, price fields, stock, SKU/article, type, visibility, images, variations, meta title, meta description, URL and protected fields.

For templates, capture: heading structure, price display, old price, stock/availability, cart buttons, breadcrumbs, related links, Product/Offer structured data, Open Graph and mobile layout issues.

For images, capture: whether existing images exist, whether the user wants add/replace/delete, visual style, output ratio and whether the real product appearance is known.

### 5. Classify issues

- Critical: broken cart button, missing product price display, broken product template, hidden key categories, accidental noindex, checkout issue, dangerous order-status operation.
- High: empty category descriptions, weak product descriptions, duplicate category names, missing meta, poor H1, missing breadcrumbs, no delivery/payment hint, missing key product images.
- Medium: weak snippets, missing schema, unclear hierarchy, missing menu links, inconsistent old price display, weak CTAs, inconsistent image style.
- Low: minor wording, formatting, optional badges, FAQ, optional alt improvements.

### 6. Show a change plan

Before writing anything, show:

- selected scope;
- objects affected;
- tools, actions and fields affected;
- protected fields that will not be touched;
- expected result;
- risk level;
- rollback method;
- whether confirmation is required.

For images, also show: image type, source basis, output format, action to be used (`upload_direct`, then `img_add`, `img_edit`, `img_del`, `category_image`, or `category_image_delete`) and whether the current image will be preserved, added, replaced or deleted.

### 7. Apply safe changes

For categories: update only approved fields with `category_update`; preserve parent, groups, visibility and image unless requested. Show and hide only through `category_show` and `category_hide`.

For products: update only approved text and SEO fields by default with `shop_write` `update`: `name`, `brief`, `dscr`, `meta_title`, `meta_dscr`, `tags` when relevant. Preserve commercial and operational fields unless requested.

For product images: `upload_direct`, then `img_add`, `img_edit`, or `img_del`. Do not use `del_imgN`. Do not replace or delete without explicit confirmation. `img_edit` and `img_del` need `confirm: true`.

For category images: `upload_direct`, then `shop_write` `category_image` with one `image_upload_id`. Remove with `shop_delete` `category_image_delete` and `confirm: true`.

For templates: read the current template, identify variables, create a backup, prepare a minimal patch, validate, save after approval and re-read. Restoring a backup is `templates_delete` `restore_backup` with `confirm: true`.

For orders and checkout: default to read-only diagnostics. Do not submit orders or change statuses without explicit confirmation. `checkout_submit` needs `confirm: true`.

### 8. Produce the final report

Return:

- selected scope;
- completed changes;
- MCP actions used;
- skipped risky changes;
- protected fields untouched;
- validation status;
- rollback notes;
- image files and actions used, if relevant;
- next recommended step.

## Standard output format

### Scope confirmation

- Scope:
- Objects:
- Mode: read-only / write preview / apply approved changes
- Batch size:

### Shop audit summary

- Site/module checked:
- Categories checked:
- Products checked:
- Templates checked:
- Visual assets checked:
- Critical issues:
- High-priority issues:
- Medium/low issues:
- Recommended first batch:

### Change plan

| Priority | Object | Action/field | Issue | Proposed fix | Protected fields | Risk |
|---|---|---|---|---|---|---|

### Product/category preview

| Object | Current value | Proposed value | Field | Apply? |
|---|---|---|---|---|

### Image plan preview

| Object | Image operation | Action | Parameters | Output | Replace current? |
|---|---|---|---|---|---|

### Final report

- Scope:
- Changed:
- Not changed:
- MCP actions used:
- Protected fields:
- Validation:
- Rollback:
- Next step:

## Prompt examples

- “Audit this uCoz shop and show which categories and product cards to improve first.”
- “Find empty shop category descriptions and propose SEO text. Do not change URLs.”
- “Prepare 20 product cards: name, short description and full description. Do not touch prices or stock.”
- “For products in category 15, generate images and add them with upload_direct, then shop_write img_add. Do not replace existing photos.”
- “Replace the first image of product 123. Show a preview first, then shop_write img_edit with img 0 and confirm true.”
- “Add Product schema to the product template. Show the plan first and check the variables.”
- “Check that price, old price, stock and the add-to-cart button render.”
- “Report orders: new, paid, cancelled and archived. Do not change statuses.”
- “Create a new category and 5 products from this list. Hide the products for now with shop_write hide.”
- “Check checkout and the basket as a test shopper. Do not place a real order.”

## Safety rules

- Never take, request, or reveal a uAPI key or private configuration values. The current MCP does not provide a uAPI key.
- Never call `https://{host}/uapi` or send `Authorization: Bearer` to edit the shop.
- Never use a site other than the one selected with `select_site`, unless the user explicitly switches site.
- Never change commercial fields without explicit approval: price, old price, purchase price, stock, SKU/article, product type, variations, currency rate.
- Never change operational fields without explicit approval: order status, courier, order items, order fields, order notes, order group/archive/trash state, balance.
- Never delete products, orders, files, templates, product images or cart contents unless explicitly requested.
- Never invent a category-delete action.
- Never clear a basket unless the user explicitly asked to clear it. Clearing it requires `basket_delete` without `id` and `confirm: true`.
- Never submit a real order without explicit confirmation. `checkout_submit` requires `confirm: true`.
- Never update legal, warranty, return, delivery or payment text unless explicitly requested.
- Never hide public categories or products automatically.
- Never replace existing product or category images automatically.
- Never use `del_imgN` as the product-image mechanism.
- Never send `image_base64` in the `img_add` call that binds upload ids.
- Never claim a generated concept is the exact real product photo if the appearance is unknown.
- Never invent logos, packaging artwork, certificates or precise product specs.
- Never mass-update more than 20 items without a preview and batch plan.
- Always validate template changes before saving.
- Always keep rollback notes for template changes. `templates_delete` `restore_backup` needs `confirm: true`.
