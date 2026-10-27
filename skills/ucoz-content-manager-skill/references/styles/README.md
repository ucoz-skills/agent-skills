# Material styles — how to write and lay out each kind of material

One file per kind of material. Each gives: when to use it, the reader's goal, a skeleton, a ready HTML skeleton for `message`, formulas for title / lead / meta, image plan, voice, kind-specific tells to avoid, a checklist and a brief template to fill from the user's request.

Write in the site's language. The skeletons are Russian because most uCoz sites are; translate the labels for other languages.

## Pick the style

| The user wants… | Style file | Usual module |
|---|---|---|
| Report something that happened or was decided | `news.md` | news, blog |
| Teach how to do something step by step | `guide.md` | publ, blog |
| Explain a topic in depth, analysis, opinion | `article.md` | publ, blog |
| Evaluate a product, service, place, book | `review.md` | publ, blog |
| A selection, top-N, ideas, checklist | `listicle.md` | publ, blog |
| Invite to an event, promo, schedule | `announcement.md` | news, blog |
| Tell a case, a story, an interview | `story.md` | blog, publ |
| Answer questions | `faq.md` | faq, publ, pages |
| Describe a category / section / album | `category.md` | any module's categories |
| Sell / give / look for something | `board-ad.md` | board |
| A static page: about, contacts, services, rules | `page.md` | site pages |
| Describe a downloadable file / program | `file.md` | load |

Tie-breaker news vs announcement: something in the future that the reader can attend or join → `announcement.md`; a past fact or a decision → `news.md`.

If the request mixes kinds (a review inside a listicle), take the skeleton of the main kind and borrow blocks from the other. Short texts (under ~1 500 characters, e.g. a cleaned demo article) keep the style's order of content but need no `<h2>`.

## The writing pipeline (every material)

1. **Brief.** Fill the brief template of the style from the user's words + site context (module, category, existing materials' tone). Missing facts → ask, or mark them (SKILL.md, "Facts the user didn't give"). Blocks of the skeleton that need specifics you don't have (prices, dates, names, local details) are omitted, never invented; well-established general knowledge of the topic is fine for evergreen guides, listicles and explainers. Checklist items that need facts you don't have are skipped and named in the report.
2. **Draft in the HTML skeleton** of the style, in the chosen voice, with images placed as `$IMG_URLn$` figures (MEDIA_WORKFLOW.md §2).
3. **Editor pass** (clarity): cut lead-ins and the obvious, one idea per paragraph, no repeated ideas, main point first, split sentences over ~30 words, every promise in the intro or a heading delivered.
4. **Humanizer pass** (AI tells): `../HUMANIZER.md` (for new copy start with its "New copy" path). Never change facts, links, HTML structure or image codes.
5. **SEO fields.** Title, lead, meta title, meta description, tags (`ownurl` only if a custom URL is wanted) — formulas in the style file, limits in COPY_GUIDE.md. If the user fixed the title, it may appear inside the meta title formula; the four texts must still differ as a whole.
6. **Sanity checks.** Weekdays of dates, placeholder-looking contacts, claims vs the object (a file that isn't attached, «5 шагов» with four).
7. **Proofread.** Spelling, agreement, «ёлочки», dashes in Russian (`—` with spaces between words, `–` in ranges, hyphen in compounds); keep the site's own convention (`&ndash;`, `&nbsp;`) in edited texts; «№ 5» with a space.

**Voice priority:** the user's instruction for this material («официально») → the site's preset (existing real materials) → the style's default. If existing materials are install demo or tests, pick the preset by site type.

## Voice presets

Choose one per site (look at existing materials) and keep it across materials.

| Preset | For | Signs |
|---|---|---|
| Редакционный нейтральный | news sites, city portals, catalogs | third person, facts first, no «мы» opinions, short sentences |
| Дружелюбный блог | personal and hobby blogs, clubs | «я/мы», direct address «вы», examples from experience, light humour where the author would |
| Экспертный | professional sites, services | precise terms explained once, numbers and conditions, no hype, conclusions with reasons |
| Официальный (учреждение) | schools, kindergartens, libraries, municipal sites | full names of the institution and documents, dates, responsible persons, no slang, no exclamation marks |
| Продающий честный | shops, services, studios | benefit in concrete terms, price/conditions when known, one call to action, no superlatives without proof |

Voice never overrides facts: the presets change wording, not content.

## HTML components (safe for uCoz materials)

Use semantic tags; no `<h1>` (the template has it), no inline `style` unless the site already uses it (exception: minimal spacing on components that look broken in the template — see «Collapsible answer»), no scripts. No `<p>` inside `<li>`: stock 2301 then puts the number on its own line (verified). Verified on a blog entry: `<h2>/<h3>`, `<ol>/<ul>`, `<blockquote>`, `<table><thead><tbody>`, `<details><summary>`, `<figure>`, `tel:`/`mailto:` links and `rel="nofollow noopener"` all reach the public page unchanged (`message_type: html`).

**Lead paragraph** (first paragraph, the answer/essence):
```html
<p><strong>Коротко:</strong> …</p>
```
Use the bold label only in guides/explainers; in news the first paragraph is plain.

**Section:** `<h2>…</h2>` then paragraphs; `<h3>` only inside a long section.

**Image with zoom and caption** (slot n of `image_upload_ids`):
```html
<figure><a href="$IMG_URL1$" class="ulightbox"><img src="$IMG_URL1$" alt="…" class="ulightbox" loading="lazy"></a><figcaption>…</figcaption></figure>
```

**Steps:**
```html
<ol>
  <li><strong>Откройте настройки.</strong> …</li>
  <li><strong>…</strong> …</li>
</ol>
```

**Note / warning** (renders on any template):
```html
<blockquote><p><strong>Важно.</strong> …</p></blockquote>
```

**Comparison / specs:**
```html
<table>
  <thead><tr><th>Параметр</th><th>Модель A</th><th>Модель B</th></tr></thead>
  <tbody><tr><td>Вес</td><td>1,2 кг</td><td>1,5 кг</td></tr></tbody>
</table>
```

**Pros and cons:**
```html
<h3>Плюсы</h3><ul><li>…</li></ul>
<h3>Минусы</h3><ul><li>…</li></ul>
```

**Collapsible answer** (FAQ inside an article):
```html
<details style="margin:0 0 12px"><summary style="cursor:pointer;font-weight:600">Вопрос?</summary><p>Ответ.</p></details>
```

**Key facts box** (events, announcements):
```html
<ul>
  <li><strong>Когда:</strong> 12 мая 2026, 11:00–18:00</li>
  <li><strong>Где:</strong> …</li>
  <li><strong>Вход:</strong> …</li>
</ul>
```
Bold labels are fine here and in the guide's «Коротко:» because they carry data or structure; a list where every item repeats its own label as prose is the AI tell (HUMANIZER.md E1).

**Internal link:** `<a href="/publ/…">descriptive anchor</a>` — never «здесь», «по ссылке».

**External link:** `<a href="https://…" target="_blank" rel="nofollow noopener">source name</a>`.

## Shared rules for all styles

- First screen = the essence. A reader who stops after the first paragraph knows the main thing.
- One idea per paragraph; paragraphs of 1–4 sentences; vary sentence length.
- Headings name what the section holds (no puns, no questions in a row, no emojis, sentence case).
- Numbers as digits, units with a space (`5 км`, `20 %` or `20%` as the site does), dates «12 мая 2026».
- No closing «Надеемся, статья была полезной», no «В заключение хочется сказать».
- Every material ends on a concrete thing: the last fact, the next step, a link, a date.
