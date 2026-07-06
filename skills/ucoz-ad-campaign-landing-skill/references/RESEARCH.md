# Research: Wordstat, SERP, Competitors

Used in branch 2B (no ready-made ads). In branch 2A (CSV available) — optional.

---

## §Wordstat via Yandex Search API v2

Documentation: [Search API Wordstat](https://aistudio.yandex.ru/docs/ru/search-api/concepts/wordstat.html)

**Do not confuse** with the old `api.wordstat.yandex.net` (OAuth + application). We use **Search API v2**.

### Setup

**Order matters: billing account first, then API key.**

1. **Billing account** in [Yandex Cloud](https://console.yandex.cloud/) → Billing → create and link to cloud. Without it you get `401`/`403` even with a valid key.
2. **API key**: [AI Studio](https://aistudio.yandex.ru/) → API keys → create, scope **`yc.search-api.execute`**.
3. **Folder ID** of the catalog (`b1g...`) — in Cloud Console.
4. SA role: **`search-api.editor`**.
5. The **Yandex Search API** product is enabled in the catalog.

Env: `YANDEX_SEARCH_API_KEY`, `YANDEX_FOLDER_ID`.

### API call (curl)

```bash
curl -s -X POST https://searchapi.api.cloud.yandex.net/v2/wordstat/topRequests \
  -H "Authorization: Api-Key $YANDEX_SEARCH_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "phrase": "wedding song",
    "folderId": "'$YANDEX_FOLDER_ID'",
    "numPhrases": 20,
    "regions": ["213"],
    "devices": ["DEVICE_ALL"]
  }'
```

Other methods: `/dynamics` (seasonality), `/regions` (geo demand), `/getRegionsTree` (region directory).

### Usage rules

| Rule | Meaning |
|------|---------|
| **When** | Before the final segment table, not after landings |
| **How many** | 3–5 calls per brief (by intent cluster) |
| **results** | **Primary source** for headlines and H1 |
| **associations** | Only after review: narrow relevant intent or negative keywords |
| **Don't call** | For branch 2A (CSV available) — Wordstat not needed |

`totalCount` < 200/month — warn: narrow demand, reformulation needed.

### Regions: order

```
brief.geo ("Moscow") → region_ids: ["213"]
→ POST /topRequests with regions: ["213"]
```

| Situation | Region |
|-----------|--------|
| `region_ids: ["213"]` in brief | Directly topRequests |
| `geo: "Kazan"` | Find ID via `/getRegionsTree` |
| Federal/online | `["225"]` (Russia) |

### Errors

| Symptom | Action |
|---------|--------|
| 401 `Unknown api key` | Checklist: billing account → key → folder |
| 403 `Permission denied` | SA role `search-api.editor` + scope `yc.search-api.execute` |
| `INVALID_ARGUMENT` + folder | Check `YANDEX_FOLDER_ID` |
| 429 / quota | Pause; don't duplicate requests |
| No key | Fallback: agent WebSearch on seed phrases |

**No Wordstat access → WebSearch fallback:** agent runs WebSearch on 3–5 seed phrases → analyzes SERP → clusters manually.

### Folder ID (how to find)

1. Cloud Console → catalog → copy `b1g...`.
2. **Trick:** make a request to YandexGPT with a wrong folder → the correct ID will appear in the error.

---

## §Competitors (SERP)

### What's needed

Real offers from search results for ads and landings.

### How to get

**Via Search API v2 (same key and folder as Wordstat):**

```bash
curl -s -X POST https://searchapi.api.cloud.yandex.net/v2/web/search \
  -H "Authorization: Api-Key $YANDEX_SEARCH_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "query": { "searchType": "SEARCH_TYPE_RU", "queryText": "wedding song Moscow" },
    "folderId": "'$YANDEX_FOLDER_ID'",
    "maxPassages": 5
  }'
```

**Fallback:** agent WebSearch — if API is unavailable; agent puts results into the table itself.

### What to do with results

1. Match query to segment `main_keyword`.
2. List 3–5 competitors: domain, offer from title/snippet.
3. Formulate `our_angle` — how we differ.
4. Transfer to `ad.text`, `hero_lead`, FAQ — **do not copy** titles verbatim.

Template in segments.json:
```json
"competitor_insights": {
  "source": "SERP API / WebSearch",
  "items": [
    { "domain": "example.com", "offer": "from $29, 24 hours", "gap": "no focus on weddings" }
  ],
  "our_angle": "3 days, client story, recording as a gift"
}
```

### Limitations

- Organic results, not the ad block.
- In OUTPUT_PACK indicate source: "SERP API v2" or "WebSearch (fallback)".

---

## §Agent: research → segments

**Scripts (if used) provide data and a draft; final segments are the agent's decision.**

### Principle

Build segments from:
- `wordstat.seeds[].results` (top with frequency, close to the service)
- `keywords` from brief
- **Not** from broad `associations` like "music listen" with no connection to the service

Use `associations` as:
- ideas for **another intent** (separate segment)
- **negative keywords** (filter out noise)

### Typical mistakes

| Mistake | Correct |
|---------|---------|
| Segment "music listen" for "custom songs" | Negative keyword or ignore |
| 5 segments from one cluster | 1 segment + A/B variants in test_notes |
| Publishing without message match | Check COPY.md §Message Match first |
| final_url before page_add | slug → page_add → UTM → final_url |

### Pre-segments checklist

```
[ ] 3–7 meaningful intents (no duplicates)
[ ] COPY.md checklists passed
[ ] Message match: pass for each segment
[ ] competitor_insights filled (or explicitly "SERP unavailable")
```
