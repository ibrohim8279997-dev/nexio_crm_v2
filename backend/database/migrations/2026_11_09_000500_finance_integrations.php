<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Customer invoicing + payments + expenses, and the developer-integration tables (API keys, outbound webhooks). */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('client_invoices')) {
            Schema::create('client_invoices', function (Blueprint $t) {
                $t->string('id', 40)->primary();
                $t->string('tenant_id', 40)->index();
                $t->string('number', 40);
                $t->string('org_id', 40)->nullable()->index();
                $t->string('order_id', 40)->nullable()->index();
                $t->string('status', 20)->default('draft');   // draft | sent | partial | paid | void  (overdue is derived from due_at)
                $t->string('currency', 8)->default('UZS');
                $t->decimal('subtotal', 18, 2)->default(0);
                $t->decimal('tax_rate', 5, 2)->default(0);
                $t->decimal('tax', 18, 2)->default(0);
                $t->decimal('total', 18, 2)->default(0);
                $t->decimal('paid_amount', 18, 2)->default(0);
                $t->text('items');                              // JSON [{name, qty, unit_price, total}]
                $t->text('notes')->nullable();
                $t->string('issued_at', 30)->nullable();
                $t->string('due_at', 10)->nullable();
                $t->string('paid_at', 30)->nullable();
                $t->string('created_by', 40)->nullable();
                $t->string('created_at', 30);
                $t->string('updated_at', 30);
                $t->unique(['tenant_id', 'number']);
            });
        }
        if (!Schema::hasTable('client_payments')) {
            Schema::create('client_payments', function (Blueprint $t) {
                $t->string('id', 40)->primary();
                $t->string('tenant_id', 40)->index();
                $t->string('invoice_id', 40)->index();
                $t->decimal('amount', 18, 2);
                $t->string('method', 20)->default('bank');     // cash | bank | card | click | payme | other
                $t->string('reference', 120)->nullable();
                $t->string('paid_at', 10);
                $t->string('created_by', 40)->nullable();
                $t->string('created_at', 30);
            });
        }
        if (!Schema::hasTable('expenses')) {
            Schema::create('expenses', function (Blueprint $t) {
                $t->string('id', 40)->primary();
                $t->string('tenant_id', 40)->index();
                $t->string('category', 30)->default('other');
                $t->string('description', 300);
                $t->string('vendor', 160)->nullable();
                $t->decimal('amount', 18, 2);
                $t->string('currency', 8)->default('UZS');
                $t->string('expense_date', 10)->index();
                $t->string('project_id', 40)->nullable();
                $t->string('created_by', 40)->nullable();
                $t->string('created_at', 30);
            });
        }
        if (!Schema::hasTable('api_keys')) {
            Schema::create('api_keys', function (Blueprint $t) {
                $t->string('id', 40)->primary();
                $t->string('tenant_id', 40)->index();
                $t->string('name', 120);
                $t->string('prefix', 16);                      // shown in the UI; the full token is shown once at creation
                $t->string('key_hash', 64)->unique();          // sha256 of the token — the token itself is never stored
                $t->text('scopes');                            // JSON list of permissions
                $t->string('last_used_at', 30)->nullable();
                $t->string('expires_at', 30)->nullable();
                $t->string('revoked_at', 30)->nullable();
                $t->string('created_by', 40)->nullable();
                $t->string('created_at', 30);
            });
        }
        if (!Schema::hasTable('webhook_endpoints')) {
            Schema::create('webhook_endpoints', function (Blueprint $t) {
                $t->string('id', 40)->primary();
                $t->string('tenant_id', 40)->index();
                $t->string('url', 500);
                $t->text('secret');                            // encrypted (Crypt); used for the HMAC-SHA256 signature
                $t->text('events');                            // JSON list, or ["*"]
                $t->boolean('is_active')->default(true);
                $t->string('created_by', 40)->nullable();
                $t->string('created_at', 30);
            });
        }
        if (!Schema::hasTable('webhook_deliveries')) {
            Schema::create('webhook_deliveries', function (Blueprint $t) {
                $t->string('id', 40)->primary();
                $t->string('tenant_id', 40)->index();
                $t->string('endpoint_id', 40)->index();
                $t->string('event', 60);
                $t->text('payload');
                $t->string('status', 12)->default('pending');   // pending | success | dead
                $t->integer('attempts')->default(0);
                $t->string('next_attempt_at', 30)->nullable();
                $t->integer('response_code')->nullable();
                $t->string('error', 300)->nullable();
                $t->string('created_at', 30);
                $t->string('delivered_at', 30)->nullable();
                $t->index(['status', 'next_attempt_at']);
            });
        }
    }

    public function down(): void
    {
        foreach (['webhook_deliveries', 'webhook_endpoints', 'api_keys', 'expenses', 'client_payments', 'client_invoices'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
