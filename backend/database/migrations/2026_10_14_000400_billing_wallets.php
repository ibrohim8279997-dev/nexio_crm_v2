<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('wallets')) Schema::create('wallets', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('tenant_id')->unique();
            $table->decimal('balance', 18, 2)->default(0);
            $table->string('currency', 8)->default('UZS');
            $table->timestamps();
        });
        if (!Schema::hasTable('wallet_transactions')) Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('tenant_id')->index();
            $table->string('wallet_id')->index();
            $table->string('kind');
            $table->decimal('amount', 18, 2);
            $table->decimal('balance_after', 18, 2);
            $table->string('provider')->nullable();
            $table->string('provider_ref')->nullable()->unique();
            $table->string('status')->default('posted');
            $table->text('description')->nullable();
            $table->timestamp('created_at')->index();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
    }
};
