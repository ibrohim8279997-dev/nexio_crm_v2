<?php

namespace App\Http\Controllers;

use App\Services\TelegramBot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController
{
    public function handle(Request $request)
    {
        $secret = (string) config('telegram.webhook_secret', '');
        if ($secret === '') {
            Log::warning('Telegram webhook ignored: TELEGRAM_WEBHOOK_SECRET is not configured');
            // 503, not 200: Telegram records it in getWebhookInfo.last_error_message, so the misconfiguration is visible.
            return response()->json(['ok' => false], 503);
        }
        if (!hash_equals($secret, (string) $request->header('X-Telegram-Bot-Api-Secret-Token'))) {
            Log::warning('Telegram webhook rejected: secret token mismatch (re-run php artisan nexio:telegram:webhook)');
            return response()->json(['ok' => false], 403);
        }

        $message = $request->input('message');
        if (is_array($message)) (new TelegramBot())->handleMessage($message);
        return response()->json(['ok' => true]);
    }
}
