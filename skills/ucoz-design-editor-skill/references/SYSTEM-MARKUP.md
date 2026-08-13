# Platform Markup Patterns

Reference for DOM shapes, CSS traps, and uCoz system markup that recur across modules.
CSS dump examples → [../EXAMPLES.md](../EXAMPLES.md).

---

## Forms & content wrappers

### Entry content variables

Inside "Entry page (with comments)" templates, the main body block pattern is:

```html
<tr><td class="eMessage">
  <?if $COVER_IMAGE$?>$COVER_IMAGE$<?endif?>
  $MESSAGE$
  <?if($ATTACHMENTS$)?><div style="text-align:start;" class="eAttach"><!--<s3172>-->Attachments<!--</s>-->: $ATTACHMENTS$</div><?endif?>
</td>
```

- `eMessage` — main entry text cell.
- `eAttach` — attachments row; wraps `$ATTACHMENTS$`.
- `<!--<sNNNN>-->...<!--</s>-->` — uCoz system language-string markers. **Keep intact** when patching around them. Never remove or rewrite these wrapper comments.

### `$META_DESCRIPTION$` self-wrapping trap (SHOP)

On shop home/category pages with no manually-set SEO description, `$META_DESCRIPTION$` auto-generates as a **complete `<meta>` tag**, not plain text. Wrapping it inside your own attribute breaks:

```html
<!-- Wrong -->
<meta name="description" content="$META_DESCRIPTION$" />
```

The variable's own `content="..."` closes the outer quote, leaving dangling `" />` as visible body text.

**Fix:** output it standalone, static fallback for OG/Twitter:

```html
<?if($META_DESCRIPTION$)?>$META_DESCRIPTION$<?else?><meta name="description" content="..." /><?endif?>
<meta property="og:description" content="static fallback" />
<meta name="twitter:description" content="static fallback" />
```

### Shop product add/edit form — `manTable` / `#bdM64` (SHOP + SKILL)

Template `20/18` contains:

```html
<table id="bdM64" class="manTable">
```

- JS is wired to `id="tb_*"` tbody IDs and `id="shop-categories"`.
- **Do NOT restructure the HTML** — JS breaks if the table structure changes.
- Only wrap the entire template in `<div class="shop-product-form">…</div>` and style via CSS.
- Submit button: `type="button" class="manFlSbm"` — target by class `.manFlSbm`, not by `type`.
- Reset button: `class="manFlRst" type="reset"`.

CSS rules for the form (in `3/3`, scoped to `.shop-product-form`):

- **Hide sidebar**: `body.module-shop:has(#bdM64) #sidebar{display:none!important}` + `.main{display:block!important}`.
- **Row layout**: `#bdM64>tbody>tr{display:flex!important}` — do NOT add `flex-direction:column` (breaks the options panel).
- **Cell width**: `td,th{display:block;width:100%}` scoped to `#bdM64>tbody>tr>td`.
- **Input styles**: scope to `.manTd2 input/select` NOT `.shop-product-form input` — otherwise nested options table `size="4"` inputs break.
- **`<h1>` alignment**: `body.module-shop:has(#bdM64) h1{max-width:860px;margin:auto}` — because `20/5` renders `<h1>$TITLE$</h1>` before `$BODY$`, placing it outside `.shop-product-form`.

### Users module registration — `#uf-register` system widget (USERS)

Template `4/9` contains the classic `manTable`/`.manFlSbm` registration form, but the **live `/register` page may render a completely different system widget**: `#uf-register`, with classes `.uf-field`, `.uf-text` (inputs), `#uf-submit` (submit button), `.register-social-list .login-with` (social buttons).

**Always verify against the live page first** before styling `4/9` markup.

CSS targets for the live widget (add to `3/3`):

```css
#uf-register .uf-text { … }   /* inputs */
#uf-submit { … }              /* submit button */
.uf-field label { … }         /* field labels */
```

The widget ships its own inline `<style>` that handles layout/grid but leaves colors/borders essentially unstyled — that is the gap custom CSS fills.

### Blog editor form — `.bef-*` field structure (SKILL / `7/6`)

`$BRIEF_FL$` and `$MESSAGE_FL$` **must NOT get a custom `<label>` wrapper** — uCoz renders its own label inside these variables; wrapping them creates a duplicate.

All other simple fields (`$CAT_FL$`, `$TITLE_FL$`, `$COVER_FL$`, `$TAGS_FL$`, etc.) need a `.bef-label`.

Wrap ordering / date / author / options fields in `.bef-section` + `.bef-section-title`. Submit + Cancel go in `.bef-actions`.

CSS in `3/3` (`.blog-editor-form`):

- `.bef-field` → `display:flex; flex-direction:column; gap:8px`
- `.bef-label` → Manrope 14px 600
- `.bef-control input/select/textarea` → full-width, border 1.5px `#e2e8f0`, `border-radius:10px`
- `.bef-control--upload` → dashed border
- `.bef-actions input:not([type=reset])` → black button; reset → gray

**Checkboxes:** use `appearance:none` + `background-image` SVG on `:checked`:

```css
background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 10 8'%3E%3Cpath d='M1 4l3 3 5-6' stroke='white' stroke-width='1.5' fill='none'/%3E%3C/svg%3E");
background-size:9px 7px; background-position:center; background-repeat:no-repeat;
```

- ❌ Do NOT use `accent-color` — overridden by uCoz base CSS.
- ❌ Do NOT use `::before/::after` with a unicode character — cross-browser unreliable.

### Blog add/edit page — `7/5` shell (SKILL)

Standalone shell — no sidebar. Add class `blog-editor-page` on `<body>` to make `.main` single-column (`display:block!important`). Include both `my.css` and `shop.css` in `<head>`.

JS at bottom of body hides the `$CUT$` separator: queries `[name=cut]`, `[name=cuts]`, and any `input[type=text]` whose `.value` contains `'$CUT$'`. Hide **only the element itself or its nearest `<p>`**, never its `.bef-control` parent (would kill the WYSIWYG editor).

Watch for accidental `$AJAX_JS$` duplication after patches — run `read_template` and remove the second instance with `patch_template` if found.

### Global block `_uWnd` — custom auth modal note (GBLOCKS)

uCoz's native `_uWnd` login popup is hard to style and sandboxed. Replace it with a fully custom modal in `AHEADER` — see [../EXAMPLES.md](../EXAMPLES.md#auth-modal).

**All other `_uWnd` / `xw-*` toast and modal styling** (forum alerts, mail-form success, 9-slice frame) → dedicated rules in [UWND.md](UWND.md).

---

## Entry lists & grids

### Blog `#allEntries` — main page vs category/archive pages (BLOG)

uCoz wraps the entries list in `<div id="allEntries">`. Its internal structure **differs between page types**:

**Main page (`7/1`)** — entry `<div>`s are direct children of `#allEntries`:

```html
<div class="blog-grid">
  <script>...spages function...</script>   ← pagination JS, not a grid item
  <div id="allEntries">                   ← THIS is the grid parent
    <div id="entryID2"><article class="blog-card">…</article></div>
    <div id="entryID1"><article class="blog-card">…</article></div>
    <div class="catPages1" …></div>        ← pagination links, inside allEntries
  </div>
</div>
```

**Category / archive / section pages (`7/2`, `7/7`)** — `#allEntries` contains a `<table>`:

```html
<div id="allEntries">
  <table class="catAllEntries">
    <tr>
      <td class="archiveEntries">          ← THIS is the real grid parent
        <div id="entryID12">…</div>
        <div id="entryID11">…</div>
        <div class="catPages1">…</div>     ← pagination, inside td
      </td>
    </tr>
  </table>
</div>
```

**The trap:** blindly applying `#allEntries{display:grid;grid-template-columns:repeat(3,1fr)}` site-wide on a category page gives `#allEntries` exactly **one child** (the `<table>`), which occupies one of three columns — the entire card row renders squeezed into a third of the container.

**Fix — split the rule with `:has()` so each page type gets the right grid parent:**

```css
/* Category/archive pages — let the table's td handle the grid */
#allEntries:has(table){display:block!important}
/* Main page — apply grid directly to #allEntries */
#allEntries:not(:has(table)){display:grid!important;gap:28px!important;grid-template-columns:repeat(3,1fr)!important;margin-top:0!important}

/* Reset table ancestors so td can actually go full-width */
table.catAllEntries,table.catAllEntries>tbody,table.catAllEntries>tbody>tr{display:block!important;width:100%!important}
/* Grid on the td — the real grid parent on category/archive pages */
td.archiveEntries{display:grid!important;gap:28px!important;grid-template-columns:repeat(3,1fr)!important;width:100%!important;box-sizing:border-box!important}
td.archiveEntries [id^='entryID']{min-width:0}
/* Pagination spans all columns */
td.archiveEntries .catPages1{grid-column:1/-1;text-align:center}
```

Note: the `display:block!important;width:100%!important` on `table`/`tbody`/`tr` is required — just adding `display:grid` to a `<td>` without resetting its table ancestors leaves the table layout algorithm active, which can shrink the whole row to fit content instead of the container's full width.

**Repeat this `:not(:has(table))` / `td.archiveEntries` pairing in every responsive breakpoint.** A one-sided fix (e.g. updating only `#allEntries` at `max-width:640px` and not `td.archiveEntries`) re-introduces the narrow-cards bug at that breakpoint.

### Pagination — `catPages1` is inside `#allEntries`

Pagination renders as `<div class="catPages1">` inside `#allEntries` (or `td.archiveEntries`). Apply `grid-column:1/-1` so it spans all columns:

```css
#allEntries .catPages1{grid-column:1/-1;text-align:center}
td.archiveEntries .catPages1{grid-column:1/-1;text-align:center}
```

### Theme `entries-grid` class trap (BLOG)

The theme CSS adds huge top-margin between adjacent entries when there is **no** `.entries-grid` descendant:

```css
.layout:where(:not(:has(.entries-grid,.ss-entries-grid))) [id^=entryID]+[id^=entryID] {
  margin-block: var(--tpl-spacing-3xl);
}
```

**Fix:** add `entries-grid` class to the blog-grid wrapper:

```html
<div class="blog-grid entries-grid">$BODY$</div>
```

Also override `#allEntries{margin-top:var(--tpl-spacing-3xl)}` with `margin-top:0!important` in `3/3`.

### Note on photo albums, news, dir, articles

Photo albums (`module 9`), news (`module 6`), directory, and articles (`module 10`) use the same `#allEntries` class wrapping behavior — the main page has direct `div[id^="entryID"]` children; category/archive pages may wrap in `table.catAllEntries` → `td.archiveEntries`. **Always verify the live DOM** on that module before writing grid CSS — do not assume it matches blog exactly.

### Shop `.goods-list` double-wrap trap (SHOP)

`$TOP_SOLD$` / `$TOP_VIEW$` / `$LAST_ADD$` output a **self-contained block** that already includes its own `.goods-list` wrapper with inline grid styles (`style="display:grid;gap:10px;grid-template-columns:repeat(auto-fill,minmax(248px,1fr))"`).

**Bug pattern:** wrapping the variable in your own `<div class="goods-list">` nests two `.goods-list` grids. The outer grid creates several ~236px tracks but has only **one grid item** (the informer's own wrapper div), squeezing the real inner product grid to ~236px wide — all product cards collapse into a single column.

```html
<!-- Wrong: double-nests .goods-list -->
<div class="info-box goods-box">
  <div class="goods-list">$TOP_VIEW$</div>
</div>

<!-- Correct: let the informer render its own grid wrapper -->
<div class="info-box goods-box">
  $TOP_VIEW$
</div>
```

Only wrap these variables in a container that is NOT `.goods-list` (e.g. `.info-box.goods-box`).

### Mobile `.goods-list` — inline grid override (SHOP)

uCoz injects inline styles on informer `.goods-list`: `style="display:grid;gap:20px;grid-template-columns:repeat(5,232px)"`. On mobile this forces 5 fixed-width columns and overflows the viewport. Theme `auto-fill` CSS loses to the inline rule.

**Fix in `20/9` with `!important` on mobile — switch to flex+wrap:**

```css
@media screen and (max-width:767px){
  body.module-shop :is(.goods-box,.products-box) .goods-list{
    display:flex!important;flex-wrap:wrap!important;
    gap:12px!important;overflow:visible!important;
    width:100%!important;max-width:100%!important;
  }
  body.module-shop :is(.goods-box,.products-box) .goods-list>:is(.list-item,.product-card){
    flex:1 1 calc(50% - 6px)!important;max-width:calc(50% - 6px)!important;min-width:0!important;
  }
}
@media screen and (max-width:420px){
  body.module-shop :is(.goods-box,.products-box) .goods-list>:is(.list-item,.product-card){
    flex:1 1 100%!important;max-width:100%!important;
  }
}
```

Also override `#goods_cont .goods-list` (category/search catalog from `$BODY$` / `<?$CATALOG$?>`):

```css
@media screen and (max-width:767px){
  body.module-shop #goods_cont .goods-list{
    display:flex!important;flex-wrap:wrap!important;grid-template-columns:unset!important;
  }
}
```

Do **not** touch `.goods-list.shop-itempage-buy-btns` (product page action buttons).

**CSS pattern examples →** [../EXAMPLES.md](../EXAMPLES.md#shop-patterns)
