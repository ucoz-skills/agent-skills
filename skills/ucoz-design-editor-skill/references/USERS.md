# Users Module (`module_id=4`, `$MODULE_ID$='index'`)

---

## Key facts

- `module_id=4`
- `$MODULE_ID$` value: **`index`** — NOT `users`. All CSS scoping uses `body.module-index`.
- Do not grep for `module-users` — it does not exist.

---

## Template map

| Template | Purpose | Page shell classes |
|---|---|---|
| `4/1` | Login page shell | `.login-form-wrapper` |
| `4/4` | Login form fragment (`manTable`, `.loginField`, `.loginButton`) | — |
| `4/2` | Register page shell | `.register-form-wrapper` |
| `4/9` | Local registration form fragment (`manTable`, `.manFlSbm`) | — |
| `4/12` | uID registration form fragment | — |
| `4/14` | uID additional-fields form fragment | — |
| `4/6` | Edit personal info page shell | `.user-profile-edit-wrapper` |
| `4/10` | Edit personal info form fragment (`manTable`, `.manFlSbm`) | — |
| `4/5` | Personal profile / account dashboard (`.nt-cabinet`, `.nt-cabinet-sidebar`) | `.userdetails-layout` |
| `4/7` | User list page shell | `.items-stat-wrapper`, `.scrollable-table-container`, `.userSchFrm` |
| `4/8` | User comments list page shell | `.breadcrumbs-wrapper`, `.items-stat-wrapper` |
| `4/11` | Personal messages (PM) page shell | plain wrapper; PM UI is `.userpm-layout` |
| `4/3` | Access denied page shell | `.access-denied-wrapper` |

All 8 page shells (`1,2,3,5,6,7,8,11`) have **no global `$GLOBAL_CLEFTER$` sidebar** — full-width layout. **Exception:** `4/5` (own profile) may render a local cabinet sidebar `.nt-cabinet-sidebar`.

---

## `$MODULE_ID$` is `index`

uCoz's Users module internal keyword is `index`. CSS scoping:

```css
body.module-index { … }
```

Never scope to `body.module-users`.

---

## Verify live registration — `#uf-register`

Do not assume `4/9`'s `manTable`/`.manFlSbm` markup is what a live visitor sees. **Always verify the live page first.**

The live `/register` page may render the system's **uID quick-registration widget** instead — markup: `#uf-register`, `.uf-field`, `.uf-text` (input class), `#uf-submit` (submit button), `.register-social-list .login-with` (social buttons).

CSS targets for the live widget (add to `3/3`):

```css
#uf-register .uf-text { … }
#uf-submit { … }
.uf-field label { … }
```

If the classic `manTable` form ever becomes active (uID disabled in Control Panel), the `.manTd2`/`.manFlSbm` CSS covers it too — nothing extra needed for that path.

---

## Two cabinet patterns — full-width vs CLEFTER dashboard

**Full-width profile** (`4/5` default): no global sidebar. Profile page renders a local `.nt-cabinet-sidebar` with nav + SVG icons.

**CLEFTER dashboard pattern** (alternative, also used in Articles Catalog `10/*`): `4/5` outputs `$GLOBAL_CLEFTER$` next to the main area. Gate the dashboard-nav inside `CLEFTER` with `<?if($_IS_OWN_PROFILE$)?>` — otherwise the global block appears on guest profiles and access-denied pages.

When the dashboard pattern is active:

- Style scoped to `body.module-users.aa-account-page`.
- Sidebar collapses to a horizontal scrollable nav on narrow screens — do not hide items or leave full-page horizontal scroll.
- External CSS in `/css/style.css`; do not use module CSS for dashboard-only rules without scoping.

Required preserved variables on own-profile pages: `$_AVATAR_URL$`, `$_NAME$`, `$_USERNAME$`, `$_EMAIL$`, `$_GROUP_NAME$`, `$_CHANGE_DETAILS_URL$`, `$_PM_READ_URL$`, subscription variables, `$MODER_PANEL_RIGHT$`. Never replace with static values.

---

## `patch_template` silent non-persistence — known bug in Users module

`patch_template` against `module 4` templates may report success and create a backup while `read_template` immediately after shows the old content.

**Workaround:** use `update_template` (full-content overwrite) as the recovery path. After one successful `update_template` on a given template, subsequent `patch_template` calls against it work again.

**Always verify writes to Users-module templates** with `read_template` right after — do not trust the success message alone.

`update_template` on a full-page shell (contains `</body>`) requires `$POWERED_BY$` to appear literally in the source — add `<?if(0)?>$POWERED_BY$<?endif?>` near `</html>`. Also check for `$AJAX_JS$\n$AJAX_JS$` duplication after `update_template` and collapse with a follow-up `patch_template`.

---

## Stale font link bug

All 8 page shells may carry a hardcoded `<link>` to a font that has since been changed site-wide. Shop and site-page templates may have been updated while Users-module templates were not.

**Symptom:** "same CSS, different look" between shop pages and Users pages — different font metrics (line-height, x-height) under identical CSS produce height/alignment differences.

**Diagnosis:** fetch a shop page and a Users page; compare the font `<link>` in `<head>`. If they differ, that is the bug.

**Fix:** update the font `<link>` in all 8 Users-module page shells (`4/1,2,3,5,6,7,8,11`) to match the site-wide font.

---

## CSS — markup/CSS mismatch trap

A global block or custom template may output class names that have **zero matching CSS** anywhere. This looks fine in `read_template` but renders as unstyled raw HTML on the live page.

**Check:** grep the live compiled CSS bundle (`_st/<name>.css` from a live page's `<head>`, never `/.s/src/css/2301.css`) for every class name the markup outputs. Do not assume "it has classes, so it's probably styled."

**Design token shortcut:** `body.module-index{--tpl-accent-blue:#191925;--tpl-accent-blue-dark:#2a2a38}` in `3/3` recolors every theme element consuming `var(--tpl-accent-blue)` inside the Users module with one rule.

---

## `shop.css` must be linked in all page shells

Some menu/footer rules may live in `shop.css` (`/_st/shop.css`) rather than the site-wide bundle. Any Users-module page shell that does not link `shop.css` will show those rules missing.

**Correct head links:**

```html
<link rel="stylesheet" href="/_st/my.css" />
<link rel="stylesheet" href="/_st/shop.css?v=091123" />
```

Long-term fix: migrate any non-shop-specific rule from `shop.css` into `3/3`. The `<link>` addition is a pragmatic immediate fix — if this area is revisited, audit `shop.css` and migrate remaining global-element rules to `3/3`.

---

## What the theme already covers by default

Do not re-style these from scratch — they already have theme CSS confirmed in the live bundle:

- `.login-form-wrapper .login-form` (card container, background/radius/padding)
- `.manTable`, `.manTd1`, `.manTd2` (register/edit form row layout — but NOT the actual `<input>`/`<select>`/submit inside them)
- `.breadcrumbs*`, `.items-stat*`, `.paging-wrapper*`, `.scrollable-table-container`, `.userSchFrm`
- `.userpm-layout` + `.userpm-messages-table`

Items that are typically **zero-coverage** and need explicit CSS in `3/3`:

- `.loginField`, `.loginButton`, `.login-form-links a`
- `:is(.register-form-wrapper,.user-profile-edit-wrapper) .manTd2 input/select/textarea` and `.manFlSbm`
- `.register-social-list`
- `.access-denied-wrapper`
- `#uf-register .uf-text`, `#uf-submit`, `.uf-field label`
- Profile section classes (`.profile-*`, `.nt-cabinet*`) — custom markup may have zero matching CSS before explicitly added

---

## Patterns → EXAMPLES.md

CSS patterns for login form, registration widget, profile dashboard, and access-denied page → [../EXAMPLES.md](../EXAMPLES.md).
