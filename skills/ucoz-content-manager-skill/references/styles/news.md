# Style: news (новость)

**Use for:** something happened or is decided: an event took place, a rule changed, results are in, a service launched. Module: news (or blog with a «Новости» category).

**Reader's goal:** learn what happened, to whom it matters, what changes for them — in 20 seconds.

## Structure — inverted pyramid

1. **Lead** (1–2 sentences): who / what / when / where. The single most important fact first.
2. **Details**: how, why, numbers, conditions.
3. **Quote or source** (only if the user provided it): who said / where it's published.
4. **Context**: what preceded it, link to the previous news on the topic.
5. **What next**: dates, how to take part, where to follow — only real ones.

No headings in news under ~1 500 characters; one or two `<h2>` in longer ones.

## HTML skeleton

```html
<p>LEAD: факт — кто, что, когда, где.</p>
<p>Подробности: как и почему, цифры, условия.</p>
<figure><a href="$IMG_URL1$" class="ulightbox"><img src="$IMG_URL1$" alt="…" class="ulightbox" loading="lazy"></a><figcaption>…</figcaption></figure>
<p>Цитата или источник: «…», — сказал(а) Имя Фамилия, должность. / Сообщение опубликовано на <a href="…" target="_blank" rel="nofollow noopener">сайте …</a>.</p>
<p>Контекст: <a href="/news/…">ранее …</a>.</p>
<p>Что дальше: дата, действие, ссылка.</p>
```

## Title, lead, meta

- **Title:** subject + verb + the fact, 50–80 characters, past or present tense: «В школе № 5 открыли кабинет робототехники». No questions, no «Новость:», no exclamation marks.
- **`description` (lead in lists):** the lead sentence itself, slightly shortened.
- **meta_title:** built on the title but not a copy of it — add the place, the date or a clarifier; ~60 characters.
- **meta_description:** the fact + one detail, 140–160 characters.
- **Tags:** the topic, the place, the organisation (2–5).

## Images

Cover: one image that shows the subject (event, place, object). Text: 0–2 images; for photo reports use the photo module and link the album. Generated images in news are illustrations — caption «Иллюстрация», never a pretend photo of the real event.

## Voice

Neutral editorial or official. Third person. No evaluation words («замечательный», «долгожданный») unless quoted.

## Avoid (news-specific)

- Opening with background («В последнее время всё больше…») — start with the fact.
- «Стоит отметить», «Важно подчеркнуть», «Данное событие стало важным шагом» — HUMANIZER.md.
- Inflated significance and send-offs: «Это событие войдёт в историю», «Впереди много интересного».
- Unattributed quotes, invented numbers, «по данным экспертов».
- Several news in a series with the same `add_date` (PLAYBOOK §2a).

## Checklist

- [ ] The first sentence answers «что случилось?».
- [ ] Every number, date, name came from the user or a source.
- [ ] Dates are absolute («12 мая»), not «вчера».
- [ ] One message per news item.
- [ ] Ends on a concrete next step or the last fact.

## Brief template

```text
Модуль/категория:
Что произошло (1 фраза):
Кто участвовал / кого касается:
Когда и где:
Цифры и детали:
Цитата (кто, должность, текст) — если есть:
Источник/ссылка — если есть:
Что дальше (даты, как участвовать):
Изображения: обложка (что на ней), в тексте (сколько, что):
Голос: редакционный / официальный
```
