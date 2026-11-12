<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Saved-card (Click card token) billing: cards per company, and the dunning / access-lock state of a subscription.
     * Only the provider's token is stored (encrypted) plus the masked number. PAN and CVV never reach the database.
     */
    public function up(): void
    {
        if (!Schema::hasTable('payment_cards')) {
            Schema::create('payment_cards', function (Blueprint $t) {
                $t->string('id', 40)->primary();
                $t->string('tenant_id', 40)->index();
                $t->string('added_by', 40)->nullable();
                $t->string('provider', 20)->default('click');
                $t->text('token_enc');                          // Crypt::encryptString(provider card token)
                $t->string('masked', 32);                       // 860006******6311
                $t->string('last4', 4);
                $t->string('expiry', 4)->nullable();            // MMYY
                $t->boolean('is_primary')->default(false);
                $t->string('status', 16)->default('active');    // active | failed | removed
                $t->string('last_error', 200)->nullable();
                $t->string('last_used_at', 30)->nullable();
                $t->string('created_at', 30);
                $t->string('updated_at', 30)->nullable();
                $t->index(['tenant_id', 'status']);
            });
        }

        if (Schema::hasTable('subscriptions')) {
            Schema::table('subscriptions', function (Blueprint $t) {
                $cols = [
                    'grace_until' => fn () => $t->string('grace_until', 30)->nullable(),                  // payment must arrive before this moment
                    'blocked_at' => fn () => $t->string('blocked_at', 30)->nullable(),                    // access closed (data kept)
                    'data_retain_until' => fn () => $t->string('data_retain_until', 30)->nullable(),      // data is not deleted before this
                    'last_charge_attempt_at' => fn () => $t->string('last_charge_attempt_at', 30)->nullable(),
                    'last_dunning_at' => fn () => $t->string('last_dunning_at', 30)->nullable(),
                    'pending_plan_code' => fn () => $t->string('pending_plan_code', 60)->nullable(),      // downgrade applied on the next 1st
                    'pending_interval' => fn () => $t->string('pending_interval', 8)->nullable(),
                    'lgota_notified_at' => fn () => $t->string('lgota_notified_at', 30)->nullable(),      // "free period ends in 3 days" notice sent
                    'charge_state' => fn () => $t->string('charge_state', 16)->nullable(),                // null | unknown (needs manual review)
                ];
                foreach ($cols as $name => $add) {
                    if (!Schema::hasColumn('subscriptions', $name)) $add();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_cards');
        if (!Schema::hasTable('subscriptions')) return;
        Schema::table('subscriptions', function (Blueprint $t) {
            foreach (['grace_until', 'blocked_at', 'data_retain_until', 'last_charge_attempt_at', 'last_dunning_at', 'pending_plan_code', 'pending_interval', 'lgota_notified_at', 'charge_state'] as $c) {
                if (Schema::hasColumn('subscriptions', $c)) $t->dropColumn($c);
            }
        });
    }
};
