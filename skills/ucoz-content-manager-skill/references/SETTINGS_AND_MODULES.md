# Modules and settings

## Installing a module

```text
modules_read modules_list                   → module, active, api_enabled
modules_write module_install module_code="blog"
```

Verified behaviour of `module_install`:

- the reply has `activated: true`, `active: true`, `api_enabled: true` — uAPI is switched on automatically;
- the module comes with **demo content**: categories and materials with stock texts and covers (checked for all 13 content modules; polls get a demo poll, photo two albums with photos);
- its templates appear in `templates_read list_modules`.

After installing: show the user the demo items (`category_list`, `list`) and ask whether to keep, rewrite or delete them (`content_delete` with `confirm: true` per item). Never delete them silently.

Uninstall: `modules_uninstall module_uninstall` with `confirm: true` — data may be lost. Only on an explicit request, after listing what will disappear. Protected modules (users, seo) refuse.

If a content tool says the module is unavailable for uAPI, `modules_write module_enable_uapi`.

## Reading settings

```text
settings_read settings_modules              → modules with settings + active flag
settings_read settings_get module="blog"    → nested settings
```

Blog settings example:

```json
{"name":"Блог","switch":2,"rating":true,
 "entries":{"per_page":10,"per_category":10,"per_rss":10,"per_top":50},
 "entry_split":{"enabled":true,"symbols":500},
 "comments":{"enabled":true,"per_page":10,"top_first":true},
 "cover":{"enabled":true,"required":false,"full_width":1920,"full_height":1080,"thumb_width":320,"thumb_height":240,"max_size_kb":6144,"quality":95},
 "images":{"max_count":5,"max_size_kb":6144,"full_width":1500,"full_height":1500,"thumb_width":400,"thumb_height":500,"jpg_quality":90,"loading_type":"lazy"},
 "categories":{"show_category_description":true,"show_numbers":true,"columns":1},
 "options":{"other_categories":false,"entry_ontop":false,"show_adddate":false,"disable_similar":false},
 "fields":[{"code":"title","checked":true,"required":true}, ...]}
```

## Changing settings

Flat keys: join the nested path with `_` (`entries.per_page` → `entries_per_page`, `cover.thumb_width` → `cover_thumb_width`, `options.show_adddate` → `options_show_adddate`). Lists such as `fields` are sent whole.

Always two steps:

```text
settings_update module="blog" dry_run=true values={"entries_per_page":12,"options_show_adddate":true}
  → current: {"entries_per_page":10,"options_show_adddate":false}, unknown: []
settings_update module="blog" values={"entries_per_page":12,"options_show_adddate":true}
  → changed: [{key, before, after}], unchanged: [{key, reason}]
```

`unknown` / `unchanged` with reason "unknown" means the key does not exist for that module — don't guess another name, read `settings_get` again.

### The `fields` list — known defect, handle with care (verified)

`fields` (which entry fields are on/required) is sent as a whole list. Every content module was tested (one field switched on, then back off, each write checked with `content_read fields`):

| Module | Result of a `fields` write |
|---|---|
| publ, load, board, dir, faq, photo, video | Correct, mapped by `code`; switching back works |
| **blog** | **Position shift** (details below) — reproduced twice |
| **news** | The sent list is written correctly, but **`fname` («Прикрепить файл») silently turns on** and can't be turned off through MCP: it isn't in the `settings_get` list, sending it explicitly returns `unchanged`. Turning it off is a Control Panel job |
| **gb** | `required` follows `checked`: a field switched on with `required: false` is saved as required (country, city). Switching it off resets both. A non-required visible field can only be set in the Control Panel |
| stuff | `settings_get` shows the list nested as `fields.fields`. Send it as `fields` (the same list format as the other modules): it is written correctly by `code`, on and back off, **but the reply says `unchanged` / "not found in settings"** — trust only `content_read fields`. The flat key `fields_fields` is silently ignored (`unchanged`, nothing written) |

Blog, the shift — the write did **not** map by `code`:

- sent in `settings_get` order with `description: true` → `description` turned on, but `other1` turned **on** and `files` (images) turned **off**;
- values inside the block `other1..other5, files` landed one slot further (value for `other1` → `other2`, value for `files` → `other1`, value for `other5` → `files`);
- the "changed/unchanged" check compares by position too, so a correct-looking list can be reported `unchanged` and not written at all.

The reply is not proof either way: `changed.after` can look right while the stored list is shifted (blog), and `unchanged` / "not found" can come back for a write that was applied (stuff). Verify with `content_read fields`, the independent reader. When `settings_get` and `content_read fields` disagree (news `fname`), `content_read fields` is the truth.

Rules:

1. Avoid changing `fields` unless the task needs it (e.g. turning on leads). Say in the plan that it touches a list setting with a known defect.
2. Snapshot first: `content_read fields module=<m>` (independent reader) and `settings_get`.
3. Send the list, then immediately re-read `content_read fields` and compare **every** field with the target.
4. If anything moved, correct it with a compensating list (shift the values back by the observed offset) and re-read again. Repeat until `content_read fields` matches the target exactly — never leave a module with images or required fields switched off.
5. News: warn before the write that «Прикрепить файл» will turn on and stay on. gb: warn that the switched-on field becomes required. Ask before going ahead.
6. Report any deviation to the user as an MCP defect (with before/after) so it gets fixed upstream.

Cover on/off is not in `fields` — use `cover_enabled`.

### Keys that need `confirm: true` (and the user's explicit decision)

| Module | Key | Effect |
|---|---|---|
| `seo` | `seo_DisableIndexing` → true | Search engines stop indexing the domain |
| `site` | `availability_closed` → true | Site closed for visitors |
| `users` | `reinstall_popup_templates`, `mass_mail` → true | Rewrites popup templates / sends mail to all users |
| `users` | `awards_enabled` → false | Deletes all awards |
| `users` | `registration_allow_unet`, `registration_allow_local` | Can switch registration mode for good |
| `forum` | `reset_visitor_record` | Resets the record |
| `shop` | `search_log_history` → false, `cart_invoice_next_number` | Drops search log / changes invoice numbering |
| `blog`, `news`, `board`, `publ`, `load`, `dir`, `stuff` | `options_other_categories` → false | Removes entries from their additional categories |

## Settings content work usually needs

| Goal | Module | Keys |
|---|---|---|
| Leads in blog/news | `blog`, `news` | `fields` (`description` `checked: true`) — follow the `fields` defect procedure above |
| Covers on | any content module | `cover_enabled`, `cover_full_width`, `cover_full_height`, `cover_thumb_width`, `cover_thumb_height` |
| More images per entry | module | `images_max_count`, `images_max_size_kb` |
| Pagination | module | `entries_per_page`, `entries_per_category` |
| Comments | module | `comments_enabled`, `comments_per_page` |
| Category/album description visible | module | `categories_show_category_description` (photo: `false` by default) |
| Show date / pin | module | `options_show_adddate`, `options_entry_ontop` |
| Several categories per entry | module | `options_other_categories` (turning **on** is safe; turning off needs confirm) |
| SEO features | `seo` | `seo_UseUniqTitleDescKeys`, `seo_ChpuUrlType`, `seo_UseRelCanonical` |
| Readable URLs | `site` | `human_friendly_urls` |
| Site title/meta | `site` | `site_name`, `seo_meta_title`, `seo_meta_description`, `seo_meta_keywords` (check with dry_run) |
| Image format/size site-wide | `site` | `images_save_format`, `images_full_width`, … |
| Watermark | `site` | `watermark_*` — only on request |

Turning a field on is reversible; turning a field off hides data that is already stored in it — warn first.

## Reporting

For every settings call list: module, key, before → after, and why. Settings are site-wide: a change affects all existing materials of the module.
