# Copy and localize images

Use this workflow for covers, `fileN` attachments, page images, shop product/category images, HTML `<img>` sources, and template assets referenced by copied content.

## Inventory and choose an action

For each image record its source URL/file slot, role, dimensions, format, transparency, `alt`/`title`, where it is referenced, and whether visible embedded text exists.

Ask the user to choose per group:

- **copy unchanged** — default when no embedded text exists;
- **translate embedded text** — edit the original while preserving composition;
- **regenerate a localized variant** — create a new image inspired by the source and target context;
- **skip** — leave the target without this media or retain an existing target image.

Always show image previews before upload. Never overwrite or modify the source image/site.

## Download safely

Download from the authenticated source or public source URL to a temporary local file. Verify MIME type, dimensions, and file size. Do not execute embedded content. Preserve the original until target verification completes.

Avoid permanent hotlinks to the source site. Copy the binary to the target unless the user explicitly approves hotlinking.

## Translate text embedded in an image

1. Inspect the original visually and transcribe all visible text.
2. Separate translatable copy from logos, trademarks, product names, numbers, legal text, and technical labels.
3. Translate the approved text using the same terminology as surrounding content.
4. Use an available image-editing/generation tool with the original as a reference. Instruct it to change only the approved text, preserve layout, people/products, brand elements, colors, aspect ratio, and transparency, and render the exact translated wording legibly.
5. Inspect the output at full resolution. Verify spelling, missing/duplicated text, logo integrity, cropping, and small-text readability.
6. If exact text cannot be rendered reliably, do not upload it. Offer the unchanged original or a controlled regeneration after a new preview.

Do not use a generic raster script to synthesize or edit visual content when an image-editing tool is available. OCR or vision output is a draft; visually verify it.

## Regenerate a localized variant

Use regeneration only after explicit approval because it may change visual meaning. Preserve the subject, brand constraints, required products, aspect ratio, and intended placement. Avoid adding unrequested claims, badges, prices, logos, or culturally sensitive symbols. Show the result before upload and allow the user to choose between the original and generated variant.

If image editing/generation is unavailable or confidence is low, copy the original unchanged and report that embedded text remains untranslated unless the user chooses to stop instead.

## Upload using the correct target operation

`files_write` `upload_direct` is the intermediate step for every binary image. It does not attach the file.

1. `files_write(action="upload_direct", filename="<name.ext>", size=<exact byte length>)`. `size` is from 1 to 16777216.
2. The reply contains `upload_url` and `upload_id`. The reservation lasts 900 seconds.
3. POST the raw bytes to `upload_url` with `Content-Type: application/octet-stream`. The body length must equal `size`. That reply is `ok`, `upload_id`, `filename`, `size`.
4. Repeat for each file. The next call binds the ids. Do not send `*_base64` together with an upload id: base64 wins over `*_upload_id`, and a long base64 body is truncated. `*_path` is a path on the MCP server host and is not reachable for a remote agent.

Mass binding is one call with comma-separated ids:

- Material images: `content_write` `update` (or `add`) with `image_upload_ids`. Schema limit 50. The cover is separate, `cover_upload_id`, so a cover update does not drop the images already in `files`.
- Product photos: `shop_write(action="img_add", id=<product>, image_upload_ids="...")`. `img_add` appends and keeps the main photo when one already exists; on an empty product the first image of the batch becomes the main photo. A category image is `shop_write` `category_image` with one `image_upload_id` (jpg, png or gif).
- Static template assets and the file manager: `files_write(action="upload", path="<folder>", upload_ids="...")`, at most 20, then point the copied HTML/CSS at the new path. `upload_url` is a different action: one file from a public http(s) URL. Material and product images stay on `files_write` `upload_direct`.

Checked on blog entry 1: three images (194866, 167402, 180705 bytes), each `upload_direct` HTTP 200, one `update` with `image_upload_ids`. `files` held three images, all 1080×864. Cover afterwards, `cover_upload_id`, 259102 bytes: same cover URL, new bytes (1024×1024, Content-Length 259102), the three `files` stayed. The entry date became 2026-10-07 10:27 after the images and 2026-10-07 10:28 after the cover.

Checked on product 85: three photos (295602, 320024, 290687 bytes), one `img_add` with `image_upload_ids`. Reply «Изображения добавлены». `num_photos` 11 → 14, new slots 11, 12 and 13. The main photo did not change.

Checked on 2026-10-19 on betatest-0007, new records only.

Blog entry 120: three images (170, 178, 187 bytes), one `content_write` `update` with `image_upload_ids`. Stored sizes 40×30, 48×36, 56×42. Public URLs are `image/webp`, so Content-Length is the webp size, not the uploaded PNG size. Cover afterwards, `cover_upload_id`, 159 bytes, stored 32×32. The three files stayed on the same URLs. Publish date 2026-10-19 15:23 after the images and 2026-10-19 15:24 after the cover.

Product 87 had no photos. One `shop_write` `img_add` with three `image_upload_ids` (173, 172, 166 bytes). Reply «Изображения добавлены», `num_photos` 3. The first image became the main photo. This is different from product 85, which already had a main photo, so new slots were appended and the main photo stayed.

File manager: `files_write` `upload` with `upload_ids` into a folder keeps the original bytes (png 154 and 153, HTTP 200, Content-Length equal to the upload). A long folder name can be rejected with `FM_NAME_TOO_LONG`; use a short path.

`img_add` appends and keeps the main photo when one already exists; on an empty product the first image of the batch becomes the main photo.

A news file attachment is the `file_*` slot, not `image_upload_ids`. The reply shows it as `file_name`, `file_url` and `file_size`. If the file is not saved, `content_write` returns `FILE_NOT_SAVED` with `entry_saved: true` and the entry id: send the file again with `update` and that id, do not call `add` again. Pass `show_attach: 1` when the public page should show the image-attachment block. The file is stored only when that field is enabled (`fname` for news, in `content_read` `fields`).

`content_delete`, `shop_delete`, `templates_delete` and `files_delete` need `confirm: true` after the user approves the deletion. Translation does not delete objects unless the user asked for that deletion.

Preserve the source slot/order when supported. Use collision-safe target filenames, retain appropriate compression, and preserve dimensions/aspect ratio unless the target schema or user requires a change.

After upload, re-read the target object or `files_read` / `files_write` path (`info`/`list`) and verify the target URL, slot/order, dimensions, format, `alt`/`title`, and rendered reference. Record the source→target media mapping.
