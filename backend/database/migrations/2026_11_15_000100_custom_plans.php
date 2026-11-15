<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Custom plans are private rows in `plans`: owner_tenant_id marks the only tenant that may buy/see them.
     * A row is immutable once created (a new configuration = a new row), so an active subscription can never be
     * widened by editing its plan. max_contacts NULL = unlimited.
     */
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $t) {
            if (!Schema::hasColumn('plans', 'owner_tenant_id')) $t->string('owner_tenant_id')->nullable()->index();
            if (!Schema::hasColumn('plans', 'max_contacts'))    $t->unsignedInteger('max_contacts')->nullable();
            if (!Schema::hasColumn('plans', 'custom_config'))   $t->text('custom_config')->nullable();
        });
    }

    public function down(): void
    {
        // Subscriptions may reference custom plans, so the data is retained.
    }
};
