# Pipeline: From Brief to Ready Package

The agent performs all steps natively. No scripts.

---

## Branching (start)

```
Are there ready-made ads (CSV or text)?
├── YES → 2A: Segments from ads → COPY → landing → UTM
└── NO  → 2B: Research → generate ads → landing → UTM

Are the ads already in the Yandex Direct / Google Ads account?
└── ADS.md §Fetch → select → to-segment → proceed as 2A
```

---

## Step 0. Brief

Details and questions: **[INTAKE.md](INTAKE.md)**.

Record `brief.json`:

| Field | Required | Example |
|-------|----------|---------|
| `niche` / `service` | yes | "Custom song composition" |
| `geo` | yes | Moscow / Russia online |
| `business_description` | yes | Timelines, prices, USP, constraints |
| `keywords` | yes* | List; *empty → Wordstat/WebSearch |
| `base_url` | for UTM | `https://site.ucoz.ru` |
| `yandex_direct_access` | before API | `test` / `full` / `none` |
| `landing_scale` | before page_add | `single` / `multi` / `match_ads` |

---

## 2A. Segments from ads (creative-first)

Have CSV or ad texts — the agent reads them and builds `segments.json` itself:

1. For each row: `headline_1` → `landing.h1` (same value — message match).
2. `page_ownurl` — transliteration from `headline_1`, 2–4 words, hyphenated:
   ```
   "Custom Telegram Chatbot Development" → "custom-telegram-chatbot-development"
   ```
3. Enrich `hero_lead`, FAQ, SEO per **[COPY.md §Landing](COPY.md)**.
4. Segment schema: `templates/segments.manifest.example.json`.

Detailed CSV import: **[ADS.md §Import](ADS.md)**.

---

## 2B. Research (no ads)

1. **Wordstat** — 3–5 seed phrases for the niche. No API → `WebSearch` on seed queries.
2. **Competitors** — TopReplies on main keywords or WebSearch. Details: **[RESEARCH.md](RESEARCH.md)**.
3. Cluster keywords by intent → draft segments.
4. Next → step 3 (ad generation).

---

## Step 3. Ad generation (only for branch 2B)

For each segment per **[COPY.md](COPY.md)**:
1. Fill in segment brief per `COPY.md §Message Match`.
2. Headline 1 = offer_core = future H1.
3. Formats and limits: COPY.md §Formats.
4. Self-check against COPY.md checklist.

---

## Step 4. Message match

The agent checks **independently** per **[COPY.md §Message Match](COPY.md)**:

```
overlap_ratio = |significant words in headline_1 ∩ words in H1| / |significant words in headline_1|
≥ 0.60 → pass ✓   0.35–0.59 → revise   < 0.35 → fail → rewrite H1
```

Status `fail` or `revise` → fix before `page_add`.

---

## Step 5. Landing on uCoz

For **each** segment → **[LANDING_PLAYBOOK.md](LANDING_PLAYBOOK.md)**:

1. Agent generates HTML following `LANDING_PLAYBOOK.md` + COPY.md rules.
2. `page_list` → check if `page_ownurl` is available.
3. Shared `/css/style.css`, `/js/script.js`, favicon, menu — **once** per site.
4. `page_add` → get `page_id`, `url` → record in `segments.json`.

Do not output `final_url` until `page_add` succeeds.

---

## Step 6. UTM

Agent builds the string using the formula:

```
{base_url}/{page_ownurl}?utm_source={source}&utm_medium=cpc
  &utm_campaign={niche}_{geo}_{YYYYMMDD}
  &utm_content={segment_id}
  &utm_term={url_encoded_keyword}
```

URL-encode Cyrillic via Shell if needed:
```bash
python -c "import urllib.parse; print(urllib.parse.quote('keyword here'))"
```

---

## Step 7. API push (Yandex Direct / Google Ads) — only with permission

1. Agent assembles payload per **[DIRECT_API.md](DIRECT_API.md)** / **[GOOGLE_ADS.md](GOOGLE_ADS.md)**.
2. Shows the user exactly what will be created.
3. Explicitly asks: "Shall we submit? Mode: sandbox / prod?"
4. Only after confirmation — executes via Shell (`curl`).

Update the URL in an existing ad: **[ADS.md §Sync](ADS.md)**.

---

## Step 8. Final report

The agent generates the OUTPUT_PACK per **[OUTPUT.md](OUTPUT.md)** from the updated `segments.json`.

---

## Automation checklist

```
[ ] brief.json filled (niche, geo, base_url, yandex_direct_access, landing_scale)
[ ] segments.json: 3–7 segments, headline_1 = h1, page_ownurl = transliteration
[ ] COPY.md: texts ready, not draft
[ ] competitor_insights filled (or explicitly "SERP unavailable")
[ ] page_add for each segment → page_id and url recorded
[ ] message match: pass for each segment
[ ] final_url with UTM after page_add
[ ] OUTPUT_PACK delivered to user
```

---

## Limitations

| Agent does natively | Requires external |
|---------------------|-------------------|
| CSV → segments.json | Wordstat API (Yandex Cloud key) |
| Landing HTML, UTM strings | OAuth tokens for Yandex Direct and Google Ads |
| Message match, SEO, FAQ | Real API push (only with permission) |
| OUTPUT_PACK, A/B recommendations | SERP via API (fallback — WebSearch) |

---

## MCP order (summary)

```text
1) templates_tool(action="page_list")
2) [research — Wordstat or WebSearch]
3) ftp: /css/style.css, /js/script.js, /favicon.ico (once per site)
4) For each segment:
   templates_tool(action="page_add", page_owntmpl=1, page_ownurl=..., page_tmpl=..., meta_*)
   [mail_* if form]
5) templates_tool(action="page_get", page_id=<new>) → record url
```
