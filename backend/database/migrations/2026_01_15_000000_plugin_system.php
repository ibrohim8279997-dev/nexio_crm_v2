<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Nexio Plugin System — 18 additive tables.
 *
 * Purely additive: the DDL only creates plugin_* tables. No existing table is altered,
 * dropped or emptied, so this migration cannot damage the live CRM.
 */
return new class extends Migration
{
    public function up(): void
    {
        $sql = file_get_contents(base_path('database/schema/0002_plugins.sql'));
        DB::unprepared($sql);
    }

    public function down(): void
    {
        // Dependency order (children first). Only plugin_* tables are touched.
        $tables = [
            'plugin_subscriptions', 'plugin_events', 'plugin_usage_counters', 'plugin_usage',
            'plugin_limits', 'plugin_feature_rules', 'plugin_entitlements', 'plugin_dependencies',
            'plugin_permissions', 'plugin_secrets', 'plugin_settings', 'plugin_connections',
            'plugin_installations', 'plugin_reviews', 'plugin_versions', 'plugins',
            'plugin_developers', 'plugin_categories',
        ];
        foreach ($tables as $t) {
            DB::unprepared("DROP TABLE IF EXISTS {$t}");
        }
    }
};
