<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** users.mfa_enabled already exists in the base schema; this adds the TOTP secret, replay guard and recovery codes. */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'mfa_enabled')) $table->boolean('mfa_enabled')->default(false);
            if (!Schema::hasColumn('users', 'mfa_secret')) $table->text('mfa_secret')->nullable();       // encrypted (Crypt)
            if (!Schema::hasColumn('users', 'mfa_last_step')) $table->bigInteger('mfa_last_step')->nullable();
            if (!Schema::hasColumn('users', 'mfa_recovery')) $table->text('mfa_recovery')->nullable();   // JSON list of sha256 hashes
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['mfa_secret', 'mfa_last_step', 'mfa_recovery']);
        });
    }
};
