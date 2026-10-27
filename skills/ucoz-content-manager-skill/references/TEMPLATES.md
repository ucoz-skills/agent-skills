# Template edits for content and SEO

This skill edits templates only to make content render correctly: SEO head tags, H1, category descriptions, Open Graph, structured data, small display fixes. Whole redesigns belong to `ucoz-design-editor-skill`.

## 1. Find the template that really renders the page

Template names mislead. Verified on a test site: **category pages of news and blog are rendered by the "Страница архива материалов" template**, not by "Страница со списком материалов раздела". Patching the wrong one changes nothing and looks like "the platform doesn't support it".

Map observed on a test site with all content modules installed (group id / template id; confirm on yours):

| Module (group) | Entry page | Category page | Section page | Module main | Entry in lists |
|---|---|---|---|---|---|
| news (6) | `2` | **`1`** (archive; also month/day archives) | `6` | — (main uses `1` too) | `3` |
| blog (7) | `3` | **`2`** (archive; also month/day archives) | `7` | `1` | `4` |
| photo (9) | `4` (full photo) | `3` (album) | `2` | `1` | `8` |
| publ (10) | `4` | `3` | `2` | `1` | `7` |
| load (11) | `4` | `3` | `2` | `1` | `7` |
| dir (12) | `4` | `3` | `2` | `1` | `7` |
| board (13) | `4` | `3` | `2` | `1` | `7` |
| faq (14) | — | `1` (all pages) | — | `1` | `2` |
| gb (15) | — | — | — | `1` | `2` |
| polls (16) | — | — | — | `1` (poll form) | — |
| mchat (17) | — | — | — | — | `1` |
| stuff (21) | `4` | `3` | `2` | `1` | `7` |
| video (22) | `4` | `3` | `2` (channels) | `1` | `8` |

Shared groups: `1` global blocks (`AHEADER`, `BFOOTER`, `CLEFTER`…), `2` site pages (`0`), `3` general (`3` CSS, `4` related, `5` material images view), `5` comments, `19` search.

**Fingerprint check (do it before concluding anything):**

1. Fetch the public page (category, entry, main) as a guest.
2. Pick a static string from the page that comes from the template, not from data — breadcrumb text («Главная», «Статьи»), a label («В категории материалов», «Добавить статью»), a CSS class used only there.
3. Search candidate templates of the group for that string (from `read_template` copies you already have).
4. The template that contains it renders the page. Patch that one.

Each page template is a **full HTML document** with its own `<head>`. Head changes go into every page template you want covered.

## 2. Reading costs and limits

- `read_template` returns the whole template **plus its variable list** (≈60 % of the reply). Stock module templates are 10–15k tokens. Read each template once and keep the copy; trust one variable list per template type.
- The stock CSS template (group 3, template 3) is ≈201k characters: `read_template` overflows the output and `validate_template` / `patch_template` return `MAX_LENGTH_EXCEEDED` (limit 200 000). Don't plan CSS edits there — use inline `style` on the element you add, or a small CSS rule inside a template/global block you already patch.
- Backups: `list_backups` reports `mcp_limit: 25`, `cp_limit: 25`. Every `patch_template` makes an automatic MCP backup; `create_backup` makes a manual one (shown with `source: "cp"`, not rotated). Take one manual baseline before a multi-template job — especially if other agents/sessions edit templates at the same time — and note every `backup.id`.

## 3. Safe editing loop

```text
1. read_template(module_id, template_id)              — fresh copy, never from memory
2. confirm variables in that reply                     — every $VAR$ you plan to use must be listed
3. build code_search (byte-exact, unique) + code_paste
4. validate_template(module_id, template_id, code_search, code_paste)
5. patch_template(...)                                — note backup.id
6. fetch the public page and check the result
```

- Never remove system variables, `<?if?>` blocks, `$MODER_PANEL$`, comment blocks, `$AJAX_JS$`. `$POWERED_BY$` is mandatory in a full template you write or replace; stock 2301 module templates carry it via `$GLOBAL_BFOOTER$` — don't add it into them, just don't remove the footer.
- Title lines differ per template (news/blog `<title>$TITLE_BAR$ - $SITE_NAME$</title>`; blog main `<title>$MODULE_NAME$ - $SITE_NAME$</title>`; publ entry `<title>$ENTRY_TITLE$ - $CAT_NAME$ <?if($SECTION_NAME$)?>- $SECTION_NAME$<?endif?> - $MODULE_NAME$ - $SITE_NAME$</title>`; publ category `<title>$CAT_NAME$ <?if($SECTION_NAME$)?>- $SECTION_NAME$<?endif?> - $MODULE_NAME$ - $SITE_NAME$</title>`). Copy the real one.
- `MATCH_NOT_FOUND` → re-read and copy again; `AMBIGUOUS_MATCH` → lengthen `code_search`.

## 4. Patch 1 — make meta title and description render (verified)

```html
<title><?if($SEO_TITLE$)?>$SEO_TITLE$<?else?>ORIGINAL TITLE CONTENT<?endif?></title>
<?if($SEO_DESCRIPTION$)?><meta name="description" content="$SEO_DESCRIPTION$"><?endif?>
```

`ORIGINAL TITLE CONTENT` is exactly what was inside `<title>…</title>` before. If a `<meta name="description">` already exists, change it instead of adding a second one.

Verified results (SEO module on, `seo_UseUniqTitleDescKeys: true`):

| Page | Template patched | Result |
|---|---|---|
| Entry pages news / blog / publ / load | 6/2, 7/3, 10/4, 11/4 | `<title>` = `meta_title`, description present; entries without meta keep the old title |
| Category pages publ / load / board, photo albums | 10/3, 11/3, 13/3, 9/3 | category meta renders (title line identical in all four: `<title>$CAT_NAME$ <?if($SECTION_NAME$)?>- $SECTION_NAME$<?endif?> - $MODULE_NAME$ - $SITE_NAME$</title>`) |
| Category pages news / blog | **6/1, 7/2 (archive)** | category meta renders |
| Category pages dir / stuff | 12/3, 21/3 (same title line as publ) | category meta renders; categories without meta keep the old title |
| Category pages video | 22/3 (`<title>$CAT_NAME$ - $MODULE_NAME$ - $SITE_NAME$</title>`) | don't patch: video categories have no meta fields |
| Category pages news / blog via 6/6, 7/7 | section templates | no effect — wrong template |
| Module main pages | 11/1, 7/1… | stays the default: module-level SEO is not exposed through MCP |

Materials without `meta_description` get no description tag — fix the data, don't put `$BRIEF$`/`$MESSAGE$` into the attribute (HTML and quotes break it).

## 5. Patch 2 — one H1 per page (verified on publ, load, news)

Stock templates have **no H1** on entry, category or main pages. Put the H1 inside the existing title container so the layout keeps its class; neutralise the browser H1 style inline (the CSS template can't be patched, see §2):

```html
<!-- publ / load / dir / board / stuff entry page (title var $ENTRY_TITLE$; load also prints $VERSION$) -->
…<?endif?><h1 class="eTitle-h1" style="font:inherit;margin:0">$ENTRY_TITLE$</h1></div>

<!-- news / blog entry page: the container also holds time + moder panel -->
<div class="eTitle"><div style="…">$ADD_TIME$ $MODER_PANEL$</div><h1 class="eTitle-h1" style="font:inherit;margin:0;display:inline">$ENTRY_NAME$</h1></div>
```

Category pages: add `<h1 class="cat-h1" style="margin:0 0 12px">$CAT_NAME$</h1>` right after the breadcrumbs block (in 2301 the `.breadcrumbs-wrapper`, which also holds the «Добавить…» link), and print the category text if the template doesn't: `<?if($CAT_DESCR$)?><div class="cat-descr">$CAT_DESCR$</div><?endif?>` (verified on load 11/3; check the variable exists in the template).

News/blog archive template (6/1, 7/2) serves categories **and** date archives: `<?if($CAT_NAME$)?><h1 …>$CAT_NAME$</h1><?else?><h1 …>$TITLE_BAR$</h1><?endif?>` (verify the variables in that template's list).

Main pages: `<h1 …>$MODULE_NAME$</h1>`.

Check there is exactly one `<h1>` on the rendered page.

## 6. Patch 3 — Open Graph (verified on publ)

`$COVER_URL$` is a **relative** path (`/_pu/0/300001.webp`); Open Graph needs absolute URLs — prefix the public origin (`https://` + the host from `select_site`, or the site's own domain):

```html
<meta property="og:type" content="article">
<meta property="og:title" content="<?if($SEO_TITLE$)?>$SEO_TITLE$<?else?>$ENTRY_TITLE$<?endif?>">
<?if($SEO_DESCRIPTION$)?><meta property="og:description" content="$SEO_DESCRIPTION$"><?endif?>
<?if($COVER_URL$)?><meta property="og:image" content="https://SITE_HOST$COVER_URL$"><?endif?>
<meta property="og:url" content="https://SITE_HOST$REQUEST_URI$">
```

## 7. Patch 4 — JSON-LD (verified; JSON parses)

```html
<script type="application/ld+json">{"@context":"https://schema.org","@type":"Article","headline":"$ENTRY_TITLE$",<?if($COVER_URL$)?>"image":"https://SITE_HOST$COVER_URL$",<?endif?>"mainEntityOfPage":"https://SITE_HOST$REQUEST_URI$"}</script>
```

Don't put in: `$ADD_DATE$`/`$MOD_DATE$` (render «Сегодня» → invalid dates), `$USER$` (empty on publ entry pages). Titles come HTML-escaped (`&quot;`) — JSON stays valid. FAQ: `FAQPage` only if questions and answers are visible. After patching, fetch the page and parse the JSON.

## 8. Canonical

With `seo_UseRelCanonical: true` stock 2301 templates still output no `<link rel="canonical">`. Report it; don't hand-write a canonical tag in templates unless the user asks — a wrong canonical (e.g. `$REQUEST_URI$` with paging/query) can de-index pages.

## 9. Display fixes that often come with content work

| Need | Where | Variable |
|---|---|---|
| Gallery images visible on the entry page | entry page | publ stock entry page prints **no** gallery — use `$IMAGEn$`/`<figure>` in the text, or add a gallery block with `$IMGS_ARRAY_JS$`/`$ATTACHMENTS$` (check variables) |
| Cover on the entry page | entry page | `<?if($COVER_IMAGE$)?>$COVER_IMAGE$<?endif?>` (in stock 2301 templates) |
| Tags | entry page / entry view | `<?if($TAGS$)?>…$TAGS$<?endif?>` |
| Category image | category page | `$CAT_IMG$`, `$CAT_IMG_URL$` |
| Category description | category page | `$CAT_DESCR$` |
| Author fields (publ) | entry page | `$AUTHOR_NAME$`, `$SOURCE$` |
| Custom field | entry page/view | `$OTHER1$`… |
| A poll | any template | `$POLLC_<id>$` from the poll's `CODE_VAR`, or `$POLL$` |

**Cover alt (verified on publ 10/4 and blog 7/3).** Stock entry pages print `<?if($COVER_IMAGE$)?>$COVER_IMAGE$<?endif?>`, which renders `<img class="e-cover-image">` without alt. The exact fragment differs (blog: `<?if $COVER_IMAGE$?>$COVER_IMAGE$<?endif?>`) — copy it from `read_template`. In an SEO task replace it with (news/blog use `$ENTRY_NAME$` instead of `$ENTRY_TITLE$`):

```html
<?if($COVER_URL$)?><img src="$COVER_URL$" class="e-cover-image" alt="$ENTRY_TITLE$"><?endif?>
```

## 10. Rollback

Reverse a patch with a reverse `patch_template` (swap search and paste) — it touches only that template. `restore_backup` (`confirm: true`) rolls back **all** templates, including other people's/agents' edits made after the backup; use it only when the user accepts that.
