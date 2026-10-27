# Playbook — ready scenarios

Every scenario starts with `list_sites` → `select_site` → `modules_read active_mods`, and ends with re-read (`get`) + public-page check + report + `skills_tool register_usage`. Step-by-step calls for the two most common jobs are the recipes: material → MODULES.md, category → CATEGORIES_AND_ACCESS.md.

## 1. Publish one material

Follow the material recipe (MODULES.md). In short: required fields → category visible to guests → images uploaded → **one `add`** with texts + formats + meta + tags + `cover_upload_id` + `image_upload_ids` + `$IMG_URLn$`/`$IMAGEn$` in the text → `ownurl` via `update` only if a custom URL is wanted → verify with `get` and the public page. If meta doesn't render → Scenario 7.

## 2. Bulk publish from a list (CSV, doc, table)

1. Normalise rows: module, category (name → id), title, lead, body, tags, meta, images (cover + text images with slot numbers and alt).
2. Validate: required fields present, categories exist (or plan them), titles unique vs. `content_read list`.
3. Preview the first batch (≤20). If the request already contains the content and the order to publish, that's the approval — include the plan in the report.
4. Upload wave: all `upload_direct` calls in parallel, POST bytes, collect ids (MEDIA_WORKFLOW §5).
5. One `add` per row (in parallel), each with its cover id, its image ids and the matching variables in its text.
6. Report: row → id → URL → status; retry failed rows only.

## 2a. A series of materials (part 1…N, a festival, a course)

1. Facts first: dates, places, names, numbers for every part. Missing → ask; if you can't, neutral text + `pending` + list of statements that need facts (recaps especially).
2. Create the parts in order with `add_date` staggered by one minute (`2026-10-25 12:00`, `12:01`…) — identical timestamps scramble prev/next links. `add_date` works on `add`; for later updates of these parts keep the dates with the `fields` date keys if the module allows date editing (MODULES.md, "Dates").
3. Cross-links: the URLs exist only after `add`. Either link to the category/series page in every part (works in the first call), or, after all parts exist, one `update` per part from `original_message` adding «Читайте также» links — keep the image markers.
4. One style card for all covers and images of the series.

## 3. Create a category structure

Category recipe (CATEGORIES_AND_ACCESS.md) per category; create and fill back-to-back; offer menu links (PAGES_AND_MENUS.md).

## 4. Install a module and prepare it

1. `modules_write module_install`.
2. Show the demo categories/materials; ask: keep / rewrite / delete.
3. `settings_get` → propose settings → `dry_run` → apply. `fields` changes follow the defect procedure (SETTINGS_AND_MODULES.md).
4. SEO preflight (SEO_AND_URLS §1) and the template patches for the module's entry and category pages (TEMPLATES.md, find the rendering template first).
5. Categories, first materials, menu link.

## 5. SEO audit of a module (read-only first)

1. **Site level**: SEO module settings, `seo_meta`, `quarantine_status`, robots.txt, canonical (SEO_AND_URLS §1).
2. **Inventory**: `category_list`; `list detail=brief` all pages; `get` every material in scope (meta, URL, `original_message`, files, cover).
3. **Page types**: fetch one entry page, one category page and the module main page as a guest. For each: status, `<title>`, meta description, number of `<h1>`, category description visible, images visible, guest access.
4. **Rendering templates**: for each page type find the template by fingerprint (TEMPLATES.md §1). Note which lack Patch 1 / H1 / `$CAT_DESCR$`.
5. **Data issues** per object: empty/duplicate meta, meta lengths, ugly automatic URLs (`_quot_`, very long — candidates for `ownurl`, never `hgu_title` edits), titles with ASCII quotes, leads duplicating the first paragraph, demo boilerplate (`/panel/` links), images not referenced in the text, missing alt / `ulightbox`, tags.
6. **Classify** test leftovers (titles like «Тест…», created by an agent) vs. install demo content (stock texts with `/panel/` links) vs. real content. Leftovers: list only. Demo: offer rewrite or delete. Real: fix.
7. Report by priority (CHECKLIST.md) + a proposed fix plan.

## 6. Fix what the audit found

Order: manual `create_backup` → template patches (Patch 1, H1, `$CAT_DESCR$`) in the templates found by fingerprint → data (`update`/`category_update`: meta, leads, boilerplate) → verify every page type again → before/after table with backup ids.

Scope: fixing meta, leads, demo boilerplate is within "fix what was found"; changing titles of real materials, categories of materials, authors, dates, access is not — propose instead.

## 7. Make meta fields render (template patch)

1. Fetch a page whose object has meta: `<title>` = meta title? description present?
2. If not: find the rendering template (fingerprint), `read_template`, copy the exact `<title>` line, `validate_template` with Patch 1, `patch_template`, note `backup.id`.
3. Fetch again: with meta → SEO values; without meta → old title. No change → wrong template.

## 8. Clean URLs for important materials

1. SEO preflight; `seo_ChpuUrlType: true`.
2. Table current URL → `ownurl` (validator rules).
3. `update` with `ownurl` in waves of ≤20; HTTP-verify each URL: new 200 + old redirect.
4. Update internal links/menus that point to old URLs.

## 9. Rewrite / improve existing materials

1. `get` each; keep originals.
2. Edit from `original_message` in the same format; keep facts, links, embeds, `$IMAGEn$` and `<!--IMG_URLn-->` blocks.
3. Preview old → new per field.
4. `update` only changed fields; `uplastmod: 1` for substantial rewrites (not for typo or meta-only fixes); if the module has `options_show_adddate: true`, send the current publication date as `fields {ya, ma, da, ha, mia}` with every update (MODULES.md, "Dates").

## 10. Add covers to materials that have none

1. `list detail=full` → materials without `cover`.
2. Style card + images (IMAGE_GENERATION_GUIDE).
3. Upload wave; one `update cover_upload_id` per material (parallel). The cover is not inserted into the text.
4. Verify `cover` in replies and on lists/entry pages.

## 11. Add images inside existing materials

1. `get` → `files[]` and `original_message`.
2. No images yet → one `update` with `image_upload_ids` **and** the text with `$IMG_URLn$` figures inserted where they belong.
3. Images exist → explain the overwrite rule; after approval re-upload existing + new in the final order and re-send the text with `$IMG_URLn$` for every slot.
4. Verify on the page: all images visible, alt present, lightbox opens.

## 12. Board / FAQ / polls quick setups

- Board: `filter_list` → filter 3 empty? agree the types → `filter_update filter_num=3 items="…"` → ads with `ad_type`, `name`, `author_email`.
- FAQ: categories by topic → `add` with `subject`, `answer` (`message_type: html`).
- Polls: `question`, `answer1`, `answer2`, more in `fields: {answer3…}` → place `$POLLC_<id>$` in a template on request.

## 13. Moderation queue

`list pending=1` → show → on approval `update pending=0` or `delete` with `confirm: true`. Comments: `comments_all`, `comments_delete` with confirm.

## 13a. Hide / show a material

`get` → `update pending=1` (keep the date fields if `options_show_adddate: true`) → guest check of the direct URL and the lists → report (news: the link still opens — MODULES.md). Show it again with `update pending=0`.

## 14. Rollback

- Content: re-`update` with saved original values (an originally empty field can't be emptied again — report it).
- Category access: `category_update` with the original arrays.
- Settings: `settings_update` with the `before` values.
- Templates: reverse `patch_template`; `restore_backup` only with the user's acceptance (it reverts all templates).
- Deleted objects cannot be restored.
