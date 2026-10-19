# uCoz Shop Optimizer Skill — Playbook

## Scenario 0: Scope selection before optimization

1. If the user did not name exact products/categories, ask what scope to use.
2. Offer concise choices: one product, selected products, one category, products in one category, selected categories, or read-only audit of the whole shop.
3. Do not start product-level optimization across the full catalog by default.
4. For mass work, show batch size and protected fields before any write operation.
5. For read-only audit, full-shop scope is allowed, but product-level changes still require a selected batch.

## Scenario 1: Read-only store audit

1. List enabled modules and shop templates through MCP.
2. Fetch the category tree with `shop_read` `category_list`.
3. Read shop-related templates: category page, product page, cart, checkout/service forms where available.
4. Read menus and identify shop/category links.
5. Optionally fetch currencies if price display or currency consistency is part of the question.
6. Return a prioritized audit without changing anything.

## Scenario 2: Category SEO and conversion cleanup

1. Fetch all categories.
2. Detect missing/weak descriptions, duplicate names, empty meta title/meta description, weak slugs and hidden important categories.
3. Prepare suggested category fields: `name`, `dscr`, `url`, `meta_title`, `meta_dscr`.
4. Show preview table.
5. Update selected categories with `shop_write` `category_update` only after approval.
6. Preserve parent category, position, access groups, visibility and image unless explicitly included in the plan.

## Scenario 3: Add a new category safely

1. Ask for or infer parent category only when the user’s request is clear.
2. Prepare `name`, `pid`, `dscr`, `url`, `pos`, `meta_title`, `meta_dscr`.
3. Confirm access rules if the store uses restricted groups.
4. Create with `shop_write` `category_add`. There is no category-delete action.
5. Re-read category list and confirm the new category exists.
6. Suggest adding it to menu only if it should be public.

## Scenario 4: Product card copy optimization

1. Get product IDs and data from the user, an export, `shop_read` `get` / `list`, or shop data you already have.
2. Detect weak names, short descriptions, full descriptions and HTML descriptions.
3. Generate improved copy while preserving facts.
4. Mark protected fields: price, old price, purchase price, stock, SKU/article, type, visibility, variations and categories.
5. Show before/after preview.
6. Update only approved text fields with `shop_write` `update`.
7. Report protected fields that were not touched.

## Scenario 5: Add products from a structured list

1. Normalize input into product rows.
2. Validate each row has at least `cat_id` and `name`.
3. Detect optional fields: `brief`, `dscr`, `html_dscr`, `price`, `price_old`, `stock`, `art`, `type`, `hide`, `cats_add`.
4. If price/stock/SKU/type/visibility are present, show them as commercial fields in preview.
5. Create products with `shop_write` `add` only after approval. `cat_id` is required by the shop.
6. If the user asks to create but not publish, follow `add` with `shop_write` `hide` and `hide` `1`.

## Scenario 6: Product template optimization

1. Use `templates_read.list_modules` to find the Online Shop module and templates.
2. Read the product page template.
3. Get available variables with `get_variables`.
4. Identify product name, price, old price, stock, image, description and cart-button variables.
5. Add or improve only safe template pieces: breadcrumbs, headings, Product/Offer JSON-LD, Open Graph fallbacks, delivery hint, trust block, related links.
6. Preserve cart buttons and dynamic variables.
7. Validate template.
8. Save after approval and re-read to confirm.

## Scenario 7: Cart and checkout diagnostic

1. Read cart and checkout templates if the issue is visual/layout related.
2. Use `shop_read` `basket_get` and `shop_read` `checkout_get` only when a test context is available.
3. Do not remove cart items or submit orders unless explicitly asked.
4. Check: product name, quantity, price, delivery/payment selection, promo code, required fields, error messages, mobile layout.
5. Return diagnostic report and safe template/content fixes.

## Scenario 8: Order status report

1. Fetch orders with `shop_read` `invoices_list` and the relevant filters.
2. Group by status, active/archive/trash group, payment and delivery when requested.
3. Highlight anomalies: old “New” orders, stuck “Paying” orders, paid but not processed, cancelled spikes.
4. Return report only.
5. Change statuses or groups only after explicit approval with exact order IDs/hashes.

## Scenario 9: Currency check

1. Fetch the currency list with `shop_read` `currencies`.
2. Report codes, rates and symbols.
3. Compare against user-provided expected rates if available.
4. Do not call `shop_write` `set_currency_rate` unless the user explicitly approves the exact `curr_code` and `curr_rate`.

## Scenario 10: Rollback template changes

1. List backups for affected template.
2. Identify the backup created before the shop optimization.
3. Restore the backup with `templates_delete` `restore_backup` and `confirm: true` if the user asks.
4. Re-read template and confirm restoration.

## Scenario 11: Product image generation

1. Identify products or categories that need images.
2. Check whether existing product photos or brand assets exist.
3. Determine image goal: product hero, gallery image, lifestyle image, category banner or placeholder.
4. Decide whether the product appearance is known.
5. If appearance is known, preserve product shape, materials, color and packaging.
6. If appearance is unknown, generate a neutral commercial concept and label it as a concept.
7. Prepare preview with: image type, style, aspect ratio, prompt, filename, alt text and placement.
8. Generate images only after the user approves the plan when replacement of existing images is involved.
9. Save generated images locally.
10. For product images, `files_write` `upload_direct`, POST the raw body to `upload_url`, then one `shop_write` `img_add` with `image_upload_ids`. Do not send `image_base64`. Existing photos are not replaced. A product that already has a main photo keeps it; a product with no photos takes the first image of that `img_add` as the main photo.
11. Replace an existing slot only after approval: `shop_write` `img_edit`, `img` (`0` = main), `confirm: true`, one `image_upload_id`. Delete with `shop_delete` `img_del` and `confirm: true`; `img` is required unless `del_all=1`.
12. Do not use `del_imgN` flags for product images.
13. For a category image, use `shop_write` `category_image` with one `image_upload_id` (jpg, png, or gif). Remove it with `shop_delete` `category_image_delete` and `confirm: true`.
14. If a shop tool cannot bind the image, report that blocker. Do not send the user to the Control Panel. Do not call `/uapi` or `/shop/editgoods`.

## Scenario 12: Category banner generation

1. Audit category pages that have weak or missing visual headers.
2. Extract category theme, product range and visual tone.
3. Generate a banner direction: clean ecommerce, seasonal campaign, premium, sale, minimal or illustrative.
4. Keep banner text outside the image unless the user explicitly wants text rendered on the banner.
5. Prepare banner sizes and placements for the category template or global block.
6. Return final image, alt text, filename and insertion recommendation.
