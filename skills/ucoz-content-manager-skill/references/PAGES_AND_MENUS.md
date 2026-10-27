# Site pages and menus

## Site pages (module "Редактор страниц")

Static pages (About, Contacts, Delivery, Privacy…) are not materials; they live in `templates_*`.

| Action | Tool | Key parameters |
|---|---|---|
| List | `templates_read page_list` | paging, `detail` |
| Read | `templates_read page_get` | `page_id` |
| Create | `templates_write page_add` | `page_name`, `page_message` (HTML), `page_parent_id`, `page_pending`, `page_ownurl`, `page_meta_title`, `page_meta_description`, `page_meta_keywords`, `page_access_all`, `page_other1..5` |
| Update | `templates_write page_update` | `page_id` + fields to change |
| Own template | `page_owntmpl: 1` + `page_tmpl` (full HTML with `$POWERED_BY$`) | only on explicit request (see below) |
| Delete | `templates_delete page_delete` | `page_id`, `confirm: true`; page 1 (home) cannot be deleted |

**Default: a regular page.** Put the content into `page_message` and leave `page_owntmpl` off: the page is rendered inside the site's common page template (`templates_read list_modules` → «Редактор страниц», template `0` «Страницы сайта») with the site header, menu, sidebar and footer, and changes of the design apply to it automatically. Never create a page as a standalone own-template HTML document on your own initiative — that cuts it off from the site design and every later design change.

Own template only when the user asks for a standalone page (a landing with its own layout) or the site already builds this kind of page that way (`page_get` → `owntmpl: true` on its siblings — then copy their structure, including `$GLOBAL_…$` blocks). The own template is then visible as `templates_read read_template module_id=2 template_id=<page id>` and is edited with `patch_template` like any template. Global blocks often switch content per page with `<?if($PAGE_ID$='sitePageN')?>` (menus, scripts): read the global blocks the siblings use, and add the new page's id (`sitePage<id>`) to every condition its siblings have — otherwise the page renders without its menu.

- `page_message` is saved as HTML whatever editor the site uses.
- `page_ownurl` gives the page a clean URL (same `ownurl` rules and HTTP check as in SEO_AND_URLS.md).
- `page_access_all: 1` — visible to all groups. Check after creating that guests can open it.
- The home page (id 1) is often built by a landing; edit it only on request, with `page_get` first.

## Menus

| Action | Tool |
|---|---|
| `menu_list`, `menu_get(menu_id)` | `templates_read` |
| `menu_create(title, layout)` | `templates_write` (layout `0` horizontal, `1` vertical) |
| `menu_update_items(menu_id, items)` | `templates_write` — **replaces the whole tree** |
| `menu_delete(menu_id)` | `templates_delete`, `confirm: true` |

Item: `{ "title": "…", "url": "/blog/", "target": 0|1, "class": "…", "children": [ … ] }`. No other keys.

Adding one item safely:

1. `menu_get` and keep the full current tree.
2. Insert the new item at the agreed position; copy every other item unchanged (titles, URLs, targets, classes, children).
3. `menu_update_items` with the full tree.
4. `menu_get` again: item count = old + 1, nothing else changed.

Menus render through `$NMENU_<id>$` / `$SMENU_<id>$` in templates. A new menu is invisible until a template prints it — that is a template change (TEMPLATES.md), ask first.

Typical content tasks: link a new category or section in the main menu; add "Статьи"/"Блог" when a module is installed; link static pages in the footer menu.
