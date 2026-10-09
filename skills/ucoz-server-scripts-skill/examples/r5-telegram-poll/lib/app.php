<?php
/**
 * Recipe R5 — Telegram bot through short polling (no webhook) + optional posts about new site materials.
 *
 * Why polling: Telegram's servers could not reach Server Scripts sites (getWebhookInfo: "Connection timed out",
 * verified 2026-10-08), while outbound calls to api.telegram.org work. So the module pulls updates itself:
 * getUpdates with timeout=0 on every cron run, plus a staff-only endpoint the admin page can call while a chat
 * is being linked. Latency for incoming commands = the cron interval.
 *
 * Files: public_html/{name}/lib/app.php (this), index.php + lib/bootstrap.php + .htaccess (templates/php),
 *        domains/{domain}/{name}_secrets.php:
 *          'tg_token'   => '<token from @BotFather>',            // required
 *          'cron_key'   => '<random 48 hex>',                    // required, X-Cron-Key
 *          'staff_gids' => [4],                                  // who may open the admin page and create a link code
 *          'origins'    => ['https://{site}'],                   // the admin page POSTs from here (CSRF check)
 *          'site'       => 'example.ucoz.net', 'uapi_token' => '<read-only uAPI key>', 'uapi_module' => 'blog', // optional: new materials
 * State: {name}_data/state.json (offset, linked chats, one-time link code hash, last announced material id). No database.
 *
 * URLs:  GET  /apps/{name}/              admin page (staff only): "Link Telegram" button, then polls every 4 s
 *        GET  /apps/{name}/api/health   bot username, linked chats count, last poll
 *        GET  /apps/{name}/api/cron     X-Cron-Key; poll once + announce new materials (cron: every 1–10 min)
 *        POST /apps/{name}/api/link     staff only; returns a t.me link with a one-time /start code (15 min)
 *        POST /apps/{name}/api/poll     staff only; poll once (the admin page calls it while a link is pending)
 * Bot commands: /start <code> links a private chat, /stop unlinks it, /latest lists the newest materials.
 * Only one consumer per bot token: getUpdates hands each update to whoever polls first.
 */
declare(strict_types=1);

const ACTIONS = [
    'health' => ['GET',  'act_health'],
    'cron'   => ['GET',  'act_cron'],
    'link'   => ['POST', 'act_link'],
    'poll'   => ['POST', 'act_poll'],
];
const LINK_TTL      = 900;   // seconds a /start code stays valid
const ANNOUNCE_MAX  = 5;     // materials per run; the rest go out on the next run

/* ---------- state (one JSON file, locked) ---------- */

function state_update(callable $fn): array
{
    $file = APP_DATA . '/state.json';
    $fh = fopen($file, 'c+');
    if ($fh === false) {
        fail(500, 'state');
    }
    flock($fh, LOCK_EX);
    $raw   = stream_get_contents($fh);
    $state = json_decode($raw === false ? '' : $raw, true);
    $state = is_array($state) ? $state : ['offset' => 0, 'chats' => [], 'link' => null, 'last_id' => null, 'cleared_for' => ''];
    $state = $fn($state);
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode($state, JSON_UNESCAPED_UNICODE));
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    @chmod($file, 0600);
    return $state;
}

function state(): array
{
    return state_update(static fn(array $s): array => $s);
}

/* ---------- Telegram ---------- */

function tg(string $method, array $params = []): mixed
{
    $token = (string)(secrets()['tg_token'] ?? '');
    $token !== '' || fail(500, 'tg_not_configured');
    $r = http_post_json('https://api.telegram.org/bot' . $token . '/' . $method, $params, [], 8);
    $j = json_decode($r['body'], true);
    if (!is_array($j) || empty($j['ok'])) {
        $desc = is_array($j) ? (string)($j['description'] ?? 'error') : ($r['error'] ?: 'http_' . $r['code']);
        throw new RuntimeException('tg: ' . mb_substr($desc, 0, 120)); // never log the URL: it contains the token
    }
    return $j['result'] ?? null;
}

function tg_text(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); // for parse_mode HTML
}

/** getUpdates returns 409 while a webhook is set: remove it once per token. */
function ensure_polling(): void
{
    $tag = hash('sha256', (string)secrets()['tg_token']);
    if ((state()['cleared_for'] ?? '') === $tag) {
        return;
    }
    tg('deleteWebhook', ['drop_pending_updates' => false]);
    state_update(static function (array $s) use ($tag): array { $s['cleared_for'] = $tag; return $s; });
}

/**
 * One short poll. The offset moves forward before an update is handled, so one bad update cannot block the rest.
 * Polls never overlap: cron and the admin page share a non-blocking lock (verified: two parallel getUpdates
 * calls make Telegram answer "Conflict: terminated by other getUpdates request").
 */
function poll_once(): int
{
    $lock = fopen(APP_DATA . '/poll.lock', 'c');
    if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
        return 0; // another poll is running right now
    }
    try {
        ensure_polling();
        $offset = (int)(state()['offset'] ?? 0);
        try {
            $updates = (array)tg('getUpdates', ['offset' => $offset, 'timeout' => 0, 'limit' => 50, 'allowed_updates' => ['message']]);
        } catch (RuntimeException $e) {
            if (str_contains($e->getMessage(), 'Conflict')) {
                app_log('poll_conflict', ['msg' => $e->getMessage()]); // someone else polls this token
                return 0;
            }
            throw $e;
        }
        return handle_updates($updates);
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

function handle_updates(array $updates): int
{
    foreach ($updates as $u) {
        $next = (int)$u['update_id'] + 1;
        state_update(static function (array $s) use ($next): array { $s['offset'] = max((int)$s['offset'], $next); return $s; });
        try {
            handle_update($u);
        } catch (Throwable $e) {
            app_log('tg_update_failed', ['msg' => $e->getMessage()]);
        }
    }
    state_update(static function (array $s): array { $s['polled_at'] = time(); return $s; });
    return count($updates);
}

function handle_update(array $u): void
{
    $msg  = $u['message'] ?? null;
    $chat = (int)($msg['chat']['id'] ?? 0);
    $text = trim((string)($msg['text'] ?? ''));
    if ($chat === 0 || ($msg['chat']['type'] ?? '') !== 'private') {
        return;
    }
    $known = isset(state()['chats'][(string)$chat]);

    if (preg_match('#^/start\s+([a-f0-9]{16})$#', $text, $m)) {
        $linked = false;
        state_update(static function (array $s) use ($m, $chat, $msg, &$linked): array {
            $l = $s['link'] ?? null;
            if (is_array($l) && $l['exp'] > time() && hash_equals((string)$l['hash'], hash('sha256', $m[1]))) {
                $s['chats'][(string)$chat] = ['name' => mb_substr((string)($msg['chat']['first_name'] ?? ''), 0, 40), 'since' => time()];
                $s['link'] = null; // one-time
                $linked = true;
            }
            return $s;
        });
        tg('sendMessage', ['chat_id' => $chat, 'text' => $linked
            ? 'Done: this chat is linked. Send /stop to unlink.'
            : 'This link has expired. Create a new one on the admin page.']);
        return;
    }
    if (!$known) {
        tg('sendMessage', ['chat_id' => $chat, 'text' => 'This is a private bot. Link a chat with the link from the admin page.']);
        return;
    }
    if ($text === '/stop') {
        state_update(static function (array $s) use ($chat): array { unset($s['chats'][(string)$chat]); return $s; });
        tg('sendMessage', ['chat_id' => $chat, 'text' => 'Unlinked. You will not get messages any more.']);
        return;
    }
    if ($text === '/latest') {
        $items = latest_materials(5);
        $lines = array_map(static fn($i) => '• <a href="' . tg_text($i['url']) . '">' . tg_text($i['title']) . '</a>', $items);
        tg('sendMessage', ['chat_id' => $chat, 'parse_mode' => 'HTML', 'disable_web_page_preview' => true,
            'text' => $lines ? implode("\n", $lines) : 'No materials found.']);
        return;
    }
    tg('sendMessage', ['chat_id' => $chat, 'text' => 'Commands: /latest, /stop']);
}

/* ---------- site materials through uAPI (optional) ---------- */

function uapi_get(string $path, array $query = []): array
{
    $s = secrets();
    $url = 'https://' . $s['site'] . '/uapi/' . ltrim($path, '/') . ($query ? '?' . http_build_query($query) : '');
    $r = http_request('GET', $url, null, ['Authorization: Bearer ' . $s['uapi_token'], 'Accept: application/json'], 15);
    $j = json_decode($r['body'], true);
    if (!is_array($j)) {
        throw new RuntimeException('uapi_bad_json_' . $r['code']);
    }
    if (isset($j['error'])) {
        throw new RuntimeException('uapi_' . (is_array($j['error']) ? ($j['error']['code'] ?? 'error') : $j['error']));
    }
    return $j;
}

/** Newest materials first: [['id','title','url'], …]. Empty when uAPI is not configured. */
function latest_materials(int $n): array
{
    $s = secrets();
    if (empty($s['uapi_token']) || empty($s['site'])) {
        return [];
    }
    $module = (string)($s['uapi_module'] ?? 'blog');
    $r = uapi_get($module, ['per_page' => 20, 'page' => 1]);
    $list = $r['blogs'] ?? $r['news'] ?? $r['entries'] ?? $r['items'] ?? [];
    $out = [];
    foreach ((array)$list as $p) {
        if (!is_array($p) || !isset($p['id'])) {
            continue;
        }
        $out[] = [
            'id'    => (int)$p['id'],
            'title' => html_entity_decode((string)($p['title'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'url'   => (string)($p['entry_url'] ?? $p['url'] ?? ''),
        ];
    }
    usort($out, static fn($a, $b) => $b['id'] <=> $a['id']); // do not trust the list order
    return array_slice($out, 0, $n);
}

/**
 * Sends materials newer than the last announced id. The first run, and every run with no linked chat, only
 * moves the watermark to the newest id, so a chat linked later never gets a backlog of old items.
 */
function announce_new(): int
{
    $chats = array_keys(state()['chats'] ?? []);
    $items = latest_materials(20);
    if ($items === []) {
        return 0;
    }
    $last = state()['last_id'];
    if ($last === null || $chats === []) {
        state_update(static function (array $s) use ($items): array { $s['last_id'] = $items[0]['id']; return $s; });
        return 0;
    }
    $new = array_reverse(array_filter($items, static fn($i) => $i['id'] > (int)$last)); // oldest first
    $sent = 0;
    foreach (array_slice($new, 0, ANNOUNCE_MAX) as $i) {
        foreach ($chats as $chat) {
            tg('sendMessage', ['chat_id' => (int)$chat, 'parse_mode' => 'HTML',
                'text' => 'New: <a href="' . tg_text($i['url']) . '">' . tg_text($i['title']) . '</a>']);
        }
        state_update(static function (array $s) use ($i): array { $s['last_id'] = max((int)$s['last_id'], $i['id']); return $s; });
        $sent++;
    }
    return $sent;
}

/* ---------- actions ---------- */

function act_health(array $in): array
{
    $st = state();
    $bot = null;
    try {
        $bot = (string)(tg('getMe')['username'] ?? '');
    } catch (Throwable $e) {
        $bot = null;
    }
    return ['bot' => $bot, 'chats' => count($st['chats'] ?? []), 'polled_at' => $st['polled_at'] ?? null,
            'materials' => !empty(secrets()['uapi_token'])];
}

function act_cron(array $in): array
{
    require_key('cron_key', 'X-Cron-Key');
    $read = poll_once();
    $sent = 0;
    try {
        $sent = announce_new();
    } catch (Throwable $e) {
        app_log('announce_failed', ['msg' => $e->getMessage()]);
    }
    app_log('cron', ['updates' => $read, 'announced' => $sent]);
    return ['updates' => $read, 'announced' => $sent];
}

function act_link(array $in): array
{
    require_staff();
    rate_limit('link', 10, 600);
    $code = bin2hex(random_bytes(8));
    state_update(static function (array $s) use ($code): array {
        $s['link'] = ['hash' => hash('sha256', $code), 'exp' => time() + LINK_TTL];
        return $s;
    });
    $bot = (string)(tg('getMe')['username'] ?? '');
    return ['url' => 'https://t.me/' . $bot . '?start=' . $code, 'expires_in' => LINK_TTL];
}

function act_poll(array $in): array
{
    require_staff();
    rate_limit('poll', 30, 60);
    return ['updates' => poll_once(), 'chats' => count(state()['chats'] ?? [])];
}

/** Staff-only admin page: one button that creates a link, then polls until the chat count grows. */
function render_admin(): void
{
    require_staff();
    $base = h(APP_PUBLIC_BASE);
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store');
    echo <<<HTML
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Telegram bot</title><style>body{font:16px/1.5 system-ui,sans-serif;max-width:560px;margin:40px auto;padding:0 16px}button{padding:8px 16px}</style></head>
<body><h1>Telegram bot</h1><p id="st">…</p><button id="b">Link Telegram</button><p id="out"></p>
<script>
const B = '{$base}', st = document.getElementById('st'), out = document.getElementById('out');
const api = (p, m) => fetch(B + 'api/' + p, { method: m || 'GET', credentials: 'same-origin' }).then(r => r.json());
let timer = null, chats = 0;
async function health() { const j = await api('health'); chats = j.data.chats; st.textContent = '@' + j.data.bot + ' · linked chats: ' + chats; }
document.getElementById('b').onclick = async () => {
  const j = await api('link', 'POST');
  if (!j.ok) { out.textContent = j.message || j.error; return; }
  out.innerHTML = ''; const a = document.createElement('a'); a.href = j.data.url; a.textContent = 'Open in Telegram and press Start'; a.target = '_blank'; out.append(a);
  const before = chats; clearInterval(timer);
  timer = setInterval(async () => { const p = await api('poll', 'POST'); if (p.ok && p.data.chats > before) { clearInterval(timer); out.textContent = 'Linked.'; health(); } }, 4000);
  setTimeout(() => clearInterval(timer), 15 * 60 * 1000);
};
health();
</script></body></html>
HTML;
}

/* ---------- router ---------- */

try {
    $path   = route_path();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($path === '/') {
        $method === 'GET' || fail(405, 'method');
        render_admin();
        exit;
    }
    if (preg_match('#^/api/([a-z_]+)$#', $path, $m)) {
        $action = ACTIONS[$m[1]] ?? fail(404, 'unknown_action');
        $method === $action[0] || fail(405, 'method');
        if ($method !== 'GET') {
            require_same_origin();
        }
        json_out(['ok' => true, 'data' => ($action[1])($method === 'GET' ? $_GET : json_input())]);
        exit;
    }
    fail(404, 'not_found');
} catch (HttpError $e) {
    json_out(['ok' => false, 'error' => $e->getMessage(), 'message' => $e->userMessage], $e->status);
}
