# uCoz Landing Skill Playbook

Version: **1.3**

Detailed instructions for an AI agent to build a uCoz landing page via MCP.

---

## 1) Goal and scope

This playbook is required for tasks such as:
- "build a landing page",
- "update the homepage as a landing page",
- "create a promo page on the homepage",
- "add a lead form/menu/SEO to the homepage".

In this scenario, a uCoz landing page = custom template for the homepage (`page_id=1`, `page_owntmpl=1`).
Landing HTML is published in `page_tmpl`, not in `page_message`.

---

## 2) Core principles (mandatory)

1. Work only through MCP tools (`templates_tool`, `ftp_tool`, `modules_tool`), without manual assumptions. For the homepage: read with `page_list`, `page_get`; first landing publish — `page_update` with full `page_tmpl`; **any HTML edits to an existing landing** — `patch_template` (`module_id=2`, `template_id=1`), **not** `update_template` and **not** full `page_tmpl` overwrite via `page_update`. SEO fields (`page_meta_*`) may be updated via `page_update` without `page_tmpl` for targeted meta-only edits.
2. Use `validate_template` only when the user explicitly asks — do not run it automatically.
3. Do not remove or break uCoz variables and system constructs.
4. Do not remove `$POWERED_BY$`; it must appear after `</body>`.
5. Do not use a static year in the footer; use `$YEAR$`.
6. For menus, use only menu actions + `$NMENU_N$` variables (this playbook uses vertical menus).
7. Create a contact form only if the user explicitly requested a form in the prompt; if a form is needed — use only `mail_*` actions + output `$MFORM_N$`. Before `mail_*`, verify the mail forms module is active (`modules_tool`); if inactive — install via `module_install` yourself; do not ask the user to enable the module in the panel.
8. Put landing styles in a separate `style.css` file and upload to `/css` on the site.
9. Put landing scripts in a separate `script.js` file and upload to `/js` on the site.
10. Version CSS/JS URLs (`?v=<ASSET_VERSION>`) and bump the version on every file change.
11. The landing must be responsive: correct behavior at least at 320/375/768/1024+ px.
12. Add Open Graph and Twitter Card meta tags in `<head>`.
13. Form field labels must be SEO-semantic and match page intent.
14. Do not use emoji as icons in the UI; use SVG icons (inline SVG or SVG files).
15. Treat `f1` and `f2` as system-required fields: `f1` — email, `f2` — name. Do not delete or replace them; only change labels.
16. Keep text in readable UTF-8 in HTML; do not encode Cyrillic as HTML entities like `&#1057;...` unless technically required.
17. After adding a mail form, always override the default uCoz success modal (uCoz window) to match the landing style. The window uses a 9-slice border model — zero `background-image` on all 9 border parts (`.xw-tl`, `.xw-tr`, `.xw-tc`, `.xw-ml`, `.xw-mr`, `.xw-mc`, `.xw-bl`, `.xw-br`, `.xw-bc`), and override `.xw-shadow`, `.xw-sps`, `.xw-hdr`, `.xw-hdr-text`, `.xw-body`, `.myWinCont`, `.myWinCont b`, `.myWinFooter`, `.xt-close`, `.xt-close2`, `.xw-blank`, `.xw-disabled`. See step 4.1, item 7 for the full table.
18. On mobile (≤768 px), hide the nav menu and replace it with a burger button. Toggle logic goes in `script.js`, styles in `style.css`, button markup in the template HTML.
19. Logo and favicon must be visually identical. Generate the favicon from the SVG logo used in the landing header. Upload to FTP as `/favicon.ico`, link in `<head>` with `<link rel="icon">`. On FTP errors, get/create credentials via `ftp_tool` (`ftp_password_get` → if needed `ftp_password_set` / `ftp_password_change`); only if MCP cannot provide a password (API key lacks permission) — save `favicon.ico` locally and notify the user.
20. For `patch_template`: copy `code_search` **byte-for-byte** from `read_template` (module_id=2, template_id=1) — spaces, tabs, and line breaks matter; the fragment must appear exactly once. `code_paste=""` deletes the fragment. Auto-backup is created before `patch_template` and `update_template`.

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

Decision rules:
- if the prompt does not explicitly request a form (e.g. "form", "lead", "contact us", "leave contacts"), do not create a form block and do not call `mail_*` actions;
- if a form is requested, complete step 5 fully.

### Step 1. Check the homepage via `templates_tool(action="page_list")`

1. Get the page list and identify the homepage (`page_id=1`):
```text
templates_tool(action="page_list", page_page=1, page_per_page=100)
```

2. To read the current homepage state:
```text
templates_tool(action="page_get", page_id=1)
```

3. If the homepage already has `owntmpl=1` — read the current custom template from the `tmpl` field in `page_get` and continue editing.

4. If `owntmpl=0` or there is no custom template yet — do not ask the user to enable "custom template" in the panel. Enable and fill it on the final `templates_tool(action="page_update")` (step 10): `page_owntmpl=1`, `page_tmpl` with landing HTML, `page_message` with title only, plus `page_meta_title`, `page_meta_description`, `page_meta_keywords`.

5. For custom template: landing HTML only in `page_tmpl`; `page_message` — page title only; `page_tmpl` must include `$POWERED_BY$`.

### Step 2. Check modules and context

1. Get the template catalog (recommended):
```text
templates_tool(action="list_modules")
```

2. Check site modules:
```text
modules_tool(action="modules_list")
```

3. If the task needs a mail form — find the mail forms module in the catalog (code usually `mail`; **always verify with `modules_list`**):
   - if `active: false` → `modules_tool(action="module_install", module_code="mail")` (use actual code from catalog);
   - if `api_enabled: false` → `modules_tool(action="module_enable_uapi", module_code="mail")`.

4. **Do not ask** the user to manually activate the mail forms module in the panel — use `modules_tool`.

5. `modules_tool`, `module_install`, `ftp_password_*` require an API key with "API access to control panel settings". On auth errors — tell the user to reissue the key with this permission.

### Step 2.1. Prepare FTP access (before steps 4 / 4.1 / 4.2)

If `ftp_tool` returns an auth error or the password is not set in MCP config:

1. Get connection parameters:
```text
ftp_tool(action="ftp_password_get")
```

2. If password is not set yet (`ftp_password_set=0` in response) — set it:
```text
ftp_tool(action="ftp_password_set", ftp_password="<6–15 chars: Latin letters, digits, _ and ->")
```

3. If password exists but is unknown/not working — change it:
```text
ftp_tool(action="ftp_password_change", ftp_password="<new password>")
```
or reset: `ftp_tool(action="ftp_password_reset")` → then `ftp_password_set`.

4. After successful `ftp_password_set` / `ftp_password_change` the response contains the password in plain text — **save immediately** and use for subsequent FTP operations.

5. Only if `ftp_password_*` is unavailable due to API key permissions — save files locally and notify the user about manual upload.

### Step 3. Menu (strictly via API)

1. List menus:
```text
templates_tool(action="menu_list")
```

2. If the needed menu does not exist — create:
```text
templates_tool(action="menu_create", title="Landing main menu", layout=1)
```
`layout=1` → vertical menu (output via `$NMENU_N$`).

3. Update items:
```text
templates_tool(action="menu_update_items", menu_id=<ID>, items=[...])
```

Menu item requirements:
- each item's `URL` field must be filled;
- for landing pages use anchor links (`#hero`, `#about`, `#faq`, `#contacts`, etc.);
- do not leave empty `URL` or styles/behavior may break.

4. In the template, output the menu only as:
```text
$NMENU_<ID>$
```
Insert/replace `$NMENU_<ID>$` in an already published landing via `patch_template` (module_id=2, template_id=1).

5. Mobile burger menu (mandatory):
- at width ≤768 px hide navigation (`display:none`), show burger button;
- burger button in template HTML (inline SVG, not emoji);
- toggle logic (open/close) strictly in `/js/script.js`;
- burger and mobile menu styles in `/css/style.css`;
- on anchor item click — mobile menu closes automatically.

Forbidden:
- hand-coding `<ul><li>` menu instead of API menu,
- mixing static menu and `$NMENU_N$` for the same nav block,
- creating menu items with empty `URL`,
- leaving desktop nav without a burger on mobile (≤768 px).

### Step 4. Styles only in separate `style.css` + FTP upload

Before FTP operations, complete step 2.1 if needed.

1. Generate/update `style.css` locally.
2. Check `/css` exists:
```text
ftp_tool(action="list", path="/")
```
3. If `/css` is missing — create:
```text
ftp_tool(action="mkdir", path="/css")
```
4. Upload file:
```text
ftp_tool(action="write", path="/css/style.css", content="<full CSS>")
```
or
```text
ftp_tool(action="upload", local_path="<local path to style.css>", remote_path="/css/style.css")
```
5. In the template, link with version (via `patch_template` if landing is published; on first publish — include in `page_tmpl`):
```html
<link rel="stylesheet" href="/css/style.css?v=<ASSET_VERSION>">
```

### Step 4.1. JS only in separate `script.js` + FTP upload

1. Generate/update `script.js` locally.
2. Check `/js` exists:
```text
ftp_tool(action="list", path="/")
```
3. If `/js` is missing — create:
```text
ftp_tool(action="mkdir", path="/js")
```
4. Upload file:
```text
ftp_tool(action="write", path="/js/script.js", content="<full JS>")
```
or
```text
ftp_tool(action="upload", local_path="<local path to script.js>", remote_path="/js/script.js")
```
5. In the template, link before `</body>` with version (via `patch_template` if landing is published):
```html
<script src="/js/script.js?v=<ASSET_VERSION>"></script>
```

6. On CSS/JS changes, increment `<ASSET_VERSION>` (e.g. `202604091530`).
7. If the page uses `$MFORM_<ID>$`, add override styles in `style.css` for the default uCoz success modal.

**Full uCoz modal structure (9-slice border):**

The window uses a table-based 9-slice model with background images. Zero these elements (`background: none !important; background-image: none !important; border: none !important;`) or the default frame remains:

| Group | Selectors | Purpose |
|---|---|---|
| Border (9 parts) | `.xw-tl`, `.xw-tr`, `.xw-tc`, `.xw-ml`, `.xw-mr`, `.xw-mc`, `.xw-bl`, `.xw-br`, `.xw-bc` | Window frame corners and sides (background-image) |
| Container | `.xw-shadow` | Outer container; set border + box-shadow to match landing |
| Header | `.xw-hdr`, `.xw-hdr-text`, `.xw-draggable` | Title bar; zero background-image |
| Body | `.xw-body`, `.myWinCont` | Message content |
| Footer | `.myWinFooter` | Bottom row inside window |
| Close button | `.xt-close`, `.xt-close2` | Both classes; default uses background-image; zero background-image and set custom style (`::before { content: 'X' }` or inline SVG) |
| Service | `.xw-sps`, `.xw-blank`, `.xw-disabled` | Spacer, placeholder, overlay when blocked |

**Required styling actions:**
1. Zero `background`, `background-image`, `border` on all 9-slice elements (`.xw-tl` ... `.xw-bc`).
2. Zero `background-image` on `.xw-hdr`, `.xt-close`, `.xt-close2`.
3. Hide `.xw-sps` (`display: none`).
4. Set `.xw-shadow` border + box-shadow instead of 9-slice frame.
5. For `.xt-close` / `.xt-close2` use `::before { content: 'X' }` with pixel font, since default close icon is background-image.
6. Style `.myWinCont b` (title inside success message) and body text.
7. Match window to landing visual style (colors, typography, spacing, close button).
8. Do not rely on inline styles from system markup.

### Step 4.2. Favicon — generated from logo (mandatory)

Favicon must be visually identical to the logo in the landing header. Generated via PowerShell with `System.Drawing`.

**Algorithm:**

1. Take logo parameters from the header HTML (colors, shape, graphic motif).
2. Generate `favicon.ico` (32×32, ICO with embedded PNG) with PowerShell:

```powershell
Add-Type -AssemblyName System.Drawing

$size = 32
$bmp = New-Object System.Drawing.Bitmap($size, $size)
$g = [System.Drawing.Graphics]::FromImage($bmp)
$g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias

# Background: rounded rect (colors from landing logo)
$path = New-Object System.Drawing.Drawing2D.GraphicsPath
$r = 7
$path.AddArc(0, 0, $r*2, $r*2, 180, 90)
$path.AddArc($size-$r*2, 0, $r*2, $r*2, 270, 90)
$path.AddArc($size-$r*2, $size-$r*2, $r*2, $r*2, 0, 90)
$path.AddArc(0, $size-$r*2, $r*2, $r*2, 90, 90)
$path.CloseFigure()

$brush = New-Object System.Drawing.Drawing2D.LinearGradientBrush(
    [System.Drawing.Point]::new(0,0), [System.Drawing.Point]::new($size,$size),
    [System.Drawing.Color]::FromArgb(255, <R1>, <G1>, <B1>),   # logo primary
    [System.Drawing.Color]::FromArgb(255, <R2>, <G2>, <B2>))   # logo secondary
$g.FillPath($brush, $path)

# Graphic motif (matches SVG logo): lines, arcs, circle, etc.
# ... adapt to specific logo ...

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

4. Add link in template `<head>` (right after `<meta charset>`):
```html
<link rel="icon" type="image/x-icon" href="/favicon.ico">
```
If landing is already published — insert via `patch_template` (module_id=2, template_id=1); on first publish — include in `page_tmpl`.

**Rules:**
- Favicon colors, shape, and motif must match the SVG logo in the header.
- Run step 2.1 before upload if FTP is unavailable.
- Do not use emoji or browser default favicon — only the generated file.

---

### Step 5. Mail form via `mail_*` actions

This step runs only when the user explicitly requested a form in the prompt.

0. Ensure mail forms module is active (step 2). If `active: false` — `modules_tool(action="module_install", module_code="<code from modules_list>")`. Do not ask the user to activate manually.

1. Create form:
```text
templates_tool(action="mail_create_form", mail_name="Landing form", mail_emails="...", mail_rez="...")
```

Before `mail_create_form`:
- list existing forms (`mail_list_forms`);
- take recipient emails from existing forms and use them as priority for the new form;
- only if no existing emails, ask the user for a new email.

**If reusing an existing form:**
- call `mail_list_fields` and check all fields;
- if the form has type 7 (captcha / security code) and the user did **not** request captcha — delete via `mail_delete_field`;
- if extra fields are not needed for this landing (and are not system `f1`/`f2`) — confirm with user or delete;
- after deletion, sync mail template (`mail_get_template` → `mail_save_template`).

2. Add fields:
```text
templates_tool(action="mail_add_field", mail_form_id=<ID>, mail_name="...", mail_field_type=1, mail_required=true, ...)
```

3. Captcha: add type 7 only on **explicit** user request. If captcha exists in an existing form and user did not ask — delete (`mail_delete_field`).

4. After field changes, sync mail HTML:
```text
templates_tool(action="mail_get_template", mail_form_id=<ID>)
templates_tool(action="mail_save_template", mail_form_id=<ID>, mail_tmpl="<mail HTML>")
```

5. In the page template, output only:
```text
$MFORM_<ID>$
```
Insert `$MFORM_<ID>$` in an existing landing via `patch_template` (module_id=2, template_id=1), not full rewrite.

6. Form field labels must be SEO-semantic:
- use clear names for user intent, e.g. "Your name", "Phone", "Email", "City", "Comment";
- for commercial landings, include service/product entity where appropriate, e.g. "Service of interest", "Project type";
- do not use technical or empty names like "field1", "field 2", "text";
- keep natural language, no keyword stuffing.

7. `f1` and `f2` are system-required:
- `f1` — email, `f2` — name;
- do not delete `f1`/`f2` via `mail_delete_field`;
- do not create duplicate email/name fields instead of `f1`/`f2`;
- only label changes allowed, not deletion or replacement of system fields.

8. When editing form/mail template HTML, keep system field names strictly as `name="fN"` for created fields:
- after `mail_add_field`/`mail_delete_field`/`mail_move_field`, verify via `mail_list_fields`;
- do not rename `name="fN"` to arbitrary values (`name="phone"`, `name="email"`, etc.);
- do not swap `fN` between fields: labels may change, system `name` may not;
- after structure changes, sync HTML via `mail_get_template` → `mail_save_template`;
- after any field operations, verify `f1` and `f2` are present.

Forbidden:
- hand-coding a "fake" form without `mail_create_form`,
- changing fields without updating the mail template.

### Step 6. SEO block (mandatory template)

In `<head>` use:

```html
<title><?if($SEO_TITLE$)?>$SEO_TITLE$<?else?>Site name<?endif?></title>
<?if($SEO_DESCRIPTION$)?><meta name="description" content="$SEO_DESCRIPTION$" /><?else?><meta name="description" content="Landing page description" /><?endif?>
<?if($SEO_KEYWORDS$)?><meta name="keywords" content="$SEO_KEYWORDS$" /><?else?><meta name="keywords" content="Landing page keywords" /><?endif?>
```

Note: fallback texts (`Landing page description`, etc.) should match project topic; keep the conditional structure unchanged.

### Step 6.1. Open Graph and Twitter Card (mandatory)

In `<head>` add at minimum:

```html
<meta property="og:type" content="website" />
<meta property="og:title" content="<?if($SEO_TITLE$)?>$SEO_TITLE$<?else?>Site name<?endif?>" />
<meta property="og:description" content="<?if($SEO_DESCRIPTION$)?>$SEO_DESCRIPTION$<?else?>Landing page description<?endif?>" />
<meta property="og:url" content="$HOME_PAGE_LINK$" />
<meta property="og:site_name" content="$SITE_NAME$" />
<meta property="og:image" content="https://site.com/path/to/og-image.jpg" />
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="<?if($SEO_TITLE$)?>$SEO_TITLE$<?else?>$SITE_NAME$<?endif?>" />
<meta name="twitter:description" content="<?if($SEO_DESCRIPTION$)?>$SEO_DESCRIPTION$<?else?>Landing page description<?endif?>" />
<meta name="twitter:image" content="https://site.com/path/to/og-image.jpg" />
```

Rules:
- `og:image` and `twitter:image` must point to a real public image;
- use absolute URLs;
- do not leave `site.com` placeholders in the final template.

### Step 7. Schema.org microdata (mandatory)

Add JSON-LD in `<head>` (at least one relevant type):
- `Organization` or `LocalBusiness` for company,
- `WebSite` if needed,
- `ContactPage` for contact block if appropriate.

Minimum requirements:
- valid JSON,
- real site data (name, URL, contacts),
- no placeholders like `TODO`.

### Step 8. Footer and required system variables

1. Use `$YEAR$` in the footer, not a static number.
2. After closing `</body>`, add/preserve:
```html
$POWERED_BY$
```

### Step 9. Responsiveness (mandatory check)

Before final save, verify:
- no horizontal scroll at 320 px;
- key blocks (hero, CTA, form, footer) readable and clickable at 320/375/768/1024+ px;
- images and buttons stay within container;
- menu/anchors work on mobile;
- burger button visible and clickable at 320/375 px;
- mobile menu opens/closes without artifacts;
- anchor click closes mobile menu.

### Step 10. Publish and edit homepage

Custom homepage template is available two ways:
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
- after `page_update`, check response (`id`, `url`) and `page_get` if needed.

Do **not** use `update_template` for landing — only `page_update` on first publish.

#### 10.2. Edits to existing landing (after generation)

Any HTML changes (blocks, CSS/JS links, `$MFORM_N$`, `$NMENU_N$`, favicon in `<head>`, etc.) — **only via `patch_template`**:

1. Read current code:
```text
templates_tool(action="read_template", module_id=2, template_id=1)
```

2. Copy exact fragment from response for replacement (`code_search` — byte match, exactly one occurrence).

3. Apply targeted edit:
```text
templates_tool(action="patch_template", module_id=2, template_id=1, code_search="<fragment from read_template>", code_paste="<new fragment>")
```

4. For multiple unrelated edits — separate `patch_template` per unique fragment.

5. SEO meta only, no HTML change:
```text
templates_tool(action="page_update", page_id=1, page_meta_title="...", page_meta_description="...", page_meta_keywords="...")
```

**Forbidden** for existing landing:
- `update_template` (full module template rewrite);
- `page_update` with full `page_tmpl` for targeted edits (risk of overwrite and formatting loss).

Run validation (`validate_template`) **only if the user explicitly asked** to validate the template.

### Step 11. Quarantine and indexing (final step)

**Quarantine** in uCoz — mode where the site is **not indexed** by search engines. Removing quarantine opens the site for indexing.

Run **after** landing publish and all edits (step 10), **before** the final user report.

1. Ask the user (if not stated in prompt):
   > Should the site be indexed by search engines? I can remove quarantine so indexing can start.

2. If the user does **not** want indexing now — leave quarantine; report that the site remains in quarantine and is not indexed.

3. If the user **wants** indexing (or asked in the prompt) — check status:
```text
modules_tool(action="quarantine_status")
```

4. If response has `locked=true` (quarantine on) — remove:
```text
modules_tool(action="quarantine_unlock")
```

5. If `locked=false` — quarantine already off; inform the user.

**Rules:**
- **Do not remove** quarantine without user consent for indexing.
- Do not ask the user to remove quarantine manually in the panel — use `modules_tool`.
- `quarantine_status` / `quarantine_unlock` require API key with "API access to control panel settings".

---

## 4) Pre-delivery checklist

Agent must verify and explicitly confirm:
- [ ] Homepage (`page_id=1`) read via `page_list` and `page_get`.
- [ ] If form needed: mail forms module checked via `modules_list`; if `active: false`, installed via `module_install` (no manual panel steps).
- [ ] FTP access verified; if no password — obtained/created via `ftp_password_get` / `ftp_password_set` (or `ftp_password_change`).
- [ ] **First publish:** `page_update` with `page_owntmpl=1`, full `page_tmpl` and `page_meta_*`; `page_message` — title only.
- [ ] **Post-generation edits:** HTML changed via `patch_template` (module_id=2, template_id=1); `update_template` and full `page_tmpl` rewrite not used.
- [ ] Menu created/updated via `menu_*`, template has `$NMENU_N$`.
- [ ] All menu items have `URL` filled (anchor links for landing).
- [ ] Burger menu: button (SVG) in HTML, toggle in `script.js`, styles in `style.css`.
- [ ] At 320/375 px desktop nav hidden, burger works, menu closes on item click.
- [ ] `style.css` created and uploaded to `/css/style.css`.
- [ ] Template links `/css/style.css?v=<ASSET_VERSION>`.
- [ ] `script.js` created and uploaded to `/js/script.js`.
- [ ] Template links `/js/script.js?v=<ASSET_VERSION>` (before `</body>`).
- [ ] Mail form created via `mail_create_form` or existing form reused — output as `$MFORM_N$`.
- [ ] For existing form: all fields checked via `mail_list_fields`; extra fields (incl. type 7 captcha if not requested) deleted via `mail_delete_field`.
- [ ] For new form: recipient emails taken from existing mail forms if available.
- [ ] Form field labels are SEO-semantic and human-readable.
- [ ] Form fields keep correct system `name="fN"` per `mail_list_fields`.
- [ ] `f1` is the only email field (no duplicate email fields).
- [ ] System fields `f1` (email) and `f2` (name) preserved, not deleted.
- [ ] Override styles for uCoz success modal — **full** 9-slice + content set: `.xw-shadow`, `.xw-tl`..`.xw-bc` (`background-image` zeroed), `.xw-sps` (hidden), `.xw-hdr`, `.xw-hdr-text`, `.xw-body`, `.myWinCont`, `.myWinCont b`, `.myWinFooter`, `.xt-close`/`.xt-close2` (`background-image` zeroed, `::before` close icon), `.xw-disabled`.
- [ ] After field changes, mail template updated (`mail_get_template` + `mail_save_template`).
- [ ] Footer uses `$YEAR$`.
- [ ] `$POWERED_BY$` after `</body>`.
- [ ] SEO block in required format with `SEO_*` conditionals.
- [ ] Open Graph and Twitter Card tags added with real values.
- [ ] Relevant Schema.org microdata added.
- [ ] Responsiveness checked at 320/375/768/1024+ px.
- [ ] No emoji icons in UI; icons via SVG.
- [ ] Favicon generated from SVG logo (colors and shape match); `favicon.ico` uploaded to site root (or saved locally only if `ftp_password_*` unavailable).
- [ ] `<head>` has `<link rel="icon" type="image/x-icon" href="/favicon.ico">` (via `patch_template` or in `page_tmpl` on first publish).
- [ ] After publish/edits, `id` and `url` checked in response; `page_get` if needed.
- [ ] **Final step:** indexing preference confirmed; if user agreed, `quarantine_status` checked and `quarantine_unlock` run when `locked=true`.
- [ ] Template passed `validate_template` (only if user explicitly requested validation).

---

## 5) Minimal reference action order

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

// Publish — depends on homepage state:
18a) FIRST publish (owntmpl=0):
     templates_tool(action="page_update", page_id=1, page_owntmpl=1, page_message="...", page_tmpl="...", page_meta_title="...", page_meta_description="...", page_meta_keywords="...")
18b) EDITS to existing landing:
     templates_tool(action="read_template", module_id=2, template_id=1)
     templates_tool(action="patch_template", module_id=2, template_id=1, code_search="...", code_paste="...")   // repeat per edit

19) templates_tool(action="page_get", page_id=1)   // verify result
20) Ask user: should the site be indexed by search engines?
    → if yes: modules_tool(action="quarantine_status")
    → if locked=true: modules_tool(action="quarantine_unlock")
// validate_template — only on explicit user request
```

If homepage has `owntmpl=0` at step 1, enable custom template via `page_update` (step 18a), without manual panel steps.

---

## 6) Prohibitions

- Do not skip reading homepage via `page_list` and `page_get` before editing.
- Do not ask the user to manually enable custom homepage template in uCoz panel.
- Do not ask the user to manually activate mail forms module — use `modules_tool` (`module_install`).
- Do not publish landing without `page_update` with `page_owntmpl=1` on **first** publish.
- Do not edit HTML of **existing** landing via `update_template` or full `page_tmpl` in `page_update` — only `patch_template` (module_id=2, template_id=1).
- Do not put template HTML in `page_message` or duplicate markup in `page_message` and `page_tmpl`.
- Do not pass `page_tmpl` without `$POWERED_BY$`.
- Do not publish homepage without explicit `page_meta_title`, `page_meta_description`, `page_meta_keywords` in `page_update`.
- Do not hand-code menu instead of `menu_*`.
- Do not deliver landing without burger menu on mobile (≤768 px).
- Do not put burger toggle logic inline in template — only in `/js/script.js`.
- Do not create menu items with empty `URL` (use anchors for landing).
- Do not keep styles in `<style>` in landing as the primary source (primary source is `/css/style.css`).
- Do not use large inline JS in template as primary scripts (primary source is `/js/script.js`).
- Do not link CSS/JS without version in URL (required `?v=<ASSET_VERSION>`).
- Do not deliver landing without mobile responsiveness check (minimum 320/375/768/1024+).
- Do not use static year in footer.
- Do not remove/omit `$POWERED_BY$`.
- Do not add form without `mail_*` actions.
- Do not create form by default if prompt has no explicit form request.
- Do not leave captcha (type 7) in existing form if user did not request it — delete via `mail_delete_field`.
- Do not reuse existing form without checking all fields via `mail_list_fields` first.
- Do not ignore emails from existing mail forms when creating a new form.
- Do not use non-semantic field names (`field1`, `field2`, `test`) in final version.
- Do not break system field names (structure `name="fN"` required or form breaks).
- Do not delete system fields `f1` and `f2` (only label changes allowed).
- Do not create new email/name fields instead of system `f1`/`f2`.
- Do not mass-replace text with HTML entities (`&#...;`) without technical reason.
- Do not leave default uCoz success modal styling without landing overrides. Required selectors for **full** override (9-slice + content): `.xw-shadow`, `.xw-tl`, `.xw-tr`, `.xw-tc`, `.xw-ml`, `.xw-mr`, `.xw-mc`, `.xw-bl`, `.xw-br`, `.xw-bc`, `.xw-sps`, `.xw-blank`, `.xw-hdr`, `.xw-hdr-text`, `.xw-body`, `.myWinCont`, `.myWinCont b`, `.myWinFooter`, `.xt-close`, `.xt-close2`, `.xw-disabled` — all must match landing style. Zeroing `background-image` on 9-slice elements is mandatory or default frame remains.
- Do not leave Open Graph with placeholders or without `og:image`.
- Do not use emoji as UI icons; use SVG.
- Do not publish landing without favicon: `favicon.ico` must be generated and uploaded (or saved locally only if `ftp_password_*` unavailable due to API key).
- Do not make favicon that does not visually match header logo (different colors, shape, or motif).
- Do not remove quarantine (`quarantine_unlock`) without user consent for search indexing.

---

## 7) User report format after completion

At the end, provide a brief technical report:
1. Actions called (in order), including `page_list`, `page_get`, `patch_template` / `page_update`, `modules_tool`, `ftp_password_*`.
2. `page_get` result for homepage (`page_id=1`, URL, SEO fields).
3. Mail forms module status (if form needed): whether installed via `module_install`.
4. ID of created/updated menu.
5. ID of created form.
6. Where CSS was uploaded (`/css/style.css`).
7. Where JS was uploaded (`/js/script.js`).
8. Which SEO, Open Graph/Twitter, and schema blocks were added.
9. Responsiveness confirmation (320/375/768/1024+).
10. FTP status: password obtained via `ftp_password_*` or files saved locally.
11. Favicon status: uploaded to FTP (`/favicon.ico`) or saved locally.
12. Quarantine and indexing status: whether user was asked; quarantine removed via `quarantine_unlock` or site left non-indexed.
13. Template validation result (if requested).
