# Style: review (обзор)

**Use for:** evaluating one thing: a product, service, app, book, film, place, course. Module: publ or blog.

**Reader's goal:** decide — buy / go / use or not, and is it for them.

## Structure

1. **Verdict first** (2–3 sentences): what it is, for whom it's good, the main plus and the main minus.
2. **Key facts**: price, specs, conditions — a short list or table (only known values).
3. **How it was tested / experienced**: period, conditions, what was compared (only what really happened — from the user).
4. **Criteria sections** (`<h2>` each): the 3–6 things that matter for this kind of item (for headphones: sound, fit, battery, microphone, app).
5. **Pros and cons**: two short lists.
6. **Who should / shouldn't**: concrete reader types.
7. **Alternatives** (optional): 1–3 with one-line differences.

## HTML skeleton

```html
<p><strong>Вердикт:</strong> что это, кому подойдёт, главный плюс и главный минус.</p>
<ul>
  <li><strong>Цена:</strong> …</li>
  <li><strong>Где купить / как получить:</strong> …</li>
  <li><strong>Ключевые характеристики:</strong> …</li>
</ul>
<h2>Как мы пользовались</h2>
<p>Срок, условия, с чем сравнивали.</p>
<h2>Критерий 1</h2>
<p>…</p>
<figure><a href="$IMG_URL1$" class="ulightbox"><img src="$IMG_URL1$" alt="…" class="ulightbox" loading="lazy"></a><figcaption>…</figcaption></figure>
<h2>Критерий 2</h2>
<p>…</p>
<h2>Плюсы и минусы</h2>
<h3>Плюсы</h3><ul><li>…</li></ul>
<h3>Минусы</h3><ul><li>…</li></ul>
<h2>Кому подойдёт</h2>
<p>…</p>
```

## Title, lead, meta

- **Title:** «Обзор + name: verdict in 3–6 words»: «Обзор Xiaomi Redmi Buds 6: хороший звук, слабый микрофон».
- **Lead:** the verdict sentence.
- **meta_title:** «Обзор name — плюсы, минусы, кому подойдёт» (≤60).
- **meta_description:** verdict + 2 concrete specs/prices (140–160).
- **Tags:** the product type, the brand, the use case.

## Images

The item itself (user's photos are best; generated images only as clearly labelled illustrations, never as photos of the real product). In text: 1 image per criterion that benefits from it. Alt: what exactly is shown («кейс наушников открыт, видны индикаторы заряда»).

## Voice

Honest and specific. First person if the author tested it («за две недели батарея…»), editorial otherwise. Negatives stated as clearly as positives.

## Avoid (review-specific)

- A verdict at the end only — the reader may not get there.
- Sales language: «потрясающий», «идеальный выбор», «лучший на рынке».
- Specs, prices, test results that the user didn't give — leave «уточните на сайте производителя» or skip.
- Pros/cons that are the same point twice («лёгкий» / «небольшой вес»).
- «Подводя итоги, можно сказать…» — the verdict is already at the top.

## Checklist

- [ ] Verdict and target reader in the first paragraph.
- [ ] Every spec/price/test claim is sourced from the user or official data.
- [ ] At least one real minus.
- [ ] Criteria fit the item type.
- [ ] Images are of the real item or labelled as illustrations.

## Brief template

```text
Что обозреваем (точное название, версия):
Цена и где купить:
Характеристики (от пользователя/официальные):
Опыт использования (срок, условия, впечатления пользователя):
Главные плюсы/минусы по мнению пользователя:
Для кого:
Альтернативы:
Фото: свои / иллюстрации:
Голос: от первого лица / редакционный
```
