<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * plugin_usage.result — the redacted result of a successful plugin operation.
 *
 * Why: the ledger's UNIQUE(tenant_id, plugin_id, metric, idempotency_key) already guarantees a
 * retried request is charged once, but the plugin operation itself used to run again, so a retry
 * could still create a second shipment (charge once, side effect twice). Storing the result lets
 * PluginRuntime answer a replay instead of re-executing it.
 *
 * Additive and idempotent: the column is part of database/schema/0002_plugins.sql for fresh
 * databases, and this migration adds it to databases created before it existed.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!$this->hasColumn('plugin_usage', 'result')) {
            DB::unprepared('ALTER TABLE plugin_usage ADD COLUMN result TEXT');
        }
    }

    public function down(): void
    {
        // SQLite cannot drop a column in older engines, and the column is harmless if left behind.
    }

    private function hasColumn(string $table, string $column): bool
    {
        $rows = DB::connection()->getDriverName() === 'sqlite'
            ? DB::select("PRAGMA table_info({$table})")
            : DB::select('SELECT column_name AS name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = ?', [$table]);
        foreach ($rows as $col) {
            if ((string) $col->name === $column) {
                return true;
            }
        }
        return false;
    }
};
