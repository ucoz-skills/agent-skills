# Platform markup patterns (greenfield CSS)

Condensed DOM traps and system classes that a new Template Creator design **must** handle in `3/3` (and module CSS where noted). This is not a full Design Editor dump — only what greenfield CSS needs to avoid broken grids, menus, and shells.

Related: breadcrumbs and menu variables → [FRAMEWORK.md](FRAMEWORK.md). Broader DE patterns → Design Editor `SYSTEM-MARKUP.md`.

---

## Entry lists — `#allEntries` vs archive table

uCoz wraps list items in `<div id="allEntries">`. Internal structure **differs by page type**:

**Main / home list** — entry wrappers are direct children of `#allEntries`:

```html
<div id="allEntries">
  <div id="entryID2">…</div>
  <div id="entryID1">…</div>
  <div class="catPages1">…</div>
</div>
```

**Category / archive / section** — `#allEntries` contains a table; the real grid parent is the cell:

```html
<div id="allEntries">
  <table class="catAllEntries">
    <tr>
      <td class="archiveEntries">
        <div id="entryID12">…</div>
        <div class="catPages1">…</div>
      </td>
    </tr>
  </table>
</div>
```

**Trap:** `#allEntries { display:grid; grid-template-columns:repeat(3,1fr) }` site-wide on an archive page gives `#allEntries` **one child** (the `<table>`). Cards squeeze into one column track.

**Fix — split with `:has()` and reset table ancestors:**

```css
#allEntries:has(table) { display: block !important; }
#allEntries:not(:has(table)) {
  display: grid !important;
  gap: 28px !important;
  grid-template-columns: repeat(3, 1fr) !important;
  margin-top: 0 !important;
}

table.catAllEntries,
table.catAllEntries > tbody,
table.catAllEntries > tbody > tr {
  display: block !important;
  width: 100% !important;
}

td.archiveEntries {
  display: grid !important;
  gap: 28px !important;
  grid-template-columns: repeat(3, 1fr) !important;
  width: 100% !important;
  box-sizing: border-box !important;
}
td.archiveEntries [id^="entryID"] { min-width: 0; }

#allEntries .catPages1,
td.archiveEntries .catPages1 {
  grid-column: 1 / -1;
  text-align: center;
}
```

Repeat the `#allEntries:not(:has(table))` / `td.archiveEntries` pair at **every** responsive breakpoint. Photo, News, Directory, Articles often share this pattern — **verify live DOM** per module before copying Blog rules.

Optional theme trap: some bases add large margins between entries unless a wrapper carries `.entries-grid`. Prefer adding that class on the list wrapper when the theme expects it.

---

## Standard entry chrome — `.eBlock` family (via `3/3`)

When a module has no framework `*_entry_view` / `*_entry_page__body`, or leftover standard markup remains, style these in site-wide `3/3`:

| Class | Role |
|---|---|
| `.eBlock` | Entry / result card wrapper |
| `.eTitle` | Title row / link |
| `.eMessage` | Body / excerpt cell |
| `.eDetails` | Meta row (date, author, views, etc.) |
| `.eAttach` | Attachments (keep system `<!--<sNNNN>-->…<!--</s>-->` markers intact) |

Also expect `.e-category`, `.e-reads`, `.e-author`, `.ed-title`, `.ed-value` on metadata. Prefer shared tokens and the same card language as custom entry views. Do not restyle by rewriting HTML IDs that platform JS depends on.

Breadcrumbs: one shared class contract already defined in [FRAMEWORK.md](FRAMEWORK.md) (wrapper / items / one `currentColor` SVG separator). Style via `3/3`; do not invent a second breadcrumb system per module.

---

## Shop grids — `.goods-list` / `#goods_cont`

Informers `$TOP_SOLD$` / `$TOP_VIEW$` / `$LAST_ADD$` already emit a `.goods-list` with **inline** grid styles. Do **not** wrap them in another `.goods-list` (double grid → one narrow column).

On mobile, inline `grid-template-columns:repeat(5,232px)` overflows. Override with `!important` (typically in shop CSS `20/9`, accents still from site tokens):

```css
@media screen and (max-width: 767px) {
  body.module-shop :is(.goods-box, .products-box) .goods-list {
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 12px !important;
    width: 100% !important;
    max-width: 100% !important;
  }
  body.module-shop :is(.goods-box, .products-box)
    .goods-list > :is(.list-item, .product-card) {
    flex: 1 1 calc(50% - 6px) !important;
    max-width: calc(50% - 6px) !important;
    min-width: 0 !important;
  }
  body.module-shop #goods_cont .goods-list {
    display: flex !important;
    flex-wrap: wrap !important;
    grid-template-columns: unset !important;
  }
}
```

Do **not** restyle `.goods-list.shop-itempage-buy-btns` (product action row).

---

## Forms — verify live before restyle

| Surface | Note |
|---|---|
| Shop product add/edit (`20/18`) | `table#bdM64.manTable` — JS binds to `tb_*`, `#shop-categories`. Wrap only; do not restructure. Target `.manFlSbm` / `.manFlRst`. |
| Users registration (`4/9`) | Template may show `manTable`, but live `/register` often renders `#uf-register` (`.uf-field`, `.uf-text`, `#uf-submit`). **Always inspect live DOM** before writing CSS. |
| Other `manTable` / `#uf-*` widgets | Preserve IDs and hooks; style colors/borders in `3/3` (or module CSS for module-only pages). |

Never break platform JS IDs. Prefer outer wrappers + scoped CSS over HTML surgery.

---

## Search (pointer)

Always migrate Search shell `19/1` to the theme shell + versioned `/_st/my.css?v=…`. Style result rows `19/2` via `3/3` (`.eBlock` or design cards). Full Search notes → [FRAMEWORK.md](FRAMEWORK.md#search).

---

## Anti-patterns (markup CSS)

- Grid only on `#allEntries` without the archive `:has(table)` / `td.archiveEntries` split
- Nesting shop informers in a second `.goods-list`
- Styling registration as `manTable` without checking `#uf-register` live
- Hiding or duplicating `$POWERED_BY$` (production rules → [FRAMEWORK.md](FRAMEWORK.md))
- Wrapping `$NMENU_*$` / `$SMENU_*$` in a custom `<ul>` (menu reset → [FRAMEWORK.md](FRAMEWORK.md#menu-variables-nmenu_-vs-smenu_))
