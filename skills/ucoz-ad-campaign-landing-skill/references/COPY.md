# Ad Copy and Landing Pages

Before `page_add` and the final package, every segment goes through **paid-traffic copywriting**.  
The copy must be **ready to paste into Yandex Direct/Google** and look right on the landing page.

---

## §Message Match: ad ↔ landing page

**Main rule:** after clicking the ad, the user **immediately** sees the same meaning — not “the service in general.”

### Numeric metric (the agent calculates it)

1. From `headline_1`, take **significant words** (length > 2, excluding stop words: `в на для по и с к за из`).
2. `overlap_ratio = |significant headline words ∩ H1 words| / |significant headline words|`
3. Verdict:
   - `≥ 0.60` → **pass** ✓
   - `0.35–0.59` → **revise** — refine the hero
   - `< 0.35` → **fail** → rewrite the H1

**Example:** `headline = "Бухгалтер для ИП на УСН"` → significant: `{бухгалтер, усн}` (stop words `для на` are not counted)  
H1 = `"Бухгалтер для ИП на УСН"` → overlap = 2/2 = 1.0 → **pass** ✓

### Pass / fail examples

| Ad | H1 | Verdict |
|------------|-----|---------|
| A song for newlyweds as a gift | A song for newlyweds as a gift | ✅ pass |
| Turnkey apartment renovation in Kazan | Turnkey apartment renovation in Kazan | ✅ pass |
| A song for newlyweds as a gift | Custom original songs | ❌ fail (different intent) |
| Flower delivery in 2 hours | A flower shop since 2010 | ❌ fail (no urgency offer) |

### Forbidden in the hero

- A menu with links to other services as the main emphasis.
- “Our services” with 5+ cards before the offer is confirmed.
- An H1 from `forbidden_generic_hero` (for example, “Original song studio”, “All services”).

Below the fold, short blocks are allowed: “Other formats”, “About the studio”.

---

## §Ad (Yandex / Google)

### Required in the per-segment package

- **Headline 1** — the main keyword + offer (≤56 characters in Yandex); = the future H1.
- **Headline 2** — a clarification: timeline / geo / price “from” / format.
- **Text** — the benefit + a fact from the brief + **one** clear CTA.
- **2 A/B variants** of the headline or the text.

### Practical bans for an ad series

- Do not use “Call us” if the landing page has no phone number and no call scenario.
- Do not put internal test names into external copy: “without a price”, “all templates”, “variant B”.
- Do not write technical explanations such as “registration will open”, “verified price”, “the link goes to the catalog”.
- Do not mix independent hypotheses. AI transfer of an existing site is a separate offer and a separate series, unless the owner decided otherwise.
- If 24/7 support is confirmed, use it as a concrete advantage, not as a minor footnote.

### Sources (in priority order)

| # | Source | What to take |
|---|----------|-----------|
| 1 | `brief.business_description` | Timelines, price “from”, format, constraints — facts only |
| 2 | Competitors (SERP) | Offers, prices, weak spots |
| 3 | Wordstat `results` | A popular wording for headline_1 / H1 |
| 4 | `association_ideas` | Relevant intent only |

### Quality checklist

```
[ ] The headline contains words from main_keyword (or the top Wordstat result)
[ ] Specifics for the segment (format, “as a gift”, “turnkey”)
[ ] No promises that are absent from business_description
[ ] No clickbait (“the best”, “#1”) without proof
[ ] The CTA verb matches the button on the landing page
[ ] Geo is in the text if the keyword or the brief contains geo
[ ] The text fits the limits (§Formats in ADS.md)
```

### Bad → good

| Bad | Good |
|-------|--------|
| Leave a request — we will get in touch | A wedding song in 3 days — lyrics based on your story. Order |
| Custom songs | A song for newlyweds as a gift — recording + backing track |
| Quality services | From 15 000 ₽ · an original turnkey song |

---

## §Landing page (first screen and below)

### Hero

| Field | Requirement |
|------|------------|
| `landing.h1` | = `ad.headline_1` (message match, overlap ≥ 0.60) |
| `landing.hero_lead` | 1–2 sentences: expands `ad.text` + the USP from the brief |
| `landing.cta` | The same verb as in the ad |

### Below-the-fold blocks (MVP)

- **Trust:** 2–4 points with numbers or facts from the brief (timeline, what is included, experience).
- **How to order:** 3 steps, with no abstraction.
- **FAQ:** 3–5 questions **for the segment intent** (price, timeline, what is needed from the client).
- Repeat the CTA with the same offer.

### Price and timeline consistency

| In the ad | On the landing page |
|--------------|-------------|
| Contains “from 15 000 ₽”, “in 3 days” | The same numbers appear somewhere on the page (hero, FAQ, trust) |
| No price (offer + CTA only) | The landing page also must not promise a specific price in the hero |
| The brief has a price, and the landing page hides it | Do not put the price in the ad; in the FAQ: “We will calculate it after the brief” |

**Rule:** what the click promised is not contradicted on the page.

In the hidden variant, do not write that the price is hidden, and do not leave the number in SEO or in service text. In the shown variant, the value must match across the ad, the hero, the pricing block, and the FAQ.

### CTA scenario

The CTA describes the user’s action, not the technical route:

| Purpose | Public wording |
|---|---|
| Sign-up | “Create a site”, “Try for free” |
| Thematic templates | “Choose a template”, “View designs” |
| General catalog | “Pick a design”, “Start with a template” |

If a thematic category contains few options, a separate test of the general catalog is allowed. The difference stays in destination_url and in the internal campaign name; the landing page does not have to say “all templates” in public copy.

### Landing checklist

```
[ ] The H1 is not taken from forbidden_generic_hero
[ ] hero_lead does not duplicate the H1 word for word
[ ] The offer is visible on the first screen without scrolling (320px)
[ ] There is no “studio of every genre” on a segment landing page
[ ] There is no price or timeline contradiction with the ad
```

---

## §SEO

- `seo.title` — keyword + offer + brand, ≤70 characters.
- `seo.description` — offer + CTA; include price or timeline only if they are in the ad.
- `seo.keywords` — `main_keyword` + 2–4 variants from Wordstat `results`, not junk from associations.

SEO is **for the segment**, not for the whole niche.

---

## §Competitors → copy

The agent works with WebSearch data or Wordstat research:

1. List 3–5 competitors: domain, and the offer from the title or snippet.
2. Formulate `our_angle` — how our landing page is different.
3. Use the **gap** in `hero_lead` and `ad.text`.

```yaml
competitor_insights:
  - domain: example.ru
    offer: "от 2999₽, 24 часа"
    gap: "нет акцента на свадьбу"
  our_angle: "3 дня + история клиента + запись в подарок"
```

---

## §Final check before delivery

```
[ ] The ad, landing, and SEO checklists are passed
[ ] Message match: pass (overlap ≥ 0.60) for every segment
[ ] There are no unedited template phrases
[ ] competitor_insights is filled in (or explicitly “SERP unavailable”)
[ ] OUTPUT_PACK contains finished copy, not “fill this in yourself”
```

**Forbidden:** delivering a package that is still a draft and contains a field saying “you will invent the ideal copy yourself.”
