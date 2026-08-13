# Shop Module (`module_id=20`)

Read the main SKILL.md checklist first. This file covers shop-specific template structure, mandatory rules, and known traps. CSS patterns → [../EXAMPLES.md#shop-patterns](../EXAMPLES.md#shop-patterns). Goods-list grid traps → [SYSTEM-MARKUP.md](SYSTEM-MARKUP.md).

---

## Template map

| template_id | Purpose |
|---|---|
| `20/1` | Shop home page (`$PAGE_ID$='home'`) |
| `20/2` | Catalog / category page (`$PAGE_ID$='category'`) |
| `20/3` | Product card view (shared fragment for all listing contexts) |
| `20/4` | Product page (`$PAGE_ID$='entry'`) |
| `20/5` | Service pages shell (`$PAGE_ID$`: `checkout`, compare, price, `invoices`, add/edit product) |
| `20/6` | Order success message fragment |
| `20/10` | Order details/status page (`$ORDER_CODE$`) |
| `20/9` | **Shop CSS stylesheet** — compiled to `/_st/shop.css` |
| `20/11` | Cart / wishlist (`$PAGE_ID$='usergoods'`) |
| `20/18` | Product add/edit form fragment |

Full-page templates (`1–8`, `10–18`) are complete HTML documents. Dead `<?if($PAGE_ID$='sitePage1')?>` branches from the shared theme skeleton never fire in module 20 — safe to delete on full rewrites.

---

## Mandatory — unified breadcrumbs on ALL shop page templates

Every redesigned shop template must use the **same** `nt-breadcrumbs` markup and CSS. Never mix patterns across pages.

**Never use on redesigned pages:**

- Raw `$SHOP_PATH$` (renders `»` separators)
- Legacy `.breadcrumbs-table` table layout
- Page-specific breadcrumb classes — one class only: `nt-breadcrumbs`

**Always use this pattern:**

```html
<nav class="breadcrumbs nt-breadcrumbs" aria-label="Breadcrumb">
  <a href="$HOME_PAGE_LINK$">Home</a><span class="breadcrumb-sep">/</span>
  <a href="/shop">Catalog</a><span class="breadcrumb-sep">/</span>
  <span class="breadcrumb-curr" aria-current="page">CURRENT PAGE TITLE</span>
</nav>
```

CSS in `20/9` only — use `!important` throughout because theme `2301.css` sets `.layout .breadcrumbs a{font-size:13px}` and `.layout .breadcrumbs .breadcrumb-curr{opacity:.4}`:

```css
body.module-shop .nt-breadcrumbs{align-items:center;color:#9a9da7;display:flex;flex-wrap:wrap;font-size:16px!important;gap:0;line-height:1.5;margin:0 0 20px}
body.module-shop .layout .nt-breadcrumbs a,body.module-shop .nt-breadcrumbs a{color:#9a9da7!important;font-size:16px!important;font-weight:400!important;opacity:1!important;text-decoration:none}
body.module-shop .nt-breadcrumbs a:hover{color:#191925!important}
body.module-shop .nt-breadcrumbs .breadcrumb-sep{color:#c4c8d0!important;font-size:16px!important;margin:0 10px;opacity:1!important}
body.module-shop .nt-breadcrumbs .breadcrumb-curr{color:#6b7280!important;font-size:16px!important;font-weight:400!important;opacity:1!important}
```

---

## `.goods-list` grid traps

See [SYSTEM-MARKUP.md — Shop `.goods-list` double-wrap trap](SYSTEM-MARKUP.md#shop-goods-list-double-wrap-trap-shop) and [SYSTEM-MARKUP.md — Mobile `.goods-list`](SYSTEM-MARKUP.md#mobile-goods-list--inline-grid-override-shop).

Key rule: never re-wrap `$TOP_SOLD$`/`$TOP_VIEW$`/`$LAST_ADD$` in your own `.goods-list` div. The informers already output their own grid wrapper with inline styles.

---

## `#goods_cont`

Category/search catalog listing renders inside `#goods_cont`. uCoz injects inline grid styles on `#goods_cont .goods-list` — override on mobile with `display:flex!important;flex-wrap:wrap!important;grid-template-columns:unset!important`.

---

## Catalog CTA URLs — use `/shop/all`, not `/shop`

Buttons and links whose intent is "all products" must point to **`/shop/all`** (the full product listing), not `/shop` (shop home with hero and sections).

| Button copy | Correct href | Wrong |
|---|---|---|
| "Go to catalog", "All products", "View all" | `/shop/all` | `/shop` |
| Bestsellers / new arrivals (in-page scroll) | `#popular`, `#bestsellers` | — |

---

## Selector trap: `.layout.module-shop` never matches

On shop pages:

```html
<body class="module-shop">
<div id="layout" class="layout home-layout">
```

`module-shop` is on **`<body>`**, not `#layout`. Selector `.layout.module-shop .catalog-box` requires both classes on the same element — matches nothing, overrides silently fail.

**Always use** `body.module-shop .catalog-box …`, never `.layout.module-shop …`.

---

## Interactive hover checklist (links, buttons, icons)

When adding or restyling shop UI, **always add `:hover` (and `:focus-visible` where appropriate)** for every clickable control:

| Element | Pattern | Avoid |
|---|---|---|
| Text links | `opacity:.62` or keep `#191925`, no blue | `color:var(--tpl-accent-blue)` from theme |
| Wishlist (`.wish`) | bg `#f4f5f7`/`#eceef1`, border `#d0d7de`; active `.wdel` stays red | Theme hover blue |
| Primary buttons | Dark `#191925`, hover `#2a2a38` — prefer `body.module-shop{--tpl-accent-blue:#191925}` token | Default theme blue |
| FAQ accordion arrow | Gray/dark arrow, optional subtle scale | Theme plus-icon blue hover |
| Header icons (search, profile, wishlist, cart) | `#191925`, hover `opacity:.62` | `2301.css` `.header-wishlist-btn:hover{color:var(--tpl-accent-blue)}` |

Use `@media (hover:hover){…}` so touch devices don't stick hover styles. Add `transition` on the same properties changed (background, border-color, opacity, color).

---

## `.toggle-btn` trap — filters mobile toggle (`20/5` invoices)

Theme `2301.css` hides `.toggle-btn` on desktop and shows it only inside `@container invoices-filters (width < 480px)`. Label text comes from `.toggle-btn-text:before{content:attr(data-open-text)}`.

**Do NOT:**

- Include bare `button` in the black-button `:is()` rule — this beats `.toggle-btn` on specificity, painting a full-width black bar with no label.
- Force `display:inline-flex!important` on `.toggle-btn` — overrides theme `display:none` on desktop.

**Do:**

- Use `button:not(.toggle-btn)` for primary action selectors.
- `body.module-shop .invoices-layout .invoices-filters-container .toggle-btn{display:none!important}` on desktop.
- Style `.toggle-btn` only inside `@container invoices-filters (width < 480px)`.
- Restore `.toggle-btn-text:before{content:attr(data-open-text)}`.

---

## Checkout / order / invoices — which templates

| Layer | Template | What to change |
|---|---|---|
| Page shell | `20/5` | `$PAGE_ID$='checkout'` and `$PAGE_ID$='invoices'` branches; breadcrumbs; hide sidebar per `$PAGE_ID$`; font in `<head>` |
| Order details | `20/10` | Breadcrumbs, no sidebar, `.nt-order-cart` wrapper |
| Styles | `20/9` | Blocks for `.checkout-layout`, `.order-layout`, `.invoices-layout` |

**When the task touches orders** — edit all three layers, not only checkout CSS. Apply `.checkout-layout`, `.order-layout`, and `.invoices-layout` in the same pass when the change applies to orders UI.

**`<?elseif?>` trap:** uCoz does NOT support `<?elseif?>`. It renders as literal text and both branches may appear — duplicate breadcrumbs and `<h1>`. Use separate `<?if($PAGE_ID$='checkout')?> … <?endif?>` and `<?if($PAGE_ID$='invoices')?> … <?endif?>` blocks.

**`$PAGE_ID$-layout` selector trap:** the layout class (`checkout-layout`, `invoices-layout`) is added to `#layout`, not `<body>`. Use `body.module-shop .checkout-layout …`, never `body.module-shop.checkout-layout`.

---

## Product form `#bdM64` — `20/18`

See [SYSTEM-MARKUP.md — Shop product add/edit form](SYSTEM-MARKUP.md#shop-product-addedit-form--mantable--bdm64-shop--skill).

---

## CSS specificity trap — `.layout .product-card` beats `.product-card`

The default theme stylesheet uses `.layout .product-card{…}` selectors (specificity `0,2,0`). A plain `.product-card{…}` override in `20/9` has specificity `0,1,0` and **loses** regardless of load order.

**Symptom:** you patch `shop.css`, verify via `read_template`, even fetch the live compiled `/_st/shop.css` and see your rule verbatim — and the border/color still does not change on the live page.

**Fix:** prefix overrides with `.layout` to match or exceed competing specificity:

```css
.layout .product-card{border:none}
.layout .product-card .product-img-link{height:auto}
.layout .product-card .product-card-title{color:#191925}
```

Selectors using `:where(…)` in the site CSS contribute **zero** specificity for the wrapped part — those are safe to beat with a plain `.product-card{…}` override.

Before writing overrides, fetch the live compiled CSS bundle (`_st/<name>.css` from a live page's `<head>`) and grep for the exact class being touched to see the competing selector's specificity. Never grep `/.s/src/css/2301.css` — it does not reflect site customizations.

---

## `$META_DESCRIPTION$` trap

On shop home/category pages, `$META_DESCRIPTION$` auto-generates as a **complete `<meta>` tag**, not plain text. See [SYSTEM-MARKUP.md — `$META_DESCRIPTION$` self-wrapping trap](SYSTEM-MARKUP.md#meta_description-self-wrapping-trap-shop) for the full fix.

---

## Pre-save checklist

- [ ] `read_template` on each template before writing.
- [ ] `create_backup` or rely on auto-backup.
- [ ] All variables confirmed via `read_template` or `get_variables` — never invent.
- [ ] `$GLOBAL_AHEADER$` and `$GLOBAL_BFOOTER$` kept in page templates; no duplicate markup.
- [ ] `$POWERED_BY$` only in `BFOOTER`; extra wrappers after `$GLOBAL_BFOOTER$` removed.
- [ ] Shop-only CSS → `20/9`; global-block/shared CSS → `3/3`.
- [ ] No re-wrapping of `$TOP_SOLD$`/`$TOP_VIEW$`/`$LAST_ADD$` in `.goods-list`.
- [ ] `:hover` added for every new/changed link, button, icon button, and accordion control.
- [ ] Catalog CTA links point to `/shop/all`, not `/shop`.
- [ ] `read_template` after saving to verify persistence (byte-diff for large templates).
