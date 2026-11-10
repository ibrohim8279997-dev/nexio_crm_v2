<?php

/**
 * Nexio Plugin System configuration.
 *
 * The secrets key is read from the environment only. It is never stored in the database and
 * never in the repository, so a database dump does not expose provider credentials.
 */
return [

    'frontend_url' => rtrim((string) env('FRONTEND_URL', env('APP_URL', 'http://localhost:3000')), '/'),

    'plugins' => [

        /*
         * The ONLY directories whose PHP is ever loaded. Official plugin code lives here and
         * is part of the reviewed Nexio Lab codebase. Uploaded packages are never included.
         */
        'paths' => array_values(array_filter([base_path('plugins')])),

        /* Encryption for plugin_secrets.ciphertext. No fallback: unset key => explicit error. */
        'secrets_key' => env('PLUGIN_SECRETS_KEY'),

        /* Nexio core version, used for min_nexio_version compatibility checks. */
        'nexio_version' => env('NEXIO_VERSION', '2.0.0'),

        /*
         * Third-party executable plugins are disabled until a real isolation runtime exists.
         * With this false, installing a third_party plugin is refused with a clear error
         * instead of pretending the request is safe.
         */
        'sandbox_available' => (bool) env('PLUGIN_SANDBOX_AVAILABLE', false),

        /* Upload guards. A package exceeding any of these is rejected, not partially accepted. */
        'package' => [
            'max_bytes' => 20 * 1024 * 1024,
            'max_uncompressed_bytes' => 80 * 1024 * 1024,
            'max_entries' => 500,
            'max_compression_ratio' => 120,
            'allowed_extensions' => [
                'json', 'md', 'txt', 'php', 'js', 'css', 'svg', 'png', 'jpg', 'jpeg', 'webp',
                'woff', 'woff2', 'yaml', 'yml', 'sql', 'csv', 'map',
            ],
        ],

        /*
         * Explicit allowlist of outbound hosts per plugin. An official plugin may only call
         * hosts listed here (SSRF guard). An empty list means "no outbound network at all".
         *
         * Only hosts confirmed against official documentation are listed. Sources:
         *  - Yandex Delivery (Russia, other-day/platform API): production host
         *    b2b-authproxy.taxi.yandex.net, test host b2b.taxi.tst.yandex.net.
         *    https://yandex.ru/support/delivery-profile/ru/api/other-day/access
         *    The separate Yandex Express API uses b2b.taxi.yandex.net and is NOT enabled here.
         *  - Telephony and Uzum are user-supplied vendor connections. They do not use a global
         *    host allowlist; OutboundEndpoint validates each connection as HTTPS and rejects
         *    localhost/private/link-local targets before opening a socket.
         */
        'allowed_outbound_hosts' => [
            'nexio.telephony' => [],
            'nexio.delivery' => [],
            'nexio.uzum.delivery' => [],
            'nexio.yandex.delivery' => [
                'b2b-authproxy.taxi.yandex.net',
                'b2b.taxi.tst.yandex.net',
            ],
        ],

        /*
         * Safe-by-default policy. When Super Admin has configured no rule for a plugin/plan,
         * the platform DENIES. It never silently allows an unbounded operation.
         */
        'default_policy' => [
            'can_install' => false,
            'can_use' => false,
            'unlimited_when_unconfigured' => false,
        ],

        /* Default enforcement policy for a metric that has no explicit limit row. */
        'default_enforcement' => 'BLOCK',
    ],

    /*
     * Voice provider callbacks (POST /api/webhooks/ai-voice). The provider signs
     * "{timestamp}.{raw body}" with HMAC-SHA256 using this secret. Empty = endpoint disabled.
     */
    'ai_voice' => [
        'webhook_secret' => env('AI_VOICE_WEBHOOK_SECRET', ''),
        'tolerance_seconds' => 300,
    ],
];
