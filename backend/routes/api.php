<?php
use App\Support\H;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\ClickWebhookController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\BotsSyncController;

// Health probe — parity with the Worker's /api/health.
Route::get('/health', function () {
    $ok = false;
    try { DB::selectOne('SELECT 1 AS ok'); $ok = true; } catch (\Throwable) { $ok = false; }
    return H::json(['status' => $ok ? 'ok' : 'degraded', 'service' => 'nexio-crm', 'time' => now()->toIso8601String()]);
});

Route::post('/webhooks/click', [ClickWebhookController::class, 'handle']);
Route::post('/webhooks/payme', [\App\Http\Controllers\PaymeWebhookController::class, 'handle']);
Route::post('/webhooks/ai-voice', [\App\Http\Controllers\AiVoiceWebhookController::class, 'handle']);
Route::post('/oauth/token', [\App\Http\Controllers\OAuthController::class, 'token']);
Route::get('/public/forms/{key}', [\App\Http\Controllers\Api\V1\LeadFormController::class, 'publicConfig']);
Route::post('/public/forms/{key}/submit', [\App\Http\Controllers\Api\V1\LeadFormController::class, 'submit']);
Route::options('/public/forms/{key}/submit', [\App\Http\Controllers\Api\V1\LeadFormController::class, 'preflight']);
Route::post('/webhooks/telegram', [TelegramWebhookController::class, 'handle']);

// Site <-> bot server (signed requests only; see config/bots.php).
Route::prefix('bots')->group(function () {
    Route::get('/tables', [BotsSyncController::class, 'tables']);
    Route::get('/tables/{table}/rows', [BotsSyncController::class, 'rows'])->where('table', '[A-Za-z0-9_]+');
    Route::post('/telegram/link', [BotsSyncController::class, 'telegramLink']);
    Route::post('/telegram/unlink', [BotsSyncController::class, 'telegramUnlink']);
    Route::post('/notify', [BotsSyncController::class, 'notify']);
});

require __DIR__.'/api_v1.php';
