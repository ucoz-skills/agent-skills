# Search Module (`module_id=19`) + Header Search Overlay

---

## Template map

| template_id | Purpose |
|---|---|
| `19/1` | Search results page shell (`/search/`, `$PAGE_ID$='results'`) |
| `19/2` | Single search result row (`$BODY$` items) |

Global chrome: `$GLOBAL_AHEADER$` (search toggle + drawer), `$GLOBAL_BFOOTER$`.

---

## Header search overlay — `AHEADER` (`module_id=1`)

### Markup contract — do not break IDs/classes used by theme JS

| Element | Role |
|---|---|
| `#header_mb_search.header-search` | Root wrapper |
| `.js-mb-search-btn` | Toggle button (theme `main.js` toggles `.open` on panel) |
| `#header_mb_search_panel.header-search-panel` | Full-screen overlay root |
| `.js-header-search-overlay` | Dimmed backdrop; click closes panel (`main.js`) |
| `.header-search-drawer` | Black bar that slides down from top |
| `.js-mb-search-input` | Query field; auto-focused on open (`main.js`) |
| `.js-header-search-close` | Close button (custom script in `AHEADER`) |

Form: `method="get"`, `action="/search/"`, hidden `name="t" value="0"`, field `name="q"`.

### Animation rules

- Panel: `position:fixed; inset:0; z-index:10050`
- Drawer: dark background, `transform:translateY(-100%)` → `translateY(0)` when `.open`
- Overlay: semi-transparent dark, fades in with panel
- Input: large underline field, white text, placeholder text
- Hide browser native clear button: `::-webkit-search-cancel-button`, `::-ms-clear`

### JS contract

Use `MutationObserver` on `#header_mb_search_panel` class changes:

- Add `body.search-open` + `body.freeze` while open
- Sync `aria-hidden` / `aria-expanded`
- `.js-header-search-close` + `Escape` remove `.open`

**Do NOT add a second click handler on `.js-mb-search-btn`** — `/.s/t/2301/main.js` already toggles `.open`; a duplicate listener double-toggles (opens then immediately closes).

### Desktop visibility

Theme default hides `.header-search` on desktop. Enable it in `20/9`:

```css
@media screen and (min-width:1025px){
  .header-search{display:block;position:relative}
  .header-search .header-mobile-button{display:flex}
}
```

Do NOT re-add old 280px absolute dropdown rules — the overlay CSS in `AHEADER` replaces them entirely.

---

## Search results page — `19/1`

Same pattern as other full-page shells:

- Font + versioned `/_st/my.css` (required) + `/_st/shop.css` when used in `<head>` — module 19 does **NOT** auto-load shop CSS; link it manually. Keep the system theme reference (`/.s/src/css/2301.css`) if the good site shells use it, but **do not** rely on `2301.css` alone without `/_st/my.css`.
- `$GLOBAL_AHEADER$` + full-width main, **no sidebar** (remove `CLEFTER` / `#sidebar` block entirely from the template).
- `$GLOBAL_BFOOTER$`
- `<?if(0)?>$POWERED_BY$<?endif?>` (copyright lives in `BFOOTER`)

**High-risk orphan:** if the live `19/1` shell still links bare `/my.css` or uses foreign layout classes (`#layout`, `search-layout` from another brand), rewrite the shell to match a known-good site shell **before** styling the form/results.

Body class: `module-search search-page`, layout: `search-layout`.

Content blocks:

1. Breadcrumbs + `<h1>`
2. `$SEARCHFORM$` in `.search-page-form`
3. If `$SEARCH_QUERY$`: summary line (`$NUM_SHOWN$` / `$NUM_ENTRIES$`) + `$BODY$` + `$PAGE_SELECTOR$`
4. Else: short hint text

---

## Result row — `19/2`

Card layout (`.search-result-card`), not `<table class="eBlock">`:

- Optional thumb (`$THUMB$` / `$SMALL$`) for shop hits
- `$TITLE$` link, `$MESSAGE$` snippet, `$ENTRY_DEPTH$` + `$SAVING_DATE$` meta

---

## CSS

Search page styles typically live in `20/9` (or the site CSS template) under a dedicated search-page comment block, scoped with `body.module-search` selectors.

Style the platform `$SEARCHFORM$` output:

- `.search-page-form .queryField` — bordered input
- `.search-page-form .searchSbmFl` / `#sfSbm` — dark submit button
- Hide `.advanced-search-container` (and bracket spans)

**Cascade trap:** uCoz injects `base.min.css` / `layer7.min.css` **after** `/_st/shop.css` in `<head>`, so `[type=submit]{background:var(--tpl-accent-blue)}` can win over shop.css rules without `!important`.

**Fix:** inline `<style>` at the **end of `19/1` `<body>`** (after platform CSS in document order) with `!important` on field/button overrides — this ensures document order wins over the injected platform stylesheets.

---

## Post-edit checklist

- [ ] Click search icon → drawer slides down, overlay dims, input auto-focused
- [ ] Close via X, overlay click, Escape, second icon click — all four methods work
- [ ] No native blue clear button in drawer input (WebKit/Edge)
- [ ] `/search/?q=…` — header + footer present, no sidebar, correct font, styled form + result cards
- [ ] Live HTML `<head>` includes versioned `/_st/my.css` (not bare `/my.css`)
- [ ] Pagination matches site button style
