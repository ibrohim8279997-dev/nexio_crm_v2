<?php

use App\Support\H;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Accounts that already exist are grandfathered in as verified. */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'email_verified_at')) {
                $table->dateTime('email_verified_at')->nullable();
            }
            if (!Schema::hasColumn('users', 'email_verify_token')) {
                $table->string('email_verify_token')->nullable()->index();
            }
        });
        DB::table('users')->whereNull('email_verified_at')->update(['email_verified_at' => H::now()]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['email_verify_token']);
            $table->dropColumn(['email_verify_token', 'email_verified_at']);
        });
    }
};
