<?php

return [
    'provider' => env('PAYMENT_PROVIDER', 'sandbox'),
    'click' => [
        'merchant_id' => env('CLICK_MERCHANT_ID', env('PAYMENT_CLICK_MERCHANT_ID')),
        'service_id' => env('CLICK_SERVICE_ID', env('PAYMENT_CLICK_SERVICE_ID')),
        'secret_key' => env('CLICK_SECRET_KEY', env('PAYMENT_CLICK_SECRET_KEY')),
        'return_url' => env('CLICK_RETURN_URL', env('PAYMENT_CLICK_RETURN_URL')),
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
