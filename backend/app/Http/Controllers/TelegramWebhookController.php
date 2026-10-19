<?php

namespace App\Http\Controllers;

use App\Services\NotificationDispatcher;
use App\Support\H;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TelegramWebhookController
{
    public function handle(Request $request)
    {
        $secret = (string) config('telegram.webhook_secret', '');
        if ($secret === '' || !hash_equals($secret, (string) $request->header('X-Telegram-Bot-Api-Secret-Token'))) return response()->json(['ok' => true]);
        $message = $request->input('message');
        if (!is_array($message) || !isset($message['chat']['id'], $message['text'])) return response()->json(['ok' => true]);
        if (!preg_match('/^\/start\s+([A-Za-z0-9_-]{16,80})$/', (string) $message['text'], $matches)) return response()->json(['ok' => true]);
        $token = $matches[1];
        if (str_starts_with($token, 'invite_')) {
            $inviteToken = substr($token, 7);
            $invite = DB::table('invitations')->where('token', $inviteToken)->where('status', 'pending')->first();
            if ($invite) {
                $inviteUrl = rtrim((string) config('nexio.frontend_url'), '/') . "/invite/{$inviteToken}";
                (new NotificationDispatcher())->telegram((string) $message['chat']['id'], "Nexio CRM taklifi:\n{$inviteUrl}");
            }
            return response()->json(['ok' => true]);
        }
        $link = DB::table('user_telegram_links')->where('link_token', $token)->first();
        if (!$link) return response()->json(['ok' => true]);
        DB::table('user_telegram_links')->where('id', $link->id)->update(['chat_id' => (string) $message['chat']['id'], 'username' => $message['from']['username'] ?? null, 'linked_at' => H::now()]);
        (new NotificationDispatcher())->telegram((string) $message['chat']['id'], 'Nexio CRM bilan Telegram bog‘landi.');
        return response()->json(['ok' => true]);
    }
}
