<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Nexio CRM schema — 43 tables.
 *
 * The DDL is byte-identical to migrations/0001_init.sql (the Cloudflare D1 migration),
 * so the seeded data and the frontend contract stay the same between the two backends.
 */
return new class extends Migration
{
    public function up(): void
    {
        $sql = file_get_contents(base_path('database/schema/0001_init.sql'));
        DB::unprepared($sql);
    }

    public function down(): void
    {
        $tables = [
            'tenant_settings', 'feature_flags', 'ai_evaluations', 'suppression_list', 'consents',
            'ai_approvals', 'ai_tool_calls', 'ai_calls', 'ai_campaigns', 'ai_agents',
            'billing_events', 'usage_records', 'payments', 'invoices', 'subscriptions',
            'plan_features', 'plans', 'nexio_service_requests', 'webhook_events', 'integrations',
            'automations', 'documents', 'communications', 'tickets', 'tasks',
            'project_milestones', 'projects', 'order_items', 'orders', 'products',
            'activities', 'deals', 'leads', 'pipeline_stages', 'pipelines',
            'contacts', 'organizations', 'audit_logs', 'invitations', 'sessions',
            'tenant_memberships', 'tenants', 'users',
        ];
        foreach ($tables as $t) {
            DB::unprepared("DROP TABLE IF EXISTS {$t}");
        }
    }
};
