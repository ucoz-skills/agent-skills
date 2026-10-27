# Style: static site page (О нас, Контакты, Услуги, Правила, Доставка)

**Use for:** pages of the page editor (`templates_write page_add` / `page_update`, field `page_message`). Not for the home page landing (ucoz-landing-skill).

**Reader's goal:** find a specific fact (address, price, rule, how to order) fast, or decide to trust/contact the site.

## Page types

### О нас / О школе / О проекте
1. Who we are in one sentence (name, what we do, where, since when).
2. What we do / offer — 3–6 concrete points.
3. Facts that build trust: years, numbers, licences, team (only provided).
4. How to contact or what to do next.

### Контакты
1. Address (full), how to get there, map embed only if the user gives the code.
2. Phones, email, messengers — as links (`tel:`, `mailto:`).
3. Working hours as a list or table.
4. Requisites (for organisations) — as provided, exactly.

### Услуги / Цены
1. List of services, each: name → what's included → price/from-price → duration.
2. Table for comparable services.
3. How to order; conditions; what's not included.

### Правила / Политика / Условия
1. Who the rules apply to, date of the current edition.
2. Numbered sections and clauses (`<ol>`), exact wording from the user/lawyer — **don't rewrite legal text**, only lay it out.

### Доставка и оплата
1. Ways and prices as a table; free-delivery threshold.
2. Time frames; regions.
3. Payment methods; return conditions with a link to rules.

## HTML skeleton (generic)

```html
<p>Суть страницы одним-двумя предложениями.</p>
<h2>Раздел</h2>
<ul><li>…</li></ul>
<h2>Раздел</h2>
<table><thead><tr><th>…</th><th>…</th></tr></thead><tbody><tr><td>…</td><td>…</td></tr></tbody></table>
<p>Телефон: <a href="tel:+70000000000">+7 000 000-00-00</a>, почта: <a href="mailto:…">…</a></p>
```

## Meta

- `page_meta_title`: page type + name (+ city for local businesses): «Контакты — Студия „Луч“, Казань».
- `page_meta_description`: the facts a searcher wants (address + hours, or services + from-price).
- `page_ownurl` if the user wants a clean URL (`ownurl` rules, SEO_AND_URLS.md).

## Avoid (page-specific)

- «Мы — команда профессионалов с многолетним опытом», «индивидуальный подход к каждому клиенту», «высокое качество по доступным ценам».
- Invented addresses, phones, requisites, licences, prices.
- Rewriting legal texts or official documents.
- Images with text instead of text (hours as a picture).

## Checklist

- [ ] The key fact of the page is in the first lines.
- [ ] Contacts are clickable and exact.
- [ ] Legal text is verbatim.
- [ ] Prices/conditions have a date or "актуально на".
- [ ] The page is reachable from a menu (PAGES_AND_MENUS.md).

## Brief template

```text
Тип страницы:
Название организации/проекта:
Факты (адрес, телефоны, часы, услуги, цены, документы):
Юридический текст (дословно), если есть:
Куда вести пользователя (действие):
Чистый URL:
Голос: официальный / дружелюбный / экспертный
```
