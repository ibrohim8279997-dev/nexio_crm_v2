<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('plugin_limits') || !DB::getSchemaBuilder()->hasTable('plans') || !DB::getSchemaBuilder()->hasTable('plugins') || !DB::table('plugins')->where('id', 'nexio.ai.assistant')->exists()) return;
        $now = gmdate('Y-m-d H:i:s');
        foreach ([['starter', 10000, 'BLOCK'], ['business', 100000, 'BLOCK'], ['enterprise', null, 'WARN']] as [$code, $limit, $policy]) {
            $plan = DB::table('plans')->where('code', $code)->first();
            if (!$plan) continue;
            $existing = DB::table('plugin_limits')->where(['plugin_id' => 'nexio.ai.assistant', 'plan_id' => $plan->id, 'metric' => 'ai_tokens', 'period' => 'monthly', 'scope' => 'company'])->first();
            if ($existing) {
                DB::table('plugin_limits')->where('id', $existing->id)->update(['limit_value' => $limit, 'enforcement_policy' => $policy, 'updated_at' => $now]);
            } else {
                DB::table('plugin_limits')->insert(['id' => 'lim_ai_' . $code, 'plugin_id' => 'nexio.ai.assistant', 'plan_id' => $plan->id, 'metric' => 'ai_tokens', 'period' => 'monthly', 'scope' => 'company', 'limit_value' => $limit, 'enforcement_policy' => $policy, 'unit' => 'token', 'notes' => 'AI yordamchi oylik token limiti', 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void {}
};
