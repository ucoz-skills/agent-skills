# Media Workflow for Product and Category Images

This workflow describes how the skill generates, uploads and binds images through the Online Shop API. A separate `shop-openapi.yaml` is not used: required operations are documented in `references/API_REFERENCE.md` and `SKILL.md`.

## Principle

Generated images must be uploaded and bound through the uCoz Online Shop API.

FTP upload is not the workflow for product images. Manual Control Panel upload is not the normal fallback. If the API does not allow the operation with the current key, the agent must report the API blocker and must not substitute a manual instruction.

## Getting site and key

1. Connect to `ucoz-mcp`.
2. Read the site/domain and API key from the current MCP context.
3. Use the same key for direct shop API requests:

```http
Authorization: Bearer <API_KEY_FROM_UCOZ_MCP>
```

4. Base URL:

```text
https://{SITE_FROM_UCOZ_MCP}/uapi
```

Do not ask the user for the key again if MCP is already connected.

## Scope gate

Before generating or changing images, know the exact work scope:

- one product;
- selected products;
- one category;
- selected categories;
- products inside one chosen category.

Do not generate or upload images for all products by default.

## Product image workflow

1. Confirm product scope.
2. Fetch current product data via:

```http
GET /shop/request?page=viewgoods&id={id}
```

3. Determine the required action:
   - add a new image;
   - replace a specific image by number;
   - delete a specific image;
   - delete all images.
4. Generate or prepare the image file.
5. Execute the required API operation.

### Add product image

```http
POST /shop/editgoods/img_add
Content-Type: multipart/form-data
```

Fields:

- `id` — product ID;
- `file_add_cnt` — number of files;
- `file_add_1`, `file_add_2`, … — image files.

### Replace product image

```http
POST /shop/editgoods/img_edit
Content-Type: multipart/form-data
```

Fields:

- `id` — product ID;
- `img` — image number/index;
- new image file in field `file` or `file_<img>` according to API behavior for the selected image.

Replacement requires explicit confirmation and preview.

### Delete product image

```http
POST /shop/editgoods/img_del
Content-Type: application/x-www-form-urlencoded
```

Fields:

- `id` — product ID;
- `img` — image number to delete;
- `del_all=1` — delete all product images;
- `files_sequence` — image sequence when deleting one image, if required.

`del_all=1` requires separate explicit confirmation.

## Important product-image warning

Do not use `del_img1`, `del_img2`, … as the general product-image mechanism. Those flags belong to other modules with nested material images. For the shop, use dedicated operations:

- `/shop/editgoods/img_add`
- `/shop/editgoods/img_edit`
- `/shop/editgoods/img_del`

## Category image workflow

1. Confirm category scope.
2. Generate or prepare the image.
3. Use:

```http
POST /shop/edit_cat
Content-Type: application/x-www-form-urlencoded
```

Fields:

- `mode=img`;
- `id` — category ID;
- `cat_img` — path to the image file;
- `del=1` — delete category image, only if explicitly confirmed.

4. Re-read categories via `GET /shop/request?page=categories` if needed to confirm the result.

## Do not do this

- Do not use `ftp_tool.upload` as the primary product-image upload path.
- Do not embed product images in `html_dscr` as a gallery substitute unless the user asked for an illustration inside the description.
- Do not send the user to the Control Panel for manual upload as the main answer.
- Do not use `del_imgN` for shop products.
- Do not delete all images via `del_all` without explicit confirmation.
- Do not replace existing images without preview and confirmation.

## Required report fields

- object: product/category;
- object ID;
- image purpose;
- generated file;
- endpoint used;
- request fields used, e.g. `file_add_1`, `img`, `del_all`, `cat_img`;
- alt/filename, if applicable;
- outcome: added, replaced, deleted, preserved;
- API blockers, if any.
