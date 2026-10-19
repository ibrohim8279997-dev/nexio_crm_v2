<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('documents')) return;
        Schema::table('documents', function (Blueprint $table) {
            if (!Schema::hasColumn('documents', 'content')) $table->text('content')->nullable();
            if (!Schema::hasColumn('documents', 'source_deal_id')) $table->string('source_deal_id')->nullable()->index();
            if (!Schema::hasColumn('documents', 'approved_at')) $table->dateTime('approved_at')->nullable();
            if (!Schema::hasColumn('documents', 'approved_by')) $table->string('approved_by')->nullable();
            if (!Schema::hasColumn('documents', 'updated_at')) $table->dateTime('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('documents')) return;
        Schema::table('documents', function (Blueprint $table) {
            foreach (['content', 'source_deal_id', 'approved_at', 'approved_by', 'updated_at'] as $column) {
                if (Schema::hasColumn('documents', $column)) $table->dropColumn($column);
            }
        });
    }
};
