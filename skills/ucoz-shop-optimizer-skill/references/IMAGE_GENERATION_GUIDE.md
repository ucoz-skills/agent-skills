# Product Image Generation Guide for uCoz Shop Optimizer

## Goal

This guide defines how the skill should generate product and category visuals safely.

## Supported image tasks

- product hero image on white background;
- additional gallery image;
- lifestyle image;
- category banner;
- placeholder for products without photos;
- consistent visual style for a batch of products.

## Default rules

1. Confirm the exact scope before image work: one product, selected products, one category, or selected categories.
2. Do not replace existing product images without explicit approval.
3. Do not claim a generated image is the exact real product if there is no trusted visual reference.
4. Do not invent logos, branded packaging or certification marks.
5. Keep text off the image unless the user explicitly requests text rendered in the image.
6. Prefer white background and clean ecommerce composition for main catalog images.
7. If the user asks for a unified style, keep lighting, camera angle and background consistent across the batch.
8. Always provide metadata: prompt, alt, filename and placement recommendation.
9. Upload and bind generated image files through documented Online Shop API image operations.
10. Do not use FTP upload or “manual Control Panel upload” as the normal product-image fallback.

## Input data to collect

- product name;
- category;
- short description;
- materials;
- color;
- size/form factor;
- brand constraints;
- known appearance references;
- desired style;
- output format.

## Output package per image

For each generated image, provide:

- image purpose;
- prompt;
- negative constraints or restrictions if needed;
- suggested filename;
- alt text;
- short caption/description;
- aspect ratio;
- placement recommendation.

## Safe fallback when appearance is unknown

If there is not enough information to produce a reliable exact-looking product image:

- generate a neutral commercial concept;
- say that it is a concept illustration or generic ecommerce visual;
- avoid showing non-existent controls, labels or package details.

## API upload and binding workflow

1. Generate the file locally.
2. For product images, use documented Online Shop API image operations such as `file_add_*`, replacement by image number/slot, and deletion through `img` / `del_all` when approved.
3. For category images, use `POST /shop/edit_cat?mode=img` with documented `cat_img` / `del` parameters.
4. Do not use `del_imgN` flags as a generic product-image mechanism.
5. If no confirmed product-image operation exists or the token lacks permission, report the API blocker. Do not use FTP upload or manual Control Panel upload as the main answer.

## Example prompt shapes

### White-background product image

“Create a clean ecommerce product image of [product], isolated on a white background, centered composition, realistic lighting, no text, no watermark, suitable for an online store.”

### Lifestyle image

“Create a lifestyle image featuring [product] in use, realistic setting matching the category, premium commercial look, no text overlay, focus on the product.”

### Category banner

“Create a wide category banner for [category], showing a curated set of representative products in a clean composition, modern ecommerce style, no text on image unless specified.”
