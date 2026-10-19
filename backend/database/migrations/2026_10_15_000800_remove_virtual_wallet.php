<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('plugin_purchases') && Schema::hasColumn('plugin_purchases', 'wallet_transaction_id')) {
            Schema::table('plugin_purchases', fn (Blueprint $table) => $table->dropColumn('wallet_transaction_id'));
        }
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
    }

    public function down(): void {}
};
