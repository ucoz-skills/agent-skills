# End-to-End Examples

Three complete examples: different niches, different scenarios.

---

## Case 1: "Wedding Song" — branch 2A (one CSV, one segment)

**Input:** niche "Custom Songs", service "Original Song", geo "Russia, online", ready-made ad.

### Ad

| Field | Value |
|-------|-------|
| Headline 1 | Wedding song as a gift |
| Headline 2 | Ready in 3 days · based on your story |
| Text | Recording + lyrics + instrumental. The perfect wedding gift. Order. |

### Segment (agent builds it)

```json
{
  "segment_id": "seg_wedding_gift",
  "segment_name": "Wedding song as a gift",
  "ad": {
    "headline_1": "Wedding song as a gift",
    "headline_2": "Ready in 3 days · based on your story",
    "text": "Recording + lyrics + instrumental. The perfect wedding gift. Order.",
    "main_keyword": "wedding song as a gift"
  },
  "landing": {
    "h1": "Wedding song as a gift",
    "hero_lead": "A ready song for the wedding in 3 days — lyrics based on your story, recording and instrumental track.",
    "cta": "Order a song"
  },
  "page_ownurl": "wedding-song-gift",
  "offer_core": "Custom wedding song — the perfect ready-made gift"
}
```

### Message match

Headline: `{wedding, song, gift}` → H1 has the same words → overlap = 3/3 = 1.0 → **pass** ✓

### Landing (hero fragment)

```html
<h1>Wedding song as a gift</h1>
<p class="hero-lead">A ready song for the wedding in 3 days — lyrics based on your story, recording and instrumental track.</p>
<a href="#order" class="btn-primary">Order a song</a>
```

### UTM

```
https://mysongs.ucoz.ru/wedding-song-gift
  ?utm_source=yandex
  &utm_medium=cpc
  &utm_campaign=songs_us_20260607
  &utm_content=seg_wedding_gift
  &utm_term=wedding+song+as+a+gift
```

### Test recommendations

1. A/B: headline 2 "in 3 days" vs "based on your story".
2. Evaluate form CR after 30–50 clicks.
3. Negative keywords: `free`, `download`, `instrumental`, `listen`, `lyrics`.

---

## Case 2: "Accounting for Business" — branch 2A, hyper-segmentation (3 segments)

**Input:** ready-made ads for three audiences, one CSV with 3 rows.

| headline_1 | page_ownurl |
|-----------|------------|
| Accounting services for LLC | `accounting-services-llc` |
| Accountant for sole proprietor on simplified tax | `accountant-sole-proprietor-simplified` |
| Accounting records restoration | `accounting-records-restoration` |

**What the agent does:**

1. Reads CSV → builds 3 segments in `segments.json`.
2. Slug from headline: `"Accountant for sole proprietor on simplified tax"` → `"accountant-sole-proprietor-simplified"`.
3. COPY.md: different offer for each hero_lead:
   - `accounting-services-llc`: "LLC accounting turnkey — reports, taxes, payroll"
   - `accountant-sole-proprietor-simplified`: "Accountant for sole proprietor on simplified tax — from $50/month"
   - `accounting-records-restoration`: "Accounting records restored in 5 days — guaranteed no penalties"
4. Message match: checks overlap for each → all **pass**.
5. `page_add` for each segment.
6. UTM: three separate links with `utm_content=seg_01/02/03`.

**OUTPUT_PACK:** three blocks "ad ↔ landing" with individual links.

---

## Case 3: "Apartment Renovation, Kazan" — branch 2B (no ads, Wordstat)

**Input:** niche "Apartment renovation", geo "Kazan", no ready-made creatives.

### Step 2B: Research

Agent runs Wordstat (or WebSearch if no API) on seed phrases:
```
seeds: ["apartment renovation kazan", "turnkey renovation kazan", "finishing work kazan"]
```

Results (top-3 by `results`):
1. "apartment renovation turnkey kazan" — 1800/month
2. "apartment renovation kazan affordable" — 900/month
3. "one-bedroom apartment renovation kazan" — 450/month

### Draft segments

| segment_id | offer_hypothesis |
|-----------|-----------------|
| seg_pod_kluch | Apartment renovation turnkey in Kazan |
| seg_nedorogo | Affordable apartment renovation in Kazan — from 3,500 ₽/m² |
| seg_1k | One-bedroom apartment renovation turnkey in Kazan |

### Ads (agent generates per COPY.md)

**Segment seg_pod_kluch:**
- Headline 1: Apartment renovation turnkey — Kazan
- Headline 2: Completion in 60 days · 2-year warranty
- Text: Materials + labor. Fixed estimate. Leave a request.

### Competitors (WebSearch)

| Domain | Offer | Our angle |
|--------|-------|-----------|
| remont-kazan.ru | Renovation from 2,900 ₽/m² | Fixed estimate, no surprises |
| kazan-stroy.com | Free measurement | + 2-year warranty in contract |

### Result

3 pages published on uCoz: `/remont-pod-kluch-kazan`, `/nedorogoj-remont-kazan`, `/remont-1k-kazan`. Three separate ads with different URLs and H1.
