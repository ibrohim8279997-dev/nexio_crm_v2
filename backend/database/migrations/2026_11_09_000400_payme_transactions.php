<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Payme Merchant API transaction ledger (timestamps in milliseconds, as the protocol requires). */
    public function up(): void
    {
        if (Schema::hasTable('payme_transactions')) return;
        Schema::create('payme_transactions', function (Blueprint $t) {
            $t->string('id', 40)->primary();
            $t->string('tenant_id', 40)->index();
            $t->string('invoice_id', 40)->index();
            $t->string('payme_id', 64)->unique();
            $t->bigInteger('amount');                  // tiyin (1 UZS = 100 tiyin)
            $t->smallInteger('state');                 // 1 created, 2 performed, -1 cancelled before perform, -2 cancelled after perform
            $t->smallInteger('reason')->nullable();
            $t->bigInteger('payme_time');
            $t->bigInteger('create_time');
            $t->bigInteger('perform_time')->nullable();
            $t->bigInteger('cancel_time')->nullable();
            $t->string('created_at', 30);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payme_transactions');
    }
};
