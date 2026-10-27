# Style: guide / how-to (инструкция)

**Use for:** "how to do X": set up, choose, fix, cook, prepare, apply. Module: publ or blog.

**Reader's goal:** get the result. They scan for the step they're on, so steps must be findable and doable.

## Structure

1. **Result first** (1–2 sentences): what the reader will have at the end and how long it takes.
2. **Before you start** (if needed): what you need — tools, accounts, ingredients, prerequisites, time, cost. Items that follow directly from the steps (a pump for «проверить давление») may be listed; mark them as inferred in the report.
3. **Steps**: numbered; one action per step; the expected result after tricky steps.
4. **Problems and fixes** — from the user's notes, the site, or the common, well-known mistakes of the topic; no invented cases, numbers or brands.
5. **What next** — only a real related material, check or maintenance step the user gave; otherwise end on the last step.

Lead vs first paragraph (they must differ): `description` = who it's for + scope («для тех, кто обслуживает велосипед сам: шины, цепь, тормоза»); the «Коротко:» paragraph = result + time («через час велосипед готов к сезону»).

Use `<h2>` for the blocks above; steps inside `<ol>`. Long guides (>10 steps) split steps into stages with `<h2>` per stage.

## HTML skeleton

```html
<p><strong>Коротко:</strong> что получится и сколько займёт.</p>
<h2>Что понадобится</h2>
<ul><li>…</li><li>…</li></ul>
<h2>Пошагово</h2>
<ol>
  <li><strong>Действие в повелительном наклонении.</strong> Уточнение: где нажать, что ввести.</li>
  <li><strong>…</strong> …
      <figure><a href="$IMG_URL1$" class="ulightbox"><img src="$IMG_URL1$" alt="Экран … с выделенной кнопкой …" class="ulightbox" loading="lazy"></a><figcaption>Шаг 2: …</figcaption></figure></li>
  <li><strong>…</strong> …<br>Должно получиться: …</li>
</ol>
<blockquote><p><strong>Важно.</strong> Предупреждение, которое реально спасает от ошибки.</p></blockquote>
<h2>Если что-то пошло не так</h2>
<p><strong>Ошибка «…».</strong> Причина и что сделать.</p>
<h2>Что дальше</h2>
<p>Ссылка на <a href="/publ/…">связанную инструкцию</a>.</p>
```

## Title, lead, meta

- **Title:** «Как + глагол + объект (+ условие)»: «Как перенести контакты с Android на iPhone без компьютера». Numbers if real: «… за 5 шагов».
- **Lead (`description`):** result + time + who it's for.
- **meta_title:** «Как … — пошаговая инструкция» (≤60).
- **meta_description:** what you'll do, how long, one key condition (140–160).
- **Tags:** the task, the tool/product, the platform.

## Images

One image per step where seeing helps: a non-obvious button (software) or a part, position, wear mark (physical tasks: bike, cooking, repair). None for obvious steps. Screens/diagrams: PNG; photos: JPG. Alt says what is shown and what to look at. Generated images are captioned «Иллюстрация: …». Cover: the result or the object, not a stock "person at laptop"; add one when the module shows covers, and say so in the report.

## Voice

Expert or friendly. Imperative mood for steps («Откройте», «Нажмите»), «вы» in explanations. Terms explained once on first use.

## Avoid (guide-specific)

- Steps that bundle 3 actions — split.
- Bold labels on every bullet in the "what you need" list (only the step titles are bold).
- «Давайте разберёмся», «Итак, приступим!», «Поздравляем, вы справились!».
- Warnings for everything — keep one or two that matter.
- Invented UI labels or menu paths: use exact labels from the user or the product; if unknown, describe the action without quoting a label.

## Checklist

- [ ] The result and time are in the first paragraph.
- [ ] Every step starts with a verb and is doable on its own.
- [ ] Tricky steps say what should happen.
- [ ] UI labels, versions, prices are exact or absent.
- [ ] Images sit inside the step they illustrate.

## Brief template

```text
Задача читателя (результат):
Для кого и какой уровень:
Что понадобится:
Шаги (как знает пользователь) / источник:
Типичные ошибки:
Платформа/версия (для программ) или модель/тип (для вещей):
Изображения: какие шаги показать:
Связанные материалы для ссылок:
Голос: экспертный / дружелюбный
```
