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
        DB::unprepared($sql);
    }

    public function down(): void
    {
        foreach (['delivery_shipments', 'delivery_status_map', 'telephony_calls', 'telephony_numbers'] as $t) {
            DB::unprepared("DROP TABLE IF EXISTS {$t}");
        }
    }
};
