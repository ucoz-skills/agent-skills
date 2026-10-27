# Media workflow — cover, images in the text, category images, files, bulk uploads

Uploads go through MCP: `files_write upload_direct` gives a one-time URL, you POST the raw bytes there (curl is fine — the tool itself asks for it), then a `content_write` call binds the `upload_id`. Never upload through the Control Panel, never send handwritten `/uapi` requests, never use scripts that bypass these MCP steps. Generating or resizing images locally with code is fine.

## 1. Cover and text images are different things

| | **Cover** (обложка материала) | **Images of the material** (изображения материала) |
|---|---|---|
| Parameter | `cover_upload_id` (one) | `image_upload_ids` (slots `file1..N`, ≤50 per call); `image_upload_id` = slot 1 |
| Stored in reply | `cover` {`full`, `small`, sizes} | `files[]` [{`full`, `small`, sizes}] in slot order |
| Where it shows | Templates only: `$COVER_IMAGE$`, `$COVER_URL$`, `$COVER_SMALL_URL$` (lists, entry header, Open Graph). Its `<img>` and alt come from the template (stock: no alt) — a template matter, not data | **Only where the text references them** — `$IMAGEn$` / `$IMG_URLn$` inside `message` — or where a template prints `$ATTACHMENTS$` / `$IMGS_ARRAY_JS$` (stock publ entry page prints none) |
| Format kept | as uploaded (jpg stays jpg) | converted to the site format (webp) |
| Module switch | `cover_enabled` setting | field `files` in `content_read fields` |

Uploading images to slots without referencing them in the text = images nobody sees. **Every time you bind `image_upload_ids`, put the matching variables into the text.**

## 2. Text variables for material images (verified)

| Variable in `message` | Renders as | Use for |
|---|---|---|
| `$IMAGEn$` | `<!--IMGn--><a href="FULL" class="ulightbox" rel="entGallery<id>"><img src="SMALL" loading="lazy"></a><!--IMGn-->` — thumbnail that opens the full image in the lightbox; **no alt** | Quick galleries, many images |
| `$IMG_URLn$` | The URL of the full image of slot n (absolute in the API, relative `/_bl/0/<n>.webp` on the page) | Your own markup with **alt, caption, sizes** |

There is no variable for the small (thumbnail) URL of slot n (`$IMG_SMALL_URLn$` etc. stay literal). Use `$IMAGEn$`, or the full image via `$IMG_URLn$`.

Standard markup for an SEO-friendly image that zooms on click (fancybox via `class="ulightbox"`; verified that both `<a class="ulightbox">` and `<img class="ulightbox">` open the lightbox):

```html
<figure>
  <a href="$IMG_URL1$" class="ulightbox"><img src="$IMG_URL1$" alt="Factual description" class="ulightbox" loading="lazy"></a>
  <figcaption>Caption</figcaption>
</figure>
```

Always put `class="ulightbox"` on images in materials (and the wrapping `<a href="$IMG_URLn$">` so it also works without JS).

**One call is enough**: send `image_upload_ids` and the `message` with `$IMG_URLn$` / `$IMAGEn$` in the same `add` or `update` — the variables resolve against the slots bound in that call (verified). Number n = position of the id in `image_upload_ids`.

How they are stored (verified): `$IMAGEn$` stays a live variable in `original_message`; `$IMG_URLn$` is resolved **at save time** into `<!--IMG_URLn-->https://…/file.webp<!--IMG_URLn-->`. Consequences:

- Replacing slot n later updates `$IMAGEn$` automatically but **not** the `$IMG_URLn$` places — they keep pointing at the old file. After replacing images, re-send the text with each `<!--IMG_URLn-->…<!--IMG_URLn-->` block replaced by `$IMG_URLn$` (resending the marker blocks unchanged does not refresh them — verified).
- When editing any text that contains images, start from `content_read get` → `original_message`, keep `$IMAGEn$` and the marker blocks (or turn them back into `$IMG_URLn$`), never strip them.
- The `add`/`update` reply still shows literal `$IMG_URLn$` in `original_message`; the marker blocks appear in the next `get` (check the reply's `message` or a fresh `get`).
- Text-only edits (no image change): re-send the marker blocks from `get` unchanged — they keep the current files; no `image_upload_ids` needed (verified).
- `files[].alt` / `title` exist in replies but can't be set through MCP — alt lives in your `<img alt>` markup.

`show_attach: 1` (news, blog) additionally prints all material images under the text as attachments; leave it as it was unless asked.

## 3. Other image targets

| Target | Parameter | Template variable | Stored as |
|---|---|---|---|
| Category image | `image_upload_id` on `category_add` / `category_update` | `$CAT_IMG$`, `$CAT_IMG_URL$`, `$CAT_IMG_SMALL_URL$` | `image.full_url` (`c<cid>.webp`) + 150×150 thumb |
| Photo module photo | `photo_upload_id` | photo templates | `photo_direct_url` (`/_ph/<album>/<n>.webp`), `thumb_small`, `thumb_mid` |
| Load file | `archive_upload_id` | download link | `archive_direct_url` (`/_ld/0/<id>_<name>`), original bytes |
| Screenshot / game logo | `screenshot_upload_id` | module templates | screenshot |
| File-manager file | `files_write upload` with `path` + `upload_ids` (≤20) | any URL | original bytes |

Before uploading, read the module settings (`settings_get`): `cover.full_*`, `cover.thumb_*`, `cover.max_size_kb`, `images.full_*`, `images.max_count`, `images.max_size_kb`, site `images.save_format`, `watermark`. MCP writes do **not** enforce `images.max_count` (6 were accepted with `max_count: 5`) — respect it yourself.

## 4. Single upload

1. `files_write upload_direct` with `filename` (latin, lower case, hyphens, real extension) and the exact `size` in bytes → `upload_url`, `upload_id`, `expires_in: 900`.
2. POST the raw bytes to `upload_url`, `Content-Type: application/octet-stream`, body length = `size` → `ok`, `upload_id`, `size`, `sha256`. Check `ok` and `size`.
3. Bind the id in the next `content_write` call. Unused ids expire after 15 minutes; an id is spent only by the call that consumes it.

## 5. Bulk upload (many images, many materials)

Plan a table first — the slot number decides the text variable:

| # | Local file | Bytes | Material | Role | Slot n | In text as | Alt | upload_id |
|---|---|---|---|---|---|---|---|---|
| 1 | naushniki-cover.jpg | 53 811 | publ #4 | cover | — | (template) | … | … |
| 2 | naushniki-1.jpg | 31 044 | publ #4 | image | 1 | `$IMG_URL1$` figure after h2 #2 | … | … |
| 3 | naushniki-2.jpg | 29 870 | publ #4 | image | 2 | `$IMG_URL2$` figure after h2 #4 | … | … |

Then:

1. **Prepare all files** and measure exact sizes before requesting URLs (the 900-second clock starts at `upload_direct`).
2. **Request upload URLs in parallel** — one `upload_direct` per file, all in one message. 20–30 files per wave.
3. **POST the bytes** for every URL. Any non-`ok`: new `upload_direct` for that file only.
4. **Bind and reference in one call per material**:
   - new material → `add` with `cover_upload_id`, `image_upload_ids` (in slot order) **and** `message` containing the matching `$IMG_URLn$` / `$IMAGEn$`;
   - existing material → `update` with the same trio (mind the overwrite rule in §6; start from `original_message`);
   - several materials → those calls in parallel;
   - category images → one `category_update` per category;
   - photo album → one `add` per photo with its `photo_upload_id` (parallel);
   - stuff → `screenshot_upload_id` + `image_upload_ids` in the same `add`;
   - files that belong in the file manager itself (a PDF to link, not content images) → one `files_write upload` with `path` + `upload_ids` (≤20).
5. **Verify**: reply `files[]` count and order, `cover`, and that the reply's `message` (not `original_message`) contains the resolved images; open the public page — every image visible, lightbox opens (the first click right after page load can be swallowed while scripts load; click again before declaring it broken).

Measured on a 4-news series: 5 MCP calls per material (3 `upload_direct`, `add`, `get`) + ~10 shared calls for the session.
6. Failed bind: the upload_id is still valid until it expires — retry the bind, not the upload.

## 6. The overwrite rule for gallery slots (verified)

`image_upload_ids` writes from `file1` upward and **replaces** existing images in those slots (entry with 2 images + 1 id → slot 1 replaced, slot 2 kept).

- Material without images: send the ids.
- Adding to a material with K images, keeping them: tell the user the existing images must be re-sent. After approval, download each `files[n].full`, upload them again with the new ones, send all ids in the final order, and re-send the text with `$IMG_URLn$` so the references match the new slots.
- Replacing image n: send ids for slots 1..n (1..n-1 re-uploads) **and** the text with `$IMG_URLn$` in the same `update`.
- A gallery never shrinks by sending fewer ids — the remaining slots stay.
- A replaced image gets a **new file name**; the old file stays on the server and keeps answering 200 at its URL. If the reason for replacing is legal/privacy, tell the user the old URL is still reachable (removing it is outside these tools).

### Recipe — replace the first image in the text of material 4 (verified, one call)

```text
content_read get module="news" id=4                     → original_message, files[]
files_write upload_direct filename="festival-programma-1b.jpg" size=<bytes>   → POST → <new id>
content_write update module="news" id=4
  image_upload_ids="<new id>"                            ← slot 1 replaced, slot 2 kept
  message_type="html"
  message="<original_message with every <!--IMG_URLn-->…<!--IMG_URLn--> block turned back into $IMG_URLn$,
           and the alt + figcaption of image 1 rewritten for the new picture>"
content_read get module="news" id=4                     → original_message points at the new file
GET the page                                             → new file shown, old file name absent, lightbox opens
```

A new picture needs a new alt and caption — never keep the old description.

## 7. The only allowed path for material and category images

**Covers, material images, category images, photos, screenshots, archives → `files_write upload_direct` → POST bytes → `*_upload_id` / `image_upload_ids`. Nothing else.**

Forbidden for materials and categories, even though the tool schema offers them:

| Path | Why it's forbidden |
|---|---|
| `*_base64` + `*_filename` | Bytes pass through the model context; long base64 arrives truncated and corrupts files |
| `*_site_path` (file from the file manager) | Leaves a duplicate in the file manager, mixes site files with content media, extra calls — `upload_direct` binds directly |
| `files_write upload` / `upload_url` and then `*_site_path` | Same — a detour through the file manager |
| `*_path` | MCP-host path, not reachable |

If the user only has an image URL: download it locally (rights permitting), then `upload_direct`. The file manager is for file-manager tasks only (§8).

## 8. File manager — only for its own tasks

Use `files_read` / `files_write` / `files_delete` only when the task is about site files themselves: a document to link from a page (PDF price list, rules), `robots.txt`-like text files, a folder cleanup, a file the user explicitly wants in the file manager. Never as a step of putting images into materials or categories.

Limits as reported by `files_read quota` (read them on the target site — they depend on the plan):

| Limit | Value seen |
|---|---|
| Folder name | ≤15 characters; depth ≤10; ≤1 000 files per folder |
| File name | ≤200 characters; allowed bytes `A-Z a-z 0-9 . _ / -` in paths, no leading slash |
| Files per `upload` call | 30 (tool: `upload_ids` ≤20) |
| `upload_direct` size | 1..16 777 216 bytes |
| Reserved root names | module folders (`blog`, `news`, `publ`, `shop`, `_bl`, `_nw`, `_pu`…) — don't create them |
| Forbidden extensions | `php`, `cgi`, `asp`, `jsp`… |
| Text files readable/writable | `css`, `js`, `json`, `txt`, `html`, `xml`, `svg`, `md`, `csv`, `yaml` (`content_get`/`content_put`, ≤300 000 chars) |

Upload a file into the file manager (verified, bytes kept — sha256 identical): `upload_direct` per file → POST → `files_write upload path="docs" upload_ids=[…]` → reply `results[].entity.url`. Deleting (`files_delete`, `confirm: true`) removes a folder with its contents. On some servers the file-manager API is unavailable (`NOT_SUPPORTED_METHOD`, seen on s500) — report it.

## 9. Rules

- Cover and text images are planned separately: a cover never substitutes for images in the text, and vice versa.
- Every bound material image is referenced in the text (or the user agreed to a template gallery).
- Every `<img>` in a material: `alt`, `class="ulightbox"`, `loading="lazy"`; wrapped in `<a href="$IMG_URLn$" class="ulightbox">`.
- Do not replace a cover or image without approval; show current → new.
- Only images the user has rights to; generated ones are fine (IMAGE_GENERATION_GUIDE.md).
- File names `<topic>-<n>.jpg`, latin, no spaces. JPG for photos, PNG for flat graphics.
