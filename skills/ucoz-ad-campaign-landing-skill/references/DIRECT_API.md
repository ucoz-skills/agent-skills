# Yandex Direct API v5 — Authorization and Quick Start

The agent works directly with **Yandex Direct API v5** via Shell (`curl`).

The token is **not the same** as `YANDEX_SEARCH_API_KEY` (Wordstat / Search API in Yandex Cloud) — a separate **OAuth token** for the Yandex Direct user account is required.

Official documentation:
- [Application registration](https://yandex.ru/dev/direct/doc/ru/concepts/register)
- [Authorization tokens](https://yandex.ru/dev/direct/doc/ru/concepts/auth-token)

---

## For the agent: when the user doesn't have `YANDEX_DIRECT_TOKEN`

If the variable is not set — **do not push**, but provide the user with the instructions below (briefly, with a link to this file).

**What to tell the user (template):**

> To upload campaigns to Yandex Direct you need an OAuth token `YANDEX_DIRECT_TOKEN`. Add it to `.env.local`.
>
> **Quick guide:**
> 1. Register an app at [Yandex ID](https://oauth.yandex.ru/client/new) → type **"For API access or debugging"**.
> 2. In the **"Data access"** block add permission **"Yandex.Direct API usage"** (`direct:api`) — without it the API won't work.
> 3. In [Yandex Direct API Settings](https://direct.yandex.ru/registered/main.pl) create an **application** (full or test access), specify the Client ID, wait for status "approved".
> 4. Accept the **API terms of service** in Yandex Direct account (API tab).
> 5. Get a token via the authorization link (see "Step 4" below) and add to `.env.local`:
>    `YANDEX_DIRECT_TOKEN=...`
>
> Full details: `references/DIRECT_API.md`

---

## User instructions (obtaining a token)

### What you'll need

| What | Why |
|------|-----|
| Yandex login with access to **Yandex Direct** | Token is issued on behalf of this user |
| At least **one campaign** in Yandex Direct web interface | Otherwise the "API Settings" page won't open |
| Registered app in **Yandex ID** | Client ID for the application and OAuth |
| **Approved application** for Yandex Direct API access | Without approval, API requests are rejected |

---

### ⚠️ Required: permissions in Yandex ID (don't confuse with the application in Yandex Direct)

The **"Approved"** status is checked in **Yandex Direct** → Settings → API → "My Applications".  
In **Yandex ID** (oauth.yandex.ru) there is **no** application status — only **app permissions**.

| Where | What to configure |
|-------|------------------|
| **Yandex ID** → app → "Data access" | At minimum **`direct:api`** ("Yandex.Direct API usage") |
| **Yandex Direct** → Settings → API | Application with your **Client ID** + terms of service |

**Without `direct:api`:** an OAuth token may be issued, but the Yandex Direct API won't work properly.

**If the permission was added later** — the old token doesn't pick up the new scopes. You need to go through OAuth again (Step 4 below) and update `YANDEX_DIRECT_TOKEN` in `.env.local`.

| API error | Common cause |
|-----------|--------------|
| **58** Incomplete registration | No **approved application** in Yandex Direct for this Client ID |
| **53** AccessDenied | Terms of service **not accepted** in Yandex Direct account |
| Token exists, 58 persists | Application is on a **different** Client ID or token was created before adding `direct:api` |

`passport:business` (Yandex ID organizations) is **not required** for the skill — **`direct:api`** is sufficient.

---

### Step 1 — App in Yandex ID

1. Open [Create application](https://oauth.yandex.ru/client/new) or [app list](https://oauth.yandex.ru/).
2. Choose type: **"For API access or debugging"** (not "Web services" for site login, if the goal is only Yandex Direct).
3. Fill in the name and contact email.
4. **"Data access"** block → add:

   **Yandex.Direct API usage** — **`direct:api`** (required)

   > Wordstat (`YANDEX_SEARCH_API_KEY`) — a separate key in Yandex Cloud, not this permission.

5. Save the application.
6. Save the **Client ID** and **Client secret** in `.env.local`:

```env
YANDEX_DIRECT_CLIENT_ID=05c89462fef4400eb3493c4f67d9ceea
YANDEX_DIRECT_CLIENT_SECRET=...
```

App list: [oauth.yandex.ru](https://oauth.yandex.ru/).

More details: [App registration — Yandex ID](https://yandex.ru/dev/direct/doc/ru/concepts/register#registraciya-na-servise-yandeks-id).

---

### Step 2 — Application for Yandex Direct API access

Without an approved application, a token may be issued but API calls will return an error.

1. Log in to [Yandex Direct](https://direct.yandex.ru/) under the same login that will own the campaigns.
2. Open **Settings → API** (or [applications page](https://direct.yandex.ru/registered/main.pl)).
3. On first visit — **accept the API terms of service**.
4. **"New application"** → select type:
   - **Full access** — manage real campaigns + sandbox;
   - **Test access** — [sandbox](https://yandex.ru/dev/direct/doc/ru/concepts/sandbox) only (for debugging without live impressions).
5. Enter the **Client ID** from step 1, current email, application description.
6. Submit the application. Review time — **up to 7 business days**, check status in the **"My Applications"** tab.

### After **test** application is approved

| Status in account | API host |
|-------------------|----------|
| **approved** + **test** access | `api-sandbox.direct.yandex.com` |
| **approved** + **full** access | `api.direct.yandex.com` |

In `.env.local` for test application:

```env
YANDEX_DIRECT_USE_SANDBOX=1
```

**Sandbox must be enabled manually:** Yandex Direct → Settings → API → **"Sandbox"** tab → **"Start sandbox"**. Without this, error **513** ("login not connected").

**Sandbox limitation (June 2026):** `campaigns.add` creates campaigns (DRAFT status), but `adgroups.add` may return a group ID that **does not appear** in `adgroups.get` — keywords and ads then fail with "Group not found". Workaround: in the sandbox UI **"Create test campaigns"**, or apply for **Full access** and remove `YANDEX_DIRECT_USE_SANDBOX`.

More details: [Creating an API access application](https://yandex.ru/dev/direct/doc/ru/concepts/register#create-application).

---

## Agent modes (`yandex_direct_access`)

Before an API push the agent **asks the user** and writes to `brief.json` (see **INTAKE.md**).

| `yandex_direct_access` | Agent actions |
|------------------------|---------------|
| **test** | Host `api-sandbox.direct.yandex.com`; ensure sandbox is enabled in UI; landings/UTM/Google — allowed |
| **full** | Host `api.direct.yandex.com`; curl to live account (campaigns created as SUSPENDED) |
| **none** | Provide OAuth instructions from this file; do not call API |
| **unknown** | Ask user to check "My Applications" status in Yandex Direct account |

**Do not mix:** with a test application, requests to `api.direct` will give **58**; with full access using the sandbox host — you'll hit an empty sandbox.

---

### Step 3 — API terms of service in account (if not yet accepted)

Yandex Direct → **Settings → API** → **"Accept terms of use"**.

Without this, **error_code 53** (AccessDenied) is common.

---

### Step 4 — Get OAuth token

After **application approval**, get a token for your Client ID.

**Option A — in browser (for personal / agency account):**

1. Substitute your Client ID in the URL (type `token` — token in address bar after redirect):

```
https://oauth.yandex.ru/authorize?response_type=token&client_id=YOUR_CLIENT_ID
```

2. Log in with Yandex Direct login → **Allow** access to the app.
3. Copy the **`access_token`** value from the URL after `#` (up to `&`).

**Option B — authorization code (if redirect URI is configured):**

See [Authorization tokens](https://yandex.ru/dev/direct/doc/ru/concepts/auth-token) — `response_type=code` and server-side exchange.

**Agency account:** if the app acts on behalf of an agency client, additionally specify the advertiser login in env:

```
YANDEX_DIRECT_CLIENT_LOGIN=advertiser-login
```

---

### Step 5 — Save token to `.env.local`

```env
# Yandex Direct API (user's OAuth token)
YANDEX_DIRECT_TOKEN=AQAAAAxxxxxxxxxxxxxxxx
# Agency only — client login:
# YANDEX_DIRECT_CLIENT_LOGIN=client-login
```

`.env.local` in `.gitignore` — **do not commit** the token.

---

### Step 6 — Verification

Agent verifies the token with a curl request to the API:

```bash
curl -s -X POST \
  -H "Authorization: Bearer $YANDEX_DIRECT_TOKEN" \
  -H "Content-Type: application/json; charset=utf-8" \
  https://api.direct.yandex.com/json/v5/campaigns \
  -d '{"method":"get","params":{"SelectionCriteria":{},"FieldNames":["Id","Name"],"Page":{"Limit":1}}}'
```

Expected: `{"result": {"Campaigns": [...]}}`. Error 53/58 — see error table above.

---

## Quick start (curl)

Full curl commands for fetch, push, sync URL — in **[ADS.md §Fetch and §Sync](ADS.md)**.

### Dry-run (show payload without sending)

Agent shows JSON payload and asks for confirmation. Only after explicit "Yes" — executes curl.

### Real push (campaign created as SUSPENDED)

See: **[ADS.md §Sync](ADS.md)** — full cycle "fetch → segment → landing → update URL".

After a successful curl, `direct.campaign_id`, `group_id`, `ad_id` are recorded in the segment.

---

## API push parameters (curl)

When building the curl payload, the agent uses these parameters from `brief.json` and `segments.json`:

| Parameter | Default | Description |
|-----------|---------|-------------|
| `campaign.Name` | `niche \| geo` | Campaign name |
| `campaign.StartDate` | today | YYYY-MM-DD |
| `dailyBudget.Amount` | 1000 | Daily budget, ₽ (in micro-units ×1,000,000) |
| `strategy.avgCpc` | 10.0 | Average CPC, ₽ |
| `campaign.GeoTargeting` | `russia` | Alias or region ID |
| Status | `SUSPENDED` | Campaign always created as suspended |

### Regions (aliases)

| Alias | ID |
|-------|-----|
| `russia` | 225 |
| `moscow` | 1 |
| `spb` | 2 |
| `ekb` | 54 |
| `kazan` | 43 |

---

## Common errors

| Symptom | Cause | What to do |
|---------|-------|------------|
| No `YANDEX_DIRECT_TOKEN` | Not added to `.env.local` | Instructions above, steps 1–6 |
| HTTP 401 | Expired or invalid token | Get new token (step 4) |
| error_code **53** | Terms of service not accepted | Step 3 |
| error_code **58** | No permission for another account | `YANDEX_DIRECT_CLIENT_LOGIN` |
| Application not approved | API closed for Client ID | Wait for "approved" in "My Applications" |
| No `direct:api` permission in app | Wrong scope in Yandex ID | Step 1 — add **Yandex.Direct API usage** |
| No `final_url` | Landing not published | `page_add` → UTM → then push |

---

## Pipeline order

```
page_add → UTM (PIPELINE.md §6) → [show payload → permission] → curl push (SUSPENDED)
  → user tops up account → enables manually → statistics
```

**Rule:** do not push without `final_url`.
