# Style: announcement (анонс события, акции, набора)

**Use for:** an upcoming event, a promo, a sign-up, a schedule change, a competition. Module: news or blog; for schools/institutions often news.

**Reader's goal:** decide whether to come/take part and know exactly how.

## Structure

1. **What and when** (first sentence): the event, the date and time, the place.
2. **Key facts box**: when, where, cost/entry, who can take part, how to register — as a list.
3. **What will happen**: programme or the main points (list or short paragraphs).
4. **How to take part**: steps, deadline, contacts, link to a form.
5. **Extras** (if known): what to bring, transport, age limits, accessibility.

## HTML skeleton

```html
<p>Что, когда и где — одним предложением.</p>
<ul>
  <li><strong>Когда:</strong> 12 мая 2026, 11:00–18:00</li>
  <li><strong>Где:</strong> адрес / онлайн-площадка</li>
  <li><strong>Участие:</strong> бесплатно / цена / условия</li>
  <li><strong>Для кого:</strong> …</li>
  <li><strong>Регистрация:</strong> до 10 мая, <a href="…">форма</a></li>
</ul>
<h2>Программа</h2>
<ul><li>11:00 — …</li><li>13:00 — …</li></ul>
<figure><a href="$IMG_URL1$" class="ulightbox"><img src="$IMG_URL1$" alt="…" class="ulightbox" loading="lazy"></a><figcaption>…</figcaption></figure>
<h2>Как принять участие</h2>
<ol><li>…</li><li>…</li></ol>
<p>Вопросы: телефон / почта / <a href="…">страница</a>.</p>
```

Times in a programme use the dash between time and item (`11:00 — …`) and an en dash in ranges (`11:00–18:00`).

Omit every line of the key-facts box you have no fact for — never assume «бесплатно» or a place. In the news module an announcement under ~1 500 characters has no `<h2>`: programme and steps go as plain lists after a sentence («Программа:», «Как записаться:»); use `<h2>` only in longer announcements.

**Sanity checks before publishing:** compute the weekday of every date and flag unusual ones (a school event on Sunday, a date in the past); flag contacts that look like placeholders (`000-00-00`, `example.com`, `+7 (999) 999-99-99`); flag a place that can't be found (no address and not a known institution; «школа № 12» on a local site is fine). Report them to the user. If the user gave the date and asked to publish, publish and mention the weekday in the report — weekend events are normal; hold as `pending` only for a date in the past or a placeholder contact you can't check.

## Title, lead, meta

- **Title:** event + date (+ place): «Фестиваль уличной еды пройдёт 12 мая в парке Победы».
- **Lead:** the first sentence.
- **meta_title:** event + date (≤60).
- **meta_description:** date, place, entry, one programme highlight (140–160).
- **Tags:** event type, place, organiser («№» is stripped from tags: «школа № 5» → «школа 5»).
- After the event, offer to update the text («Фестиваль прошёл…») or publish a recap news.

## Images

Cover: the poster or a picture of the place/previous event (user-provided), or an illustration labelled as such. Poster with text: put all its information in the text too (text in images isn't readable for search engines or screen readers).

## Voice

Inviting but factual. Official preset for institutions: the institution's name as the user wrote it (expand «МБОУ» once only if the user or the site does so), documents and the responsible person only if given. One call to action, built from the facts given (date, phone, form).

## Avoid (announcement-specific)

- «Не пропустите!», «Это событие нельзя пропустить!», «Вас ждёт незабываемая атмосфера».
- Missing practical data (date, time, place) — ask; if you can't, publish as `pending` and list what's missing. Missing price or programme — just omit those lines.
- Relative dates («в эту субботу») — always the date.
- Several CTAs with different links.

## Checklist

- [ ] Date, time, place, entry conditions are all present and exact.
- [ ] Registration deadline and method are clear.
- [ ] The poster's text is duplicated in the material.
- [ ] One call to action with a working link or contact.

## Brief template

```text
Событие (название, формат):
Дата, время, место (адрес/ссылка):
Стоимость/условия участия:
Для кого, ограничения:
Программа:
Как зарегистрироваться, до какого числа:
Организатор и контакты:
Афиша/фото:
Голос: приглашающий / официальный
```
