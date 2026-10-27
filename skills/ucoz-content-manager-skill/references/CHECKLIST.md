# Content & SEO audit checklist

## Critical

- [ ] Category or material hidden from guests by accident (`access2category` without `999`, "Доступ запрещён").
- [ ] Materials or own URLs answering 404.
- [ ] `seo_DisableIndexing` on, site closed, or indexing quarantine — without the owner knowing.
- [ ] Broken HTML in materials (escaped `&lt;p&gt;`, unclosed tags breaking the layout).
- [ ] Template with broken `<?if?>` after an edit, or the global footer (`$GLOBAL_BFOOTER$`, which carries `$POWERED_BY$` in stock 2301 templates) removed.
- [ ] Indexing quarantine (`quarantine_status` `locked: true`) / robots.txt `Disallow: /` — nothing gets indexed.
- [ ] Material images bound but not referenced in the text and not printed by the template (invisible).

## High

- [ ] `meta_title` / `meta_description` empty on key materials and categories.
- [ ] Meta fields filled, but templates print `$TITLE_BAR$` / no `<meta name="description">`.
- [ ] SEO module off, `seo_UseUniqTitleDescKeys` or `seo_ChpuUrlType` off.
- [ ] Duplicate titles or near-duplicate materials.
- [ ] Empty or one-line bodies; leads duplicating the first paragraph.
- [ ] Categories without descriptions; empty categories in menus.
- [ ] No `h1` on entry pages, or several.
- [ ] Demo content from module installation still public, especially texts with `/panel/` links and "edit this news" instructions.
- [ ] Site-wide `seo_meta` (title/description in `settings_get site`) empty; module main pages titled just «Файлы», «Блог».
- [ ] Category description not printed on category pages (`$CAT_DESCR$` missing in the template).
- [ ] `seo_UseRelCanonical` on, but no `rel=canonical` in the HTML.

## Medium

- [ ] Important materials with ugly automatic URLs (`_quot_`, very long) — propose an `ownurl`; never edit `hgu_title`.
- [ ] Materials without covers on modules whose templates show covers.
- [ ] Images without alt (lightbox `$IMAGEn$` only) or without `class="ulightbox"` (no zoom on click).
- [ ] `$IMG_URLn$` places pointing at old files after images were replaced.
- [ ] No internal links between related materials.
- [ ] No Open Graph tags / no og:image.
- [ ] Tags missing or chaotic (near-duplicates).
- [ ] Board ads expired (`till_date` past) but still expected live.

## Low

- [ ] Typography (straight quotes, hyphen instead of dash).
- [ ] Old `mod_date` on evergreen materials that were not refreshed.
- [ ] JSON-LD absent (Article / FAQPage).
- [ ] Inconsistent cover style.

## Protected by default (never change without a request)

Category of a material, author, `add_date`, `pending`, `ontop`, `comments`, access arrays, filters, `hgu_title` (never sent) and `ownurl`, existing covers and images, `other1..5`, counters, module settings, templates, menus.
