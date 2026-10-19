<?php

namespace App\Services;

use App\Support\H;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class NotificationDispatcher
{
    public function notify(string $userId, ?string $tenantId, string $type, string $title, ?string $body = null, ?string $url = null): void
    {
        $duplicate = DB::table('notifications')->where('user_id', $userId)->where('type', $type)
            ->where('title', $title)->where('body', $body)->where('created_at', '>=', now()->subDay())->exists();
        if ($duplicate) return;
        DB::table('notifications')->insert([
            'id' => H::newId('ntf'), 'tenant_id' => $tenantId, 'user_id' => $userId, 'type' => $type,
            'title' => $title, 'body' => $body, 'action_url' => $url, 'created_at' => H::now(),
        ]);
    }

    public function email(string $address, string $subject, string $body): bool
    {
        if (!config('mail.mailers.smtp.host') || !config('mail.mailers.smtp.username')) return false;
        Mail::raw($body, fn ($message) => $message->to($address)->subject($subject));
        return true;
    }

    public function telegram(string $chatId, string $text): bool
    {
        $token = (string) config('telegram.bot_token', '');
        if ($token === '') return false;
        $response = Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", ['chat_id' => $chatId, 'text' => $text]);
        return $response->successful();
    }

    public function whatsapp(string $to, string $text): bool
    {
        $config = (array) config('services.whatsapp', []);
        if (empty($config['api_url']) || empty($config['access_token']) || empty($config['phone_number_id'])) return false;
        $response = Http::withToken((string) $config['access_token'])
            ->acceptJson()->timeout(15)
            ->post(rtrim((string) $config['api_url'], '/') . '/' . rawurlencode((string) $config['phone_number_id']) . '/messages', [
                'messaging_product' => 'whatsapp', 'to' => $to, 'type' => 'text',
                'text' => ['preview_url' => false, 'body' => $text],
            ]);
        return $response->successful();
    }
}
