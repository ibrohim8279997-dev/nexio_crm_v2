<?php
namespace Nexio\Inventory;

use App\Plugins\Contracts\OfficialPlugin;
use App\Plugins\PluginContext;
use App\Support\ApiException;
use App\Support\H;
use Illuminate\Support\Facades\DB;

final class Plugin implements OfficialPlugin
{
    public const PLUGIN_ID = 'nexio.inventory';

    public function id(): string { return self::PLUGIN_ID; }

    public function operations(): array
    {
        $read = ['permission' => 'inventory.read', 'metric' => null, 'requires_connection' => false];
        $move = ['permission' => 'inventory.movements.write', 'metric' => 'inventory_movements', 'requires_connection' => false];
        return [
            'dashboard' => $read + ['description' => 'Сводка склада'],
            'products' => $read + ['description' => 'Список товаров'],
            'create_product' => ['permission' => 'inventory.products.write', 'metric' => null, 'requires_connection' => false, 'description' => 'Создать товар'],
            'warehouses' => $read + ['description' => 'Список складов'],
            'create_warehouse' => ['permission' => 'inventory.warehouses.write', 'metric' => null, 'requires_connection' => false, 'description' => 'Создать склад'],
            'stock' => $read + ['description' => 'Остатки по складам'],
            'movements' => $read + ['description' => 'История движений'],
            'receive' => $move + ['description' => 'Оприходовать товар'],
            'issue' => $move + ['description' => 'Списать товар'],
            'transfer' => $move + ['description' => 'Переместить товар'],
            'reserve' => ['permission' => 'inventory.reservations.write', 'metric' => null, 'requires_connection' => false, 'description' => 'Зарезервировать товар'],
            'release_reservation' => ['permission' => 'inventory.reservations.write', 'metric' => null, 'requires_connection' => false, 'description' => 'Снять резерв'],
            'suppliers' => $read + ['description' => 'Список поставщиков'],
            'create_supplier' => ['permission' => 'inventory.products.write', 'metric' => null, 'requires_connection' => false, 'description' => 'Создать поставщика'],
            'purchase_orders' => $read + ['description' => 'Список закупок'],
            'create_purchase_order' => ['permission' => 'inventory.movements.write', 'metric' => null, 'requires_connection' => false, 'description' => 'Создать закупку'],
            'approve_purchase_order' => ['permission' => 'inventory.movements.write', 'metric' => null, 'requires_connection' => false, 'description' => 'Согласовать закупку'],
            'receive_purchase' => $move + ['description' => 'Принять поставку'],
            'return_purchase' => $move + ['description' => 'Вернуть товар поставщику'],
            'create_count' => ['permission' => 'inventory.movements.write', 'metric' => null, 'requires_connection' => false, 'description' => 'Начать инвентаризацию'],
            'record_count' => ['permission' => 'inventory.movements.write', 'metric' => null, 'requires_connection' => false, 'description' => 'Зафиксировать фактический остаток'],
            'approve_count' => ['permission' => 'inventory.movements.write', 'metric' => null, 'requires_connection' => false, 'description' => 'Утвердить расхождения'],
            'financials' => $read + ['description' => 'Финансовая аналитика запасов'],
        ];
    }

    public function execute(string $operation, array $input, PluginContext $context): array
    {
        return match ($operation) {
            'dashboard' => $this->dashboard($context),
            'products' => ['data' => $this->rows('inventory_products', $context, 'sku')],
            'warehouses' => ['data' => $this->rows('inventory_warehouses', $context, 'code')],
            'stock' => $this->stock($context, $input),
            'movements' => $this->movements($context, $input),
            'create_product' => $this->createProduct($context, $input),
            'create_warehouse' => $this->createWarehouse($context, $input),
            'receive' => $this->move($context, $input, 'receive'),
            'issue' => $this->move($context, $input, 'issue'),
            'transfer' => $this->transfer($context, $input),
            'reserve' => $this->reserve($context, $input),
            'release_reservation' => $this->releaseReservation($context, $input),
            'suppliers' => ['data' => $this->rows('inventory_suppliers', $context, 'name')],
            'create_supplier' => $this->createSupplier($context, $input),
            'purchase_orders' => ['data' => $this->purchaseOrders($context)],
            'create_purchase_order' => $this->createPurchaseOrder($context, $input),
            'approve_purchase_order' => $this->approvePurchaseOrder($context, $input),
            'receive_purchase' => $this->receivePurchase($context, $input),
            'return_purchase' => $this->returnPurchase($context, $input),
            'create_count' => $this->createCount($context, $input),
            'record_count' => $this->recordCount($context, $input),
            'approve_count' => $this->approveCount($context, $input),
            'financials' => $this->financials($context, $input),
            default => throw new ApiException(404, 'Неизвестная операция Inventory', 'unknown_operation'),
        };
    }

    public function health(PluginContext $context): array
    { return ['status' => 'ready', 'message' => 'Локальный складской модуль готов', 'checks' => [['name' => 'database', 'ok' => true, 'detail' => 'Tenant-scoped tables доступны']]]; }

    public function onEvent(string $event, array $payload, PluginContext $context): void
    { /* Order hooks are intentionally observational until a mapping is configured. */ }

    public function settingsSchema(): array
    { return ['default_warehouse' => ['type' => 'string', 'default' => '', 'label' => 'Склад по умолчанию'], 'negative_stock_policy' => ['type' => 'string', 'default' => 'block', 'label' => 'Политика отрицательных остатков'], 'autonomous_purchase_policy' => ['type' => 'string', 'default' => 'approval_required', 'label' => 'Политика AI-закупок']]; }

    private function rows(string $table, PluginContext $ctx, string $order): array
    { return array_map(fn ($r) => (array) $r, DB::select("SELECT * FROM {$table} WHERE tenant_id = ? ORDER BY {$order}", [$ctx->tenantId])); }

    private function dashboard(PluginContext $ctx): array
    {
        $t = $ctx->tenantId;
        return ['products' => (int) DB::selectOne('SELECT COUNT(*) c FROM inventory_products WHERE tenant_id=? AND is_active=1', [$t])->c,
            'warehouses' => (int) DB::selectOne('SELECT COUNT(*) c FROM inventory_warehouses WHERE tenant_id=? AND is_active=1', [$t])->c,
            'low_stock' => (int) DB::selectOne('SELECT COUNT(*) c FROM inventory_stock s JOIN inventory_products p ON p.id=s.product_id AND p.tenant_id=s.tenant_id WHERE s.tenant_id=? AND s.quantity-s.reserved_quantity <= p.min_stock', [$t])->c,
            'reserved_quantity' => (float) DB::selectOne('SELECT COALESCE(SUM(reserved_quantity),0) q FROM inventory_stock WHERE tenant_id=?', [$t])->q];
    }

    private function createProduct(PluginContext $ctx, array $in): array
    { $sku = $this->text($in, 'sku'); $name = $this->text($in, 'name'); $now = H::now(); $id = H::newId('invp'); DB::insert('INSERT INTO inventory_products (id,tenant_id,sku,name,unit,category,cost,min_stock,created_by,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)', [$id,$ctx->tenantId,$sku,$name,(string)($in['unit']??'pcs'),$in['category']??null,(float)($in['cost']??0),(float)($in['min_stock']??0),$ctx->userId,$now,$now]); return ['data' => (array) DB::selectOne('SELECT * FROM inventory_products WHERE id=? AND tenant_id=?',[$id,$ctx->tenantId])]; }

    private function createWarehouse(PluginContext $ctx, array $in): array
    { $code = $this->text($in, 'code'); $name = $this->text($in, 'name'); $now = H::now(); $id = H::newId('invw'); DB::insert('INSERT INTO inventory_warehouses (id,tenant_id,name,code,address,created_by,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?)',[$id,$ctx->tenantId,$name,$code,$in['address']??null,$ctx->userId,$now,$now]); return ['data'=>(array)DB::selectOne('SELECT * FROM inventory_warehouses WHERE id=? AND tenant_id=?',[$id,$ctx->tenantId])]; }

    private function move(PluginContext $ctx, array $in, string $type): array
    { $qty = $this->quantity($in); $product = $this->product($ctx, $in); $warehouse = $this->warehouse($ctx, (string)($in['warehouse_id']??'')); $key = isset($in['idempotency_key']) ? (string)$in['idempotency_key'] : null; return DB::transaction(function () use ($ctx,$in,$type,$qty,$product,$warehouse,$key) { $stock=$this->ensureStock($ctx,$product->id,$warehouse->id); if ($type==='issue' && (float)$stock->quantity-(float)$stock->reserved_quantity < $qty) throw new ApiException(409,'Недостаточно доступного остатка','insufficient_stock'); $delta=$type==='receive'?$qty:-$qty; DB::update('UPDATE inventory_stock SET quantity=quantity+?, updated_at=? WHERE id=? AND tenant_id=?',[$delta,H::now(),$stock->id,$ctx->tenantId]); $id=H::newId('invm'); DB::insert('INSERT INTO inventory_movements (id,tenant_id,product_id,warehouse_id,type,quantity,note,idempotency_key,created_by,created_at) VALUES (?,?,?,?,?,?,?,?,?,?)',[$id,$ctx->tenantId,$product->id,$warehouse->id,$type,$qty,$in['note']??null,$key,$ctx->userId,H::now()]); return ['movement_id'=>$id,'type'=>$type,'quantity'=>$qty,'product_id'=>$product->id,'warehouse_id'=>$warehouse->id]; }); }

    private function transfer(PluginContext $ctx, array $in): array
    { $qty=$this->quantity($in); $product=$this->product($ctx,$in); $from=$this->warehouse($ctx,(string)($in['from_warehouse_id']??'')); $to=$this->warehouse($ctx,(string)($in['to_warehouse_id']??'')); if($from->id===$to->id) throw new ApiException(400,'Склады должны отличаться','bad_request'); return DB::transaction(function()use($ctx,$in,$qty,$product,$from,$to){$src=$this->ensureStock($ctx,$product->id,$from->id); if((float)$src->quantity-(float)$src->reserved_quantity<$qty)throw new ApiException(409,'Недостаточно доступного остатка','insufficient_stock');$dst=$this->ensureStock($ctx,$product->id,$to->id);$now=H::now();DB::update('UPDATE inventory_stock SET quantity=quantity-?,updated_at=? WHERE id=?',[$qty,$now,$src->id]);DB::update('UPDATE inventory_stock SET quantity=quantity+?,updated_at=? WHERE id=?',[$qty,$now,$dst->id]);$id=H::newId('invm');DB::insert('INSERT INTO inventory_movements (id,tenant_id,product_id,warehouse_id,type,quantity,reference_type,reference_id,note,created_by,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)',[$id,$ctx->tenantId,$product->id,$from->id,'transfer_out',$qty,'warehouse',$to->id,$in['note']??null,$ctx->userId,$now]);DB::insert('INSERT INTO inventory_movements (id,tenant_id,product_id,warehouse_id,type,quantity,reference_type,reference_id,note,created_by,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)',[H::newId('invm'),$ctx->tenantId,$product->id,$to->id,'transfer_in',$qty,'warehouse',$from->id,$in['note']??null,$ctx->userId,$now]);return['movement_id'=>$id,'quantity'=>$qty,'from_warehouse_id'=>$from->id,'to_warehouse_id'=>$to->id];}); }

    private function reserve(PluginContext $ctx,array $in):array{$qty=$this->quantity($in);$p=$this->product($ctx,$in);$w=$this->warehouse($ctx,(string)($in['warehouse_id']??''));return DB::transaction(function()use($ctx,$in,$qty,$p,$w){$s=$this->ensureStock($ctx,$p->id,$w->id);if((float)$s->quantity-(float)$s->reserved_quantity<$qty)throw new ApiException(409,'Недостаточно доступного остатка','insufficient_stock');$id=H::newId('invr');DB::update('UPDATE inventory_stock SET reserved_quantity=reserved_quantity+?,updated_at=? WHERE id=?',[$qty,H::now(),$s->id]);DB::insert('INSERT INTO inventory_reservations (id,tenant_id,product_id,warehouse_id,quantity,reference_type,reference_id,created_by,created_at) VALUES (?,?,?,?,?,?,?,?,?)',[$id,$ctx->tenantId,$p->id,$w->id,$qty,$in['reference_type']??null,$in['reference_id']??null,$ctx->userId,H::now()]);return['reservation_id'=>$id,'quantity'=>$qty];});}

    private function releaseReservation(PluginContext $ctx,array $in):array{return DB::transaction(function()use($ctx,$in){$r=DB::selectOne("SELECT * FROM inventory_reservations WHERE id=? AND tenant_id=? AND status='active'",[(string)($in['reservation_id']??''),$ctx->tenantId]);if(!$r)throw new ApiException(404,'Резерв не найден','not_found');$s=$this->ensureStock($ctx,$r->product_id,$r->warehouse_id);DB::update("UPDATE inventory_reservations SET status='released',released_at=? WHERE id=? AND tenant_id=?",[H::now(),$r->id,$ctx->tenantId]);DB::update('UPDATE inventory_stock SET reserved_quantity=MAX(0,reserved_quantity-?),updated_at=? WHERE id=?',[$r->quantity,H::now(),$s->id]);return['reservation_id'=>$r->id,'status'=>'released'];});}

    private function stock(PluginContext $ctx,array $in):array{$where='s.tenant_id=?';$b=[$ctx->tenantId];if(!empty($in['warehouse_id'])){$where.=' AND s.warehouse_id=?';$b[]=$in['warehouse_id'];}return['data'=>array_map(fn($r)=>(array)$r,DB::select("SELECT s.*,p.sku,p.name,w.code warehouse_code FROM inventory_stock s JOIN inventory_products p ON p.id=s.product_id AND p.tenant_id=s.tenant_id JOIN inventory_warehouses w ON w.id=s.warehouse_id AND w.tenant_id=s.tenant_id WHERE {$where} ORDER BY p.sku",$b))];}
    private function movements(PluginContext $ctx,array $in):array{$limit=min(200,max(1,(int)($in['limit']??50)));return['data'=>array_map(fn($r)=>(array)$r,DB::select('SELECT m.*,p.sku,p.name,w.code warehouse_code FROM inventory_movements m JOIN inventory_products p ON p.id=m.product_id AND p.tenant_id=m.tenant_id JOIN inventory_warehouses w ON w.id=m.warehouse_id AND w.tenant_id=m.tenant_id WHERE m.tenant_id=? ORDER BY m.created_at DESC LIMIT ?',[$ctx->tenantId,$limit]))];}
    private function product(PluginContext $ctx,array $in):object{$id=(string)($in['product_id']??'');$r=DB::selectOne('SELECT * FROM inventory_products WHERE id=? AND tenant_id=? AND is_active=1',[$id,$ctx->tenantId]);if(!$r)throw new ApiException(404,'Товар не найден','not_found');return $r;}
    private function warehouse(PluginContext $ctx,string $id):object{$r=DB::selectOne('SELECT * FROM inventory_warehouses WHERE id=? AND tenant_id=? AND is_active=1',[$id,$ctx->tenantId]);if(!$r)throw new ApiException(404,'Склад не найден','not_found');return $r;}
    private function ensureStock(PluginContext $ctx,string $p,string $w):object{$r=DB::selectOne('SELECT * FROM inventory_stock WHERE tenant_id=? AND product_id=? AND warehouse_id=?',[$ctx->tenantId,$p,$w]);if(!$r){DB::insert('INSERT INTO inventory_stock (id,tenant_id,product_id,warehouse_id,updated_at) VALUES (?,?,?,?,?)',[H::newId('invs'),$ctx->tenantId,$p,$w,H::now()]);$r=DB::selectOne('SELECT * FROM inventory_stock WHERE tenant_id=? AND product_id=? AND warehouse_id=?',[$ctx->tenantId,$p,$w]);}return $r;}
    private function text(array $in,string $key):string{$v=trim((string)($in[$key]??''));if($v==='')throw new ApiException(422,"Поле {$key} обязательно",'validation_error');return mb_substr($v,0,160);}
    private function quantity(array $in):float{$q=(float)($in['quantity']??0);if($q<=0)throw new ApiException(422,'Количество должно быть больше нуля','validation_error');return $q;}

    private function createSupplier(PluginContext $ctx, array $in): array
    { $name=$this->text($in,'name');$code=$this->text($in,'code');$id=H::newId('invsup');$now=H::now();DB::insert('INSERT INTO inventory_suppliers (id,tenant_id,name,code,phone,email,tax_id,payment_terms,created_by,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)',[$id,$ctx->tenantId,$name,$code,$in['phone']??null,$in['email']??null,$in['tax_id']??null,$in['payment_terms']??null,$ctx->userId,$now,$now]);return ['data'=>(array)DB::selectOne('SELECT * FROM inventory_suppliers WHERE id=? AND tenant_id=?',[$id,$ctx->tenantId])]; }

    private function purchaseOrders(PluginContext $ctx): array
    { return array_map(fn($r)=>(array)$r,DB::select('SELECT o.*,s.name supplier_name, (SELECT COUNT(*) FROM inventory_purchase_items i WHERE i.purchase_order_id=o.id AND i.tenant_id=o.tenant_id) item_count FROM inventory_purchase_orders o JOIN inventory_suppliers s ON s.id=o.supplier_id AND s.tenant_id=o.tenant_id WHERE o.tenant_id=? ORDER BY o.created_at DESC',[$ctx->tenantId])); }

    private function createPurchaseOrder(PluginContext $ctx,array $in):array
    { $supplier=DB::selectOne('SELECT id FROM inventory_suppliers WHERE id=? AND tenant_id=? AND status=?',[(string)($in['supplier_id']??''),$ctx->tenantId,'active']);if(!$supplier)throw new ApiException(404,'Поставщик не найден','not_found');$items=(array)($in['items']??[]);if($items===[])throw new ApiException(422,'Закупка должна содержать позиции','validation_error');$id=H::newId('invpo');$now=H::now();$number=(string)($in['number']??('PO-'.date('Ymd-His')));$subtotal=0;DB::transaction(function()use($ctx,$in,$items,$id,$supplier,$now,$number,&$subtotal){foreach($items as $item){$p=$this->product($ctx,$item);$q=$this->quantity($item);$cost=max(0,(float)($item['unit_cost']??0));$subtotal+=$q*$cost;DB::insert('INSERT INTO inventory_purchase_items (id,tenant_id,purchase_order_id,product_id,ordered_quantity,unit_cost,tax_rate,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?)',[H::newId('invpi'),$ctx->tenantId,$id,$p->id,$q,$cost,(float)($item['tax_rate']??0),$now,$now]);}DB::insert('INSERT INTO inventory_purchase_orders (id,tenant_id,supplier_id,number,status,currency,subtotal,tax,total,expected_at,note,created_by,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)',[$id,$ctx->tenantId,$supplier->id,$number,'draft',(string)($in['currency']??'UZS'),$subtotal,(float)($in['tax']??0),$subtotal+(float)($in['tax']??0),$in['expected_at']??null,$in['note']??null,$ctx->userId,$now,$now]);});return['purchase_order_id'=>$id,'number'=>$number,'total'=>$subtotal+(float)($in['tax']??0)]; }

    private function approvePurchaseOrder(PluginContext $ctx,array $in):array
    { $id=(string)($in['purchase_order_id']??'');$row=DB::selectOne('SELECT * FROM inventory_purchase_orders WHERE id=? AND tenant_id=?',[$id,$ctx->tenantId]);if(!$row)throw new ApiException(404,'Закупка не найдена','not_found');if($row->status!=='draft')throw new ApiException(409,'Закупка уже обработана','conflict');DB::update("UPDATE inventory_purchase_orders SET status='approved',approved_by=?,approved_at=?,updated_at=? WHERE id=? AND tenant_id=?",[$ctx->userId,H::now(),H::now(),$id,$ctx->tenantId]);return['purchase_order_id'=>$id,'status'=>'approved']; }

    private function receivePurchase(PluginContext $ctx,array $in):array
    { $order=DB::selectOne("SELECT * FROM inventory_purchase_orders WHERE id=? AND tenant_id=? AND status IN ('approved','partially_received')",[(string)($in['purchase_order_id']??''),$ctx->tenantId]);if(!$order)throw new ApiException(404,'Утверждённая закупка не найдена','not_found');$warehouse=$this->warehouse($ctx,(string)($in['warehouse_id']??''));$items=(array)($in['items']??[]);if($items===[])throw new ApiException(422,'Укажите принятую поставку','validation_error');$receipt=H::newId('invrct');$now=H::now();DB::transaction(function()use($ctx,$in,$order,$warehouse,$items,$receipt,$now){DB::insert('INSERT INTO inventory_receipts (id,tenant_id,purchase_order_id,warehouse_id,status,received_at,note,created_by,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?)',[$receipt,$ctx->tenantId,$order->id,$warehouse->id,'draft',$now,$in['note']??null,$ctx->userId,$now,$now]);foreach($items as $item){$pi=DB::selectOne('SELECT * FROM inventory_purchase_items WHERE id=? AND tenant_id=? AND purchase_order_id=?',[(string)($item['purchase_item_id']??''),$ctx->tenantId,$order->id]);if(!$pi)throw new ApiException(404,'Позиция закупки не найдена','not_found');$q=$this->quantity($item);$remaining=(float)$pi->ordered_quantity-(float)$pi->received_quantity;if($q>$remaining)throw new ApiException(409,'Принято больше заказанного','over_receipt');$stock=$this->ensureStock($ctx,$pi->product_id,$warehouse->id);DB::update('UPDATE inventory_stock SET quantity=quantity+?,updated_at=? WHERE id=?',[$q,$now,$stock->id]);DB::update('UPDATE inventory_purchase_items SET received_quantity=received_quantity+?,updated_at=? WHERE id=?',[$q,$now,$pi->id]);DB::insert('INSERT INTO inventory_receipt_items (id,tenant_id,receipt_id,purchase_item_id,quantity,created_at) VALUES (?,?,?,?,?,?)',[H::newId('invri'),$ctx->tenantId,$receipt,$pi->id,$q,$now]);DB::insert('INSERT INTO inventory_movements (id,tenant_id,product_id,warehouse_id,type,quantity,reference_type,reference_id,created_by,created_at) VALUES (?,?,?,?,?,?,?,?,?,?)',[H::newId('invm'),$ctx->tenantId,$pi->product_id,$warehouse->id,'purchase_receipt',$q,'purchase_order',$order->id,$ctx->userId,$now]);}DB::update("UPDATE inventory_receipts SET status='posted',updated_at=? WHERE id=?",[$now,$receipt]);$remaining=DB::selectOne('SELECT COUNT(*) c FROM inventory_purchase_items WHERE purchase_order_id=? AND received_quantity<ordered_quantity',[$order->id])->c;DB::update('UPDATE inventory_purchase_orders SET status=?,updated_at=? WHERE id=?',[$remaining?'partially_received':'received',$now,$order->id]);});return['receipt_id'=>$receipt,'purchase_order_id'=>$order->id,'status'=>'posted']; }

    private function createCount(PluginContext $ctx,array $in):array
    { $warehouse=$this->warehouse($ctx,(string)($in['warehouse_id']??''));$id=H::newId('invct');$now=H::now();$rows=DB::select('SELECT product_id,quantity-reserved_quantity available FROM inventory_stock WHERE tenant_id=? AND warehouse_id=?',[$ctx->tenantId,$warehouse->id]);DB::transaction(function()use($ctx,$in,$warehouse,$id,$now,$rows){DB::insert('INSERT INTO inventory_counts (id,tenant_id,warehouse_id,status,note,started_at,created_by,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?)',[$id,$ctx->tenantId,$warehouse->id,'open',$in['note']??null,$now,$ctx->userId,$now,$now]);foreach($rows as $row)DB::insert('INSERT INTO inventory_count_items (id,tenant_id,count_id,product_id,expected_quantity,created_at,updated_at) VALUES (?,?,?,?,?,?,?)',[H::newId('invci'),$ctx->tenantId,$id,$row->product_id,$row->available,$now,$now]);});return['count_id'=>$id,'warehouse_id'=>$warehouse->id,'items'=>count($rows)]; }

    private function recordCount(PluginContext $ctx,array $in):array
    { $id=(string)($in['count_item_id']??'');$item=DB::selectOne('SELECT * FROM inventory_count_items WHERE id=? AND tenant_id=?',[$id,$ctx->tenantId]);if(!$item)throw new ApiException(404,'Позиция инвентаризации не найдена','not_found');$q=(float)($in['counted_quantity']??-1);if($q<0)throw new ApiException(422,'Укажите фактическое количество','validation_error');$variance=$q-(float)$item->expected_quantity;DB::update('UPDATE inventory_count_items SET counted_quantity=?,variance=?,reason=?,updated_at=? WHERE id=? AND tenant_id=?',[$q,$variance,$in['reason']??null,H::now(),$id,$ctx->tenantId]);return['count_item_id'=>$id,'variance'=>$variance]; }

    private function approveCount(PluginContext $ctx,array $in):array
    { $count=DB::selectOne("SELECT * FROM inventory_counts WHERE id=? AND tenant_id=? AND status='open'",[(string)($in['count_id']??''),$ctx->tenantId]);if(!$count)throw new ApiException(404,'Инвентаризация не найдена','not_found');$items=DB::select('SELECT * FROM inventory_count_items WHERE count_id=? AND tenant_id=?',[$count->id,$ctx->tenantId]);foreach($items as $item)if($item->counted_quantity===null)throw new ApiException(409,'Не все позиции пересчитаны','count_incomplete');DB::transaction(function()use($ctx,$count,$items){foreach($items as $item){$stock=$this->ensureStock($ctx,$item->product_id,$count->warehouse_id);$delta=(float)$item->variance;if(abs($delta)>0.000001){DB::update('UPDATE inventory_stock SET quantity=quantity+?,updated_at=? WHERE id=?',[$delta,H::now(),$stock->id]);DB::insert('INSERT INTO inventory_movements (id,tenant_id,product_id,warehouse_id,type,quantity,reference_type,reference_id,note,created_by,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)',[H::newId('invm'),$ctx->tenantId,$item->product_id,$count->warehouse_id,'inventory_adjustment',abs($delta),'inventory_count',$count->id,$item->reason,$ctx->userId,H::now()]);}}DB::update("UPDATE inventory_counts SET status='approved',completed_at=?,approved_by=?,updated_at=? WHERE id=?",[H::now(),$ctx->userId,H::now(),$count->id]);});return['count_id'=>$count->id,'status'=>'approved']; }

    private function returnPurchase(PluginContext $ctx, array $in): array
    { $pi=DB::selectOne('SELECT * FROM inventory_purchase_items WHERE id=? AND tenant_id=?',[(string)($in['purchase_item_id']??''),$ctx->tenantId]);if(!$pi)throw new ApiException(404,'Позиция закупки не найдена','not_found');$warehouse=$this->warehouse($ctx,(string)($in['warehouse_id']??''));$q=$this->quantity($in);if($q>(float)$pi->received_quantity)throw new ApiException(409,'Нельзя вернуть больше принятого','over_return');return DB::transaction(function()use($ctx,$in,$pi,$warehouse,$q){$stock=$this->ensureStock($ctx,$pi->product_id,$warehouse->id);if((float)$stock->quantity-(float)$stock->reserved_quantity<$q)throw new ApiException(409,'Недостаточно доступного товара для возврата','insufficient_stock');$now=H::now();DB::update('UPDATE inventory_stock SET quantity=quantity-?,updated_at=? WHERE id=?',[$q,$now,$stock->id]);DB::update('UPDATE inventory_purchase_items SET received_quantity=received_quantity-?,updated_at=? WHERE id=?',[$q,$now,$pi->id]);$id=H::newId('invm');DB::insert('INSERT INTO inventory_movements (id,tenant_id,product_id,warehouse_id,type,quantity,reference_type,reference_id,note,created_by,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)',[$id,$ctx->tenantId,$pi->product_id,$warehouse->id,'purchase_return',$q,'purchase_item',$pi->id,$in['note']??null,$ctx->userId,$now]);return['movement_id'=>$id,'status'=>'returned','quantity'=>$q];}); }

    private function financials(PluginContext $ctx,array $in):array
    { $t=$ctx->tenantId;$start=(string)($in['period_start']??date('Y-m-01'));$end=(string)($in['period_end']??date('Y-m-d'));$value=DB::selectOne('SELECT COALESCE(SUM(s.quantity*p.cost),0) v FROM inventory_stock s JOIN inventory_products p ON p.id=s.product_id AND p.tenant_id=s.tenant_id WHERE s.tenant_id=?',[$t])->v;$received=DB::selectOne("SELECT COALESCE(SUM(m.quantity*p.cost),0) v FROM inventory_movements m JOIN inventory_products p ON p.id=m.product_id AND p.tenant_id=m.tenant_id WHERE m.tenant_id=? AND m.type IN ('receive','purchase_receipt') AND m.created_at BETWEEN ? AND ?",[$t,$start,$end.' 23:59:59'])->v;$issued=DB::selectOne("SELECT COALESCE(SUM(m.quantity*p.cost),0) v FROM inventory_movements m JOIN inventory_products p ON p.id=m.product_id AND p.tenant_id=m.tenant_id WHERE m.tenant_id=? AND m.type='issue' AND m.created_at BETWEEN ? AND ?",[$t,$start,$end.' 23:59:59'])->v;return['period_start'=>$start,'period_end'=>$end,'inventory_value'=>(float)$value,'received_value'=>(float)$received,'issued_value'=>(float)$issued,'cogs_value'=>(float)$issued,'movement_count'=>(int)DB::selectOne('SELECT COUNT(*) c FROM inventory_movements WHERE tenant_id=? AND created_at BETWEEN ? AND ?',[$t,$start,$end.' 23:59:59'])->c]; }
}
