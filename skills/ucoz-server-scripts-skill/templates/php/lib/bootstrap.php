<?php
/**
 * Bootstrap for a uCoz Server Scripts PHP module.
 * Location: domains/{domain}/public_html/{name}/lib/bootstrap.php  (lib/ is denied by .htaccess)
 * Requires PHP >= 8.1.
 *
 * Replace {name} in APP_NAME with the module folder name.
 */
declare(strict_types=1);

const APP_NAME    = '{name}';          // folder under public_html; also the secrets file prefix
const APP_LOG_MAX = 1048576;           // rotate logs at ~1 MB
const JSON_MAX    = 102400;            // max JSON body, bytes

define('APP_PUBLIC_BASE', '/apps/' . APP_NAME . '/');          // absolute public URL prefix
define('APP_DOMAIN_ROOT', dirname(__DIR__, 3));                 // domains/{domain}
define('APP_DATA', APP_DOMAIN_ROOT . '/' . APP_NAME . '_data'); // private storage, outside docroot

if (!is_dir(APP_DATA)) {
    @mkdir(APP_DATA, 0700, true);
}

ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', APP_DATA . '/php-error.log');   // verified: fatals (e.g. memory exhausted) land here

/* ---------- errors ---------- */

final class HttpError extends RuntimeException
{
    public function __construct(
        public readonly int $status,
        string $code,
        public readonly string $userMessage = ''
    ) {
        parent::__construct($code);
    }
}

function fail(int $status, string $code, string $userMessage = ''): never
{
    throw new HttpError($status, $code, $userMessage);
}

set_error_handler(static function (int $no, string $str, string $file, int $line): bool {
    if (!(error_reporting() & $no)) {
        return false;   // suppressed with @ (PHP 8 masks error_reporting inside @) — respect it
    }
    if ($no & (E_DEPRECATED | E_USER_DEPRECATED | E_NOTICE | E_USER_NOTICE)) {
        return true; // never let deprecations/notices break a request (PHP 8.4/8.5 deprecate a lot)
    }
    throw new ErrorException($str, 0, $no, $file, $line);
});

set_exception_handler(static function (Throwable $e): void {
    app_log('fatal', ['msg' => $e->getMessage(), 'at' => basename($e->getFile()) . ':' . $e->getLine()]);
    if (!headers_sent()) {
        json_out(['ok' => false, 'error' => 'server'], 500);
    }
});

register_shutdown_function(static function (): void {
    $err = error_get_last();
    if ($err && ($err['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR))) {
        app_log('shutdown', ['msg' => $err['message'], 'at' => basename($err['file']) . ':' . $err['line']]);
        if (!headers_sent()) {
            json_out(['ok' => false, 'error' => 'server'], 500);
        }
    }
});

/* ---------- logging ---------- */

function app_log(string $event, array $ctx = []): void
{
    $file = APP_DATA . '/app.log';
    if (is_file($file) && filesize($file) > APP_LOG_MAX) {
        @rename($file, $file . '.1');
    }
    $line = gmdate('c') . ' ' . $event . ' ' . json_encode($ctx, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
}

/* ---------- config ---------- */

/** Loads domains/{domain}/{APP_NAME}_secrets.php by walking up from this folder. */
function secrets(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $dir = __DIR__;
    for ($i = 0; $i < 6; $i++) {
        $file = $dir . '/' . APP_NAME . '_secrets.php';
        if (is_file($file)) {
            $data = require $file;
            if (is_array($data)) {
                return $cache = $data;
            }
            break;
        }
        $parent = dirname($dir);
        if ($parent === $dir) {
            break;
        }
        $dir = $parent;
    }
    app_log('config_missing');
    fail(500, 'config');
}

/* ---------- database (optional) ---------- */

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $c = secrets()['db'] ?? fail(500, 'config');
    $pdo = new PDO(
        "mysql:host={$c['host']};dbname={$c['name']};charset=utf8mb4",
        $c['user'],
        $c['pass'],
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
    $pdo->exec("SET time_zone = '+00:00'");
    if (function_exists('migrate')) {
        migrate($pdo); // define migrate(PDO) in your module (see references/MYSQL.md)
    }
    return $pdo;
}

/* ---------- HTTP helpers ---------- */

function json_out(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    if (!preg_grep('/^Cache-Control:/i', headers_list())) {
        header('Cache-Control: no-store');   // default for API answers; a handler may set its own caching
    }
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/**
 * Request path relative to the module. The uCoz proxy strips "/apps", so PHP sees "/{name}/…"
 * for both https://{site}/apps/{name}/… and https://{tech}/{name}/… ("/apps/{name}" is tolerated too).
 */
function route_path(): string
{
    $p = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    foreach (['/apps/' . APP_NAME, '/' . APP_NAME] as $prefix) {
        if ($p === $prefix || str_starts_with($p, $prefix . '/')) {
            $p = substr($p, strlen($prefix));
            break;
        }
    }
    $p = '/' . trim($p, '/');
    return $p === '/index.php' ? '/' : $p;
}

function json_input(): array
{
    $raw = file_get_contents('php://input', false, null, 0, JSON_MAX + 1);
    if ($raw === false || $raw === '') {
        return [];
    }
    if (strlen($raw) > JSON_MAX) {
        fail(413, 'too_large');
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        fail(400, 'bad_json');
    }
    return $data;
}

/** CSRF guard for state-changing requests: browsers send Origin on POST/PUT/DELETE. */
function require_same_origin(): void
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin === '') {
        return; // non-browser client (curl, cron, server-to-server)
    }
    $allowed = secrets()['origins'] ?? [];
    if (!in_array($origin, $allowed, true)) {
        fail(403, 'origin');
    }
}

function client_ip(): string
{
    // Behind the uCoz proxy REMOTE_ADDR is the proxy; the first X-Forwarded-For entry is the client.
    $xff = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
    $ip  = trim(explode(',', $xff)[0] ?? '');
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}

function ip_hash(): string
{
    return hash_hmac('sha256', client_ip(), (string)(secrets()['hash_salt'] ?? APP_NAME));
}

/** Sliding-window rate limit stored in APP_DATA/rl. Throws 429 when exceeded. */
function rate_limit(string $bucket, int $max, int $windowSec): void
{
    $dir = APP_DATA . '/rl';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    $fh = @fopen($dir . '/' . hash('sha256', $bucket) . '.json', 'c+');
    if (!$fh) {
        return;
    }
    flock($fh, LOCK_EX);
    $now  = time();
    $hits = json_decode(stream_get_contents($fh) ?: '[]', true) ?: [];
    $hits = array_values(array_filter($hits, static fn($t) => is_int($t) && $t > $now - $windowSec));
    $over = count($hits) >= $max;
    if (!$over) {
        $hits[] = $now;
        ftruncate($fh, 0);
        rewind($fh);
        fwrite($fh, json_encode($hits));
    }
    flock($fh, LOCK_UN);
    fclose($fh);
    if ($over) {
        fail(429, 'rate_limited', 'Too many requests, try again later.');
    }
}

/**
 * Shared-secret check for cron, webhooks and admin actions (header such as X-Cron-Key).
 * Fails CLOSED: a missing/short secret in config never matches an empty header (hash_equals('', '') is true!).
 * Answers 404 so the endpoint does not reveal itself.
 */
function require_key(string $secretName, string $header): void
{
    $expected = (string)(secrets()[$secretName] ?? '');
    $given    = (string)($_SERVER['HTTP_' . strtoupper(str_replace('-', '_', $header))] ?? '');
    if (strlen($expected) < 16 || !hash_equals($expected, $given)) {
        fail(404, 'not_found');
    }
}

/* ---------- identity (see references/IDENTITY.md) ---------- */

/**
 * ucoz_user() is preloaded by the platform (auto_prepend_file /opt/ucoz/lib/ucoz.php). It decrypts the
 * X-Ucoz-User header, enforces expiry itself, never throws, and returns [] for a guest.
 * Identity exists only on https://{site}/apps/… requests.
 */
function current_user(): ?array
{
    if (!function_exists('ucoz_user')) {
        return null;
    }
    $u = ucoz_user();
    if (!is_array($u) || empty($u['id'])) {
        return null;
    }
    return ['id' => (int)$u['id'], 'login' => (string)($u['user'] ?? ''), 'gid' => (int)($u['gid'] ?? 0)];
}

function require_user(): array
{
    return current_user() ?? fail(401, 'auth', 'Please log in to the site.');
}

/** Staff check: group ids come from secrets (verify ids with users_read groups). */
function require_staff(): array
{
    $u = require_user();
    if (!in_array($u['gid'], secrets()['staff_gids'] ?? [], true)) {
        fail(403, 'forbidden');
    }
    return $u;
}

/* ---------- outbound HTTP ---------- */

/**
 * Small curl wrapper for calls to external services. Never throws: returns ['code' => int, 'body' => string,
 * 'error' => string]. code 0 = network error/timeout. Keep timeouts short — this runs inside a web request.
 */
function http_request(string $method, string $url, ?string $body = null, array $headers = [], int $timeout = 8): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_PROTOCOLS      => CURLPROTO_HTTPS | CURLPROTO_HTTP,
        CURLOPT_USERAGENT      => APP_NAME . '/1.0 (uCoz server script)',
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    $resp = curl_exec($ch);
    return [
        'code'  => (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE),
        'body'  => is_string($resp) ? $resp : '',
        'error' => curl_error($ch),
    ];
}

function http_post_json(string $url, array $data, array $headers = [], int $timeout = 8): array
{
    $headers[] = 'Content-Type: application/json';
    return http_request('POST', $url, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $headers, $timeout);
}

function h(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
