# Calling the site uAPI from server code

Server scripts often read or write site data: catalog items, news, users, comments, and shop data.
They do this over HTTP with the site's **uAPI**.

## 1. Two different keys

| Who calls | Key | Where it comes from |
|---|---|---|
| **You, the agent**, through MCP (`content_read` / `content_write`, `users_read` / `users_write`, `shop_read` / `shop_write`, …) | Minted server-side by the MCP. You never see it. | automatic |
| **The script**, at runtime | A uAPI key issued by the site owner | The owner creates it in the site Control Panel and gives it to you, or pastes it into the secrets file themselves |

You cannot hand the MCP's key to a script. Ask the user for a uAPI key only when the script really
needs uAPI **at runtime** (see §0). Store it in `{name}_secrets.php` / `config.json`.

**How the owner creates the key** (Help Center: https://www.ucoz.ru/help/extensions/uapi):
Control Panel → **Site → uAPI** → "Create key" → name + **the site user the key acts as** → check the
modules the API may access → save. The key **inherits all rights of that user**, so:
- recommend a **dedicated site user** whose group has only the rights the script needs (e.g. read news,
  add comments) — not the admin account;
- one key per integration, so it can be deactivated or deleted without breaking others;
- the owner pastes the key into the secrets file, or sends it to you and you write it there. Never echo it back.

## 0. When a script needs uAPI (and when it does not)

| Situation | Use |
|---|---|
| A one-off or admin task done **now** by you (import content, fix categories, create users) | **MCP tools** (`content_read` / `content_write`, `users_read` / `users_write`, `shop_read` / `shop_write`) — no key, no script |
| Code that must read/write site data **later, on its own** — on a visitor's request, a webhook, or cron, when no agent is present | **uAPI from the script** with its own key |

Examples of the second case: a cron job that posts new news to Telegram, a widget that shows a member's
favorites with entry titles, a form that publishes a comment on behalf of the logged-in visitor, a
search or "popular this week" widget built from catalog data.

## 2. Inspect real shapes with MCP first

Before coding against an endpoint, call the matching MCP tool on the same site and look at the
actual JSON (field names, nesting, encoding). For example, `content_read` action=list module=stuff per_page=2`,
`users_read` action=get user_id=1`. Where docs and live responses disagree, **trust the live response**.

**Where the uAPI documentation is: `docs_tool`** (works without `select_site`). It reads the official
OpenAPI specs `https://api.ucoz.net/{module}-openapi.yaml`; the index is `https://api.ucoz.net/llms.txt`.

| Call | Returns |
|---|---|
| `docs_tool action=list` | Catalogue: 28 modules (`blog`, `news`, `publ`, `load`, `board`, `dir`, `faq`, `forum`, `gb`, `photo`, `video`, `shop`, `user`/`users`, `mail` (mail forms), `search`, `pages`, `menus`, `polls`, `subscriptions`, `templates`, `settings`, `security`, …) with their spec URLs |
| `docs_tool action=search module=blog query=per_page limit=5` | Matching YAML fragments with line numbers — the fastest way to find a parameter or field |
| `docs_tool action=fetch_spec module=blog` | The whole spec of one module (large; use `search` first) |

Order: the schemas of the domain MCP tools (`content_read` / `content_write`, `users_read` / `users_write`, `shop_read` / `shop_write`) → `docs_tool search`
for anything missing → one live call to confirm the real JSON before coding against it.

## 3. Request basics

```text
GET https://{site}/uapi/<module>[/<sub>]?param=…
Authorization: Bearer <uapi_token>
```

PHP helper:

```php
function uapi(string $method, string $path, array $query = [], ?array $body = null): array {
    $s   = secrets();
    $url = 'https://' . $s['site'] . '/uapi/' . ltrim($path, '/') . ($query ? '?' . http_build_query($query) : '');
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $s['uapi_token'], 'Accept: application/json'],
    ]);
    if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($body));
    $raw  = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    if ($raw === false) throw new RuntimeException('uapi_unreachable');
    $json = json_decode($raw, true);
    if (!is_array($json)) throw new RuntimeException("uapi_bad_json_$code");
    if (isset($json['error'])) throw new RuntimeException('uapi_' . (is_array($json['error']) ? ($json['error']['code'] ?? 'error') : $json['error']));
    return $json;
}
```

(No `curl_close()` — it does nothing since PHP 8.0 and is deprecated in 8.5.)

## 4. Verified facts (October 2026)

Shapes differ between sites and modules — inspect every endpoint you use with §2 first.

- The script's Bearer-key calls to `/uapi/publ` with `per_page=50` worked; a 748-item catalog took ~15
  requests (~15 s) — run full reads from cron.
- Catalog materials carry `reads` (cumulative), `rating`, `rate_num`, `rate_sum`, `comments_num`,
  `add_date_ts`, `mod_date_ts`, `category`, `pending`, `entry_url` (CAPABILITIES.md §4).
- **No popularity sort.** Compute rankings yourself (cron + snapshots). List order differs by module (blog users:
  oldest first), so sort by id or date yourself.
- Unpublished items have `pending: "yes"`. Links come from `entry_url` (may be `https` and custom paths).
- Text may contain HTML entities: decode (`html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8')`) before
  comparing, escape again on output.

## 5. Errors

| Error | Meaning | Action |
|---|---|---|
| `INVALID_API_KEY` | Key revoked or wrong | Ask the owner for a new key. Update secrets. |
| `API_ACCESS_DENY` | API for that module is disabled for the site | `modules_read module_status` / `module_enable_uapi` — with approval. |
| `NOT_SUPPORTED_METHOD` | Wrong path or method | Check with `docs_tool` and live MCP calls. |
| Body like `({"SCRIPTDATA":…})` | You hit the legacy uCoz PHP wrapper, not uAPI | Fix the URL: `https://{site}/uapi/…`. Old scripts using OAuth 1.0a `uAPImodule.php`: [MIGRATION.md](MIGRATION.md). |

## 6. Performance and limits

- Cache read results (JSON file or MySQL) for 1–10 minutes. Pages that call uAPI on every request are slow.
- Batch with `per_page` (50 verified), and never loop over all pages in a single web request (~60 s
  gateway limit, PHP 30 s). Use cron with a cursor.
- Back off on 429/5xx. Retry at most twice, with jitter.
