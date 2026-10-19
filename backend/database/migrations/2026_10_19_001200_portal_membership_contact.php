<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tenant_memberships', 'contact_id')) {
            Schema::table('tenant_memberships', function ($table) {
                $table->string('contact_id')->nullable();
            });
        }
        if (!Schema::hasColumn('invitations', 'contact_id')) {
            Schema::table('invitations', function ($table) {
                $table->string('contact_id')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tenant_memberships', 'contact_id')) {
            Schema::table('tenant_memberships', fn ($table) => $table->dropColumn('contact_id'));
        }
        if (Schema::hasColumn('invitations', 'contact_id')) {
            Schema::table('invitations', fn ($table) => $table->dropColumn('contact_id'));
        }
    }
};
