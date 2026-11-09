<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Custom fields, notes, file attachments, many-to-many contact↔company links,
 * task time tracking and task dependencies. Every row carries tenant_id; ids are app-generated strings.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('custom_field_defs')) {
            Schema::create('custom_field_defs', function (Blueprint $t) {
                $t->string('id', 40)->primary();
                $t->string('tenant_id', 40)->index();
                $t->string('entity', 30);                 // lead | contact | organization | deal | project | order
                $t->string('key', 60);
                $t->string('label', 120);
                $t->string('type', 20)->default('text');   // text | number | date | select | boolean
                $t->text('options')->nullable();           // JSON list for select
                $t->boolean('is_required')->default(false);
                $t->integer('position')->default(0);
                $t->string('created_at', 30);
                $t->unique(['tenant_id', 'entity', 'key']);
            });
        }
        if (!Schema::hasTable('custom_field_values')) {
            Schema::create('custom_field_values', function (Blueprint $t) {
                $t->string('id', 40)->primary();
                $t->string('tenant_id', 40)->index();
                $t->string('entity', 30);
                $t->string('entity_id', 40);
                $t->string('field_id', 40);
                $t->text('value')->nullable();
                $t->string('updated_at', 30);
                $t->unique(['field_id', 'entity_id']);
                $t->index(['tenant_id', 'entity', 'entity_id']);
            });
        }
        if (!Schema::hasTable('notes')) {
            Schema::create('notes', function (Blueprint $t) {
                $t->string('id', 40)->primary();
                $t->string('tenant_id', 40);
                $t->string('entity_type', 30);
                $t->string('entity_id', 40);
                $t->text('body');
                $t->string('author_id', 40)->nullable();
                $t->string('author_name', 160)->nullable();
                $t->string('created_at', 30);
                $t->index(['tenant_id', 'entity_type', 'entity_id']);
            });
        }
        if (!Schema::hasTable('attachments')) {
            Schema::create('attachments', function (Blueprint $t) {
                $t->string('id', 40)->primary();
                $t->string('tenant_id', 40);
                $t->string('entity_type', 30);
                $t->string('entity_id', 40);
                $t->string('name', 255);                  // original filename, display only — never used as a storage path
                $t->string('mime', 120)->nullable();
                $t->bigInteger('size')->default(0);
                $t->string('path', 255);                  // private disk path built from ids only
                $t->string('uploaded_by', 40)->nullable();
                $t->string('uploaded_by_name', 160)->nullable();
                $t->string('created_at', 30);
                $t->index(['tenant_id', 'entity_type', 'entity_id']);
            });
        }
        if (!Schema::hasTable('contact_organizations')) {
            Schema::create('contact_organizations', function (Blueprint $t) {
                $t->string('id', 40)->primary();
                $t->string('tenant_id', 40)->index();
                $t->string('contact_id', 40);
                $t->string('org_id', 40);
                $t->string('role', 120)->nullable();
                $t->boolean('is_primary')->default(false);
                $t->string('created_at', 30);
                $t->unique(['contact_id', 'org_id']);
                $t->index('org_id');
            });
        }
        if (!Schema::hasTable('time_entries')) {
            Schema::create('time_entries', function (Blueprint $t) {
                $t->string('id', 40)->primary();
                $t->string('tenant_id', 40);
                $t->string('task_id', 40);
                $t->string('user_id', 40)->nullable();
                $t->string('user_name', 160)->nullable();
                $t->integer('minutes');
                $t->string('note', 500)->nullable();
                $t->string('entry_date', 10);
                $t->string('created_at', 30);
                $t->index(['tenant_id', 'task_id']);
            });
        }
        if (!Schema::hasTable('task_dependencies')) {
            Schema::create('task_dependencies', function (Blueprint $t) {
                $t->string('id', 40)->primary();
                $t->string('tenant_id', 40);
                $t->string('task_id', 40);                // the blocked task
                $t->string('depends_on_id', 40);          // must be done first
                $t->string('created_at', 30);
                $t->unique(['task_id', 'depends_on_id']);
                $t->index(['tenant_id', 'depends_on_id']);
            });
        }
    }

    public function down(): void
    {
        foreach (['task_dependencies', 'time_entries', 'contact_organizations', 'attachments', 'notes', 'custom_field_values', 'custom_field_defs'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
