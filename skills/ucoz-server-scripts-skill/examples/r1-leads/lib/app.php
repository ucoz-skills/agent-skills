<?php
/**
 * Recipe R1 — lead form that stores submissions in MySQL; staff see them as JSON or CSV.
 * Files: public_html/{name}/lib/app.php (this), public_html/{name}/index.php (templates/php stub), public_html/{name}/lib/bootstrap.php (templates/php),
 *        public_html/{name}/.htaccess (templates/php), domains/{domain}/{name}_secrets.php (db, staff_gids, hash_salt, origins)
 * URLs:  GET  /apps/{name}/              form page
 *        POST /apps/{name}/api/submit    JSON {name,email,message,website(honeypot)}
 *        GET  /apps/{name}/api/list      staff only (ucoz_user gid in staff_gids)
 *        GET  /apps/{name}/api/export    staff only, CSV download
 */
declare(strict_types=1);

const ACTIONS = [
    'health' => ['GET',  'act_health'],
    'submit' => ['POST', 'act_submit'],
    'list'   => ['GET',  'act_list'],
    'export' => ['GET',  'act_export'],
];

/** Idempotent schema migrations (MCP cannot run SQL — the module owns its schema). */
function migrate(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS leads_meta (k VARCHAR(64) PRIMARY KEY, v VARCHAR(255) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $ver = (int)($pdo->query("SELECT v FROM leads_meta WHERE k = 'schema'")->fetchColumn() ?: 0);
    $steps = [
        1 => "CREATE TABLE leads_items (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(120) NOT NULL,
                email VARCHAR(190) NOT NULL,
                message TEXT NOT NULL,
                uid INT UNSIGNED NULL,
                ip_hash CHAR(64) NOT NULL,
                status ENUM('new','done','spam') NOT NULL DEFAULT 'new',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_created (created_at)
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    ];
    foreach ($steps as $n => $sql) {
        if ($n <= $ver) {
            continue;
        }
        $pdo->exec($sql);
        $pdo->prepare("REPLACE INTO leads_meta (k, v) VALUES ('schema', ?)")->execute([(string)$n]);
    }
}

function act_health(array $in): array
{
    $count = (int)db()->query('SELECT COUNT(*) FROM leads_items')->fetchColumn();
    return ['php' => PHP_VERSION, 'db' => true, 'leads' => $count, 'identity' => function_exists('ucoz_user')];
}

function act_submit(array $in): array
{
    rate_limit('submit:' . ip_hash(), 5, 600);
    if (($in['website'] ?? '') !== '') {
        return ['saved' => true]; // honeypot: pretend success
    }
    $name    = trim((string)($in['name'] ?? ''));
    $email   = trim((string)($in['email'] ?? ''));
    $message = trim((string)($in['message'] ?? ''));
    if ($name === '' || mb_strlen($name) > 120) {
        fail(400, 'name', 'Enter your name (up to 120 characters).');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        fail(400, 'email', 'Enter a valid e-mail.');
    }
    if ($message === '' || mb_strlen($message) > 4000) {
        fail(400, 'message', 'Message is required (up to 4000 characters).');
    }
    db()->prepare('INSERT INTO leads_items (name, email, message, uid, ip_hash) VALUES (?, ?, ?, ?, ?)')
        ->execute([$name, $email, $message, current_user()['id'] ?? null, ip_hash()]);
    $id = (int)db()->lastInsertId();
    app_log('lead', ['id' => $id]);
    notify_lead(['id' => $id, 'name' => $name, 'email' => $email, 'message' => $message]);
    return ['saved' => true, 'id' => $id];
}

/**
 * Recipe R1 (notifications) — notify about a new lead. Best effort: a failed notification never fails the submission
 * (the lead is already in MySQL). Configure in secrets:
 *   tg_token, tg_chat                  → Telegram message via the Bot API (plain text, no parse_mode)
 *   notify_webhook_url, notify_secret  → signed JSON POST to a CRM / automation / your R3 receiver
 */
function notify_lead(array $lead): void
{
    $s = secrets();
    if (!empty($s['tg_token']) && !empty($s['tg_chat'])) {
        $text = "New lead #{$lead['id']}\n{$lead['name']} <{$lead['email']}>\n\n" . mb_substr($lead['message'], 0, 1500);
        $r = http_post_json('https://api.telegram.org/bot' . $s['tg_token'] . '/sendMessage',
            ['chat_id' => $s['tg_chat'], 'text' => $text, 'disable_web_page_preview' => true], [], 5);
        app_log('notify_tg', ['id' => $lead['id'], 'code' => $r['code'], 'error' => $r['error']]);
    }
    if (!empty($s['notify_webhook_url']) && !empty($s['notify_secret'])) {
        $body = json_encode(['type' => 'lead.created', 'lead' => $lead], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $t    = time();
        $sig  = hash_hmac('sha256', $t . '.' . $body, (string)$s['notify_secret']);
        $r = http_request('POST', (string)$s['notify_webhook_url'], $body, [
            'Content-Type: application/json',
            'X-Event-Id: lead-' . $lead['id'],
            "X-Signature: t={$t},v1={$sig}",
        ], 5);
        app_log('notify_webhook', ['id' => $lead['id'], 'code' => $r['code'], 'error' => $r['error']]);
    }
}

function act_list(array $in): array
{
    require_staff();
    $rows = db()->query('SELECT id, name, email, message, uid, status, created_at FROM leads_items ORDER BY id DESC LIMIT 100')->fetchAll();
    return ['items' => $rows];
}

function act_export(array $in): never
{
    require_staff();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="leads-' . gmdate('Ymd') . '.csv"');
    header('Cache-Control: no-store');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
    fputcsv($out, ['id', 'name', 'email', 'message', 'uid', 'status', 'created_at_utc'], ';');
    $stmt = db()->query('SELECT id, name, email, message, uid, status, created_at FROM leads_items ORDER BY id');
    while ($r = $stmt->fetch()) {
        // neutralize spreadsheet formulas (CSV injection)
        $r = array_map(static fn($v) => is_string($v) && $v !== '' && strpbrk($v[0], '=+-@') !== false ? "'" . $v : $v, $r);
        fputcsv($out, $r, ';');
    }
    fclose($out);
    exit;
}

function render_page(): void
{
    header('Content-Type: text/html; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    $base = h(APP_PUBLIC_BASE);
    echo <<<HTML
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Contact us</title>
  <style>
    body{font:16px/1.5 system-ui,sans-serif;max-width:560px;margin:40px auto;padding:0 16px}
    label{display:block;margin:12px 0 4px}input,textarea{width:100%;padding:10px;font:inherit;box-sizing:border-box}
    button{margin-top:16px;padding:10px 18px;font:inherit;cursor:pointer}.hp{position:absolute;left:-9999px}
  </style>
</head>
<body>
  <h1>Send a request</h1>
  <form id="f">
    <label for="name">Name</label><input id="name" name="name" maxlength="120" required>
    <label for="email">E-mail</label><input id="email" name="email" type="email" maxlength="190" required>
    <label for="message">Message</label><textarea id="message" name="message" rows="5" maxlength="4000" required></textarea>
    <input class="hp" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">
    <button type="submit">Send</button>
    <p id="msg" role="status"></p>
  </form>
  <script>
    const BASE = '{$base}';
    document.getElementById('f').addEventListener('submit', async (e) => {
      e.preventDefault();
      const out = document.getElementById('msg');
      out.textContent = 'Sending…';
      try {
        const r = await fetch(BASE + 'api/submit', {method: 'POST', credentials: 'same-origin',
          headers: {'Content-Type': 'application/json'}, body: JSON.stringify(Object.fromEntries(new FormData(e.target)))});
        const j = await r.json();
        out.textContent = j.ok ? 'Thank you! We will get back to you.' : (j.message || 'Error: ' + j.error);
        if (j.ok) e.target.reset();
      } catch (err) { out.textContent = 'Network error, please try again.'; }
    });
  </script>
</body>
</html>
HTML;
}

try {
    $path   = route_path();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($path === '/') {
        $method === 'GET' || fail(405, 'method');
        render_page();
        exit;
    }
    if (preg_match('#^/api/([a-z_]+)$#', $path, $m)) {
        $action = ACTIONS[$m[1]] ?? fail(404, 'unknown_action');
        $method === $action[0] || fail(405, 'method');
        if ($method !== 'GET') {
            require_same_origin();
        }
        $input = $method === 'GET' ? $_GET : json_input();
        json_out(['ok' => true, 'data' => ($action[1])($input)]);
        exit;
    }
    fail(404, 'not_found');
} catch (HttpError $e) {
    json_out(['ok' => false, 'error' => $e->getMessage(), 'message' => $e->userMessage], $e->status);
}
