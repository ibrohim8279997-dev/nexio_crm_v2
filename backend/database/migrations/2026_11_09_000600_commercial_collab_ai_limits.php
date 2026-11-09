<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quotes, price lists, tags, project members/comments, idempotent AI-call provider events,
 * and the budget / approval-metadata columns used by the AI hard limits.
 * Every row carries tenant_id; ids are app-generated strings (same convention as the rest of the schema).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('price_lists')) {
            Schema::create('price_lists', function (Blueprint $t) {
                $t->string('id', 40)->primary();
                $t->string('tenant_id', 40)->index();
                $t->string('name', 160);
                $t->string('currency', 8)->default('UZS');
                $t->boolean('is_default')->default(false);
                $t->boolean('is_active')->default(true);
                $t->string('created_at', 30);
            });
        }
        if (!Schema::hasTable('price_list_items')) {
            Schema::create('price_list_items', function (Blueprint $t) {
                $t->string('id', 40)->primary();
                $t->string('tenant_id', 40)->index();
                $t->string('price_list_id', 40);
                $t->string('product_id', 40);
                $t->double('price');
                $t->unique(['price_list_id', 'product_id']);
            });
        }
        if (!Schema::hasTable('quotes')) {
            Schema::create('quotes', function (Blueprint $t) {
                $t->string('id', 40)->primary();
                $t->string('tenant_id', 40);
                $t->string('number', 40);
                $t->string('deal_id', 40)->nullable();
                $t->string('org_id', 40)->nullable();
                $t->string('contact_id', 40)->nullable();
                $t->string('price_list_id', 40)->nullable();
                $t->string('status', 20)->default('draft');   // draft | sent | accepted | rejected | expired
                $t->string('currency', 8)->default('UZS');
                $t->double('subtotal')->default(0);
                $t->double('discount_percent')->default(0);
                $t->double('total')->default(0);
                $t->string('valid_until', 10)->nullable();
                $t->text('notes')->nullable();
                $t->string('order_id', 40)->nullable();       // set when an accepted quote is converted
                $t->string('created_by', 40)->nullable();
                $t->string('created_at', 30);
                $t->string('updated_at', 30);
                $t->unique(['tenant_id', 'number']);
                $t->index(['tenant_id', 'status']);
            });
        }
        if (!Schema::hasTable('quote_items')) {
            Schema::create('quote_items', function (Blueprint $t) {
                $t->string('id', 40)->primary();
                $t->string('tenant_id', 40)->index();
                $t->string('quote_id', 40)->index();
                $t->string('product_id', 40)->nullable();
                $t->string('description', 300);
                $t->double('quantity')->default(1);
                $t->double('unit_price')->default(0);
                $t->double('line_total')->default(0);
            });
        }
        if (!Schema::hasTable('tags')) {
            Schema::create('tags', function (Blueprint $t) {
                $t->string('id', 40)->primary();
                $t->string('tenant_id', 40);
                $t->string('name', 60);
                $t->string('color', 9)->default('#2563EB');
                $t->string('created_at', 30);
                $t->unique(['tenant_id', 'name']);
            });
        }
        if (!Schema::hasTable('taggings')) {
            Schema::create('taggings', function (Blueprint $t) {
                $t->string('id', 40)->primary();
                $t->string('tenant_id', 40)->index();
                $t->string('tag_id', 40);
                $t->string('entity_type', 30);
                $t->string('entity_id', 40);
                $t->string('created_at', 30);
                $t->unique(['tag_id', 'entity_type', 'entity_id']);
                $t->index(['tenant_id', 'entity_type', 'entity_id']);
            });
        }
        if (!Schema::hasTable('project_members')) {
            Schema::create('project_members', function (Blueprint $t) {
                $t->string('id', 40)->primary();
                $t->string('tenant_id', 40)->index();
                $t->string('project_id', 40);
                $t->string('user_id', 40);
                $t->string('role', 20)->default('member');    // lead | member | viewer
                $t->string('created_at', 30);
                $t->unique(['project_id', 'user_id']);
            });
        }
        if (!Schema::hasTable('project_comments')) {
            Schema::create('project_comments', function (Blueprint $t) {
                $t->string('id', 40)->primary();
                $t->string('tenant_id', 40);
                $t->string('project_id', 40);
                $t->text('body');
                $t->string('author_id', 40)->nullable();
                $t->string('author_name', 160)->nullable();
                $t->string('created_at', 30);
                $t->index(['tenant_id', 'project_id']);
            });
        }
        if (!Schema::hasTable('ai_call_events')) {
            // One row per provider callback. The unique key makes redelivery of the same event a no-op.
            Schema::create('ai_call_events', function (Blueprint $t) {
                $t->string('id', 40)->primary();
                $t->string('tenant_id', 40);
                $t->string('call_id', 40);
                $t->string('provider_event_id', 120);
                $t->string('type', 40);
                $t->text('payload')->nullable();
                $t->string('created_at', 30);
                $t->unique(['tenant_id', 'provider_event_id']);
                $t->index(['tenant_id', 'call_id']);
            });
        }
        if (Schema::hasTable('ai_campaigns') && !Schema::hasColumn('ai_campaigns', 'budget_limit')) {
            Schema::table('ai_campaigns', function (Blueprint $t) {
                $t->double('budget_limit')->nullable();       // null = no campaign cap
                $t->double('budget_used')->default(0);
            });
        }
        if (Schema::hasTable('ai_approvals') && !Schema::hasColumn('ai_approvals', 'meta')) {
            Schema::table('ai_approvals', function (Blueprint $t) {
                $t->text('meta')->nullable();                 // {amount, discount_percent} that was checked against the policy
            });
        }
    }

    public function down(): void
    {
        foreach (['ai_call_events', 'project_comments', 'project_members', 'taggings', 'tags', 'quote_items', 'quotes', 'price_list_items', 'price_lists'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
