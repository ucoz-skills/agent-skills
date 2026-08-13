# Template Architecture

## Page template structure

Full page templates (e.g. "Entry page", "Archive page") are **complete HTML documents** — they include `<html>`, `<head>`, `<body>`, scripts, styles, and module content variables.

There is **no** generic `$MODULE_CONTENT$` or `$PAGE_CONTENT$` variable. Module content is embedded via module-specific variables (`$BODY$`, `$MESSAGE$`, `$ENTRY_NAME$`, `$COM_BODY$`, etc.) — confirm the exact ones via `get_variables`.

The `<link href="/.s/src/css/2301.css">` in a template's `<head>` is a system CSS reference. **Never remove or edit this link directly.** At serve time uCoz replaces it with the site's compiled bundle (`/_st/<name>.css?v=…`). Fetching `/.s/src/css/2301.css` directly will never show your `module 3/3` edits — see [CSS verification](#css-verification).

---

## Finding where an element actually lives

Header, navigation, footer, and sidebar are almost always in **global blocks**, not in page templates — the page template only holds the placeholder variable.

**Algorithm:**
1. `read_template` on the page template.
2. Locate the target element.
3. If it is a `$GLOBAL_XXX$` placeholder → the element lives in that global block. Read and edit `module_id=1, template_id=XXX`.
4. If it is part of repeated entries inside `$BODY$` / `$RELATED_ENTRIES$` → the element lives in the module's "Entry view" template, not the page template that outputs the variable.
5. If it is inline in the page template → edit the page template directly.

---

## Template chains within a content module

Content modules (News, Blog, Publications, etc.) split rendering across multiple templates.

| You want to change | Edit this template | Not this one |
|---|---|---|
| How each entry looks in any entries list | "Entry view" | The list page template — it only outputs `$BODY$` |
| Page layout around the entries list | The list page template | "Entry view" |
| How a single opened entry is displayed | "Entry page (with comments)" | — |
| Fields/layout of the add/edit form | "Entry add/edit form" | "Entry add/edit page" — it is just the shell |
| How related entries are rendered | "Related entries view" | The template that calls `$RELATED_ENTRIES$` |

**List pages → Entry view.** "Archive page", "Category page", "Section page", and the module's main page all output individual entry markup from the single "Entry view" template. To restyle entries in any of these lists, edit "Entry view".

**Entry page content variables.** The "Entry page (with comments)" template renders one entry via `$MESSAGE$` (full text), `$BRIEF$`, `$COVER_IMAGE$`, `$ATTACHMENTS$`, etc.

**System language-string marker.** The pattern `<!--<sNNNN>-->…<!--</s>-->` is a uCoz system marker — **keep it intact** when patching around it.

**Related entries.** Call via `<?$RELATED_ENTRIES$(limit, $OTHER1$)?>` where `$OTHER1$` is an extra field for comma-separated entry IDs. Default selection is by tags; user-defined selection uses the IDs from `$OTHER1$`.

---

## Don't duplicate global block markup into page templates

If something must appear on every page, put it in a global block (`AHEADER` / `BFOOTER` / `CLEFTER`), not copy-pasted into every individual page template.

---

## Module CSS templates

Several modules have their own CSS template separate from site-wide `module 3/3`:

| Module | CSS template |
|---|---|
| Photo albums (9) | module 9, template 10 |
| Online store (20) | module 20, template 9 |
| Video (22) | module 22, template 11 |
| TgShop (24) | module 24, template 5 |
| Subscriptions (25) | module 25, template 8 |

---

## Key `$PAGE_ID$` values

Available in global blocks and templates for per-page branching.

| `$PAGE_ID$` | Page |
|---|---|
| `sitePage1` | Homepage (Page editor) |
| `sitePage2`, `sitePage3`, … | Other Page-editor pages |
| `archive` | Archive/listing page |
| `entry` | Single entry page |
| `category` | Category page |
| `home` | Module's home page |
| `login` | Login page |
| `userdetails` | User's personal page |
| `wishlist` | Wishlist (store) |

## Key `$MODULE_ID$` values

| `$MODULE_ID$` | Module |
|---|---|
| `index` | Page editor |
| `np` | News |
| `bl` | Blog |
| `forum` | Forum |
| `shop` | Online store |
| `search` | Search |
| `pa` | Photo albums |

---

## CSS: where styles live

The site-wide CSS template is `module_id=3, template_id=3` ("Stylesheet").

**Save method rule:** if the CSS template already has content, **always use `patch_template`** — for any number of rule changes. Reserve `update_template` for the only case where it is actually cheap: the template is **empty or has no styles yet**. Once it has real content, every future edit goes through `patch_template`.

**Rule 0 — read before you decide.** Before any CSS task, read `module 3/3` and look for existing classes that already solve the problem. Only add a new class if nothing suitable exists. Warn the user before reading — the file can be 1000+ lines.

---

## CSS design tokens

All project-defined CSS custom properties must be declared in a single canonical `:root` block before being used anywhere.

**Required workflow for any new reusable value:**
1. Find the existing project `:root` block. If it does not exist, create one at the beginning of the stylesheet.
2. Add the semantic token there (e.g. `--aa-color-ink`, `--aa-space-24`, `--aa-radius-card`).
3. Reference it in component classes only via `var(--…)`.
4. Reuse or update the token when the value changes; do not scatter new hard-coded values.

**Forbidden for project styles:**
- Declaring a new reusable `--aa-*` variable inside a component, modifier, page, or media-query selector.
- Using `var(--aa-…)` without declaring that token in the project `:root`.
- Introducing a second local token registry for a module or template.

Do not copy system/platform-supplied variables into the project `:root`.

---

## Never put `<style>` in an HTML template (default rule)

Styles belong in `module 3/3`, not in page templates or global blocks.

**Exception:** if you are editing the page/global-block template where `$MFORM_<ID>$` is output, on-site styling for that form goes **in that same template** (scoped `<style>`, wrapper classes) rather than the shared CSS template. The mail email template (`mail_save_template`) has its own inline HTML styling, separate from module 3 entirely.

---

## CSS scenarios

**A — Small scoped change (1–10 rules) when `3/3` is blocked**

Prefer still trying `patch_template` on `3/3` first. If it does not persist (re-`read_template` empty), recovery options:

1. One FTP CSS file linked from the relevant shell / `AHEADER` (best recovery for site-wide rules)
2. Temporary `<style>` in `1/AHEADER` (after `</header>` in the block body — AHEADER is not `<head>`; avoid FOUC by keeping the block tiny)

Do **not** treat AHEADER `<style>` as the normal home for CSS. Default remains scenario B.

**B — New reusable component, or editing existing rules (any size)**

Use `patch_template` on `module 3/3` with a unique anchor fragment. For a large refactor, run multiple scoped `patch_template` calls (one per distinct change) rather than reaching for `update_template`.

```
read_template(module_id=3, template_id=3)
→ copy a unique anchor fragment
→ patch_template(module_id=3, template_id=3,
    code_search=<anchor>, code_paste=<anchor + new/modified CSS>)
→ repeat for further changes
```

**C — Empty template (new site or theme with no existing content)**

`update_template` is the right tool here — nothing to lose. Once it is populated, all subsequent edits go back to scenario B.

---

## CSS verification

**Never check `/.s/src/css/2301.css` directly** to verify that an edit went live — it is a static theme-default asset that never reflects any `module 3/3` edits, no matter how many times `patch_template` is called.

**Correct procedure — every time, no exceptions:**
1. Fetch any live page's HTML and find the *actual* stylesheet `<link>` it serves:
   ```
   fetch('https://<domain>/<some-page>?v=' + Date.now())
     → search the returned HTML for <link rel="stylesheet" href="/_st/...css?v=...">
   ```
2. Fetch that exact URL (with its cache-busting query, or append a fresh `?v=` timestamp) and search it for a unique string from your edit.
3. Only if the string is genuinely absent from *that* file should you suspect the save did not go through.

Do not retry `patch_template` or burn backups based on a `2301.css` check.

---

## Global block CSS must go in module 3/3

Classes used in `$GLOBAL_AHEADER$`, `$GLOBAL_BFOOTER$`, and any custom `$GLOBAL_<ID>$` block that appears on non-module pages **must** be in `module 3/3`, not only in a module CSS file. Module CSS (e.g. `module 20/9`) loads only on pages of that module — rules left there will be missing on every other page type, causing the header/footer to render differently depending on the active module.

**Diagnostic signal:** "menu/footer looks different on module X" → check (a) whether that module's CSS is linked on those pages, and (b) whether the rule actually belongs in `module 3/3` instead.

---

## CSS markup/class mismatch trap

Real-world themes can have drifted: a global block may use one class name in its HTML while the CSS template only defines a similarly-named but different class — left over from an earlier version. The mismatch means that UI renders with **no styling at all**.

**Verify before assuming coverage:**
1. Read the actual template/global-block markup and note every class name it outputs.
2. Grep the CSS template for those *exact* class names.
3. If a class used in markup has no matching CSS rule, add one. Do not assume a similarly-named rule is close enough.

This check is especially important after redesigning global blocks (header/footer/sidebar).

---

## `filter`/`backdrop-filter`/`transform`/`will-change` on an ancestor silently breaks `position:fixed` overlays

If any ancestor of a `position:fixed` element has a non-`none` `filter`, `backdrop-filter`, `transform`, `perspective`, `will-change` (implying one of those), or `contain:layout/paint`, that ancestor becomes the **containing block** for the fixed element instead of the viewport. The fixed element then spans only the ancestor's box, not the full screen.

**Typical victim:** a full-screen mobile menu overlay (`position:fixed; top:0; bottom:0`) nested inside a header that has `backdrop-filter`. The "full-screen" menu becomes only as tall as the header bar.

**Checklist before adding these properties to any header/nav/toolbar:**
1. Check whether any `position:fixed` element (mobile menu, modal, cart popup, cookie banner, etc.) is nested inside that element in the HTML.
2. If yes, either avoid `filter`/`backdrop-filter`/`transform` on that ancestor, or move the fixed element out of the DOM so it is not a descendant of the filtered ancestor (re-verify all JS hooks still fire after moving).
3. Live-check on a page where content directly below the header is visually distinct (e.g. a colorful hero) — that is the scenario where this bug becomes obvious; a plain white page can hide it completely.
