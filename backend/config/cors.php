<?php

/**
 * CORS for the SPA. The frontend (FRONTEND_URL) may live on a different host than the API,
 * and it sends the nexio_session cookie (credentials: 'include'), so the origin must be listed
 * explicitly — browsers reject a wildcard '*' when credentials are included.
 */
$origins = array_filter(array_map(
    fn ($o) => rtrim(trim($o), '/'),
    array_merge(
        [(string) env('FRONTEND_URL', '')],
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', '')),
        // Origins of the Capacitor mobile app (iOS capacitor://localhost, Android https://localhost). Those requests
        // authenticate with the X-Nexio-Session header, never with a cross-site cookie.
        explode(',', (string) env('MOBILE_APP_ORIGINS', 'capacitor://localhost,https://localhost')),
    ),
));

return [

    // api/public/* (embeddable lead form) is deliberately absent: it answers CORS itself, per form,
    // without credentials. The credentialed SPA policy below must not apply to it.
    'paths' => ['api/v1/*', 'api/health', 'api/oauth/*', 'api/webhooks/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_unique($origins)),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 3600,

    'supports_credentials' => true,

];
