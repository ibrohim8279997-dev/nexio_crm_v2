<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('user_telegram_links')) return;
        Schema::create('user_telegram_links', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('user_id')->unique();
            $table->string('chat_id')->unique();
            $table->string('username')->nullable();
            $table->string('link_token')->unique();
            $table->timestamp('linked_at')->nullable();
            $table->timestamp('created_at');
        });
    }
    public function down(): void { Schema::dropIfExists('user_telegram_links'); }
};
