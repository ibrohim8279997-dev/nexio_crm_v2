<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Links an AI call to the voice provider's own call id so signed provider callbacks can find it. */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('ai_calls') && !Schema::hasColumn('ai_calls', 'provider_call_id')) {
            Schema::table('ai_calls', function (Blueprint $t) {
                $t->string('provider', 40)->nullable();
                $t->string('provider_call_id', 120)->nullable();
                $t->unique(['tenant_id', 'provider_call_id']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ai_calls') && Schema::hasColumn('ai_calls', 'provider_call_id')) {
            Schema::table('ai_calls', function (Blueprint $t) {
                $t->dropUnique(['tenant_id', 'provider_call_id']);
                $t->dropColumn(['provider', 'provider_call_id']);
            });
        }
    }
};
