# Output Package and Page Deployment

---

## §OUTPUT_PACK: final report

The agent generates this report **itself** — reads `segments.json` and fills in the template below.

### Mapping from segments.json

```
brief.niche / geo / base_url / utm_source → report header
len(segments) → number of segments
seg.segment_id / segment_name / main_keyword → identification
seg.ad.headline_1 / headline_2 / text → ad copy
seg.final_url / final_url_yandex / final_url_google → URL with UTM
seg.page_ownurl → /slug
seg.landing.h1 → Hero H1
seg.message_match.status → pass/revise/fail
seg.seo.title / description → SEO fields
seg.competitor_insights → competitor data
seg.ucoz.page_id / published → publish status
seg.test_notes[] → test recommendations
```

Segment status:
- `ucoz.published = true` → "published"
- `final_url` exists, `published` is missing → "URL ready, check page_add"
- otherwise → "draft"

---

### Summary (once at the beginning of the report)

```markdown
## Segmented Traffic Package — {niche}, {geo}

**Date:** {YYYY-MM-DD}
**Segments:** {N}
**Site:** {base_url}
**Research:** Wordstat ✓ / WebSearch (fallback) / user keywords

### Segments

| ID | Offer | URL | Status |
|----|-------|-----|--------|
| seg_01 | … | https://… | published / draft |

### What to do manually

1. Create a campaign in {Yandex Direct/Google} with N ad groups.
2. Insert ad copy from blocks below.
3. Enter **final_url** (pages already published via page_add).
4. Launch with the recommended test budget.
```

---

### Block for one segment (repeat for each)

```markdown
---

## Segment: {segment_name} (`{segment_id}`)

### Intent and keywords
- **Main keyword:** {main_keyword}
- **Additional keywords:** key1, key2, …

### Ad ({yandex|google})

**Headline 1:** …
**Headline 2:** …
**Text:** …
**A/B variant:** …

### Landing
- **URL:** {full_url_with_utm}
- **Slug:** /{page_ownurl}
- **Message match:** pass ✓ (overlap_ratio: 0.8)
- **Hero H1:** "…"

### SEO
- **Title:** …
- **Description:** …

### UTM (breakdown)
| Parameter | Value |
|-----------|-------|
| utm_source | yandex |
| utm_medium | cpc |
| utm_campaign | … |
| utm_content | {segment_id} |
| utm_term | … |

### Competitors (SERP)
- **Source:** SERP API v2 / WebSearch (fallback)
- **Our angle:** {our_angle}
- **Sample offers:** domain — offer

### Test recommendations
1. A/B: headline 1 variant A vs B — evaluate CTR after 30+ clicks.
2. Success metric: form CR / phone click.
3. Stop ad when: CPC > X₽ + 0 conversions in 50 clicks.

### Technical (if published on uCoz)
- `page_id`: …
- MCP actions: page_add, ftp write css/js
```

---

### Draft without publishing

If user requests texts only without MCP:
- Deliver segment blocks **without** `page_id`.
- Add: "HTML template available on request".
- If `page_add` was not performed — do not specify `final_url`; mark as "draft, publishing pending".

---

## §Page deployment on uCoz

Full MCP rules, CSS/JS, forms, SEO, checklists — in **[LANDING_PLAYBOOK.md](LANDING_PLAYBOOK.md)** (section 0 and step 10A).

Brief:

1. `page_list` — check available `page_ownurl`
2. `page_add` — publish landing **before** giving `final_url`
3. `page_get` — record `page_id` and `url` in `segments.json`

Homepage (`page_id=1`) — only on explicit request → **step 10B** in `LANDING_PLAYBOOK.md`.
