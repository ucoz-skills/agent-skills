# uCoz Landing Publishing Playbook (Ad Campaign Landing Skill)

Unified playbook for publishing landings on uCoz via MCP (`templates_tool`, `ftp_tool`).
**Do not use** the separate `ucoz-landing-agent` skill — all rules are here.

Connection to marketing: `COPY.md §Message Match` (hero = ad headline), `PIPELINE.md` (full pipeline).

---

## 0) Default mode: segment landing

Paid traffic → **separate page per segment**, not the homepage.

| Parameter | Value |
|-----------|-------|
| Creation | `templates_tool(action="page_add")` |
| Slug | `page_ownurl` from `segments.json` (deterministic slug) |
| HTML | `page_tmpl` (full document), title in `page_message` |
| Hero | Strictly the ad offer (`COPY.md §Message Match`) |

**Order (ad + landing in one pipeline):**

```text
1) segment_id + page_ownurl + ad (from CSV or generation)
2) page_list → check for available slug
3) CSS/JS/favicon/menu/form (steps 3–5 below) — once per site or v+N
4) page_add → page exists, response has id + url
5) agent builds UTM `final_url` per PIPELINE.md §6
6) Ad in OUTPUT_PACK already has a working link (don't give URL before page_add)
```

**URL and "chicken/egg":** slug is known before HTML; page must be published **before** giving `final_url` to the marketer. No lazy-render — static `page_add` on uCoz.

### UTM on landing

| Where | Rule |
|-------|------|
| **Ad** | `final_url` with UTM — primary attribution channel |
| **Anchor CTAs** (`href="#order"`) | UTM is already in the address bar — no separate tags needed |
| **Internal links** (other pages on same domain) | Optional: snippet below in `/js/script.js` — carries `utm_*` to other site pages |
| **External links** (messengers, other domain) | Add UTM manually in `href` if needed |
| **Buttons** | Don't duplicate UTM in HTML; for `<button>` without navigation, analytics counts the visit via the landing URL |

**Optional (only if landing has links to other site pages):** add to the end of `/js/script.js`:

```javascript
// UTM-preserve: carries utm_* from the ad landing to internal links
(function () {
  var params = new URLSearchParams(window.location.search);
  var keys = ["utm_source","utm_medium","utm_campaign","utm_content","utm_term"];
  var utm = [];
  keys.forEach(function(k){ var v=params.get(k); if(v) utm.push(encodeURIComponent(k)+"="+encodeURIComponent(v)); });
  if (!utm.length) return;
  var suffix = "?" + utm.join("&");
  document.addEventListener("DOMContentLoaded", function(){
    document.querySelectorAll("a[href]").forEach(function(a){
      var h = a.getAttribute("href");
      if (!h || /^(#|mailto:|tel:|javascript:)/i.test(h)) return;
      try {
        var u = new URL(h, window.location.origin);
        if (u.origin !== window.location.origin || u.search) return;
        a.setAttribute("href", u.pathname + suffix + (u.hash || ""));
      } catch(e){}
    });
  });
})();
```

On a typical segment landing (only anchors `#order`, `#faq`) this block is **not needed**.

**Homepage (`page_id=1`)** — only if user explicitly requests it. Then steps 1 and 10 below (homepage).

### 0.1) Critical: custom template (`owntmpl = 1`) — don't break content

**Source of truth:** MCP `get_guide` / uAPI Pages. Read this block before `page_add` / `page_update`.

| Rule | Action |
|------|--------|
| **All landing HTML** | Only in `page_tmpl` (full `<!DOCTYPE html>…</html>` + `$POWERED_BY$` after `</body>`) |
| **`page_message`** | Only the page **title** (same text as `page_name` / H1 for SEO), **no HTML** |
| **Prohibited** | `$PAGE_CONTENT$`, `$MODULE_CONTENT$` and any "guessed" content variables |
| **Prohibited** | Duplicating markup in both `page_message` and `page_tmpl` |
| **Bug symptom** | Site shows only background/styles, without hero and blocks → HTML went to wrong field instead of `page_tmpl` |

**Why:** uCoz has **no** universal `$PAGE_CONTENT$`. The "content" field in the API with `owntmpl=1` is not substituted into the custom template body like WordPress content.

**HTML generation:** agent creates a full `<!DOCTYPE html>…</html>` document following this playbook's rules, and passes it entirely in `page_tmpl` during `page_add`.

**Post-publish check:** `page_get` → in response `content` = short title; at public URL — full landing with H1 = `ad.headline_1`.

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

Shared CSS/JS for all segments: one `/css/style.css`, one `/js/script.js`, different content in `page_tmpl`.

---

## 1) Scope of application

The playbook is required for:
- **segment landings** (default): `page_add` + `page_ownurl` per each traffic segment;
- homepage landing (only on request): `page_id=1`, `page_owntmpl=1`;
- lead form / menu / SEO / responsive layout on any of the above.

Landing HTML is always passed in `page_tmpl`, not `page_message` (`page_message` — title only).

---

## 2) Core principles (mandatory)

1. Work only through MCP tools (`templates_tool`, `ftp_tool`) — no "manual" guessing. For homepage use `templates_tool` with actions `page_list`, `page_get`, `page_update`.
2. `validate_template` — only if user explicitly requests it; do not run automatically.
3. Do not delete or break uCoz variables and system constructs.
4. Do not remove `$POWERED_BY$` — it must be placed after `</body>`.
5. In the footer do not use a static year — only `$YEAR$`.
6. Menu — only via menu actions + `$NMENU_N$` variables (playbook describes vertical menu).
7. Create a lead form only if the user explicitly asked for it; if needed — only via mail actions + output via `$MFORM_N$`.
8. Landing styles — in a separate `style.css` file, upload to site in `/css` folder.
9. Landing scripts — in a separate `script.js` file, upload to site in `/js` folder.
10. For CSS/JS use URL versioning (`?v=<ASSET_VERSION>`), increment version on each file change.
11. Landing must be responsive: correct display at minimum 320/375/768/1024+ px.
12. In `<head>` always add Open Graph and Twitter Card meta tags.
13. Form field names must be SEO-semantic and match the page intent.
14. Do not use emojis as UI icons on the landing — only SVG (inline or as file).
15. Fields `f1` and `f2` are system fields: `f1` — email, `f2` — name. Cannot be deleted or replaced; only labels may be changed.
16. Store text in HTML in readable UTF-8 form; do not encode Cyrillic as HTML entities like `&#1057;...` without technical necessity.
17. After adding a mail form always override the styles of the standard uCoz modal window to match the landing style. The window uses a 9-slice model — reset `background-image` on all 9 border parts (`.xw-tl`, `.xw-tr`, `.xw-tc`, `.xw-ml`, `.xw-mr`, `.xw-mc`, `.xw-bl`, `.xw-br`, `.xw-bc`), and also override `.xw-shadow`, `.xw-sps`, `.xw-hdr`, `.xw-hdr-text`, `.xw-body`, `.myWinCont`, `.myWinCont b`, `.myWinFooter`, `.xt-close`, `.xt-close2`, `.xw-blank`, `.xw-disabled`. Detailed table — in step 4.1, item 7.
18. On mobile (≤768 px) the navigation menu is hidden, replaced by a burger button. Toggle logic — in `script.js`, styles — in `style.css`, button — in HTML template.
19. Logo and favicon must be visually identical. Favicon is generated programmatically from the SVG logo in the page header. Upload via FTP as `/favicon.ico`, link in `<head>` via `<link rel="icon">`. If FTP is unavailable — save locally and notify the user.

---

## 3) Agent action order

### Step 0. Clarify requirements with user

Before editing the template, determine:
- landing structure (blocks: hero, benefits, FAQ, contacts, CTA),
- brand colors and fonts,
- whether a separate top menu block is needed,
- whether a form is needed and which fields,
- whether captcha is needed (default: no),
- SEO tone (commercial/informational),
- whether microdata is needed (Organization, WebSite, LocalBusiness, etc.).

Decision rule:
- if the prompt does not explicitly request a form ("form", "application", "leave contacts") — do not create a form block and do not call `mail_*`;
- if form is requested — complete step 5 fully.

### Step 1. Check pages via `templates_tool(action="page_list")`

Always start with the page list:
```text
templates_tool(action="page_list", page_page=1, page_per_page=100)
```

**Segment mode (default):**
1. Check if `page_ownurl` from the manifest is available; on conflict — add suffix `-2` or ask user.
2. If updating an existing page — `page_get` by its `page_id`.
3. Do not use `page_id=1` unless the user explicitly asked for the homepage.

**Homepage mode (only on request):**
1. Find the homepage `page_id=1` in the list.
2. `templates_tool(action="page_get", page_id=1)`.
3. If `owntmpl=1` — read `tmpl` and continue editing.
4. If `owntmpl=0` — enable via `page_update` in step 10, do not ask user to switch in the panel.

**For all modes:** HTML only in `page_tmpl`; title only in `page_message`; `$POWERED_BY$` required after `</body>`.

### Step 2. Check available modules (recommended)

```text
templates_tool(action="list_modules")
```

Use the response to verify subsequent operations.

### Step 3. Menu — only via API

1. Get menu list:
```text
templates_tool(action="menu_list")
```

2. If the required menu doesn't exist — create it:
```text
templates_tool(action="menu_create", title="Landing Main Menu", layout=1)
```
`layout=1` → vertical menu (output via `$NMENU_N$`).

3. Update items:
```text
templates_tool(action="menu_update_items", menu_id=<ID>, items=[...])
```

Menu item requirements:
- every item must have the `URL` field filled;
- for a landing — anchor links (`#hero`, `#about`, `#faq`, `#contacts`, etc.);
- do not leave `URL` empty — this breaks menu styles and behavior.

4. In the HTML template output menu only via:
```text
$NMENU_<ID>$
```

5. Burger menu on mobile (required):
- at ≤768 px navigation is hidden (`display:none`), a burger button is shown;
- button is built in HTML template (inline SVG, not emoji);
- toggle logic (open/close) — strictly in `/js/script.js`;
- burger and open mobile menu styles — in `/css/style.css`;
- clicking an anchor item closes the mobile menu automatically.

Prohibited:
- building menu manually with `<ul><li>` instead of API,
- mixing static menu and `$NMENU_N$` for the same navigation block,
- creating items with empty `URL` field,
- leaving desktop navigation without a burger on mobile (≤768 px).

### Step 4. Styles — only in separate `style.css` + upload via FTP

1. Generate/update `style.css` content locally.
2. Check `/css` folder:
```text
ftp_tool(action="list", path="/")
```
3. If `/css` doesn't exist — create:
```text
ftp_tool(action="mkdir", path="/css")
```
4. Upload file:
```text
ftp_tool(action="write", path="/css/style.css", content="<full CSS>")
```
or
```text
ftp_tool(action="upload", local_path="<local path>", remote_path="/css/style.css")
```
5. In template, link with version:
```html
<link rel="stylesheet" href="/css/style.css?v=<ASSET_VERSION>">
```

### Step 4.1. JS — only in separate `script.js` + upload via FTP

1. Generate/update `script.js` content locally.
2. Check `/js` folder:
```text
ftp_tool(action="list", path="/")
```
3. If `/js` doesn't exist — create:
```text
ftp_tool(action="mkdir", path="/js")
```
4. Upload file:
```text
ftp_tool(action="write", path="/js/script.js", content="<full JS>")
```
or
```text
ftp_tool(action="upload", local_path="<local path>", remote_path="/js/script.js")
```
5. In template, link before `</body>` with version:
```html
<script src="/js/script.js?v=<ASSET_VERSION>"></script>
```

6. When changing CSS/JS — increment `<ASSET_VERSION>` (e.g., `202604091530`).
7. If page uses `$MFORM_<ID>$` — `style.css` must override the styles of the standard uCoz modal window.

**Full structure of uCoz modal window (9-slice border):**

The window is built on a tabular 9-slice model with background images. All elements need to be reset (`background: none !important; background-image: none !important; border: none !important;`), otherwise the default border remains:

| Group | Selectors | Purpose |
|---|---|---|
| Border (9 parts) | `.xw-tl`, `.xw-tr`, `.xw-tc`, `.xw-ml`, `.xw-mr`, `.xw-mc`, `.xw-bl`, `.xw-br`, `.xw-bc` | Window border corners and sides (background-image) |
| Container | `.xw-shadow` | Outer container; set border + box-shadow matching landing style |
| Header | `.xw-hdr`, `.xw-hdr-text`, `.xw-draggable` | Window title; reset background-image |
| Body | `.xw-body`, `.myWinCont` | Message content |
| Footer | `.myWinFooter` | Bottom row inside window |
| Close button | `.xt-close`, `.xt-close2` | Both classes; default background-image; reset and set custom style (via `::before { content: 'X' }` or inline SVG) |
| Service | `.xw-sps`, `.xw-blank`, `.xw-disabled` | Separator, placeholder, overlay when blocked |

**Required styling actions:**
1. Reset `background`, `background-image`, `border` on all 9-slice elements (`.xw-tl` ... `.xw-bc`).
2. Reset `background-image` on `.xw-hdr`, `.xt-close`, `.xt-close2`.
3. Hide `.xw-sps` (`display: none`).
4. Set `.xw-shadow` border + box-shadow instead of 9-slice border.
5. For `.xt-close` / `.xt-close2` use `::before { content: 'X' }` with pixel font — the default cross is a background-image.
6. Style `.myWinCont b` (heading in the success message) and regular text.
7. Bring the window to the landing's visual style (colors, typography, spacing, close button).
8. Do not rely on inline styles from the system window markup.

### Step 4.2. Favicon — generate from logo (required)

The favicon must be visually identical to the logo in the landing header. Generated via PowerShell with `System.Drawing`.

**Algorithm:**

1. Take logo parameters from the header HTML (colors, shape, graphic motif).
2. Generate `favicon.ico` (32×32, ICO format with embedded PNG) via PowerShell script:

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

4. Add tag to `<head>` template (immediately after `<meta charset>`):
```html
<link rel="icon" type="image/x-icon" href="/favicon.ico">
```

**Rules:**
- Favicon colors, shape, and graphic motif must match the SVG logo in the header.
- If FTP is unavailable — save file locally and explicitly tell the user to upload manually via the uCoz panel file manager.
- Do not use emoji or default browser favicon — only the generated file.

---

### Step 5. Mail form via `mail_*` actions

Only performed if a form is explicitly requested in the prompt.

1. Create form:
```text
templates_tool(action="mail_create_form", name="Landing Form", ...)
```

Before `mail_create_form`:
- get the list of existing forms (`mail_list_forms`);
- take recipient email from already created forms (priority);
- only if email not found — ask the user.

**If form already exists (using existing):**
- always call `mail_list_fields` and check all fields;
- if there's a field of type 7 (captcha) and user **did not** ask for captcha — delete via `mail_delete_field`;
- if there are extra fields (not system `f1`/`f2`) — clarify with user or delete;
- after deletion — always sync the email template (`mail_get_template` → `mail_save_template`).

2. Add fields:
```text
templates_tool(action="mail_add_field", form_id=<ID>, ...)
```

3. Captcha: add field type 7 only on **explicit** request. If captcha already exists in an existing form and user didn't request it — delete (`mail_delete_field`).

4. After changing fields always sync email HTML:
```text
templates_tool(action="mail_get_template", form_id=<ID>)
templates_tool(action="mail_save_template", form_id=<ID>, template_html="<email HTML>")
```

5. In page `tmpl` output only:
```text
$MFORM_<ID>$
```

6. Field names and labels — SEO-semantic:
- clear names by page intent, e.g.: "Your name", "Phone", "Email", "City", "Comment";
- for commercial landings — add service entity, e.g.: "Service type", "Project type";
- do not use technical or empty names like "field1", "field 2", "text";
- natural language, no keyword stuffing.

7. Fields `f1` and `f2` — system fields:
- `f1` — email, `f2` — name;
- do not delete `f1`/`f2` via `mail_delete_field`;
- do not create duplicate email/name fields instead of `f1`/`f2`;
- only labels may be changed, not deleted or replaced.

8. When editing form/email template HTML, preserve system field names strictly in the format `name="fN"`:
- after `mail_add_field`/`mail_delete_field`/`mail_move_field` — verify current set via `mail_list_fields`;
- do not rename `name="fN"` to arbitrary values (`name="phone"`, `name="email"`, etc.);
- do not confuse `fN` between fields: labels can change, system `name` cannot;
- when changing field structure — always sync HTML via `mail_get_template` → `mail_save_template`;
- after any field operations — ensure `f1` and `f2` are present in the form.

Prohibited:
- creating a "fake" form without `mail_create_form`,
- modifying fields without updating the email template.

### Step 6. SEO block (required template)

In `<head>` use strictly this template:

```html
<title><?if($SEO_TITLE$)?>$SEO_TITLE$<?else?>Site Name<?endif?></title>
<?if($SEO_DESCRIPTION$)?><meta name="description" content="$SEO_DESCRIPTION$" /><?else?><meta name="description" content="Page description" /><?endif?>
<?if($SEO_KEYWORDS$)?><meta name="keywords" content="$SEO_KEYWORDS$" /><?else?><meta name="keywords" content="Keywords" /><?endif?>
```

Note: fallback texts ("Page description", "Keywords") are adapted by the agent to the project's theme, but the conditional structure remains unchanged.

### Step 6.1. Open Graph and Twitter Card (required)

In `<head>` add at minimum:

```html
<meta property="og:type" content="website" />
<meta property="og:title" content="<?if($SEO_TITLE$)?>$SEO_TITLE$<?else?>Site Name<?endif?>" />
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

### Step 7. Schema.org microdata (required)

Add JSON-LD to `<head>` (at least one relevant type):
- `Organization` or `LocalBusiness` for the company,
- `WebSite` if needed,
- `ContactPage` fits the contacts block.

Minimum requirements:
- valid JSON,
- real site data (name, URL, contacts),
- no `TODO` placeholders.

### Step 8. Footer and required system variables

1. In footer use `$YEAR$`, not a static number.
2. After the closing `</body>` always add/preserve:
```html
$POWERED_BY$
```

### Step 9. Responsiveness (mandatory check)

Before final save, verify:
- no horizontal scroll at 320 px;
- key blocks (hero, CTA, form, footer) are readable and clickable at 320/375/768/1024+ px;
- images and buttons don't overflow the container;
- menu/anchors work correctly on mobile;
- burger button is visible and clickable at 320/375 px;
- mobile menu opens and closes without artifacts;
- clicking an anchor menu item closes the mobile menu.

### Step 10. Publishing the landing

#### 10A. Segment page (default) — `page_add` or `page_update`

**New segment:**
```text
templates_tool(action="page_add", page_owntmpl=1, page_ownurl="<slug>", page_name="...", page_message="...", page_tmpl="<HTML>", page_meta_title="...", page_meta_description="...", page_meta_keywords="...")
```

**Edit existing segment:**
```text
templates_tool(action="page_update", page_id=<ID>, ...)
```

After publishing: verify `id` and `url` in response; run `page_get`; record `ucoz.page_id` and `ucoz.published: true` in `segments.json`. Only then include `final_url` in OUTPUT_PACK.

#### 10B. Homepage (only on user request) — `page_update` on `page_id=1`

```text
templates_tool(action="page_update", page_id=1, page_owntmpl=1, page_message="<title>", page_tmpl="<new HTML>", page_meta_title="...", page_meta_description="...", page_meta_keywords="...")
```

Publishing rules (both modes):
- `page_tmpl` — full HTML document; hero must match ad headline (`COPY.md §Message Match`);
- `page_message` — title only;
- pass meta fields explicitly;
- verify via `page_get` after saving.

`validate_template` — **only if user explicitly requests it**.

---

## 4) Pre-delivery checklist

Agent must verify and explicitly confirm:
- [ ] Page list read via `page_list` and needed page via `page_get`.
- [ ] Page published via `page_update` / `page_add` with `page_owntmpl=1`; `page_tmpl` contains landing HTML, `page_message` — title only.
- [ ] `page_meta_title`, `page_meta_description`, `page_meta_keywords` passed explicitly.
- [ ] Menu created/updated via `menu_*`, in template output via `$NMENU_N$`.
- [ ] All menu items have `URL` field filled (anchor links for landing).
- [ ] Burger menu implemented: button (SVG) in HTML, toggle in `script.js`, styles in `style.css`.
- [ ] At 320/375 px desktop navigation is hidden, burger works, menu closes on item click.
- [ ] `style.css` created and uploaded to `/css/style.css`.
- [ ] Template links `/css/style.css?v=<ASSET_VERSION>`.
- [ ] `script.js` created and uploaded to `/js/script.js`.
- [ ] Template links `/js/script.js?v=<ASSET_VERSION>` (before `</body>`).
- [ ] Mail form created via `mail_create_form` or existing used — output via `$MFORM_N$`.
- [ ] For existing form all fields verified via `mail_list_fields`; extra fields (including captcha type 7 without request) deleted via `mail_delete_field`.
- [ ] For new form recipient email taken from existing mail forms (if any).
- [ ] Form field names are SEO-semantic and human-readable.
- [ ] Form fields maintain correct system `name="fN"` per `mail_list_fields` data.
- [ ] Field `f1` used as the only email field (no additional email fields created).
- [ ] System fields `f1` (email) and `f2` (name) preserved and not deleted.
- [ ] For form, style overrides added for standard uCoz modal window — full 9-slice + content set: `.xw-shadow`, `.xw-tl`..`.xw-bc` (reset `background-image`), `.xw-sps` (hidden), `.xw-hdr`, `.xw-hdr-text`, `.xw-body`, `.myWinCont`, `.myWinCont b`, `.myWinFooter`, `.xt-close`/`.xt-close2` (reset `background-image`, `::before` with X added), `.xw-disabled`.
- [ ] After form field changes email template updated (`mail_get_template` + `mail_save_template`).
- [ ] Footer uses `$YEAR$`.
- [ ] `$POWERED_BY$` present after `</body>`.
- [ ] SEO block inserted in required format with `SEO_*` conditions.
- [ ] Open Graph and Twitter Card tags added with real values.
- [ ] Relevant Schema.org microdata added.
- [ ] Responsiveness verified at 320/375/768/1024+ px.
- [ ] No emoji icons in UI; icons implemented via SVG.
- [ ] Favicon generated from SVG logo (colors and shape match); `favicon.ico` uploaded to site root or saved locally with user notification.
- [ ] `<link rel="icon" type="image/x-icon" href="/favicon.ico">` added to `<head>`.
- [ ] After `page_update`, `id` and `url` in response verified; `page_get` run if needed.
- [ ] Template passed `validate_template` (only if user explicitly requested).

---

## 5) Minimum MCP call order

### Segment page (default)

```text
1) templates_tool(action="page_list", page_page=1, page_per_page=100)
2) templates_tool(action="menu_list")
3) ftp: /css/style.css, /js/script.js, /favicon.ico (shared for all segments)
4) mail_* (only if form requested)
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
9.1) Shell: PowerShell → generate favicon.ico from logo
9.2) ftp_tool(action="upload", local_path="<path>/favicon.ico", remote_path="/favicon.ico")
10) templates_tool(action="mail_create_form", ...)
11) templates_tool(action="mail_add_field", ...)
12) templates_tool(action="mail_get_template", form_id=...)
13) templates_tool(action="mail_save_template", form_id=..., template_html="...")
14) templates_tool(action="page_update", page_id=1, ...)
15) templates_tool(action="page_get", page_id=1)
```

For homepage: if `owntmpl=0` — enable via step 14 `page_update`, do not ask user to switch in the panel.

---

## 6) Prohibitions

- Do not skip reading pages via `page_list` and `page_get` before editing.
- Do not ask the user to manually enable the custom template for the homepage in the uCoz panel.
- Do not publish landing without `page_owntmpl=1`.
- Do not put template HTML in `page_message` and do not duplicate markup in `page_message` and `page_tmpl`.
- Do not pass `page_tmpl` without `$POWERED_BY$`.
- Do not publish page without explicitly passing `page_meta_title`, `page_meta_description`, `page_meta_keywords`.
- Do not build menu manually instead of `menu_*`.
- Do not deliver landing without burger menu on mobile (≤768 px).
- Do not put burger logic inline in the template — only in `/js/script.js`.
- Do not create menu items with empty `URL` field — use anchors for landings.
- Do not leave styles in `<style>` inside the landing as the main source (main source — `/css/style.css`).
- Do not insert large inline JS in the template as the main source (main source — `/js/script.js`).
- Do not link CSS/JS without version in URL (required `?v=<ASSET_VERSION>`).
- Do not deliver landing without responsiveness check (minimum 320/375/768/1024+).
- Do not use static year in footer.
- Do not remove/omit `$POWERED_BY$`.
- Do not add form without `mail_*` actions.
- Do not create form "by default" if not explicitly requested in prompt.
- Do not leave captcha (type 7) in existing form without request — delete via `mail_delete_field`.
- Do not use existing form without checking all fields via `mail_list_fields` first.
- Do not ignore email from existing mail forms when creating a new one.
- Do not create form fields with non-semantic names (`field1`, `field2`, `test`) in the final version.
- Do not break system field names (structure `name="fN"` is required, otherwise form won't work).
- Do not delete system fields `f1` and `f2` (only labels may be changed).
- Do not create new email/name fields instead of system `f1`/`f2`.
- Do not mass-replace text with HTML entities (`&#...;`) without technical necessity.
- Do not leave the standard uCoz modal dialog without style overrides matching the landing. Required selectors for **full** override (9-slice border + content): `.xw-shadow`, `.xw-tl`, `.xw-tr`, `.xw-tc`, `.xw-ml`, `.xw-mr`, `.xw-mc`, `.xw-bl`, `.xw-br`, `.xw-bc`, `.xw-sps`, `.xw-blank`, `.xw-hdr`, `.xw-hdr-text`, `.xw-body`, `.myWinCont`, `.myWinCont b`, `.myWinFooter`, `.xt-close`, `.xt-close2`, `.xw-disabled` — all must match the landing's visual style. Resetting `background-image` on 9-slice elements is required, otherwise the default window border remains.
- Do not leave Open Graph with placeholders or without `og:image`.
- Do not use emoji as UI icons — only SVG.
- Do not publish landing without favicon: `favicon.ico` must be generated and uploaded (or saved locally with explicit user notification).
- Do not create a favicon that doesn't visually match the logo in the landing header (different colors, shape, or motif).

---

## 7) Report format after completion

At the end the agent provides a brief technical report:
1. What actions were called (in order), including `page_list`, `page_get`, `page_update`/`page_add`.
2. Result of `page_get` and publishing (URL, SEO fields).
3. ID of created/updated menu.
4. ID of created form.
5. Where CSS was uploaded (`/css/style.css`).
6. Where JS was uploaded (`/js/script.js`).
7. Which SEO, Open Graph/Twitter, and schema blocks were added.
8. Responsiveness confirmation (320/375/768/1024+).
9. Favicon status: uploaded to FTP (`/favicon.ico`) or saved locally (with path and manual upload instructions).
10. Result of `validate_template` (if requested).
