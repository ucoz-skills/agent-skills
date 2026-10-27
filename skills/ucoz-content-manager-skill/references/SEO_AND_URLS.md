# SEO and URLs

SEO on uCoz has three layers. All three must line up, or the work is invisible:

1. **SEO module settings** — switch the features on.
2. **Data** — meta fields and own URLs (`ownurl`) on materials and categories.
3. **Templates** — print the data into `<head>` (see TEMPLATES.md).

## 1. SEO module: always on, URL settings always on

Run this preflight before any SEO or URL work, and fix what is off:

```text
modules_read active_mods                     → "seo" present?
  no → modules_write module_install module_code="seo"
settings_read settings_get module="seo"
settings_read settings_get module="site"     → human_friendly_urls
```

Target state:

| Module | Key | Value | Why |
|---|---|---|---|
| `seo` | `seo_UseUniqTitleDescKeys` | `true` | Per-material/category `meta_title`, `meta_description`, `meta_keywords` (feed `$SEO_TITLE$`, `$SEO_DESCRIPTION$`, `$SEO_KEYWORDS$`) |
| `seo` | `seo_ChpuUrlType` | `true` | "URL модулей, категорий и материалов задаются вручную" — enables `ownurl` (the platform mirrors it to `use_ownurl`) |
| `seo` | `seo_UseRelCanonical` | `true` | `rel=canonical` on pages |
| `seo` | `seo_EnableDisableRobotxTxt` | `true` | Managed robots.txt |
| `seo` | `seo_DisableIndexing` | `false` | **Never** set to true without an explicit request (needs `confirm`) |
| `site` | `human_friendly_urls` | `true` | Readable automatic slugs in system URLs |

Apply with a dry run first:

```text
settings_write settings_update module="seo" dry_run=true values={"seo_ChpuUrlType":true,"seo_UseUniqTitleDescKeys":true,"seo_UseRelCanonical":true}
settings_write settings_update module="seo" values={...only the keys that are off...}
```

Report `changed` (before → after). An entry reply with `"use_ownurl":"yes"` confirms own URLs are on.

### Site-level indexing (check in every SEO task)

| Check | How | Meaning |
|---|---|---|
| Quarantine | `modules_read quarantine_status` → `locked: true` | The site is held out of search engines (seen together with robots.txt `Disallow: /`). Nothing you do on pages gets indexed until it's lifted |
| robots.txt | GET `https://<host>/robots.txt` | `Disallow: /` = closed; managed by the SEO module when `seo_EnableDisableRobotxTxt` is on |
| Indexing switch | `settings_get seo` → `seo_DisableIndexing` | Must be `false` |
| Site-wide meta | `settings_get site` → `seo_meta` | Empty title/description = weak home and fallback snippets |
| Canonical | `seo_UseRelCanonical: true` + look for `<link rel="canonical">` in the HTML | Stock templates (2301) output none — report, don't hand-write one |

Report all five. Lifting quarantine (`quarantine_unlock`, `confirm: true`) is the owner's decision.

## 2. Meta fields

| Field | Entry / category parameter | Limit (CP validator) | Target |
|---|---|---|---|
| Title | `meta_title` | 150 chars | 50–60 chars, unique, main keyword first, brand at the end only if the template doesn't add it |
| Description | `meta_description` | 250 chars | 140–160 chars, unique, a reason to click |
| Keywords | `meta_keywords` | 1024 chars | Optional, 3–8 phrases "a, b, c" |

- Not echoed by `add` — confirm with `get`.
- `$SEO_TITLE$` prints `meta_title` as is (verified: `<title>SEO заголовок демо-статьи</title>`), so write the full title you want in the tab.
- Module-level title/description (the module main page) are module SEO settings in the Control Panel (`GlobalTitle`, `GlobalDescription`). If they are not exposed by `settings_get` for the module, say so and leave them.
- Site-wide defaults: `settings_get site` → `seo_meta` (`title`, `description`, `keywords`).

## 3. System URLs — automatic, never touched

The system URL of a material or category (`/blog/<auto-slug>/2026-10-25-6`, `/publ/<slug>/…`) is built by the platform from the title (`hgu_title` in replies). **Don't send `hgu_title`** in `add`, `update`, `category_add` or `category_update`. Changing a title later keeps the existing URL. If a URL has to look a certain way, that's `ownurl` (§4).

## 4. Own URLs (`ownurl`) — the only way to set a URL

`ownurl` is the SEO-module feature that gives a material or a category an address without the module path: `https://site/<ownurl>`.

Format (platform validator `ownurl_is_valid`):

- characters `a-z 0-9 - _ /` only, lower case;
- no leading or trailing `/`; ≤255 chars (materials ≤227);
- must not start with a system path: `index, uindex, board, blog, dir, faq, forum, gb, load, news, photo, publ, shop, stuff, tests, video, panel, admin, uapi, api, search, mail, main, informer, widget, register, uid, secure, stat, tmpls, poll, subscriptions, upay, …` and must not equal a file-manager folder;
- unique across the site.

Procedure:

1. Preflight from section 1 (SEO module on, `seo_ChpuUrlType: true`).
2. Propose URLs in a table (current URL → new URL). Changing URLs of indexed pages affects search traffic; get approval.
3. `update` (or `category_update`) with `ownurl`, ≤20 objects per wave. The reply shows `ownurl` and `entry_url` / `category.url` with the new address.
4. Verify over HTTP:

   ```text
   curl -s -o /dev/null -w "%{http_code} %{url_effective}\n" -L https://site/<ownurl>      → 200, title is the material's
   curl -s -o /dev/null -w "%{http_code} %{url_effective}\n" -L https://site/<old system url> → ends at /<ownurl>
   ```


An `ownurl` cannot be removed through MCP — an empty value is not sent — so agree on the URLs before writing.

Verified: `update ownurl="proverka-ownurl-mcp"` on a blog entry → reply `entry_url` = `https://<host>/proverka-ownurl-mcp`; the new URL answers 200; both old URLs (`/blog/<slug>/<date>-<id>` and `/blog/<date>-<id>`) answer **301** to it. Deleting the entry frees the URL (404 afterwards).

On modules with `options_show_adddate: true`, the `update` that sets `ownurl` also resets the publication date unless you send the date fields (MODULES.md, "Dates").

Module-level own URL (e.g. `/articles` instead of `/publ`) is a module SEO setting (`module_url`) of the Control Panel; change it only on request and only if exposed by the tools.

## 5. Verifying SEO on the public page

For every changed object, fetch the public URL as a guest and check:

```text
curl -sL <url> | grep -iE "<title>|name=\"description\"|rel=\"canonical\"|property=\"og:"
```

- `<title>` = `meta_title` (if set) — otherwise the template still prints `$TITLE_BAR$`/`$ENTRY_TITLE$`: patch the template that really renders the page (TEMPLATES.md §1 — news/blog categories use the archive template 6/1, 7/2). No change after a patch = wrong template, find it by fingerprint.
- `<meta name="description">` present and equal to `meta_description`.
- Status 200, no "Доступ запрещён" (category access), no 404.

## 6. Content-level SEO checklist

- One topic per material; the main phrase in title, first paragraph, one `h2`, slug and meta title — naturally.
- `h2`/`h3` structure in the body; the page `h1` comes from the template.
- Internal links: 2–4 links to related materials/categories with descriptive anchors.
- Images: alt text, sensible file names, width/height.
- No duplicates: before creating, `content_read list` the module and search titles; prefer updating an existing material over a near-duplicate.
- Freshness: when substantially updating a material, `uplastmod: 1`.
