<?php
namespace App\Http\Controllers\Api\V1;

use App\Events\QuoteStatusChanged;
use App\Http\Requests\StoreQuoteRequest;
use App\Support\ApiException;
use App\Support\Ctx;
use App\Support\H;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Commercial building blocks of the CRM: price lists, quotes (with a status workflow that can be converted
 * into an order) and tags. Every query is tenant-scoped and every referenced id is re-verified against the tenant.
 */
class CommercialController
{
    private const TAGGABLE = [
        'lead' => 'leads', 'contact' => 'contacts', 'organization' => 'organizations', 'deal' => 'deals',
        'project' => 'projects', 'order' => 'orders', 'task' => 'tasks',
    ];

    /** The caller as a plain object for the Gate (the CRM has its own session layer, not Laravel's guard). */
    private function actor(Ctx $ctx): object
    {
        return (object) [
            'id' => $ctx->user['id'] ?? null, 'role' => $ctx->role, 'tenant_id' => $ctx->tenantId,
            'is_platform_admin' => !empty($ctx->user['is_platform_admin']),
        ];
    }

    private function gate(Ctx $ctx, string $ability, object $quote, mixed ...$extra): void
    {
        $allowed = Gate::forUser($this->actor($ctx))->allows("quote.{$ability}", [$quote, ...$extra]);
        if (!$allowed) throw new ApiException(403, 'Это действие с коммерческим предложением сейчас недоступно', 'forbidden');
    }

    private function quoteOrFail(Ctx $ctx, string $id): object
    {
        $q = DB::table('quotes')->where('id', $id)->where('tenant_id', $ctx->tenantId)->first();
        if (!$q) throw new ApiException(404, 'Коммерческое предложение не найдено', 'not_found');
        return $q;
    }

    // ── price lists ──────────────────────────────────────────────────────────
    public function listPriceLists(Ctx $ctx)
    {
        $ctx->auth(); H::requireTenant($ctx); H::requirePermission($ctx, 'crm.read');
        $rows = DB::table('price_lists')->where('tenant_id', $ctx->tenantId)->orderByDesc('is_default')->orderBy('name')->get();
        return H::json(['data' => $rows->map(fn ($r) => [...(array) $r, 'is_default' => (bool) $r->is_default, 'is_active' => (bool) $r->is_active,
            'items_count' => DB::table('price_list_items')->where('price_list_id', $r->id)->count()])->all()]);
    }

    public function createPriceList(Ctx $ctx)
    {
        $ctx->auth(); H::requireTenant($ctx); H::requirePermission($ctx, 'crm.write');
        $b = H::body($ctx);
        $id = H::newId('prl');
        $makeDefault = !empty($b['is_default']);
        DB::transaction(function () use ($ctx, $b, $id, $makeDefault) {
            if ($makeDefault) DB::table('price_lists')->where('tenant_id', $ctx->tenantId)->update(['is_default' => 0]);
            DB::table('price_lists')->insert([
                'id' => $id, 'tenant_id' => $ctx->tenantId, 'name' => H::str($b['name'] ?? null, 'name', true, 160),
                'currency' => strtoupper(H::str($b['currency'] ?? null, 'currency', false, 8) ?? 'UZS'),
                'is_default' => $makeDefault ? 1 : 0, 'is_active' => 1, 'created_at' => H::now(),
            ]);
        });
        H::audit($ctx, 'price_list.created', 'price_list', $id);
        return H::json(['id' => $id], 201);
    }

    public function getPriceList(Ctx $ctx)
    {
        $ctx->auth(); H::requireTenant($ctx); H::requirePermission($ctx, 'crm.read');
        $pl = DB::table('price_lists')->where('id', $ctx->params['id'])->where('tenant_id', $ctx->tenantId)->first();
        if (!$pl) throw new ApiException(404, 'Прайс-лист не найден', 'not_found');
        $items = DB::table('price_list_items as i')->join('products as p', 'p.id', '=', 'i.product_id')
            ->where('i.price_list_id', $pl->id)->where('i.tenant_id', $ctx->tenantId)
            ->get(['i.product_id', 'p.name', 'p.sku', 'p.price as base_price', 'i.price']);
        return H::json([...(array) $pl, 'is_default' => (bool) $pl->is_default, 'is_active' => (bool) $pl->is_active, 'items' => $items]);
    }

    /** Replaces the whole item set: { items: [{ product_id, price }] }. */
    public function setPriceListItems(Ctx $ctx)
    {
        $ctx->auth(); H::requireTenant($ctx); H::requirePermission($ctx, 'crm.write');
        $pl = DB::table('price_lists')->where('id', $ctx->params['id'])->where('tenant_id', $ctx->tenantId)->first();
        if (!$pl) throw new ApiException(404, 'Прайс-лист не найден', 'not_found');
        $items = H::body($ctx)['items'] ?? null;
        if (!is_array($items) || count($items) > 1000) throw new ApiException(400, 'items: массив до 1000 позиций', 'bad_request');
        $rows = [];
        foreach ($items as $it) {
            $pid = H::tenantRef($ctx, $it['product_id'] ?? null, 'products', 'товар');
            if (!$pid) throw new ApiException(400, 'Укажите product_id', 'bad_request');
            $rows[$pid] = [
                'id' => H::newId('pli'), 'tenant_id' => $ctx->tenantId, 'price_list_id' => $pl->id, 'product_id' => $pid,
                'price' => (float) H::num($it['price'] ?? null, 'price', true, 0),
            ];
        }
        DB::transaction(function () use ($pl, $rows) {
            DB::table('price_list_items')->where('price_list_id', $pl->id)->delete();
            if ($rows) DB::table('price_list_items')->insert(array_values($rows));
        });
        return H::json(['ok' => true, 'items' => count($rows)]);
    }

    public function deletePriceList(Ctx $ctx)
    {
        $ctx->auth(); H::requireTenant($ctx); H::requirePermission($ctx, 'crm.delete');
        $n = DB::transaction(function () use ($ctx) {
            $n = DB::table('price_lists')->where('id', $ctx->params['id'])->where('tenant_id', $ctx->tenantId)->delete();
            if ($n) {
                DB::table('price_list_items')->where('price_list_id', $ctx->params['id'])->delete();
                DB::table('quotes')->where('tenant_id', $ctx->tenantId)->where('price_list_id', $ctx->params['id'])->update(['price_list_id' => null]);
            }
            return $n;
        });
        if (!$n) throw new ApiException(404, 'Прайс-лист не найден', 'not_found');
        return H::json(['ok' => true]);
    }

    // ── quotes ───────────────────────────────────────────────────────────────
    public function listQuotes(Ctx $ctx)
    {
        $ctx->auth(); H::requireTenant($ctx); H::requirePermission($ctx, 'crm.read');
        [$limit, $page, $offset] = H::paginate($ctx);
        $q = DB::table('quotes as q')->leftJoin('organizations as o', 'o.id', '=', 'q.org_id')->where('q.tenant_id', $ctx->tenantId);
        if ($s = $ctx->query('status')) $q->where('q.status', (string) $s);
        if ($d = $ctx->query('deal_id')) $q->where('q.deal_id', (string) $d);
        $total = (clone $q)->count();
        $rows = $q->orderByDesc('q.created_at')->limit($limit)->offset($offset)->get(['q.*', 'o.name as org_name']);
        return H::json(['data' => $rows, 'total' => $total, 'page' => $page, 'limit' => $limit]);
    }

    public function getQuote(Ctx $ctx)
    {
        $ctx->auth(); H::requireTenant($ctx);
        $quote = $this->quoteOrFail($ctx, $ctx->params['id']);
        $this->gate($ctx, 'view', $quote);
        $items = DB::table('quote_items')->where('quote_id', $quote->id)->where('tenant_id', $ctx->tenantId)->orderBy('id')->get();
        return H::json([...(array) $quote, 'items' => $items]);
    }

    public function createQuote(Ctx $ctx, StoreQuoteRequest $req)
    {
        $user = $ctx->auth(); H::requireTenant($ctx); H::requirePermission($ctx, 'deals.write');
        $b = $req->validated();
        $orgId = H::tenantRef($ctx, $b['org_id'] ?? null, 'organizations', 'компания');
        $contactId = H::tenantRef($ctx, $b['contact_id'] ?? null, 'contacts', 'контакт');
        $dealId = H::tenantRef($ctx, $b['deal_id'] ?? null, 'deals', 'сделка');
        $plId = H::tenantRef($ctx, $b['price_list_id'] ?? null, 'price_lists', 'прайс-лист');
        $pl = $plId ? DB::table('price_lists')->where('id', $plId)->first() : null;
        $currency = $pl->currency ?? 'UZS';
        $discount = (float) ($b['discount_percent'] ?? 0);

        $lines = [];
        $subtotal = 0.0;
        foreach ($b['items'] as $it) {
            $pid = H::tenantRef($ctx, $it['product_id'] ?? null, 'products', 'товар');
            $product = $pid ? DB::table('products')->where('id', $pid)->first() : null;
            $desc = $it['description'] ?? ($product->name ?? null);
            if (!$desc) throw new ApiException(422, 'У позиции должно быть описание или товар', 'validation_failed');
            $unit = $it['unit_price'] ?? null;
            if ($unit === null && $product) {
                $listed = $plId ? DB::table('price_list_items')->where('price_list_id', $plId)->where('product_id', $pid)->value('price') : null;
                $unit = $listed ?? $product->price;
            }
            if ($unit === null) throw new ApiException(422, 'У позиции без товара нужна unit_price', 'validation_failed');
            $qty = (float) ($it['quantity'] ?? 1);
            $total = round($qty * (float) $unit, 2);
            $subtotal += $total;
            $lines[] = ['product_id' => $pid, 'description' => $desc, 'quantity' => $qty, 'unit_price' => (float) $unit, 'line_total' => $total];
        }
        $subtotal = round($subtotal, 2);
        $grand = round($subtotal * (1 - $discount / 100), 2);

        $id = H::newId('qte');
        DB::transaction(function () use ($ctx, $user, $b, $id, $orgId, $contactId, $dealId, $plId, $currency, $discount, $subtotal, $grand, $lines) {
            $seq = DB::table('quotes')->where('tenant_id', $ctx->tenantId)->count() + 1;
            DB::table('quotes')->insert([
                'id' => $id, 'tenant_id' => $ctx->tenantId, 'number' => 'QT-' . str_pad((string) $seq, 5, '0', STR_PAD_LEFT) . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 3)),
                'deal_id' => $dealId, 'org_id' => $orgId, 'contact_id' => $contactId, 'price_list_id' => $plId,
                'status' => 'draft', 'currency' => $currency, 'subtotal' => $subtotal, 'discount_percent' => $discount, 'total' => $grand,
                'valid_until' => $b['valid_until'] ?? null, 'notes' => $b['notes'] ?? null,
                'created_by' => $user['id'], 'created_at' => H::now(), 'updated_at' => H::now(),
            ]);
            foreach ($lines as $l) DB::table('quote_items')->insert(['id' => H::newId('qti'), 'tenant_id' => $ctx->tenantId, 'quote_id' => $id, ...$l]);
        });
        H::audit($ctx, 'quote.created', 'quote', $id, ['total' => $grand]);
        return H::json(['id' => $id, 'subtotal' => $subtotal, 'total' => $grand], 201);
    }

    public function setQuoteStatus(Ctx $ctx)
    {
        $user = $ctx->auth(); H::requireTenant($ctx);
        $quote = $this->quoteOrFail($ctx, $ctx->params['id']);
        $to = H::oneOf(H::body($ctx)['status'] ?? null, 'status', ['sent', 'accepted', 'rejected', 'expired']);
        $this->gate($ctx, 'transition', $quote, $to);
        // Compare-and-set: two concurrent transitions cannot both win.
        $changed = DB::table('quotes')->where('id', $quote->id)->where('status', $quote->status)->update(['status' => $to, 'updated_at' => H::now()]);
        if (!$changed) throw new ApiException(409, 'Статус предложения уже изменился', 'conflict');
        QuoteStatusChanged::dispatch($ctx->tenantId, $quote->id, $quote->number, $quote->status, $to, $user['id'], $user['name'], $quote->deal_id);
        H::audit($ctx, 'quote.' . $to, 'quote', $quote->id);
        return H::json(['ok' => true, 'status' => $to]);
    }

    /** Accepted quote → draft order with the same lines. One quote converts exactly once. */
    public function convertQuote(Ctx $ctx)
    {
        $user = $ctx->auth(); H::requireTenant($ctx);
        $quote = $this->quoteOrFail($ctx, $ctx->params['id']);
        $this->gate($ctx, 'convert', $quote);
        $orderId = H::newId('ord');
        DB::transaction(function () use ($ctx, $user, $quote, $orderId) {
            // Claim the quote first; a concurrent convert finds order_id already set and aborts.
            $claimed = DB::table('quotes')->where('id', $quote->id)->whereNull('order_id')->update(['order_id' => $orderId, 'updated_at' => H::now()]);
            if (!$claimed) throw new ApiException(409, 'Предложение уже преобразовано в заказ', 'conflict');
            $seq = DB::table('orders')->where('tenant_id', $ctx->tenantId)->count() + 1;
            DB::table('orders')->insert([
                'id' => $orderId, 'tenant_id' => $ctx->tenantId, 'number' => 'ORD-' . str_pad((string) $seq, 5, '0', STR_PAD_LEFT) . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 3)),
                'org_id' => $quote->org_id, 'contact_id' => $quote->contact_id, 'deal_id' => $quote->deal_id,
                'status' => 'draft', 'amount' => $quote->total, 'currency' => $quote->currency, 'source' => 'manual', 'payment_status' => 'unpaid',
                'notes' => "Из коммерческого предложения {$quote->number}", 'created_by' => $user['id'], 'created_at' => H::now(), 'updated_at' => H::now(),
            ]);
            $factor = 1 - ((float) $quote->discount_percent / 100);
            foreach (DB::table('quote_items')->where('quote_id', $quote->id)->get() as $l) {
                $price = round((float) $l->unit_price * $factor, 2);
                DB::table('order_items')->insert([
                    'id' => H::newId('oit'), 'tenant_id' => $ctx->tenantId, 'order_id' => $orderId, 'product_id' => $l->product_id,
                    'name' => $l->description, 'qty' => $l->quantity, 'price' => $price, 'total' => round($price * (float) $l->quantity, 2),
                ]);
            }
        });
        H::audit($ctx, 'quote.converted', 'quote', $quote->id, ['order_id' => $orderId]);
        return H::json(['ok' => true, 'order_id' => $orderId], 201);
    }

    public function deleteQuote(Ctx $ctx)
    {
        $ctx->auth(); H::requireTenant($ctx);
        $quote = $this->quoteOrFail($ctx, $ctx->params['id']);
        $this->gate($ctx, 'update', $quote); // only drafts can be removed
        DB::transaction(function () use ($quote) {
            DB::table('quote_items')->where('quote_id', $quote->id)->delete();
            DB::table('quotes')->where('id', $quote->id)->delete();
        });
        H::audit($ctx, 'quote.deleted', 'quote', $quote->id);
        return H::json(['ok' => true]);
    }

    // ── tags ─────────────────────────────────────────────────────────────────
    public function listTags(Ctx $ctx)
    {
        $ctx->auth(); H::requireTenant($ctx); H::requirePermission($ctx, 'crm.read');
        $rows = DB::table('tags as t')->where('t.tenant_id', $ctx->tenantId)->orderBy('t.name')
            ->get(['t.id', 't.name', 't.color'])
            ->map(fn ($t) => [...(array) $t, 'usage' => DB::table('taggings')->where('tag_id', $t->id)->count()]);
        return H::json(['data' => $rows->all()]);
    }

    public function createTag(Ctx $ctx)
    {
        $ctx->auth(); H::requireTenant($ctx); H::requirePermission($ctx, 'crm.write');
        $b = H::body($ctx);
        $name = H::str($b['name'] ?? null, 'name', true, 60);
        $color = H::str($b['color'] ?? null, 'color', false, 9) ?? '#2563EB';
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) throw new ApiException(400, 'color: #RRGGBB', 'bad_request');
        if (DB::table('tags')->where('tenant_id', $ctx->tenantId)->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->exists()) {
            throw new ApiException(409, 'Тег с таким названием уже существует', 'conflict');
        }
        $id = H::newId('tag');
        DB::table('tags')->insert(['id' => $id, 'tenant_id' => $ctx->tenantId, 'name' => $name, 'color' => $color, 'created_at' => H::now()]);
        return H::json(['id' => $id], 201);
    }

    public function deleteTag(Ctx $ctx)
    {
        $ctx->auth(); H::requireTenant($ctx); H::requirePermission($ctx, 'crm.write');
        $n = DB::transaction(function () use ($ctx) {
            $n = DB::table('tags')->where('id', $ctx->params['id'])->where('tenant_id', $ctx->tenantId)->delete();
            if ($n) DB::table('taggings')->where('tag_id', $ctx->params['id'])->delete();
            return $n;
        });
        if (!$n) throw new ApiException(404, 'Тег не найден', 'not_found');
        return H::json(['ok' => true]);
    }

    /** Verifies the tag and the tagged record both belong to this tenant. */
    private function taggingTarget(Ctx $ctx, array $b): array
    {
        $tag = DB::table('tags')->where('id', $ctx->params['id'])->where('tenant_id', $ctx->tenantId)->first();
        if (!$tag) throw new ApiException(404, 'Тег не найден', 'not_found');
        $type = H::oneOf($b['entity_type'] ?? null, 'entity_type', array_keys(self::TAGGABLE));
        $eid = H::str($b['entity_id'] ?? null, 'entity_id', true, 40);
        if (!DB::table(self::TAGGABLE[$type])->where('id', $eid)->where('tenant_id', $ctx->tenantId)->exists()) throw new ApiException(404, 'Запись не найдена', 'not_found');
        return [$tag, $type, $eid];
    }

    public function assignTag(Ctx $ctx)
    {
        $ctx->auth(); H::requireTenant($ctx); H::requirePermission($ctx, 'crm.write');
        [$tag, $type, $eid] = $this->taggingTarget($ctx, H::body($ctx));
        DB::table('taggings')->insertOrIgnore(['id' => H::newId('tgg'), 'tenant_id' => $ctx->tenantId, 'tag_id' => $tag->id, 'entity_type' => $type, 'entity_id' => $eid, 'created_at' => H::now()]);
        return H::json(['ok' => true], 201);
    }

    public function unassignTag(Ctx $ctx)
    {
        $ctx->auth(); H::requireTenant($ctx); H::requirePermission($ctx, 'crm.write');
        [$tag, $type, $eid] = $this->taggingTarget($ctx, ['entity_type' => $ctx->query('entity_type'), 'entity_id' => $ctx->query('entity_id')]);
        DB::table('taggings')->where('tag_id', $tag->id)->where('entity_type', $type)->where('entity_id', $eid)->delete();
        return H::json(['ok' => true]);
    }

    /** Tags on one record, or — with `tag_id` — the ids of records carrying a tag. */
    public function entityTags(Ctx $ctx)
    {
        $ctx->auth(); H::requireTenant($ctx); H::requirePermission($ctx, 'crm.read');
        $type = H::oneOf($ctx->query('entity_type'), 'entity_type', array_keys(self::TAGGABLE));
        $q = DB::table('taggings as g')->join('tags as t', 't.id', '=', 'g.tag_id')->where('g.tenant_id', $ctx->tenantId)->where('g.entity_type', $type);
        if ($tagId = $ctx->query('tag_id')) return H::json(['data' => $q->where('g.tag_id', (string) $tagId)->pluck('g.entity_id')->all()]);
        $q->where('g.entity_id', H::str($ctx->query('entity_id'), 'entity_id', true, 40));
        return H::json(['data' => $q->get(['t.id', 't.name', 't.color'])]);
    }
}
