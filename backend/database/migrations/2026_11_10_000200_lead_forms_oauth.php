<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Embeddable lead-capture forms (web widget) and OAuth2 client_credentials access tokens. */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('lead_forms')) {
            Schema::create('lead_forms', function (Blueprint $t) {
                $t->string('id', 40)->primary();
                $t->string('tenant_id', 40);
                $t->string('public_key', 40)->unique();          // not a secret: it is embedded in the page
                $t->string('name', 160);
                $t->text('allowed_origins');                     // JSON list; "*" must be listed explicitly
                $t->string('success_message', 300)->nullable();
                $t->string('redirect_url', 500)->nullable();
                $t->integer('is_active')->default(1);
                $t->string('owner_id', 40)->nullable();          // new leads are assigned to this member
                $t->integer('submissions')->default(0);
                $t->string('created_by', 40)->nullable();
                $t->string('created_at', 30);
                $t->index('tenant_id');
            });
        }
        if (Schema::hasTable('api_keys') && !Schema::hasColumn('api_keys', 'parent_id')) {
            Schema::table('api_keys', function (Blueprint $t) {
                $t->string('parent_id', 40)->nullable();         // set on short-lived OAuth2 access tokens
                $t->index('parent_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_forms');
        if (Schema::hasTable('api_keys') && Schema::hasColumn('api_keys', 'parent_id')) {
            Schema::table('api_keys', function (Blueprint $t) {
                $t->dropIndex(['parent_id']);
                $t->dropColumn('parent_id');
            });
        }
    }
};
