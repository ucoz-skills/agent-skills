# Content modules — fields, required data and quirks

All 13 content modules below were installed and written to through MCP. "Verified" notes are observed behaviour, not documentation.

Always start with `content_read` `fields` for the module: it lists every field with `code`, `checked` (on) and `required` **on this site**. A required field must be sent on `add`. Turning a field on/off is a module setting (`fields`) — read the warning in SETTINGS_AND_MODULES.md before touching it.

Module codes: `news`, `blog`, `publ`, `load`, `board`, `dir`, `faq`, `photo`, `video`, `gb`, `stuff`, `polls` (= `poll`), `mchat`. Forum → `forum_*` tools; shop → `shop_*` tools.

## Quick matrix

| Module | Required on `add` (stock) | Text params | Media | Category SEO renders via `$SEO_TITLE$` | Template group id* |
|---|---|---|---|---|---|
| news | `title`, `message` | `description` (off by default), `message` | `cover_*`, `image_upload_ids`, `file_*` (field `fname`, off) | yes — via archive template 6/1 | 6 |
| blog | `title`, `message` | `description` (off by default), `message` | `cover_*`, `image_upload_ids` | yes — via archive template 7/2 | 7 |
| publ | `title`, `description`, `message` | + `name`, `author_email`, `author_site`, `link2source` | `cover_*`, `image_upload_ids` | yes (10/3) | 10 |
| load | `title`, `description`, `message` | + `name`, `author_email`, `author_site` | `archive_*`, `cover_*`, `screenshot_*` | yes (11/3) | 11 |
| dir | `title`, `link2site`, `description`, `name` | `message` off by default | `screenshot_*` (off) | yes (12/3) | 12 |
| board | `ad_type`, `title`, `description`, `name`, `author_email`, `till_date` (auto) | `message`, `author_phone` | `screenshot_*`, `image_upload_ids` | yes (13/3) | 13 |
| faq | `subject` | `question`, `answer` | `image_upload_ids` | — | 14 |
| photo | the photo itself | `title`, `description` | `photo_upload_id` (one per entry) | yes, after patching 9/3 | 9 |
| video | `title`, `category`, `description`, `object` | extra fields via `fields` | `image_upload_ids` (screenshots) | **no** — video categories have no meta fields | 22 |
| gb | `message` | — | — | — | 15 |
| stuff | `title`, `description`, `screenshot` (logo), `files`, `filter1` | `name`, links via `fields` | `screenshot_*`, `image_upload_ids` | yes (21/3) | 21 |
| polls | `question`, `answer1`, `answer2` | `answer3..N` via `fields` | — | — | 16 |
| mchat | `message` | — | — | — | 17 |

\* Template group ids from `templates_read list_modules`; they are stable per module type, but confirm them on the target site.

## Recipe — a material with full SEO, end to end

```text
# 0. Preflight (once per module)
content_read fields module="publ"                        → required/on fields
content_read category_list module="publ"                 → target category id (guests can see it?)
settings_read settings_get module="seo"                  → seo_UseUniqTitleDescKeys / seo_ChpuUrlType on (SEO_AND_URLS §1)
content_read list module="publ" detail="brief"           → no near-duplicate title already exists

# 1. Images (cover + gallery), in parallel
files_write upload_direct filename="kak-vybrat-noutbuk-cover.jpg" size=<bytes>   → POST → cover id
files_write upload_direct filename="kak-vybrat-noutbuk-1.jpg"     size=<bytes>   → POST → img id 1
files_write upload_direct filename="kak-vybrat-noutbuk-2.jpg"     size=<bytes>   → POST → img id 2

# 2. Create (draft first if the user wants to review: pending=1)
content_write add module="publ"
  category=4
  title="Как выбрать ноутбук для учёбы: 7 критериев"
  description="<p>Разбираем, на что смотреть при выборе ноутбука для учёбы: вес, автономность, экран, память и бюджет.</p>"
  description_type="html"
  message="<p>…</p><h2>1. Вес и размер</h2><p>…</p>
           <figure><a href=\"$IMG_URL1$\" class=\"ulightbox\"><img src=\"$IMG_URL1$\" alt=\"Лёгкий ноутбук на столе студента\" class=\"ulightbox\" loading=\"lazy\"></a><figcaption>…</figcaption></figure>
           <h2>2. Автономность</h2><p>…</p>
           <figure><a href=\"$IMG_URL2$\" class=\"ulightbox\"><img src=\"$IMG_URL2$\" alt=\"Индикатор заряда ноутбука\" class=\"ulightbox\" loading=\"lazy\"></a></figure>"
  message_type="html"                                    ← $IMG_URLn$ = slot n of image_upload_ids below, same call
  name="Редакция"                                        ← publ author field, if on
  meta_title="Как выбрать ноутбук для учёбы в 2026: 7 критериев"
  meta_description="Вес, автономность, экран, память и бюджет: простой чек-лист выбора ноутбука для школы и вуза с примерами конфигураций."
  meta_keywords="как выбрать ноутбук, ноутбук для учёбы"
  tags="ноутбуки,учёба,советы"
  cover_upload_id="<cover id>"                           ← cover: templates/lists/OG only, never in the text
  image_upload_ids="<img id 1>,<img id 2>"               ← slots 1, 2 → $IMG_URL1$, $IMG_URL2$ in message
  → id, entry_url, cover, files[] (webp), message with resolved images, includehtml_* = yes

# 3. Own URL — only if the user wants one (SEO_AND_URLS §4); never send hgu_title
content_write update module="publ" id=<id> ownurl="kak-vybrat-noutbuk"

# 4. Verify
content_read get module="publ" id=<id>                   → meta_title, meta_description, original_message (real tags, image markers), files, cover
                                                           (get returns no tags and no includehtml_* — those come from the add reply / the page)
GET entry_url as a guest                                 → 200, <title> = meta_title, <meta name="description">, one h1, cover,
                                                           every text image visible with alt, tags shown, lightbox opens
```

Material meta renders after patching the entry page template (verified on news, blog, publ, load). In every module, check the `<head>` of the first material you publish: if `<title>` is still the default, patch the entry page template (TEMPLATES.md Patch 1).

Per-module differences in step 2: required fields from the matrix above (board needs `ad_type`, `name`, `author_email`; video needs `category` and `object`; stuff needs `screenshot_upload_id`, `image_upload_ids`, `filter1`; photo needs `photo_upload_id`; faq uses `subject`/`answer`).

## Field code → parameter

| `fields` code | Parameter | Notes |
|---|---|---|
| `title` | `title` | Avoid ASCII `"` (see FORMATS.md) |
| `description` | `description` + `description_type` | Lead / ad text / site description |
| `message` | `message` + `message_type` | Full text |
| `subject` / `answer_text` (faq) | `subject` / `answer` | |
| `author` (publ, load, board, dir, stuff, video) | `name` | Author / owner / contact person. The `author` **parameter** is the login of the material owner — different thing |
| `author_email`, `author_site`, `author_phone` | same names | |
| `link2source` (publ), `link2site` (dir) | same names | |
| `ad_type` (board) | `ad_type` or `filter3` | Value **id** from `filter_list` |
| `files` | `image_upload_ids` / `image_upload_id` | Gallery slots |
| `cover` | `cover_upload_id` | |
| `screenshot` | `screenshot_upload_id` | stuff: the game logo |
| `archive` | `archive_upload_id` | load / board / dir / stuff |
| module-specific (`year`, `producer`, `acters`, `language`, `duration`, `quality`, `genre` for video; `link2swf`, `link2pc`, `link2mac`, `link2mob`, `playground_sizes` for stuff; `archlink`, `archsize`, `versions`, `licence`, `os`, `language` for load) | inside `fields: {…}` | Verified for video and stuff |
| `other1..5` | `other1..other5` | Site-specific custom fields |
| `comments` | `subscribe` | Notifications for the author; allow/deny comments is the `comments` parameter (`1`/`0`) |

## Per module (verified)

### news — Новости сайта
- `title` + `message` (HTML always kept). `description` is off by default — turn it on only if leads are needed.
- Cover stored as uploaded JPG (`/_nw/0/<n>.jpg`).
- Attached file (`fname`) is off by default; with a file and the field off, the call is refused. `FILE_NOT_SAVED` + `entry_saved: true` → send the file with `update`.
- Templates (group 6): `2` entry page, `1` archive — **renders category pages and /news/ too**, `6` section list, `3` entry view.
- Entry title variable `$ENTRY_NAME$`; the stock entry page has no H1.

### blog — Блог
- Same shape as news. Cover keeps the upload format (`.jpg`), gallery images become webp.
- Templates (group 7): `3` entry page, `2` archive — **renders category pages**, `7` section list, `1` main, `4` entry view (prints the title into `alt="$ENTRY_NAME$"` — another reason to avoid ASCII quotes in titles).

### publ — Каталог статей
- Sections (top level) contain categories. `category_add` has no parameter for the parent section: on a site with sections, tell the user where the new category must be moved in the Control Panel. Author fields go in `name`, `author_email`, `author_site`, `link2source` — fill them only with what the user gave; never invent a signature like «Редакция».
- Cover stays JPG as uploaded; images become webp. The stock entry page prints the cover but **no gallery** — reference images in the text.
- Templates (group 10): `4` entry page, `3` category list, `2` section list, `1` main, `7` entry view.

### load — Каталог файлов
- File: `archive_upload_id` (field `archive` on by default). Reply: `archive_direct_url` (`/_ld/0/<id>_<name>.zip`), `archive_url` (download page), `archive_size`.
- External file: `archlink` (+ `archsize`) via `fields`.

### dir — Каталог сайтов
- Required: `title`, `link2site`, `description`, `name` (owner). `message` and `screenshot` are off by default.

### board — Доска объявлений
- After install filter 3 "Тип объявления" has **no values**; add them first: `filter_update module=board filter_num=3 items="Продам|Куплю|Услуги"`.
- `ad_type` takes the value id. `till_date` defaults to +31 days.
- Value lists keep ids by name: `filter_update` with existing names in any order plus a new one kept the old ids and gave the new value the next id. Display order follows the ids, so a new type always appears last; reordering would mean renumbering types existing ads use — tell the user instead. Renaming a value: untested, assume it may renumber.
- The ad page shows the contact (`name`) **and** "Добавил: <account login>"; the email renders as a script-built mailto link, not as plain text.
- Guests may be unable to open the add form even if category `access2add` includes `999` — group rights of the module decide.

### faq — FAQ
- `subject` is the question line; `question` the optional longer description; `answer` + `message_type`.
- Entry URL `/faq/<cid>-0-<id>`.

### photo — Фотоальбомы
- Albums are categories. One photo per entry: `photo_upload_id` + `title` + `description`. The `fields` code for the photo name is `name`, but the **parameter is `title`**.
- Reply: `photo_direct_url` (`/_ph/<album>/<n>.webp`), `thumb_small`, `thumb_mid`, `photo_url` — **no name/description**; verify with `album_list category=<album id>`.
- Bulk photos into an album = one `add` per photo (parallel calls), each with its own `photo_upload_id`.
- Sizes differ from other modules: `images.full_width` 500 (page view), `original_width` 1600 (kept original). Upload ~1600 px on the long side.
- Album page (template 9/3) and photo page (9/4) have no `$SEO_TITLE$`, meta description, `$CAT_DESCR$` or h1 in stock 2301. Patch 1 on 9/3 makes album meta render (verified); the album description shows only with `categories_show_category_description: true` (`false` by default for photo).
- No generator: clean illustrations are fine for an album when the user agreed; start each caption with «Иллюстрация:» so they aren't mistaken for real photos of the event.

### video — Видео
- `category` is mandatory (error "Не выбрана категория" otherwise); video categories come from `category_list module=video`.
- `object` — embed iframe HTML. It is stored escaped and renders as a working player; the platform appends `?wmode=transparent&autoplay=1` to the src.
- Extra fields through `fields`: `{"year":"2024","duration":"3:33","language":"ru"}` → stored `duration: "00:03:33"`, `language: "Русский"`. **Always send `language`** (`en` → English): if omitted it is saved as «Русский».
- Replies are unreliable for video: `entry_url` is malformed (`/video/vip/<id>//slug`, 301-redirects), the `categories` array is junk, `comments` differs between add and `get`, and `get` has no category. Verify on the public page.
- Channels: `channel_add`/`channel_update`, `channel_category_*`.
- Categories run on the video module's own engine (verified on a live site): `category_add` / `category_update` take only `category` (parent), `name`, `description`, `access2category`, `access2add`. **No `meta_title` / `meta_description` / `meta_keywords`** (silently dropped, not in the reply, not in `category_list`), no `access2page`, no image. Category SEO for video can't be done — say so, don't patch templates for it. The reply shows rights as the string «Все группы».
- `category_list` gives a broken category `url` (`/video/vicobuchajushhie_video`, 404). The real address is `/video/vic/<hgu_title>` — build it yourself or take it from the module page.

### gb — Гостевая книга
- `add` posts **as the signed-in account** (author = account name). `name`, `email`, `www` are ignored, required-field checks don't apply. Use it for official replies/notices, not to fabricate visitor reviews.
- `rate` (good/bad) and moderation via `pending`.

### stuff — Онлайн-игры
- Required on a stock site: `title`, `description`, `screenshot` (logo, `screenshot_upload_id`), `files` (≥1 image, `image_upload_ids`), `filter1` (platform: `1` online, `2` downloadable, `3` mobile; multiselect).
- Game links via `fields`: `link2swf`, `link2pc`, `link2mac`, `link2mob`, `playground_sizes`.

### polls — Опросы
- `question`, `answer1`, `answer2` are required; more answers via `fields: {"answer3":"…","answer4":"…"}`. The `answers` parameter is ignored on add.
- Reply `CODE_VAR` (e.g. `$POLLC_3$`) is the code to place the poll in a template; `$POLL$` shows a random poll.

### mchat — Мини-чат
- `message`; posted as the signed-in account. Moderation/cleanup only on request.

## Moderation, pinning, dates

- `pending: 1` → moderation queue (not public). `list pending=1` shows it; publish with `update pending=0`.

**Hiding a published material on request (verified):** `update pending=1` takes it off the module lists, category pages and the main page; `update pending=0` brings it back with the same URL and date (on modules with `options_show_adddate: true`, also send the date fields — "Dates" below).

| Module | Direct link for guests after `pending=1` |
|---|---|
| publ, blog | 302 to the module page — fully hidden |
| news | **still 200** — gone from lists, but the page opens by its link |

Check the direct URL as a guest after hiding and tell the user what the result is. If a news item must become unreachable, say so and offer deletion (`content_delete`, `confirm: true`) — hiding there only takes it off the lists. To hide a whole category, restrict `access2category` to staff (`3,4`) — CATEGORIES_AND_ACCESS.md.
- `ontop: 1` pins (only if the module option `options_entry_ontop` is on).
- `user_id` acts as another user — only on request.

## Dates: publication date and modification date (verified)

Two dates per material: `add_date` / `add_date_ts` — publication (shown on the page, orders the lists, part of the blog/news URL `/2026-10-25-6`); `mod_date` / `mod_date_ts` — last modification (`$MOD_DATE$`, sitemap `lastmod`).

**Modification date — `uplastmod`.**

| `update` call | `mod_date` |
|---|---|
| without `uplastmod` | unchanged (4 updates in a row left it as it was) |
| `uplastmod: 1` | set to now |

Use `uplastmod: 1` when the content really changed (rewrite, new facts, new section) — search engines re-crawl by `lastmod`. Don't use it for typo fixes, meta-only edits or bulk technical changes unless the user wants all of them marked fresh.

**Publication date — the trap.** Each module has the option «allow editing the add date» (`settings_get <module>` → `options_show_adddate`).

| `options_show_adddate` | What `update` does to `add_date` |
|---|---|
| `false` | keeps it (verified) |
| `true` | **resets it to now on every update** unless the date is sent as form fields — the `add_date` parameter is ignored on update (verified) |

When the option is `true`, every `update` of an existing material must carry its current publication date in the site's local time:

```text
content_read get → add_date "2026-10-25 21:49" (server time)
settings_get site → timezone_offset 3
→ local time 2026-10-26 00:49
content_write update … fields={"ya":"2026","ma":"10","da":"26","ha":"00","mia":"49"}
→ add_date stays "2026-10-25 21:49" (minute precision)
```

Combine with `uplastmod: 1` when the modification date should move: verified — `add_date` kept, `mod_date` = now.

Changing the publication date on purpose (back-dating, scheduling, staggering a series): the same `fields` with the new local date on `update`; on `add` the `add_date` parameter works. After any write, compare `add_date_ts` in the reply with the planned value.
