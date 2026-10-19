<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('invitations', 'org_id')) {
            Schema::table('invitations', function ($table) {
                $table->string('org_id')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('invitations', 'org_id')) {
            Schema::table('invitations', function ($table) {
                $table->dropColumn('org_id');
            });
        }
    }
};
