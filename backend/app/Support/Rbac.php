<?php
namespace App\Support;

/** Role → permission matrix, ported 1:1 from api/core.ts. */
class Rbac
{
    public const PERMISSIONS = [
        'crm.read', 'crm.write', 'crm.delete',
        'deals.write', 'orders.write', 'projects.write', 'tasks.write',
        'finance.read', 'finance.write',
        'billing.read', 'billing.write',
        'ai.read', 'ai.write', 'ai.start_campaign', 'ai.recordings',
        'team.read', 'team.write', 'roles.write',
        'settings.read', 'settings.write',
        'integrations.write', 'export.data', 'audit.read',
        'services.request', 'portal.read',
    ];

    public const ROLES = [
        'tenant_owner', 'tenant_admin', 'sales_manager', 'sales_agent', 'team_lead',
        'project_manager', 'developer', 'support_agent', 'finance_manager',
        'analyst', 'employee', 'read_only', 'client_portal_user',
    ];

    private const MATRIX = [
        'tenant_owner' => ['crm.read','crm.write','crm.delete','deals.write','orders.write','projects.write','tasks.write','finance.read','finance.write','billing.read','billing.write','ai.read','ai.write','ai.start_campaign','ai.recordings','team.read','team.write','roles.write','settings.read','settings.write','integrations.write','export.data','audit.read','services.request'],
        'tenant_admin' => ['crm.read','crm.write','crm.delete','deals.write','orders.write','projects.write','tasks.write','finance.read','billing.read','ai.read','ai.write','ai.start_campaign','team.read','team.write','roles.write','settings.read','settings.write','integrations.write','export.data','audit.read','services.request'],
        'sales_manager' => ['crm.read','crm.write','deals.write','orders.write','tasks.write','finance.read','ai.read','ai.write','ai.start_campaign','team.read','billing.read','export.data','services.request'],
        'team_lead' => ['crm.read','crm.write','deals.write','orders.write','tasks.write','projects.write','ai.read','team.read','export.data','services.request'],
        'sales_agent' => ['crm.read','crm.write','deals.write','orders.write','tasks.write','ai.read','team.read','services.request'],
        'project_manager' => ['crm.read','projects.write','tasks.write','orders.write','team.read','export.data','services.request'],
        'developer' => ['crm.read','projects.write','tasks.write','team.read','services.request'],
        'support_agent' => ['crm.read','crm.write','tasks.write','team.read','services.request'],
        'finance_manager' => ['crm.read','finance.read','finance.write','billing.read','billing.write','orders.write','export.data','audit.read','services.request'],
        'analyst' => ['crm.read','finance.read','billing.read','ai.read','export.data','services.request'],
        'employee' => ['crm.read','tasks.write','team.read','services.request'],
        'read_only' => ['crm.read','finance.read','team.read'],
        'client_portal_user' => ['portal.read'],
    ];

    public static function can(?string $role, string $permission): bool
    {
        if (!$role) return false;
        return in_array($permission, self::MATRIX[$role] ?? [], true);
    }
}
