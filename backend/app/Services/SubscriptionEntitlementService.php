<?php

namespace App\Services;

use App\Support\H;
use Illuminate\Support\Facades\DB;

final class SubscriptionEntitlementService
{
    private const PLAN_MODULES = [
        'starter' => ['crm', 'projects'],
        'business' => ['crm', 'projects', 'finance', 'helpdesk', 'analytics', 'automations', 'api', 'portal', 'documents'],
        'enterprise' => ['crm', 'projects', 'finance', 'helpdesk', 'analytics', 'automations', 'api', 'portal', 'documents', 'ai_agents', 'voice'],
    ];

    public function apply(string $tenantId, string $planCode): void
    {
        $modules = self::PLAN_MODULES[$planCode] ?? [];
        $flags = [
            'ai_agents' => in_array('ai_agents', $modules, true) ? 1 : 0,
            'voice_minutes' => in_array('voice', $modules, true) ? 1 : 0,
            'custom_domain' => $planCode === 'enterprise' ? 1 : 0,
            'automations' => in_array('automations', $modules, true) ? 1 : 0,
        ];

        foreach ($flags as $key => $enabled) {
            DB::table('feature_flags')->updateOrInsert(
                ['id' => "ff_{$tenantId}_{$key}"],
                ['tenant_id' => $tenantId, 'key' => $key, 'enabled' => $enabled, 'created_at' => H::now()]
            );
        }
    }
}
