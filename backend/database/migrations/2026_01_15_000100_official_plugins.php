<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Data tables owned by the official plugins (telephony, delivery).
 * Additive only — no core table is touched.
 */
return new class extends Migration
{
    public function up(): void
    {
        $sql = file_get_contents(base_path('database/schema/0003_official_plugins.sql'));
        if (DB::connection()->getDriverName() !== 'sqlite') {
            $sql = preg_replace('/^PRAGMA[^;]+;\s*/mi', '', $sql) ?? $sql;
            $sql = str_replace(' COLLATE NOCASE', '', $sql);
            $sql = preg_replace('/\s+REFERENCES\s+[A-Za-z_][A-Za-z0-9_]*\s*\([^)]*\)(?:\s+ON\s+DELETE\s+(?:CASCADE|SET\s+NULL|RESTRICT|NO\s+ACTION))?/i', '', $sql) ?? $sql;
        }
        DB::unprepared($sql);
    }

    public function down(): void
    {
        foreach (['delivery_shipments', 'delivery_status_map', 'telephony_calls', 'telephony_numbers'] as $t) {
            DB::unprepared("DROP TABLE IF EXISTS {$t}");
        }
    }
};
