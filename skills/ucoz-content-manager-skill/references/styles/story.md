# Style: story, case, interview (история, кейс, интервью)

**Use for:** a client case, a project report, a personal story, a trip report, an interview, a "how we did it". Module: blog or publ.

**Reader's goal:** see a real situation and take something from it — an approach, a result, a warning.

## Structure — case / story

1. **Result up front** (1–2 sentences): what was achieved / what happened, with the key number if there is one.
2. **Situation**: who, what was the problem or starting point.
3. **What was done**: steps or episodes in order, with decisions and why.
4. **What went wrong**: honest difficulties — this is what makes it credible.
5. **Result**: numbers, before/after, quotes of the participants (only provided ones).
6. **Takeaways**: 2–4 concrete lessons, not slogans.

## Structure — interview

1. **Intro**: who the person is (name, role, why they matter for this site) and the topic — 2–4 sentences.
2. **Q&A**: questions in bold, answers as given. Edit answers only for clarity, never for meaning; keep the speaker's voice.
3. **Ending**: the last answer, or one line with where to find the person/project.

## HTML skeleton (case)

```html
<p>Результат: что получилось, ключевая цифра.</p>
<h2>С чего начинали</h2>
<p>…</p>
<h2>Что сделали</h2>
<p>…</p>
<figure><a href="$IMG_URL1$" class="ulightbox"><img src="$IMG_URL1$" alt="…" class="ulightbox" loading="lazy"></a><figcaption>Было / стало: …</figcaption></figure>
<h2>Что пошло не так</h2>
<p>…</p>
<h2>Итог</h2>
<p>…</p>
<blockquote><p>«…», — Имя, роль.</p></blockquote>
<h2>Что из этого взять</h2>
<ul><li>…</li></ul>
```

## HTML skeleton (interview)

```html
<p>Кто герой и о чём разговор.</p>
<p><strong>Вопрос?</strong></p>
<p>Ответ.</p>
<p><strong>Вопрос?</strong></p>
<p>Ответ.</p>
```

## Title, lead, meta

- **Case title:** result + how: «Как кафе на 20 мест удвоило доставку за три месяца».
- **Interview title:** the strongest quote or the topic + name: «Анна Петрова: „Мы перестали делать скидки — и выручка выросла“».
- **meta_description:** who, what changed, one number (140–160).
- **Tags:** industry, method, person/project.

## Images

Before/after, process photos, the people (with consent — the user's photos only). Generated images: only illustrations, never portraits of real named people.

## Voice

First person for own stories («мы», «я»), editorial for third-party cases. Keep the hero's words and specific details — they are what makes it human.

## Avoid (story-specific)

- Invented numbers, quotes, names, timelines — the whole value is that it's real.
- Polished success-only narrative: «всё прошло идеально».
- Moral at the end of each section; «Эта история показывает, что…».
- Rewriting an interviewee's answers into the site's voice.

## Checklist

- [ ] The result is in the first paragraph.
- [ ] Every number and quote is from the user/interview.
- [ ] There's at least one real difficulty.
- [ ] Takeaways are actions, not slogans.
- [ ] Consent for photos/names is assumed only if the user said so.

## Brief template

```text
Тип: кейс / история / интервью
Герой (кто, роль) и согласие на публикацию:
Исходная ситуация:
Что сделали (по шагам):
Трудности:
Результат и цифры:
Цитаты (дословно):
Выводы автора:
Фото:
Голос: от первого лица / редакционный
```
