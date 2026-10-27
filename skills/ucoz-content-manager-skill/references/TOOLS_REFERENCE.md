# Tool reference — content, files, settings, modules, templates

Every call works on the site chosen with `select_site`. Parameter names below are the MCP tool parameters. Pass `confirm: true` only after the user approved that exact change.

## content_read

| Action | Purpose | Parameters |
|---|---|---|
| `list` | Entries of a module, paged | `module`; optional `category`, `date` (`yyyy-mm-dd` or `yyyy`), `pending` (`1` = moderation queue), `user_id`, `sort` (`asc`/`desc`), `page`, `per_page` (≤50), `offset`, `detail` (`brief` default / `full`) |
| `get` | One complete entry | `module`, `id` |
| `fields` | Entry fields of the module on this site: `code`, `checked` (on), `required` | `module` |
| `category_list` | All categories, or one by `id` (includes `image`, access arrays, meta, slug, `entry_count`, public `url`) | `module`; optional `id` |
| `comments_list` | Comments of an entry | `module`, `id`; optional paging, `detail` |
| `comments_all` | Latest comments of all modules | optional paging, `detail`, `tree`, `sort` |
| `filter_list` | Entry filters (value lists) | `module`; optional `filter_num`, `id` |
| `album_list` | Photos of albums (module `photo`) | `module`; optional `category` |
| `channel_list` / `channel_category_list` | Video channels and their categories | `module: video` |
| `smiles` | Mini-chat smiles | `module: mchat` |

Notes:
- `list` replies are capped at 30 KB; `brief` entries carry id, title, status, date, URL, category id, excerpt. Use `get` for full texts.
- `has_more` + `next` give the next page; never assume a single page is everything.

## content_write

| Action | Purpose | Required | Useful optional |
|---|---|---|---|
| `add` | Create and publish an entry | `module` + the module's required fields (see `fields`) | `category`, `title`, `description`, `message`, `description_type`, `message_type`, `meta_title`, `meta_description`, `meta_keywords`, `tags`, `pending`, `ontop`, `comments`, `add_date`, `other1..5`, `filter1..5`, file slots, `image_upload_ids`, `show_attach` |
| `update` | Change an entry | `module`, `id` | same as `add`, plus `ownurl`, `uplastmod` (`1` = set modification date to now). `add_date` is ignored on update; to keep/set the publication date when the module allows date editing, send `fields: {ya, ma, da, ha, mia}` in site local time (MODULES.md, "Dates") |
| `tags` | Replace tags of an entry | `module`, `id`, `tags` (comma-separated) | — |
| `category_add` | Create a category | `module` | `name`, `description`, `position`, `access2category`, `access2add`, `access2page`, `meta_title`, `meta_description`, `meta_keywords`, image slot (`image_upload_id` …), `fields` |
| `category_update` | Change a category | `module`, `id` | same, plus `ownurl`. Empty values are not sent |
| `comments_add` / `comments_update` | Comments | `module`, `id`, `message` / `module`, `id` | `parent_id`, `user_id` |
| `filter_add` / `filter_update` | Entry filter value lists | `module` / `module`, `filter_num` | `name`, `items` (`A|B|C`) |
| `channel_add` / `channel_update` / `channel_category_*` | Video channels | `module: video` | `name`, `title`, `category`, `description`, `parent_id` |
| `rate` | Rate a guestbook entry | `module: gb`, `id`, `mark` (1 good, 2 bad) | — |

### File slots of add / update / category_add / category_update

Slots: `image_*` (entry `file1`, or the category image), `cover_*`, `photo_*` (photo module), `screenshot_*`, `file_*` (attached file), `archive_*` (load/board/dir/stuff archive).

**Use only `*_upload_id` (and `image_upload_ids`) from `upload_direct`.** The schema also accepts `*_base64`, `*_site_path` and `*_path` — they are forbidden in this skill (MEDIA_WORKFLOW.md §7): no base64 through the model, no detour through the file manager.

`image_upload_ids` (comma-separated, ≤50) fills `file1`, `file2`, … in order, after `image_upload_id` if both are given. It **replaces** what is in those slots.

The attached `file_*` / `archive_*` is saved only when that field is on for the module (`fname` for news, `archive` for the others in `fields`) and the group may upload files; otherwise the call is refused. News: an add whose file was not saved returns `FILE_NOT_SAVED` with `entry_saved: true` — send the file with `update` and the new id, never a second `add`.

### Reply fields worth checking

`id`, `entry_url`, `hgu_title`, `ownurl`, `use_ownurl`, `includehtml_desc`, `includehtml_msg`, `end2br_*`, `original_description`, `original_message`, `files[]` (`full`, `small`, sizes), `cover` (`full`, `small`, sizes), `category` (with `url`), `filters`, `pending`.

What each reply shows (verified):

| | `add` reply | `update` reply | `get` |
|---|---|---|---|
| `meta_title` / `meta_description` | no | no | **yes** |
| tags | no | no | **no** — check the public page |
| `includehtml_*`, `end2br_*` | yes | yes | no |
| `original_message` | echoes your input (literal `$IMG_URLn$`); the resolved images are in `message` | often `""` even when saved correctly | **yes** — the stored source: `$IMAGEn$` kept, `$IMG_URLn$` resolved between `<!--IMG_URLn-->` markers |
| `files[]`, `cover` | yes | yes | yes |

Verify stored values with `get` + the public page; never stop a batch because an `update` reply shows an empty `original_message`.

## content_delete (cannot be undone, all need `confirm: true`)

`delete` (`module`, `id`), `category_delete` (`module`, `id`), `comments_delete`, `filter_delete` (`filter_num`), `channel_delete`, `channel_category_delete`.

Before `category_delete`: read `entry_count`; ask what happens to the materials. Never delete demo content, categories or materials unless the user asked.

## files_read / files_write

| Action | Purpose |
|---|---|
| `files_read` `quota` | Disk quota, upload size limit, allowed extension filters |
| `files_read` `list` / `info` / `content_get` | Browse the file manager, read text files |
| `files_write` `upload_direct` | `filename`, exact `size` (1..16777216 bytes) → `upload_url`, `upload_id`, `expires_in: 900`. POST the raw bytes to `upload_url` as `application/octet-stream`. Reply: `ok`, `upload_id`, `filename`, `size`, `sha256` |
| `files_write` `upload` | Save uploaded ids into a file-manager folder: `path`, `upload_id` or `upload_ids` (≤20). Original bytes kept. **File-manager tasks only** — never for material/category images |
| `files_write` `upload_url` | Download a public URL into the file manager (`url`, `path`, `name`). File-manager tasks only |
| `files_write` `mkdir` / `rename` / `move` / `content_put` | File manager housekeeping |
| `files_delete` `delete` | `path`/`paths`, `confirm: true`; a folder goes with its contents |

Limits and when to use the file manager at all: MEDIA_WORKFLOW.md §8.

An `upload_id` is spent only when an entry/category/folder actually consumes it; unused ids expire after 900 s.

## settings_read / settings_write

| Action | Purpose |
|---|---|
| `settings_read` `settings_modules` | Modules that have settings and whether each is active |
| `settings_read` `settings_get` | Current settings of `module` (`blog`, `board`, `dir`, `faq`, `forum`, `gb`, `load`, `mchat`, `news`, `photo`, `poll`, `publ`, `search`, `seo`, `shop`, `site`, `stuff`, `tests`, `users`, `video`) |
| `settings_write` `settings_update` | `module`, `values` (flat keys: nested path joined with `_`, e.g. `cover.thumb_width` → `cover_thumb_width`), `dry_run`, `confirm` |

`dry_run: true` returns `current` values and `unknown` keys without saving. A real update returns `changed` (before/after) and `unchanged` (with a reason: already set, unknown, clamped). See `SETTINGS_AND_MODULES.md` for keys that need `confirm`.

## modules_read / modules_write / modules_uninstall

| Action | Purpose |
|---|---|
| `modules_read` `active_mods` | Installed modules (`code`, `name`) |
| `modules_read` `modules_list` | All modules with `active` and `api_enabled` |
| `modules_read` `module_status` | One module |
| `modules_read` `quarantine_status` | Indexing quarantine of the site |
| `modules_write` `module_install` | Install `module_code`; also turns its uAPI on and creates demo content |
| `modules_write` `module_enable_uapi` / `module_disable_uapi` | uAPI flag |
| `modules_write` `quarantine_unlock` | Lift indexing quarantine (`confirm: true`) |
| `modules_uninstall` `module_uninstall` | Remove a module, data may be lost (`confirm: true`). Protected modules (users, seo…) refuse |

## templates_read / templates_write / templates_delete

| Action | Purpose |
|---|---|
| `list_modules` | Template groups with numeric `id` and template ids |
| `read_template` / `get_variables` | Template code + its variables (`code`, `description_mcp`) |
| `validate_template` | Check a full body or a `code_search`/`code_paste` patch without saving — an action of **`templates_read`**, not `templates_write` |
| `patch_template` | Replace one exact fragment (auto-backup; result has `backup.id`) |
| `update_template` | Replace a whole template (auto-backup) — only for a new template or an approved rewrite |
| `create_backup` / `list_backups` | Manual backup (never rotated) / list |
| `restore_backup` | Restore a backup over ALL templates (`confirm: true`) |
| `gblock_create` / `gblock_delete` | Global blocks |
| `page_list` / `page_get` / `page_add` / `page_update` / `page_delete` | Site pages (Page editor module) |
| `menu_list` / `menu_get` / `menu_create` / `menu_update` / `menu_update_items` / `menu_delete` | Menus |
| `get_reference` / `get_guide` / `get_backup_guide` | Variable reference and guides (markdown) |
