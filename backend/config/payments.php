<?php

return [
    'provider' => env('PAYMENT_PROVIDER', 'sandbox'),
    'click' => [
        'merchant_id' => env('CLICK_MERCHANT_ID', env('PAYMENT_CLICK_MERCHANT_ID')),
        'service_id' => env('CLICK_SERVICE_ID', env('PAYMENT_CLICK_SERVICE_ID')),
        'secret_key' => env('CLICK_SECRET_KEY', env('PAYMENT_CLICK_SECRET_KEY')),
        'return_url' => env('CLICK_RETURN_URL', env('PAYMENT_CLICK_RETURN_URL')),
        // Saved-card (token) billing: Merchant API credentials from the Click cabinet.
        'merchant_user_id' => env('CLICK_MERCHANT_USER_ID'),
        'api_base' => env('CLICK_API_BASE', 'https://api.click.uz/v2/merchant'),
    ],
    // Subscription lifecycle: charge on the 1st of each month (Asia/Tashkent), retry daily during the grace window.
    'billing' => [
        'timezone' => env('BILLING_TIMEZONE', 'Asia/Tashkent'),
        'grace_days' => (int) env('BILLING_GRACE_DAYS', 7),                // unpaid for this long -> access closed
        'data_retention_days' => (int) env('BILLING_DATA_RETENTION_DAYS', 90), // data is kept this long after the lock
        'lgota_notice_days' => (int) env('BILLING_LGOTA_NOTICE_DAYS', 3),
        'max_cards' => (int) env('BILLING_MAX_CARDS', 5),
    ],
    'payme' => [
        'merchant_id' => env('PAYME_MERCHANT_ID'),
        // Payme issues separate keys for the sandbox cabinet and production.
        'key' => env('PAYME_TEST_MODE', false) ? env('PAYME_TEST_KEY') : env('PAYME_KEY'),
        'test_mode' => (bool) env('PAYME_TEST_MODE', false),
        'return_url' => env('PAYME_RETURN_URL'),
    ],
    'cbu' => [
        'url' => env('CBU_EXCHANGE_URL', 'https://cbu.uz/uz/arkhiv-kursov-valyut/json'),
        'timeout' => (int) env('CBU_EXCHANGE_TIMEOUT', 8),
    ],
];
