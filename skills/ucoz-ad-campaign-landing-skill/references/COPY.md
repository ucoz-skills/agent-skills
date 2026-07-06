# Ad and Landing Copy

Before `page_add` and the final package, each segment goes through **paid traffic copywriting**.  
Texts must be **ready to paste into Yandex Direct/Google** and look good on the landing.

---

## §Message Match: ad ↔ landing

**Main rule:** a user who clicks on an ad **immediately** sees the same meaning — not "the service in general".

### Numeric metric (agent calculates itself)

1. Take **significant words** from `headline_1` (length > 2, excluding stop words: `in on for by and with to for from`).
2. `overlap_ratio = |significant words in headline ∩ words in H1| / |significant words in headline|`
3. Verdict:
   - `≥ 0.60` → **pass** ✓
   - `0.35–0.59` → **revise** — clarify hero
   - `< 0.35` → **fail** → rewrite H1

**Example:** `headline = "Accountant for Sole Proprietor on Simplified Tax"` → significant: `{accountant, simplified}` (stop words `for on` not counted)  
H1 = `"Accountant for Sole Proprietor on Simplified Tax"` → overlap = 2/2 = 1.0 → **pass** ✓

### Pass / fail examples

| Ad | H1 | Verdict |
|----|-----|---------|
| Wedding song as a gift | Wedding song as a gift | ✅ pass |
| Apartment renovation turnkey in Kazan | Apartment renovation turnkey in Kazan | ✅ pass |
| Wedding song as a gift | Custom songs made to order | ❌ fail (different intent) |
| Flower delivery in 2 hours | Flower shop since 2010 | ❌ fail (no urgency offer) |

### Prohibited in hero

- Navigation menu with links to other services as the main focus.
- "Our services" with 5+ cards before the offer is confirmed.
- H1 from `forbidden_generic_hero` (e.g., "Custom Song Studio", "All services").

Below the fold is fine: "Other formats", "About the studio" — kept brief.

---

## §Ad (Yandex / Google)

### Required in segment package

- **Headline 1** — main keyword + offer (≤56 characters Yandex); = future H1.
- **Headline 2** — clarification: deadline / geo / price "from" / format.
- **Text** — benefit + fact from brief + **one** clear CTA.
- **2 A/B variants** of headline or text.

### Sources (priority order)

| # | Source | What to take |
|---|--------|-------------|
| 1 | `brief.business_description` | Deadlines, price "from", format, constraints — truth only |
| 2 | Competitors (SERP) | Offers, prices, weak points |
| 3 | Wordstat `results` | Popular phrasing for headline_1 / H1 |
| 4 | `association_ideas` | Only relevant intent |

### Quality checklist

```
[ ] Headline contains words from main_keyword (or top Wordstat result)
[ ] Specific to segment (format, "as a gift", "turnkey")
[ ] No promises not in business_description
[ ] No clickbait ("best", "#1") without proof
[ ] CTA verb matches landing button
[ ] Geo in text if keyword/brief contains geo
[ ] Text fits within limits (§Formats in ADS.md)
```

### Bad → Good

| Bad | Good |
|-----|------|
| Leave a request — we'll call you | Wedding song in 3 days — text based on your story. Order |
| Custom songs | Wedding song as a gift — recording + instrumental |
| Quality services | From 15,000 ₽ · custom song turnkey |

---

## §Landing (first screen and below)

### Hero

| Field | Requirement |
|-------|-------------|
| `landing.h1` | = `ad.headline_1` (message match, overlap ≥ 0.60) |
| `landing.hero_lead` | 1–2 sentences: expands `ad.text` + USP from brief |
| `landing.cta` | Same verb as in the ad |

### Blocks below the fold (MVP)

- **Trust:** 2–4 points with numbers/facts from brief (deadline, what's included, experience).
- **How to order:** 3 steps, no abstractions.
- **FAQ:** 3–5 questions **specific to the segment intent** (price, deadline, what the client needs).
- Repeated CTA with the same offer.

### Price and deadline consistency

| In ad | On landing |
|-------|-----------|
| Has "from 15,000 ₽", "in 3 days" | Same figures somewhere on page (hero, FAQ, trust) |
| No price (offer + CTA only) | Don't promise a specific price in hero on landing either |
| Brief has price, landing hides it | Don't write price in ad; in FAQ: "We'll calculate after a brief" |

**Rule:** what was promised in the click — we don't contradict on the page.

### Landing checklist

```
[ ] H1 not from forbidden_generic_hero
[ ] hero_lead does not duplicate H1 word for word
[ ] Offer is visible on first screen without scrolling (320px)
[ ] No "studio of all genres" on a segment landing
[ ] No contradiction with ad on price/deadline
```

---

## §SEO

- `seo.title` — keyword + offer + brand, ≤70 characters.
- `seo.description` — offer + CTA; price/deadline — only if present in the ad.
- `seo.keywords` — `main_keyword` + 2–4 variants from Wordstat `results`, no noise from associations.

SEO **per segment**, not for the whole niche.

---

## §Competitors → copy

Agent works with WebSearch or Wordstat research data:

1. List 3–5 competitors: domain, offer from title/snippet.
2. Formulate `our_angle` — how our landing is different.
3. Use the **gap** in `hero_lead` and `ad.text`.

```yaml
competitor_insights:
  - domain: example.com
    offer: "from $29, 24 hours"
    gap: "no focus on weddings"
  our_angle: "3 days + client story + recording as a gift"
```

---

## §Final check before delivery

```
[ ] Ad + landing + SEO checklists passed
[ ] Message match: pass (overlap ≥ 0.60) for each segment
[ ] No template phrases left unedited
[ ] competitor_insights filled (or explicitly "SERP unavailable")
[ ] OUTPUT_PACK contains ready-to-use texts, not "fill in yourself"
```

**Prohibited** to deliver a package with a draft and a field saying "write the perfect texts yourself".
