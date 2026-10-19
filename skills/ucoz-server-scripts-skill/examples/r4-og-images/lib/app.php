<?php
/**
 * Recipe R4 — Open Graph preview images (1200×630 JPEG) for site materials. No uAPI key needed.
 *
 * The image URL is the material's own path: /apps/{name}/blog/some-title/2026-10-04-8.jpg for the page
 * /blog/some-title/2026-10-04-8. The module opens that public page on the site, takes its og:title (or <title>),
 * draws the image once and caches it. Only material paths of this site are accepted and the text always comes
 * from the site's own page, so nobody can make the site render arbitrary text. (Putting the title into the URL,
 * ?t=$ENTRY_NAME$, is unsafe and breaks on quotes and &: uCoz templates do not evaluate functions such as
 * str_replace/htmlspecialchars, so a variable cannot be URL-encoded.)
 *
 * Files: public_html/{name}/index.php (templates/php stub), lib/bootstrap.php, lib/app.php (this), .htaccess,
 *        domains/{domain}/{name}_secrets.php: admin_key, hash_salt, site, og (brand settings, optional)
 *        {name}_data/fonts/*.ttf — downloaded by /api/setup (apps_tool cannot upload binary files)
 *
 * URLs:  GET /apps/{name}/api/setup              header X-Admin-Key → downloads fonts once, reports GD
 *        GET /apps/{name}/<material path>.jpg    → image/jpeg
 *
 * Template (inside <head> of the module's material page, e.g. Blog → «Страница материала и комментариев к нему»;
 * the title variable there is $ENTRY_NAME$, the page path is $REQUEST_URI$):
 *   <meta property="og:type" content="article">
 *   <meta property="og:title" content="$ENTRY_NAME$">
 *   <meta property="og:image" content="https://{site}/apps/{name}$REQUEST_URI$.jpg">
 *   <meta property="og:image:width" content="1200"><meta property="og:image:height" content="630">
 */
declare(strict_types=1);

const W = 1200;
const H = 630;
const MAX_TITLE = 140;
const MAX_SUB   = 90;
const CACHE_MAX = 2000;   // files; oldest are pruned
const DESIGN_V  = 3;      // bump after any design change: files drawn with an older version are redrawn
const REFRESH   = 86400;  // a cached image is re-checked against the page after a day (renamed materials)
// Material pages: /<module>/[slug/]<yyyy-mm-dd>-<id> or /<module>/<n>-<n>-<id>[-…] — Latin, digits, _ and - only.
const PATH_RE   = '#^/(blog|news|publ|load|board|dir|stuff)/[A-Za-z0-9_\-/]{1,180}$#';
const FONTS = [   // Google Fonts (OFL), static TTF with Cyrillic
    'bold'    => 'https://raw.githubusercontent.com/google/fonts/main/ofl/ptsans/PT_Sans-Web-Bold.ttf',
    'regular' => 'https://raw.githubusercontent.com/google/fonts/main/ofl/ptsans/PT_Sans-Web-Regular.ttf',
];

function font_path(string $key): string
{
    return APP_DATA . '/fonts/' . $key . '.ttf';
}

function act_setup(): array
{
    require_key('admin_key', 'X-Admin-Key');
    is_dir(APP_DATA . '/fonts') || mkdir(APP_DATA . '/fonts', 0700, true);
    $out = [];
    foreach (FONTS as $key => $url) {
        if (!is_file(font_path($key))) {
            $r = http_request('GET', $url, null, [], 20);
            $magic = substr($r['body'], 0, 4);
            if ($r['code'] !== 200 || !in_array($magic, ["\x00\x01\x00\x00", 'true', 'OTTO'], true)) {
                fail(502, 'font_download_failed', "HTTP {$r['code']}");
            }
            file_put_contents(font_path($key), $r['body']);
        }
        $out[$key] = filesize(font_path($key));
    }
    $gd = function_exists('gd_info') ? gd_info() : [];
    return ['fonts' => $out, 'gd' => ['freetype' => !empty($gd['FreeType Support']), 'png' => !empty($gd['PNG Support'])]];
}

/** Word-wraps $text to $maxWidth px; shrinks the font until it fits in $maxLines. Returns [size, lines]. */
function fit_text(string $text, string $font, int $size, int $minSize, int $maxWidth, int $maxLines): array
{
    for (; $size >= $minSize; $size -= 4) {
        $lines = [];
        $line  = '';
        foreach (preg_split('/\s+/u', $text) as $word) {
            $try = $line === '' ? $word : $line . ' ' . $word;
            $box = imagettfbbox($size, 0, $font, $try);
            if ($box[2] - $box[0] > $maxWidth && $line !== '') {
                $lines[] = $line;
                $line = $word;
            } else {
                $line = $try;
            }
        }
        $lines[] = $line;
        if (count($lines) <= $maxLines) {
            return [$size, $lines];
        }
    }
    $lines = array_slice($lines, 0, $maxLines);
    $lines[$maxLines - 1] = rtrim(mb_substr($lines[$maxLines - 1], 0, -1)) . '…';
    return [$minSize, $lines];
}

function hex_color($img, string $hex)
{
    $hex = ltrim($hex, '#');
    return imagecolorallocate($img, hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2)));
}

function render_og(string $title, string $sub): string
{
    $cfg  = (secrets()['og'] ?? []) + ['bg1' => '#1e3a8a', 'bg2' => '#0f172a', 'fg' => '#ffffff', 'muted' => '#cbd5e1',
                                        'accent' => '#38bdf8', 'site_name' => secrets()['site'] ?? ''];
    $bold = font_path('bold');
    $reg  = font_path('regular');
    if (!is_file($bold) || !is_file($reg)) {
        fail(503, 'not_set_up', 'Run /api/setup first.');
    }
    $img = imagecreatetruecolor(W, H);
    // vertical gradient bg1 → bg2
    [$r1, $g1, $b1] = sscanf(ltrim($cfg['bg1'], '#'), '%02x%02x%02x');
    [$r2, $g2, $b2] = sscanf(ltrim($cfg['bg2'], '#'), '%02x%02x%02x');
    for ($y = 0; $y < H; $y++) {
        $k = $y / (H - 1);
        $c = imagecolorallocate($img, (int)($r1 + ($r2 - $r1) * $k), (int)($g1 + ($g2 - $g1) * $k), (int)($b1 + ($b2 - $b1) * $k));
        imageline($img, 0, $y, W, $y, $c);
    }
    imagefilledrectangle($img, 80, 96, 200, 104, hex_color($img, $cfg['accent']));

    // With a subtitle the title gets 3 lines max, so the subtitle never overlaps it.
    [$size, $lines] = fit_text($title, $bold, 68, 40, W - 160, $sub !== '' ? 3 : 4);
    $y = 150 + $size;
    foreach ($lines as $i => $l) {
        imagettftext($img, $size, 0, 80, $y, hex_color($img, $cfg['fg']), $bold, $l);
        if ($i < count($lines) - 1) {
            $y += (int)($size * 1.3);
        }
    }
    if ($sub !== '') {
        imagettftext($img, 30, 0, 80, $y + 70, hex_color($img, $cfg['muted']), $reg, $sub);   // below the last line
    }
    if ($cfg['site_name'] !== '') {
        imagettftext($img, 28, 0, 80, H - 60, hex_color($img, $cfg['muted']), $bold, $cfg['site_name']);
    }
    ob_start();
    imagejpeg($img, null, 88);
    imagedestroy($img);
    return (string)ob_get_clean();
}

/** Title of a material page of this site (og:title, else <title>), or null when the page is not a 200 HTML page. */
function page_title(string $path): ?string
{
    $site = (string)(secrets()['site'] ?? '');
    $site !== '' || fail(503, 'not_set_up', 'site is required.');
    $r = http_request('GET', 'https://' . $site . $path, null, ['Accept: text/html'], 10);
    if ($r['code'] !== 200) {
        app_log('page', ['path' => $path, 'code' => $r['code']]);
        return null;
    }
    $title = preg_match('#<meta\s+property="og:title"\s+content="([^"]*)"#iu', $r['body'], $m) ? $m[1]
           : (preg_match('#<title>(.*?)</title>#isu', $r['body'], $m) ? $m[1] : '');
    $title = trim(html_entity_decode(strip_tags($title), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    return $title === '' ? null : mb_substr($title, 0, MAX_TITLE);
}

function act_image(string $path): never
{
    $dir   = APP_DATA . '/cache';
    $file  = $dir . '/' . hash('sha256', $path) . '.jpg';
    $fresh = is_file($file) && filemtime($file) > time() - REFRESH && is_file("$file.v" . DESIGN_V);
    if (!$fresh) {
        rate_limit('og:' . ip_hash(), 30, 60);   // only cache misses cost a page fetch and CPU
        $title = page_title($path) ?? fail(404, 'not_found');
        is_dir($dir) || mkdir($dir, 0700, true);
        file_put_contents("$file.tmp", render_og($title, ''));
        rename("$file.tmp", $file);
        touch("$file.v" . DESIGN_V);              // marks the design version the file was drawn with
        if (random_int(1, 50) === 1) {           // occasional pruning
            $all = glob("$dir/*.jpg") ?: [];
            if (count($all) > CACHE_MAX) {
                usort($all, static fn($a, $b) => filemtime($a) <=> filemtime($b));
                foreach (array_slice($all, 0, count($all) - CACHE_MAX) as $old) {
                    array_map('unlink', glob("$old*") ?: []);
                }
            }
        }
    }
    header('Content-Type: image/jpeg');
    header('Cache-Control: public, max-age=86400');
    header('Content-Length: ' . filesize($file));
    header('X-Content-Type-Options: nosniff');
    readfile($file);
    exit;
}

try {
    $path   = route_path();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method !== 'GET') {
        fail(405, 'method');
    }
    // A shared link often carries a query (?utm_…); $REQUEST_URI$ then puts ".jpg" after it: …/2026-10-04-8?utm_source=tg.jpg
    $query = (string)parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY);
    $entry = str_ends_with($path, '.jpg') ? substr($path, 0, -4) : (str_ends_with($query, '.jpg') ? $path : '');
    if ($entry !== '' && preg_match(PATH_RE, $entry) && !str_contains($entry, '..')) {
        act_image($entry);
    }
    if ($path === '/api/setup') {
        json_out(['ok' => true, 'data' => act_setup()]);
        exit;
    }
    fail(404, 'not_found');
} catch (HttpError $e) {
    json_out(['ok' => false, 'error' => $e->getMessage(), 'message' => $e->userMessage], $e->status);
}
