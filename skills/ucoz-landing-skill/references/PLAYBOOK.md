# uCoz Landing Skill Playbook

Version: **1.4**

Detailed instructions for an AI agent building uCoz landing pages via MCP.

---

## 1) Goal and scope

This playbook is mandatory for tasks such as:
- "build a landing page",
- "update the home page as a landing",
- "create a promo page on the home page",
- "add a lead form/menu/SEO on the home page".

In this scenario, a uCoz landing page = personal template of the home page (`page_id=1`, `page_owntmpl=1`).
Landing HTML is published in `page_tmpl`, not in `page_message`.

---

## 2) Core principles (mandatory)

1. Work only through MCP tools (`templates_tool`, `ftp_tool`, `modules_tool`) — no manual assumptions. For the home page: read via `page_list`, `page_get`; first publish via `page_update` with full `page_tmpl`; **any HTML edits to an existing landing** via `patch_template` (`module_id=2`, `template_id=1`), **not** `update_template` and **not** full `page_tmpl` rewrite via `page_update`. SEO fields (`page_meta_*`) may be updated via `page_update` without `page_tmpl` for targeted meta-only changes.
2. Use `validate_template` only when the user explicitly asks — do not run automatically.
3. Do not remove or break uCoz variables and system constructs.
4. Do not remove `$POWERED_BY$`; it must appear after `</body>`.
5. Do not use a static year in the footer; use `$YEAR$`.
6. For menus, use only menu actions + `$NMENU_N$` variables (this playbook standardizes vertical menus).
7. Create a contact form only if the user explicitly requested a form in the prompt; if needed — use only `mail_*` actions + `$MFORM_N$` output. Before `mail_*`, verify the mail forms module is active (`modules_tool`); if inactive — install via `module_install` yourself; do not ask the user to enable the module in the panel.
8. Put landing styles in a separate `style.css` file and upload to `/css` on the site.
9. Put landing scripts in a separate `script.js` file and upload to `/js` on the site.
10. Version CSS/JS URLs (`?v=<ASSET_VERSION>`) and bump the version on every file change.
11. The landing must be responsive: correct behavior at least at 320/375/768/1024+ px.
12. Add Open Graph and Twitter Card meta tags in `<head>`.
13. Form field labels must be SEO-semantic and match page intent.
14. Do not use emoji as icons in the landing UI; use SVG icons (inline SVG or SVG files).
15. Treat `f1` and `f2` as system-required fields: `f1` = email, `f2` = name. Do not delete or replace them; only change labels.
16. Keep text in readable UTF-8 in HTML; do not encode Cyrillic (or other text) as HTML entities like `&#1057;...` unless technically required.
17. After adding a mail form, override the default uCoz success modal window styles to match the landing. The window uses a 9-slice border model — reset `background-image` on all 9 frame parts (`.xw-tl`, `.xw-tr`, `.xw-tc`, `.xw-ml`, `.xw-mr`, `.xw-mc`, `.xw-bl`, `.xw-br`, `.xw-bc`), and override `.xw-shadow`, `.xw-sps`, `.xw-hdr`, `.xw-hdr-text`, `.xw-body`, `.myWinCont`, `.myWinCont b`, `.myWinFooter`, `.xt-close`, `.xt-close2`, `.xw-blank`, `.xw-disabled`. See step 4.1, item 7 for the full table.
18. On mobile (≤768 px), hide the navigation menu and replace it with a burger button. Toggle logic goes in `script.js`, styles in `style.css`, button markup in the template HTML.
19. Logo and favicon must be visually identical. Generate the favicon programmatically from the SVG logo used in the landing header. Upload to FTP as `/favicon.ico`, link in `<head>` with `<link rel="icon">`. On FTP errors, first obtain/create credentials via `ftp_tool` (`ftp_password_get` → `ftp_password_set` / `ftp_password_change` if needed); only if MCP cannot issue a password (missing API key permissions) — save `favicon.ico` locally and notify the user.
20. For `patch_template`: copy `code_search` **byte-for-byte** from `read_template` (module_id=2, template_id=1) — spaces, tabs, and line breaks matter; the fragment must match exactly once. `code_paste=""` deletes the fragment. Auto-backup runs before `patch_template` and `update_template`.
21. Before writing any CSS/HTML (Step 4), read `references/VISUAL.md` and follow its style selection, layout/grid, section layout recipes, typography/spacing floor, color tokens, component states, icon rule, CSS/JS toolbox, motion, imagery, social proof, copy/voice, and accessibility rules. This applies even when the user gave brand colors/fonts in Step 0 — `VISUAL.md` still governs the typography floor, token structure, icon source, and technique tiers on top of the brand input. Never fall back to one universal palette/typeface pairing, one container width, or one section layout shape for every brief regardless of niche.

---

## 3) Mandatory agent workflow

### Step 0. Gather requirements from the user

Before the first template edit, clarify:
- landing structure (blocks: hero, benefits, FAQ, contacts, CTA),
- brand colors/fonts,
- whether a separate top menu block is needed,
- whether a form is needed and which fields,
- whether captcha is needed (default: no),
- SEO tone (commercial/informational),
- whether microdata is needed (Organization, WebSite, LocalBusiness, etc.).

Decision rule:
- if the prompt has no explicit form request (e.g. "form", "lead", "contact us", "leave contacts"), do not create a form block and do not call `mail_*` actions;
- if a form is requested, complete step 5 fully.

**Visual style decision (mandatory, before Step 4):**
- if the user gave brand colors/fonts, use them as the token values in `references/VISUAL.md` §3.1, but still pick the closest named style from `VISUAL.md` §1.2 to drive layout character and component decisions;
- if the user gave no brand input beyond "make it nice"/"looks good", pick one named style from `VISUAL.md` §1.2 based on the niche (routing table in §1.3) and state the choice — palette, type pairing, one signature move — in one sentence to the user before generating CSS;
- never silently reuse the same palette/typeface pairing across unrelated briefs;
- write the non-negotiables + must-not-look-like lists for the chosen style (`VISUAL.md` §1.6) before generating any CSS — this is what keeps the build from drifting toward generic mid-way through Step 4.

### Step 1. Check the home page via `templates_tool(action="page_list")`

1. Get the page list and identify the home page (`page_id=1`):
```text
templates_tool(action="page_list", page_page=1, page_per_page=100)
```

2. Read the current home page state:
```text
templates_tool(action="page_get", page_id=1)
```

3. If the home page already has `owntmpl=1` — read the current personal template from the `tmpl` field in `page_get` and continue editing.

4. If `owntmpl=0` or no personal template yet — do not ask the user to enable "personal page template" in the panel. Enable and fill it on the final `templates_tool(action="page_update")` (step 10): `page_owntmpl=1`, `page_tmpl` with landing HTML, `page_message` with title only, plus `page_meta_title`, `page_meta_description`, `page_meta_keywords`.

5. For a personal template: landing HTML goes only in `page_tmpl`; `page_message` — page title only; `page_tmpl` must include `$POWERED_BY$`.

### Step 2. Check modules and context

1. Get the template catalog (recommended):
```text
templates_tool(action="list_modules")
```

2. Check site modules:
```text
modules_tool(action="modules_list")
```

3. If a mail form is needed — find the mail forms module in the catalog (code is usually `mail`; **always verify against `modules_list`**):
   - if `active: false` → `modules_tool(action="module_install", module_code="mail")` (use the actual code from the catalog);
   - if `api_enabled: false` → `modules_tool(action="module_enable_uapi", module_code="mail")`.

4. **Do not ask** the user to manually activate the mail forms module in the panel — do it via `modules_tool`.

5. `modules_tool`, `module_install`, and `ftp_password_*` require an API key with **Control Panel settings access**. On authorization errors — tell the user to reissue a key with that permission.

### Step 2.1. Prepare FTP access (before steps 4 / 4.1 / 4.2)

If `ftp_tool` returns an authorization error or no password is set in the MCP config:

1. Get connection settings:
```text
ftp_tool(action="ftp_password_get")
```

2. If no password is set yet (`ftp_password_set=0` in the response) — set one:
```text
ftp_tool(action="ftp_password_set", ftp_password="<6–15 chars: Latin letters, digits, _ and ->")
```

3. If a password exists but is unknown/not working — change it:
```text
ftp_tool(action="ftp_password_change", ftp_password="<new password>")
```
or reset: `ftp_tool(action="ftp_password_reset")` → then `ftp_password_set`.

4. After successful `ftp_password_set` / `ftp_password_change`, the response contains the password in plain text — **save it immediately** and use it for subsequent FTP operations.

5. Only if `ftp_password_*` is unavailable due to API key permissions — save files locally and notify the user about manual upload.

### Step 3. Menus (strictly via API)

0. **Pick the right output variable for the intended layout — before touching CSS.** uCoz exposes two independent menu output variables, not one:
   - `$NMENU_<ID>$` → **vertical** menu output.
   - `$SMENU_<ID>$` → **horizontal** menu output.
   The `layout` param on `menu_create` only sets the default preview orientation inside the uCoz control panel — it does **not** decide what markup you get in the template. The variable prefix you place in the HTML is what decides. Most `VISUAL.md` §2 header/hero recipes need a horizontal top nav — for those, output with `$SMENU_<ID>$`. Reserve `$NMENU_<ID>$` for genuinely vertical placements (sidebar, footer link column, mobile drawer list). Confirm this variable choice with `templates_tool(action="get_guide")` if unsure — do not guess from the `layout` param alone.

1. List menus:
```text
templates_tool(action="menu_list")
```

2. If the needed menu does not exist — create:
```text
templates_tool(action="menu_create", title="Landing main menu", layout=1)
```
`layout` sets only the control-panel preview default; see item 0 for which template variable actually controls the rendered orientation.

3. Update items:
```text
templates_tool(action="menu_update_items", menu_id=<ID>, items=[...])
```

Menu item requirements:
- every menu item must have a non-empty `URL`;
- for landings use anchor links (`#hero`, `#about`, `#faq`, `#contacts`, etc.);
- do not leave `URL` empty — styles and behavior may break.

4. Output the menu in the template only as:
```text
$NMENU_<ID>$
```
or, for a horizontal nav:
```text
$SMENU_<ID>$
```
Insert/replace the variable in an already published landing via `patch_template` (module_id=2, template_id=1).

4a. **Before wiring the variable into header/footer HTML, verify the actual generated markup — never assume a flat `<li>` list.** Call `templates_tool(action="get_guide")` and/or render the page once and inspect the output. Empirically, `$NMENU_<ID>$` expands into a full block, not bare `<li>` tags:
```html
<div class="uMenuV"><ul class="uMenuRoot">
  <li class="uWithSubmenu"><a href="...">Label</a><ul></ul></li>
  ...
</ul></div>
```
(Wrapper/class names can vary by account or theme — re-verify with a live render if yours differ; don't hardcode this snippet blindly.) Consequences for your markup and CSS:
- Never nest the variable inside your own `<ul>`/`<ol>` — the result is an invalid `<ul><div>...<ul>...</ul></div></ul>` structure; the browser hoists the inner block out and breaks your flex/grid row. Wrap it in a `<nav>` or `<div>` instead.
- Reset list-style/layout on the *system's own* generated classes (e.g. `.uMenuRoot`, `.uWithSubmenu`), not only on your wrapper class — resetting only the wrapper leaves the system's default bullets/block stacking untouched underneath.
- Hide the empty trailing `<ul></ul>` per item (submenu placeholder) unless you're intentionally building dropdowns.

Minimal horizontal-nav CSS pattern (adapt class names after verifying your actual render):
```css
.site-nav{display:flex}
.site-nav .uMenuV{display:contents}          /* match your real wrapper class */
.site-nav .uMenuRoot{display:flex;gap:24px;list-style:none;margin:0;padding:0}
.site-nav .uMenuRoot>li{list-style:none}
.site-nav .uMenuRoot>li>ul{display:none}     /* hide empty submenu placeholder */
.site-nav a{text-decoration:none}
```

5. Burger menu for mobile (mandatory):
- at ≤768 px width, hide navigation (`display:none`), show burger button;
- burger button in template HTML (inline SVG, not emoji);
- toggle logic strictly in `/js/script.js`, toggling a class (e.g. `.is-open`) on the `<nav>`/`<div>` wrapper from item 4a — not inline `style.display`;
- burger and open mobile menu styles in `/css/style.css`, targeting the wrapper class **and** the system's `.uMenuRoot`/item classes from item 4a so the mobile column layout actually applies;
- on anchor menu item click — mobile menu closes automatically.

Forbidden:
- hand-coding `<ul><li>` menu instead of API menu,
- mixing static menu and `$NMENU_N$`/`$SMENU_N$` in the same navigation block,
- creating menu items with empty `URL`,
- leaving desktop navigation without a burger on mobile (≤768 px),
- wrapping `$NMENU_N$`/`$SMENU_N$` output inside your own `<ul>`/`<ol>` element,
- styling only your own wrapper class while ignoring the system's generated list/item classes (bullets and broken layout will leak through),
- picking `$NMENU_N$` vs `$SMENU_N$` by guesswork instead of the intended visual orientation from `VISUAL.md`.

### Step 4. Styles only in `style.css` + FTP upload

Before writing a single rule, read `references/VISUAL.md` (style catalog, typography/spacing floor, color token contract, icon rule, CSS toolbox). `style.css` must open with the `:root` token block from `VISUAL.md` §3.1 (adapted to the chosen/brand colors) — component rules read those variables, they do not invent hex values inline. CSS nesting never exceeds 3 levels; any selector overriding uCoz system markup (`.xw-*`, `$NMENU_N$`-generated classes) stays outside `@layer` (see `VISUAL.md` §5, platform rule).

Run step 2.1 before FTP operations if needed.

1. Generate/update `style.css` locally.
2. Check for `/css` folder:
```text
ftp_tool(action="list", path="/")
```
3. If `/css` is missing — create:
```text
ftp_tool(action="mkdir", path="/css")
```
4. Upload the file:
```text
ftp_tool(action="write", path="/css/style.css", content="<full CSS>")
```
or
```text
ftp_tool(action="upload", local_path="<local path to style.css>", remote_path="/css/style.css")
```
5. Link in the template with versioning (via `patch_template` if landing is already published; on first publish — include in `page_tmpl`):
```html
<link rel="stylesheet" href="/css/style.css?v=<ASSET_VERSION>">
```

### Step 4.1. JS only in `script.js` + FTP upload

`script.js` stays one plain script (no `type="module"`, no bundler, no external framework/CDN). Follow the JS baseline tiers in `references/VISUAL.md` §7: defensive syntax (`?.`, `??`, optional chaining around every `querySelector`), `IntersectionObserver` as the fallback for scroll reveals, `requestAnimationFrame` to throttle scroll/resize/mousemove handlers. **Never use `fetch`/`FormData` to intercept `$MFORM_<ID>$` submission** — the platform already handles it natively and shows the `_uWnd` success modal (styled in item 7 below); `fetch` is only for genuinely custom, non-system widgets.

1. Generate/update `script.js` locally.
2. Check for `/js` folder:
```text
ftp_tool(action="list", path="/")
```
3. If `/js` is missing — create:
```text
ftp_tool(action="mkdir", path="/js")
```
4. Upload the file:
```text
ftp_tool(action="write", path="/js/script.js", content="<full JS>")
```
or
```text
ftp_tool(action="upload", local_path="<local path to script.js>", remote_path="/js/script.js")
```
5. Link before `</body>` with versioning (via `patch_template` if landing is already published):
```html
<script src="/js/script.js?v=<ASSET_VERSION>"></script>
```

6. Bump `<ASSET_VERSION>` on CSS/JS changes (e.g. `202604091530`).
7. If `$MFORM_<ID>$` is used, add override styles for the default uCoz success modal in `style.css`.

**Full uCoz modal structure (9-slice border):**

The window uses a table-based 9-slice model with background images. Reset all of these (`background: none !important; background-image: none !important; border: none !important;`) or the default frame remains:

| Group | Selectors | Purpose |
|---|---|---|
| Frame (9 parts) | `.xw-tl`, `.xw-tr`, `.xw-tc`, `.xw-ml`, `.xw-mr`, `.xw-mc`, `.xw-bl`, `.xw-br`, `.xw-bc` | Window frame corners and sides (background-image) |
| Container | `.xw-shadow` | Outer container; set border + box-shadow to match landing style |
| Header | `.xw-hdr`, `.xw-hdr-text`, `.xw-draggable` | Title bar; reset background-image |
| Body | `.xw-body`, `.myWinCont` | Message content |
| Footer | `.myWinFooter` | Bottom line inside the window |
| Close button | `.xt-close`, `.xt-close2` | Both classes; default uses background-image; reset and style (via `::before { content: 'X' }` or inline SVG) |
| Service | `.xw-sps`, `.xw-blank`, `.xw-disabled` | Spacer, placeholder, overlay when blocked |

**Required styling steps:**
1. Reset `background`, `background-image`, `border` on all 9-slice elements (`.xw-tl` … `.xw-bc`).
2. Reset `background-image` on `.xw-hdr`, `.xt-close`, `.xt-close2`.
3. Hide `.xw-sps` (`display: none`).
4. Style `.xw-shadow` with border + box-shadow instead of 9-slice frame.
5. For `.xt-close` / `.xt-close2` use `::before { content: 'X' }` with a pixel font — default close icon is background-image.
6. Style `.myWinCont b` (title inside success message) and body text.
7. Match window visuals to the landing (colors, typography, spacing, close button).
8. Do not rely on inline styles from system markup.

### Step 4.2. Favicon — generate from logo (mandatory)

The favicon must be visually identical to the logo in the landing header. Generate via PowerShell with `System.Drawing`.

**If the client provided no logo asset**, do not skip straight to favicon generation as if one exists: first generate a minimal monogram mark per `references/VISUAL.md` §4.1 (accent-colored tile + 1–2 letters), place it in the header next to the site name, then continue below using that mark's colors/shape as the favicon source.

**Algorithm:**

1. Take logo parameters from the header HTML (colors, shape, graphic motif).
2. Generate `favicon.ico` (32×32, ICO with embedded PNG) via PowerShell script:

```powershell
Add-Type -AssemblyName System.Drawing

$size = 32
$bmp = New-Object System.Drawing.Bitmap($size, $size)
$g = [System.Drawing.Graphics]::FromImage($bmp)
$g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias

# Background: rounded rectangle (colors from landing logo)
$path = New-Object System.Drawing.Drawing2D.GraphicsPath
$r = 7
$path.AddArc(0, 0, $r*2, $r*2, 180, 90)
$path.AddArc($size-$r*2, 0, $r*2, $r*2, 270, 90)
$path.AddArc($size-$r*2, $size-$r*2, $r*2, $r*2, 0, 90)
$path.AddArc(0, $size-$r*2, $r*2, $r*2, 90, 90)
$path.CloseFigure()

$brush = New-Object System.Drawing.Drawing2D.LinearGradientBrush(
    [System.Drawing.Point]::new(0,0), [System.Drawing.Point]::new($size,$size),
    [System.Drawing.Color]::FromArgb(255, <R1>, <G1>, <B1>),   # primary logo color
    [System.Drawing.Color]::FromArgb(255, <R2>, <G2>, <B2>))   # secondary logo color
$g.FillPath($brush, $path)

# Graphic motif (matches SVG logo shape): lines, arcs, circle, etc.
# ... adapt to the specific logo ...

$g.Dispose(); $brush.Dispose(); $path.Dispose()

# Save PNG → ICO with embedded PNG
$ms = New-Object System.IO.MemoryStream
$bmp.Save($ms, [System.Drawing.Imaging.ImageFormat]::Png)
$pngBytes = $ms.ToArray(); $ms.Close(); $bmp.Dispose()

$ico = New-Object System.IO.MemoryStream
$offset = 22; $len = $pngBytes.Length
$ico.Write([byte[]](0,0,1,0,1,0), 0, 6)
$ico.Write([byte[]](32,32,0,0,1,0,32,0,
    ($len-band 0xFF),(($len-shr 8)-band 0xFF),(($len-shr 16)-band 0xFF),(($len-shr 24)-band 0xFF),
    ($offset-band 0xFF),(($offset-shr 8)-band 0xFF),(($offset-shr 16)-band 0xFF),(($offset-shr 24)-band 0xFF)), 0, 16)
$ico.Write($pngBytes, 0, $pngBytes.Length)
[System.IO.File]::WriteAllBytes("<workspace_path>\favicon.ico", $ico.ToArray())
$ico.Close()
```

3. Upload to site root via FTP:
```text
ftp_tool(action="upload", local_path="<workspace_path>/favicon.ico", remote_path="/favicon.ico")
```
or
```text
ftp_tool(action="write", path="/favicon.ico", content="<binary>")
```

4. Add link tag in template `<head>` (right after `<meta charset>`):
```html
<link rel="icon" type="image/x-icon" href="/favicon.ico">
```
If the landing is already published — insert via `patch_template` (module_id=2, template_id=1); on first publish — include in `page_tmpl`.

**Rules:**
- Favicon colors, shape, and motif must match the header SVG logo.
- Run step 2.1 before upload if FTP is unavailable.
- Do not use emoji or the browser default favicon — only the generated file.

---

### Step 5. Mail form via `mail_*` actions

This step runs only when the user explicitly requested a form in the prompt.

0. Ensure the mail forms module is active (step 2). If `active: false` — `modules_tool(action="module_install", module_code="<code from modules_list>")`. Do not ask the user to activate the module manually.

1. Create the form:
```text
templates_tool(action="mail_create_form", mail_name="Landing form", mail_emails="...", mail_rez="...")
```

Before `mail_create_form`:
- list existing forms (`mail_list_forms`);
- reuse recipient emails from existing forms when creating a new one;
- only if no existing emails — ask the user for a new email.

**If reusing an existing form:**
- call `mail_list_fields` and review all fields;
- if type 7 (captcha / security code) exists and the user **did not** request captcha — delete via `mail_delete_field`;
- remove extra fields not needed for this landing (if not system `f1`/`f2`) — confirm with user or delete;
- after deletion sync the email template (`mail_get_template` → `mail_save_template`).

2. Add fields:
```text
templates_tool(action="mail_add_field", mail_form_id=<ID>, mail_name="...", mail_field_type=1, mail_required=true, ...)
```

3. Captcha: add type 7 only on **explicit** user request. If captcha exists in a reused form and was not requested — delete (`mail_delete_field`).

4. After field changes, sync the email HTML:
```text
templates_tool(action="mail_get_template", mail_form_id=<ID>)
templates_tool(action="mail_save_template", mail_form_id=<ID>, mail_tmpl="<email HTML>")
```

5. Output in the page template only:
```text
$MFORM_<ID>$
```
Insert `$MFORM_<ID>$` into an existing landing via `patch_template` (module_id=2, template_id=1), not full rewrite.

6. Form field labels must be SEO-semantic:
- use clear names for user intent, e.g. "Your name", "Phone", "Email", "City", "Comment";
- for commercial landings add service/product context where appropriate, e.g. "Service of interest", "Project type";
- do not use technical or empty names like "field1", "field 2", "text";
- keep natural language; no keyword stuffing.

7. `f1` and `f2` are system-required:
- `f1` = email, `f2` = name;
- do not delete `f1`/`f2` via `mail_delete_field`;
- do not create duplicate email/name fields instead of `f1`/`f2`;
- only label changes allowed; no deletion or replacement of system fields.

8. When editing form/email template HTML, keep system field names as `name="fN"` matching actual fields:
- after `mail_add_field`/`mail_delete_field`/`mail_move_field` verify via `mail_list_fields`;
- do not rename `name="fN"` to arbitrary values (`name="phone"`, `name="email"`, etc.);
- labels may change; system `name` may not;
- on field structure changes sync HTML via `mail_get_template` → `mail_save_template`;
- after any field operations verify `f1` and `f2` are present.

Forbidden:
- fake hand-coded form without `mail_create_form`,
- changing fields without updating the email template.

### Step 6. SEO block (mandatory template)

Use in `<head>`:

```html
<title><?if($SEO_TITLE$)?>$SEO_TITLE$<?else?>Site name<?endif?></title>
<?if($SEO_DESCRIPTION$)?><meta name="description" content="$SEO_DESCRIPTION$" /><?else?><meta name="description" content="Landing description" /><?endif?>
<?if($SEO_KEYWORDS$)?><meta name="keywords" content="$SEO_KEYWORDS$" /><?else?><meta name="keywords" content="Landing keywords" /><?endif?>
```

Note: adapt fallback texts (`Landing description`, `Landing keywords`) to the project theme; keep the conditional structure unchanged.

### Step 6.1. Open Graph and Twitter Card (mandatory)

Add at minimum in `<head>`:

```html
<meta property="og:type" content="website" />
<meta property="og:title" content="<?if($SEO_TITLE$)?>$SEO_TITLE$<?else?>Site name<?endif?>" />
<meta property="og:description" content="<?if($SEO_DESCRIPTION$)?>$SEO_DESCRIPTION$<?else?>Landing description<?endif?>" />
<meta property="og:url" content="$HOME_PAGE_LINK$" />
<meta property="og:site_name" content="$SITE_NAME$" />
<meta property="og:image" content="https://site.com/path/to/og-image.jpg" />
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="<?if($SEO_TITLE$)?>$SEO_TITLE$<?else?>$SITE_NAME$<?endif?>" />
<meta name="twitter:description" content="<?if($SEO_DESCRIPTION$)?>$SEO_DESCRIPTION$<?else?>Landing description<?endif?>" />
<meta name="twitter:image" content="https://site.com/path/to/og-image.jpg" />
```

Rules:
- `og:image` and `twitter:image` must point to a real public image;
- use absolute URLs;
- do not leave `site.com` placeholders in the final template.

### Step 7. Schema.org microdata (mandatory)

Add JSON-LD in `<head>` (at least one relevant type):
- `Organization` or `LocalBusiness` for a company,
- `WebSite` if needed,
- `ContactPage` acceptable for contact blocks.

Minimum requirements:
- valid JSON,
- real site data (name, URL, contacts),
- no `TODO` placeholders.

### Step 8. Footer and required system variables

1. Use `$YEAR$` in the footer, not a static year.
2. After closing `</body>` always include/preserve:
```html
$POWERED_BY$
```

### Step 9. Responsiveness (mandatory check)

Before final save, verify:
- no horizontal scroll at 320 px;
- key blocks (hero, CTA, form, footer) readable and clickable at 320/375/768/1024+ px;
- images and buttons stay within the container;
- menu/anchors work on mobile;
- burger button visible and clickable at 320/375 px;
- mobile menu opens/closes without artifacts;
- mobile menu closes on anchor item click.

**Visual QA (from `references/VISUAL.md` §11) — check together with the above:**
- `:focus-visible` visible on every link, button, and form field; no `outline: none` without a replacement;
- tap targets ≥ 44×44px with ≥ 8px spacing;
- body text vs. background contrast ≥ 4.5:1, large text/UI borders ≥ 3:1;
- `prefers-reduced-motion` respected — page stays fully usable with motion reduced, never stuck hidden;
- hero uses `dvh`, not `vh`;
- headline follows the type scale in `VISUAL.md` §2.1, not an unexamined 68–82px default;
- exactly one orchestrated motion moment (hero), not fade-up on every section;
- CSS nesting nowhere exceeds 3 levels; no override of `.xw-*`/menu selectors lives inside `@layer`.

### Step 10. Publish and edit the home page

The home personal template is accessible two ways:
- **Pages API:** `page_get` / `page_update` (`page_tmpl`, `page_meta_*`);
- **Templates API:** `read_template` / `patch_template` with `module_id=2`, `template_id=1` (same HTML).

#### 10.1. First landing publish (`owntmpl=0` or empty `tmpl`)

Full write via Pages API:
```text
templates_tool(action="page_update", page_id=1, page_owntmpl=1, page_message="<title>", page_tmpl="<full HTML>", page_meta_title="...", page_meta_description="...", page_meta_keywords="...")
```

Rules:
- `page_tmpl` — full landing HTML document;
- `page_message` — page title only, no HTML;
- pass `page_meta_title`, `page_meta_description`, `page_meta_keywords` explicitly;
- after `page_update` check response (`id`, `url`) and `page_get` if needed.

Do **not** use `update_template` for the landing — only `page_update` on first publish.

#### 10.2. Edits to an existing landing (after generation)

Any HTML changes (blocks, CSS/JS links, `$MFORM_N$`, `$NMENU_N$`, favicon in `<head>`, etc.) — **only via `patch_template`**:

1. Read current code:
```text
templates_tool(action="read_template", module_id=2, template_id=1)
```

2. Copy the exact fragment from the response (`code_search` — byte-exact match, exactly one occurrence).

3. Apply the patch:
```text
templates_tool(action="patch_template", module_id=2, template_id=1, code_search="<fragment from read_template>", code_paste="<new fragment>")
```

4. For multiple unrelated edits — separate `patch_template` per unique fragment.

5. SEO meta only, without HTML changes:
```text
templates_tool(action="page_update", page_id=1, page_meta_title="...", page_meta_description="...", page_meta_keywords="...")
```

**Forbidden** for an existing landing:
- `update_template` (full module template rewrite);
- `page_update` with full `page_tmpl` for small edits (risk of overwrite and formatting loss).

Run `validate_template` **only if the user explicitly asked** to validate the template.

### Step 11. Quarantine and indexing (final step)

**Quarantine** in uCoz is a mode where the site **is not indexed** by search engines. Unlocking quarantine allows indexing.

Run **after** landing publish and all edits (step 10), **before** the final user report.

1. Ask the user (if not stated in the prompt):
   > Do you want the site indexed in search engines? I can unlock quarantine so indexing can start.

2. If the user **does not** want indexing now — leave quarantine unchanged; report that the site remains in quarantine and is not indexed.

3. If the user **wants** indexing (or explicitly requested it) — check status:
```text
modules_tool(action="quarantine_status")
```

4. If response has `locked=true` (quarantine on) — unlock:
```text
modules_tool(action="quarantine_unlock")
```

5. If `locked=false` — quarantine already off; inform the user.

**Rules:**
- **Do not unlock** quarantine without user consent for indexing.
- Do not ask the user to unlock quarantine manually in the panel — use `modules_tool`.
- `quarantine_status` / `quarantine_unlock` require an API key with **Control Panel settings access**.

---

## 4) Pre-delivery checklist

The agent must verify and explicitly confirm:
- [ ] Home page (`page_id=1`) read via `page_list` and `page_get`.
- [ ] If form needed: mail forms module checked via `modules_list`; if `active: false`, installed via `module_install` (no manual panel steps).
- [ ] FTP access verified; if no password — obtained/created via `ftp_password_get` / `ftp_password_set` (or `ftp_password_change`).
- [ ] **First publish:** `page_update` with `page_owntmpl=1`, full `page_tmpl` and `page_meta_*`; `page_message` — title only.
- [ ] **Post-generation edits:** HTML changed via `patch_template` (module_id=2, template_id=1); `update_template` and full `page_tmpl` rewrite not used.
- [ ] Menu created/updated via `menu_*`; template has `$NMENU_N$`.
- [ ] All menu items have `URL` filled (anchor links for landings).
- [ ] Burger menu implemented: button (SVG) in HTML, toggle in `script.js`, styles in `style.css`.
- [ ] At 320/375 px desktop nav hidden, burger works, menu closes on item click.
- [ ] `style.css` created and uploaded to `/css/style.css`.
- [ ] Template links `/css/style.css?v=<ASSET_VERSION>`.
- [ ] `script.js` created and uploaded to `/js/script.js`.
- [ ] Template links `/js/script.js?v=<ASSET_VERSION>` (before `</body>`).
- [ ] Mail form created via `mail_create_form` or existing form reused — output as `$MFORM_N$`.
- [ ] For existing form: all fields checked via `mail_list_fields`; extra fields (incl. type 7 captcha if not requested) removed via `mail_delete_field`.
- [ ] For new form: recipient email taken from existing mail forms if available.
- [ ] Form field labels are SEO-semantic and human-readable.
- [ ] Correct system `name="fN"` preserved per `mail_list_fields`.
- [ ] `f1` is the only email field (no extra email fields).
- [ ] System fields `f1` (email) and `f2` (name) preserved and not deleted.
- [ ] Full override styles for uCoz success modal — 9-slice + content: `.xw-shadow`, `.xw-tl`..`.xw-bc` (`background-image` reset), `.xw-sps` (hidden), `.xw-hdr`, `.xw-hdr-text`, `.xw-body`, `.myWinCont`, `.myWinCont b`, `.myWinFooter`, `.xt-close`/`.xt-close2` (`background-image` reset, `::before` close icon), `.xw-disabled`.
- [ ] Email template HTML updated after field changes (`mail_get_template` + `mail_save_template`).
- [ ] Footer uses `$YEAR$`.
- [ ] `$POWERED_BY$` present after `</body>`.
- [ ] SEO block in required format with `SEO_*` conditionals.
- [ ] Open Graph and Twitter Card tags added with real values.
- [ ] Relevant Schema.org microdata added.
- [ ] Responsiveness verified at 320/375/768/1024+ px.
- [ ] No emoji icons in UI; icons via SVG.
- [ ] Favicon generated from SVG logo (colors and shape match); `favicon.ico` uploaded to site root (or saved locally only if `ftp_password_*` unavailable).
- [ ] `<link rel="icon" type="image/x-icon" href="/favicon.ico">` in `<head>` (via `patch_template` or in `page_tmpl` on first publish).
- [ ] After publish/edits, `id` and `url` checked; `page_get` if needed.
- [ ] **Final step:** indexing need confirmed; if user agreed, `quarantine_status` checked and `quarantine_unlock` run when `locked=true`.
- [ ] Template passed `validate_template` (only if user explicitly requested validation).
- [ ] Visual style chosen from `VISUAL.md` §1.2 (or derived from user's brand input) and stated to the user before CSS was written; no default palette reused across unrelated briefs.
- [ ] Non-negotiables + must-not-look-like lists written for the chosen style (`VISUAL.md` §1.6) before CSS was generated.
- [ ] `style.css` opens with the `:root` token contract from `VISUAL.md` §3.1; component rules read those variables.
- [ ] Icons are Heroicons/Lucide/Tabler inline SVG (`stroke="currentColor"`) — no hand-drawn paths, no emoji, no icon webfont.
- [ ] CSS nesting nowhere exceeds 3 levels; no selector overriding `.xw-*` / menu / module system markup lives inside `@layer`.
- [ ] Hero height uses `dvh`, not `vh`; headline follows the type scale in `VISUAL.md` §2.1.
- [ ] Exactly one orchestrated motion moment (hero); any scroll-driven reveal has an `@supports`/`IntersectionObserver` fallback and respects `prefers-reduced-motion`.
- [ ] `:focus-visible`, tap targets ≥44×44px, and contrast minimums from `VISUAL.md` §11 verified.
- [ ] `$MFORM_N$` submission untouched by custom `fetch`/`FormData` code in `script.js`.
- [ ] Container width picked once from `VISUAL.md` §1.4's range and set in `:root` — not a different number improvised per section.
- [ ] Each section follows a deliberate layout recipe from `VISUAL.md` §1.5 — not the same centered/3-card shape repeated everywhere.
- [ ] Button/input/card hover, `:focus-visible`, active, and disabled states defined per `VISUAL.md` §3.5.
- [ ] Section backgrounds alternate deliberately per `VISUAL.md` §3.6 — page isn't one flat, undifferentiated scroll.
- [ ] No generic stock-photo clichés used as imagery; if real photos are used, crop ratio and treatment are consistent (`VISUAL.md` §8).
- [ ] Headlines/CTAs state a specific outcome, not a generic category label (`VISUAL.md` §10).
- [ ] Footer copyright line uses `$YEAR$`, styled small/muted, visually separated from link columns (`VISUAL.md` §10.1).
- [ ] If no brand logo was provided, a monogram fallback was generated per `VISUAL.md` §4.1 before favicon generation.

---

## 5) Minimum action call order (reference)

```text
1) templates_tool(action="page_list", page_page=1, page_per_page=100)
2) templates_tool(action="page_get", page_id=1)
3) templates_tool(action="list_modules")
4) modules_tool(action="modules_list")   // if form needed — check/install mail forms module
5) ftp_tool(action="ftp_password_get")   // on FTP error — ftp_password_set / ftp_password_change
6) templates_tool(action="menu_list")
7) templates_tool(action="menu_create"/"menu_update"/"menu_update_items", ...)
8) ftp_tool(action="list", path="/")
9) ftp_tool(action="mkdir", path="/css")   // if needed
10) ftp_tool(action="write", path="/css/style.css", content="...")
11) ftp_tool(action="mkdir", path="/js")   // if needed
12) ftp_tool(action="write", path="/js/script.js", content="...")
12.1) Shell: PowerShell → generate favicon.ico from logo
12.2) ftp_tool(action="upload", local_path="<path>/favicon.ico", remote_path="/favicon.ico")
13) modules_tool(action="module_install", module_code="mail")   // only if module inactive and form needed
14) templates_tool(action="mail_create_form", mail_name="...", mail_emails="...", mail_rez="...")
15) templates_tool(action="mail_add_field", mail_form_id=..., ...)
16) templates_tool(action="mail_get_template", mail_form_id=...)
17) templates_tool(action="mail_save_template", mail_form_id=..., mail_tmpl="...")

// Publish — depends on home page state:
18a) FIRST publish (owntmpl=0):
     templates_tool(action="page_update", page_id=1, page_owntmpl=1, page_message="...", page_tmpl="...", page_meta_title="...", page_meta_description="...", page_meta_keywords="...")
18b) EDITS to existing landing:
     templates_tool(action="read_template", module_id=2, template_id=1)
     templates_tool(action="patch_template", module_id=2, template_id=1, code_search="...", code_paste="...")   // repeat per edit

19) templates_tool(action="page_get", page_id=1)   // verify result
20) Ask user: indexing needed in search engines?
    → if yes: modules_tool(action="quarantine_status")
    → if locked=true: modules_tool(action="quarantine_unlock")
// validate_template — only on explicit user request
```

If home page has `owntmpl=0` at step 1, enable personal template via `page_update` (step 18a) without manual panel steps.

---

## 6) Prohibitions

- Do not skip reading the home page via `page_list` and `page_get` before editing.
- Do not ask the user to manually enable the home personal template in the uCoz panel.
- Do not ask the user to manually activate the mail forms module — use `modules_tool` (`module_install`).
- Do not publish a landing without `page_update` with `page_owntmpl=1` on **first** publish.
- Do not edit HTML of an **existing** landing via `update_template` or full `page_tmpl` in `page_update` — only `patch_template` (module_id=2, template_id=1).
- Do not put template HTML in `page_message` or duplicate markup in `page_message` and `page_tmpl`.
- Do not pass `page_tmpl` without `$POWERED_BY$`.
- Do not publish the home page without explicit `page_meta_title`, `page_meta_description`, `page_meta_keywords` in `page_update`.
- Do not hand-code menus instead of `menu_*`.
- Do not deliver a landing without a burger menu on mobile (≤768 px).
- Do not put burger toggle logic inline in the template — only in `/js/script.js`.
- Do not create menu items with empty `URL` (use anchors for landings).
- Do not keep styles in inline `<style>` as the primary source (primary source is `/css/style.css`).
- Do not use large inline JS as the primary script source (primary source is `/js/script.js`).
- Do not link CSS/JS without URL versioning (required: `?v=<ASSET_VERSION>`).
- Do not deliver without mobile responsiveness check (minimum 320/375/768/1024+).
- Do not use a static year in the footer.
- Do not remove or omit `$POWERED_BY$`.
- Do not add a form without `mail_*` actions.
- Do not create a form by default if the prompt has no explicit form request.
- Do not leave captcha (type 7) in an existing form if the user did not request it — delete via `mail_delete_field`.
- Do not reuse an existing form without checking all fields via `mail_list_fields` first.
- Do not ignore existing recipient emails when creating a new form.
- Do not use non-semantic form field names (`field1`, `field2`, `test`) in the final version.
- Do not break system form field names (`name="fN"` required).
- Do not delete system fields `f1` and `f2` (label changes only).
- Do not create new email/name fields instead of system `f1`/`f2`.
- Do not mass-encode text as HTML entities (`&#...;`) without technical reason.
- Do not leave default uCoz success modal styling without landing overrides. Full override selectors: `.xw-shadow`, `.xw-tl`, `.xw-tr`, `.xw-tc`, `.xw-ml`, `.xw-mr`, `.xw-mc`, `.xw-bl`, `.xw-br`, `.xw-bc`, `.xw-sps`, `.xw-blank`, `.xw-hdr`, `.xw-hdr-text`, `.xw-body`, `.myWinCont`, `.myWinCont b`, `.myWinFooter`, `.xt-close`, `.xt-close2`, `.xw-disabled` — all must match landing visuals. Resetting `background-image` on 9-slice elements is mandatory.
- Do not leave Open Graph with placeholders or without `og:image`.
- Do not use emoji as UI icons; use SVG.
- Do not publish without a favicon: `favicon.ico` must be generated and uploaded (or saved locally only if `ftp_password_*` unavailable due to API key permissions).
- Do not make a favicon that does not visually match the header logo (colors, shape, or motif).
- Do not unlock quarantine (`quarantine_unlock`) without user consent for search engine indexing.
- Do not reuse the same palette/typeface pairing across unrelated briefs regardless of niche — pick a named style from `VISUAL.md` §1.2 and state it.
- Do not hand-draw new icon path data — use Heroicons/Lucide/Tabler inline SVG.
- Do not wrap `.xw-*` / `$NMENU_N$` / module-generated selectors inside `@layer`.
- Do not nest CSS more than 3 levels deep.
- Do not intercept `$MFORM_N$` submission with `fetch`/`FormData` — style the native `_uWnd` modal instead (Step 4.1).
- Do not add GSAP, Tailwind, or any external UI-framework CDN — CSS/JS stay inline in `style.css`/`script.js`; use the native toolbox in `VISUAL.md` §5–§7.
- Do not ship any animation without a `prefers-reduced-motion` fallback.
- Do not use `100vh` for the hero — use `100dvh`.
- Do not fade-and-slide-up every section — spend the one orchestrated motion moment on the hero only.
- Do not hardcode the same container max-width on every landing regardless of content — pick from the range in `VISUAL.md` §1.4 per brief.
- Do not apply the same "centered heading + 3-card grid" recipe to every section — pick a layout per section from `VISUAL.md` §1.5.
- Do not leave button/input/card states undefined beyond default+hover — cover `:focus-visible`, active, and disabled per `VISUAL.md` §3.5.
- Do not use generic stock-photo clichés (handshake, diverse-team fist-bump, stock-smile) as landing imagery — use `VISUAL.md` §8.1's abstract/icon-led default when no real photos exist.
- Do not leave the footer copyright line as unstyled default text — style it per `VISUAL.md` §10.1 (still using `$YEAR$`, per principle 5 above).
- Do not leave a header with plain site-name text and no graphic anchor when the client provided no logo — generate the monogram fallback in `VISUAL.md` §4.1 before Step 4.2's favicon generation.

---

## 7) User report format after completion

At the end, provide a brief technical report:
1. Actions called (in order), including `page_list`, `page_get`, `patch_template` / `page_update`, `modules_tool`, `ftp_password_*`.
2. `page_get` result for home (`page_id=1`, URL, SEO fields).
3. Mail forms module status (if form needed): whether installed via `module_install`.
4. Created/updated menu ID.
5. Created form ID.
6. CSS upload path (`/css/style.css`).
7. JS upload path (`/js/script.js`).
8. SEO, Open Graph/Twitter, and schema blocks added.
9. Responsiveness confirmation (320/375/768/1024+).
10. FTP status: password obtained via `ftp_password_*` or files saved locally.
11. Favicon status: uploaded to FTP (`/favicon.ico`) or saved locally.
12. Quarantine and indexing status: whether user was asked; quarantine unlocked via `quarantine_unlock` or site left non-indexable.
13. Template validation result (if requested).
14. Chosen visual style (name from `VISUAL.md` §1.2) and where the token contract lives (`style.css` `:root`).
15. Whether a logo was provided or a monogram fallback was generated (`VISUAL.md` §4.1).
