# Style: FAQ (вопросы и ответы)

**Use for:** answers to real questions of the site's audience: the FAQ module (one entry per question), an FAQ block inside an article, or an FAQ site page.

**Reader's goal:** find their question and get the answer in the first sentence.

## FAQ module (one entry = one question)

| Field | Content |
|---|---|
| `subject` | The question as a user would ask it, ending with «?». 30–100 characters |
| `question` | Optional clarification of the question (context, conditions) |
| `answer` + `message_type: html` | The answer: first sentence answers directly («Да, …», «Нет, …», «Через …», «В разделе …»), then details, steps or conditions, then a link |
| `category` | Group of questions by topic (оплата, доставка, учёба, регистрация) |

## HTML skeleton (answer)

```html
<p>Прямой ответ в первом предложении. Одно-два уточнения.</p>
<ol><li>Шаг, если нужно действие.</li><li>…</li></ol>
<p>Подробнее — в разделе <a href="/…">…</a>.</p>
```

## FAQ block inside an article or page

```html
<h2>Частые вопросы</h2>
<details><summary>Сколько стоит доставка?</summary><p>Бесплатно от 3000 ₽, иначе 300 ₽.</p></details>
<details><summary>…?</summary><p>…</p></details>
```

`<details>` keeps long FAQs compact. If FAQPage structured data is planned (TEMPLATES.md), the answers must be visible text on the page — `<details>` content counts as present in the HTML.

## Question selection

- Take questions from the user, comments, support emails, search queries — real ones.
- One question per entry; split «Как оплатить и когда придёт заказ?».
- Group by topic; order by frequency or by the user's journey (before purchase → payment → delivery → returns).

## Answer rules

- 300–1 500 characters; longer → a guide (guide.md) and link to it.
- Conditions and numbers exact; if they change often, say where the current value lives.
- No «Отличный вопрос!», «Спасибо за вопрос», «Надеемся, мы ответили».
- Same terms as the rest of the site (if the site says «личный кабинет», don't write «профиль»).

## Meta

FAQ entries usually share the module's SEO. For an FAQ page: meta_title «Вопросы и ответы — тема | Название», meta_description lists 2–3 covered topics.

## Checklist

- [ ] Each `subject` is a real question in the user's words.
- [ ] Each answer's first sentence answers it.
- [ ] Numbers, prices, deadlines are current and from the user.
- [ ] Links lead to the detailed pages.
- [ ] No duplicate questions across categories.

## Brief template

```text
Где: модуль FAQ / блок в статье / страница
Список вопросов (как спрашивают люди):
Ответы или источники ответов:
Группы/категории:
Ссылки на подробные материалы:
Голос: дружелюбный / официальный
```
