# Yandex Direct API v5 — authorization and quick start

The agent works with the **Yandex Direct API v5** directly via Shell (`curl`).

The token **is not the same** as `YANDEX_SEARCH_API_KEY` (Wordstat / Search API in Yandex Cloud) — a separate **OAuth token** of the Direct user is required.

Official documentation:
- [Application registration](https://yandex.ru/dev/direct/doc/ru/concepts/register)
- [Authorization tokens](https://yandex.ru/dev/direct/doc/ru/concepts/auth-token)

---

## For the agent: when the user has no `YANDEX_DIRECT_TOKEN`

If the variable is not set — **do not run the push**; give the user the instruction below (briefly, with a link to this file).

**What to tell the user (template):**

> To upload campaigns to Direct you need the OAuth token `YANDEX_DIRECT_TOKEN`. Add it to `.env.local`.
>
> **In short:**
> 1. Register an application in [Yandex ID](https://oauth.yandex.ru/client/new) → type **“For API access or debugging”**.
> 2. In the **“Data access”** block, add the permission **“Using the Yandex Direct API”** (`direct:api`) — without it the API will not work.
> 3. In [Direct API settings](https://direct.yandex.ru/registered/main.pl) create an **application** (full or test access), specify the application Client ID, and wait for the status “approved”.
> 4. Accept the API **user agreement** in the Direct account (API tab).
> 5. Get a token via the authorization link (see “Step 4” below) and add it to `.env.local`:
>    `YANDEX_DIRECT_TOKEN=...`
>
> Details: `references/DIRECT_API.md`

---

## Instructions for the user (obtaining a token)

### What you will need

| What | Why |
|-----|--------|
| A Yandex login with access to **Yandex Direct** | The token is issued on behalf of this user |
| At least **one campaign** in the Direct web interface | Otherwise the “API settings” page will not open |
| A registered application in **Yandex ID** | Client ID for the application and for OAuth |
| An **approved application** for Direct API access | Without approval, API requests are rejected |

---

### ⚠️ Required: permissions in Yandex ID (do not confuse with the application in Direct)

The **“Approved”** status is checked in **Direct** → Settings → API → “My applications”.
In **Yandex ID** (oauth.yandex.ru) there is **no** application status — only **application permissions**.

| Where | What to configure |
|-----|----------------|
| **Yandex ID** → application → “Data access” | At minimum **`direct:api`** (“Using the Yandex Direct API”) |
| **Yandex Direct** → Settings → API | An application with your **Client ID** + the offer |

**Without `direct:api`:** an OAuth token may be issued, but the Direct API will not work as required.

**If the permission was added later** — the old token does not pick up the new scope. You need to go through OAuth again (Step 4 below) and update `YANDEX_DIRECT_TOKEN` in `.env.local`.

| API error | Common cause |
|------------|----------------|
| **58** Incomplete registration | There is no **approved application** in Direct for this Client ID |
| **53** AccessDenied | The API **offer** has not been accepted in the Direct account |
| Token exists, 58 remains | The application is for a **different** Client ID, or the token predates adding `direct:api` |

`passport:business` (Yandex ID organizations) is **not required** for the skill — **`direct:api`** is enough.

---

### Step 1 — Application in Yandex ID

1. Open [Create an application](https://oauth.yandex.ru/client/new) or the [application list](https://oauth.yandex.ru/).
2. Choose the type: **“For API access or debugging”** (not “Web services” for signing in to a site, if the only goal is Direct).
3. Fill in the name and a contact email.
4. **“Data access”** block → add:

   **Using the Yandex Direct API** — **`direct:api`** (required)

   > Wordstat (`YANDEX_SEARCH_API_KEY`) is a separate key in Yandex Cloud, not this permission.

5. Save the application.
6. Save the **Client ID** and **Client secret** in `.env.local`:

```env
YANDEX_DIRECT_CLIENT_ID=05c89462fef4400eb3493c4f67d9ceea
YANDEX_DIRECT_CLIENT_SECRET=...
```

Application list: [oauth.yandex.ru](https://oauth.yandex.ru/).

More: [Application registration — Yandex ID](https://yandex.ru/dev/direct/doc/ru/concepts/register#registraciya-na-servise-yandeks-id).

---

### Step 2 — Application for Direct API access

Without an approved application a token may be issued, but API calls will be rejected.

1. Sign in to [Yandex Direct](https://direct.yandex.ru/) with the same login that will own the campaigns.
2. Open **Settings → API** (or the [applications page](https://direct.yandex.ru/registered/main.pl)).
3. On the first visit — **accept the API user agreement**.
4. **“New application”** → choose the type:
   - **Full access** — manage real campaigns + sandbox;
   - **Test access** — [sandbox](https://yandex.ru/dev/direct/doc/ru/concepts/sandbox) only (for debugging without production impressions).
5. Specify the **Client ID** from step 1, a current email, and an application description.
6. Submit the application. Review takes **up to 7 business days**; check the status on the **“My applications”** tab.

### After a **test** application is approved

| Status in the account | API host |
|-------------------|----------|
| **approved** + **test** access | `api-sandbox.direct.yandex.com` |
| **approved** + **full** access | `api.direct.yandex.com` |

In `.env.local` for a test application:

```env
YANDEX_DIRECT_USE_SANDBOX=1
```

**The sandbox must be enabled manually:** Direct → Settings → API → **“Sandbox”** tab → **“Start the sandbox”**. Without this, error **513** (“логин не подключен” / login is not connected).

**Sandbox limitation (June 2026):** `campaigns.add` creates campaigns (status DRAFT), but `adgroups.add` may return a group Id that **does not appear** in `adgroups.get` — keywords and ads then fail with “Группа не найдена” / “Group not found”. Workaround: in the sandbox UI **“Create test campaigns”**, or a **“Full access”** application and remove `YANDEX_DIRECT_USE_SANDBOX`.

More: [Creating an application for API access](https://yandex.ru/dev/direct/doc/ru/concepts/register#create-application).

---

## Modes for the agent (`yandex_direct_access`)

Before an API push the agent **asks the user** and writes the value to `brief.json` (see **INTAKE.md**).

| `yandex_direct_access` | Agent actions |
|------------------------|-----------------|
| **test** | Host `api-sandbox.direct.yandex.com`; make sure the sandbox is enabled in the UI; landings/UTM/Google — allowed |
| **full** | Host `api.direct.yandex.com`; curl against the production account (campaigns are created SUSPENDED) |
| **none** | Give the OAuth instructions from this file; do not call the API |
| **unknown** | Ask the user to check the “My applications” status in the Direct account |

**Do not mix them:** with a test application, requests to `api.direct` return **58**; with full access on the sandbox host, you end up in an empty sandbox.

### Organization mode of the account

A working OAuth token and a successful `clients.get` do not guarantee access to campaigns opened in the browser in organization mode.

Before changing anything:

1. run read-only `clients.get` and `campaigns.get`;
2. record the client login, available roles, and the verified `Client-Login`;
3. compare the specific campaign_id with the browser account and the `ulogin` parameter;
4. if the API does not return a campaign, do not treat it as missing and do not try random logins;
5. go to `DIRECT_BROWSER_AND_PACKAGES.md` or obtain officially confirmed representation.

Store the token only in a local gitignored env file. Do not copy the token into evidence, commands, reports, or the skill package.

---

### Step 3 — API offer in the account (if not yet accepted)

Yandex Direct → **Settings → API** → **“Accept the terms of use”**.

Without this, **error_code 53** (AccessDenied) is common.

---

### Step 4 — Get an OAuth token

After the **application is approved**, get a token for your Client ID.

**Option A — in the browser (for a personal / agency account):**

1. Substitute your Client ID into the link (`token` type — the token is in the address bar after the redirect):

```
https://oauth.yandex.ru/authorize?response_type=token&client_id=YOUR_CLIENT_ID
```

2. Sign in with the Direct login → **Allow** the application access.
3. Copy the **`access_token`** value from the URL after `#` (up to `&`).

**Option B — a code to exchange (if a redirect URI is configured):**

See [Authorization tokens](https://yandex.ru/dev/direct/doc/ru/concepts/auth-token) — `response_type=code` and a server-side exchange.

**Agency account:** if the application acts on behalf of an agency client, also set the advertiser login in env:

```
YANDEX_DIRECT_CLIENT_LOGIN=login-reklamodatelya
```

---

### Step 5 — Write the token to `.env.local`

```env
# Yandex Direct API (OAuth token of the Direct user)
YANDEX_DIRECT_TOKEN=AQAAAAxxxxxxxxxxxxxxxx
# Agency only — client login:
# YANDEX_DIRECT_CLIENT_LOGIN=client-login
```

`.env.local` is in `.gitignore` — **do not commit** the token.

---

### Step 6 — Check

The agent checks the token with a curl request to the API:

```bash
curl -s -X POST \
  -H "Authorization: Bearer $YANDEX_DIRECT_TOKEN" \
  -H "Content-Type: application/json; charset=utf-8" \
  https://api.direct.yandex.com/json/v5/campaigns \
  -d '{"method":"get","params":{"SelectionCriteria":{},"FieldNames":["Id","Name"],"Page":{"Limit":1}}}'
```

Expected: `{"result": {"Campaigns": [...]}}`. Errors 53/58 — see the error table above.

---

## Quick start (curl)

Full curl commands for fetch, push, and URL sync are in **[ADS.md §Fetch and §Sync](ADS.md)**.

### Dry-run (show the payload without sending)

The agent first does a read-only account check, shows the exact IDs and the JSON payload, then asks for confirmation. Only after an explicit “Yes” does it run curl.

### Real push (the campaign is created SUSPENDED)

See: **[ADS.md §Sync](ADS.md)** — the full cycle “fetch → segment → landing → update URL”.

After a successful curl, `direct.campaign_id`, `group_id`, and `ad_id` are written to the segment.

---

## API push parameters (curl)

When building the curl payload, the agent uses these parameters from `brief.json` and `segments.json`:

| Parameter | Default | Description |
|----------|-------------|----------|
| `campaign.Name` | `niche \| geo` | Campaign name |
| `campaign.StartDate` | today | YYYY-MM-DD |
| `dailyBudget.Amount` | 1000 | Daily budget, ₽ (in micro-units ×1 000 000) |
| `strategy.avgCpc` | 10.0 | Average cost per click, ₽ |
| `campaign.GeoTargeting` | `russia` | Region alias or ID |
| Status | `SUSPENDED` | The campaign is always created suspended |

### Regions (aliases)

| Alias | ID |
|-------|-----|
| `russia` | 225 |
| `moscow` | 1 |
| `spb` | 2 |
| `ekb` | 54 |
| `kazan` | 43 |

---

## Typical errors

| Symptom | Cause | What to do |
|---------|---------|-------------|
| No `YANDEX_DIRECT_TOKEN` | Not added to `.env.local` | Instructions above, steps 1–6 |
| HTTP 401 | Expired or invalid token | Get a new token (step 4) |
| error_code **53** | API offer not accepted | Step 3 |
| error_code **58** | No rights to someone else’s account | `YANDEX_DIRECT_CLIENT_LOGIN` |
| Application not approved | API is closed for the Client ID | Wait for “approved” in “My applications” |
| No `direct:api` permission on the application | Wrong scope in Yandex ID | Step 1 — add **Using the Yandex Direct API** |
| No `final_url` | Landing is not published | `page_add` → UTM → then push |

---

## Order in the pipeline

```
page_add → UTM (PIPELINE.md §6) → [show payload → permission] → curl push (SUSPENDED)
  → the user tops up the account → enables it manually → statistics
```

**Rule:** do not run a push without `final_url`.
