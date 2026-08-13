# Global Blocks (`module_id=1`)

---

## What a global block is

A global block (`module_id=1`) is an **HTML fragment** — no `<html>`, `<head>`, or `<body>`. It contains markup with uCoz variables and conditionals.

`$GLOBAL_<ID>$` in a page template is replaced by the fully rendered HTML of that block. Editing the block updates every page that references it immediately.

---

## Creating a custom global block

Use when a reusable section (reviews, FAQ, promo strip, CTA banner) must appear on multiple page templates with identical markup.

**Workflow:**

1. Plan the block ID — short uppercase name (e.g. `REVIEWS`, `FAQ`, `SHCTA`). Check `list_modules` to avoid collisions.
2. Create — `gblock_create` with `id=<BLOCK_ID>` and `content=<HTML fragment>`.
3. Style — add CSS classes to the **site-wide CSS template** (`module_id=3, template_id=3`) — not to module CSS.
4. Insert — add `$GLOBAL_<BLOCK_ID>$` at the target position in each page template.
5. Verify variables — call `get_variables` for the global block context; only use variables valid there.

Custom blocks follow the same `$GLOBAL_<ID>$` naming pattern as built-in blocks.

---

## Standard built-in blocks

| Variable | Block ID | Contains |
|---|---|---|
| `$GLOBAL_AHEADER$` | AHEADER | Header: logo, nav, search, cart |
| `$GLOBAL_BFOOTER$` | BFOOTER | Footer: copyright, contacts, `$POWERED_BY$` |
| `$GLOBAL_CLEFTER$` | CLEFTER | Sidebar (first container) |
| `$GLOBAL_SLIDER$` | SLIDER | Slider (usually homepage) |
| `$GLOBAL_CONTACTBOX$` | CONTACTBOX | Contact form/block |
| `$GLOBAL_TEAM$` | TEAM | Team block |
| `$GLOBAL_REVIEWS$` | REVIEWS | Reviews block |
| `$GLOBAL_ADVANTAGES$` | ADVANTAGES | Advantages strip |
| `$GLOBAL_LANDBOX$` | LANDBOX | Landing block |
| `$GLOBAL_FAQ$` | FAQ | FAQ block |
| `$GLOBAL_SHSLIDER$` | SHSLIDER | Store category slider init template |

Confirm current state with `list_modules` / `read_template` on `module_id=1`.

---

## Styling global blocks

Global blocks are **markup-only** — no `<style>` tags inside them.

CSS for global block classes must exist in a stylesheet that loads on **every page** where the block appears:

| Scope | CSS template | Loads on |
|---|---|---|
| Site-wide (header, footer, shared sections) | `module_id=3, template_id=3` | All pages using the theme |
| Module-specific (shop product cards, etc.) | e.g. `module_id=20, template_id=9` | Shop pages only |

**Trap:** CSS added only to `20/9` (shop.css) will NOT style `$GLOBAL_AHEADER$` or `$GLOBAL_BFOOTER$` on non-shop pages (contact, news, users, etc.).

**Fix:** styles for any block referenced from multiple modules → **always patch `module 3 / template 3`**.

**Checklist after editing a global block:**

1. List every class name the block HTML outputs.
2. Grep the live compiled CSS bundle (`_st/<name>.css` — from a live page's `<head>`) for those class names.
3. If missing → `patch_template` on `3/3`.
4. Test both a shop page and a non-shop page.

Never verify against `/.s/src/css/2301.css` — that is a static theme default and never reflects `3/3` edits.

---

## `$POWERED_BY$` and global blocks

`$POWERED_BY$` must remain **visible** — never hide with CSS (`display:none`, `opacity:0`, color matching, `height:0`, off-screen positioning, or overlaying).

**Placement rules:**

- Canonical location: `BFOOTER` — inside or immediately after the footer markup.
- Only one active render per page. If `$POWERED_BY$` appears in both `BFOOTER` and a page template, the first occurrence renders it and the second is empty — leaving a styled but blank bar.

**If the validator requires `$POWERED_BY$` in a page template** that already gets it via `$GLOBAL_BFOOTER$`, use:

```html
$GLOBAL_BFOOTER$
</div>
<?if(0)?>$POWERED_BY$<?endif?>
```

**Never** use `<div style="text-align:center;padding-block:8px 24px">$POWERED_BY$</div>` after `$GLOBAL_BFOOTER$` — creates a white bar below the dark footer and a blank duplicate `$POWERED_BY$`.

---

## `AHEADER` and `BFOOTER` invariant — non-negotiable

- The only production header lives in `1/AHEADER`; the only production footer (including the single visible `$POWERED_BY$`) lives in `1/BFOOTER`.
- Every public full-page template must render exactly `$GLOBAL_AHEADER$` and `$GLOBAL_BFOOTER$` — never copy header, navigation, account links, footer, or copyright markup into module/page templates.
- Build navigation only from the configured menu variable inside `AHEADER` (e.g. `$NMENU_1$`), never from module-local hardcoded menu HTML.

---

## Variable scope in global blocks

Global blocks render in a site-wide context. Module-specific variables are NOT available inside global blocks.

| Works in global blocks | Usually NOT available |
|---|---|
| `$SITE_NAME$`, `$HOME_PAGE_LINK$`, `$YEAR$` | `$SHOP_TOTAL_GOODS$`, `$SHOP_TOTAL_CATS$` |
| `$NMENU_1$`, `$BASKET$`, `$SEARCH_FORM$` | `$CAT_NAME$`, `$BODY$`, `$TOP_SOLD$` |
| `$POWERED_BY$` | Module entry/list variables |

**Informer variables trap:** `$BASKET$`, `$LOG_FORM$`, `$SHFEED$`, etc. **always render their full system widget** — they never evaluate to an empty string in practice. An `<?if($BASKET$)?><?else?>custom fallback<?endif?>` pattern almost always takes the first branch on every page load.

Fetch a live page, read the informer's actual rendered HTML, and style those real classes directly in `3/3` with `!important` as needed — do not rely on the `<?else?>` branch being reachable.

Same caution applies to any other informer variable documented as returning ready-made HTML (`$LOG_FORM$`, `$SHFEED$`, `$POLL$`, `$ONLINE_COUNTER$`, `$CURRENCY$`, `$RATE$`) — treat `<?if?>` as "is the feature enabled," not "is there content to show."

---

## Anti-patterns

| ❌ Wrong | ✅ Correct |
|---|---|
| Copy-paste reviews/FAQ HTML into `20/1`, `20/2`, and `2/0` | Create/edit one global block; insert `$GLOBAL_REVIEWS$` everywhere |
| Style `.site-footer` only in `20/9` (shop.css) | Patch `module 3 / template 3` for footer/header/shared blocks |
| Add `$POWERED_BY$` in both `BFOOTER` and a shop home template | Single `$POWERED_BY$` in `BFOOTER`; remove duplicates |
| White `.container` with padding after footer for copyright | Dark `.site-footer-bottom` inside or right after footer in `BFOOTER` |
| Use `$SHOP_TOTAL_GOODS$` inside a global block | Keep shop stats in page template; global block for static shell only |
| Assume global block CSS "just works" after patching shop.css | Verify class names in live bundle; test non-shop pages too |
| Put `<style>` inline in `AHEADER` "just for header stuff" | Patch `module 3 / template 3` |
| Trust `<?if($BASKET$)?><?else?>custom<?endif?>` to hide the widget | Fetch a live page, read the informer's real rendered HTML, style those classes |

---

## Auth modal → examples

For a fully custom login modal replacing the `_uWnd` popup — see [../EXAMPLES.md#auth-modal](../EXAMPLES.md#auth-modal).

Brief summary:

- Add modal HTML at the very end of `AHEADER` (after all `</script>` tags).
- Replace `href="$LOGIN_LINK$"` with `href="#" data-auth-open="login"`.
- JS delegated click handler opens/closes the modal; override `window.loginPopupForm` so uCoz-rendered links still trigger the modal.
- POST to `/index/sub/` with `credentials:'same-origin'`.
- CSS in `3/3` under `/* ===== NT AUTH MODAL ===== */`.
- **Always declare `border:none!important; outline:none!important`** on `.nt-modal` — some global CSS resets inject a border.
