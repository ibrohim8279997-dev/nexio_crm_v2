<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\TelegramBot;
use App\Support\Ctx;
use App\Support\H;
use Illuminate\Support\Facades\DB;

class NotificationController
{
    public function index(Ctx $ctx)
    {
        $user = $ctx->auth();
        $limit = min(100, max(1, (int) ($ctx->query('limit') ?: 30)));
        $rows = DB::table('notifications')->where('user_id', $user['id'])
            ->orderByDesc('created_at')->limit($limit)->get();
        $unread = DB::table('notifications')->where('user_id', $user['id'])->whereNull('read_at')->count();
        $linked = DB::table('user_telegram_links')->where('user_id', $user['id'])->whereNotNull('chat_id')->exists();
        return H::json(['data' => $rows, 'unread' => $unread, 'telegram' => ['configured' => $this->botUsername() !== '', 'linked' => $linked]]);
    }

    public function read(Ctx $ctx)
    {
        $user = $ctx->auth();
        DB::table('notifications')->where('id', $ctx->params['id'])->where('user_id', $user['id'])->update(['read_at' => H::now()]);
        return H::json(['ok' => true]);
    }

    public function readAll(Ctx $ctx)
    {
        $user = $ctx->auth();
        DB::table('notifications')->where('user_id', $user['id'])->whereNull('read_at')->update(['read_at' => H::now()]);
        return H::json(['ok' => true]);
    }

    public function telegramLink(Ctx $ctx)
    {
        $user = $ctx->auth();
        $token = H::newToken();
        $link = DB::table('user_telegram_links')->where('user_id', $user['id'])->first();
        if ($link) {
            DB::table('user_telegram_links')->where('id', $link->id)->update(['link_token' => $token]);
        } else {
            DB::table('user_telegram_links')->insert(['id' => H::newId('tgl'), 'user_id' => $user['id'], 'chat_id' => null, 'link_token' => $token, 'created_at' => H::now()]);
        }
        $username = $this->botUsername();
        if ($username === '') return H::json(['configured' => false, 'url' => null, 'warning' => null]);

        $status = (new TelegramBot())->status();
        if ($status['token_valid'] === false) {
            return H::json(['configured' => false, 'url' => null, 'warning' => 'Telegram bot tokeni noto‘g‘ri. Administrator tokenni yangilashi kerak.']);
        }
        // The token's own bot is what will receive /start, even if TELEGRAM_BOT_USERNAME went stale.
        $username = $status['username'] ?? $username;
        return H::json([
            'configured' => true,
            'url' => "https://t.me/{$username}?start={$token}",
            'warning' => $status['receiving'] === false ? $this->notReceivingWarning() : null,
        ]);
    }

    private function notReceivingWarning(): string
    {
        return app()->isProduction()
            ? 'Telegram bot hozir xabar qabul qilmayapti. Administratorga murojaat qiling.'
            : 'Telegram bot hozir xabar qabul qilmayapti. Ulanish tugashi uchun serverda «php artisan nexio:telegram:poll» ni ishga tushiring.';
    }

    public function telegramUnlink(Ctx $ctx)
    {
        $user = $ctx->auth();
        DB::table('user_telegram_links')->where('user_id', $user['id'])->update(['chat_id' => null, 'username' => null, 'linked_at' => null]);
        return H::json(['ok' => true]);
    }

    /** Registers (or moves to this user) the FCM token of the mobile app on this device. */
    public function registerDevice(Ctx $ctx)
    {
        $user = $ctx->auth();
        $b = H::body($ctx);
        $token = H::str($b['token'] ?? null, 'token', true, 512);
        $platform = H::oneOf($b['platform'] ?? null, 'platform', ['android', 'ios']);
        $now = H::now();
        $values = ['user_id' => $user['id'], 'tenant_id' => $ctx->tenantId, 'platform' => $platform, 'updated_at' => $now];
        DB::table('device_tokens')->where('token', $token)->exists()
            ? DB::table('device_tokens')->where('token', $token)->update($values)
            : DB::table('device_tokens')->insert($values + ['id' => H::newId('dev'), 'token' => $token, 'created_at' => $now]);
        return H::json(['ok' => true]);
    }

    /** Called on logout so the next person using this device does not receive the previous user's notifications. */
    public function unregisterDevice(Ctx $ctx)
    {
        $user = $ctx->auth();
        $token = H::str(H::body($ctx)['token'] ?? null, 'token', true, 512);
        DB::table('device_tokens')->where('token', $token)->where('user_id', $user['id'])->delete();
        return H::json(['ok' => true]);
    }

    private function botUsername(): string
    {
        if ((string) config('telegram.bot_token', '') === '') return '';
        return ltrim((string) config('telegram.bot_username', ''), '@');
    }
}
