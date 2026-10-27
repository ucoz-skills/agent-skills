# Image generation guide — covers, inline illustrations, category images

## Decide the source first

| Situation | Do |
|---|---|
| The user supplied images | Use them. Resize/convert only. |
| The user asks for (or allows) free stock photos (Unsplash and similar) | Take real photos with a checked licence and credit the author — «Stock photos» below. |
| You have an image-generation capability (built-in, a connected image MCP/tool, or an API the user configured) | Generate, following this guide. |
| No generator, but you can run code locally | Draw **graphic images** programmatically — covers, images in the text, album pictures (flat shapes/gradients with Pillow, SVG→PNG). Never fake photos; mark them «иллюстрация» in captions. |
| None of the above | Deliver prompts, alt texts and filenames; ask the user to provide files; bind them later. |

Say in the plan which path you take. No generator → skip the prompt recipes and go to "Programmatic images (no generator)" below. Do not stall the whole task on images: text can be published first, covers added with `update` later.

Plan the role of every image before making it: **cover** (one per material, shown by templates and in social previews) or **image in the text** (slot n, referenced as `$IMG_URLn$`/`$IMAGEn$` at a specific place in the body). They have different sizes and jobs — see MEDIA_WORKFLOW.md §1.

## Sizes

Read the module settings first (`settings_get` → `cover`, `images`). Defaults that work:

| Purpose | Size | Ratio | Format | Notes |
|---|---|---|---|---|
| Entry cover (also OG image) | 1200×630 | 1.91:1 | JPG q85 | Fits `cover.full_*` limits; good social preview. Lists crop it to `cover.thumb_*` (publ 320×240 = 4:3) — keep the subject in the central 4:3 area |
| Large hero cover | 1920×1080 | 16:9 | JPG q85 | Only if the template shows covers full width |
| Image in the text | 1200×800 | 3:2 | JPG/PNG | `$IMG_URLn$` shows the full image in the text — keep it ≤ module `images.full_width` (1500 default; larger gets downscaled) |
| Category image | 800×800 or 1200×630 | 1:1 / 1.91:1 | JPG/PNG | Thumb is 150×150 — keep the subject centred |
| Diagram / screenshot-like graphic | 1600×900 | 16:9 | PNG | Text must be legible |

Keep files under the module `max_size_kb` (6 MB default) — target 150–400 KB for photos. `upload_direct` accepts up to 16 MB.

## Style system for a batch

Before generating several images, fix one style card and reuse it in every prompt:

```text
Style card — <site/section name>
- Medium: editorial photo | flat illustration | 3D render | minimal gradient graphic
- Palette: <3–5 colours from the site CSS / logo>
- Light & mood: <soft daylight, warm, high-key…>
- Composition: subject centred, safe margins 10%, space for no text
- Camera (photo): 35mm, eye level, shallow depth of field
- Always: no text, no watermark, no logos, no brand marks
```

When the user wants on-brand images, take the palette from the live site: fetch the stylesheet linked in the page `<head>` over HTTP (the CSS template is too large to read through MCP — TEMPLATES.md §2).

## Prompt recipes

**Article cover (editorial photo):**
```text
Editorial photograph illustrating "<article topic>": <concrete scene with 1–2 subjects>, <setting>,
<light>, <palette>. Composition for a 1200x630 web cover, subject centred with margins. No text,
no watermark, no logos, realistic, high detail.
```

**News cover (neutral):**
```text
Neutral editorial image for a news item about <event type>, <symbolic but factual scene>,
no real identifiable people, no text, <palette>, 1200x630.
```

**Concept illustration:**
```text
Flat vector illustration explaining <idea>: <objects and their relation>, simple shapes,
<palette>, white background, generous spacing, no text, 1200x800.
```

**Category image:**
```text
Iconic image representing the category "<name>": <1 central object>, clean background in <colour>,
centred, readable at 150x150, no text, square 800x800.
```

**FAQ / help graphic:**
```text
Minimal illustration of <topic> for a help page, <palette>, friendly, no text, 1200x630.
```

## Hard rules

- No text inside generated images unless the user asks (models misspell; text also isn't indexable). Put words in the title and alt.
- No logos, trademarks, packaging, certificates, real people's faces or recognisable private persons. No fake screenshots of real products.
- News and factual pieces: illustrative, never a pretend photo of the real event. Mark such images as illustrations in the caption when it could mislead.
- Don't replace an existing cover or image without approval.
- One image per idea: no near-identical covers across a batch — vary scene, keep the style card.

## Output package per image

| Field | Example |
|---|---|
| Purpose | Cover of material blog #12 |
| Prompt | … |
| File | `kak-vybrat-noutbuk-cover.jpg`, 1200×630, 214 381 bytes |
| Alt | «Ноутбук на рабочем столе рядом с блокнотом и чашкой кофе» |
| Caption | «Иллюстрация: …» — required for generated or drawn images in the text. Covers have no caption: never make a photo-like generated cover for news or facts |
| Target | `blog` id 12, `cover_upload_id` |

## Stock photos (Unsplash and similar)

When the user asks for (or allows) real photos from a free stock:

- Find them through the stock site's own search pages (e.g. fetch `https://unsplash.com/s/photos/<query>`); don't scrape internal APIs.
- Licence: only photos under the Unsplash License. Skip Unsplash+ (`plus.unsplash.com`, ids starting with `premium_photo-`). Open each photo page to confirm.
- Download the file (`images.unsplash.com/…?w=1600&q=80`), crop and resize to the sizes above, save JPG q85, then upload as usual (MEDIA_WORKFLOW.md).
- Alt: describe what you see yourself — stock descriptions are often wrong (a «woman running» that shows a man).
- Credit in the caption: `Фото: <a href="https://unsplash.com/@<user>" target="_blank" rel="nofollow noopener">Имя</a> / <a href="https://unsplash.com/photos/<id>" target="_blank" rel="nofollow noopener">Unsplash</a>`. The cover has no caption — credit it as the last line of the text («Фото на обложке: …»).
- A stock photo is never presented as a photo of the real event or product.

## Programmatic images (no generator)

When only code is available, build clean graphic images — covers, images in the text, album pictures — not imitation photos:

- background: two-colour gradient from the site palette;
- one geometric motif (circle, blob, grid) varied per material;
- optional large emoji-free pictogram drawn with shapes;
- no text by default; if the user wants the title on the cover, render it with a real font, max 2 lines, 64–80 px, high contrast, margins ≥ 80 px.

Save as JPG q85, measure the exact byte size, then follow MEDIA_WORKFLOW.md (single or bulk upload).

## Alt text

- Describe what is visible and why it matters for the material, 5–15 words, in the site language.
- No "image of", no keyword lists, no file names.
- Decorative graphics in a figure with a caption: alt may repeat the essence of the caption briefly.
