<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** FCM registration tokens of the mobile apps (one row per device). */
    public function up(): void
    {
        if (Schema::hasTable('device_tokens')) return;
        Schema::create('device_tokens', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->string('user_id')->index();
            $t->string('tenant_id')->nullable()->index();
            $t->string('token', 512)->unique();
            $t->string('platform', 16);
            $t->timestamp('created_at');
            $t->timestamp('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
