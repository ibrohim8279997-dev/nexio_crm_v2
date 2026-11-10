<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** A telephony number / extension can be assigned to one CRM user: that user's calls go out from it. */
    public function up(): void
    {
        if (!Schema::hasTable('telephony_numbers')) return;
        Schema::table('telephony_numbers', function (Blueprint $t) {
            if (!Schema::hasColumn('telephony_numbers', 'assigned_user_id')) $t->string('assigned_user_id')->nullable()->index();
            if (!Schema::hasColumn('telephony_numbers', 'label'))            $t->string('label', 80)->nullable();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('telephony_numbers')) return;
        Schema::table('telephony_numbers', function (Blueprint $t) {
            if (Schema::hasColumn('telephony_numbers', 'assigned_user_id')) $t->dropColumn('assigned_user_id');
            if (Schema::hasColumn('telephony_numbers', 'label'))            $t->dropColumn('label');
        });
    }
};
