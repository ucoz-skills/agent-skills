# Categories, access rights, filters, tags

## Category anatomy

`content_read category_list` returns per category: `id`, `name`, `description`, `position`, `hgu_title` (slug), `url`, `meta_title`, `meta_description`, `meta_keywords`, `entry_count`, `image` (when set: `full_url`, `small_url`), `access2category`, `access2add`, `access2page`, `type` (`category` or `section`).

| Parameter | Meaning | What to send |
|---|---|---|
| `access2category` | Groups that may **see** the category and its materials | **All group ids** (default) |
| `access2page` | Page-level access; `[4]` here did not hide categories from guests, but all groups is the safe default | **All group ids** (default) |
| `access2add` | Groups that may **add** materials into it (also limited by group rights in the module) | `3,4` — staff (default); other groups only on request |

Restricted categories (members only, subscribers, staff drafts) only when the user asks — then build the arrays from the groups the user names.

Group ids come from `users_read groups`. Stock ids: `1` Пользователи, `2` Проверенные, `3` Модераторы, `4` Администраторы, `251` Друзья, `255` Заблокированные, `999` Гости. Read the live list — sites can have custom groups. The server drops `255` from the arrays on add and on update — expected.

## The access trap (verified)

`category_add` without access parameters produced `{"access2category":[4], "access2add":[4], "access2page":[4]}`; the public category page showed "Гостям запрещено просматривать данную страницу". Materials inside are invisible to visitors and search engines.

1. `users_read groups` → all group ids of the site (stock: `1,2,3,4,251,255,999`; sites can have custom groups like `5` for subscribers).
2. Pass all three arrays on every `category_add` per the table above — all groups for viewing, staff for adding.
3. Re-read with `category_list id=…` and open the public URL as a guest (no cookies): no "Доступ запрещён". Verified: `access2category` and `access2page` with all groups + `access2add=3,4` → guest gets 200 immediately.

Video is the exception: its categories take only `access2category` and `access2add` (no `access2page`, no meta, no image) — MODULES.md, video.

Don't copy the rights of a sibling category blindly: siblings can be restricted on purpose (a subscribers-only category with `[1,3,4,5]`).

## Reply quirks (verified)

- `category_add` reply has `id`, `hgu_title`, access arrays, meta — but **no `url`**. Get it from `category_list id=`; a just-created category may come back as `/publ/3` (no slug) — it 301-redirects to the slug URL.
- `position` is renumbered by the server (sent `3`, stored `2`) — positions are relative order, not exact values.
- Empty values are not sent by `category_update`: a field can't be cleared with `""`.

## URL of a category

- The system URL (with the automatic slug `hgu_title`) is generated from the name — never send `hgu_title`.
- A custom URL is set only with `ownurl` through the SEO module (SEO_AND_URLS.md); old URLs then 301-redirect to it.

## Category description

`description` accepts HTML and renders it (verified: `<p>…<strong>…</strong></p>` shown formatted on the load category page). There is no `description_type` for categories. Match the existing categories of the module: if they are plain text, write plain text; simple `<p>`, `<strong>`, `<a>` are fine. The template shows it via `$CAT_DESCR$` when the module setting `categories_show_category_description` is on — some stock category templates don't print `$CAT_DESCR$` at all (load 11/3): check the page, add it if missing (TEMPLATES.md §5).

## Category SEO

Fields: `meta_title`, `meta_description`, `meta_keywords` on `category_add` / `category_update`. They reach the page only if the template that renders the category page prints `$SEO_TITLE$` / `$SEO_DESCRIPTION$` (TEMPLATES.md, Patch 1).

Verified after patching the **right** template:

| Module | Template that renders category pages | Category meta renders |
|---|---|---|
| publ | 10/3 | yes |
| load | 11/3 | yes |
| news | **6/1** (archive template; not 6/6) | yes |
| blog | **7/2** (archive template; not 7/7) | yes |
| photo (album) | 9/3 | yes |
| board | 13/3 | yes |
| dir | 12/3 | yes (same title line as publ) |
| stuff | 21/3 | yes (same title line as publ) |
| video | 22/3 renders the page, but | **no** — video categories store no meta at all (MODULES.md, video) |

Verified fallback: a category without meta keeps the original title after the patch.

If the `<title>` doesn't change after a patch, you patched the wrong template — find the right one by fingerprint (TEMPLATES.md §1), don't conclude "not supported".

## Recipe — a category with full SEO, end to end

```text
# 0. Preflight
users_read groups                                        → group ids
content_read category_list module="publ"                 → siblings: description format, naming style (not their rights)
settings_read settings_get module="seo"                  → seo_UseUniqTitleDescKeys / seo_ChpuUrlType on (SEO_AND_URLS §1)

# 1. Image (optional)
files_write upload_direct filename="obzory-gadzhetov.jpg" size=<bytes>  → POST bytes → upload_id

# 2. Create — all fields in one call
content_write category_add module="publ"
  name="Обзоры гаджетов"
  description="Честные обзоры смартфонов, наушников и носимой электроники: тесты, сравнения, плюсы и минусы."   ← plain, like the siblings
  access2category="1,2,3,4,251,255,999"   ← all groups from users_read groups
  access2page="1,2,3,4,251,255,999"       ← all groups
  access2add="3,4"                          ← staff
  meta_title="Обзоры гаджетов: наушники, умные часы и советы по выбору"
  meta_description="Гайды по выбору гаджетов: беспроводные наушники, умные часы и носимая электроника — характеристики простым языком и чек-листы."
  meta_keywords="обзоры гаджетов, как выбрать наушники, умные часы"
  image_upload_id="<upload_id>"
  → id (the system URL is generated automatically — no hgu_title)

# 3. Own URL — only if the user wants one (rules: SEO_AND_URLS §4)
content_write category_update module="publ" id=<id> ownurl="obzory"

# 4. Fill it right away — materials recipe in MODULES.md (no public empty category)

# 5. Verify
content_read category_list module="publ" id=<id>         → access arrays, meta, image, url
GET url as a guest                                        → 200, no "Доступ запрещён", <title> = meta_title, meta description,
                                                            one h1, description visible, materials listed
```

## Category image

`category_add` / `category_update` take the image from the first filled slot of `image_*`, `file_*`, `cover_*`, `photo_*` — use `image_upload_id`. Result: `image.full_url` (`/_<mod>/0/c<cid>.webp`) and a 150×150 thumb. Template variables: `$CAT_IMG$`, `$CAT_IMG_URL$`, `$CAT_IMG_SMALL$`, `$CAT_IMG_SMALL_URL$`. `category_update` with a new image replaces the old one — approval first.

## Structure rules

- Order with `position` (lower first). Keep existing positions unless the user asked to reorder.
- Names: 1–4 words, distinct, no duplicates in one module; sentence case («Мировые инновации», not «Мировые Инновации»).
- No empty categories in public view: create the category and its materials back-to-back; for a large batch, create it with `access2category="3,4"` first and open it (`category_update` with all group ids) when filled.
- `category_delete` needs `confirm: true`; first read `entry_count` and agree what happens to its materials.

## Entry filters

- Entries carry `filter1..5`; the filter actions take `filter_num` 1–3. Board uses filter 3 as ad type; stuff uses filter 1 as platform.
- `filter_list` shows names and value ids. `filter_add` / `filter_update` with `items: "A|B|C"`.
- An entry stores value **ids**: send `filter1: "2"` etc.
- Ids are kept by value name; a new value gets the next id; display order = id order (verified on board). Renaming a value may renumber — show the mapping before changing a list in use.

## Tags

- On `add`/`update`: `tags: "tag1,tag2"`; or the separate `tags` action to replace them.
- Not returned by `add` or `get` — verify on the public page (`$TAGS$` in the template).
- 3–7 tags per material, lower case, reuse existing tags instead of near-duplicates.
