# Style: listicle / selection (подборка, топ, идеи)

**Use for:** «10 идей…», «7 мест…», «лучшие … для …», checklists, collections of links/resources. Module: publ or blog.

**Reader's goal:** compare options quickly and pick one.

## Structure

1. **Intro** (1–3 sentences): what's in the list, how items were chosen (criteria), for whom.
2. **Items**: `<h2>` per item (number + name), then the same mini-structure for every item: what it is → why it's here → one concrete detail (price/place/time/condition) → link if any.
3. **Comparison table** (optional, 5+ items with comparable parameters).
4. **How to choose** (optional): 2–4 sentences or a short list by situation.

Order items by a stated logic (by price, by distance, by popularity, by difficulty) — say which.

## HTML skeleton

```html
<p>Что в подборке, по какому принципу отбирали, для кого.</p>
<h2>1. Название</h2>
<figure><a href="$IMG_URL1$" class="ulightbox"><img src="$IMG_URL1$" alt="…" class="ulightbox" loading="lazy"></a></figure>
<p>Что это и почему в списке. Конкретная деталь: цена, адрес, время, условие.</p>
<p><a href="…">Подробнее</a></p>
<h2>2. Название</h2>
<p>…</p>
<h2>Сравнение</h2>
<table><thead><tr><th>Вариант</th><th>Цена</th><th>Кому</th></tr></thead><tbody><tr><td>…</td><td>…</td><td>…</td></tr></tbody></table>
<h2>Как выбрать</h2>
<ul><li>Если …, берите …</li></ul>
```

## Title, lead, meta

- **Title:** number + noun + qualifier: «12 идей для дня рождения ребёнка дома». The number = the real count of items.
- **Lead:** what kind of options and the selection principle.
- **meta_title:** «N … — подборка 2026» only if the year matters (≤60).
- **meta_description:** count + criterion + 1–2 example items (140–160).
- **Tags:** the topic, the use case.

## Images

One image per item if items are visual (places, dishes, products); otherwise a cover only. The slot number follows item order (item 3 → `$IMG_URL3$`) to keep bulk uploads simple. With fewer images than items (the user's number or `images.max_count`), give them to the most visual items and number the slots in the order those items appear.

## Voice

Friendly blog or editorial. Items described in parallel structure but not identical sentences.

## Avoid (listicle-specific)

- Padding the count with near-duplicates.
- Every item starting the same way («Это отличный вариант для…»).
- Bold label + colon on every line inside items.
- A conclusion that re-lists the items.
- Prices, addresses, schedules not provided by the user — omit or say where to check.

## Checklist

- [ ] The number in the title equals the number of items.
- [ ] The selection principle is stated and the order follows it.
- [ ] Every item has one concrete, verifiable detail.
- [ ] Items don't repeat each other.
- [ ] Images match item order.

## Brief template

```text
Тема подборки:
Сколько пунктов:
Принцип отбора и порядок:
Пункты (название + что известно о каждом):
Для кого:
Параметры для сравнения (если таблица):
Изображения: по одному на пункт / только обложка:
Голос: блог / редакционный
```
