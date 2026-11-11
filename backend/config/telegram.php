<?php

return [
    'bot_token' => env('TELEGRAM_BOT_TOKEN', ''),
    'bot_username' => env('TELEGRAM_BOT_USERNAME', ''),
    'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET', ''),
    // true when the bot runs as a separate nexio:telegram:poll process on another server (the site cannot see its heartbeat).
    'external' => (bool) env('TELEGRAM_BOT_EXTERNAL', false),
    'webhook_url' => env('TELEGRAM_WEBHOOK_URL', ''),
];
