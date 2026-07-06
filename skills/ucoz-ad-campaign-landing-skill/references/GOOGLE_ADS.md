# Google Ads API

Authorization, push, fetch, errors. Official docs: [First API call](https://developers.google.com/google-ads/api/docs/first-call/overview) · [OAuth](https://developers.google.com/google-ads/api/docs/oauth/overview)

---

## §What you need (env variables)

| Variable | Source |
|----------|--------|
| `GOOGLE_ADS_DEVELOPER_TOKEN` | Google Ads → Tools → **API Center** |
| `GOOGLE_ADS_CUSTOMER_ID` | Ad account ID (10 digits, no dashes) |
| `GOOGLE_ADS_CLIENT_ID` | Google Cloud Console → Credentials → OAuth client |
| `GOOGLE_ADS_CLIENT_SECRET` | Same place |
| `GOOGLE_ADS_REFRESH_TOKEN` | OAuth Playground |
| `GOOGLE_ADS_LOGIN_CUSTOMER_ID` | Only for MCC / manager account |

---

## §OAuth setup (step by step)

### Step 1 — Developer token

1. [Google Ads](https://ads.google.com/) → **Tools & settings** → **API Center** → request token.
2. Access levels:
   - **Test** — available immediately, but works **only with test** ad accounts.
   - **Basic** — requires application, a few days. Opens live accounts.

> **Trap:** a regular Google Ads account will be **inaccessible** with a Test token. If you need a live account — apply for Basic access.

### Step 2 — Google Cloud Console (OAuth app)

1. [console.cloud.google.com](https://console.cloud.google.com/) → create project → **APIs & Services → Library → Google Ads API → Enable**.
2. **OAuth consent screen**:
   - Audience: **External**.
   - Fill in App name, email.
   - **Scopes**: add `https://www.googleapis.com/auth/adwords`.
   - **Test users**: add the Gmail account you use for Google Ads.
3. **Credentials → + Create Credentials → OAuth client ID**:
   - Type: **Web application**.
   - **Authorized redirect URIs**: add `https://developers.google.com/oauthplayground`.
   - Copy **Client ID** and **Client secret**.

```env
GOOGLE_ADS_CLIENT_ID=123456789-xxxx.apps.googleusercontent.com
GOOGLE_ADS_CLIENT_SECRET=GOCSPX-...
```

### Step 3 — Refresh token (OAuth Playground)

1. Open [OAuth 2.0 Playground](https://developers.google.com/oauthplayground/).
2. ⚙️ → **Use your own OAuth credentials** → paste Client ID and Secret.
3. **Step 1**: enter scope `https://www.googleapis.com/auth/adwords` → **Authorize APIs**.
4. Log in with the account that has Google Ads access → **Allow**.
5. **Step 2** → **Exchange authorization code for tokens** → copy **Refresh token**.

```env
GOOGLE_ADS_REFRESH_TOKEN=1//0g...
GOOGLE_ADS_DEVELOPER_TOKEN=xsLwgnr1fHf1slJgfx9T3w
GOOGLE_ADS_CUSTOMER_ID=6097804519
```

### Step 4 — Verification

```bash
# Get access token:
curl -s -X POST https://oauth2.googleapis.com/token \
  -d "client_id=$GOOGLE_ADS_CLIENT_ID&client_secret=$GOOGLE_ADS_CLIENT_SECRET&refresh_token=$GOOGLE_ADS_REFRESH_TOKEN&grant_type=refresh_token"
# Expected: {"access_token": "...", "expires_in": 3599}
```

---

## §Push: create campaign from segments.json

**Only after explicit user permission.** Agent shows payload and asks for confirmation.

Campaign is created as **PAUSED** — no charges until manually enabled.

### Get access token

```bash
ACCESS_TOKEN=$(curl -s -X POST https://oauth2.googleapis.com/token \
  -d "client_id=$GOOGLE_ADS_CLIENT_ID&client_secret=$GOOGLE_ADS_CLIENT_SECRET&refresh_token=$GOOGLE_ADS_REFRESH_TOKEN&grant_type=refresh_token" \
  | python -c "import sys,json; print(json.load(sys.stdin)['access_token'])")
```

### Create campaign

```bash
curl -s -X POST \
  "https://googleads.googleapis.com/v18/customers/$GOOGLE_ADS_CUSTOMER_ID/campaigns:mutate" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "developer-token: $GOOGLE_ADS_DEVELOPER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "operations": [{
      "create": {
        "name": "ucoz_niche_moscow_20260607",
        "advertisingChannelType": "SEARCH",
        "status": "PAUSED",
        "manualCpc": {},
        "campaignBudget": "customers/<CUSTOMER_ID>/campaignBudgets/<BUDGET_ID>"
      }
    }]
  }'
```

### RSA limits (Google)

| Field | Limit |
|-------|-------|
| Headlines | 3–15, ≤30 characters each |
| Descriptions | 2–4, ≤90 characters |

---

## §Agent parameters for building payload

| Parameter | Default | Description |
|-----------|---------|-------------|
| `daily-budget` | 1000 ₽ | Daily budget |
| `cpc` | 10.0 ₽ | Group bid |
| `status` | PAUSED | Always PAUSED on creation |
| `geo-target` | from `brief.geo` | Country/city |
| `language` | ru | Audience language |

---

## §Errors

| Symptom | Solution |
|---------|---------|
| `DEVELOPER_TOKEN_PROHIBITED` | Token not approved / Test vs Production mismatch |
| `AUTHENTICATION_ERROR` | Check refresh_token, client_id/secret |
| `USER_PERMISSION_DENIED` | Customer ID or login_customer_id is incorrect |
| `CUSTOMER_NOT_ENABLED` | Account not activated / no billing |

---

## §Comparison with Yandex Direct

| | Yandex Direct | Google Ads |
|--|---------------|------------|
| Approval | Application up to 7 days | Developer token (test is faster) |
| OAuth scope | `direct:api` | `adwords` |
| Default status | SUSPENDED | PAUSED |

---

## §Pipeline order

```
page_add → UTM (PIPELINE.md §6) → [show payload → permission] → push (PAUSED)
  → user tops up billing → enables manually → statistics
```

Fetch and sync ads: **[ADS.md §Fetch and §Sync](ADS.md)**.
