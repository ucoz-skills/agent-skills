<?php
/**
 * Module logic: actions, page, router. Loaded by index.php AFTER lib/bootstrap.php, so even a syntax
 * error in this file is caught by the bootstrap's exception handler and logged with file:line.
 *
 *   GET  /apps/{name}/             -> HTML page (render_page)
 *   GET  /apps/{name}/api/health   -> {"ok":true,"data":{...}}
 *   POST /apps/{name}/api/submit   -> JSON body -> {"ok":true,"data":{...}}
 *   GET  /apps/{name}/api/list     -> staff only
 *
 * Add a feature = add a function + one line in ACTIONS.
 */
declare(strict_types=1);

/** action => [HTTP method, handler] */
const ACTIONS = [
    'health' => ['GET',  'act_health'],
    'submit' => ['POST', 'act_submit'],
    'list'   => ['GET',  'act_list'],
];

/* ---------- handlers ---------- */

function act_health(array $in): array
{
    secrets(); // throws 500 "config" if the secrets file is missing
    return [
        'php'      => PHP_VERSION,
        'writable' => is_writable(APP_DATA),
        'identity' => function_exists('ucoz_user'),
        'time'     => gmdate('c'),
    ];
}

function act_submit(array $in): array
{
    rate_limit('submit:' . ip_hash(), 5, 600);

    if (($in['website'] ?? '') !== '') {          // honeypot field must stay empty
        return ['saved' => true];
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

    $row = [
        'id'      => bin2hex(random_bytes(8)),
        'name'    => $name,
        'email'   => $email,
        'message' => $message,
        'uid'     => current_user()['id'] ?? null,
        'ip'      => ip_hash(),
        'at'      => gmdate('c'),
    ];
    // File storage (JSON Lines). For concurrent writers or reporting, switch to MySQL (references/MYSQL.md).
    file_put_contents(APP_DATA . '/submissions.jsonl', json_encode($row, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);
    app_log('submit', ['id' => $row['id']]);

    return ['saved' => true, 'id' => $row['id']];
}

function act_list(array $in): array
{
    require_staff();
    $file  = APP_DATA . '/submissions.jsonl';
    $lines = is_file($file) ? file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
    $items = array_map(static fn($l) => json_decode($l, true), array_slice($lines, -100));
    return ['items' => array_reverse(array_values(array_filter($items)))];
}

/* ---------- page ---------- */

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
  <title>Contact</title>
  <style>
    body{font:16px/1.5 system-ui,sans-serif;max-width:560px;margin:40px auto;padding:0 16px}
    label{display:block;margin:12px 0 4px}input,textarea{width:100%;padding:10px;font:inherit;box-sizing:border-box}
    button{margin-top:16px;padding:10px 18px;font:inherit;cursor:pointer}.hp{position:absolute;left:-9999px}
    #msg{margin-top:12px}
  </style>
</head>
<body>
  <h1>Contact us</h1>
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
      const body = Object.fromEntries(new FormData(e.target));
      out.textContent = 'Sending…';
      try {
        const r = await fetch(BASE + 'api/submit', {
          method: 'POST', credentials: 'same-origin',
          headers: {'Content-Type': 'application/json'}, body: JSON.stringify(body)
        });
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

/* ---------- router ---------- */

try {
    $path   = route_path();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($path === '/') {
        if ($method !== 'GET') {
            fail(405, 'method');
        }
        render_page();
        exit;
    }

    if (preg_match('#^/api/([a-z_]+)$#', $path, $m)) {
        $action = ACTIONS[$m[1]] ?? fail(404, 'unknown_action');
        if ($method !== $action[0]) {
            fail(405, 'method');
        }
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
