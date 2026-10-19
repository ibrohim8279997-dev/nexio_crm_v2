<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS inventory_suppliers (
  id TEXT PRIMARY KEY, tenant_id TEXT NOT NULL, name TEXT NOT NULL, code TEXT NOT NULL,
  phone TEXT, email TEXT, tax_id TEXT, payment_terms TEXT, rating REAL NOT NULL DEFAULT 0,
  status TEXT NOT NULL DEFAULT 'active', created_by TEXT, created_at TEXT NOT NULL, updated_at TEXT NOT NULL,
  UNIQUE(tenant_id, code)
);
CREATE INDEX IF NOT EXISTS idx_inventory_suppliers_tenant ON inventory_suppliers(tenant_id, status);

CREATE TABLE IF NOT EXISTS inventory_purchase_orders (
  id TEXT PRIMARY KEY, tenant_id TEXT NOT NULL, supplier_id TEXT NOT NULL, number TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'draft', currency TEXT NOT NULL DEFAULT 'UZS', subtotal REAL NOT NULL DEFAULT 0,
  tax REAL NOT NULL DEFAULT 0, total REAL NOT NULL DEFAULT 0, expected_at TEXT, note TEXT,
  created_by TEXT, approved_by TEXT, approved_at TEXT, created_at TEXT NOT NULL, updated_at TEXT NOT NULL,
  UNIQUE(tenant_id, number)
);
CREATE INDEX IF NOT EXISTS idx_inventory_purchase_orders_tenant ON inventory_purchase_orders(tenant_id, status, created_at);

CREATE TABLE IF NOT EXISTS inventory_purchase_items (
  id TEXT PRIMARY KEY, tenant_id TEXT NOT NULL, purchase_order_id TEXT NOT NULL, product_id TEXT NOT NULL,
  ordered_quantity REAL NOT NULL, received_quantity REAL NOT NULL DEFAULT 0, unit_cost REAL NOT NULL DEFAULT 0,
  tax_rate REAL NOT NULL DEFAULT 0, created_at TEXT NOT NULL, updated_at TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_inventory_purchase_items_order ON inventory_purchase_items(tenant_id, purchase_order_id);

CREATE TABLE IF NOT EXISTS inventory_receipts (
  id TEXT PRIMARY KEY, tenant_id TEXT NOT NULL, purchase_order_id TEXT NOT NULL, warehouse_id TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'draft', received_at TEXT, note TEXT, created_by TEXT, created_at TEXT NOT NULL, updated_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS inventory_receipt_items (
  id TEXT PRIMARY KEY, tenant_id TEXT NOT NULL, receipt_id TEXT NOT NULL, purchase_item_id TEXT NOT NULL,
  quantity REAL NOT NULL, created_at TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_inventory_receipts_tenant ON inventory_receipts(tenant_id, created_at);

CREATE TABLE IF NOT EXISTS inventory_counts (
  id TEXT PRIMARY KEY, tenant_id TEXT NOT NULL, warehouse_id TEXT NOT NULL, status TEXT NOT NULL DEFAULT 'draft',
  note TEXT, started_at TEXT, completed_at TEXT, created_by TEXT, approved_by TEXT, created_at TEXT NOT NULL, updated_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS inventory_count_items (
  id TEXT PRIMARY KEY, tenant_id TEXT NOT NULL, count_id TEXT NOT NULL, product_id TEXT NOT NULL,
  expected_quantity REAL NOT NULL DEFAULT 0, counted_quantity REAL, variance REAL, reason TEXT, created_at TEXT NOT NULL, updated_at TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_inventory_counts_tenant ON inventory_counts(tenant_id, status, created_at);

CREATE TABLE IF NOT EXISTS inventory_financial_snapshots (
  id TEXT PRIMARY KEY, tenant_id TEXT NOT NULL, period_start TEXT NOT NULL, period_end TEXT NOT NULL,
  inventory_value REAL NOT NULL DEFAULT 0, received_value REAL NOT NULL DEFAULT 0, issued_value REAL NOT NULL DEFAULT 0,
  cogs_value REAL NOT NULL DEFAULT 0, turnover REAL NOT NULL DEFAULT 0, payload TEXT NOT NULL DEFAULT '{}', created_at TEXT NOT NULL,
  UNIQUE(tenant_id, period_start, period_end)
);
SQL);
    }

    public function down(): void
    {
        foreach (['inventory_financial_snapshots', 'inventory_count_items', 'inventory_counts', 'inventory_receipt_items', 'inventory_receipts', 'inventory_purchase_items', 'inventory_purchase_orders', 'inventory_suppliers'] as $table) {
            DB::statement("DROP TABLE IF EXISTS {$table}");
        }
    }
};
