# Research: Wordstat, SERP, Competitors

Used in branch 2B (no ready-made ads). In branch 2A (a CSV exists) it is optional.

---

## §Wordstat via Yandex Search API v2

Documentation: [Search API Wordstat](https://aistudio.yandex.ru/docs/ru/search-api/concepts/wordstat.html)

**Do not confuse this** with the old `api.wordstat.yandex.net` (OAuth + an application). We use **Search API v2**.

### Setup

**Order matters: billing account first, then the key.**

1. **Billing account** in [Yandex Cloud](https://console.yandex.cloud/) → Billing → create one and attach it to the cloud. Without it, `401`/`403` occur even with a correct key.
2. **API key**: [AI Studio](https://aistudio.yandex.ru/) → API keys → create one, scope **`yc.search-api.execute`**.
3. **Folder ID** of the folder (`b1g...`) — in Cloud Console.
4. Service-account role: **`search-api.editor`**.
5. The **Yandex Search API** product is enabled in the folder.

Env: `YANDEX_SEARCH_API_KEY`, `YANDEX_FOLDER_ID`.

### Call (curl)

```bash
curl -s -X POST https://searchapi.api.cloud.yandex.net/v2/wordstat/topRequests \
  -H "Authorization: Api-Key $YANDEX_SEARCH_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "phrase": "песня молодоженам",
    "folderId": "'$YANDEX_FOLDER_ID'",
    "numPhrases": 20,
    "regions": ["213"],
    "devices": ["DEVICE_ALL"]
  }'
```

Other methods: `/dynamics` (seasonality), `/regions` (geo demand), `/getRegionsTree` (region directory).

### Usage rules

| Rule | Meaning |
|---------|------|
| **When** | Before the final segment table, not after the landing pages |
| **How many** | 3–5 calls per brief (one per intent cluster) |
| **results** | **Primary source** of headlines and H1s |
| **associations** | Only after review: a narrow relevant intent, or negative keywords |
| **Do not call** | In branch 2A (a CSV exists) — Wordstat is not needed |

If `totalCount` < 200/month, warn: demand is narrow and the wording needs to change.

### Regions: order

```
brief.geo (“Moscow”) → region_ids: ["213"]
→ POST /topRequests with regions: ["213"]
```

| Situation | Region |
|----------|--------|
| The brief has `region_ids: ["213"]` | Call topRequests immediately |
| `geo: "Казань"` | Look up the ID via `/getRegionsTree` |
| Federal / online | `["225"]` (Russia) |

### Errors

| Symptom | Action |
|---------|----------|
| 401 `Unknown api key` | Checklist: billing account → key → folder |
| 403 `Permission denied` | Service-account role `search-api.editor` + scope `yc.search-api.execute` |
| `INVALID_ARGUMENT` + folder | Check `YANDEX_FOLDER_ID` |
| 429 / quota | Pause; do not duplicate the requests |
| No key | Fallback: the agent’s WebSearch on the seed phrases |

**No Wordstat access → WebSearch fallback:** the agent runs WebSearch on 3–5 seed phrases → analyzes the SERP → clusters manually.

### Folder ID (how to find it)

1. Cloud Console → folder → copy `b1g...`.
2. **Tip:** send a YandexGPT request with the wrong folder → the error contains the correct ID.

---

## §Competitors (SERP)

### What is needed

Real offers from the search results, for ads and landing pages.

### How to get them

**Via Search API v2 (the same key and folder as Wordstat):**

```bash
curl -s -X POST https://searchapi.api.cloud.yandex.net/v2/web/search \
  -H "Authorization: Api-Key $YANDEX_SEARCH_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "query": { "searchType": "SEARCH_TYPE_RU", "queryText": "песня молодоженам Москва" },
    "folderId": "'$YANDEX_FOLDER_ID'",
    "maxPassages": 5
  }'
```

**Fallback:** the agent’s WebSearch — if the API is unavailable; the agent puts the result into the table itself.

### What to do with the results

1. Match the query to the segment’s `main_keyword`.
2. List 3–5 competitors: domain, and the offer from the title or snippet.
3. Formulate `our_angle` — how we differ.
4. Carry it into `ad.text`, `hero_lead`, and the FAQ — **do not copy** the title word for word.

Template in segments.json:
```json
"competitor_insights": {
  "source": "SERP API / WebSearch",
  "items": [
    { "domain": "example.ru", "offer": "от 2999₽, 24 часа", "gap": "нет акцента на свадьбу" }
  ],
  "our_angle": "3 дня, история клиента, запись в подарок"
}
```

### Limits

- Organic results, not the ad block.
- In OUTPUT_PACK, name the source: “SERP API v2” or “WebSearch (fallback)”.

---

## §Agent: research → segments

**Scripts (if they were used) supply data and a draft; the final segments are the agent’s decision.**

### Principle

Build segments from:

- `wordstat.seeds[].results` (the top by frequency, close to the service)
- `keywords` from the brief
- **Not** from broad `associations` such as “музыка слушать” with no link to the service

Use `associations` as:

- ideas for **another intent** (a separate segment)
- **negative keywords** (to cut junk)

### Typical mistakes

| Mistake | Correct |
|--------|-----------|
| A “музыка слушать” segment for “песни на заказ” | A negative keyword, or ignore it |
| 5 segments from one cluster | 1 segment + A/B variants in test_notes |
| Publishing without message match | Check COPY.md §Message Match first |
| final_url before page_add | slug → page_add → UTM → final_url |

### Checklist before segments

```
[ ] 3–7 meaningful intents (not duplicates)
[ ] COPY.md checklists are passed
[ ] Message match: pass for every segment
[ ] competitor_insights is filled in (or explicitly “SERP unavailable”)
```
