<?php

namespace App\Http\Controllers;

use App\Services\TelegramBot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

        // The relay (or Telegram) retries on timeouts; an update that was already handled must not run twice
        // (a repeated /start would find its token rotated, a repeated AI question would be answered twice).
        $updateId = $request->input('update_id');
        $seenKey = is_int($updateId) ? "tg-update:{$updateId}" : null;
        if ($seenKey && !Cache::add($seenKey, 1, 3600)) return response()->json(['ok' => true]);

        $message = $request->input('message');
        try {
            if (is_array($message)) (new TelegramBot())->handleMessage($message);
        } catch (\Throwable $e) {
            if ($seenKey) Cache::forget($seenKey);   // let the retry run
            throw $e;
        }
        return response()->json(['ok' => true]);
    }
}
