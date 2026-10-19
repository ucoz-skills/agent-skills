# Media workflow for product and category images

This workflow is MCP-only. Generate or prepare the file, upload the bytes, then bind them with a shop tool. Action details are in `references/API_REFERENCE.md` and `SKILL.md`. Do not look for `shop-openapi.yaml`.

Do not call shop HTTP routes. Do not take a uAPI key. The current MCP does not provide one.

## Principle

Product and category images are bound with `shop_write` and removed with `shop_delete` after `files_write` `upload_direct`.

A manual Control Panel upload is not the normal fallback. If a shop tool cannot do the operation, report that blocker. Do not switch the task to handwritten HTTP.

Do not invent trademarks, logos, branded packaging, or certificates. Write alt text from facts you actually have. Do not replace an existing product photo, category image, or banner without explicit approval.

## Select the site

1. `list_sites`.
2. `select_site(site_id)`.

Shop tools use the selected site.

## Upload, then bind

1. `files_write` action `upload_direct` with `filename` and the exact `size` in bytes (`1`..`16777216`). Reply: `upload_url`, `upload_id`, `expires_in` 900. The file is not attached yet.
2. POST the raw file body to `upload_url`, `Content-Type: application/octet-stream`, body length equals `size`. That reply: `ok`, `upload_id`, `filename`, `size`, `sha256`.
3. Bind with the next call. Several product photos: one `shop_write` `img_add` with `image_upload_ids` (comma-separated string). Do not send `image_base64` in that call. Do not POST multipart to `/shop/editgoods` or `/uapi`.
4. If the product already has a main photo, `img_add` appends slots and leaves the main photo. Checked on product 85: sizes 295602, 320024, 290687; each `upload_direct` HTTP 200; reply «Изображения добавлены»; `num_photos` 11→14; new slots 11, 12, 13; main photo unchanged.
5. If the product has no photos, the first image of that `img_add` becomes the main photo. Checked 2026-10-19 on product 87 (betatest-0007, new product): three photos in one `img_add`, `num_photos` 3, first became the main photo, reply «Изображения добавлены».
6. Replacing an existing slot is `shop_write` `img_edit`, parameter `img` (`0` = main), `confirm: true`, and one `image_upload_id`. Deleting is `shop_delete` `img_del`, `confirm: true`; `img` is required unless `del_all=1`.
7. Category image: `shop_write` `category_image` with one `image_upload_id`. The file must be jpg, png, or gif (`.jpeg` is renamed to jpg). Removing it is `shop_delete` `category_image_delete` with `confirm: true`.
8. The public product image URL is webp. `Content-Length` on that URL is the webp size, not the uploaded file size. Dimensions in the shop reply are the stored image.
9. File-manager files (not product photos) are a separate `files_write` `upload` with `path` and `upload_ids` (at most 20). Those keep the original bytes.

## Scope gate

Before generating or changing images, know the exact scope:

- one product;
- selected products;
- one category;
- selected categories;
- products inside one chosen category.

Do not generate or upload images for every product by default.

## Product image workflow

1. Confirm the product scope.
2. Read the current card with `shop_read` `get` (`id`).
3. Decide what to do:
   - add a new image;
   - replace one existing slot;
   - delete one image;
   - delete all images.
4. Generate or prepare the file. Keep a factual alt text and a filename. If the real appearance is unknown, make a clearly labeled concept illustration and do not invent a trademark.
5. Run the upload steps above, then the matching bind or delete.

### Add

`shop_write` `img_add` with `id` and `image_upload_ids`. One call can carry several ids.

If a main photo already exists, new slots are appended and that main photo stays. If the product has no photos, the first image of this call becomes the main photo. Adding is still not a silent replacement of a photo the user wanted to keep: when photos already exist, show the plan and do not replace a slot unless the user approved `img_edit`.

### Replace

`shop_write` `img_edit` with `id`, `img` (`0` = main), `confirm: true`, and one `image_upload_id`.

Replacement needs an explicit confirmation and a preview. Do not replace without approval.

### Delete

`shop_delete` `img_del` with `id` and `confirm: true`.

- `img` is the slot to delete (`0` = main) and is required unless `del_all=1`;
- `del_all=1` deletes every product image and needs its own explicit confirmation;
- `files_sequence` is optional when deleting a single image.

Do not use `del_img1`, `del_img2`, or other `del_imgN` flags. Those belong to other modules. Shop images use `img_add`, `img_edit`, and `img_del`.

## Category image workflow

1. Confirm the category scope.
2. Generate or prepare the image. Same rules: factual alt text, no invented trademarks, no replacement without approval.
3. `files_write` `upload_direct`, POST the raw body, then `shop_write` `category_image` with `id` and one `image_upload_id`.
4. The file must be jpg, png, or gif. `.jpeg` is renamed to jpg.
5. To remove the category image, `shop_delete` `category_image_delete` with `id` and `confirm: true`. There is no category-delete action.
6. Re-read with `shop_read` `category_list` when you need to confirm the result.

`category_image` replaces the current category image. Do not call it unless the user approved that replacement.

## Do not do this

- Do not send `image_base64` in the `img_add` or `category_image` call that binds `upload_id` values.
- Do not POST multipart to `/shop/editgoods` or `/uapi`.
- Do not put product images into `html_dscr` as a substitute for the product gallery unless the user asked for an illustration inside the description.
- Do not send the user to the Control Panel for a manual upload as the main answer.
- Do not use `del_imgN` for shop products.
- Do not delete every image with `del_all` without explicit confirmation.
- Do not replace existing images without a preview and approval.
- Do not invent logos, packaging artwork, certificates, or trademarks in the picture or in the alt text.

## Report fields

- object: product or category;
- object id;
- image purpose;
- generated file and exact byte size;
- `upload_id` values;
- action used: `img_add`, `img_edit`, `img_del`, `category_image`, or `category_image_delete`;
- parameters, for example `image_upload_ids`, `img`, `del_all`, `image_upload_id`;
- alt text and filename when they apply;
- what happened: added, replaced, deleted, or left as it was;
- whether the main photo changed;
- blockers, if any.
