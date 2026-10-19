<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('plugin_purchases')) return;
        Schema::create('plugin_purchases', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('tenant_id')->index();
            $table->string('plugin_id')->index();
            $table->decimal('amount', 18, 2);
            $table->string('currency', 8)->default('UZS');
            $table->string('status')->default('active');
            $table->string('wallet_transaction_id')->nullable();
            $table->timestamp('created_at')->index();
            $table->timestamp('updated_at')->nullable();
            $table->unique(['tenant_id', 'plugin_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugin_purchases');
    }
};
