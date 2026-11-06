<?php

namespace App\Services;

use App\Support\H;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/** Handles messages sent to the bot, whichever way they arrive: webhook in production, polling locally. */
class TelegramBot
{
    /** Refreshed by a running nexio:telegram:poll; expires soon after the poller stops. */
    public const POLL_HEARTBEAT = 'telegram.poll.heartbeat';

    public function __construct(private NotificationDispatcher $sender = new NotificationDispatcher())
    {
    }

    /**
     * Asks Telegram who the token belongs to and whether messages can reach this app, which needs a
     * webhook or a running poller. Without either, /start waits at Telegram and nothing links.
     * Null means unknown: Telegram could not be reached.
     *
     * @return array{token_valid: ?bool, username: ?string, receiving: ?bool}
     */
    public function status(): array
    {
        $api = 'https://api.telegram.org/bot' . config('telegram.bot_token', '');
        try {
            $me = Http::connectTimeout(3)->timeout(5)->get("{$api}/getMe");
            if ($me->status() === 401) return ['token_valid' => false, 'username' => null, 'receiving' => false];
            $username = $me->json('result.username');
            $receiving = Cache::has(self::POLL_HEARTBEAT)
                || (string) Http::connectTimeout(3)->timeout(5)->get("{$api}/getWebhookInfo")->json('result.url') !== '';
            return ['token_valid' => $me->successful() ? true : null, 'username' => is_string($username) ? $username : null, 'receiving' => $receiving];
        } catch (ConnectionException) {
            // Never surface the exception: its text contains the request URL, and with it the bot token.
            return ['token_valid' => null, 'username' => null, 'receiving' => null];
        }
    }

    public function handleMessage(array $message): void
    {
        if (!isset($message['chat']['id'], $message['text'])) return;

        $chatId = (string) $message['chat']['id'];
        $text = trim((string) $message['text']);

        if ($text === '/stop') {
            DB::table('user_telegram_links')->where('chat_id', $chatId)->update(['chat_id' => null, 'username' => null, 'linked_at' => null]);
            $this->sender->telegram($chatId, 'Telegram bildirishnomalari o‘chirildi.');
            return;
        }
        if (!preg_match('/^\/start\s+([A-Za-z0-9_-]{16,80})$/', $text, $matches)) {
            if (str_starts_with($text, '/start')) {
                $this->sender->telegram($chatId, 'Nexio CRM bildirishnomalarini olish uchun CRM ichidagi qo‘ng‘iroqcha menyusidan «Telegram’ni ulash» tugmasini bosing.');
            }
            return;
        }

        $token = $matches[1];
        if (str_starts_with($token, 'invite_')) {
            $inviteToken = substr($token, 7);
            $invite = DB::table('invitations')->where('token', $inviteToken)->where('status', 'pending')->first();
            if ($invite) {
                $inviteUrl = rtrim((string) config('nexio.frontend_url'), '/') . "/invite/{$inviteToken}";
                $this->sender->telegram($chatId, "Nexio CRM taklifi:\n{$inviteUrl}");
            }
            return;
        }

        $link = DB::table('user_telegram_links')->where('link_token', $token)->first();
        if (!$link) {
            $this->sender->telegram($chatId, 'Havola eskirgan. CRM ichidan «Telegram’ni ulash» tugmasini qayta bosing.');
            return;
        }
        // chat_id is unique: one Telegram account follows one CRM user, the latest link wins.
        // The token is rotated so a forwarded link cannot re-bind this account later.
        DB::table('user_telegram_links')->where('chat_id', $chatId)->where('id', '<>', $link->id)->update(['chat_id' => null, 'username' => null, 'linked_at' => null]);
        DB::table('user_telegram_links')->where('id', $link->id)->update([
            'chat_id' => $chatId, 'username' => $message['from']['username'] ?? null, 'linked_at' => H::now(), 'link_token' => H::newToken(),
        ]);
        $this->sender->telegram($chatId, 'Nexio CRM bilan Telegram bog‘landi. Vazifa, loyiha va boshqa bildirishnomalar shu yerga keladi. O‘chirish uchun: /stop');
    }
}
