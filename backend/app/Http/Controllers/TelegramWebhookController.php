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
            return response()->json(['ok' => true]);
        }
        if (!hash_equals($secret, (string) $request->header('X-Telegram-Bot-Api-Secret-Token'))) return response()->json(['ok' => true]);

        $message = $request->input('message');
        if (is_array($message)) (new TelegramBot())->handleMessage($message);
        return response()->json(['ok' => true]);
    }
}
