<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/** Registers this API with Telegram. Without this, the bot never receives /start and users cannot link. */
class TelegramWebhook extends Command
{
    protected $signature = 'nexio:telegram:webhook {--info : Only show the current webhook state}';
    protected $description = 'Register or inspect the Telegram bot webhook';

    public function handle(): int
    {
        $token = (string) config('telegram.bot_token', '');
        if ($token === '') {
            $this->error('TELEGRAM_BOT_TOKEN sozlanmagan.');
            return self::FAILURE;
        }
        $api = "https://api.telegram.org/bot{$token}";

        try {
            if (!$this->option('info')) {
                $secret = (string) config('telegram.webhook_secret', '');
                $url = (string) config('telegram.webhook_url', '') ?: rtrim((string) config('app.url'), '/') . '/api/webhooks/telegram';
                if ($secret === '') {
                    $this->error('TELEGRAM_WEBHOOK_SECRET sozlanmagan: webhook har bir so‘rovni e’tiborsiz qoldiradi.');
                    return self::FAILURE;
                }
                $response = Http::timeout(15)->post("{$api}/setWebhook", [
                    'url' => $url, 'secret_token' => $secret, 'allowed_updates' => ['message'], 'drop_pending_updates' => true,
                ]);
                if (!$response->successful() || !$response->json('ok')) {
                    $this->error('setWebhook xatosi: HTTP ' . $response->status());
                    return self::FAILURE;
                }
                $this->info("Webhook o‘rnatildi: {$url}");
            }

            $info = Http::timeout(15)->get("{$api}/getWebhookInfo")->json('result');
            $this->line((string) json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return self::SUCCESS;
        } catch (ConnectionException) {
            // Never print the exception: its text contains the request URL, and with it the bot token.
            $this->error('Telegram bilan aloqa yo‘q. HTTPS sertifikati yoki internetni tekshiring.');
            return self::FAILURE;
        }
    }
}
