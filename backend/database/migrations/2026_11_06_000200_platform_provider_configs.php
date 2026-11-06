<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Platform-wide provider credentials managed from the superadmin settings page. */
    public function up(): void
    {
        if (Schema::hasTable('platform_provider_configs')) {
            return;
        }
        Schema::create('platform_provider_configs', function (Blueprint $table) {
            $table->string('provider')->primary();
            $table->text('encrypted_secret')->nullable();
            $table->string('secret_hint')->nullable();
            $table->string('model')->nullable();
            $table->boolean('enabled')->default(false);
            $table->string('updated_by')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_provider_configs');
    }
};
