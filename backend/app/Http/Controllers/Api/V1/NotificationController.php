<?php

namespace App\Http\Controllers\Api\V1;

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
        return H::json(['data' => $rows, 'unread' => $unread]);
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
            DB::table('user_telegram_links')->insert(['id' => H::newId('tgl'), 'user_id' => $user['id'], 'chat_id' => '', 'link_token' => $token, 'created_at' => H::now()]);
        }
        $username = ltrim((string) config('telegram.bot_username', ''), '@');
        return H::json(['configured' => $username !== '', 'url' => $username !== '' ? "https://t.me/{$username}?start={$token}" : null]);
    }
}
