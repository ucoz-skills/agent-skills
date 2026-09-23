# Framework contract and MCP workflow

## Contents

- Scope boundary
- Required MCP actions
- Service blocks
- Conditional rendering
- CSS contract
- CSS live verification
- Containing-block trap (`position:fixed`)
- Class/markup mismatch
- Menu variables (`$NMENU_*$` vs `$SMENU_*$`)
- Module views and system markup
- `$POWERED_BY$` production rules
- Search
- Global blocks and popup
- Publication safety
- Active-template migration
- Validation and browser QA
- Failure handling

## Scope boundary

A framework is the source for the complete site design. `skeleton_publish` regenerates all supported templates. Use `templates_tool.patch_template` for a fragment in one template and `templates_tool.update_template` for one complete template. Never use the framework to avoid finding a template ID.

Point edits are not synchronized back into a saved framework. Preserve intentional template-level changes in the framework source before the next publication or accept that they will be overwritten.

## Required MCP actions

Use this order:

1. `skeleton_guide` — current platform instructions.
2. `skeleton_rules` — current framework-level variables.
3. `project_list`/`project_get` or `designs_list`/`design_get` — source base.
4. `skeleton_validate` — read-only server validation.
5. `project_save` — source snapshot and recovery point; it does not affect the live site.
6. Explicit user approval for replacement of CSS and all templates.
7. `templates_tool.create_backup` — manual restore point before the first destructive write.
8. `templates_tool.update_template` for the complete `3/3` stylesheet.
9. `skeleton_publish(confirm=true, pda=0|1)` — this changes the live site.
10. `project_save_active` with the final post-informer source — this marks the source active but does not publish templates.

Do not confuse activation with publication: `project_save_active` changes the active project pointer, while `skeleton_publish` changes the site. When the user asks to install or show the generated design on the site, perform both and leave the final second-pass source active. `skeleton_archive` produces a ZIP. `project_delete` and backup deletion require an exact target and user approval.

## Service blocks

Service comments are parser instructions, not decorative HTML. Preserve their exact form and nesting.

| Block | Result |
|---|---|
| `header` | `$GLOBAL_AHEADER$` |
| `footer` | `$GLOBAL_BFOOTER$` |
| `container1` | `$GLOBAL_CLEFTER$` |
| `container2` | `$GLOBAL_DRIGHTER$` |
| `popup` | popup template using `[TITLE]` and `[BODY]` |
| `config` | JSON design settings |
| `global_x` | `$GLOBAL_X$` |
| `blockN` | reusable inserted block |
| `*_entry_view` | one item in a list |
| `*_entry_page__body` | full item page and comments |
| `new_informer` | one-time informer definition |

Do not assume this table is exhaustive. Call the live guide and inspect a retrieved design/project before each build.

`container1` and `container2` define global side-container contents; they are not additional page bodies. Never put `[BODY]` inside them. Keep the module body in `middle` exactly once, otherwise the generated page may repeat the complete archive/material list through `$GLOBAL_CLEFTER$` and `$GLOBAL_DRIGHTER$`. Side containers may contain verified navigation, account, informer or platform block content. If a retrieved standard design instead uses a `container`/nested `block` family with `TITLE` and `CONTENT`, preserve that exact family rather than mixing both syntaxes.

The framework must contain a valid popup, sufficient real structure, balanced `<?if?>`/`<?endif?>`, and one visible `$POWERED_BY$`. Legacy `{IF}` syntax is forbidden.

Declare `sblock_nmenu` only once, normally in `header`. Prefer its already expanded, idempotent body `<?if($NMENU_1$)?><!-- <bc> -->$NMENU_1$<!-- </bc> --><?endif?>`; do not publish the literal `CONTENT` placeholder. In footer or containers, reuse the verified `$NMENU_1$` code without another `sblock_nmenu` declaration. Repeating the service placeholder can leave the literal word `CONTENT` on the site.

## Conditional rendering

Treat conditions as part of the framework architecture, not as cleanup after markup is written. Inspect the selected standard design for behavioral branches and preserve or deliberately replace the useful ones. Do not remove conditions merely to simplify the HTML.

Plan conditions at two levels:

- Page/module/layout conditions control structures such as a homepage-only hero, module-specific rail, search or shop tools, account controls, and layouts that genuinely differ by page type.
- Item/data/permission conditions control optional cover images, categories, authors, dates, views, comments, rating, prices, downloads, durations, forms, and user capabilities.

Use only the platform syntax `<?if(...)?>...<?else?>...<?endif?>`. Keep every pair balanced. Never use legacy `{IF}` syntax. Verify every condition variable in the live `skeleton_rules`, the retrieved design/project, and, for a targeted template, `get_variables`; a code available in one module or global block may be unavailable in another. In particular, never assume generic page/module identifiers are valid across all generated templates.

Wrap the complete dependent component, not only its text value: if an optional field is absent, its icon, separator, link, label, and empty wrapper must also disappear. Combine related availability and permission checks where interaction depends on both, for example rating availability, comment reading, and comment submission.

Prefer verified module service blocks for stable module-specific markup. Add page-level branches when the surrounding layout or feature set truly changes; avoid one giant condition tree that duplicates the whole document. A conditional custom global block must be declared at its intended conditional render position, not declared at the root and called again inside the condition.

QA both sides of every important branch: value present/absent, logged in/logged out, permission allowed/denied, relevant module/page types, and desktop/mobile. A framework is not complete when only the populated happy path was checked.

## CSS contract

The framework contains structure only. Include this link in `<head>`:

```html
<link type="text/css" rel="stylesheet" href="/_st/my.css?v=1">
```

**Forbidden stylesheet paths:** bare `/my.css`, `/css/my.css`, `my.css`, and stale `/.s/t/<design-id>/` theme assets as the site design stylesheet. Those paths do not receive the compiled `3/3` table the same way `/_st/my.css?v=…` does and produce orphan shells (Search `19/1` is a frequent offender). Local lint fails on missing versioned `/_st/my.css` and on bare `/my.css`.

Do not add `<style>` blocks. For a new design, send the complete stylesheet to `templates_tool.update_template(module_id=3, template_id=3)`. This is a full replacement, not an append operation. Never send a CSS fragment to `update_template`. Read `VISUAL.md` before writing the stylesheet (non-negotiables, tokens, type/spacing floors).

After the first full write, use `read_template` plus `patch_template` for incremental CSS changes. The generated site rewrites the query version automatically; independent custom pages require a manual version bump.

### Site chrome vs module CSS templates

Header, footer, nav, and other site-wide chrome **always** live in `3/3` (`/_st/my.css`). Module-only stylesheet templates exist and load only on that module's pages — use them solely for module-local widgets:

| Module CSS | Typical role |
|---|---|
| `20/9` | Shop (e.g. `.goods-list` mobile overrides) |
| `9/10` | Photo |
| `25/8` | Subscriptions |
| `22/11` | Video |

Rules left only in module CSS will not style `$GLOBAL_AHEADER$` / `$GLOBAL_BFOOTER$` on non-module pages. Derive accents and shared tokens from the site design system in `3/3`; module CSS may refine local grids/forms but must not redefine the brand palette in isolation.

DOM traps for lists, shop grids, and standard `.eBlock` chrome → [SYSTEM-MARKUP.md](SYSTEM-MARKUP.md).

## CSS live verification

**Never trust `/.s/src/css/2301.css`** (or similar stock theme paths) to verify that `3/3` edits went live — that URL is a static theme-default asset and never reflects your stylesheet, no matter how many times you publish or `patch_template`.

**Correct procedure — every time:**
1. Fetch any live page HTML and find the *actual* stylesheet `<link>` it serves — typically `/_st/my.css?v=…` (and module CSS URLs such as `/_st/shop.css` when present).
2. Fetch that exact URL (keep its cache-busting query, or append a fresh `?v=` timestamp) and search for a unique string from your edit.
3. Only if the string is genuinely absent from *that* file should you suspect the save did not go through.

Do not retry `update_template` / `patch_template` or burn backups based on a `2301.css` check. After framework publish, also confirm module CSS URLs on module pages when those tables were edited.

## Containing-block trap (`position:fixed`)

If any ancestor of a `position:fixed` element has a non-`none` **`filter`**, **`backdrop-filter`**, **`transform`**, **`perspective`**, **`contain`** (`layout`/`paint`/`strict`), or **`will-change`** implying one of those, that ancestor becomes the **containing block** for the fixed element instead of the viewport. The overlay then spans only the ancestor's box, not the full screen.

**Typical victim:** a full-screen mobile menu / search drawer nested inside a header with `backdrop-filter` — the "full-screen" panel becomes only as tall as the header bar.

**Checklist before adding these properties to any header/nav/toolbar:**
1. Check whether any `position:fixed` element (off-canvas, modal, cart popup, cookie banner, search overlay) is nested inside that element in the HTML.
2. If yes, either avoid those properties on that ancestor, or move the fixed overlay out of the DOM so it is not a descendant (re-verify JS/checkbox hooks after moving).
3. Live-check on a page where content below the header is visually distinct (e.g. a colorful hero) — a plain white page can hide the bug.

## Class/markup mismatch

Themes and example CSS can drift: markup may use one class while CSS defines a similarly named but different class. After restyle, **verify selectors match live DOM classes** from `get_variables` / browser inspection — not assumed class names from examples or prior projects.

1. Read the actual framework / global-block / module markup and note every class it outputs.
2. Grep `3/3` (and relevant module CSS) for those *exact* class names.
3. If a class in markup has no matching rule, add one. Do not assume a near-miss name is close enough.

Especially important after redesigning header/footer/sidebar and after adapting an `examples/` starter.

## Menu variables (`$NMENU_*$` vs `$SMENU_*$`)

- `$NMENU_<ID>$` expands to **vertical** menu markup (typically `.uMenuV` / `.uMenuRoot`).
- `$SMENU_<ID>$` expands to **horizontal** menu markup.
- The **variable name**, not the control-panel `layout` flag on `menu_create`, decides the rendered orientation. Confirm orientation by the variable you insert, then inspect `.uMenuV` / `.uMenuRoot` live before writing CSS.
- Declare `sblock_nmenu` once (normally in `header`); reuse `$NMENU_1$` (or `$SMENU_1$` when horizontal chrome is intentional) elsewhere without a second service placeholder.
- **Never** wrap `$NMENU_*$` / `$SMENU_*$` inside your own `<ul>` — the platform already emits a nested list. Style the real `.uMenuRoot` tree; do not style only an outer decorative wrapper and ignore the platform nodes.
- Hide empty submenu lists; empty `<ul>` under a leaf item often still exists in the DOM.

### DOM reset pattern

After confirming the live DOM for `$NMENU_1$` or `$SMENU_1$`:

```css
/* after confirming live DOM for $NMENU_1$ or $SMENU_1$ */
.site-nav .uMenuV { display: contents; } /* or reset as needed */
.site-nav .uMenuRoot {
  display: flex;
  gap: /* token */;
  list-style: none;
  margin: 0;
  padding: 0;
}
.site-nav .uMenuRoot > li { list-style: none; }
.site-nav .uMenuRoot > li > ul:empty { display: none; }
/* never wrap $NMENU_$ inside your own <ul> */
```

Adapt selectors to your shell class names; keep the idea: neutralize platform wrappers, flex the real root list, and suppress empty submenu `ul`s.

## Module views and system markup

Separate the shell from repeated content. An archive/category/home shell renders `[BODY]`; a verified `*_entry_view` renders one list item; a verified `*_entry_page__body` renders one full material and comments; related items, editors, and forms remain separate templates. Use only block prefixes present in `skeleton_guide`, `design_get`, or `project_get`. Typical families include News, Blog, Publ, File catalog, Site directory, Board, Photo, Games, FAQ, Guestbook, Video, Forum, and Shop, but a family listed here is not proof that the live parser supports it.

For every enabled content module:

- provide a coherent archive/card view and, where supported, a coherent full-material view;
- preserve the module's verified title, URL, image, author, category, date, views, comments, rating, and form variables;
- wrap an optional value together with its icon, separator, label, and link;
- place media above text on narrow screens unless a compact list is intentional;
- preserve native comments, editors, Shop actions, Forum states, IDs, and JavaScript hooks;
- use the native rating generator described in `RATING.md`, never decorative static stars.

Variables differ even between similar modules. News may expose `$ENTRY_NAME$` in a full page while Publ exposes `$ENTRY_TITLE$`; retrieve the working source and call `get_variables` for targeted templates. Passing `skeleton_validate` does not prove that a syntactically valid code will render data in every generated module.

A full-material block should keep the title, cover, content, native rating, metadata, and comments as distinct regions. This is a structural pattern, not a promise that these variable names exist in every module:

```html
<!-- <news_entry_page__body> -->
<article class="entry-page">
  <nav class="breadcrumbs" aria-label="Хлебные крошки"><!-- verified path levels and one shared SVG separator --></nav>
  <header class="entry-page__header"><h1>$ENTRY_NAME$</h1></header>
  <?if($COVER_IMAGE$)?><figure class="entry-page__hero"><img src="$COVER_URL$" alt="$ENTRY_NAME$"></figure><?endif?>
  <div class="entry-page__content">$MESSAGE$</div>
  <footer class="entry-page__footer">
    <?if($RATING$)?><div class="entry-page__rating"><?$RSTARS$('24','','1','float')?></div><?endif?>
    <div class="entry-page__meta"><!-- verified date, views, category and author values with semantic SVG icons --></div>
  </footer>
  <?if($COM_CAN_READ$)?><section class="entry-comments">$COM_BODY$</section><?endif?>
  <?if($COM_CAN_ADD$)?><section class="entry-comment-form">$COM_ADD_FORM$</section><?endif?>
</article>
<!-- </news_entry_page__body> -->
```

Confirm every variable and condition against the retrieved News full-page source. Adapt the same regions and visual language to each supported module rather than copying News variables into Blog, Publ, Photo, Shop, or other blocks.

Not every template has a framework service block. When structure is acceptable, style standard uCoz markup through `3/3`, including verified `.breadcrumbs`, `.eBlock`, `.eTitle`, `.eMessage`, `.eDetails`, `.e-category`, `.e-reads`, `.e-author`, `.ed-title`, `.ed-value`, comments, tables, forms, pagination, and `_uWnd` classes. Change a template only when semantic order, shell, or required structure cannot be made consistent with CSS. Grid and shop traps for that markup → [SYSTEM-MARKUP.md](SYSTEM-MARKUP.md).

Breadcrumb consistency is a hard contract. Use one wrapper/item class system, typography, gaps, outer gutter, rule treatment, responsive behavior, and one `currentColor` SVG separator in archives, categories, full materials, Search, system pages, and custom Pages. Only path depth and verified labels/URLs may change. Keep an optional separator inside the same condition as its optional level. If standard markup differs, make it visually identical with shared CSS or perform an authorized targeted template migration.

## `$POWERED_BY$` production rules

`$POWERED_BY$` is the mandatory platform copyright link. Production rules (from Design Editor GBLOCKS):

- **One visible instance** via the footer path (`BFOOTER` / framework `footer` → `$GLOBAL_BFOOTER$`).
- **Never hide** with CSS (`display:none`, `opacity:0`, matching background color, `height:0`, off-screen, overlay).
- Full HTML shells that already render copyright through `$GLOBAL_BFOOTER$` may keep a validator-only stub near `</body>`:

```html
$GLOBAL_BFOOTER$
<?if(0)?>$POWERED_BY$<?endif?>
```

- **Never duplicate a visible** `$POWERED_BY$` after `$GLOBAL_BFOOTER$` — the second occurrence renders empty and leaves a styled blank bar under the footer.

## Search

Always migrate Search after framework publish. Treat Search as high-risk chrome — stock shells often ship orphan `/my.css` and foreign layout classes.

### Shell `19/1` (`/search/`, high-risk)

- Match a known-good live shell: versioned `/_st/my.css?v=…`, same fonts, `$GLOBAL_AHEADER$` / `$GLOBAL_BFOOTER$`, theme body/layout classes — **not** a foreign orphan with bare `/my.css`.
- Keep the system theme reference the good shells use (`/.s/src/css/2301.css` or the site's `/_st/…` pattern) consistently, but **never** rely on `2301.css` alone without `/_st/my.css` (see [CSS live verification](#css-live-verification)).
- Prefer no sidebar on Search (remove `CLEFTER` / foreign `#sidebar` blocks unless the design intentionally keeps a rail).
- Browser-test live `/search/?q=…` on desktop and mobile after every redesign.

### Result rows `19/2`

- Style via `3/3` (`.eBlock` family or design cards); no second stylesheet path.
- Prefer a consistent card/list treatment: optional thumb, title link, snippet, meta — reuse archive card tokens where possible.

### Header search overlay (if present)

Some designs include a search toggle/drawer in `$GLOBAL_AHEADER$`. **Do not invent IDs or JS hooks** from memory — verify the live DOM (and any theme `main.js` contracts) before styling or wiring:

- Confirm toggle button, panel/drawer root, close control, and form `action="/search/"` + query field name on the live site.
- Panel is typically `position:fixed`; keep it outside ancestors that create a containing block (see [Containing-block trap](#containing-block-trap-positionfixed)).
- Do not add a duplicate click listener if theme JS already toggles the panel open class.

Style Search chrome and results primarily through `3/3`. Details and list DOM traps → [SYSTEM-MARKUP.md](SYSTEM-MARKUP.md).

## Global blocks and popup

Declare a framework global block as `global_` plus 1–10 lowercase Latin letters. No digits, underscore inside the name, hyphen, uppercase, or Cyrillic. A declaration such as `global_notice` becomes `$GLOBAL_NOTICE$`.

The declaration is also the render position: tmaker replaces the marked block with `$GLOBAL_NOTICE$` exactly where the markers stand. It is not a neutral registry declaration. Put the marked block inside the intended header, condition, rail, or module location and do not add a second explicit `$GLOBAL_NOTICE$` call elsewhere. A root-level declaration renders on every generated full page; a root declaration plus an explicit call renders twice. For example, place `global_icons` inside `header`, and place a homepage-only hero inside its verified page condition.

Global blocks use only variables valid in their rendering context. Module-entry variables do not become globally available.

In `popup`, keep `[TITLE]` and `[BODY]` unchanged. Style popup and `_uWnd` system markup in `3/3`; preserve platform JavaScript hooks and close controls.

## Publication safety

Before `skeleton_publish`:

- explain that all generated templates will be overwritten;
- explain that `3/3` will be replaced in full for a new design;
- obtain explicit approval covering the intended desktop/PDA target and, for informers, both publication passes;
- run local lint and server validation;
- save the framework as a project;
- create a manual template backup before replacing CSS;
- preserve at least 2–3 useful backups whenever backup cleanup is required.

Treat the framework, CSS, and targeted full templates as UTF-8 end to end. When PowerShell is used to load a BOM-less source for an MCP call, specify `Get-Content -Raw -Encoding UTF8`; the Windows PowerShell default may decode UTF-8 as the active ANSI code page and publish mojibake such as `Р...`/`С...` even though both validators accept the HTML. Before writing, assert that a known Cyrillic phrase is readable in the exact string passed to MCP. After writing, read back a generated template and check the same phrase again.

Without approval, generate and validate only.

## Active-template migration

A successful `skeleton_publish` does not prove every live system template was regenerated. Full redesign approval must cover both framework publication and targeted migration of active/system templates that remain standard or stale. After the final publication:

1. Call `modules_list`, then `templates_tool.list_modules`. Inventory Pages, Users, every active content module, Search, common system/error templates, globals, comments, forms, related-material views, and other shared partials.
2. Read every mapped template, not only IDs returned in `affected_templates`. Always inspect Search `19/1` and `19/2`; current tmaker sources do not verify a `search_entry_view`, so the result item may remain standard.
3. Classify each template as current theme, standard inner markup safely handled by shared CSS, or stale/structurally incompatible markup requiring a targeted change.
4. For each stale template, call `get_variables`, preserve required placeholders and behavior, validate the candidate, then use `patch_template` for a narrow structural edit or `update_template` for one complete template. Do not republish the entire framework to fix one missed system template.
5. Read changed templates back. A full document must use the current shell and versioned `/_st/my.css?v=...`; a partial must not duplicate `<html>`, `<head>`, or the stylesheet link. Check UTF-8, no old `/.s/t/<design-id>/` assets, no literal `CONTENT` or unknown variables, and exactly one visible `$POWERED_BY$` across a rendered full document.
6. **Full-page shell chrome audit** (mandatory): against one known-good live shell, verify every full HTML shell of enabled modules — (1) CSS only `/_st/…`, never `/my.css`; (2) matching font `<link>`s; (3) `$GLOBAL_AHEADER$` / `$GLOBAL_BFOOTER$`; (4) body/layout classes match the theme, not a foreign skeleton; (5) no foreign brand strings; (6) one visible `$POWERED_BY$` (shells may use `<?if(0)?>$POWERED_BY$<?endif?>` when BFOOTER already renders it). Treat Search `19/1` as high-risk. **Users shells** to inventory briefly: `4/1` login, `4/2` register, `4/3` access denied, `4/5` profile, `4/6` edit info, `4/7` user list, `4/8` user comments, `4/11` PM (forms `4/4`, `4/9`, `4/10`, `4/12`, `4/14` are fragments — style via `3/3`, verify live `#uf-register` on register). Fragments (`19/2`, entry views) are styled via `3/3`, but their parent shell must still pass.
7. Browser-test a representative URL from every active family on desktop and mobile, including `/search/?q=…`. A 200 response or correct CSS URL alone is not proof of migration.

If a template has the current shell but standard inner markup, prefer narrowly scoped shared CSS. Old shells, scripts, compiled globals, legacy assets, or unrelated blocks are structural defects and require a targeted template update within the approved migration. Record every template ID changed after publication so a future framework publication can preserve or intentionally reapply it.

## Validation and browser QA

Before publication, run `python scripts/lint_skeleton.py <framework.html>`, inspect duplicate IDs, placeholder copy, hard-coded test links, unverified variables, and mojibake, then call `skeleton_validate` and process every `details[]` entry. Save the validated source with `project_save`.

After publication, record `success`, `affected_templates`, informer results, project IDs, desktop/PDA target, and backup result. Read back a generated global and module template; compare a known Cyrillic phrase against the UTF-8 source. Then complete the active-template migration above.

Test at 320, 375, 768, 1024, and wide desktop widths:

- homepage/custom Page, archive, category, full entry with comments, Users/login, Search, popup, and every enabled high-value module;
- no overflow, clipped text, media overlap, double borders, empty rails, inconsistent gutters, or unexplained header offset;
- **menu DOM**: live `.uMenuV` / `.uMenuRoot` reset, no custom `<ul>` wrapping `$NMENU_*$`/`$SMENU_*$`, empty submenu `ul`s hidden, drawer items visible;
- **`#allEntries` on archive/category**: grid on `td.archiveEntries` (or `:has(table)` split), not a single squeezed column;
- **button / CTA specificity**: theme buttons keep accent colors; no bare `a { color: inherit }` (or equally broad link reset) beating CTA link colors;
- working desktop navigation, one mobile close control, visible drawer items, keyboard operation, focus states, and reduced-motion behavior;
- working Search on live `/search/?q=…`, authentication, basket, forms, pagination, comments, editors, and native module actions;
- identical breadcrumbs everywhere; **copyright line** visible — one `$POWERED_BY$` via BFOOTER, not CSS-hidden and not a duplicate empty bar;
- informer creation in the control panel after pass one and correct placement with no tail after `</html>` after pass two;
- one five-star native rating row with correct normal/hover/selected states, tooltip, and voting interaction;
- the exact live `/_st/*.css?v=...` contains a unique selector from the new theme and no rendered page loads stale design assets;
- custom Pages share the theme shell/CSS/icon system and render one effective title/description without literal or duplicated SEO codes.

Report tested URLs, viewport sizes, informer codes, rating behavior, migrated template IDs, and any check that still requires the user's authenticated session.

## Failure handling

- `VALIDATION_ERROR`: process all `details[]` entries.
- `MISSING_POPUP`: restore exact popup markers and placeholders.
- `MISSING_POWERED_BY`: restore a visible code in the footer path.
- `SKELETON_TOO_SHORT`: use a real design base; do not pad with meaningless comments.
- `UNBALANCED_CONDITION`: repair every condition pair.
- `DUPLICATE_CODE`: find duplicate service blocks/codes.
- backup limit: list backups and ask before deleting an exact backup.
- styles appear stale: inspect the live `/_st/*.css?v=...` URL before rewriting anything.
- literal `$NAME$` on a page: the variable is unavailable in that module; retrieve the module template/variables and correct it.
- readable local Cyrillic becomes `Р...`/`С...` after publication: stop further writes, compare the local UTF-8 source with the MCP readback, reload the source explicitly as UTF-8, validate again, and republish only after backup capacity and approval are confirmed.
