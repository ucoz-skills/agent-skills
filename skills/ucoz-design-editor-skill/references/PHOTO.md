# Photo Albums Module (`module_id=9`)

Thin reference for the Photo Albums module. Follow [BLOG.md](BLOG.md) and [SYSTEM-MARKUP.md](SYSTEM-MARKUP.md) for the general content-module chain and `#allEntries` grid behavior.

---

## Key facts

- `module_id=9`
- Module CSS template: `module 9, template 10` — compiled into the site's `/_st/<name>.css` bundle (same verification rules as all CSS — see [BLOG.md § CSS scoping](BLOG.md#css-scoping)).
- Body class on photo-album pages: `body.module-pa` (value of `$MODULE_ID$` for this module is `pa`).

---

## Template chain

Follows the same chain as Blog:

- Full-page shells wrap `$BODY$` and include `$GLOBAL_AHEADER$` + `$GLOBAL_BFOOTER$`.
- Entry view fragment renders each photo/album card inside `$BODY$`.
- Fragments have no `<html>/<head>/<body>` and no `$POWERED_BY$`.
- Full-page shells include `<?if(0)?>$POWERED_BY$<?endif?>` near `</body>`.

---

## `#allEntries` grid

Photo album listing pages use the same `#allEntries` wrapper and the same main-page vs. category-page DOM difference as Blog. **Verify the live DOM** before writing grid CSS — do not assume photo album pages match blog exactly.

Apply the same `:has(table)` / `:not(:has(table))` split pattern documented in [SYSTEM-MARKUP.md — Entry lists & grids](SYSTEM-MARKUP.md#entry-lists--grids).

---

## CSS location

- Site-wide grid/layout rules → `module 3 / template 3`.
- Photo-module-specific rules → `module 9, template 10`.
- Never put photo-module CSS into `shop.css` (`20/9`) or other module CSS files — they do not load on photo pages.

**Always verify a CSS edit went live** by fetching the real compiled `/_st/<name>.css` bundle URL from a live page's `<head>` — never check `/.s/src/css/2301.css`.

---

## Workflow

1. `read_template` on the relevant photo template — confirm exact variables before writing any `$VAR$`.
2. Check the live DOM on the category/album listing page to confirm `#allEntries` structure.
3. For CSS: patch `module 9 / template 10` for module-specific rules; `3/3` for site-wide rules.
4. After saving: re-`read_template` to verify persistence; fetch a live page to verify the compiled CSS.
