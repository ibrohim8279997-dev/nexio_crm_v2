<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/** Events for the platform operators (superadmins): in-app notification plus their linked Telegram chat. */
final class PlatformAlerts
{
    public function notify(string $title, ?string $body = null, ?string $url = null, string $type = 'info'): void
    {
        try {
            $sender = new NotificationDispatcher();
            foreach (DB::table('users')->where('is_platform_admin', 1)->pluck('id') as $userId) {
                $sender->notify((string) $userId, null, $type, $title, $body, $url, false);
            }
        } catch (\Throwable $e) {
            // An alert must never fail the business action that raised it.
            Log::warning('Platform alert failed', ['error' => $e->getMessage()]);
        }
    }
}
