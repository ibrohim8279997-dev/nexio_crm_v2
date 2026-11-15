<?php

return [
    // Encrypted database copies sent to the platform operators in Telegram (see TelegramBackup).
    'telegram' => [
        'enabled' => (bool) env('BACKUP_TELEGRAM_ENABLED', false),
        // Passphrase for AES-256. Keep a copy OUTSIDE the server (password manager): without it the backups cannot be opened.
        'key' => (string) env('BACKUP_ENCRYPTION_KEY', ''),
        'every_minutes' => max(30, (int) env('BACKUP_TELEGRAM_EVERY_MINUTES', 90)),
        // Extra chat ids (a private group/channel where the bot is admin). They work even when the database is down.
        'chat_ids' => array_values(array_filter(array_map('trim', explode(',', (string) env('BACKUP_TELEGRAM_CHAT_IDS', ''))))),
        // Telegram bots may upload up to 50 MB per file: larger backups are split.
        'part_bytes' => 45 * 1024 * 1024,
        // Above this size the backup is not sent (too heavy for a chat): use off-server storage instead.
        'max_mb' => (int) env('BACKUP_TELEGRAM_MAX_MB', 400),
    ],
];
