# Blog Module Playbook (`module_id=7`)

Canonical playbook for content-module editing (blog as the worked example). See also [SYSTEM-MARKUP.md](SYSTEM-MARKUP.md) and [../EXAMPLES.md](../EXAMPLES.md).

---

## Template map

| Template ID | Name | Role |
|---|---|---|
| `7/1` | Main blog page | **Full-page shell** — wraps `$BODY$` (article card list), loads CSS/fonts, has `$GLOBAL_AHEADER$` / `$GLOBAL_BFOOTER$`. No sidebar. |
| `7/2` | Archive / category page | Full-page shell for monthly archive and category/section pages. Same structure as `7/1`. |
| `7/3` | Article + comments | Full-page shell for a single article with comments. |
| `7/4` | **Article card view** | **Fragment** — renders one card in lists on `7/1` / `7/2` / `7/7`. No `<html>`, no `$POWERED_BY$`. |
| `7/5` | Add/edit form page | Full-page shell for the article add/edit admin form. |
| `7/6` | Add/edit form content | Fragment — the actual form markup. |
| `7/7` | Section list page | Full-page shell for a section/category listing. |

---

## Body class

`<body class="module-$MODULE_ID$">` → **`body.module-blog`** on live pages.
Scope all blog-specific CSS to `body.module-blog`. Do NOT scope to `body.module-publ` (module-level keyword differs from the body class).

Note: `body.module-publ .main{margin-block:0!important;padding:0!important}` is a pre-existing theme reset in `3/3` that may be required regardless — check live.

---

## Shell vs fragment rules

- **Full-page shells** (`7/1`, `7/2`, `7/3`, `7/7`): must have `<html>/<head>/<body>`, `$GLOBAL_AHEADER$`, `$GLOBAL_BFOOTER$`, and `<?if(0)?>$POWERED_BY$<?endif?>` near `</body>`.
- **Fragments** (`7/4`, `7/6`): no `<html>/<head>/<body>` tags, no `$POWERED_BY$`. They render inside the shell's `$BODY$` variable.

---

## `$BODY$` internal structure and grid placement

`$BODY$` on the main page wraps all cards in `<div id="allEntries">`. The grid must go on `#allEntries`, not `.blog-grid`:

```html
<div class="blog-grid entries-grid">$BODY$</div>
```

```css
.blog-grid{display:block}
#allEntries:not(:has(table)){display:grid!important;gap:28px!important;grid-template-columns:repeat(3,1fr)!important;margin-top:0!important}
```

**Category/archive pages** (`7/2`, `7/7`) wrap `#allEntries` contents in `<table class="catAllEntries"><tr><td class="archiveEntries">`. Apply grid to `td.archiveEntries` instead. **Full rules including `:has(table)` split → see [SYSTEM-MARKUP.md — Entry lists & grids](SYSTEM-MARKUP.md#entry-lists--grids).** Do not compress or omit those rules.

Add `entries-grid` class to the grid wrapper to suppress the theme's `margin-block` trap between adjacent entry divs.

---

## Template chain — edit the card view, not the shell

To change how cards look in any listing (main page, archive, section): edit **`7/4`** (the view template), not `7/1`. The shell's `$BODY$` just renders a list of `7/4` instances.

```
7/1 (shell): <div class="blog-grid entries-grid">$BODY$</div>  ← grid parent CSS here
7/4 (view):  <article class="blog-card">…</article>             ← card markup here
```

---

## Editor traps (`7/5`, `7/6`)

**`7/5` shell:**

- No sidebar. `blog-editor-page` on `<body>` → `.main{display:block!important}`.
- Include both `my.css` and `shop.css` in `<head>`.
- JS at bottom hides `$CUT$` separator — query `[name=cut]`, `[name=cuts]`, `input[type=text]` with `.value` containing `'$CUT$'`. Hide only the element or nearest `<p>`, **never** `.bef-control` (would kill the editor).
- Watch for `$AJAX_JS$` duplication after `update_template`; collapse with `patch_template`.

**`7/6` form:**

- `$BRIEF_FL$` and `$MESSAGE_FL$` — do NOT add custom `<label>` wrappers (uCoz renders its own label inside).
- All other fields (`$TITLE_FL$`, `$CAT_FL$`, etc.) need `.bef-label`.
- Wrap ordering/date/options fields in `.bef-section` + `.bef-section-title`.
- Submit + Cancel in `.bef-actions`.
- Full CSS rules for `.blog-editor-form` → [SYSTEM-MARKUP.md — Forms & content wrappers](SYSTEM-MARKUP.md#blog-editor-form--bef--field-structure-skill--76).

---

## `$POWERED_BY$` on shells

For full-page shells, add near `</body>`:

```html
<?if(0)?>$POWERED_BY$<?endif?>
```

This satisfies the validator. The real copyright renders through `$GLOBAL_BFOOTER$`. Never add a second visible `$POWERED_BY$`.

---

## CSS scoping

- All blog CSS → `3/3`, scoped to `body.module-blog`.
- Reset system `.main` spacing: `body.module-blog .main{margin-block:0!important;padding:0!important}`.
- Never scope to `#layout.module-blog` — the module class is on `<body>`, not `#layout`.
- Grid CSS: see [SYSTEM-MARKUP.md](SYSTEM-MARKUP.md#entry-lists--grids).

---

## Required links in full-page shells

All full-page blog shells need:

```html
<!-- In <head> -->
<link rel="stylesheet" href="/_st/my.css" />
<link rel="stylesheet" href="/_st/shop.css?v=091123" />

<!-- Before </body>, after $GLOBAL_BFOOTER$ -->
<script src="/.s/t/2301/main.js"></script>
```

**Without `main.js` the menu stays invisible.** The theme sets `.main-menu.menu-evolving{opacity:0}` on init; `main.js` removes that class after measuring menu items.

---

## Variable notes

| Variable | Rule |
|---|---|
| `$COVER_IMAGE$` | Use for `<?if?>` checks — truthy flag |
| `$COVER_URL$` | Full-size; use in article page hero (`7/3`) |
| `$COVER_SMALL_URL$` | Thumbnail; use in card listings (`7/4`) and sidebar related |
| `$DATE_BAR$` | Outputs HTML `<span>` — **never use in `<title>` tag** (tags leak into title) |
| `$TITLE_BAR$` | Plain text — use in `<title>` |
| `<?$RELATED_ENTRIES$(5)?>` | Parameterized — does NOT appear in `get_variables`. Syntax: count only, no second param. |

In `7/2` (doubles as archive AND category page):

- `<title>` → use `$TITLE_BAR$`, not `$DATE_BAR$`
- Page `<h1>` → `<?if($CAT_NAME$)?>$CAT_NAME$<?else?>$DATE_BAR$<?endif?>`
- Category description → `$CAT_DESCR$` (not `$SECTION_DESCR$` — that belongs to `7/7`; always check `get_variables` per template)

Do NOT use `$MODULE_SEARCH_FORM$` in `7/3` sidebar — outputs uCoz table/float layout that cannot be reliably overridden. Use a custom hardcoded `<form action="/search/" method="get">` instead.

Do NOT use `$CATEGORIES$` in sidebar — outputs `[N]` count nodes and description paragraphs. Hardcode the category list with known `/blog/1-0-{catID}` URLs instead.

`<!--<new_informer>-->` JSON block does **not** work in full custom templates (renders as raw JSON text). Use `$SIMILAR_ENTRIES$` or `<?$RELATED_ENTRIES$(N)?>` instead.

---

## Applying to other content modules

The same chain and rules apply to: **News** (`module_id=6`), **Directory**, **Load**, **Photo albums** (`module_id=9`), and **Articles Catalog** (`module_id=10`).

All follow the same pattern:

1. A full-page **shell** wraps `$BODY$` with `$GLOBAL_AHEADER$` and `$GLOBAL_BFOOTER$`.
2. An **entry view fragment** defines how each card renders inside `$BODY$`.
3. The grid lives on `#allEntries`; fragments have no `<html>` and no `$POWERED_BY$`.
4. Category/archive pages may wrap `#allEntries` in `table.catAllEntries` → `td.archiveEntries` — **verify the live DOM** before writing grid CSS.

### Articles Catalog (`module_id=10`) known deltas

Articles is a **dashboard module** — do not confuse it with Blog (`7`):

- Templates `10/1`–`10/6` are full-page **dashboard shells** that include `$GLOBAL_CLEFTER$` (sidebar).
- Templates `10/7` (card) and `10/8` (form fields) are fragments.
- The dashboard layout wrapper: `<div id="layout" class="aa-account-layout">$GLOBAL_CLEFTER$<main class="aa-account-main">…</main></div>` — `AHEADER` is NOT used in these shells.
- Each shell contains its own copy of `#nt-auth-modal` and loads `/js/script.js`.
- Guests on `10/1` and `10/3` render a public access block (between `$GLOBAL_AHEADER$` and `$GLOBAL_BFOOTER$`) without the dashboard shell.
- `$BODY$` and `$PAGE_SELECTOR$` must be preserved in list templates. Do NOT output `$PAGE_SELECTOR1$` (creates a duplicate system pagination).
- Do NOT output `$SORTING_MENU$` in `10/3`.
- CSS lives only in an external FTP file (`/css/style.css`); after changes, bump `?v=` on all `10/1`–`10/6` shells.
- In `10/8` (form fields): do NOT delete or rename native `$..._FL$` fields, submit/cancel buttons, or embedded uCoz scripts.

Preserve `$BODY$`, paging, filters, moderation, and every native form field. Do not confuse articles (`10`) with blog (`7`) — different templates, variables, and output format.

See [SYSTEM-MARKUP.md](SYSTEM-MARKUP.md) for `#allEntries` grid behavior shared by both modules.
