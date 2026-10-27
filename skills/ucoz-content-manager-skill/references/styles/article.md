# Style: article / explainer / long read (статья, разбор, лонгрид)

**Use for:** explaining a topic, analysis, an expert opinion, a history, "what is X and why it matters". Module: publ or blog.

**Reader's goal:** understand. They read more than they scan, but still need a map: headings that form a logical path.

## Structure

1. **Thesis** (first paragraph): the main answer or claim of the article in 2–3 sentences. No warm-up.
2. **Body sections** (3–7 `<h2>`): one sub-question each, in a deliberate order — problem → causes → options → recommendation, or simple → complex, or chronological. Hold one logic.
3. **Evidence inside sections**: examples, numbers, comparisons, quotes with sources — what the user/site provided.
4. **Practical part** (if relevant): what to do with this knowledge — a short list or a table.
5. **Ending**: the last concrete point, a recommendation, or links to related materials. Not a summary of what was just said.

Optional: a table of contents for >10 000 characters (a `<ul>` of anchor links is fine if the site uses anchors; otherwise skip).

## HTML skeleton

```html
<p>Тезис: главный ответ статьи в двух-трёх предложениях.</p>
<h2>Подвопрос 1</h2>
<p>…</p>
<figure><a href="$IMG_URL1$" class="ulightbox"><img src="$IMG_URL1$" alt="…" class="ulightbox" loading="lazy"></a><figcaption>…</figcaption></figure>
<h2>Подвопрос 2</h2>
<p>…</p>
<table><thead><tr><th>…</th><th>…</th></tr></thead><tbody><tr><td>…</td><td>…</td></tr></tbody></table>
<h2>Подвопрос 3</h2>
<p>…</p>
<blockquote><p>«Цитата», — Имя Фамилия, кто это (источник).</p></blockquote>
<h2>Что с этим делать</h2>
<ul><li>…</li></ul>
<p>Читайте также: <a href="/publ/…">…</a>.</p>
```

## Title, lead, meta

- **Title:** the topic + the angle, 50–80 characters: «Почему дорожают ноутбуки и когда ждать снижения цен». Avoid «Всё, что нужно знать о…», «Полное руководство».
- **Lead (`description`):** the thesis compressed to one sentence + who it's for.
- **meta_title:** main phrase first (≤60).
- **meta_description:** the answer + what's inside (140–160).
- **Tags:** the topic, 2–3 subtopics.

## Images

Cover that illustrates the topic concretely. 1 image per 2 000–3 000 characters where it explains something (a diagram, a comparison, an example) — not decoration. Charts/diagrams as PNG with a caption that states what they show.

## Voice

Expert or editorial. Claims with reasons; uncertainty stated plainly where it exists («данных пока нет»), not as hedge stacks.

## Avoid (article-specific)

- «В современном мире», «В наше время», «Ни для кого не секрет» as openings.
- Every section ending with a one-line moral; a final «Таким образом, …» that repeats the intro.
- Triads everywhere («быстро, удобно и надёжно»), «не просто X, а Y» contrasts — HUMANIZER.md.
- «Эксперты считают» without naming who.
- Sections that could be swapped without loss — the order has no logic.

## Checklist

- [ ] The thesis is in the first paragraph.
- [ ] Reading only the `<h2>`s gives the argument.
- [ ] Every claim has an example, a number or a reason.
- [ ] Each paragraph holds one idea; no repeated ideas across sections.
- [ ] The ending adds something (action, link, the last fact).

## Brief template

```text
Тема и угол (на какой вопрос отвечаем):
Тезис (если у пользователя есть позиция):
Читатель и его уровень:
Факты, цифры, источники от пользователя:
Подвопросы/разделы (если заданы):
Что читатель должен сделать после:
Объём:
Изображения/схемы:
Связанные материалы:
Голос: экспертный / редакционный / блог
```
