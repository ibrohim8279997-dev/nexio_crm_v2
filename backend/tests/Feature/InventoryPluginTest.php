<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;

final class InventoryPluginTest extends PluginTestCase
{
    public function test_inventory_is_tenant_scoped_and_issue_cannot_go_negative(): void
    {
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.inventory');
        $product = $this->actingAsAlpha()->postJson('/api/v1/plugins/nexio.inventory/operations/create_product', [
            'sku' => 'SKU-1', 'name' => 'Widget', 'min_stock' => 1,
        ])->assertStatus(200)->json('data.id');
        $warehouse = $this->actingAsAlpha()->postJson('/api/v1/plugins/nexio.inventory/operations/create_warehouse', [
            'code' => 'MAIN', 'name' => 'Main warehouse',
        ])->assertStatus(200)->json('data.id');

        $this->actingAsAlpha()->postJson('/api/v1/plugins/nexio.inventory/operations/issue', [
            'product_id' => $product, 'warehouse_id' => $warehouse, 'quantity' => 1,
        ])->assertStatus(409)->assertJsonPath('error.code', 'insufficient_stock');

        $this->actingAsAlpha()->postJson('/api/v1/plugins/nexio.inventory/operations/receive', [
            'product_id' => $product, 'warehouse_id' => $warehouse, 'quantity' => 3,
        ])->assertStatus(200);

        $this->actingAsBeta()->postJson('/api/v1/plugins/nexio.inventory/operations/stock', [
            'warehouse_id' => $warehouse,
        ])->assertStatus(404);
    }

    public function test_purchase_receipt_supports_partial_delivery_and_updates_stock(): void
    {
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.inventory');
        $product = $this->actingAsAlpha()->postJson('/api/v1/plugins/nexio.inventory/operations/create_product', [
            'sku' => 'SKU-2', 'name' => 'Partially delivered item', 'cost' => 100,
        ])->assertStatus(200)->json('data.id');
        $warehouse = $this->actingAsAlpha()->postJson('/api/v1/plugins/nexio.inventory/operations/create_warehouse', [
            'code' => 'MAIN2', 'name' => 'Main warehouse 2',
        ])->assertStatus(200)->json('data.id');
        $supplier = $this->actingAsAlpha()->postJson('/api/v1/plugins/nexio.inventory/operations/create_supplier', [
            'code' => 'SUP-1', 'name' => 'Supplier 1',
        ])->assertStatus(200)->json('data.id');
        $order = $this->actingAsAlpha()->postJson('/api/v1/plugins/nexio.inventory/operations/create_purchase_order', [
            'supplier_id' => $supplier, 'items' => [['product_id' => $product, 'quantity' => 10, 'unit_cost' => 100]],
        ])->assertStatus(200)->json('purchase_order_id');
        $this->actingAsAlpha()->postJson('/api/v1/plugins/nexio.inventory/operations/approve_purchase_order', ['purchase_order_id' => $order])->assertStatus(200);
        $item = (string) DB::selectOne('SELECT id FROM inventory_purchase_items WHERE purchase_order_id = ?', [$order])->id;
        $this->actingAsAlpha()->postJson('/api/v1/plugins/nexio.inventory/operations/receive_purchase', [
            'purchase_order_id' => $order, 'warehouse_id' => $warehouse, 'items' => [['purchase_item_id' => $item, 'quantity' => 4]],
        ])->assertStatus(200);
        $this->assertSame('partially_received', (string) DB::selectOne('SELECT status FROM inventory_purchase_orders WHERE id=?', [$order])->status);
        $this->assertSame(4.0, (float) DB::selectOne('SELECT quantity FROM inventory_stock WHERE tenant_id=? AND product_id=? AND warehouse_id=?', ['tnt_a', $product, $warehouse])->quantity);
    }
}
