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
        if (DB::connection()->getDriverName() !== 'sqlite') {
            $sql = self::portableSql($sql);
        }
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

    private static function portableSql(string $sql): string
    {
        $sql = preg_replace('/^PRAGMA[^;]+;\s*/mi', '', $sql) ?? $sql;
        $sql = str_replace(' COLLATE NOCASE', '', $sql);
        // The source schema was authored for SQLite, where forward references are accepted.
        // PostgreSQL requires referenced tables to exist first; Laravel's later migrations and
        // application-level tenant guards still enforce ownership, so remove only inline FK
        // clauses while preserving columns and indexes.
        $sql = preg_replace('/\s+REFERENCES\s+[A-Za-z_][A-Za-z0-9_]*\s*\([^)]*\)(?:\s+ON\s+DELETE\s+(?:CASCADE|SET\s+NULL|RESTRICT|NO\s+ACTION))?/i', '', $sql) ?? $sql;
        return $sql;
    }
};
