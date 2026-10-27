<?php
/**
 * Secrets for module {name}.
 * Deploy to: domains/{domain}/{name}_secrets.php   (OUTSIDE public_html), then files_chmod perm "384" (= 0600 — apps_write reads perm as DECIMAL).
 * Never commit real values. Never echo them in chat or reports.
 * Keep unrelated keys at the TOP level (do not nest them inside 'db').
 */
return [
    'site'       => '{site}',                       // used by uapi() (references/UAPI.md §3) and R4
    'origins'    => ['https://{site}'],             // allowed Origin values for POST (add https://{tech} if used)
    'hash_salt'  => '<random 32 hex>',              // bin2hex(random_bytes(16))
    'staff_gids' => [],                             // group ids allowed to see admin data (verify via users_read groups)

    // Optional: MySQL created with apps_write db_create_with_user (names carry the {username}_ prefix)
    'db' => [
        'host' => 'localhost',
        'name' => '{username}_main',
        'user' => '{username}_main',
        'pass' => '<from db_create_with_user response>',
    ],

    // Optional — keys checked with require_key() must be random and >= 16 chars (fail closed)
    'uapi_token'     => '<uAPI key issued by the site owner on a dedicated low-privilege user>',
    'cron_key'       => '<random 32 hex>',            // X-Cron-Key for cron endpoints
    'admin_key'      => '<random 32 hex>',            // X-Admin-Key for setup/maintenance endpoints
    'webhook_secret' => '<random 64 hex>',            // R3: HMAC secret shared with the sender

    // Optional — R1 notifications
    'tg_token'           => '<bot token from @BotFather>',
    'tg_chat'            => '<chat id or @channel>',
    'notify_webhook_url' => 'https://…',              // CRM / automation / R3 receiver
    'notify_secret'      => '<random 64 hex>',
];
