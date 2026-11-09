<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Knowledge documents an AI agent may rely on. agent_id NULL = available to every agent of the company. */
    public function up(): void
    {
        if (Schema::hasTable('ai_knowledge_docs')) return;
        Schema::create('ai_knowledge_docs', function (Blueprint $t) {
            $t->string('id', 40)->primary();
            $t->string('tenant_id', 40)->index();
            $t->string('agent_id', 40)->nullable()->index();
            $t->string('title', 200);
            $t->string('kind', 20)->default('faq');   // faq | catalog | script | policy | other
            $t->text('content');
            $t->boolean('is_enabled')->default(true);
            $t->string('created_by', 40)->nullable();
            $t->string('created_at', 30);
            $t->string('updated_at', 30);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_knowledge_docs');
    }
};
