<?php
/**
 * Environment probe for uCoz Server Scripts — TEMPORARY. Delete after use (files_delete, confirm).
 *
 * Deploy:  domains/{domain}/public_html/_probe_<random>/index.php
 *          Replace PROBE_TOKEN with a random 32-hex value first (the file refuses to run otherwise).
 * Call:    https://{site}/apps/_probe_<random>/?t=<PROBE_TOKEN>      (via the site URL so X-Ucoz-User can arrive)
 *
 * Reports capabilities only — no secrets, no personal data (identity is reduced to booleans + gid).
 */
declare(strict_types=1);

const PROBE_TOKEN = '<random 32 hex>';

if (str_starts_with(PROBE_TOKEN, '<') || !hash_equals(PROBE_TOKEN, (string)($_GET['t'] ?? ''))) {
    http_response_code(404);
    exit;
}

$identity = ['helper' => function_exists('ucoz_user'), 'header' => isset($_SERVER['HTTP_X_UCOZ_USER'])];
if ($identity['helper']) {
    try {
        $u = ucoz_user();
        $identity['member'] = is_array($u) && !empty($u['id']);
        $identity['gid']    = $identity['member'] ? (int)($u['gid'] ?? 0) : null;
        $identity['fields'] = $identity['member'] ? array_keys($u) : [];
    } catch (Throwable $e) {
        $identity['error'] = get_class($e);
    }
}

$disabled = array_filter(array_map('trim', explode(',', (string)ini_get('disable_functions'))));
$home     = getenv('HOME') ?: (function_exists('posix_getpwuid') ? (posix_getpwuid(posix_geteuid())['dir'] ?? '') : '');

$out = [
    'php'         => PHP_VERSION,
    'sapi'        => PHP_SAPI,
    'identity'    => $identity,
    'ini'         => array_combine(
        $k = ['memory_limit', 'upload_max_filesize', 'post_max_size', 'max_execution_time', 'max_input_time',
              'display_errors', 'log_errors', 'error_log', 'open_basedir', 'allow_url_fopen', 'session.save_path', 'date.timezone'],
        array_map(static fn($x) => ini_get($x), $k)
    ),
    'disabled_functions' => array_values($disabled),
    'shell_available'    => function_exists('exec') && !in_array('exec', $disabled, true),
    'extensions'         => get_loaded_extensions(),
    'pdo_drivers'        => class_exists('PDO') ? PDO::getAvailableDrivers() : [],
    'paths'              => [
        'script'       => __FILE__,
        'home'         => $home,
        'tmp'          => sys_get_temp_dir(),
        'tmp_writable' => is_writable(sys_get_temp_dir()),
        'dir_writable' => is_writable(__DIR__),
    ],
    'request'            => [
        'uri'         => $_SERVER['REQUEST_URI'] ?? null,
        'host'        => $_SERVER['HTTP_HOST'] ?? null,
        'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? null,
        'xff_present' => isset($_SERVER['HTTP_X_FORWARDED_FOR']),
        'https'       => $_SERVER['HTTPS'] ?? null,
    ],
    'server_time'        => date('c'),
];

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
