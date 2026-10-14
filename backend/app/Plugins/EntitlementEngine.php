<?php
namespace App\Plugins;

use App\Support\Ctx;
use Illuminate\Support\Facades\DB;

/**
 * Plan entitlements for plugins.
 *
 * NO plan rule is hardcoded anywhere in PHP. Every answer comes from rows that Super Admin
 * created: `plugin_entitlements`, `plugin_feature_rules` and `plugin_limits`. The plan itself is
 * read from the CRM's existing subscription/tenant record — this class never invents a plan.
 *
 * Resolution order for a (plugin, plan) pair, first hit wins:
 *   1. plugin-specific + plan-specific row
 *   2. plugin-specific + default row (plan_id IS NULL)
 *   3. catch-all + plan-specific row (plugin_id IS NULL)
 *   4. catch-all + default row
 *   5. config('nexio.plugins.default_policy')  → deny
 *
 * A missing configuration therefore means DENY, never "allow everything" (brief §6).
 */
class EntitlementEngine
{
    /** The plan actually in force for a company: the newest subscription, else tenants.plan_id. */
    public static function planIdForTenant(?string $tenantId): ?string
    {
        if (!$tenantId) {
            return null;
        }
        $sub = DB::selectOne(
            "SELECT plan_id FROM subscriptions WHERE tenant_id = ? AND status IN ('active','trialing','past_due','grace')
              ORDER BY created_at DESC LIMIT 1",
            [$tenantId]
        );
        if ($sub && $sub->plan_id) {
            return (string) $sub->plan_id;
        }
        $t = DB::selectOne('SELECT plan_id FROM tenants WHERE id = ?', [$tenantId]);
        return $t && $t->plan_id ? (string) $t->plan_id : null;
    }

    public static function planCode(?string $planId): ?string
    {
        if (!$planId) {
            return null;
        }
        $p = DB::selectOne('SELECT code FROM plans WHERE id = ?', [$planId]);
        return $p?->code;
    }

    /**
     * Resolution order, most specific first. The (NULL, NULL) pair is last but NOT skipped: it is
     * the platform-default rule an operator configures deliberately, and it is what makes the
     * marketplace usable without per-plan rules. Note the SQL uses `IS ?`, which is NULL-safe.
     *
     * @return array<int,array{0:?string,1:?string}>
     */
    private static function candidatePairs(?string $pluginId, ?string $planId): array
    {
        return [
            [$pluginId, $planId],
            [$pluginId, null],
            [null, $planId],
            [null, null],
        ];
    }

    /** Nearest applicable entitlement row (plugin-specific preferred over catch-all). */
    public static function resolve(?string $pluginId, ?string $planId): ?object
    {
        foreach (self::candidatePairs($pluginId, $planId) as [$p, $pl]) {
            $row = DB::selectOne(
                'SELECT * FROM plugin_entitlements
                  WHERE plugin_id IS ? AND plan_id IS ? AND is_enabled = 1 LIMIT 1',
                [$p, $pl]
            );
            if ($row) {
                return $row;
            }
        }
        return null;
    }

    /**
     * @return array{allowed:bool,code:string,reason:string}
     */
    public static function canInstall(?string $pluginId, ?string $planId): array
    {
        $row = self::resolve($pluginId, $planId);
        $default = (bool) config('nexio.plugins.default_policy.can_install', false);

        if (!$row) {
            return $default
                ? ['allowed' => true, 'code' => 'default_allow', 'reason' => '']
                : ['allowed' => false, 'code' => 'plan_install_denied', 'reason' => 'Установка плагинов не разрешена на вашем тарифе. Обратитесь к администратору Nexio.'];
        }
        if ($row->can_install === null) {
            return $default
                ? ['allowed' => true, 'code' => 'default_allow', 'reason' => '']
                : ['allowed' => false, 'code' => 'plan_install_denied', 'reason' => 'Установка плагинов не разрешена на вашем тарифе. Обратитесь к администратору Nexio.'];
        }
        if ((int) $row->can_install === 1) {
            return ['allowed' => true, 'code' => 'ok', 'reason' => ''];
        }
        return ['allowed' => false, 'code' => 'plan_install_denied', 'reason' => 'Установка плагинов не разрешена на вашем тарифе. Обратитесь к администратору Nexio.'];
    }

    /** @return array{allowed:bool,code:string,reason:string} */
    public static function canUse(?string $pluginId, ?string $planId): array
    {
        $row = self::resolve($pluginId, $planId);
        $default = (bool) config('nexio.plugins.default_policy.can_use', false);

        if (!$row || $row->can_use === null) {
            return $default
                ? ['allowed' => true, 'code' => 'default_allow', 'reason' => '']
                : ['allowed' => false, 'code' => 'plan_use_denied', 'reason' => 'Использование этого плагина не входит в ваш тариф. Обратитесь к администратору Nexio.'];
        }
        if ((int) $row->can_use === 1) {
            return ['allowed' => true, 'code' => 'ok', 'reason' => ''];
        }
        return ['allowed' => false, 'code' => 'plan_use_denied', 'reason' => 'Использование этого плагина не входит в ваш тариф. Обратитесь к администратору Nexio.'];
    }

    /** NULL = unlimited, 0 = none, int = cap. */
    public static function maxInstallations(?string $pluginId, ?string $planId): ?int
    {
        $row = self::resolve($pluginId, $planId);
        return $row && $row->max_installations !== null ? (int) $row->max_installations : null;
    }

    public static function maxConnections(?string $pluginId, ?string $planId): ?int
    {
        $row = self::resolve($pluginId, $planId);
        return $row && $row->max_connections !== null ? (int) $row->max_connections : null;
    }

    /** Named feature flag. Unknown feature = disabled (fail closed). */
    public static function featureEnabled(?string $pluginId, ?string $planId, string $feature): bool
    {
        foreach (self::candidatePairs($pluginId, $planId) as [$p, $pl]) {
            $row = DB::selectOne(
                'SELECT is_enabled FROM plugin_feature_rules WHERE plugin_id IS ? AND plan_id IS ? AND feature_key = ? LIMIT 1',
                [$p, $pl, $feature]
            );
            if ($row) {
                return (int) $row->is_enabled === 1;
            }
        }
        return false;
    }

    public static function features(?string $pluginId, ?string $planId): array
    {
        $rows = DB::select(
            'SELECT feature_key, is_enabled, config FROM plugin_feature_rules
              WHERE plugin_id IS ? AND plan_id IS ? ORDER BY feature_key',
            [$pluginId, $planId]
        );
        $out = [];
        foreach ($rows as $r) {
            $out[(string) $r->feature_key] = [
                'enabled' => (int) $r->is_enabled === 1,
                'config' => \App\Support\H::parseJson($r->config, []),
            ];
        }
        return $out;
    }

    /**
     * EVERY limit rule that applies to one metric, in the nearest matching scope.
     *
     * A metric may be limited per day AND per month at the same time (Super Admin configures one row
     * per period), and both must hold. Returning a single "best" row would silently drop the other
     * one, so all periods of the most specific scope are returned and the meter enforces each.
     *
     * @return array<int,array{limit:?int,policy:string,period:string,scope:string,period_days:?int,unit:?string}>
     */
    public static function limitsFor(?string $pluginId, ?string $planId, string $metric): array
    {
        foreach (self::candidatePairs($pluginId, $planId) as [$p, $pl]) {
            $rows = DB::select(
                'SELECT * FROM plugin_limits WHERE plugin_id IS ? AND plan_id IS ? AND metric = ? AND is_enabled = 1 ORDER BY period',
                [$p, $pl, $metric]
            );
            if ($rows !== []) {
                return array_map(fn ($row) => [
                    'limit' => $row->limit_value === null ? null : (int) $row->limit_value,
                    'policy' => strtoupper((string) ($row->enforcement_policy ?: config('nexio.plugins.default_enforcement', 'BLOCK'))),
                    'period' => (string) $row->period,
                    'scope' => (string) ($row->scope ?: 'company'),
                    'period_days' => $row->period_days === null ? null : (int) $row->period_days,
                    'unit' => $row->unit === null ? null : (string) $row->unit,
                ], $rows);
            }
        }
        return [];
    }

    /** The single most specific rule for a metric, or null. Convenience for read-only views. */
    public static function limitFor(?string $pluginId, ?string $planId, string $metric, ?string $period = null): ?array
    {
        $rules = self::limitsFor($pluginId, $planId, $metric);
        if ($rules === []) {
            return null;
        }
        if ($period !== null) {
            foreach ($rules as $rule) {
                if ($rule['period'] === $period) {
                    return $rule;
                }
            }
        }
        return $rules[0];
    }

    /**
     * All applicable rules for one plugin (and the catch-all ones), across every metric and period.
     * This is what the metering engine will actually enforce for a company on this plan — not merely
     * the rows that happen to name the plan.
     */
    public static function allLimitsFor(?string $pluginId, ?string $planId): array
    {
        $rows = DB::select(
            'SELECT DISTINCT metric FROM plugin_limits
              WHERE is_enabled = 1 AND (plugin_id IS ? OR plugin_id IS NULL) AND (plan_id IS ? OR plan_id IS NULL)
              ORDER BY metric',
            [$pluginId, $planId]
        );
        $out = [];
        foreach ($rows as $r) {
            foreach (self::limitsFor($pluginId, $planId, (string) $r->metric) as $rule) {
                $out[] = ['plugin_id' => $pluginId, 'metric' => (string) $r->metric] + $rule;
            }
        }
        return $out;
    }

    /** The effective rules for a plugin/plan pair — for the tenant and Super Admin screens. */
    public static function limits(?string $pluginId, ?string $planId): array
    {
        return array_map(fn ($r) => [
            'plugin_id' => $r['plugin_id'],
            'metric' => $r['metric'],
            'period' => $r['period'],
            'scope' => $r['scope'],
            'limit' => $r['limit'],
            'policy' => $r['policy'],
            'unit' => $r['unit'],
            'enabled' => true,
        ], self::allLimitsFor($pluginId, $planId));
    }

    /** The effective limit set for a plan, across every plugin that has rules. */
    public static function limitsForPlan(?string $planId): array
    {
        $plugins = DB::select('SELECT DISTINCT plugin_id FROM plugin_limits');
        $out = [];
        foreach ($plugins as $row) {
            $pluginId = $row->plugin_id === null ? null : (string) $row->plugin_id;
            $name = null;
            if ($pluginId !== null) {
                $pluginRow = DB::selectOne('SELECT name FROM plugins WHERE id = ?', [$pluginId]);
                $name = $pluginRow->name ?? null;
            }
            foreach (self::allLimitsFor($pluginId, $planId) as $rule) {
                $out[] = [
                    'plugin_id' => $pluginId,
                    'plugin_name' => $name,
                    'metric' => $rule['metric'],
                    'period' => $rule['period'],
                    'scope' => $rule['scope'],
                    'limit' => $rule['limit'],
                    'policy' => $rule['policy'],
                    'unit' => $rule['unit'],
                ];
            }
        }
        return $out;
    }

    /** Convenience: entitlements for the caller's own company. */
    public static function forContext(Ctx $ctx, string $pluginId): array
    {
        $planId = self::planIdForTenant($ctx->tenantId);
        return [
            'plan_id' => $planId,
            'plan_code' => self::planCode($planId),
            'install' => self::canInstall($pluginId, $planId),
            'use' => self::canUse($pluginId, $planId),
            'features' => self::features($pluginId, $planId),
            'limits' => self::limits($pluginId, $planId),
        ];
    }
}
