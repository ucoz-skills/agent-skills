# Tools Reference

## FTP Tool

`ftp_tool` covers two unrelated capabilities: raw FTP file operations, and FTP password management via uAPI. Use it for files that live outside the template system (images, PDFs, custom CSS/JS assets, fonts). For template HTML/CSS, use `templates_tool` instead.

### File operations

| Action | Notes |
|---|---|
| `list` | List files/folders at a remote path |
| `read` | Read a remote file |
| `write` | Write text content to a remote file |
| `mkdir` | Create a remote directory |
| `delete` | Delete a remote file/folder. **Confirm with the user first** — irreversible |
| `rename` | Rename or move a remote file/folder |
| `upload` | Upload a local file to an FTP path |

### FTP password management

Requires an API key with **Control Panel settings access** enabled.

| Action | Notes |
|---|---|
| `ftp_password_get` | Returns connection settings without the password |
| `ftp_password_set` | Sets the password for the first time (only works when none is set yet) |
| `ftp_password_change` | Changes the existing password. **Confirm with user first** — invalidates current password immediately |
| `ftp_password_reset` | Resets the password. **Confirm with user first** — invalidates current password immediately |

After a successful `ftp_password_set` or `ftp_password_change`, the response contains the new password in plain text. **Relay it to the user immediately** — it will not be shown again.

### When to use FTP vs. `templates_tool`

| Need | Tool |
|---|---|
| Edit page/global-block HTML | `templates_tool` |
| Edit the CSS template (module 3) | `templates_tool` |
| Upload a logo, favicon, banner image | `ftp_tool` (`upload`), then reference its path from a template |
| Upload a standalone CSS/JS asset file | `ftp_tool` (`upload`) |
| Browse/organize the file structure | `ftp_tool` (`list`, `mkdir`, `rename`, `delete`) |
| Rotate/reset the FTP password | `ftp_tool` (`ftp_password_*`) |

### Canonical external CSS for a large landing/feature redesign

When a single-module or page landing has substantial custom styling (hero, pricing, responsive layout, etc.), serve one external stylesheet from FTP instead of scattering rules across template CSS fields.

1. Create an asset directory if needed (e.g. `/assets`).
2. Write one versioned stylesheet (e.g. `/assets/feature.css`) with `ftp_tool`.
3. Add one `<link rel="stylesheet">` for that exact asset to every full-page template for the module. Keep the system theme stylesheet link intact.
4. Scope rules with the page/module body class. Do not use `@import`, inline `<style>`, or extra feature stylesheet links.
5. Verify the public page emits the asset exactly once and that it returns the expected unique CSS marker.

Use the site-wide CSS template (`module 3/3`) only for small corrections that must load on every module page.

---

## Modules Tool

`modules_tool` manages site modules and admin functions. Most actions require an API key with **Control Panel settings access**. The exception is `active_mods`, which does not.

### Actions

| Action | Notes |
|---|---|
| `modules_list` | Full module catalog with `active` / `api_enabled` flags |
| `module_status` | Status of one module (requires `module_code`) |
| `module_install` | Installs a module |
| `module_uninstall` | Uninstalls a module. **Confirm with user first** — protected modules (Users, SEO, etc.) cannot be removed regardless |
| `active_mods` | Legacy simplified list of active modules (no special permission needed) |
| `module_enable_uapi` | Enables uAPI methods for a module |
| `module_disable_uapi` | Disables uAPI methods for a module |
| `quarantine_status` | Checks whether site quarantine is active (`locked=true` = quarantine on) |
| `quarantine_unlock` | Removes site quarantine. **Confirm with user first** — quarantine is usually applied for a reason; unlocking must be a deliberate user decision |

### `module_code` values

`news`, `board`, `publ`, `blog`, `photo`, `video`, `faq`, `forum`, `shop`, `dir`, `load`, `gb`, `search`, `mchat`, `polls`.

### Typical flow

```
modules_list                               # see what's installed/active
module_status(module_code="shop")          # check one module before acting
module_install(module_code="shop")         # install if needed
module_enable_uapi(module_code="shop")     # enable API access if the task needs it
```

Before uninstalling or making other destructive module changes, run `module_status` first and confirm with the user.

---

## Mail Forms

### Core rule

Never hand-write a `<form>` and a JS submit handler for a contact/feedback form. Use the `mail_*` actions and the `$MFORM_<ID>$` output variable.

### Actions

| Action | Notes |
|---|---|
| `mail_list_forms` | Lists all mail forms |
| `mail_get_form` | Returns form details |
| `mail_create_form` | Creates a new form |
| `mail_update_form` | Updates name, recipients, or success message |
| `mail_delete_form` | Deletes a form. **Confirm with user first** — irreversible |
| `mail_list_fields` | Lists a form's fields |
| `mail_add_field` | Adds a field |
| `mail_update_field` | Updates a field |
| `mail_delete_field` | Deletes a field |
| `mail_move_field` | Reorders a field up or down |
| `mail_get_template` | Reads the HTML email template for a form |
| `mail_save_template` | Saves the HTML email template |

Field types (`mail_field_type`) are numbered 1–8. For types 3–5 (select/radio/checkbox-style), pass `mail_values` as newline-separated options.

### Output in templates

Insert `$MFORM_<ID>$` via `patch_template` (or `update_template` for a new template) at the point in the page or global-block template where the form should render.

### Styling

- **On-site form appearance:** style it **in the same template** where `$MFORM_<ID>$` is output (scoped `<style>`, wrapper classes). Do not put these in `module 3/3` if the styling is specific to this form's placement.
- **Success popup:** after submit, uCoz shows a system `_uWnd` window with `mail_rez` text — restyle via site-wide `_uWnd` CSS, not the form template. Rules: [UWND.md](UWND.md) (9-slice override mandatory).
- **Email appearance:** styled inline within the HTML saved via `mail_save_template`. This is entirely separate from module 3.

### Field sync rule (critical)

**After any field add or delete**, in the same pass, update the HTML email template:

```
mail_get_template(form_id)
→ edit HTML to match the new field set
→ mail_save_template(form_id, updated_html)
```

Skipping this leaves the email missing new fields or referencing deleted ones.

### System fields

`f1` (email) and `f2` (name) are system fields — they can be **renamed** but **never deleted**.

### Captcha (type 7)

- Never add a type-7 captcha field unless the user explicitly requests it.
- The correct placeholder in the HTML email template is `$SECURITY_CODE$` — not `$SEC_CODE$` or any other variant.

### New recipient verification

After `mail_create_form` or `mail_update_form`, if a recipient email address is new to the site (never used in a mail form before), tell the user: it must be **verified** in the uCoz Control Panel under "Mail forms". Delivery to an unverified address may not work until then.

### End-to-end flow for a contact form

1. `mail_create_form` → note the returned `id`.
2. `mail_list_fields` → confirm `f1`/`f2` are present; add extra fields with `mail_add_field`.
3. `mail_get_template` → adjust the email HTML to include all fields → `mail_save_template`.
4. `patch_template` on the target page/global block to insert `$MFORM_<ID>$` at the right spot.
5. If custom CSS is needed, scope it to that same template.
6. If the recipient email is new, remind the user to verify it in the Control Panel.

---

## Menus

### Core rule

Never hand-write `<ul><li>` menu markup in a template. Use `menu_*` MCP actions. In templates and global blocks, output the result only via its variable (`$NMENU_<ID>$` or `$SMENU_<ID>$`).

Every menu item **must** have a non-empty URL. For landing-page or anchor navigation, use `#section-id` rather than leaving the URL blank.

### Actions

| Action | Notes |
|---|---|
| `menu_list` | Lists all site menus |
| `menu_create` | Creates a new menu. `layout`: `0` = horizontal, `1` = vertical |
| `menu_get` | Returns a menu with its items |
| `menu_update` | Updates menu title and/or layout |
| `menu_delete` | Deletes a menu. **Confirm with user first** — irreversible |
| `menu_update_items` | Replaces the menu's item tree (supports nested `children`) |

### Output variables

- `$NMENU_<ID>$` — vertical menu with the given ID.
- `$SMENU_<ID>$` — horizontal menu with the given ID.

### Recommended workflow

1. Create or update the menu structure via `menu_create` / `menu_update` / `menu_update_items`.
2. Place `$NMENU_<ID>$` or `$SMENU_<ID>$` in the template via `patch_template`.

### Nested items (shop catalog submenu)

Fetch category URLs from `/uapi/shop/request?page=categories` — each category has a `url` slug. Use `menu_get` to find the parent item, then `menu_update_items` with a `children` array on that parent. The platform renders the nested `<ul>` automatically; do not hand-write it in `AHEADER`.

---

## Pages

Site pages are managed through `templates_tool` `page_*` actions.

### Actions

| Action | Notes |
|---|---|
| `page_list` | List all pages. Supports `page_page` / `page_per_page` pagination |
| `page_get` | Get one page by `page_id` |
| `page_add` | Create a page |
| `page_update` | Update a page — **PATCH semantics**, only passed fields change |
| `page_delete` | Delete a page. **Home page (ID=1) cannot be deleted** — do not attempt it |

### Key fields

| Parameter | Description |
|---|---|
| `page_id` | Page ID — required for `page_get`, `page_update`, `page_delete` |
| `page_name` | Page title |
| `page_message` | Page content. **When using a personal template, this must contain only the page title text — no HTML** |
| `page_tmpl` | Full HTML of a personal template (`<!DOCTYPE html>` … `$POWERED_BY$` … `</html>`) |
| `page_owntmpl` | `1` = personal template; `0` = site's shared template. Passing `page_tmpl` sets this to `1` automatically. Passing `0` on `page_update` **removes** the personal template |
| `page_ownurl` | SEO-friendly URL slug |
| `page_parent_id` | Parent page ID (`0` = root) |
| `page_pending` | `1` = pending activation; `0` = active |
| `page_meta_title` | Meta title |
| `page_meta_description` | Meta description |
| `page_meta_keywords` | Meta keywords |

### Shared vs. personal template

- **`owntmpl=0`** — uses the site's shared template (`module 2 / template 0`). Content goes in `page_message` (rendered via `$CONTENT$`). You cannot fully redesign such a page's layout by editing only `page_message`.
- **`owntmpl=1`** — uses a **personal template**. The entire HTML document goes in `page_tmpl`. `page_message` holds **only the page title**. The personal template appears in the template catalog as `module 2 / template <page_id>`.

**Default to a personal template** when creating or redesigning a page that needs custom HTML layout.

### Rules for a personal template

1. Set `page_owntmpl=1` (or just pass `page_tmpl` — set automatically).
2. `page_message` = page title text only, matching `page_name`. No HTML, no `<style>`, no sections.
3. `page_tmpl` = a full HTML document: `<!DOCTYPE html>`, `<html>`, `<head>`, `<body>`, styles, markup.
4. `$POWERED_BY$` is **required** inside `page_tmpl` — the API returns `VALIDATION_ERROR` without it.
5. `$POWERED_BY$` must never be hidden via any CSS technique.
6. Set `page_ownurl` and the SEO meta fields explicitly if the page needs a permanent public URL.

### Minimal contract for a personal-template page

```
templates_tool(
  action="page_add",
  page_name="Title",
  page_message="Title",
  page_owntmpl=1,
  page_ownurl="slug",
  page_meta_title="Title",
  page_meta_description="Description",
  page_tmpl="<!DOCTYPE html>...$POWERED_BY$...</html>"
)
```

### Converting an existing page to a personal template

1. `page_list` → find the page (`id`, current `owntmpl`, URL).
2. `page_get` → read current title and SEO fields.
3. If `owntmpl=0` and the task needs custom HTML/CSS/layout — do **not** ask the user to enable "личный шаблон" in the panel. Call `page_update` with:
   - `page_owntmpl=1`
   - `page_message` = page title only (no HTML)
   - `page_tmpl` = full HTML document with `$GLOBAL_AHEADER$`, `$GLOBAL_BFOOTER$`, `$MFORM_<ID>$` as needed, and `$POWERED_BY$`
   - `page_meta_*` as needed
4. After first publish, the page template is editable via `read_template` / `patch_template` on `module_id=2, template_id=<page_id>`.
5. **First publish** → full `page_tmpl` in `page_update`. **Later HTML edits** → `patch_template` on `2/<page_id>`, not another full `page_tmpl` overwrite (unless the user confirms a full rewrite).

### `$POWERED_BY$`

`$POWERED_BY$` is the mandatory uCoz platform copyright link. It is a Terms of Service requirement and must remain visible in every full HTML document template.

**Never remove, hide, or visually mask it.** This includes any of: `display:none`, `visibility:hidden`, `opacity:0`, matching text/background color, `height:0`, `font-size:0`, `overflow:hidden` on a wrapping element, covering it with `position:absolute`/`fixed`, or moving it off-screen. All of these count as violations.

### Don'ts

- Do not put HTML markup in `page_message` when `page_owntmpl=1`.
- Do not duplicate markup across both `page_message` and `page_tmpl`.
- Do not pass `page_owntmpl=0` if the page is meant to keep its personal template — this deletes it.
- Do not pass `page_tmpl` without `$POWERED_BY$`.
- Do not hide `$POWERED_BY$` with any CSS technique.
- Do not attempt to delete the homepage (`page_id=1`).
