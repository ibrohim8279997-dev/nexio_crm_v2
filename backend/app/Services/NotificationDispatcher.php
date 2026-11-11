<?php

namespace App\Services;

use App\Support\H;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationDispatcher
{
    /** Audited actions that notify their actor: [title, page]. Assigned work is notified where it is assigned. */
    private const AUDIT_NOTICES = [
        'organization.created' => ['Yangi kompaniya', '/app/companies'],
        'contact.created' => ['Yangi kontakt', '/app/contacts'],
        'product.created' => ['Yangi katalog pozitsiyasi', '/app/catalog'],
        'document.created' => ['Yangi hujjat', '/app/documents'],
        'document.proposal_generated' => ['Tijorat taklifi tayyorlandi', '/app/documents'],
        'automation.created' => ['Yangi avtomatlashtirish', '/app/automations'],
        'nexio_service.requested' => ['Xizmat so‘rovi yuborildi', '/app/nexio-services'],
        'user.invited' => ['Taklif yuborildi', '/app/team'],
    ];

    public function fromAudit(?string $tenantId, ?string $actorId, string $action, mixed $meta): void
    {
        $notice = self::AUDIT_NOTICES[$action] ?? null;
        if (!$notice) return;
        $body = is_array($meta) ? ($meta['name'] ?? $meta['title'] ?? $meta['email'] ?? null) : null;
        $this->notifyMember($tenantId, $actorId, 'info', $notice[0], is_string($body) ? $body : null, $notice[1]);
    }

    /** Stores an in-app notification and mirrors it to the user's linked Telegram chat. */
    public function notify(string $userId, ?string $tenantId, string $type, string $title, ?string $body = null, ?string $url = null, bool $dedupe = true): void
    {
        if ($dedupe) {
            $duplicate = DB::table('notifications')->where('user_id', $userId)->where('type', $type)
                ->where('title', $title)->where('body', $body)->where('created_at', '>=', now()->subDay())->exists();
            if ($duplicate) return;
        }
        DB::table('notifications')->insert([
            'id' => H::newId('ntf'), 'tenant_id' => $tenantId, 'user_id' => $userId, 'type' => $type,
            'title' => $title, 'body' => $body, 'action_url' => $url, 'created_at' => H::now(),
        ]);
        $this->pushTelegram($userId, $title, $body, $url);
        $this->pushDevices($userId, $title, $body, $url);
    }

    /**
     * Notifies one tenant member about a CRM event. The target id comes from request input, so
     * membership is checked here: a foreign id must never receive this tenant's record titles.
     * A notification failure must not fail the business action that triggered it.
     */
    public function notifyMember(?string $tenantId, ?string $userId, string $type, string $title, ?string $body = null, ?string $url = null): void
    {
        if (!$tenantId || !$userId) return;
        try {
            $isMember = DB::table('tenant_memberships')->where('tenant_id', $tenantId)->where('user_id', $userId)->where('status', 'active')->exists();
            if ($isMember) $this->notify($userId, $tenantId, $type, $title, $body, $url, false);
        } catch (\Throwable $e) {
            Log::warning('Notification failed', ['tenant' => $tenantId, 'error' => $e->getMessage()]);
        }
    }

    /** Notifies the owners and admins of a tenant, for events nobody is personally assigned to. */
    public function notifyAdmins(?string $tenantId, string $type, string $title, ?string $body = null, ?string $url = null): void
    {
        if (!$tenantId) return;
        $admins = DB::table('tenant_memberships')->where('tenant_id', $tenantId)->where('status', 'active')
            ->whereIn('role', ['tenant_owner', 'tenant_admin'])->pluck('user_id');
        foreach ($admins as $userId) {
            $this->notifyMember($tenantId, (string) $userId, $type, $title, $body, $url);
        }
    }

    /** Tells a tenant's admins that a plugin changed state. Plugin operations never call this: they can run on every read. */
    public function pluginChanged(?string $tenantId, string $pluginId, string $type, string $title): void
    {
        try {
            $name = DB::table('plugins')->where('id', $pluginId)->value('name');
            $this->notifyAdmins($tenantId, $type, $title, (string) ($name ?? $pluginId), "/app/plugins/{$pluginId}");
        } catch (\Throwable $e) {
            Log::warning('Notification failed', ['tenant' => $tenantId, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Something broke for a tenant (plugin, telephony, ...): tell its admins and the platform operators.
     * Repeats of the same problem are muted for an hour so a failing integration cannot flood Telegram.
     */
    public function problem(?string $tenantId, string $source, string $detail): void
    {
        try {
            if (!Cache::add('problem:' . ($tenantId ?? 'platform') . ':' . sha1($source), 1, 3600)) return;
            $title = "⚠️ {$source}";
            $this->notifyAdmins($tenantId, 'error', $title, $detail, '/app/plugins');
            $company = $tenantId ? DB::table('tenants')->where('id', $tenantId)->value('name') : null;
            (new PlatformAlerts())->notify($title, trim(($company ? "{$company}: " : '') . $detail), $tenantId ? "/platform/tenants/{$tenantId}" : null, 'error');
        } catch (\Throwable $e) {
            Log::warning('Problem notification failed', ['error' => $e->getMessage()]);
        }
    }

    public function mailConfigured(): bool
    {
        return (bool) config('mail.mailers.smtp.host') && (bool) config('mail.mailers.smtp.username');
    }

    public function email(string $address, string $subject, string $body): bool
    {
        if (!$this->mailConfigured()) return false;
        try {
            Mail::raw($body, fn ($message) => $message->to($address)->subject($subject));
            return true;
        } catch (\Throwable $e) {
            Log::warning('Email delivery failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function telegram(string $chatId, string $text): bool
    {
        $token = (string) config('telegram.bot_token', '');
        if ($token === '') return false;
        try {
            $response = Http::connectTimeout(3)->timeout(8)->post("https://api.telegram.org/bot{$token}/sendMessage", ['chat_id' => $chatId, 'text' => $text]);
            if ($response->failed()) Log::warning('Telegram delivery failed', ['status' => $response->status()]);
            return $response->successful();
        } catch (\Throwable $e) {
            // The exception message can contain the request URL, which embeds the bot token.
            Log::warning('Telegram delivery failed', ['exception' => $e::class]);
            return false;
        }
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

    /** Mobile push (FCM), sent like Telegram: after the response, never failing the CRM action. */
    private function pushDevices(string $userId, string $title, ?string $body, ?string $url): void
    {
        if (!FcmPush::configured()) return;
        $send = fn () => (new FcmPush())->sendToUser($userId, $title, $body, $url);
        app()->runningInConsole() ? $send() : app()->terminating($send);
    }

    /** Sends after the HTTP response so a slow Telegram API never delays a CRM action. */
    private function pushTelegram(string $userId, string $title, ?string $body, ?string $url): void
    {
        $chatId = DB::table('user_telegram_links')->where('user_id', $userId)->whereNotNull('chat_id')->where('chat_id', '<>', '')->value('chat_id');
        if (!$chatId) return;
        $link = $url ? rtrim((string) config('nexio.frontend_url'), '/') . $url : null;
        $text = implode("\n", array_filter(["🔔 {$title}", $body, $link]));
        $send = fn () => $this->telegram((string) $chatId, $text);
        if (app()->runningInConsole()) {
            $send();
        } else {
            app()->terminating($send);
        }
    }
}
