<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Proof of consent (which policy version, when, from where) and the 30-day account deletion grace period. */
    public function up(): void
    {
        if (!Schema::hasTable('policy_acceptances')) {
            Schema::create('policy_acceptances', function (Blueprint $t) {
                $t->string('id')->primary();
                $t->string('user_id')->index();
                $t->string('policy_version', 16);
                $t->boolean('terms_accepted')->default(false);
                $t->boolean('privacy_accepted')->default(false);
                $t->string('ip', 64)->nullable();
                $t->string('user_agent', 250)->nullable();
                $t->dateTime('accepted_at');
            });
        }
        Schema::table('users', function (Blueprint $t) {
            if (!Schema::hasColumn('users', 'deletion_requested_at')) $t->dateTime('deletion_requested_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn('deletion_requested_at');
        });
        Schema::dropIfExists('policy_acceptances');
    }
};
