# Standard captions (signs)

Standard captions are the fixed texts that modules show to visitors and editors: module names, labels of the add/edit form fields (including «Дополнительное поле N» for `other1..5`), list titles («Последние поступления», «Архив материалов»), buttons («Найти»), «Читать дальше», date and time words. In the Control Panel they live on the page «Замена стандартных надписей».

## Tools

| Tool / action | Purpose | Parameters |
|---|---|---|
| `signs_read signs_groups` | Groups: `code`, `module`, `active`, title, section titles, `signs_count`, `customized_count` | — |
| `signs_read signs_list` | Captions grouped as in the Control Panel: `id`, `value` (current text), `default` (default text in the site language), `customized` | optional `group` (comma-separated codes), `ids` (≤1000), `customized: true` (only changed ones), `offset` (replies over 30 KB continue from `next`) |
| `signs_write signs_update` | Set texts by id, live on the site at once | `signs`: `{"<id>": "<text>"}`; `""`, `null` or `0` restores the default of that caption |
| `signs_delete signs_reset` | Restore defaults by `ids` and/or `group` (their union), or every caption with `all: true` alone | `confirm: true` when the server asks for it; own texts are lost |

Group codes: `modules` (module names), `users`, `online`, `news`, `publ`, `photo`, `blog`, `load`, `dir`, `board`, `stuff`, `shop`, `video`, `faq`, `other` (misc), `adviser` (tell a friend), `datetime`, `tg_shop`. Groups of inactive modules are listed too (`active: false`), so captions can be prepared before a module is switched on.

## The agent proposes, the user decides

By default captions and fields stay as they are: a news post, an article or a gallery uses the stock wording and the fields already switched on. Any action with fields — renaming a label, switching a field on, making it required — is a **proposal**: the agent explains why it is needed and writes only after the user's yes. A request that already names the change («переименуй поле», «добавь поле "Цена"») is the yes for that change only.

When to propose:

| Signal in the task | What to propose |
|---|---|
| The content has data that has no field of its own (photo source, price, address, dates of an event, cooking time) | Use an extra field `other1..5`: which one, the label («Дополнительное поле N» → «Источник фото»), whether to switch it on, whether the template must print it |
| The module holds something other than "materials" (recipes in publ, a price list in board, a knowledge base in FAQ, projects in a portfolio) | Rename the labels of standard fields (`title`, `description`, `message`, `link2source`, `cover`, `category`…) and the module's list titles: «Название материала» → «Название рецепта», «Последние поступления» → «Новые рецепты» |
| A label already on the site contradicts the content | Rename that label |
| Nothing of the above | Nothing: don't propose renames for their own sake |

How to propose — one line per change in the plan, then ask:

| Field | Caption id | Now | Proposed | Why | Also needed |
|---|---|---|---|---|---|
| `other1` | 226 | Дополнительное поле 1 | Источник фото | every news post credits a photo author | switch the field on (`fields`), print `$OTHER1$` in the entry template |

If the user says no, publish with the stock captions and fields (put the data into the text if it matters). If the user can't be asked, don't rename or switch fields — list the proposal in the report.

Rules for every rename:

- Rename the whole set that describes one thing together (field labels of the form + list titles of the same module), in one `signs_update` call, so the site does not mix «материал» and «рецепт».
- Keep the meaning and the role: a title stays a title, a lead stays a lead. Short labels (2–4 words), no keywords for SEO, no marketing slogans, no HTML.
- Only the groups of the modules in scope.

Never as part of content work: `datetime`, `users`, `online`, `shop` / `tg_shop` captions, whole groups "just in case", `signs_reset` with `all: true`.

## Field labels: read the module fields first

```text
1. content_read fields module=<m>
     → codes, checked, required, name (current label)
2. signs_read signs_list group=<m>
     → section «Форма добавления модуля "…"»: find the caption of each field
       by its default text:
         other1..5    «Дополнительное поле 1..5»
         title        «Название материала»
         description  «Краткое описание»
         message      «Полный текст материала»
         cover        «Обложка материала»
         category     «Категория»
         link2source  «Ссылка на источник материала» (publ)
3. plan: id, field, current → new text, why
4. signs_write signs_update signs={"<id>": "<label>", …}      (one call, atomic)
5. content_read fields module=<m>   → re-read the names
```

- Map by the `default` text inside the module's form section, never by id arithmetic: ids differ per module (news `title` = 223, `other1..5` = 226–230, further fields 503–532; publ `title` = 150, extra fields 745–774) and a site may differ again. Module-specific fields (author, e-mail, price, file) have their own captions in the same section — read them, don't guess.
- `fields` reflects some captions (`title`, `otherN` verified) but not all (`description` did not follow its caption). A label that `fields` doesn't show is still the label of the add/edit form; it can't be checked through MCP — say so in the report.
- A label is text, not a switch. Turning a field on or making it required is a module `fields` setting — follow the defect procedure in `SETTINGS_AND_MODULES.md` (blog shift, news `fname`, gb `required`).
- Values in `other1..5` render only where the template prints `$OTHER1$`…; the label itself is not shown on public entry pages. If the value must be visible with its label, the template prints the label text next to the variable — that is a template edit (`TEMPLATES.md`).
- Name a field the same way in the plan, the caption and the template so editors, the form and the page agree.

## Verified behaviour (live site, 2026-11)

- A change is live immediately. Caption 252 (news «Архив материалов») changed the `<title>` and the breadcrumb of `/news/`; caption 327 («Найти») changed the search form button. Reverting restored both.
- `content_read fields` and `settings_get` `fields[].name` take the names of `title` and `otherN` from the captions (news 223 → `title`, 226 → `other1`). Not every field name comes from a caption (the news `description` name did not follow caption 224) — always re-read `fields` after a write.
- The `modules` group (e.g. 109 «Каталог статей») did **not** change the module's page title, breadcrumb or menu: those show the module name from settings (`settings_get <module>` → `name`, changed with `settings_update` `name`) and the menu item text (`menu_update_items`). Use those for a visible module name; change the `modules` caption only on request.
- Saving is all or nothing: an unknown id or a text over 150 characters (spaces included) fails the whole call, nothing is saved.
- `signs_update` with `""` restores the default without confirmation. `signs_reset` may require `confirm: true` (site owner setting) and loses own texts.
- Texts are output as is (HTML allowed, not escaped). Write plain text; keep entities that the default already has (`Читать дальше &raquo;`). No scripts, no links.
- `datetime` defaults may show in English even on a Russian site — do not "fix" them as part of content work.
- Strings marked `<!--<s5208>-->…<!--</s>-->` in page HTML are template language strings, not captions: they are not in `signs_list`. Those are template work, not this tool.

## Safety and rollback

- Before writing, keep `value` and `customized` of every caption you change (from `signs_list ids=…`).
- Rollback of your own change: `signs_update` with the old `value` if it was customized, `""` if it was the default. Use `signs_reset` only when the user asks for defaults.
- Captions are site-wide: one change affects every page and form that uses it. List them in the plan like settings.

## Report

| Caption id | Group / section | Field or place | Before → after | Where visible |
|---|---|---|---|---|
