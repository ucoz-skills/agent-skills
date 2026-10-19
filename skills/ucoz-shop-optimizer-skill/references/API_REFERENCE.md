# uCoz Shop Optimizer — MCP action reference

This file replaces the old HTTP cookbook. Call the shop only through the current uCoz MCP tools on the site selected with `select_site`. Do not call `https://{host}/uapi`. Do not send `Authorization: Bearer`. Do not take or ask for a uAPI key: the current MCP does not provide one.

OpenAPI field names correspond to these tool parameters.

Do not look for `shop-openapi.yaml`.

## Site

1. `list_sites`.
2. `select_site(site_id)`.

Every `shop_read`, `shop_write`, and `shop_delete` call uses that selected site. There is no base URL and no bearer token.

If a shop tool returns a permission error, say that the current account cannot do that operation. Do not ask for another key.

## Confirm

Pass `confirm: true` only after the user approves that change.

- Every `shop_delete` action requires `confirm: true`.
- `shop_write` requires `confirm: true` for `img_edit`, `variation_gen`, `checkout_submit`, `balance_change`, and `balance_set`.
- `shop_delete` `basket_delete` requires `confirm: true` when clearing the whole basket (no `id`). Deleting one line by `id` does not.
- `templates_delete`, including `restore_backup`, requires `confirm: true`.

## Read — `shop_read`

| Action | Role | Parameters |
|---|---|---|
| `category_list` | list categories | optional `parent_id` or `pid`, `get_globals` (`1` includes global categories) |
| `list` | product list | optional `view` (`allgoods` default, `wishlist`, `usergoods`), `user_id`, `pnum`, `page`, `sort`, `order`, `f_name`, `f_brand`, `f_art`, `f_price`, `f_stock`, `f_status`, `f_warr`, `f_weight` |
| `get` | one product card | `id` required; optional `cnt`, `goods_id` |
| `variations_list` | variations of a product | `id` |
| `variation_get` | one variation | `id`, `variation_crc` |
| `variation_dict_list` | one variation dictionary | `id` |
| `stock_get` | stock by option sets | `id` |
| `basket_get` | basket | optional `user_id` |
| `checkout_get` | checkout form | optional `promo_code`, `custom_delivery_tax` |
| `invoices_list` | orders | optional `page`, `user_id`, `hide`, `status`, `group_courier`, `payment_id`, `delivery_id`, `ids`, `id`, `inv_sum`, `cdate`, `date` |
| `order_get` | one order | order hash in `order` or `id` |
| `comments_list` | product comments | `id` |
| `currencies` | currencies and rates | none |
| `balance_get` | user balance | optional `user_id` |

`list` filters are plain strings. `sort` may be `id`, `art`, `brand`, `name`, `price`, `warr`, `weight`, `added_time`, `modified_time`, `num_com`, `rating`, `ordered`, `views`, `solds`, `other#`. `order` on a list is `asc` or `desc`. `f_price` is `MIN|MAX|CURRENCY`.

Use `list` only after the scope is chosen. Do not start by optimizing every product.

Read `get` before editing a product, replacing an image, or building a preview.

## Write — `shop_write`

### Categories

There is no category-delete action. Do not invent one.

| Action | Role | Parameters |
|---|---|---|
| `category_add` | create a category | `name`; optional `pid` / `parent_id`, `global_cat`, `dscr`, `meta_title`, `meta_dscr`, `url`, `pos`, `view_groups`, `add_groups`, `exclude_from_discount` |
| `category_update` | edit a category | `id` required; same text and structure fields as add |
| `category_show` | show a category | `id` |
| `category_hide` | hide a category | `id` |
| `category_image` | set the category image | `id`, one `image_upload_id` |

`name` is required for a new category. Protected unless the user asked: visibility, access groups, parent, position, discount exclusion, and the category image.

Category image file must be jpg, png, or gif. `.jpeg` is renamed to jpg. Upload the bytes first (`files_write` `upload_direct`), then bind with `category_image`. See `references/MEDIA_WORKFLOW.md`.

### Products

| Action | Role | Parameters |
|---|---|---|
| `add` | create a product | `cat_id` is required by the shop; `name`, `price`, `brief`, `dscr`, `html_dscr`, `art`, `brand`, `stock`, `url`, `meta_title`, `meta_dscr`, `tags`. Also accepted: `price_in`, `price_old`, `type`, `cats_add`, `ch_addcat`, `unit`, `weight`, `warr`, `undisc` |
| `update` | edit a product | `id` required; same fields as `add` |
| `hide` | hide or show | `id`, `hide` (`1` hide, `0` show) |
| `cnt_save` | counters | `id`, `views`, `solds`, `ordered`, `rate_num`, `rate_sum`, `stock_q` |

`html_dscr` `1` means `dscr` is HTML. `ch_addcat` `1` is required when `cats_add` is set. `cats_add` is extra category ids, `[ID1, ID2, ...]`.

Product types: `0` physical, `1` file download, `2` electronic code, `3` service.

Protected unless the user asked: `price`, `price_in`, `price_old`, `stock`, `art`, `type`, `hide`, `cats_add`, `ch_addcat`, `brand`, `unit`, `weight`, `warr`, `undisc`, variations, and stock-by-options.

`cnt_save` is an operational counter write. Do not use it for normal SEO.

To create a hidden draft, `shop_write` `add`, then `shop_write` `hide` with `hide` `1` when the user asked not to publish.

### Product images

Do not send `image_base64` on the bind call. Do not POST multipart to `/shop/editgoods` or `/uapi`. Do not use `del_img1`, `del_img2`, and the rest of `del_imgN`.

| Action | Tool | Parameters |
|---|---|---|
| `img_add` | `shop_write` | `id`, `image_upload_ids` (comma-separated string). Several photos are one call |
| `img_edit` | `shop_write` | `id`, `img` (`0` = main), `confirm: true`, one `image_upload_id`. The slot must already exist |
| `img_del` | `shop_delete` | `id`, `confirm: true`. `img` is required unless `del_all=1`. Optional `files_sequence` when deleting one image |

`img_add` appends slots and leaves the main photo when the product already has one. Checked on product 85: sizes 295602, 320024, 290687; each `upload_direct` HTTP 200; reply «Изображения добавлены»; `num_photos` 11→14; new slots 11, 12, 13; main photo unchanged.

If the product has no photos, the first image of that `img_add` becomes the main photo. Checked 2026-10-19 on product 87 (betatest-0007, new product): three photos in one `img_add`, `num_photos` 3, first became the main photo, reply «Изображения добавлены».

The public product image URL is webp. `Content-Length` on that URL is the webp size, not the uploaded file size. Dimensions in the shop reply are the stored image.

Replacing or deleting an existing photo needs explicit approval. `del_all=1` needs its own confirmation.

### Variations and stock

| Action | Tool | Parameters |
|---|---|---|
| `variation_update` | `shop_write` | `id`, `variation_crc`; optional `art`, `stock`, `price`, `price_old`, `weight` |
| `variation_dict_save` | `shop_write` | optional `id`, `name`, `values` (comma-separated, for example `S,M,L`) |
| `variation_load` | `shop_write` | `id` |
| `variation_gen` | `shop_write` | `id`, `confirm: true`; `var_*` keys go in `fields` |
| `variation_edit_save` | `shop_write` | `id`; `var_*` keys go in `fields` |
| `variation_dict_delete` | `shop_delete` | `id`, `confirm: true` |
| `stock_genall` | `shop_write` | `id` |
| `stock_add` | `shop_write` | `id`, `opt_sel` |
| `stock_save` | `shop_write` | `id`; dynamic `stock_`, `art_`, and `crc_` keys go in `fields` |
| `stock_remove` | `shop_delete` | `id`, `ids`, `confirm: true` |
| `stock_remove_all` | `shop_delete` | `id`, `confirm: true` |

These operations are protected. Run them only when the user asked.

### Basket and checkout

| Action | Tool | Parameters |
|---|---|---|
| `basket_add` | `shop_write` | `id`; optional `cnt`, `opt`, `now` (`1` = buy now) |
| `basket_update` | `shop_write` | `id`; optional `cnt`, `opt`, `now` |
| `basket_delete` | `shop_delete` | `id` removes one line. No `id` clears the basket and then `confirm: true` is required |
| `checkout_update` | `shop_write` | `mode` is `recalc` or `change`. Extra keys such as `cnt_REC_ID`, `del_REC_ID`, and `field_N` go in `fields` |
| `checkout_submit` | `shop_write` | `confirm: true`; optional `payment_id`, `delivery_id`, `promo_code`, `custom_delivery_tax`; `field_N` in `fields` |

Use the basket and checkout for diagnosis. Do not submit a real order without explicit confirmation.

### Orders

Order group `hide`: `0` active, `1` archive, `2` trash.

| Status | Meaning |
|---:|---|
| `0` | New |
| `1` | Paying |
| `2` | Paid |
| `3` | Processing |
| `4` | Ready |
| `5` | Shipping |
| `6` | Delivered to pickup point |
| `7` | Delivered |
| `8` | Completed |
| `9` | Cancelled |
| `10` | Payment processing |
| `11` | Prepayment received |

| Action | Tool | Parameters |
|---|---|---|
| `invoice_hide` | `shop_write` | `ids` or `id`, `hide` |
| `invoice_courier` | `shop_write` | `group_courier`; `ids` or `id` |
| `invoice_status` | `shop_write` | `status`; `ids` or `id` |
| `invoice_payment` | `shop_write` | `payment_id`; order hash in `order` or `id` |
| `order_update` | `shop_write` | `order`; optional `status`, `note`; goods and `field_N` keys in `fields` |
| `order_add_goods` | `shop_write` | `order`; extra goods keys in `fields` |
| `order_delivery_tax` | `shop_write` | `order`, `custom_delivery_tax` |

Default for orders is read-only reporting. Status, courier, payment, delivery cost, items, fields, notes, and group changes need explicit approval.

### Currency, comments, balance, wishlist

| Action | Tool | Parameters |
|---|---|---|
| `set_currency_rate` | `shop_write` | `curr_code`, `curr_rate` (non-zero). Explicit approval required |
| `comments_add` | `shop_write` | `message`; product id in `entry_id` or `id`; optional `pros`, `cons`, `parent_id`, `user_id`, `subscribe_to_replies` |
| `comments_update` | `shop_write` | `id`; optional `message`, `pros`, `cons` |
| `comments_delete` | `shop_delete` | `id`, `confirm: true` |
| `balance_change` | `shop_write` | `user_id`, `amount` (non-zero signed change), `confirm: true`. A note can go in `fields.comment` |
| `balance_set` | `shop_write` | `user_id`, `amount` (new balance, zero or more), `confirm: true` |
| `wishlist_clear` | `shop_delete` | `confirm: true` |

Do not mix comment moderation or balance changes into an SEO edit.

## Templates and ordinary files

Templates stay on `templates_read`, `templates_write`, and `templates_delete`. `templates_delete`, including `restore_backup`, needs `confirm: true`.


File-manager files (not product photos) are a separate `files_write` `upload` with `path` and `upload_ids` (at most 20). Those keep the original bytes.

## Images — upload then bind

1. `files_write` action `upload_direct` with `filename` and exact `size` in bytes (`1`..`16777216`). Reply: `upload_url`, `upload_id`, `expires_in` 900. The file is not attached yet.
2. POST the raw file body to `upload_url`, `Content-Type: application/octet-stream`, body length equals `size`. That reply: `ok`, `upload_id`, `filename`, `size`, `sha256`.
3. Bind with the next shop call, as in the tables above.
