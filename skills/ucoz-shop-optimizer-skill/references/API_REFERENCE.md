# uCoz Shop Optimizer — Built-in Online Shop API Reference

This file replaces a separate OpenAPI schema. The agent must know the capabilities listed below and use them directly without searching for `shop-openapi.yaml`.

## Site connection and authorization

1. First connect to `ucoz-mcp` and read from its current context:
   - the site/domain MCP is working with;
   - the API key/token MCP already uses for that site.
2. Do not ask the user to manually paste an API key if `ucoz-mcp` is already connected.
3. Do not expose the API key in responses, logs, plans or reports.
4. For direct shop API requests, use the same site and the same API key:

```http
Authorization: Bearer <API_KEY_FROM_UCOZ_MCP>
```

Base URL:

```text
https://{SITE_FROM_UCOZ_MCP}/uapi
```

If MCP returns a full URL, normalize it to the site host and append `/uapi`. If the API returns a permission error, do not ask for the key again — report that the current key/user lacks rights for the operation.

## General request rules

- `GET` methods pass parameters in the query string.
- Most shop `POST`/`PUT` methods use `application/x-www-form-urlencoded`.
- Product images use `multipart/form-data`.
- Do not use FTP for product image upload. The shop has dedicated image API operations.
- Do not use `del_img1`, `del_img2`, … as the general product-image deletion mechanism. For the shop, use `img` and `del_all` in dedicated image operations.

## Categories

### Get categories

```http
GET /shop/request?page=categories
```

Parameters:

| Parameter | Required | Description |
|---|---:|---|
| `page=categories` | yes | fixed request type |
| `parent_id` | no | parent category ID |
| `get_globals` | no | `0/1`, get shop global categories |

Use to build the category tree, select scope, find categories without descriptions and check structure.

### Add / edit / show / hide category / manage category image

```http
POST /shop/edit_cat
Content-Type: application/x-www-form-urlencoded
```

Main parameter — `mode`:

| `mode` | Action |
|---|---|
| `add` | add category |
| `edit` | edit category |
| `show` | show category |
| `hide` | hide category |
| `img` | add/replace/delete category image |

Fields:

| Field | Purpose |
|---|---|
| `id` | category ID, required for `edit`, `img`, `show`, `hide` |
| `name` | category name, required for `add` |
| `pid` | parent category ID |
| `global_cat` | global product group category ID |
| `dscr` | category description |
| `meta_title` | category page HTML title |
| `meta_dscr` | category page meta description |
| `url` | category SEO-friendly URL |
| `pos` | category position |
| `cat_img` | image file path for `mode=img` |
| `view_groups` | groups allowed to view the category |
| `add_groups` | groups allowed to add materials |
| `exclude_from_discount` | exclude category from discounts |
| `del` | delete category image for `mode=img`; ignored if `cat_img` is passed |

Protected fields by default: visibility, access groups, parent category, discounts and category image.

## Products

### Get all products list

```http
GET /shop/request?page=allgoods
```

Actual URL:

```text
/uapi/shop/request?page=allgoods
```

Parameters:

| Parameter | Description |
|---|---|
| `pnum` | page number, default `1` |
| `sort` | sort field: `id`, `art`, `brand`, `name`, `price`, `warr`, `weight`, `added_time`, `modified_time`, `num_com`, `rating`, `ordered`, `views`, `solds`, `other#` |
| `order` | `asc` or `desc` |
| `f_name` | name filter, Base64 value |
| `f_brand` | brand filter, Base64 |
| `f_art` | SKU/article filter, Base64 |
| `f_price` | price filter, Base64, format `MIN|MAX|CURRENCY_CODE` |
| `f_stock` | stock filter, Base64 |
| `f_status` | status filter, Base64 |
| `f_warr` | warranty filter, Base64 |
| `f_weight` | weight filter, Base64 |

Use this method only after scope is selected. Do not start optimizing all products by default.

### Get product card

```http
GET /shop/request?page=viewgoods&id={id}
```

Parameters:

| Parameter | Required | Description |
|---|---:|---|
| `page=viewgoods` | yes | fixed request type |
| `id` | yes | product ID |
| `cnt` | no | quantity for total price calculation |
| `goods_id` | no | product ID for wishlist |
| `clear=1` | no | clear wishlist |

Use before editing a product, replacing an image or preparing preview.

### Add product

```http
POST /shop/addgoods
Content-Type: application/x-www-form-urlencoded
```

Required fields:

| Field | Value |
|---|---|
| `method` | `submit` |
| `cat_id` | main category ID |
| `name` | product name |

Additional fields:

| Field | Description | Protection |
|---|---|---|
| `cats_add` | additional categories, format `[ID1, ID2, ID3,...]` | change only on request |
| `ch_addcat` | `0/1`, required when `cats_add` is set | change only on request |
| `brief` | short description | safe text field |
| `dscr` | full description | safe text field |
| `html_dscr` | `0/1`, allow HTML in `dscr` | confirm if HTML mode changes |
| `type` | `0` physical, `1` file, `2` electronic code, `3` service | protected |
| `price` | price | protected |
| `price_in` | purchase price | protected |
| `price_old` | old price | protected |
| `stock` | stock or expected date | protected |
| `art` | SKU/article | protected |
| `hide` | `0/1`, whether product is hidden | protected |

For draft product creation, suggest `hide=1` by default unless the user asks to publish immediately.

### Edit product

```http
POST /shop/editgoods
Content-Type: application/x-www-form-urlencoded
```

Required fields:

| Field | Value |
|---|---|
| `method` | `submit` |
| `id` | product ID to edit |
| `cat_id` | product category ID |
| `name` | product name |

Edit fields:

| Field | Description | Protection |
|---|---|---|
| `brand` | manufacturer | change only with confirmation |
| `brief` | short description | safe text field |
| `dscr` | full description | safe text field |
| `undisc` | exclude product from discounts | protected |
| `price` | price | protected |
| `price_in` | purchase price | protected |
| `price_old` | old price | protected |
| `unit` | unit of measure | change only with confirmation |
| `weight` | weight | protected |
| `art` | SKU/article | protected |
| `stock` | stock/expected date | protected |
| `warr` | warranty | protected |
| `type` | product type: `0/1/2/3` | protected |
| `tags` | comma-separated tags | may change per task |
| `url` | product SEO-friendly URL | protected, change only after preview |
| `meta_title` | product HTML title | safe after preview |
| `meta_dscr` | product meta description | safe after preview |
| `cats_add` | additional categories | protected |
| `ch_addcat` | additional categories flag | protected |
| `hide` | hide/show product | protected |

Before editing, always read the current card via `viewgoods` so required fields are not overwritten.

### Delete product

```http
POST /shop/editgoods/delete
Content-Type: application/x-www-form-urlencoded
```

Fields:

| Field | Value |
|---|---|
| `method` | delete mode |
| `id` | product ID |

Deletion is irreversible. Perform only after explicit confirmation with the product ID.

### Hide / show product

```http
POST /shop/editgoods/hide
Content-Type: application/x-www-form-urlencoded
```

Fields:

| Field | Description |
|---|---|
| `id` | product ID |
| `hide` | `1` hide, `0` show; if omitted — toggle current state |

Changing product visibility is a protected operation.

### Save product counters

```http
POST /shop/editgoods/cnt_save
Content-Type: application/x-www-form-urlencoded
```

Fields: `id`, `views`, `solds`, `ordered`, `rate_num`, `rate_sum`, `stock_q`.

This is an operational/statistics operation. Do not use it for normal SEO optimization.

## Product images

### Add product images

```http
POST /shop/editgoods/img_add
Content-Type: multipart/form-data
```

Fields:

| Field | Required | Description |
|---|---:|---|
| `id` | yes | product ID |
| `file_add_cnt` | no | number of uploaded files |
| `file_add_1` | yes for first file | image file 1 |
| `file_add_2` | no | image file 2 |
| `file_add_N` | no | subsequent images by number |

Rule: adding images is safer than replacing, but still requires preview if the product already has images.

### Replace product image

```http
POST /shop/editgoods/img_edit
Content-Type: multipart/form-data
```

Fields:

| Field | Required | Description |
|---|---:|---|
| `id` | yes | product ID |
| `img` | yes | image number/index to replace |
| `file` or `file_<img>` | yes | new image file; field name must match API behavior for the selected `img` |

Replacing an existing image requires explicit confirmation with the image number.

### Delete product image

```http
POST /shop/editgoods/img_del
Content-Type: application/x-www-form-urlencoded
```

Fields:

| Field | Required | Description |
|---|---:|---|
| `id` | yes | product ID |
| `img` | no | image number to delete |
| `del_all` | no | delete all product images |
| `files_sequence` | no | image sequence when deleting one image in edit mode |

Deleting one image requires confirmation. `del_all=1` requires separate explicit confirmation and listing of the affected product.

## Product variations

### Get variation

```http
GET /shop/editgoods/variation?id={id}&variation_crc={variation_crc}
```

### Update variation

```http
PUT /shop/editgoods/variation
Content-Type: application/x-www-form-urlencoded
```

Fields: `id`, `variation_crc`, `art`, `stock`, `price`, `price_old`, `weight`.

Variations are protected by default because they affect prices, stock, weight and SKU/article.

### List variations

```http
GET /shop/editgoods/variations?id={id}
```

## Stock by options

| Method | Purpose |
|---|---|
| `GET /shop/editgoods/stock_get?id={id}` | get stock option sets |
| `GET /shop/editgoods/stock_get_genall?id={id}` | generate all stock option sets |
| `POST /shop/editgoods/stock_get_add` | add option set, fields `id`, `opt_sel` |
| `POST /shop/editgoods/stock_get_save` | save stock/SKU by sets, field `id`, dynamic `stock_<id>`, `art_<id>`, `crc_<id>` |
| `POST /shop/editgoods/stock_get_removeselected` | remove selected sets, fields `id`, `ids` |
| `POST /shop/editgoods/stock_get_removeall` | remove all sets, field `id` |

All stock operations are protected and run only on explicit request.

## Cart

| Method | Action | Fields/parameters |
|---|---|---|
| `GET /shop/basket/` | get cart | `user_id` optional, admin only |
| `POST /shop/basket/` | add item | form: `mode`, `id`, `cnt`, `opt`, `now` |
| `PUT /shop/basket/` | update quantity | form: `mode`, `id`, `cnt`, `opt`, `now` |
| `DELETE /shop/basket/` | remove item or clear cart | form: `id`; if `id` is omitted, cart is cleared |

Use cart operations mainly for diagnostics. Clearing the cart — only on explicit request.

## Checkout

| Method | Action | Fields/parameters |
|---|---|---|
| `GET /shop/checkout/` | get checkout data | query: `promo_code`, `custom_delivery_tax` |
| `PUT /shop/checkout/` | recalc/edit pending order | form: `mode`, `cnt_REC_ID`, `del_REC_ID` |
| `POST /shop/checkout/` | submit order | form: `payment_id`, `delivery_id`, `promo_code`, `custom_delivery_tax`, `field_N` |

Do not submit a real order without explicit confirmation and test conditions.

## Orders

### Order list

```http
GET /shop/invoices/
```

Filters:

| Parameter | Description |
|---|---|
| `page` | page number |
| `user` | user ID, admin filter |
| `hide` | order group: `0` active, `1` archive, `2` trash |
| `status` | order status |
| `inv_sum` | sum range `sum1|sum2` |
| `courier` | courier |
| `cdate` | delivery period `date1|date2` |
| `payment` | payment method ID |
| `delivery` | delivery method ID |
| `id` | order ID(s), comma-separated |
| `date` | created date range `date1|date2` |

Statuses:

| ID | Status |
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

### Group, courier, status, payment

| Method | Action | Fields |
|---|---|---|
| `PUT /shop/invoices/hide` | assign order group | `ids`, `hide` |
| `PUT /shop/invoices/setCourier` | assign courier | `ids`, `group_courier` |
| `PUT /shop/invoices/status` | set status | `ids`, `status` |
| `PUT /shop/invoices/change` | change payment method and restart payment | `order`, `payment_id` |

All these operations are protected and require explicit confirmation.

### Order by hash and order changes

| Method | Action |
|---|---|
| `GET /shop/order/?order={hash}` | get order by hash |
| `POST /shop/order/` | add product to existing order |
| `PUT /shop/order/` | change items, fields, status or note |
| `PUT /shop/order/delivery_tax` | change order delivery cost |

`PUT /shop/order/` uses fields: `order`, `mode`, `cnt_REC_ID`, `del_REC_ID`, `field_N`, `status`, `note`. `mode` values: `goods`, `fields`, `status`, `note`.

Changing an order is a protected operation.

## Currencies

### Get currencies

```http
GET /shop/getshopdata/?page=currencies_list
```

### Update currency rate

```http
POST /shop/setcurrrate
Content-Type: application/x-www-form-urlencoded
```

Fields:

| Field | Description |
|---|---|
| `curr_code` | currency code from shop settings |
| `curr_rate` | non-zero currency rate |

Currency changes — only with explicit confirmation.

## Product comments

| Method | Action | Fields/parameters |
|---|---|---|
| `GET /shop/comments?id={id}` | get material comments | `id` |
| `POST /shop/comments` | add comment | `entry_id`, `message`, `pros`, `cons`, `parent_id`, `user_id`, `subscribe_to_replies` |
| `PUT /shop/comments?id={comment_id}` | edit comment | `message`, `pros`, `cons` |
| `DELETE /shop/comments?id={comment_id}` | delete comment | comment `id` |

Comment moderation is a separate operation; do not mix it with product SEO optimization.
