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

Read the current target OpenAPI schema before uploading:

- common content modules: `cover` and enabled `file1`…`fileN` slots via `multipart/form-data`;
- Page Editor pages: enabled `fileN` slots via `multipart/form-data`;
- shop products/categories: dedicated shop image add/edit operations, not generic `fileN` assumptions;
- template/global-block static assets: target FTP upload, then patch the copied HTML/CSS to the new target path.

Preserve the source slot/order when supported. Use collision-safe target filenames, retain appropriate compression, and preserve dimensions/aspect ratio unless the target schema or user requires a change.

After upload, re-read the target object or FTP path and verify the target URL, slot/order, dimensions, format, `alt`/`title`, and rendered reference. Record the source→target media mapping.
