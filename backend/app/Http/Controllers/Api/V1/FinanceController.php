<?php
namespace App\Http\Controllers\Api\V1;

use App\Services\WebhookService;
use App\Support\ApiException;
use App\Support\Ctx;
use App\Support\H;
use Illuminate\Support\Facades\DB;

/**
 * Customer invoicing, incoming payments and expenses. Totals are always computed on the server from line items;
 * the client never supplies a total, a paid amount or a status.
 */
class FinanceController
{
    public const EXPENSE_CATEGORIES = ['salary', 'rent', 'marketing', 'software', 'travel', 'equipment', 'taxes', 'other'];
    public const PAY_METHODS = ['cash', 'bank', 'card', 'click', 'payme', 'other'];

    private function dec(mixed $v): float
    {
        return round((float) $v, 2);
    }

    /** Overdue is derived, not stored: a sent/partial invoice past its due date. */
    private function view(object $i): array
    {
        $outstanding = max(0, $this->dec($i->total) - $this->dec($i->paid_amount));
        $overdue = in_array($i->status, ['sent', 'partial'], true) && $i->due_at && $i->due_at < gmdate('Y-m-d');
        return [
            ...(array) $i, 'subtotal' => $this->dec($i->subtotal), 'tax_rate' => $this->dec($i->tax_rate), 'tax' => $this->dec($i->tax), 'total' => $this->dec($i->total),
            'paid_amount' => $this->dec($i->paid_amount), 'outstanding' => $outstanding, 'display_status' => $overdue ? 'overdue' : $i->status,
            'items' => H::parseJson($i->items, []),
        ];
    }

    private function invoice(Ctx $ctx, string $id): object
    {
        $i = DB::table('client_invoices')->where('id', $id)->where('tenant_id', $ctx->tenantId)->first();
        if (!$i) throw new ApiException(404, 'Счёт не найден', 'not_found');
        return $i;
    }

    // ── invoices ─────────────────────────────────────────────────────────────
    public function listInvoices(Ctx $ctx)
    {
        $ctx->auth(); H::requireTenant($ctx); H::requirePermission($ctx, 'finance.read');
        $t = $ctx->tenantId;
        [$limit, $page, $offset] = H::paginate($ctx);
        $where = ['i.tenant_id = ?']; $binds = [$t];
        if ($s = $ctx->query('status')) {
            if ($s === 'overdue') { $where[] = "i.status IN ('sent','partial') AND i.due_at < ?"; $binds[] = gmdate('Y-m-d'); }
            else { $where[] = 'i.status = ?'; $binds[] = (string) $s; }
        }
        if ($q = trim((string) $ctx->query('q'))) { $where[] = '(i.number LIKE ? OR o.name LIKE ?)'; array_push($binds, H::like($q), H::like($q)); }
        $w = implode(' AND ', $where);
        $rows = DB::select("SELECT i.*, o.name AS org_name FROM client_invoices i LEFT JOIN organizations o ON o.id = i.org_id WHERE {$w} ORDER BY i.created_at DESC LIMIT ? OFFSET ?", [...$binds, $limit, $offset]);
        $total = (int) (DB::selectOne("SELECT COUNT(*) AS c FROM client_invoices i LEFT JOIN organizations o ON o.id = i.org_id WHERE {$w}", $binds)->c ?? 0);
        $today = gmdate('Y-m-d');
        $stats = DB::selectOne(
            "SELECT COALESCE(SUM(CASE WHEN status IN ('sent','partial') THEN total - paid_amount END),0) AS outstanding,
                    COALESCE(SUM(CASE WHEN status IN ('sent','partial') AND due_at < ? THEN total - paid_amount END),0) AS overdue,
                    COALESCE(SUM(CASE WHEN status IN ('sent','partial') AND due_at < ? THEN 1 END),0) AS overdue_count,
                    COALESCE(SUM(CASE WHEN status = 'draft' THEN 1 END),0) AS drafts
               FROM client_invoices WHERE tenant_id = ?",
            [$today, $today, $t]
        );
        $paidMonth = (float) DB::table('client_payments')->where('tenant_id', $t)->where('paid_at', '>=', gmdate('Y-m-01'))->sum('amount');
        return H::json([
            'data' => array_map(fn ($r) => [...$this->view($r), 'org_name' => $r->org_name], $rows), 'total' => $total, 'page' => $page, 'limit' => $limit,
            'stats' => ['outstanding' => (float) $stats->outstanding, 'overdue' => (float) $stats->overdue, 'overdue_count' => (int) $stats->overdue_count, 'drafts' => (int) $stats->drafts, 'paid_this_month' => $paidMonth],
        ]);
    }

    public function getInvoice(Ctx $ctx)
    {
        $ctx->auth(); H::requireTenant($ctx); H::requirePermission($ctx, 'finance.read');
        $i = $this->invoice($ctx, (string) $ctx->params['id']);
        $org = $i->org_id ? DB::table('organizations')->where('id', $i->org_id)->where('tenant_id', $ctx->tenantId)->first(['id', 'name', 'email']) : null;
        $payments = DB::table('client_payments')->where('tenant_id', $ctx->tenantId)->where('invoice_id', $i->id)->orderByDesc('paid_at')->get();
        return H::json([...$this->view($i), 'org' => $org, 'payments' => $payments]);
    }

    /** @return array{0:array,1:float,2:float,3:float} [items, subtotal, tax, total] */
    private function computeItems(mixed $raw, float $taxRate): array
    {
        if (!is_array($raw) || count($raw) < 1 || count($raw) > 50) throw new ApiException(400, 'Добавьте от 1 до 50 позиций', 'bad_request');
        $items = []; $subtotal = 0.0;
        foreach ($raw as $it) {
            $name = H::str($it['name'] ?? null, 'name', true, 200);
            $qty = (float) H::num($it['qty'] ?? 1, 'qty', true);
            $price = (float) H::num($it['unit_price'] ?? null, 'unit_price', true);
            if ($qty <= 0 || $qty > 1_000_000) throw new ApiException(400, 'Количество должно быть больше нуля', 'bad_request');
            if ($price < 0 || $price > 1e12) throw new ApiException(400, 'Цена не может быть отрицательной', 'bad_request');
            $line = round($qty * $price, 2);
            $items[] = ['name' => $name, 'qty' => $qty, 'unit_price' => round($price, 2), 'total' => $line];
            $subtotal += $line;
        }
        $subtotal = round($subtotal, 2);
        $tax = round($subtotal * $taxRate / 100, 2);
        return [$items, $subtotal, $tax, round($subtotal + $tax, 2)];
    }

    private function nextNumber(string $tenantId): string
    {
        $year = gmdate('Y');
        $last = DB::table('client_invoices')->where('tenant_id', $tenantId)->where('number', 'like', "INV-{$year}-%")->orderByDesc('number')->value('number');
        $seq = $last ? ((int) substr((string) $last, -5)) + 1 : 1;
        return sprintf('INV-%s-%05d', $year, $seq);
    }

    public function createInvoice(Ctx $ctx)
    {
        $user = $ctx->auth(); H::requireTenant($ctx); H::requirePermission($ctx, 'finance.write');
        $t = $ctx->tenantId;
        $b = H::body($ctx);
        $orgId = H::tenantRef($ctx, $b['org_id'] ?? null, 'organizations', 'kompaniya');
        $orderId = H::tenantRef($ctx, $b['order_id'] ?? null, 'orders', 'buyurtma');
        $currency = strtoupper(H::str($b['currency'] ?? null, 'currency', false, 8) ?? 'UZS');
        $items = $b['items'] ?? null;
        if ($orderId) {
            $order = DB::table('orders')->where('id', $orderId)->where('tenant_id', $t)->first();
            if (DB::table('client_invoices')->where('tenant_id', $t)->where('order_id', $orderId)->where('status', '!=', 'void')->exists()) {
                throw new ApiException(409, 'По этому заказу счёт уже выставлен', 'conflict');
            }
            $orgId = $orgId ?? $order->org_id; $currency = $order->currency ?: $currency;
            if (empty($items)) $items = DB::table('order_items')->where('order_id', $orderId)->get()->map(fn ($r) => ['name' => $r->name, 'qty' => $r->qty, 'unit_price' => $r->price])->all();
        }
        $taxRate = (float) (H::num($b['tax_rate'] ?? 0, 'tax_rate', false) ?? 0);
        if ($taxRate < 0 || $taxRate > 100) throw new ApiException(400, 'Ставка налога: от 0 до 100%', 'bad_request');
        [$lines, $subtotal, $tax, $total] = $this->computeItems($items, $taxRate);
        $due = H::str($b['due_at'] ?? null, 'due_at', false, 10);
        if ($due !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $due)) throw new ApiException(400, 'Срок оплаты в формате ГГГГ-ММ-ДД', 'bad_request');

        $id = H::newId('cin');
        for ($try = 0; ; $try++) {   // the unique (tenant, number) index guards against two requests taking the same number
            try {
                DB::table('client_invoices')->insert([
                    'id' => $id, 'tenant_id' => $t, 'number' => $this->nextNumber($t), 'org_id' => $orgId, 'order_id' => $orderId, 'status' => 'draft', 'currency' => $currency,
                    'subtotal' => $subtotal, 'tax_rate' => $taxRate, 'tax' => $tax, 'total' => $total, 'paid_amount' => 0, 'items' => json_encode($lines, JSON_UNESCAPED_UNICODE),
                    'notes' => H::str($b['notes'] ?? null, 'notes', false, 2000), 'due_at' => $due, 'created_by' => $user['id'], 'created_at' => H::now(), 'updated_at' => H::now(),
                ]);
                break;
            } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                if ($try >= 3) throw $e;
            }
        }
        H::audit($ctx, 'client_invoice.created', 'client_invoice', $id, ['total' => $total]);
        return H::json(['id' => $id], 201);
    }

    public function sendInvoice(Ctx $ctx)
    {
        $ctx->auth(); H::requireTenant($ctx); H::requirePermission($ctx, 'finance.write');
        $i = $this->invoice($ctx, (string) $ctx->params['id']);
        if ($i->status !== 'draft') throw new ApiException(409, 'Отправить можно только черновик', 'conflict');
        $due = $i->due_at ?: gmdate('Y-m-d', time() + 14 * 86400);
        DB::table('client_invoices')->where('id', $i->id)->update(['status' => 'sent', 'issued_at' => H::now(), 'due_at' => $due, 'updated_at' => H::now()]);
        H::audit($ctx, 'client_invoice.sent', 'client_invoice', $i->id);
        return H::json(['ok' => true]);
    }

    public function voidInvoice(Ctx $ctx)
    {
        $ctx->auth(); H::requireTenant($ctx); H::requirePermission($ctx, 'finance.write');
        $i = $this->invoice($ctx, (string) $ctx->params['id']);
        if ($i->status === 'void') return H::json(['ok' => true]);
        if ($this->dec($i->paid_amount) > 0) throw new ApiException(409, 'Счёт с платежами аннулировать нельзя', 'conflict');
        DB::table('client_invoices')->where('id', $i->id)->update(['status' => 'void', 'updated_at' => H::now()]);
        H::audit($ctx, 'client_invoice.voided', 'client_invoice', $i->id);
        return H::json(['ok' => true]);
    }

    public function deleteInvoice(Ctx $ctx)
    {
        $ctx->auth(); H::requireTenant($ctx); H::requirePermission($ctx, 'finance.write');
        $i = $this->invoice($ctx, (string) $ctx->params['id']);
        if ($i->status !== 'draft') throw new ApiException(409, 'Удалить можно только черновик; выставленный счёт аннулируется', 'conflict');
        DB::table('client_invoices')->where('id', $i->id)->delete();
        H::audit($ctx, 'client_invoice.deleted', 'client_invoice', $i->id);
        return H::json(['ok' => true]);
    }

    // ── payments ─────────────────────────────────────────────────────────────
    public function addPayment(Ctx $ctx)
    {
        $user = $ctx->auth(); H::requireTenant($ctx); H::requirePermission($ctx, 'finance.write');
        $b = H::body($ctx);
        $amount = $this->dec(H::num($b['amount'] ?? null, 'amount', true));
        $method = H::oneOf($b['method'] ?? null, 'method', self::PAY_METHODS, 'bank');
        $date = H::str($b['paid_at'] ?? null, 'paid_at', false, 10) ?? gmdate('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $date > gmdate('Y-m-d', time() + 86400)) throw new ApiException(400, 'Некорректная дата платежа', 'bad_request');
        if ($amount <= 0) throw new ApiException(400, 'Сумма платежа должна быть больше нуля', 'bad_request');

        $paymentId = H::newId('cpay');
        $result = DB::transaction(function () use ($ctx, $user, $amount, $method, $date, $b, $paymentId) {
            $i = DB::table('client_invoices')->where('id', $ctx->params['id'])->where('tenant_id', $ctx->tenantId)->lockForUpdate()->first();
            if (!$i) throw new ApiException(404, 'Счёт не найден', 'not_found');
            if (!in_array($i->status, ['sent', 'partial'], true)) throw new ApiException(409, 'Принимать оплату можно только по выставленному неоплаченному счёту', 'conflict');
            $outstanding = $this->dec($i->total) - $this->dec($i->paid_amount);
            if ($amount - $outstanding > 0.001) throw new ApiException(422, 'Сумма больше остатка к оплате (' . number_format($outstanding, 2, '.', ' ') . ')', 'overpayment');
            DB::table('client_payments')->insert([
                'id' => $paymentId, 'tenant_id' => $ctx->tenantId, 'invoice_id' => $i->id, 'amount' => $amount, 'method' => $method,
                'reference' => H::str($b['reference'] ?? null, 'reference', false, 120), 'paid_at' => $date, 'created_by' => $user['id'], 'created_at' => H::now(),
            ]);
            $paid = round($this->dec($i->paid_amount) + $amount, 2);
            $full = $paid + 0.001 >= $this->dec($i->total);
            DB::table('client_invoices')->where('id', $i->id)->update(['paid_amount' => $paid, 'status' => $full ? 'paid' : 'partial', 'paid_at' => $full ? H::now() : null, 'updated_at' => H::now()]);
            if ($i->order_id) DB::table('orders')->where('id', $i->order_id)->where('tenant_id', $ctx->tenantId)->update(['payment_status' => $full ? 'paid' : 'partial', 'updated_at' => H::now()]);
            return ['status' => $full ? 'paid' : 'partial', 'paid_amount' => $paid, 'invoice_id' => $i->id, 'number' => $i->number, 'total' => $this->dec($i->total), 'currency' => $i->currency];
        });
        H::audit($ctx, 'client_payment.recorded', 'client_invoice', $result['invoice_id'], ['amount' => $amount, 'method' => $method]);
        if ($result['status'] === 'paid') WebhookService::dispatch((string) $ctx->tenantId, 'invoice.paid', ['invoice_id' => $result['invoice_id'], 'number' => $result['number'], 'total' => $result['total'], 'currency' => $result['currency']]);
        return H::json(['id' => $paymentId, 'status' => $result['status'], 'paid_amount' => $result['paid_amount']], 201);
    }

    public function listPayments(Ctx $ctx)
    {
        $ctx->auth(); H::requireTenant($ctx); H::requirePermission($ctx, 'finance.read');
        $t = $ctx->tenantId;
        [$limit, $page, $offset] = H::paginate($ctx);
        $where = ['p.tenant_id = ?']; $binds = [$t];
        if ($m = $ctx->query('method')) { $where[] = 'p.method = ?'; $binds[] = (string) $m; }
        if ($f = $ctx->query('from')) { $where[] = 'p.paid_at >= ?'; $binds[] = (string) $f; }
        if ($to = $ctx->query('to')) { $where[] = 'p.paid_at <= ?'; $binds[] = (string) $to; }
        $w = implode(' AND ', $where);
        $rows = DB::select("SELECT p.id, p.amount, p.method, p.reference, p.paid_at, i.id AS invoice_id, i.number AS invoice_number, i.currency, o.name AS org_name
                              FROM client_payments p JOIN client_invoices i ON i.id = p.invoice_id LEFT JOIN organizations o ON o.id = i.org_id
                             WHERE {$w} ORDER BY p.paid_at DESC, p.created_at DESC LIMIT ? OFFSET ?", [...$binds, $limit, $offset]);
        $total = (int) (DB::selectOne("SELECT COUNT(*) AS c FROM client_payments p WHERE {$w}", $binds)->c ?? 0);
        $sums = DB::select("SELECT i.currency, COALESCE(SUM(p.amount),0) AS amount FROM client_payments p JOIN client_invoices i ON i.id = p.invoice_id WHERE {$w} GROUP BY i.currency", $binds);
        return H::json(['data' => array_map(fn ($r) => [...(array) $r, 'amount' => (float) $r->amount], $rows), 'total' => $total, 'page' => $page, 'limit' => $limit, 'sums' => $sums]);
    }

    // ── expenses ─────────────────────────────────────────────────────────────
    public function listExpenses(Ctx $ctx)
    {
        $ctx->auth(); H::requireTenant($ctx); H::requirePermission($ctx, 'finance.read');
        $t = $ctx->tenantId;
        [$limit, $page, $offset] = H::paginate($ctx);
        $where = ['tenant_id = ?']; $binds = [$t];
        if ($c = $ctx->query('category')) { $where[] = 'category = ?'; $binds[] = (string) $c; }
        if ($f = $ctx->query('from')) { $where[] = 'expense_date >= ?'; $binds[] = (string) $f; }
        if ($to = $ctx->query('to')) { $where[] = 'expense_date <= ?'; $binds[] = (string) $to; }
        if ($q = trim((string) $ctx->query('q'))) { $where[] = '(description LIKE ? OR vendor LIKE ?)'; array_push($binds, H::like($q), H::like($q)); }
        $w = implode(' AND ', $where);
        $rows = DB::select("SELECT * FROM expenses WHERE {$w} ORDER BY expense_date DESC, created_at DESC LIMIT ? OFFSET ?", [...$binds, $limit, $offset]);
        $total = (int) (DB::selectOne("SELECT COUNT(*) AS c FROM expenses WHERE {$w}", $binds)->c ?? 0);
        $byCat = DB::select("SELECT category, currency, COALESCE(SUM(amount),0) AS amount FROM expenses WHERE {$w} GROUP BY category, currency ORDER BY amount DESC", $binds);
        $sum = DB::select("SELECT currency, COALESCE(SUM(amount),0) AS amount FROM expenses WHERE {$w} GROUP BY currency", $binds);
        return H::json(['data' => array_map(fn ($r) => [...(array) $r, 'amount' => (float) $r->amount], $rows), 'total' => $total, 'page' => $page, 'limit' => $limit,
            'by_category' => array_map(fn ($r) => [...(array) $r, 'amount' => (float) $r->amount], $byCat), 'sums' => $sum, 'categories' => self::EXPENSE_CATEGORIES]);
    }

    private function expenseFields(array $b, bool $partial = false): array
    {
        $out = [];
        if (!$partial || array_key_exists('description', $b)) $out['description'] = H::str($b['description'] ?? null, 'description', true, 300);
        if (!$partial || array_key_exists('amount', $b)) {
            $a = (float) H::num($b['amount'] ?? null, 'amount', true);
            if ($a <= 0 || $a > 1e12) throw new ApiException(400, 'Сумма должна быть больше нуля', 'bad_request');
            $out['amount'] = round($a, 2);
        }
        if (!$partial || array_key_exists('category', $b)) $out['category'] = H::oneOf($b['category'] ?? null, 'category', self::EXPENSE_CATEGORIES, 'other');
        if (!$partial || array_key_exists('expense_date', $b)) {
            $d = H::str($b['expense_date'] ?? null, 'expense_date', false, 10) ?? gmdate('Y-m-d');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) throw new ApiException(400, 'Дата в формате ГГГГ-ММ-ДД', 'bad_request');
            $out['expense_date'] = $d;
        }
        if (!$partial || array_key_exists('vendor', $b)) $out['vendor'] = H::str($b['vendor'] ?? null, 'vendor', false, 160);
        if (!$partial || array_key_exists('currency', $b)) $out['currency'] = strtoupper(H::str($b['currency'] ?? null, 'currency', false, 8) ?? 'UZS');
        return $out;
    }

    public function createExpense(Ctx $ctx)
    {
        $user = $ctx->auth(); H::requireTenant($ctx); H::requirePermission($ctx, 'finance.write');
        $b = H::body($ctx);
        $id = H::newId('exp');
        DB::table('expenses')->insert([...$this->expenseFields($b), 'id' => $id, 'tenant_id' => $ctx->tenantId, 'project_id' => H::tenantRef($ctx, $b['project_id'] ?? null, 'projects', 'loyiha'),
            'created_by' => $user['id'], 'created_at' => H::now()]);
        H::audit($ctx, 'expense.created', 'expense', $id);
        return H::json(['id' => $id], 201);
    }

    public function updateExpense(Ctx $ctx)
    {
        $ctx->auth(); H::requireTenant($ctx); H::requirePermission($ctx, 'finance.write');
        $e = DB::table('expenses')->where('id', $ctx->params['id'])->where('tenant_id', $ctx->tenantId)->first();
        if (!$e) throw new ApiException(404, 'Расход не найден', 'not_found');
        $upd = $this->expenseFields(H::body($ctx), true);
        if ($upd) DB::table('expenses')->where('id', $e->id)->update($upd);
        H::audit($ctx, 'expense.updated', 'expense', $e->id);
        return H::json(['ok' => true]);
    }

    public function deleteExpense(Ctx $ctx)
    {
        $ctx->auth(); H::requireTenant($ctx); H::requirePermission($ctx, 'finance.write');
        $n = DB::table('expenses')->where('id', $ctx->params['id'])->where('tenant_id', $ctx->tenantId)->delete();
        if (!$n) throw new ApiException(404, 'Расход не найден', 'not_found');
        H::audit($ctx, 'expense.deleted', 'expense', (string) $ctx->params['id']);
        return H::json(['ok' => true]);
    }
}
