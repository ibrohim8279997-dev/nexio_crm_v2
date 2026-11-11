<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/** Registers this API with Telegram. Without this, the bot never receives /start and users cannot link. */
class TelegramWebhook extends Command
{
    protected $signature = 'nexio:telegram:webhook {--info : Only show the current webhook state} {--ensure : Production only: register the webhook if it is missing, points elsewhere or reports errors}';
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
            if ($this->option('ensure')) return $this->ensure($api);
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

    private function desiredUrl(): string
    {
        return (string) config('telegram.webhook_url', '') ?: rtrim((string) config('app.url'), '/') . '/api/webhooks/telegram';
    }

    /** Self-healing, safe to run from deploy and the scheduler. Does nothing outside production (local dev uses the poller). */
    private function ensure(string $api): int
    {
        if (!app()->isProduction()) { $this->line('Skipped: not production.'); return self::SUCCESS; }
        $secret = (string) config('telegram.webhook_secret', '');
        $url = $this->desiredUrl();
        if ($secret === '' || !str_starts_with($url, 'https://')) {
            $this->error('TELEGRAM_WEBHOOK_SECRET va HTTPS APP_URL/TELEGRAM_WEBHOOK_URL kerak (Telegram faqat HTTPS qabul qiladi).');
            return self::FAILURE;
        }
        $info = (array) Http::timeout(15)->get("{$api}/getWebhookInfo")->json('result');
        $recentError = !empty($info['last_error_date']) && $info['last_error_date'] > time() - 3600;
        if (($info['url'] ?? '') === $url && !$recentError) { $this->info('Webhook OK.'); return self::SUCCESS; }
        $response = Http::timeout(15)->post("{$api}/setWebhook", ['url' => $url, 'secret_token' => $secret, 'allowed_updates' => ['message']]);
        if (!$response->successful() || !$response->json('ok')) {
            $this->error('setWebhook xatosi: HTTP ' . $response->status());
            return self::FAILURE;
        }
        $this->info("Webhook qayta o‘rnatildi: {$url}");
        return self::SUCCESS;
    }
}
