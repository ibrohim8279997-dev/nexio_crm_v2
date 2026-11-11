<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** A platform operator can grant a plan to a tenant without payment (own / partner / demo companies). */
    public function up(): void
    {
        if (!Schema::hasTable('subscriptions')) return;
        Schema::table('subscriptions', function (Blueprint $t) {
            if (!Schema::hasColumn('subscriptions', 'complimentary')) $t->boolean('complimentary')->default(false);
            if (!Schema::hasColumn('subscriptions', 'grant_note'))    $t->string('grant_note', 200)->nullable();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('subscriptions')) return;
        Schema::table('subscriptions', function (Blueprint $t) {
            foreach (['complimentary', 'grant_note'] as $c) {
                if (Schema::hasColumn('subscriptions', $c)) $t->dropColumn($c);
            }
        });
    }
};
