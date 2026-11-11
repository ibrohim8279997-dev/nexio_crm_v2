<?php

namespace App\Services;

use App\Support\H;
use Illuminate\Support\Facades\DB;

/** Writes to user_telegram_links. Always executed on the site: it owns the data, the bot server only mirrors it. */
final class TelegramLinks
{
    /** @return bool false when the link token is unknown or expired */
    public static function bind(string $token, string $chatId, ?string $username): bool
    {
        $link = DB::table('user_telegram_links')->where('link_token', $token)->first();
        if (!$link) return false;
        // chat_id is unique: one Telegram account follows one CRM user, the latest link wins.
        // The token is rotated so a forwarded link cannot re-bind this account later.
        DB::table('user_telegram_links')->where('chat_id', $chatId)->where('id', '<>', $link->id)->update(['chat_id' => null, 'username' => null, 'linked_at' => null]);
        DB::table('user_telegram_links')->where('id', $link->id)->update([
            'chat_id' => $chatId, 'username' => $username, 'linked_at' => H::now(), 'link_token' => H::newToken(),
        ]);
        return true;
    }

    public static function unbind(string $chatId): void
    {
        DB::table('user_telegram_links')->where('chat_id', $chatId)->update(['chat_id' => null, 'username' => null, 'linked_at' => null]);
    }
}
