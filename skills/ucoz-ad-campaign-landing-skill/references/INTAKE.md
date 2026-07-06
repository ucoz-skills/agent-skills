# Dialog Start and Clarifying Questions (agent)

Before starting research or building the package, collect the **brief**. Do not publish landings until the required fields are clear — or the user explicitly said "do it on example / from brief.example".

Field schema: **§brief.json schema** below → file `brief.json` (template: `templates/brief.example.json`).

---

## How to start (agent's first message)

**Before pushing to Yandex Direct / Google and before mass `page_add`**, the agent must clarify two decisions (see tables below) if the user hasn't stated them:

1. **Yandex Direct API access** — test (sandbox only) or full (live account).
2. **Landing scale** — one landing or hyper-segmentation (separate URL per ad/segment).

Record the answers in `brief.json`: `yandex_direct_access`, `landing_scale`, `max_segments` (see **§brief.json schema** below).

**If the user sent ad texts** — this is the **creative-first** branch (**PIPELINE.md §2A**): Wordstat is not required, but **the two questions above** must still be asked if API or multiple URLs are planned.

If the user's message is vague ("make landing pages for ads"), respond briefly and to the point:

```markdown
I'll build a segmented traffic package: ads + landings on uCoz (separate URL per segment) + UTM.

To get started, I need answers to the **required** items (free-form list is fine):

1. **Niche and service** — what do you do in one phrase?
2. **Geography** — city / region / "all of Russia, online"?
3. **Description for copy** — timelines, price (if allowed in ads), what's included, what we don't promise?
4. **Keywords** — 3–10 phrases you want traffic for (or "choose for me").
5. **Website** — uCoz URL (for ad links and page publishing).
6. **Advertising** — Yandex Direct, Google, or both?
7. **Direct API** — is your application **test** (sandbox only) or **full** access? (see `DIRECT_API.md`)
8. **Landings** — **one** shared landing or a **separate URL per ad/segment** (hyper-segmentation)?

**Optional:** ready-made ads (text/CSV), form on the landing, whether to show price on the site, exact number of segments (if multi — default 3–5).

Once you answer — I'll fill the brief and start research (Wordstat + competitors).
```

If the first message already contains everything required — **don't duplicate** the questions; list what you understood and clarify only the gaps.

---

## Required questions

| # | Question | Brief field | If not asked |
|---|----------|-------------|--------------|
| 1 | What are we selling / what service? | `niche`, `service` | Cannot write copy |
| 2 | Where are the customers? (city, Russia, online) | `geo` | Wordstat/SERP without region |
| 3 | USP: deadline, price, service details, constraints | `business_description` | Only generic phrases in COPY |
| 4 | Keywords or "choose by niche" | `keywords` / empty → Wordstat | Ask about intent priority |
| 5 | Site base URL | `base_url` | UTM and page_add impossible |
| 6 | Yandex Direct / Google | `utm_source` | default `yandex` |
| 7 | Yandex Direct: test or full API access? | `yandex_direct_access` | See branching below |
| 8 | One landing or separate per segment/ad? | `landing_scale`, `max_segments` | default multi, 3–5 |

---

## Yandex Direct API access (`yandex_direct_access`)

**Ask explicitly** if the task involves pushing ads to Yandex Direct or fetching ads from an account:

```markdown
What is the status of your application in Yandex Direct → Settings → API → "My Applications"?
- **Test** — sandbox only (`api-sandbox`), live campaigns are unavailable.
- **Full** — real account (`api.direct`) + sandbox for debugging.
- **No application yet / don't know** — first OAuth and application (`DIRECT_API.md`), defer push.
```

| User's answer | `brief.json` | Env / agent behavior |
|---------------|-------------|----------------------|
| Test / sandbox only | `"test"` | `YANDEX_DIRECT_USE_SANDBOX=1` in `.env.local`; verify token against `api-sandbox.direct.yandex.com`; sandbox **enabled** in UI |
| Full access | `"full"` | Remove `YANDEX_DIRECT_USE_SANDBOX` or set to `0`; host `api.direct.yandex.com` |
| No token / no application | `"none"` | Do not call Yandex Direct API; provide `DIRECT_API.md` |
| Doesn't know | `"unknown"` | Ask for screenshot of "My Applications" or verify token via `curl` per `DIRECT_API.md` |

**Test access — agent restrictions:**

- Do not promise ad impressions and ads in the web UI (sandbox is not visible in UI).
- Before pushing to sandbox: verify sandbox has ad groups via `curl` per `DIRECT_API.md`, or confirm `adgroups.add` works.
- If no groups (known sandbox bug) — tell the user: recreate "test campaigns" in UI or apply for **full access**; locally continue with landings + UTM + **Google** push.
- **Google Ads** is **independent** of Yandex Direct mode — can run in parallel.

Details: `DIRECT_API.md` (section "Agent modes").

---

## Landing scale (`landing_scale`)

**Ask explicitly** if the task involves landings or publishing on uCoz:

```markdown
How many landing pages are we creating?
- **One** — a single page for all ads (simpler, worse message match if there are multiple creatives).
- **Hyper-segmentation** — separate URL and H1 = headline per ad/segment (recommended by default).
- **Exact number** — if multi: 1, 3, 5, 7, or "match the number of ads in CSV".
```

| Answer | `landing_scale` | `max_segments` | Actions |
|--------|-----------------|----------------|---------|
| One landing | `"single"` | `1` | One `page_add`; one segment in `segments.json`; warn if ads > 1 |
| Separate per ad | `"multi"` | number or `5` | Generate HTML + `page_add` **per** segment; check message match per `COPY.md` |
| Match CSV rows | `"match_ads"` | = number of ads | Build `segments.json` from all CSV rows without truncating to `max_segments` |
| "Default as in skill" | `"multi"` | `5` | 3–7 segments after research |

**Skill rule:** do not output a single `final_url` for all ads on **multi** without user consent (`SKILL.md` prohibitions).

**Push to ads:**

- `landing_scale: single` → one group/ad or one campaign; one shared landing URL.
- `landing_scale: multi` / `match_ads` → separate campaign or ad group per segment if needed — confirm with user before API push.

---

## Clarifying questions (ask as relevant)

| Question | Field | Why |
|----------|-------|-----|
| Are there ready-made ads or CSV? | `ads_csv` | Branch 2A — no Wordstat for creatives |
| Are ads already in Yandex Direct/Google account? | — | **ADS.md §Fetch** → select → to-segment → landing → **ADS.md §Sync** |
| How many segments at once? (3–7) | `max_segments` | Mirrors `landing_scale`; don't ask again if already answered in item 8 |
| Is a lead form needed on the landing? | `need_form` | LANDING_PLAYBOOK, mail_* |
| Show **price/deadline** on site and in ads? | affects COPY | See `COPY.md` — "if no price" |
| Publish pages to uCoz now? | `publish_to_ucoz` | MCP page_add or text only |
| Site homepage or only new URLs? | agent behavior | default: **only** `page_add`, not `page_id=1` |
| Keyword priority if > 10? | seed order | Which 3–5 intents matter most |
| Negative keywords / what we don't sell? | `suggested_minus_words` | Filter out noise; pushed to campaign |
| Brand, tone (you/formal), copy restrictions | in copywriting prompt | `COPY.md` |
| Logo/colors for the landing? | LANDING_PLAYBOOK step 0 | Design |

### Clarifying questions — for **Google Ads push** only

If the user wants a Google Ads push (network = google), ask before building the API payload:

| # | Agent question | Payload field | If not answered |
|---|---------------|-----------------|-----------------|
| 1 | **Geo:** which country / city to target? (Russia, Moscow, region, other) | geo target | Takes from `geo` in brief, otherwise no restriction |
| 2 | **Audience language?** (ru, en, ka...) | language | default `ru` |
| 3 | **Ad schedule?** (Mon–Fri 9–18, 24/7...) | schedule | 24/7 |
| 4 | **Search partners** (Google partner search networks)? | search partners | Off |
| 5 | **Display Network** (banner ads on sites)? | content network | Off |
| 6 | **Negative keywords** — already in `suggested_minus_words` of segments; confirm adding? | negative keywords | Added automatically |

If the user answers "Georgia, Russian, Mon–Fri 9–18, no partners", the agent includes these in the payload per **GOOGLE_ADS.md**, shows it to the user, and waits for explicit approval before `curl`.

**Don't ask unnecessary questions:** if the user provided a detailed brief — go straight to `brief.json` and "Please confirm: …".

---

## Branching after answers

```
yandex_direct_access?
├── none / unknown → DIRECT_API.md, no API push
├── test → sandbox env, verify sandbox before push/list
└── full → api.direct, push via curl per DIRECT_API.md (with user approval)

landing_scale?
├── single → max_segments=1, one page_add, warning if ads > 1
├── multi → 3–7 segments, page_add each, message match
└── match_ads → segments = ads from CSV
```

```
Ready-made ads (CSV/text)?
├── YES → PIPELINE.md §2A → build segments.json → generate HTML → page_add (each segment)
└── NO  → keywords present?
         ├── YES → RESEARCH.md + PIPELINE.md §2B
         └── NO  → "I'll pick seeds by niche, ok?" → Wordstat after brief
```

```
publish_to_ucoz / "publish on site"?
├── YES → MCP ucoz-mcp, LANDING_PLAYBOOK
└── NO  → final report per OUTPUT.md + segments.json only, no final_url "as on site"
```

---

## Minimal brief from a single user message

Example user input:

> Custom songs, Moscow, site https://mysite.ucoz.ru, keywords: wedding song, order a song. 3 days, from 15k. Yandex Direct.

Agent:

1. Confirms understanding in one paragraph.
2. Clarifies only gaps: **form?** **price on landing?** **publish pages?**
3. Writes `brief.json` and proceeds per **PIPELINE.md**.

---

## Pre-work checklist

```
[ ] niche, service, geo, business_description filled
[ ] base_url for UTM (if package with links needed)
[ ] keywords or consent "choose seeds"
[ ] yandex_direct_access (test | full | none) — if Yandex Direct API needed
[ ] landing_scale + max_segments — if landings needed
[ ] Branch 2A/2B selected
[ ] need_form / price on site / publish — explicit or by default
[ ] brief.json saved (not just in chat)
[ ] env: YANDEX_DIRECT_USE_SANDBOX aligned with yandex_direct_access
```

---

## Links to other files

| Stage | Document |
|-------|----------|
| Research | `RESEARCH.md` |
| Copy | `COPY.md` |
| Pipeline | `PIPELINE.md` |
| Publishing | `LANDING_PLAYBOOK.md` |

---

## §brief.json schema

Template: `templates/brief.example.json`.

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `niche` | string | yes | Niche |
| `service` | string | yes | Service / product |
| `geo` | string | yes | Geo for UTM and Wordstat |
| `business_description` | string | yes | USP, timelines, prices, constraints |
| `keywords` | string[] | yes* | Keywords; *if empty — only `wordstat_seeds` |
| `wordstat_seeds` | string[] | no | Up to **5** seed phrases for API |
| `region_ids` | string[] | null | Explicit IDs (`213` Moscow, `225` Russia). Takes priority over `geo` |
| `base_url` | string | yes for UTM | `https://site.ucoz.ru` without trailing slash |
| `utm_source` | string | no | `yandex` / `google` |
| `utm_campaign` | string | no | null = auto `{niche}_{geo}_{date}` |
| `ads_csv` | string | no | Ads CSV → branch 2A |
| `max_segments` | number | no | Limit 3–7, default 5 |
| `publish_to_ucoz` | bool | no | Hint for agent to publish via MCP |
| `need_form` | bool | no | Form on the landing |
| `utm_both_networks` | bool | no | `true` → UTM for both Yandex Direct + Google |
| `yandex_direct_access` | string | no* | `test` \| `full` \| `none` \| `unknown`; *required before API |
| `landing_scale` | string | no* | `single` \| `multi` \| `match_ads`; *required before page_add |

### `yandex_direct_access` → behavior

| Value | API host | Agent |
|-------|----------|-------|
| `test` | `api-sandbox.direct.yandex.com` | Do not promise live account |
| `full` | `api.direct.yandex.com` | Real campaigns |
| `none` | — | Only `DIRECT_API.md`, no push |
| `unknown` | Check token | Ask user |

### `landing_scale` → scope of work

| Value | `max_segments` | Publishing |
|-------|----------------|------------|
| `single` | `1` | One `page_add`, one `final_url` |
| `multi` | `3`–`7` (default 5) | `page_add` per segment |
| `match_ads` | = number of CSV rows | All rows → segments |
