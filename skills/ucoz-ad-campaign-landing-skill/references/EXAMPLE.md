# End-to-End Examples

Three complete examples: different niches, different scenarios.

---

## Case 1: “A song for newlyweds” — branch 2A (one CSV, one segment)

**Input:** niche “Custom songs”, service “Original song”, geo “Russia, online”, a ready-made ad.

### Ad

| Field | Value |
|------|----------|
| Headline 1 | Песня молодожёнам в подарок |
| Headline 2 | Готово за 3 дня · по вашей истории |
| Text | Запись + текст + минус. Идеальный подарок на свадьбу. Заказать. |

### Segment (the agent builds it)

```json
{
  "segment_id": "seg_wedding_gift",
  "segment_name": "Песня молодожёнам в подарок",
  "ad": {
    "headline_1": "Песня молодожёнам в подарок",
    "headline_2": "Готово за 3 дня · по вашей истории",
    "text": "Запись + текст + минус. Идеальный подарок на свадьбу. Заказать.",
    "main_keyword": "песня молодоженам в подарок"
  },
  "landing": {
    "h1": "Песня молодожёнам в подарок",
    "hero_lead": "Готовая песня к свадьбе за 3 дня — текст по вашей истории, запись и минусовка.",
    "cta": "Заказать песню"
  },
  "page_ownurl": "pesnya-molodozhenam",
  "offer_core": "Авторская песня молодожёнам — готовый подарок к свадьбе"
}
```

### Message match

Headline: `{песня, молодожёнам, подарок}` → the H1 has the same words → overlap = 3/3 = 1.0 → **pass** ✓

### Landing page (hero fragment)

```html
<h1>Песня молодожёнам в подарок</h1>
<p class="hero-lead">Готовая песня к свадьбе за 3 дня — текст по вашей истории, запись и минусовка.</p>
<a href="#order" class="btn-primary">Заказать песню</a>
```

### UTM

```
https://mysongs.ucoz.ru/pesnya-molodozhenam
  ?utm_source=yandex
  &utm_medium=cpc
  &utm_campaign=pesni_ru_20260607
  &utm_content=seg_wedding_gift
  &utm_term=%D0%BF%D0%B5%D1%81%D0%BD%D1%8F+%D0%BC%D0%BE%D0%BB%D0%BE%D0%B4%D0%BE%D0%B6%D0%B5%D0%BD%D0%B0%D0%BC+%D0%B2+%D0%BF%D0%BE%D0%B4%D0%B0%D1%80%D0%BE%D0%BA
```

### Test recommendations

1. A/B: headline 2 “in 3 days” vs “lyrics based on the story”.
2. Evaluate form CR after 30–50 clicks.
3. Negative keywords: `бесплатно`, `скачать`, `минусовка`, `слушать`, `лирика`.

---

## Case 2: “Accounting for business” — branch 2A, hyper-segmentation (3 segments)

**Input:** ready-made ads for three audiences, one CSV with 3 rows.

| headline_1 | page_ownurl |
|-----------|------------|
| Бухгалтерское обслуживание ООО | `buhgalterskoe-obsluzhivanie-ooo` |
| Бухгалтер для ИП на УСН | `buhgalter-ip-usn` |
| Восстановление бухучёта | `vosstanovlenie-buhuchyota` |

**What the agent does:**

1. Reads the CSV → builds 3 segments in `segments.json`.
2. Translit: `"Бухгалтер для ИП на УСН"` → `"buhgalter-ip-usn"`.
3. COPY.md: a different offer for each hero_lead:
   - `buhgalterskoe-obsluzhivanie-ooo`: “Turnkey LLC accounting — reports, taxes, payroll”
   - `buhgalter-ip-usn`: “Accountant for a sole proprietor on the simplified tax system — from 3 000 ₽/month”
   - `vosstanovlenie-buhuchyota`: “We will restore the books in 5 days — a no-penalty guarantee”
4. Message match: checks overlap for each → all **pass**.
5. `page_add` for each segment.
6. UTM: three separate links with `utm_content=seg_01/02/03`.

**OUTPUT_PACK:** three “ad ↔ landing page” blocks with individual links.

---

## Case 3: “Apartment renovation, Kazan” — branch 2B (no ads, Wordstat)

**Input:** niche “Apartment renovation”, geo “Kazan”, no ready-made creatives.

### Step 2B: Research

The agent runs Wordstat (or WebSearch if there is no API) on the seed phrases:
```
seeds: ["ремонт квартир казань", "ремонт под ключ казань", "отделочные работы казань"]
```

Result (top 3 by `results`):
1. “ремонт квартир под ключ казань” — 1800/month
2. “ремонт квартир в казани недорого” — 900/month
3. “ремонт однокомнатной квартиры казань” — 450/month

### Segment drafts

| segment_id | offer_hypothesis |
|-----------|-----------------|
| seg_pod_kluch | Turnkey apartment renovation in Kazan |
| seg_nedorogo | Affordable apartment renovation in Kazan — from 3500 ₽/m² |
| seg_1k | Turnkey one-room apartment renovation in Kazan |

### Ads (the agent generates them using COPY.md)

**Segment seg_pod_kluch:**
- Headline 1: Turnkey apartment renovation — Kazan
- Headline 2: Handover in 60 days · 2-year warranty
- Text: Materials + labor. A fixed estimate. Leave a request.

### Competitors (WebSearch)

| Domain | Offer | Our angle |
|-------|-------|---------|
| remont-kazan.ru | Renovation from 2900 ₽/m² | A fixed estimate, no surprises |
| kazan-stroy.com | Free measurement | + a 2-year warranty in the contract |

### Outcome

3 pages published on uCoz: `/remont-pod-kluch-kazan`, `/nedorogoj-remont-kazan`, `/remont-1k-kazan`. Three separate ads with different URLs and H1s.
