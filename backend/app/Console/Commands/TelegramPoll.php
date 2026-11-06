<?php

namespace App\Console\Commands;

use App\Services\TelegramBot;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Receives bot messages without a public URL, for local development: Telegram cannot reach
 * localhost, so a webhook would never deliver /start there. Telegram refuses polling (HTTP 409)
 * while a webhook is registered for the same bot, so use a separate test bot locally.
 */
class TelegramPoll extends Command
{
    protected $signature = 'nexio:telegram:poll {--once : Fetch one batch and exit}';
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
            if (!$once) Cache::put(TelegramBot::POLL_HEARTBEAT, true, 60);

            foreach ((array) $response->json('result', []) as $update) {
                $offset = (int) $update['update_id'] + 1;
                if (is_array($update['message'] ?? null)) {
                    $bot->handleMessage($update['message']);
                    $this->line('Xabar qayta ishlandi.');
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
}
