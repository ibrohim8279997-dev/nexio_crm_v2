<?php

use App\Support\H;
use Database\Seeders\PluginSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Laravel installs never received the default plans (they lived only in the Worker's D1 seed), so
     * registration created tenants without a subscription: billing rendered empty and AI was refused.
     * Seeds the plans, gives every plan-less tenant a fresh 14-day trial, then re-applies the plugin rules
     * that are keyed by plan and were skipped while the plans table was empty.
     */
    public function up(): void
    {
        $data = json_decode((string) file_get_contents(database_path('schema/default_plans.json')), true);

        foreach ($data['plans'] as $plan) {
            if (DB::table('plans')->where('code', $plan['code'])->exists()) {
                continue;
            }
            DB::table('plans')->insert($plan);
            foreach ($data['features'] as $feature) {
                if ($feature['plan_id'] === $plan['id']) {
                    DB::table('plan_features')->insert($feature);
                }
            }
        }

        $starter = DB::table('plans')->where('code', 'starter')->first();
        $now = H::now();
        $trialEnd = gmdate('Y-m-d H:i:s', time() + 14 * 86400);

        $tenants = DB::table('tenants')->whereNotIn('id', DB::table('subscriptions')->select('tenant_id'))->get();
        foreach ($tenants as $tenant) {
            $plan = ($tenant->plan_id ? DB::table('plans')->where('id', $tenant->plan_id)->first() : null) ?? $starter;
            DB::table('subscriptions')->insert([
                'id' => H::newId('sub'), 'tenant_id' => $tenant->id, 'plan_id' => $plan->id,
                'status' => 'trialing', 'seats' => $plan->seats, 'amount' => $plan->price_monthly,
                'currency' => $plan->currency, 'interval' => 'month',
                'period_start' => $now, 'period_end' => $trialEnd, 'created_at' => $now,
            ]);
            DB::table('tenants')->where('id', $tenant->id)->update(['plan_id' => $plan->id, 'trial_ends_at' => $trialEnd]);
        }

        app(PluginSeeder::class)->run();
    }

    public function down(): void
    {
        // Plans and subscriptions may be referenced by live billing data, so they are retained.
    }
};
