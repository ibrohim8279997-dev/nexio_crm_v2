<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * - documents: a real uploaded file (path on a storage disk) next to the existing text `content`; ai_generated marks
     *   drafts written by the AI so the UI can show what is missing and what the AI may not write.
     * - ai_crm_access: the ONE API key per company that the AI assistant reads CRM data through. Only the key id is kept
     *   (never the token); revoking the key in "API keys" cuts the AI off immediately.
     */
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $t) {
            if (!Schema::hasColumn('documents', 'file_path'))     $t->string('file_path')->nullable();
            if (!Schema::hasColumn('documents', 'file_disk'))     $t->string('file_disk', 20)->nullable();
            if (!Schema::hasColumn('documents', 'mime'))          $t->string('mime', 120)->nullable();
            if (!Schema::hasColumn('documents', 'original_name')) $t->string('original_name', 255)->nullable();
            if (!Schema::hasColumn('documents', 'ai_generated'))  $t->boolean('ai_generated')->default(false);
        });

        if (!Schema::hasTable('ai_crm_access')) {
            Schema::create('ai_crm_access', function (Blueprint $t) {
                $t->string('tenant_id')->primary();
                $t->string('api_key_id');
                $t->string('connected_by')->nullable();
                $t->dateTime('connected_at');
                $t->dateTime('last_used_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_crm_access');
    }
};
