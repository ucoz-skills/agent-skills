# Working with Ads

Three scenarios: **import from CSV**, **fetch from account**, **update URLs after landing**.

---

## §Import: CSV → segments.json (agent-native)

Branch 2A: ads are ready → agent reads CSV and builds `segments.json` itself.

### CSV format (UTF-8)

| Column | Required | Description |
|--------|----------|-------------|
| `headline_1` | **yes** | = future H1 (message match) |
| `segment_id` | no | Otherwise agent generates `seg_01`, `seg_02`, … |
| `segment_name` | no | Human-readable name |
| `headline_2` | no | Second headline |
| `text` | no | Ad text |
| `main_keyword` | no | For UTM; default = headline_1 |
| `offer_core` | no | Default = headline_1 |
| `page_ownurl` | no | Slug; otherwise transliteration from headline_1 |
| `display_path` | no | Display path |

Template: `templates/ads.example.csv`.

### What the agent does with CSV

1. `headline_1` → `ad.headline_1` and `landing.h1` (identical — message match).
2. `page_ownurl` — from column or transliterated: `"Chatbot Development"` → `"chatbot-development"`.
3. `hero_lead`, FAQ, SEO — agent fills in per **COPY.md** (if not in CSV).
4. Result: `segments.json` per schema `templates/segments.manifest.example.json`.

---

## §Ad formats

### Yandex Direct

Minimum **2 headline variants** per segment for A/B:

```
Headline 1: {up to 56 characters, offer_core}   ← = future H1
Headline 2: {clarification: deadline / geo / price}
Text:       {up to 81 characters — offer + CTA}
```

Rules:
- Headline 1 = landing H1 (message match).
- Include geo in text if keyword contains a city.
- Don't promise anything not in the brief.

### Google Ads (Responsive Search Ad)

```
Headline 1–3: {≤30 characters each, first = offer_core}
Description 1–2: {≤90 characters}
Final URL: {with UTM after page_add}
```

Minimum 3 headlines, 2 descriptions.

### Sitelinks (Yandex)

Only anchors on the **same** page: `#order`, `#faq`, `#examples`. Do not link to other segments.

### Negative keywords

Suggest 3–10 negative keywords for the narrow segment:
```
segment "wedding song" → negatives: free, karaoke, download, instrumental
```

---

## §Fetch: get ads from account

When ads **already exist** in Yandex Direct / Google Ads — agent calls API via Shell (`curl`).

### Yandex Direct

**Requirements:** `YANDEX_DIRECT_TOKEN` in env (obtain via: `DIRECT_API.md` → OAuth).

```bash
curl -s -X POST \
  -H "Authorization: Bearer $YANDEX_DIRECT_TOKEN" \
  -H "Content-Type: application/json; charset=utf-8" \
  https://api.direct.yandex.com/json/v5/ads \
  -d '{
    "method": "get",
    "params": {
      "SelectionCriteria": { "CampaignIds": [<CAMPAIGN_ID>] },
      "FieldNames": ["Id","CampaignId","AdGroupId","Status","State","Type"],
      "TextAdFieldNames": ["Title","Title2","Text","Href","DisplayUrlPath"]
    }
  }'
```

Sandbox: use host `api-sandbox.direct.yandex.com`.

Response (key fields):
```json
{ "result": { "Ads": [{ "Id": 123, "TextAd": { "Title": "Headline", "Href": "..." } }] } }
```

**Errors:**

| Code | Cause |
|------|-------|
| 58 | No application / wrong host (sandbox vs prod) |
| 513 | Sandbox not started |
| `Ads: []` | No TEXT_AD in campaign |

### Google Ads (RSA)

**Requirements:** env `GOOGLE_ADS_*` (see `GOOGLE_ADS.md`).

```bash
# First get access token:
ACCESS_TOKEN=$(curl -s -X POST https://oauth2.googleapis.com/token \
  -d "client_id=$GOOGLE_ADS_CLIENT_ID&client_secret=$GOOGLE_ADS_CLIENT_SECRET&refresh_token=$GOOGLE_ADS_REFRESH_TOKEN&grant_type=refresh_token" \
  | python -c "import sys,json; print(json.load(sys.stdin)['access_token'])")

curl -s -X POST \
  "https://googleads.googleapis.com/v18/customers/$GOOGLE_ADS_CUSTOMER_ID/googleAds:searchStream" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "developer-token: $GOOGLE_ADS_DEVELOPER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{ "query": "SELECT ad_group_ad.ad.id, ad_group_ad.ad.final_urls, ad_group_ad.ad.responsive_search_ad.headlines, campaign.id, ad_group.id, ad_group_ad.status FROM ad_group_ad WHERE campaign.id = <CAMPAIGN_ID> AND ad_group_ad.ad.type = RESPONSIVE_SEARCH_AD" }'
```

---

## §Sync: update URL after landing

General cycle:
```text
1) §Fetch → get id/resource_name + current URL
2) Agent adds to segments.json: direct.ad_id / google_ads.ad_resource
3) PIPELINE.md → landing + page_add + UTM
4) §Sync → agent shows update payload → asks for permission → executes curl
```

**Only after explicit user permission.**

### Update Href in Yandex Direct

```bash
curl -s -X POST \
  -H "Authorization: Bearer $YANDEX_DIRECT_TOKEN" \
  -H "Content-Type: application/json; charset=utf-8" \
  https://api.direct.yandex.com/json/v5/ads \
  -d '{
    "method": "update",
    "params": { "Ads": [{ "Id": <AD_ID>, "TextAd": { "Href": "<final_url_with_utm>" } }] }
  }'
```

### Update finalUrls in Google Ads

```bash
curl -s -X POST \
  "https://googleads.googleapis.com/v18/customers/$GOOGLE_ADS_CUSTOMER_ID/adGroupAds:mutate" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "developer-token: $GOOGLE_ADS_DEVELOPER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "operations": [{ "updateMask": "ad.finalUrls", "update": {
      "resourceName": "<resource_name>",
      "ad": { "finalUrls": ["<final_url_with_utm>"] }
    }}]
  }'
```

---

## For the agent (general order)

1. Get `campaign_id` from the user.
2. `§Fetch` → show `id + headline_1 + current URL`.
3. User selects → agent adds block `direct.ad_id` / `google_ads.ad_resource` to segments.json.
4. **PIPELINE.md** → landing, message match, page_add, UTM.
5. **Show update payload → ask for permission → execute.**
6. Do not enable the campaign until the URL has been successfully updated.
