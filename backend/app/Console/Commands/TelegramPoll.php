<?php

namespace App\Console\Commands;

use App\Services\TelegramBot;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Receives bot messages without a public URL, for local development or a dedicated bot server (set TELEGRAM_RELAY_URL to forward updates to the site instead of using a local database): Telegram cannot reach
 * localhost, so a webhook would never deliver /start there. Telegram refuses polling (HTTP 409)
 * while a webhook is registered for the same bot, so use a separate test bot locally.
 */
class TelegramPoll extends Command
{
    protected $signature = 'nexio:telegram:poll {--once : Fetch one batch and exit} {--drop-webhook : Remove a registered webhook first (polling and webhooks are mutually exclusive)}';
    protected $description = 'Receive Telegram bot messages by polling (local development)';

    public function handle(): int
    {
        $token = (string) config('telegram.bot_token', '');
        if ($token === '') {
            $this->error('TELEGRAM_BOT_TOKEN sozlanmagan.');
            return self::FAILURE;
        }

        $once = (bool) $this->option('once');
        $bot = new TelegramBot();
        $status = $bot->status();
        if ($status['token_valid'] === false) {
            $this->error('TELEGRAM_BOT_TOKEN noto‘g‘ri (HTTP 401). Tokenni @BotFather’dan tekshiring.');
            return self::FAILURE;
        }
        $this->warnIfUsernameDiffers($status['username']);

        $api = "https://api.telegram.org/bot{$token}";
        if ($this->option('drop-webhook')) {
            try { Http::timeout(10)->post("{$api}/deleteWebhook"); $this->line('Webhook o‘chirildi.'); }
            catch (ConnectionException) { $this->warn('deleteWebhook bajarilmadi: Telegram bilan aloqa yo‘q.'); }
        }
        $relay = (string) config('telegram.relay_url', '');
        if ($relay !== '' && (!str_starts_with($relay, 'https://') && !app()->environment('local', 'testing'))) {
            $this->error('TELEGRAM_RELAY_URL https:// bilan boshlanishi kerak.');
            return self::FAILURE;
        }
        if ($relay !== '' && (string) config('telegram.webhook_secret', '') === '') {
            $this->error('Relay uchun TELEGRAM_WEBHOOK_SECRET kerak (sayt bilan bir xil).');
            return self::FAILURE;
        }
        $offset = 0;
        $firstRequest = true;
        $this->info('Telegram xabarlari kutilmoqda. To‘xtatish: Ctrl+C');

        do {
            try {
                // The first request returns at once, so the heartbeat appears without a long-poll delay.
                $response = Http::timeout(35)->get("{$api}/getUpdates", [
                    'timeout' => $once || $firstRequest ? 0 : 25, 'offset' => $offset, 'allowed_updates' => '["message"]',
                ]);
            } catch (ConnectionException $e) {
                // The exception text contains the request URL, and with it the bot token.
                $this->warn('Telegram bilan aloqa yo‘q. HTTPS sertifikati yoki internetni tekshiring.');
                if ($once) return self::FAILURE;
                sleep(3);
                continue;
            }

            if (!$response->successful()) {
                $this->error($this->failureMessage($response));
                return self::FAILURE;
            }
            $firstRequest = false;
            if (!$once && $relay === '') Cache::put(TelegramBot::POLL_HEARTBEAT, true, 60);

            foreach ((array) $response->json('result', []) as $update) {
                if ($relay !== '') {
                    // Relay mode: this server has no database. The site processes the update and answers the user itself.
                    $failure = $this->relay($relay, $update);
                    if ($failure !== null) {
                        $this->error($failure[0]);
                        // 4xx = wrong secret/URL: retrying cannot help. Otherwise keep the offset and retry this update.
                        if ($failure[1] || $once) return self::FAILURE;
                        sleep(5);
                        continue 2;
                    }
                    $offset = (int) $update['update_id'] + 1;
                    $this->line('Xabar saytga uzatildi.');
                    continue;
                }
                $offset = (int) $update['update_id'] + 1;
                if (is_array($update['message'] ?? null)) {
                    try {
                        $bot->handleMessage($update['message']);
                        $this->line('Xabar qayta ishlandi.');
                    } catch (\Throwable $e) {
                        // One bad message (or a DB blip) must not stop a long-running worker.
                        \Illuminate\Support\Facades\Log::error('Telegram message failed', ['exception' => $e::class, 'error' => $e->getMessage()]);
                    }
                }
            }
        } while (!$once);

        // Telegram forgets updates only when asked for a later offset. Without this, the next run
        // replays a /start whose token has rotated and tells the user the link has expired.
        if ($offset > 0) $this->acknowledge($api, $offset);

        return self::SUCCESS;
    }

    private function warnIfUsernameDiffers(?string $username): void
    {
        $configured = ltrim((string) config('telegram.bot_username', ''), '@');
        if ($username === null) return;
        if ($configured !== '' && strcasecmp($configured, $username) !== 0) {
            $this->warn("Token @{$username} botiga tegishli, TELEGRAM_BOT_USERNAME esa @{$configured}. Havolalar @{$username} ga yo‘naltiriladi; .env ni to‘g‘rilang.");
            return;
        }
        $this->line("Bot: @{$username}");
    }

    private function failureMessage(Response $response): string
    {
        if ($response->status() === 401) {
            return 'TELEGRAM_BOT_TOKEN noto‘g‘ri (HTTP 401). Tokenni @BotFather’dan tekshiring.';
        }
        if ($response->status() === 409) {
            return str_contains((string) $response->json('description'), 'getUpdates request')
                ? 'Boshqa nexio:telegram:poll jarayoni allaqachon ishlayapti (HTTP 409). Bir bot uchun bittasi kifoya.'
                : 'Bu botda webhook yoqilgan, polling ishlamaydi (HTTP 409). Lokal test uchun alohida bot ishlating.';
        }
        return 'Telegram xatosi: HTTP ' . $response->status();
    }

    private function acknowledge(string $api, int $offset): void
    {
        try {
            Http::timeout(10)->get("{$api}/getUpdates", ['timeout' => 0, 'offset' => $offset, 'limit' => 1]);
        } catch (ConnectionException) {
            $this->warn('Xabarlar Telegram’da tasdiqlanmadi: keyingi ishga tushirishda takrorlanishi mumkin.');
        }
    }

    /** @return array{0: string, 1: bool}|null [message, permanent] or null when the site accepted the update */
    private function relay(string $url, array $update): ?array
    {
        try {
            $response = Http::timeout(45)->withHeaders(['X-Telegram-Bot-Api-Secret-Token' => (string) config('telegram.webhook_secret')])->post($url, $update);
        } catch (ConnectionException) {
            return ['Sayt bilan aloqa yo‘q, qayta uriniladi.', false];
        }
        if ($response->successful()) return null;
        $permanent = $response->status() >= 400 && $response->status() < 500 && $response->status() !== 429;
        return ["Sayt xabarni qabul qilmadi (HTTP {$response->status()})." . ($permanent ? ' TELEGRAM_WEBHOOK_SECRET va URL ni tekshiring.' : ' Qayta uriniladi.'), $permanent];
    }
}
