<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS inventory_products (
  id TEXT PRIMARY KEY, tenant_id TEXT NOT NULL, sku TEXT NOT NULL, name TEXT NOT NULL,
  unit TEXT NOT NULL DEFAULT 'pcs', category TEXT, cost REAL NOT NULL DEFAULT 0,
  min_stock REAL NOT NULL DEFAULT 0, is_active INTEGER NOT NULL DEFAULT 1,
  created_by TEXT, created_at TEXT NOT NULL, updated_at TEXT NOT NULL,
  UNIQUE(tenant_id, sku)
);
CREATE INDEX IF NOT EXISTS idx_inventory_products_tenant ON inventory_products(tenant_id, is_active);

CREATE TABLE IF NOT EXISTS inventory_warehouses (
  id TEXT PRIMARY KEY, tenant_id TEXT NOT NULL, name TEXT NOT NULL, code TEXT NOT NULL,
  address TEXT, is_active INTEGER NOT NULL DEFAULT 1, created_by TEXT,
  created_at TEXT NOT NULL, updated_at TEXT NOT NULL, UNIQUE(tenant_id, code)
);
CREATE INDEX IF NOT EXISTS idx_inventory_warehouses_tenant ON inventory_warehouses(tenant_id, is_active);

CREATE TABLE IF NOT EXISTS inventory_stock (
  id TEXT PRIMARY KEY, tenant_id TEXT NOT NULL, product_id TEXT NOT NULL,
  warehouse_id TEXT NOT NULL, quantity REAL NOT NULL DEFAULT 0,
  reserved_quantity REAL NOT NULL DEFAULT 0, updated_at TEXT NOT NULL,
  UNIQUE(tenant_id, product_id, warehouse_id)
);
CREATE INDEX IF NOT EXISTS idx_inventory_stock_tenant ON inventory_stock(tenant_id, warehouse_id);

CREATE TABLE IF NOT EXISTS inventory_movements (
  id TEXT PRIMARY KEY, tenant_id TEXT NOT NULL, product_id TEXT NOT NULL,
  warehouse_id TEXT NOT NULL, type TEXT NOT NULL, quantity REAL NOT NULL,
  reference_type TEXT, reference_id TEXT, note TEXT, idempotency_key TEXT,
  created_by TEXT, created_at TEXT NOT NULL,
  UNIQUE(tenant_id, idempotency_key)
);
CREATE INDEX IF NOT EXISTS idx_inventory_movements_tenant ON inventory_movements(tenant_id, created_at);

CREATE TABLE IF NOT EXISTS inventory_reservations (
  id TEXT PRIMARY KEY, tenant_id TEXT NOT NULL, product_id TEXT NOT NULL,
  warehouse_id TEXT NOT NULL, quantity REAL NOT NULL, status TEXT NOT NULL DEFAULT 'active',
  reference_type TEXT, reference_id TEXT, created_by TEXT, created_at TEXT NOT NULL, released_at TEXT
);
CREATE INDEX IF NOT EXISTS idx_inventory_reservations_tenant ON inventory_reservations(tenant_id, status);
SQL);
    }

    public function down(): void
    {
        foreach (['inventory_reservations', 'inventory_movements', 'inventory_stock', 'inventory_warehouses', 'inventory_products'] as $table) {
            DB::statement("DROP TABLE IF EXISTS {$table}");
        }
    }
};
