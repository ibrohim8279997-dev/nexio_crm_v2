<?php

return [
    // off  = feature disabled
    // site = the production site (crmb.nexiolab.uz): serves /api/bots/* and pushes change notices to the bot server
    // bot  = the bot server: keeps a mirror of the site database and answers Telegram messages from it
    'role' => env('BOTS_ROLE', 'off'),

    // Shared by both servers. Never sent over the wire: requests are signed with it (HMAC-SHA256).
    'secret' => env('BOTS_SECRET', ''),

    // site: where to announce changes, e.g. https://bot.example.uz/api/bots/notify
    'push_url' => env('BOTS_PUSH_URL', ''),

    // bot: the site to mirror
    'site_url' => env('BOTS_SITE_URL', 'https://crmb.nexiolab.uz'),

    // Accepted clock difference for a signed request, in seconds (also the replay window).
    'skew' => 300,

    // Rows per page when the bot pulls a table.
    'page' => 1000,

    // Runtime-only tables are never mirrored: live session ids would be account takeover on a second server.
    'exclude' => ['sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'migrations', 'password_reset_tokens', 'password_resets'],
];
