<?php
/**
 * Recipe R3 — receive signed webhooks from any external system (CRM, form builder, delivery
 * service, your other site) into a MySQL queue, and process them in batches by cron.
 *
 * Files: public_html/{name}/index.php (templates/php stub), lib/bootstrap.php (templates/php),
 *        lib/app.php (this), .htaccess (templates/php),
 *        domains/{domain}/{name}_secrets.php: db, webhook_secret, cron_key, staff_gids, hash_salt, origins
 *
 * Sender contract (document this for whoever sends the webhooks):
 *   POST https://{site}/apps/{name}/api/hook
 *   Content-Type: application/json
 *   X-Event-Id:  unique id of the event (retries reuse it → processed once)
 *   X-Signature: t=<unix time>,v1=<hex HMAC-SHA256(webhook_secret, "<t>.<raw body>")>
 *
 * URLs:  POST /api/hook    → 202 {"queued":true} | 200 {"duplicate":true} | 401 bad signature
 *        GET  /api/work    → cron worker, header X-Cron-Key (404 on a wrong key)
 *        GET  /api/stats   → staff only
 */
declare(strict_types=1);

const ACTIONS = [
    'hook'  => ['POST', 'act_hook'],
    'work'  => ['GET',  'act_work'],
    'stats' => ['GET',  'act_stats'],
];
const SIG_TOLERANCE = 300;   // seconds
const BATCH         = 20;    // events per cron run
const MAX_ATTEMPTS  = 5;

function migrate(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS queue_meta (k VARCHAR(64) PRIMARY KEY, v VARCHAR(255) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $ver = (int)($pdo->query("SELECT v FROM queue_meta WHERE k = 'schema'")->fetchColumn() ?: 0);
    $steps = [
        1 => "CREATE TABLE queue_events (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                event_id VARCHAR(100) NOT NULL,
                payload MEDIUMTEXT NOT NULL,
                status ENUM('pending','working','done','failed') NOT NULL DEFAULT 'pending',
                attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
                last_error VARCHAR(255) NULL,
                locked_until DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_event (event_id),
                KEY idx_status (status, id)
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    ];
    foreach ($steps as $n => $sql) {
        if ($n <= $ver) {
            continue;
        }
        $pdo->exec($sql);
        $pdo->prepare("REPLACE INTO queue_meta (k, v) VALUES ('schema', ?)")->execute([(string)$n]);
    }
}

/** Verifies "t=<unix>,v1=<hex>" over "<t>.<raw body>" with a time window (anti-replay). */
function verify_signature(string $raw): void
{
    $secret = (string)(secrets()['webhook_secret'] ?? '');
    $header = (string)($_SERVER['HTTP_X_SIGNATURE'] ?? '');
    if (strlen($secret) < 16 || !preg_match('/^t=(\d{9,11}),v1=([a-f0-9]{64})$/', $header, $m)) {
        fail(401, 'signature');
    }
    if (abs(time() - (int)$m[1]) > SIG_TOLERANCE) {
        fail(401, 'signature_expired');
    }
    $expected = hash_hmac('sha256', $m[1] . '.' . $raw, $secret);
    if (!hash_equals($expected, $m[2])) {
        fail(401, 'signature');
    }
}

function act_hook(array $in): array
{
    // Signature is computed over the RAW body, so read it again (json_input() already validated size/JSON).
    $raw = (string)file_get_contents('php://input');
    verify_signature($raw);
    $eventId = (string)($_SERVER['HTTP_X_EVENT_ID'] ?? '');
    if (!preg_match('/^[A-Za-z0-9._:-]{1,100}$/', $eventId)) {
        fail(400, 'event_id');
    }
    try {
        db()->prepare('INSERT INTO queue_events (event_id, payload) VALUES (?, ?)')->execute([$eventId, $raw]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            return ['duplicate' => true];   // the sender retried — already queued
        }
        throw $e;
    }
    http_response_code(202);
    return ['queued' => true];
}

/** Your business logic per event. Throw to retry later (up to MAX_ATTEMPTS). */
function process_event(array $payload): void
{
    // Example: route by type. Replace with real work (uAPI call, notification, DB update...).
    $type = (string)($payload['type'] ?? 'unknown');
    app_log('event_processed', ['type' => $type]);
}

function act_work(array $in): array
{
    require_key('cron_key', 'X-Cron-Key');
    $pdo = db();
    if ((int)$pdo->query("SELECT GET_LOCK('" . APP_NAME . "_work', 0)")->fetchColumn() !== 1) {
        return ['busy' => true];   // previous run still working
    }
    $done = $failed = $retry = 0;
    try {
        $rows = $pdo->query("SELECT id, payload, attempts FROM queue_events
                             WHERE status = 'pending' OR (status = 'working' AND locked_until < UTC_TIMESTAMP())
                             ORDER BY id LIMIT " . BATCH)->fetchAll();
        foreach ($rows as $r) {
            $pdo->prepare("UPDATE queue_events SET status = 'working', locked_until = UTC_TIMESTAMP() + INTERVAL 5 MINUTE,
                           attempts = attempts + 1 WHERE id = ?")->execute([$r['id']]);
            try {
                process_event(json_decode($r['payload'], true) ?: []);
                $pdo->prepare("UPDATE queue_events SET status = 'done', locked_until = NULL, last_error = NULL WHERE id = ?")
                    ->execute([$r['id']]);
                $done++;
            } catch (Throwable $e) {
                $final = (int)$r['attempts'] + 1 >= MAX_ATTEMPTS;
                $pdo->prepare("UPDATE queue_events SET status = ?, locked_until = NULL, last_error = ? WHERE id = ?")
                    ->execute([$final ? 'failed' : 'pending', mb_substr($e->getMessage(), 0, 255), $r['id']]);
                $final ? $failed++ : $retry++;
            }
        }
    } finally {
        $pdo->query("SELECT RELEASE_LOCK('" . APP_NAME . "_work')");
    }
    app_log('work', ['done' => $done, 'retry' => $retry, 'failed' => $failed]);
    return ['done' => $done, 'retry' => $retry, 'failed' => $failed];
}

function act_stats(array $in): array
{
    require_staff();
    $rows = db()->query('SELECT status, COUNT(*) n FROM queue_events GROUP BY status')->fetchAll();
    return ['by_status' => array_column($rows, 'n', 'status')];
}

try {
    $path   = route_path();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (preg_match('#^/api/([a-z_]+)$#', $path, $m)) {
        $action = ACTIONS[$m[1]] ?? fail(404, 'unknown_action');
        $method === $action[0] || fail(405, 'method');
        $input = $method === 'GET' ? $_GET : json_input();   // no Origin check: server-to-server endpoint
        json_out(['ok' => true, 'data' => ($action[1])($input)], http_response_code() ?: 200);
        exit;
    }
    fail(404, 'not_found');
} catch (HttpError $e) {
    json_out(['ok' => false, 'error' => $e->getMessage(), 'message' => $e->userMessage], $e->status);
}
