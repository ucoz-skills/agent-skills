# Visitor identity: `ucoz_user()` and the visitor key

Sources: uCoz Help Center, "Server Scripts on uCoz → Built-in uCoz functions"
(https://www.ucoz.ru/help/tools/servernye-skripty-na-ucoz, in Russian), plus the platform helper sources and
live tests on a Server Scripts account (October 2026). Everything below marked **(verified)** was
observed live.

## 1. How it works

1. The site has a **visitor key** (32 bytes), managed with `apps_write` / `apps_delete` `visitor_key_*` (Control Panel:
   Server Scripts → data encryption key). The platform stores it in
   `~/.ucoz/conf/ucoz.conf` as `UCOZ_SITEUSER_ENCRYPT_KEY=<base64url>` **(verified)**. Never read, edit,
   or print this file — use the `visitor_key_*` actions.
2. For every request to `https://{site}/apps/…`, uCoz's nginx adds the **`X-Ucoz-User`** header:
   `base64url(nonce[12] ‖ ciphertext ‖ tag[16])`, AES-256-GCM, a JSON payload with an `exp` timestamp.
3. `ucoz_user()` decrypts and authenticates the header with the key, rejects it if `exp` has passed, and
   returns the profile. Guest, missing key, tampering, expiry, or any error → an empty result. The function
   **never throws** **(verified for guests in PHP, Node, Python)**.

Consequences:
- The header cannot be forged without the key (GCM authentication), so `ucoz_user()` is a trusted
  identity source. Never accept identity from query parameters or custom headers instead.
- **Forged-header test (verified):** a random `X-Ucoz-User` sent by a client through
  `https://{site}/apps/…` is **stripped by the uCoz proxy** — PHP and Node never see it. Sent to
  `https://{tech}/…` it **reaches the script**, and `ucoz_user()` rejects it (`[]`). So:
  - never read `$_SERVER['HTTP_X_UCOZ_USER']` / `req.headers['x-ucoz-user']` yourself — only `ucoz_user()`;
  - **never log or echo the raw header.** A real header copied from a log can be replayed against
    `https://{tech}/…` until its `exp`.
- Requests that bypass the uCoz proxy (`https://{tech}/…`, server-to-server calls, cron) carry no header,
  so the visitor is a guest.
- Key rotation takes effect immediately: PHP reads the key on every request, and Node/Python cache it by file
  mtime. No restart is needed.

## 2. Where the helpers come from (verified)

| Runtime | How it is available | Source |
|---|---|---|
| PHP | `auto_prepend_file = /opt/ucoz/lib/ucoz.php` — `ucoz_user()` is defined before your script runs. No `require`. | `/opt/ucoz/lib/ucoz.php` (user-land function, OpenSSL) |
| Node.js | `require('ucoz')` resolves to `/opt/ucoz/lib/ucoz.js` in Passenger apps. No npm install. | built-in `crypto` only |
| Python | `PYTHONPATH=/opt/ucoz/lib` in Passenger apps → `from ucoz import ucoz_user`. No pip install. | stdlib fallback for AES-GCM; uses `cryptography`/`PyCryptodome` if installed |

## 3. API (verified contract)

```php
// PHP
$user  = ucoz_user();          // array of fields; [] for a guest
$email = ucoz_user('email');   // field value; null if absent or guest
```

```js
// Node.js — pass the request (http.IncomingMessage) or a plain headers object
const { ucoz_user } = require('ucoz');
const user  = ucoz_user(req);           // object; {} for a guest
const email = ucoz_user(req, 'email');  // value; null if absent or guest
```

```python
# Python — pass the WSGI environ dict, a Django/Flask request, a headers mapping, or the raw header string
from ucoz import ucoz_user
user  = ucoz_user(environ)              # dict; {} for a guest
email = ucoz_user(environ, 'email')     # value; None if absent or guest
```

## 4. Fields

Example from the Help Center:

```json
{
  "birthday": "",
  "country": 0,
  "email": "jon@ucoz.ru",
  "full_name": "John Doe",
  "gender": 1,
  "gid": 4,
  "id": 1,
  "phone": "",
  "reg_date": 1774007586,
  "user": "tester",
  "verify": 0
}
```

| Field | Type | Meaning |
|---|---|---|
| `id` | int | User id on the site — **the key for per-user data** |
| `user` | string | Login |
| `full_name` | string | Display name |
| `email` | string | E-mail (personal data) |
| `gid` | int | User group id (in the example, 4 is the admin of that site — **always verify group ids with `users_read` / `users_write` groups`**) |
| `gender` | int | Gender code |
| `country` | int | Country code |
| `birthday`, `phone` | string | Often empty (personal data) |
| `reg_date` | int | Registration time, Unix timestamp |
| `verify` | int | Verification flag |
| `exp` | int | Token expiry. Checked by the helper itself — you do not need to check it again. **About 2 minutes** after the request (verified: `exp - time()` ≈ 117 s) |

**Verified live in PHP 8.3, Node 22, and Python 3.11 (logged-in admin):** a header of ~288 chars arrives,
and `ucoz_user()` returns exactly these 12 keys (the 11 documented + `exp`) with identical types in all
three runtimes. Node takes `req`, Python takes the raw WSGI `environ`. A missing field is `null`/`None`. Types: `id`, `gid`, `gender`, `country`, `reg_date`, `verify`,
`exp` are integers. `user`, `email`, `full_name`, `phone`, `birthday` are strings, and empty ones come as `""`.
`ucoz_user('email')` returns a string, and an unknown field returns `null`. `id`/`gid` match `users_read` / `users_write`
(`uid`, `group.id`).

**Also verified (logged-in member):**
- The header arrives on GET and on same-site `fetch()` POST alike, so members-only write APIs work.
- The platform issues a fresh header for every request, and each one lives about 2 minutes.
- Never cache identity across requests, in a cookie or a session. Call `ucoz_user()` on each request.
- The short `exp` also limits replay of a leaked header to about 2 minutes.

**Being logged in to the Control Panel or uID is not enough.** The visitor must be logged in on the
**site itself** (`https://{site}/index/1`). A first live test from a browser with only a panel session
returned a guest (`[]`, no header).

Treat types defensively (`(int)$u['id']`, `Number(u.id)`): the payload is JSON produced by the platform.

## 5. Enable it

```text
apps_read / apps_write / apps_delete visitor_key_get        → read only `present` (the response also contains the key — never echo it)
apps_read / apps_write / apps_delete visitor_key_create     → only if present=false, after the user approves
```

`visitor_key_rotate` / `visitor_key_delete` are **C**. Rotation makes headers that are already in flight
fail (those visitors look like guests for one request). Deletion makes every visitor a guest.

## 6. URLs: keep visitors on the site domain

Identity only exists on `https://{site}/apps/…`. Watch these traps:

- **A PHP folder URL without a trailing slash** (`/apps/{name}`) gets a 301 redirect **to the
  technical domain** (`https://{tech}/{name}/`), where the visitor is a guest **(verified)**. Always link to
  `/apps/{name}/` (with the slash) or `/apps/{name}/index.php`.
- Never put `https://{tech}/…` links in pages or redirects that visitors follow.
- Absolute `Location:` headers you build yourself must point to `https://{site}/apps/…`.

## 7. Use it behind one adapter

```php
function current_user(): ?array {
    if (!function_exists('ucoz_user')) return null;                 // safety net for non-standard runtimes
    $u = ucoz_user();
    if (!is_array($u) || empty($u['id'])) return null;               // guest
    return ['id' => (int)$u['id'], 'login' => (string)($u['user'] ?? ''), 'gid' => (int)($u['gid'] ?? 0)];
}
```

- Keep only what the feature needs (`id`, maybe `login`, `gid`). Do not store e-mail, phone, or birthday
  unless the feature requires it and the owner agreed.
- Authorize staff by `gid` from `users_read` / `users_write` groups` on the same site, stored in config (`staff_gids`).
- For local development, gate a `dev_uid` override behind a secret and remove it before finishing.

## 8. Typical uses

- Members-only API: `current_user()` is null → 401 JSON. The frontend shows a "log in" link to the site's login page.
- Per-user data (favorites, progress, reactions, votes): rows keyed by `id`, with a UNIQUE key against doubles.
- Staff tools: allow by `gid` instead of a separate password.
- Prefill forms (name, e-mail) without storing anything.

## 9. Diagnostics

| Symptom | Check |
|---|---|
| Always a guest | 1) the visitor is logged in on **this site** (`/index/1`), not just in the Control Panel/uID — the most common cause 2) `visitor_key_get.present` 3) the URL is `https://{site}/apps/…`, not `{tech}` 4) the link had no trailing slash and was redirected to `{tech}` |
| Guest for one request right after a key change | Expected — header encrypted with the old key. Reload. |
| Node: `Cannot find module 'ucoz'` | Not running as a Passenger app of this account — a cron script (verified: fails there) or a local test. Fall back to guest mode; cron has no visitor anyway. |
| Python: `ModuleNotFoundError: ucoz` | Same (verified in cron: `PYTHONPATH` is not set there). |

To verify on a new account, deploy `templates/probe/probe.php` as `public_html/_probe_<rand>/index.php` (token-protected), open it via
`https://{site}/apps/_probe_<rand>/?t=<token>` once as a guest and once logged in, then delete it (**C**).

## 10. Privacy

`ucoz_user()` output is personal data. Never log it in full, never return it from debug endpoints that
outlive the debugging session, and never send it to third parties. Temporary diagnostic endpoints return
only field names/types plus `id`/`gid`, and are deleted afterwards.
