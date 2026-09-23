# uCoz landing publishing playbook (Ad Campaign Landing Skill)

A single playbook for publishing landing pages on uCoz via MCP (`templates_tool`, `ftp_tool`).
**Do not use** the separate skill `ucoz-landing-agent` — every rule is here.

Marketing links: `COPY.md` (message match) and `PIPELINE.md` (the overall pipeline).

---

## 0) Default mode: segment landing page

Paid traffic → **a separate page per segment**, not the homepage.

| Parameter | Value |
|----------|----------|
| Creation | `templates_tool(action="page_add")` |
| Pretty URL | `page_ownurl` from `segments.json` (a deterministic slug) |
| HTML | `page_tmpl` (a full document), the title in `page_message` |
| Hero | Strictly the ad offer (`COPY.md`, Message Match section) |

**Order (ad + landing page in one pipeline):**

```text
1) segment_id + page_ownurl + ad (from CSV or generation)
2) page_list → check that the slug is free
3) CSS/JS/favicon/menu/form (steps 3–5 below) — once per site or v+N
4) page_add → the page exists, the response contains id + url
5) assemble UTM per `PIPELINE.md` → final_url
6) The ad in OUTPUT_PACK already has a working link (do not hand over the URL before page_add)
```

**URL and the chicken-and-egg problem:** the slug is known before the HTML; the page must be published **before** `final_url` is given to the marketer. Lazy-render is not used — a static `page_add` on uCoz.

### UTM on the landing page

| Where | Rule |
|-----|---------|
| **Ad** | `final_url` with UTM is the primary attribution channel |
| **Anchor CTAs** (`href="#order"`) | UTM is already in the address bar — separate tags are not needed |
| **Internal links** (other pages on the same domain) | Carry `utm_*` across when the link continues the ad scenario |
| **External product links** (signup, pricing, templates, AI on another domain) | Always add or carry UTM across, keeping the query and the hash |
| **Messengers and contacts** | Tag only if the platform supports parameters and doing so does not break the link |
| **Buttons** | Do not duplicate UTM in HTML; for a `<button>` with no navigation, Yandex Metrica counts the visit by the entry URL |

**If the landing page has transitions to signup, pricing, templates, or other product pages:** add UTM carry-over in `/js/script.js`. The implementation must support the same product domain and a different one, and must not drop hash fragments.

Mark product links explicitly with `data-preserve-utm`. The example works across domains, and it does not remove existing query parameters or the hash:

```javascript
(function () {
  var source = new URLSearchParams(window.location.search);
  var keys = ["utm_source", "utm_medium", "utm_campaign", "utm_content", "utm_term"];
  document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll("a[data-preserve-utm][href]").forEach(function (a) {
      try {
        var u = new URL(a.getAttribute("href"), window.location.href);
        keys.forEach(function (key) {
          var value = source.get(key);
          if (value && !u.searchParams.has(key)) u.searchParams.set(key, value);
        });
        a.href = u.toString();
      } catch (e) {}
    });
  });
})();
```

On a typical segment landing page (anchors `#order` and `#faq` only) this block is **not needed**.

**Homepage (`page_id=1`)** — only if the user explicitly asks. Then use steps 1 and 10 below (homepage).

### 0.1) Critical: custom template (`owntmpl = 1`) — do not break the content

**Source of truth:** MCP `get_guide` / uAPI Pages. Read this block before `page_add` / `page_update`.

| Rule | Action |
|---------|----------|
| **The entire landing HTML** | Only in `page_tmpl` (a full `<!DOCTYPE html>…</html>` + `$POWERED_BY$` after `</body>`) |
| **`page_message`** | Only the page **title** (the same text as `page_name` / the H1 for SEO), **without HTML** |
| **Forbidden** | `$PAGE_CONTENT$`, `$MODULE_CONTENT$`, and any guessed content variables |
| **Forbidden** | Duplicating markup in `page_message` and `page_tmpl` |
| **Bug symptom** | The site shows only the background/styles, with no hero or blocks → the HTML did not go into `page_tmpl` |

**Why:** uCoz has **no** universal `$PAGE_CONTENT$`. With `owntmpl=1`, the API "content" field is not inserted into the body of the custom template the way WordPress content is.

**HTML generation:** the agent creates a full `<!DOCTYPE html>…</html>` document, following the rules of this playbook, and passes it whole in `page_tmpl` on `page_add`.

**Check after publishing:** `page_get` → in the response, `content` is the short title; on the public URL, the full landing page has H1 = `ad.headline_1`.

### Quick reference for `page_add`

```text
templates_tool(action="page_list", page_page=1, page_per_page=100)
templates_tool(
  action="page_add",
  page_name="<offer>",
  page_message="<offer>",
  page_owntmpl=1,
  page_ownurl="<slug>",
  page_meta_title="...", page_meta_description="...", page_meta_keywords="...",
  page_tmpl="<!DOCTYPE html>... $POWERED_BY$ after </body>"
)
templates_tool(action="page_get", page_id=<new_id>)
```

Shared CSS/JS for every segment: one `/css/style.css`, one `/js/script.js`, different content in `page_tmpl`.

---

## 1) Scope

The playbook is mandatory for:
- **segment landing pages** (the default): `page_add` + `page_ownurl` for each traffic segment;
- a homepage landing page (only on request): `page_id=1`, `page_owntmpl=1`;
- a lead form / menu / SEO / responsive layout on any of the above.

Landing HTML is always passed in `page_tmpl`, not in `page_message` (`page_message` holds the title only).

---

## 2) Core principles (mandatory)

1. Work only through MCP tools (`templates_tool`, `ftp_tool`) — without manual guesses. For the homepage, use `templates_tool` with the actions `page_list`, `page_get`, `page_update`.
2. After `page_add`/`page_update`, run `validate_template` when the action supports this template type. If the tool does not apply, save the exact reason and supplement the check with `page_get` + the public URL; do not skip validation silently.
3. Do not delete or break uCoz variables and system constructs.
4. Do not remove `$POWERED_BY$` — it must stand after `</body>`.
5. Do not use a static year in the footer — only `$YEAR$`.
6. Menus go only through menu actions. A horizontal header is `$SMENU_N$`; a vertical placement (sidebar, footer column, a list in a drawer) is `$NMENU_N$`. Which variable to place is decided by the orientation in `references/VISUAL.md`, not by the `layout` parameter of `menu_create`.
7. Create a lead form only if the user explicitly asked for one; if it is needed, only through mail actions + output via `$MFORM_N$`.
8. Landing styles go in a separate `style.css` file, uploaded to the site in the `/css` folder.
9. Landing scripts go in a separate `script.js` file, uploaded to the site in the `/js` folder.
10. For CSS/JS, use URL versioning (`?v=<ASSET_VERSION>`), and increment the version on every file change.
11. The landing page must be responsive: correct display at a minimum of 320/375/768/1024+ px.
12. Always add Open Graph and Twitter Card meta tags in `<head>`.
13. Form field names must be SEO-semantic and match the page intent.
14. Do not use emoji as icons in the landing UI — SVG only (inline or as a file).
15. Fields `f1` and `f2` are system fields: `f1` is email, `f2` is name. They must not be deleted or replaced; only the labels may change.
16. Store text in HTML in readable UTF-8 form; do not encode Cyrillic into HTML entities such as `&#1057;...` without a technical need.
17. After adding a mail form, always override the styles of the standard uCoz modal to match the landing style. The window uses a 9-slice model — reset `background-image` on all 9 frame parts (`.xw-tl`, `.xw-tr`, `.xw-tc`, `.xw-ml`, `.xw-mr`, `.xw-mc`, `.xw-bl`, `.xw-br`, `.xw-bc`), and also override `.xw-shadow`, `.xw-sps`, `.xw-hdr`, `.xw-hdr-text`, `.xw-body`, `.myWinCont`, `.myWinCont b`, `.myWinFooter`, `.xt-close`, `.xt-close2`, `.xw-blank`, `.xw-disabled`. The detailed table is in step 4.1, item 7.
18. At mobile sizes (≤768 px) the navigation menu is hidden and replaced by a burger button. The toggle logic lives in `script.js`, the styles in `style.css`, and the button in the template HTML.
19. The logo and the favicon must be visually identical. The favicon is generated programmatically from the SVG logo in the landing header. Upload it via FTP as `/favicon.ico` and link it in `<head>` via `<link rel="icon">`. If FTP is unavailable, save it locally and tell the user.

20. Before Step 4 (any CSS or HTML), read `references/VISUAL.md` and follow it: style choice, grid, section recipes, the typography and spacing floor, `:root` tokens, component states, icons (Heroicons / Lucide / Tabler), CSS/JS tiers, motion, images, social proof, copy voice, and the accessibility checklist. Brand colors and fonts set the token values, but they do not cancel the typography floor, the `:root` structure, the icon source, or the tiers. For an ad series, use one visual direction for the whole site. If a general VISUAL rule conflicts with hyper-segmentation, `VISUAL.md` §0 takes priority.

### 2.1) Practical visual standard for the series

- Use the user's exact logo and brand references; do not replace them with an approximate mark.
- Take every UI icon from one SVG set. Check that stroke, size, and color are consistent.
- Show 24/7 support as a visible advantage when the product confirms it.
- A fixed header is allowed; add `scroll-margin-top` so anchors do not slide under it.
- Do not publish technical explanations of the A/B test, the price, or the button route.
- For site examples, use images without cropping: `object-fit: contain` or the natural aspect ratio. Do not use `cover` if it cuts text or the interface.
- When fixing the source site, recapture the image and bump the asset version.
- While screenshots are missing, use neutral placeholders; do not generate a fictional product interface.

---

## 3) Agent procedure

### Step 0. Clarify requirements with the user

Before the first template edit, find out:
- the landing structure (blocks: hero, benefits, FAQ, contacts, CTA),
- brand colors and fonts,
- whether a separate top-menu block is needed,
- whether a form is needed and which fields,
- whether a captcha is needed (default: no),
- SEO tone (commercial/informational),
- whether structured data is needed (Organization, WebSite, LocalBusiness, and so on).

Decision rule:
- if the prompt has no explicit form request ("form", "lead", "leave contacts") — do not create a form block and do not call `mail_*`;
- if a form is requested — complete step 5 in full.

**Visual direction (mandatory, before Step 4):**
- if the user provided brand colors and fonts — write them into the tokens of `references/VISUAL.md` §3.1 and pick the nearest named style from §1.2, so the character of the grid and components is set;
- if there is no brand — pick one style from §1.2 by niche (§1.3) and state it in one sentence before any CSS;
- before CSS, write down the non-negotiables and the "must not look like" list for the chosen style (§1.6);
- inside one ad series, do not change the style from segment to segment: a shared `style.css`.

### Step 1. Check pages via `templates_tool(action="page_list")`

Always start from the page list:
```text
templates_tool(action="page_list", page_page=1, page_per_page=100)
```

**Segment mode (default):**
1. Check whether `page_ownurl` from the manifest is free; on a conflict, use the suffix `-2` or ask the user.
2. If an existing page is being updated — `page_get` by its `page_id`.
3. Do not use `page_id=1` unless the user explicitly asked for the homepage.

**Homepage mode (only on request):**
1. Find the homepage `page_id=1` in the list.
2. `templates_tool(action="page_get", page_id=1)`.
3. If `owntmpl=1` — read `tmpl` and continue editing.
4. If `owntmpl=0` — enable it via `page_update` at step 10; do not ask the user to switch it in the control panel.

**For every mode:** HTML only in `page_tmpl`; the title only in `page_message`; `$POWERED_BY$` is required after `</body>`.

### Step 2. Check available modules (recommended)

```text
templates_tool(action="list_modules")
```

Use the response to check the operations that follow.

### Step 3. Menu — through the API only

1. Get the menu list:
```text
templates_tool(action="menu_list")
```

2. If the needed menu does not exist — create it:
```text
templates_tool(action="menu_create", title="Landing Main Menu", layout=1)
```
`layout` only sets the orientation preview in the uCoz control panel and does not choose the variable in the template. A horizontal header (most `VISUAL.md` recipes) is `$SMENU_<ID>$`. `$NMENU_<ID>$` is for vertical placements. If it is unclear — `templates_tool(action="get_guide")`.

3. Update the items:
```text
templates_tool(action="menu_update_items", menu_id=<ID>, items=[...])
```

Menu item requirements:
- every item must have the `URL` field filled in;
- for a landing page — anchor links (`#hero`, `#about`, `#faq`, `#contacts`, and so on);
- do not leave `URL` empty — otherwise menu styles and behavior break.

4. In the template HTML, output the menu only through the variable of the chosen orientation:
```text
$SMENU_<ID>$
```
for a horizontal header, or
```text
$NMENU_<ID>$
```
for a vertical block. Do not mix both variables in one navigation block.

5. Burger menu on mobile (mandatory):
- at a width of ≤768 px the navigation is hidden (`display:none`) and the burger button is shown;
- the button is built in the template HTML (inline SVG, not emoji);
- the toggle logic (open/close) lives strictly in `/js/script.js`;
- burger styles and the open mobile menu live in `/css/style.css`;
- a click on an anchor item closes the mobile menu automatically.

Forbidden:
- building the menu by hand with `<ul><li>` instead of the API,
- mixing a static menu and `$NMENU_N$` for one navigation block,
- creating items with an empty `URL` field,
- leaving desktop navigation without a burger on mobile (≤768 px);
- picking `$NMENU_N$` or `$SMENU_N$` at random, without the orientation from `VISUAL.md`.

### Step 4. Styles — only in a separate `style.css` + upload via FTP

Before the first rule, read `references/VISUAL.md` (the style catalog, the typography floor, the token contract, icons, the CSS toolbox). `style.css` opens with the `:root` block from §3.1. CSS nesting is no deeper than 3 levels. Selectors that override uCoz system markup (`.xw-*`, menu classes) stay outside `@layer` (`VISUAL.md` §5).

1. Generate or update the `style.css` contents locally.
2. Check the `/css` folder:
```text
ftp_tool(action="list", path="/")
```
3. If `/css` does not exist — create it:
```text
ftp_tool(action="mkdir", path="/css")
```
4. Upload the file:
```text
ftp_tool(action="write", path="/css/style.css", content="<full CSS>")
```
or
```text
ftp_tool(action="upload", local_path="<local path>", remote_path="/css/style.css")
```
5. Link it in the template with a version:
```html
<link rel="stylesheet" href="/css/style.css?v=<ASSET_VERSION>">
```

### Step 4.1. JS — only in a separate `script.js` + upload via FTP

`script.js` is one plain file: no `type="module"`, no bundler, and no CDN frameworks. Follow the tiers from `references/VISUAL.md` §7: `?.` and `??` around `querySelector`, `IntersectionObserver` for block reveal, `requestAnimationFrame` for scroll/resize. **Do not intercept `$MFORM_<ID>$` submission via `fetch` or `FormData`** — the platform handles the form itself and shows the `_uWnd` modal.

1. Generate or update the `script.js` contents locally.
2. Check the `/js` folder:
```text
ftp_tool(action="list", path="/")
```
3. If `/js` does not exist — create it:
```text
ftp_tool(action="mkdir", path="/js")
```
4. Upload the file:
```text
ftp_tool(action="write", path="/js/script.js", content="<full JS>")
```
or
```text
ftp_tool(action="upload", local_path="<local path>", remote_path="/js/script.js")
```
5. Link it in the template before `</body>` with a version:
```html
<script src="/js/script.js?v=<ASSET_VERSION>"></script>
```

6. When CSS/JS changes, increment `<ASSET_VERSION>` (for example, `202604091530`).
7. If the page uses `$MFORM_<ID>$`, `style.css` must override the styles of the standard uCoz modal.

**Full structure of the uCoz modal (9-slice frame):**

The window is built on a table 9-slice model with background images. Every element must be reset (`background: none !important; background-image: none !important; border: none !important;`), otherwise the default frame remains:

| Group | Selectors | Purpose |
|---|---|---|
| Frame (9 parts) | `.xw-tl`, `.xw-tr`, `.xw-tc`, `.xw-ml`, `.xw-mr`, `.xw-mc`, `.xw-bl`, `.xw-br`, `.xw-bc` | Corners and sides of the window frame (background-image) |
| Container | `.xw-shadow` | Outer container; set border + box-shadow to match the landing style |
| Header | `.xw-hdr`, `.xw-hdr-text`, `.xw-draggable` | Window title; reset background-image |
| Body | `.xw-body`, `.myWinCont` | Message content |
| Footer | `.myWinFooter` | Bottom row inside the window |
| Close button | `.xt-close`, `.xt-close2` | Both classes; background-image by default; reset it and set a custom style (via `::before { content: 'X' }` or inline SVG) |
| Utility | `.xw-sps`, `.xw-blank`, `.xw-disabled` | Separator, placeholder, dimming while blocked |

**Required styling actions:**
1. Reset `background`, `background-image`, and `border` on all 9-slice elements (`.xw-tl` ... `.xw-bc`).
2. Reset `background-image` on `.xw-hdr`, `.xt-close`, `.xt-close2`.
3. Hide `.xw-sps` (`display: none`).
4. Give `.xw-shadow` a border + box-shadow instead of the 9-slice frame.
5. For `.xt-close` / `.xt-close2`, use `::before { content: 'X' }` with a pixel font — the default cross is a background-image.
6. Style `.myWinCont b` (the title in the success message) and the regular text.
7. Bring the window into the visual style of the landing page (colors, typography, spacing, close button).
8. Do not rely on inline styles from the system window markup.

### Step 4.2. Favicon — generated from the logo (mandatory)

The favicon must match the official logo. First use a ready-made brand favicon; if there is none, create one locally with a suitable available tool. PowerShell is not mandatory.

If there is no official logo, do not skip the step: first a monogram per `references/VISUAL.md` §4.1 (an accent-color plate and 1–2 letters) in the header next to the name, then a favicon from that mark. If a logo exists, do not substitute a monogram for it (`VISUAL.md` §0).

**Algorithm:**

1. Take the logo parameters from the header HTML (colors, shape, graphic motif).
2. Generate `favicon.ico` (32×32, ICO format with an embedded PNG). Below is one possible option for an environment with PowerShell; in another environment, use an equivalent tool:

```powershell
Add-Type -AssemblyName System.Drawing

$size = 32
$bmp = New-Object System.Drawing.Bitmap($size, $size)
$g = [System.Drawing.Graphics]::FromImage($bmp)
$g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias

# Background: rounded rectangle (colors taken from the landing page logo)
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

# Graphic motif (matches the SVG logo shape): lines, arcs, circle, etc.
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

3. Upload the file to the site root via FTP:
```text
ftp_tool(action="upload", local_path="<path>/favicon.ico", remote_path="/favicon.ico")
```
or
```text
ftp_tool(action="write", path="/favicon.ico", content="<binary>")
```

4. Add the tag to the template `<head>` (immediately after `<meta charset>`):
```html
<link rel="icon" type="image/x-icon" href="/favicon.ico">
```

**Rules:**
- The favicon colors, shape, and graphic motif must match the SVG logo in the header.
- If FTP is unavailable, save the file locally and explicitly tell the user to upload it manually through the uCoz control panel file manager.
- Do not use emoji or the browser's default favicon — only the generated file.

---

### Step 5. Mail form via `mail_*` actions

Run this only if the form is explicitly requested in the prompt.

1. Create the form:
```text
templates_tool(action="mail_create_form", name="Landing Form", ...)
```

Before `mail_create_form`:
- get the list of existing forms (`mail_list_forms`);
- take the recipient email from forms that already exist (priority);
- ask the user only if the email is not found.

**If the form already exists (use the existing one):**
- always call `mail_list_fields` and check every field;
- if there is a type 7 field (captcha) and the user did **not** ask for a captcha — delete it via `mail_delete_field`;
- if there are extra fields (not the system `f1`/`f2`) — clarify with the user or delete them;
- after deletion, always sync the email template (`mail_get_template` → `mail_save_template`).

2. Add fields:
```text
templates_tool(action="mail_add_field", form_id=<ID>, ...)
```

3. Captcha: add a type 7 field only on an **explicit** request. If a captcha is already present in an existing form and the user did not ask for it — delete it (`mail_delete_field`).

4. After field changes, always sync the email HTML:
```text
templates_tool(action="mail_get_template", form_id=<ID>)
templates_tool(action="mail_save_template", form_id=<ID>, template_html="<email HTML>")
```

5. In the page `tmpl`, output only:
```text
$MFORM_<ID>$
```

6. Field names and labels are SEO-semantic:
- clear names by page intent, for example: "Your name", "Phone", "Email", "City", "Comment";
- for commercial landing pages, add the service entity, for example: "Service type", "Project type";
- do not use technical or empty names such as "field1", "field 2", "text";
- natural language, without keyword stuffing.

7. Fields `f1` and `f2` are system fields:
- `f1` is email, `f2` is name;
- do not delete `f1`/`f2` via `mail_delete_field`;
- do not create duplicate email/name fields instead of `f1`/`f2`;
- only the labels may change; do not delete or replace the system fields.

8. When editing the form HTML or the email template, keep system field names strictly in the format `name="fN"`:
- after `mail_add_field`/`mail_delete_field`/`mail_move_field`, check the current set via `mail_list_fields`;
- do not rename `name="fN"` to arbitrary values (`name="phone"`, `name="email"`, and so on);
- do not mix up `fN` between fields: labels may change, the system `name` may not;
- when the field structure changes, always sync the HTML via `mail_get_template` → `mail_save_template`;
- after any field operation, make sure `f1` and `f2` are present on the form.

Forbidden:
- creating a "dummy" form without `mail_create_form`,
- changing fields without updating the email template.

### Step 6. SEO block (mandatory template)

In `<head>`, use exactly this template:

```html
<title><?if($SEO_TITLE$)?>$SEO_TITLE$<?else?>Site name<?endif?></title>
<?if($SEO_DESCRIPTION$)?><meta name="description" content="$SEO_DESCRIPTION$" /><?else?><meta name="description" content="Page description" /><?endif?>
<?if($SEO_KEYWORDS$)?><meta name="keywords" content="$SEO_KEYWORDS$" /><?else?><meta name="keywords" content="Keywords" /><?endif?>
```

Note: the agent adapts the fallback texts (`Page description`, `Keywords`) to the project topic, but the conditional structure stays unchanged.

### Step 6.1. Open Graph and Twitter Card (mandatory)

Add at least the following in `<head>`:

```html
<meta property="og:type" content="website" />
<meta property="og:title" content="<?if($SEO_TITLE$)?>$SEO_TITLE$<?else?>Site name<?endif?>" />
<meta property="og:description" content="<?if($SEO_DESCRIPTION$)?>$SEO_DESCRIPTION$<?else?>Page description<?endif?>" />
<meta property="og:url" content="$HOME_PAGE_LINK$" />
<meta property="og:site_name" content="$SITE_NAME$" />
<meta property="og:image" content="https://site.com/path/to/og-image.jpg" />
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="<?if($SEO_TITLE$)?>$SEO_TITLE$<?else?>$SITE_NAME$<?endif?>" />
<meta name="twitter:description" content="<?if($SEO_DESCRIPTION$)?>$SEO_DESCRIPTION$<?else?>Page description<?endif?>" />
<meta name="twitter:image" content="https://site.com/path/to/og-image.jpg" />
```

Rules:
- `og:image` and `twitter:image` must point to a real public image;
- use absolute URLs;
- do not leave `site.com` placeholders in the final template.

### Step 7. Schema.org structured data (mandatory)

Add JSON-LD in `<head>` (at least one relevant type):
- `Organization` or `LocalBusiness` for a company,
- `WebSite` when needed,
- `ContactPage` fits a contacts block.

Minimum requirements:
- valid JSON,
- real site data (name, URL, contacts),
- no placeholders such as `TODO`.

### Step 8. Footer and required system variables

1. Use `$YEAR$` in the footer, not a static number.
2. After the closing `</body>`, always add or keep:
```html
$POWERED_BY$
```

### Step 9. Responsiveness (mandatory check)

Before the final save, make sure:
- there is no horizontal scroll at 320 px;
- the key blocks (hero, CTA, form, footer) are readable and clickable at 320/375/768/1024+ px;
- images and buttons do not overflow the container;
- the menu and anchors work correctly on mobile;
- the burger button is visible and clickable at 320/375 px;
- the mobile menu opens and closes without artifacts;
- a click on an anchor menu item closes the mobile menu.

**Visual QA (`references/VISUAL.md` §11) — together with the check above:**
- a visible `:focus-visible` focus, hit areas no smaller than 44×44 px, text contrast no lower than WCAG AA;
- hero height via `dvh`, not `vh`; headline size on the §2.1 scale, not the habitual 68–82 px;
- one directed motion point and `prefers-reduced-motion`;
- CSS nesting ≤ 3; `.xw-*` and menu overrides outside `@layer`;
- one container per page from the §1.4 range; sections follow the §1.5 recipes, not the same three-card grid everywhere;
- button, field, and card states per §3.5; section background rhythm per §3.6;
- no stock clichés from §8.1; product screenshots with `object-fit: contain` (§0).

### Step 10. Publishing the landing page

#### 10A. Segment page (default) — `page_add` or `page_update`

**New segment:**
```text
templates_tool(action="page_add", page_owntmpl=1, page_ownurl="<slug>", page_name="...", page_message="...", page_tmpl="<HTML>", page_meta_title="...", page_meta_description="...", page_meta_keywords="...")
```

**Edit an existing segment:**
```text
templates_tool(action="page_update", page_id=<ID>, ...)
```

After publishing: check `id` and `url` in the response; run `page_get`; write `ucoz.page_id` and `ucoz.published: true` into `segments.json`. Only then include `final_url` in OUTPUT_PACK.

#### 10B. Homepage (only on an explicit user request) — `page_update` on `page_id=1`

```text
templates_tool(action="page_update", page_id=1, page_owntmpl=1, page_message="<title>", page_tmpl="<new HTML>", page_meta_title="...", page_meta_description="...", page_meta_keywords="...")
```

Publishing rules (both modes):
- `page_tmpl` is a full HTML document; the hero must match the ad headline (`COPY.md §Message Match`);
- `page_message` is the title only;
- pass the meta fields explicitly;
- check via `page_get` after saving.

After saving, run `validate_template` if it supports the page or the template in use. If an error comes back or the action does not apply, record the response and run `page_get` + a public URL check.

---

## 4) Checklist before handoff

The agent must check and explicitly confirm:
- [ ] The page list was read via `page_list`, and the needed page via `page_get`.
- [ ] The page was published via `page_update` / `page_add` with `page_owntmpl=1`; `page_tmpl` contains the landing HTML, `page_message` is the title only.
- [ ] `page_meta_title`, `page_meta_description`, and `page_meta_keywords` were passed explicitly.
- [ ] The menu was created or updated via `menu_*`; a horizontal header is output via `$SMENU_N$`, a vertical block via `$NMENU_N$`.
- [ ] Every menu item has the `URL` field filled in (anchor links for a landing page).
- [ ] The burger menu is implemented: an SVG button in the HTML, the toggle in `script.js`, the styles in `style.css`.
- [ ] At 320/375 px the desktop navigation is hidden, the burger works, and the menu closes on an item click.
- [ ] `style.css` was created and uploaded to `/css/style.css`.
- [ ] The template links `/css/style.css?v=<ASSET_VERSION>`.
- [ ] `script.js` was created and uploaded to `/js/script.js`.
- [ ] The template links `/js/script.js?v=<ASSET_VERSION>` (before `</body>`).
- [ ] The mail form was created via `mail_create_form` or an existing one was used — it is output via `$MFORM_N$`.
- [ ] For an existing form, every field was checked via `mail_list_fields`; extra fields (including a type 7 captcha that was not requested) were deleted via `mail_delete_field`.
- [ ] For a new form, the recipient email was taken from existing mail forms (if any).
- [ ] Form field names are SEO-semantic and human-readable.
- [ ] Form fields keep the correct system `name="fN"` according to the `mail_list_fields` data.
- [ ] Field `f1` is used as the only email field (no extra email fields were created).
- [ ] System fields `f1` (email) and `f2` (name) are kept and were not deleted.
- [ ] Form styles override the standard uCoz modal — the full 9-slice set plus content: `.xw-shadow`, `.xw-tl`..`.xw-bc` (`background-image` reset), `.xw-sps` (hidden), `.xw-hdr`, `.xw-hdr-text`, `.xw-body`, `.myWinCont`, `.myWinCont b`, `.myWinFooter`, `.xt-close`/`.xt-close2` (`background-image` reset, `::before` with a cross added), `.xw-disabled`.
- [ ] After form field changes, the email HTML template was updated (`mail_get_template` + `mail_save_template`).
- [ ] The footer uses `$YEAR$`.
- [ ] `$POWERED_BY$` is present after `</body>`.
- [ ] The SEO block is inserted in the mandatory format with `SEO_*` conditions.
- [ ] Open Graph and Twitter Card tags were added with real values.
- [ ] Relevant Schema.org structured data was added.
- [ ] Responsiveness was checked at 320/375/768/1024+ px.
- [ ] There are no emoji icons in the UI; icons are inline SVG from Heroicons, Lucide, or Tabler, one set and one stroke (`VISUAL.md` §4).
- [ ] The series style was chosen from `VISUAL.md` §1.2 and stated before CSS; inside the series the style does not change from URL to URL.
- [ ] Non-negotiables and the "must not look like" list were written down before CSS (`VISUAL.md` §1.6).
- [ ] `style.css` opens with the `:root` contract from `VISUAL.md` §3.1.
- [ ] The container, section recipes, states, background rhythm, images, and copy voice were checked against `VISUAL.md` §1.4, §1.5, §3.5, §3.6, §8, §10.
- [ ] Footer: `$YEAR$`, small and muted, separate from the link columns (`VISUAL.md` §10.1).
- [ ] If there was no logo — a §4.1 monogram before the favicon; if there was a logo — that logo was used, not a monogram.
- [ ] `script.js` does not intercept `$MFORM_N$` via `fetch` / `FormData`.
- [ ] The favicon was generated from the SVG logo (colors and shape match); `favicon.ico` was uploaded to the site root or saved locally with a notice to the user.
- [ ] `<link rel="icon" type="image/x-icon" href="/favicon.ico">` was added to `<head>`.
- [ ] After `page_update`, `id` and `url` in the response were checked; `page_get` was run when needed.
- [ ] `validate_template` was run, or the exact reason it does not apply was recorded; `page_get` and the public URL were also checked.

---

## 5) Minimum MCP call order

### Segment page (default)

```text
1) templates_tool(action="page_list", page_page=1, page_per_page=100)
2) templates_tool(action="menu_list")
3) ftp: /css/style.css, /js/script.js, /favicon.ico (shared by all segments)
4) mail_* (only if a form was requested)
5) templates_tool(action="page_add", page_owntmpl=1, page_ownurl=..., page_tmpl=..., meta_*)
6) templates_tool(action="page_get", page_id=<new>) → confirm url
7) check message match (COPY.md §Message Match)
```

### Homepage (only on request)

```text
1) templates_tool(action="page_list", page_page=1, page_per_page=100)
2) templates_tool(action="page_get", page_id=1)
3) templates_tool(action="menu_list")
4) templates_tool(action="menu_create"/"menu_update"/"menu_update_items", ...)
5) ftp_tool(action="list", path="/")
6) ftp_tool(action="mkdir", path="/css")   // if missing
7) ftp_tool(action="write", path="/css/style.css", content="...")
8) ftp_tool(action="mkdir", path="/js")    // if missing
9) ftp_tool(action="write", path="/js/script.js", content="...")
9.1) Shell: PowerShell → generate favicon.ico from the logo
9.2) ftp_tool(action="upload", local_path="<path>/favicon.ico", remote_path="/favicon.ico")
10) templates_tool(action="mail_create_form", ...)
11) templates_tool(action="mail_add_field", ...)
12) templates_tool(action="mail_get_template", form_id=...)
13) templates_tool(action="mail_save_template", form_id=..., template_html="...")
14) templates_tool(action="page_update", page_id=1, ...)
15) templates_tool(action="page_get", page_id=1)
```

For the homepage: if `owntmpl=0`, enable it via step 14 `page_update`; do not ask the user to switch it in the control panel.

---

## 6) Prohibitions

- Do not skip reading pages via `page_list` and `page_get` before editing.
- Do not ask the user to manually enable the homepage custom template in the uCoz control panel.
- Do not publish a landing page without `page_owntmpl=1`.
- Do not put template HTML into `page_message`, and do not duplicate markup in `page_message` and `page_tmpl`.
- Do not pass `page_tmpl` without `$POWERED_BY$`.
- Do not publish a page without explicitly passing `page_meta_title`, `page_meta_description`, and `page_meta_keywords`.
- Do not build the menu by hand instead of `menu_*`.
- Do not hand off a landing page without a burger menu on mobile (≤768 px).
- Do not put burger logic inline in the template — only in `/js/script.js`.
- Do not create menu items with an empty `URL` field — use anchors for landing pages.
- Do not leave styles in a `<style>` block inside the landing page as the primary source (the primary source is `/css/style.css`).
- Do not insert a large inline JS block into the template as the primary source (the primary source is `/js/script.js`).
- Do not link CSS/JS without a version in the URL (`?v=<ASSET_VERSION>` is required).
- Do not hand off a landing page without a responsiveness check (minimum 320/375/768/1024+).
- Do not use a static year in the footer.
- Do not delete or omit `$POWERED_BY$`.
- Do not add a form without `mail_*` actions.
- Do not create a form "by default" if the prompt did not explicitly request one.
- Do not leave a captcha (type 7) on an existing form without a request — delete it via `mail_delete_field`.
- Do not use an existing form without first checking every field via `mail_list_fields`.
- Do not ignore the email from existing mail forms when creating a new one.
- Do not create form fields with non-semantic names (`field1`, `field2`, `test`) in the final version.
- Do not break system form field names (the `name="fN"` structure is required, otherwise the form will not work).
- Do not delete the system fields `f1` and `f2` (only the labels may change).
- Do not create new email/name fields instead of the system `f1`/`f2`.
- Do not mass-replace text with HTML entities (`&#...;`) without a technical need.
- Do not leave the standard uCoz modal dialog without style overrides for the landing page. Selectors required for a **full** override (9-slice frame + content): `.xw-shadow`, `.xw-tl`, `.xw-tr`, `.xw-tc`, `.xw-ml`, `.xw-mr`, `.xw-mc`, `.xw-bl`, `.xw-br`, `.xw-bc`, `.xw-sps`, `.xw-blank`, `.xw-hdr`, `.xw-hdr-text`, `.xw-body`, `.myWinCont`, `.myWinCont b`, `.myWinFooter`, `.xt-close`, `.xt-close2`, `.xw-disabled` — all of them must match the visual style of the landing page. Resetting `background-image` on the 9-slice elements is mandatory, otherwise the default window frame remains.
- Do not leave Open Graph with placeholders or without `og:image`.
- Do not use emoji as UI icons — SVG only.
- Do not publish a landing page without a favicon: `favicon.ico` must be generated and uploaded (or saved locally with an explicit notice to the user).
- Do not create a favicon that does not visually match the logo in the landing header (different colors, shape, or motif).
- Do not write CSS without reading `references/VISUAL.md`, and do not reuse one universal look for unrelated niches.
- Do not draw icons with custom paths, and do not mix sets; the source is Heroicons, Lucide, or Tabler.
- Do not hide `.xw-*` and menu overrides inside `@layer`; CSS nesting is no deeper than 3 levels.
- Do not intercept `$MFORM_N$` via `fetch` or `FormData`.
- Do not load GSAP, Tailwind, or CDN UI frameworks — CSS and JS live only in `style.css` and `script.js`.
- Do not animate without `prefers-reduced-motion`, and do not use `100vh` where `dvh` is required.
- Do not repeat the same three-card grid in every section, and do not leave component states at default and hover only.
- Do not use stock clichés (a handshake, a team fist bump, a stock smile) instead of the direction from `VISUAL.md` §8.1 when there are no real photos.
- Do not replace the official logo with a monogram, and do not leave the header without a graphic anchor if there is no logo.

---

## 7) Report format after completion

At the end, the agent provides a short technical report:
1. Which actions were called (in order), including `page_list`, `page_get`, `page_update`/`page_add`.
2. The `page_get` result and the publication result (URL, SEO fields).
3. The ID of the created or updated menu.
4. The ID of the created form.
5. Where the CSS was uploaded (`/css/style.css`).
6. Where the JS was uploaded (`/js/script.js`).
7. Which SEO, Open Graph/Twitter, and schema blocks were added.
8. Responsiveness confirmation (320/375/768/1024+).
9. Favicon status: uploaded to FTP (`/favicon.ico`) or saved locally (with the path and manual-upload instructions).
10. The `validate_template` result, or the exact reason it does not apply and the fallback that was run.
11. The chosen style (`VISUAL.md` §1.2) and where the token contract lives (`style.css` `:root`).
12. Whether there was an official logo or a generated monogram (`VISUAL.md` §4.1).
