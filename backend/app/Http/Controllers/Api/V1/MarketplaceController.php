<?php
namespace App\Http\Controllers\Api\V1;

use App\Plugins\EntitlementEngine;
use App\Plugins\PluginAudit;
use App\Plugins\PluginLifecycle;
use App\Plugins\PluginRegistry;
use App\Support\ApiException;
use App\Support\Ctx;
use App\Support\H;

/**
 * Marketplace, read side. Everything returned here is already filtered and computed server-side:
 * the frontend never decides whether a plugin is installable.
 */
class MarketplaceController
{
    private function requireUser(Ctx $ctx): void
    {
        $ctx->auth();
        H::requirePermission($ctx, 'crm.read');
    }

    public function categories(Ctx $ctx)
    {
        $this->requireUser($ctx);
        return H::json(['data' => PluginRegistry::categories()]);
    }

    public function plugins(Ctx $ctx)
    {
        $this->requireUser($ctx);
        $filters = [
            'category' => $ctx->query('category'),
            'type' => $ctx->query('type'),
            'q' => $ctx->query('q'),
            'state' => $ctx->query('state'),
        ];
        $plugins = PluginRegistry::catalog($ctx, array_filter($filters, fn ($v) => $v !== null && $v !== ''));

        $installed = count(array_filter($plugins, fn ($p) => $p['installed']));
        $updates = count(array_filter($plugins, fn ($p) => $p['state'] === 'update_available'));

        return H::json([
            'data' => $plugins,
            'total' => count($plugins),
            'summary' => [
                'installed' => $installed,
                'updates' => $updates,
                'official' => count(array_filter($plugins, fn ($p) => $p['is_official'])),
                'third_party' => count(array_filter($plugins, fn ($p) => !$p['is_official'])),
            ],
            'policy' => [
                'sandbox_available' => (bool) config('nexio.plugins.sandbox_available', false),
                'nexio_version' => (string) config('nexio.plugins.nexio_version', '2.0.0'),
            ],
        ]);
    }

    public function plugin(Ctx $ctx)
    {
        $this->requireUser($ctx);
        $id = (string) ($ctx->params['id'] ?? '');
        $plugin = PluginRegistry::detail($ctx, $id);
        if (!$plugin) {
            throw new ApiException(404, 'Плагин не найден', 'not_found');
        }

        // Offer lifecycle affordances only if the caller may actually use them.
        $canManage = H::can($ctx, PluginLifecycle::MANAGE_PERMISSION);

        return H::json([
            'plugin' => $plugin,
            'install_check' => $plugin['installed'] ? null : PluginLifecycle::checkInstall($ctx, $id),
            'can_manage' => $canManage,
            'permissions' => $plugin['declared_permissions'],
        ]);
    }

    public function updates(Ctx $ctx)
    {
        $this->requireUser($ctx);
        return H::json(['data' => PluginRegistry::updates($ctx)]);
    }

    /** Dry run of install: which gate would stop the install, and why. */
    public function installCheck(Ctx $ctx)
    {
        $this->requireUser($ctx);
        return H::json(PluginLifecycle::checkInstall($ctx, (string) ($ctx->params['id'] ?? '')));
    }

    /** The caller's own plan entitlements, so the UI can explain limits honestly. */
    public function planAccess(Ctx $ctx)
    {
        $this->requireUser($ctx);
        $planId = EntitlementEngine::planIdForTenant($ctx->tenantId);
        return H::json([
            'plan_id' => $planId,
            'plan_code' => EntitlementEngine::planCode($planId),
            // Effective limits for this plan across all plugins, so the UI can explain quotas.
            'limits' => EntitlementEngine::limitsForPlan($planId),
        ]);
    }

    /** Recent marketplace actions for this company (from the shared audit trail). */
    public function activity(Ctx $ctx)
    {
        $this->requireUser($ctx);
        return H::json(['data' => PluginAudit::trail($ctx->tenantId, null, 50)]);
    }
}
