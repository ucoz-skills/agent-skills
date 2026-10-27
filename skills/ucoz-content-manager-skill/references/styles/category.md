# Style: category / section / album description (описание категории)

**Use for:** `description`, `meta_title`, `meta_description` of a category, section or photo album in any content module.

**Reader's goal:** understand what's in this section and whether to go in. Search engines use it to understand the listing page.

## What to write

| Field | Length | Content |
|---|---|---|
| `name` | 1–4 words | What's inside, sentence case: «Обзоры гаджетов», «Школьные новости», «Фестиваль еды 2026» |
| `description` | 300–700 characters | What's collected here + for whom + how it differs from neighbouring categories + what's typical inside (formats, topics) |
| `meta_title` | 50–60 | «Name: 2–3 key topics» or «Name — what the reader gets» |
| `meta_description` | 140–160 | What the section covers + a reason to open it |

Format: match the siblings. Plain text if they're plain; simple `<p>`/`<strong>`/`<a>` are rendered (verified), use them only if the module's other categories do.

## Formula for `description`

```text
[Что здесь] + [для кого / в какой ситуации] + [чем отличается или что типично внутри] + [как часто обновляется или с чего начать — если известно]
```

Example: «Обзоры смартфонов, наушников и умных часов: тестируем неделю и больше, сравниваем с ближайшими конкурентами и честно пишем о минусах. Подойдёт, если выбираете технику до 50 тысяч рублей. Начните с подборки „Лучшие наушники года“».

## Album (photo module)

`description` = what event/place, when, who took the photos (if known), how many photos. Photo captions: what's in the frame, people only by name if the user gave names and consent. Turn on `categories_show_category_description` for photo if the description should show (off by default).

## Avoid (category-specific)

- The same template text in every category with only the name changed.
- «В этом разделе вы найдёте много интересного и полезного».
- Keyword lists in `description` or `meta_keywords` stuffing.
- Promises («здесь будут…», «скоро появятся»). Describing the scope of the section is fine before the first material — create the materials right after.

## Checklist

- [ ] The description says what's inside in the first sentence.
- [ ] It differs from every sibling's description.
- [ ] meta_title and meta_description are filled and differ from `name`/`description` word for word.
- [ ] Format matches the siblings.
- [ ] Access arrays copied correctly (CATEGORIES_AND_ACCESS.md).

## Brief template

```text
Модуль и соседние категории:
Название:
Что будет внутри (темы, форматы):
Для кого:
Чем отличается от соседних:
Изображение категории:
```
