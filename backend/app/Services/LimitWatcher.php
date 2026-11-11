<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** Compares each tenant's consumption with its plan and alerts once per metric, level and month. */
final class LimitWatcher
{
    public const NEAR = 0.8;

    private const LABELS = ['seats' => 'Foydalanuvchilar', 'storage' => 'Xotira (GB)', 'ai_minutes' => 'AI daqiqalari', 'api_requests' => 'API so‘rovlari'];

    /** @return array<string, array{used: float, limit: float}> */
    public function usage(string $tenantId): array
    {
        $plan = DB::table('subscriptions as s')->join('plans as p', 'p.id', '=', 's.plan_id')->where('s.tenant_id', $tenantId)
            ->orderByDesc('s.created_at')->first(['p.seats', 'p.storage_gb', 'p.ai_minutes', 'p.api_requests']);
        if (!$plan) return [];
        $monthStart = gmdate('Y-m-01 00:00:00');
        return [
            'seats' => ['used' => (float) DB::table('tenant_memberships')->where('tenant_id', $tenantId)->where('status', 'active')->count(), 'limit' => (float) $plan->seats],
            'storage' => ['used' => round((int) DB::table('attachments')->where('tenant_id', $tenantId)->sum('size') / 1024 ** 3, 3), 'limit' => (float) $plan->storage_gb],
            'ai_minutes' => ['used' => round((int) DB::table('ai_calls')->where('tenant_id', $tenantId)->where('created_at', '>=', $monthStart)->sum('duration_sec') / 60, 1), 'limit' => (float) $plan->ai_minutes],
            'api_requests' => ['used' => (float) DB::table('usage_records')->where('tenant_id', $tenantId)->where('metric', 'api_requests')->where('period', gmdate('Y-m'))->sum('quantity'), 'limit' => (float) $plan->api_requests],
        ];
    }

    /** @return int number of alerts raised */
    public function run(): int
    {
        $raised = 0;
        $tenants = DB::table('tenants as t')->join('subscriptions as s', 's.tenant_id', '=', 't.id')
            ->whereIn('s.status', ['active', 'trialing', 'past_due', 'grace'])->distinct()->get(['t.id', 't.name']);
        foreach ($tenants as $tenant) {
            foreach ($this->usage((string) $tenant->id) as $metric => $u) {
                if ($u['limit'] <= 0 ? $u['used'] <= 0 : $u['used'] < $u['limit'] * self::NEAR) continue;
                $exceeded = $u['limit'] <= 0 || $u['used'] >= $u['limit'];
                $level = $exceeded ? 'exceeded' : 'near';
                // One alert per tenant, metric and level each month; "exceeded" is sent even after "near".
                if (!Cache::add("limit:{$tenant->id}:{$metric}:{$level}:" . gmdate('Y-m'), 1, 35 * 86400)) continue;
                $label = self::LABELS[$metric] ?? $metric;
                $detail = "{$label}: {$u['used']} / {$u['limit']}";
                $title = $exceeded ? 'Limit tugadi' : 'Limit tugab bormoqda';
                $type = $exceeded ? 'error' : 'warning';
                (new NotificationDispatcher())->notifyAdmins((string) $tenant->id, $type, $title, $detail, '/app/billing');
                (new PlatformAlerts())->notify("{$title}: {$tenant->name}", $detail, "/platform/tenants/{$tenant->id}", $type);
                $raised++;
            }
        }
        return $raised;
    }
}
