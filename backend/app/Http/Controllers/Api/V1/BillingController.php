<?php
namespace App\Http\Controllers\Api\V1;

use App\Support\ApiException;
use App\Support\Ctx;
use App\Support\H;
use App\Support\SqlDialect;
use Illuminate\Support\Facades\DB;
use App\Services\CbuExchangeRateService;
use App\Services\SubscriptionEntitlementService;

/**
 * Nexio Billing + Finance. Plan changes are computed server-side with proration;
 * entitlements are re-derived from the plan, never from what the client claims to have bought.
 */
class BillingController
{
    /* Legacy virtual-wallet implementation removed from routing and database. */
    /*
        if ($amount < 1000) throw new ApiException(422, 'Minimal wallet to‘ldirish summasi 1000 UZS', 'invalid_amount');
        $invoiceId = H::newId('inv');
        $number = 'WAL-' . gmdate('Y') . '-' . strtoupper(substr($invoiceId, -6));
        DB::table('invoices')->insert([
            'id' => $invoiceId, 'tenant_id' => $ctx->tenantId, 'number' => $number,
            'amount' => $amount, 'tax' => 0, 'total' => $amount, 'currency' => 'UZS',
            'status' => 'open', 'line_items' => json_encode([['kind' => 'wallet_topup', 'label' => 'Virtual wallet top-up', 'qty' => 1, 'amount' => $amount]]),
            'issued_at' => H::now(), 'due_at' => gmdate('Y-m-d H:i:s', time() + 86400), 'created_at' => H::now(),
        ]);
        $provider = config('payments.provider', 'sandbox');
        if ($provider !== 'click') return H::json(['invoice_id' => $invoiceId, 'provider' => $provider, 'payment_url' => null]);
        $click = config('payments.click');
        foreach (['merchant_id', 'service_id', 'return_url'] as $key) {
            if (empty($click[$key])) throw new ApiException(503, 'Click konfiguratsiyasi to‘liq emas', 'payment_not_configured');
        }
        $url = 'https://my.click.uz/services/pay?' . http_build_query([
            'service_id' => $click['service_id'], 'merchant_id' => $click['merchant_id'],
            'amount' => number_format($amount, 2, '.', ''), 'transaction_param' => $invoiceId,
            'return_url' => $click['return_url'],
        ]);
        return H::json(['invoice_id' => $invoiceId, 'provider' => 'click', 'payment_url' => $url], 201);
    }
    */
    private function applyEntitlements(string $tenantId, string $planCode): void
    {
        (new SubscriptionEntitlementService())->apply($tenantId, $planCode);
    }

    /** Public catalogue used by /pricing and the in-app billing screen. */
    public function plans(Ctx $ctx)
    {
        $plans = DB::select('SELECT * FROM plans WHERE is_public = 1 ORDER BY position');
        $features = DB::select('SELECT * FROM plan_features ORDER BY position');
        return H::json(['data' => array_map(fn ($p) => [
            ...(array) $p,
            'modules' => H::parseJson($p->modules, []),
            'features' => array_values(array_map(
                fn ($f) => ['label' => $f->label, 'included' => (bool) $f->included],
                array_filter($features, fn ($f) => $f->plan_id === $p->id)
            )),
        ], $plans)]);
    }

    public function subscription(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'billing.read');
        $t = $ctx->tenantId;
        $sub = DB::selectOne(
            'SELECT s.*, p.name AS plan_name, p.code AS plan_code, p.seats AS plan_seats, p.api_requests, p.ai_minutes,
                    p.storage_gb, p.modules, p.tagline
               FROM subscriptions s JOIN plans p ON p.id = s.plan_id
              WHERE s.tenant_id = ? ORDER BY s.created_at DESC LIMIT 1',
            [$t]
        );
        $invoices = DB::select('SELECT * FROM invoices WHERE tenant_id = ? ORDER BY issued_at DESC LIMIT 24', [$t]);
        $payments = DB::select('SELECT * FROM payments WHERE tenant_id = ? ORDER BY created_at DESC LIMIT 12', [$t]);
        $usage = DB::select('SELECT metric, quantity, limit_value, period FROM usage_records WHERE tenant_id = ? ORDER BY recorded_at DESC', [$t]);
        $tenant = DB::selectOne('SELECT name FROM tenants WHERE id = ?', [$t]);
        $unpaid = DB::selectOne("SELECT COALESCE(SUM(total),0) AS v, COUNT(*) AS c FROM invoices WHERE tenant_id = ? AND status IN ('open','overdue')", [$t]);
        $yearStart = gmdate('Y-01-01 00:00:00');
        $nextYearStart = gmdate('Y-01-01 00:00:00', strtotime('+1 year'));
        $paidYtd = DB::selectOne("SELECT COALESCE(SUM(total),0) AS v FROM invoices WHERE tenant_id = ? AND status='paid' AND issued_at >= ? AND issued_at < ?", [$t, $yearStart, $nextYearStart]);
        $seatUsage = DB::selectOne("SELECT COUNT(*) AS c FROM tenant_memberships WHERE tenant_id = ? AND status='active'", [$t]);

        $latestUsage = function (string $metric) use ($usage) {
            foreach ($usage as $u) { if ($u->metric === $metric) return $u; }
            return null;
        };
        return H::json([
            'paymentProvider' => config('payments.provider', 'sandbox'),
            'subscription' => $sub ? [...(array) $sub, 'modules' => H::parseJson($sub->modules, []), 'cancel_at_period_end' => (bool) $sub->cancel_at_period_end] : null,
            'company' => $tenant->name ?? null,
            'invoices' => array_map(fn ($i) => $this->invoiceView($i), $invoices),
            'payments' => $payments,
            'usage' => [
                'seats' => ['used' => (int) ($seatUsage->c ?? 0), 'limit' => $sub->plan_seats ?? 5],
                'api' => $latestUsage('api_requests') ?? ['quantity' => 0, 'limit_value' => $sub->api_requests ?? 0],
                'ai_minutes' => $latestUsage('ai_minutes') ?? ['quantity' => 0, 'limit_value' => $sub->ai_minutes ?? 0],
                'storage' => $latestUsage('storage_gb') ?? ['quantity' => 0, 'limit_value' => $sub->storage_gb ?? 0],
            ],
            'totals' => ['unpaid' => $unpaid->v ?? 0, 'unpaidInvoices' => (int) ($unpaid->c ?? 0), 'paidYtd' => $paidYtd->v ?? 0],
        ]);
    }

    /**
     * Plan change. Sandbox provider: no card is charged, proration is computed and
     * recorded as an invoice line so the numbers stay consistent with a real provider.
     */
    public function changePlan(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'billing.write');
        $t = $ctx->tenantId;
        $b = H::body($ctx);
        $code = H::oneOf($b['plan'] ?? null, 'plan', ['starter', 'business', 'enterprise']);
        $plan = DB::selectOne('SELECT * FROM plans WHERE code = ?', [$code]);
        if (!$plan) throw new ApiException(404, 'Тариф не найден', 'not_found');
        $current = DB::selectOne(
            'SELECT s.*, p.price_monthly AS current_price, p.code AS current_code
               FROM subscriptions s JOIN plans p ON p.id = s.plan_id WHERE s.tenant_id = ? ORDER BY s.created_at DESC LIMIT 1',
            [$t]
        );
        $interval = ($b['interval'] ?? null) === 'year' ? 'year' : 'month';
        $price = $interval === 'year' ? $plan->price_yearly : $plan->price_monthly;
        $periodEnd = gmdate('Y-m-d H:i:s', time() + ($interval === 'year' ? 365 : 30) * 86400);

        // Proration: unused part of the current period is credited, the remainder is charged.
        $proration = 0;
        if ($current && $current->period_end) {
            $end = strtotime($current->period_end . ' UTC');
            $start = strtotime($current->period_start . ' UTC');
            $total = max(1, $end - $start);
            $remaining = max(0, $end - time());
            $ratio = $remaining / $total;
            $proration = round((($price - $current->current_price) * $ratio) * 100) / 100;
        }

        $invoiceId = H::newId('inv');
        $year = (int) gmdate('Y');
        $invoiceNumber = 'INV-' . $year . '-' . strtoupper(substr(H::newId('n'), -5));
        $lineItems = [
            ['label' => $plan->name . ' · ' . ($interval === 'year' ? 'годовая' : 'месячная') . ' подписка', 'qty' => 1, 'amount' => $price],
        ];
        if ($proration !== 0) {
            $lineItems[] = ['label' => 'Пропорциональный пересчёт', 'qty' => 1, 'amount' => $proration];
        }
        $total = round(($price + ($proration > 0 ? $proration : 0)) * 100) / 100;
        $clickQuote = null;
        if (config('payments.provider', 'sandbox') === 'click') {
            $clickQuote = (new CbuExchangeRateService())->quote($total, (string) $plan->currency);
        }

        // Click confirms the invoice asynchronously. Do not activate an unpaid
        // plan before the merchant webhook arrives.
        if (config('payments.provider', 'sandbox') === 'click') {
            DB::table('invoices')->insert([
                'id' => $invoiceId, 'tenant_id' => $t, 'number' => $invoiceNumber,
                'amount' => $price, 'tax' => 0, 'total' => $total, 'currency' => $plan->currency,
                'status' => 'open', 'line_items' => json_encode(array_merge($lineItems, $clickQuote ? [['kind' => 'click_conversion', 'amount' => 0, 'click_quote' => $clickQuote]] : [], [[
                    'kind' => 'subscription_plan', 'plan_code' => $code, 'interval' => $interval,
                ]]), JSON_UNESCAPED_UNICODE), 'issued_at' => H::now(),
                'due_at' => gmdate('Y-m-d H:i:s', time() + 86400), 'created_at' => H::now(),
            ]);
            $this->assertClickConfigured();
            return H::json([
                'ok' => true, 'invoiceId' => $invoiceId, 'invoiceNumber' => $invoiceNumber,
                'provider' => 'click', 'payment_url' => $this->clickPaymentUrl($invoiceId, (float) $clickQuote['target_amount']),
                'click_amount_uzs' => $clickQuote['target_amount'], 'exchange_rate' => $clickQuote['rate'], 'exchange_rate_date' => $clickQuote['rate_date'],
                'note' => 'Click to‘lovi tasdiqlangach tarif avtomatik faollashadi.',
            ], 201);
        }

        DB::transaction(function () use ($t, $plan, $price, $interval, $periodEnd, $invoiceId, $invoiceNumber, $lineItems, $total) {
            DB::update("UPDATE subscriptions SET status='cancelled', cancelled_at=?, cancel_at_period_end=0 WHERE tenant_id=? AND status IN ('active','trialing','grace','past_due')", [H::now(), $t]);
            DB::table('subscriptions')->insert([
                'id' => H::newId('sub'), 'tenant_id' => $t, 'plan_id' => $plan->id, 'status' => 'active',
                'seats' => $plan->seats, 'amount' => $price, 'currency' => $plan->currency,
                'interval' => $interval, 'period_start' => H::now(), 'period_end' => $periodEnd, 'created_at' => H::now(),
            ]);
            DB::table('invoices')->insert([
                'id' => $invoiceId, 'tenant_id' => $t, 'number' => $invoiceNumber,
                'amount' => $price, 'tax' => 0, 'total' => $total, 'currency' => $plan->currency,
                'status' => 'open', 'line_items' => json_encode($lineItems, JSON_UNESCAPED_UNICODE),
                'issued_at' => H::now(), 'due_at' => gmdate('Y-m-d H:i:s', time() + 7 * 86400), 'created_at' => H::now(),
            ]);
            DB::update("UPDATE tenants SET plan_id = ?, status='active', updated_at = ? WHERE id = ?", [$plan->id, H::now(), $t]);
        });
        $this->applyEntitlements($t, $code);
        H::audit($ctx, 'billing.plan_changed', 'subscription', $t, ['from' => $current->current_code ?? null, 'to' => $code, 'interval' => $interval, 'proration' => $proration]);
        return H::json([
            'ok' => true, 'invoiceId' => $invoiceId, 'invoiceNumber' => $invoiceNumber,
            'proration' => $proration, 'plan' => $plan->name, 'interval' => $interval,
            'sandbox' => config('payments.provider', 'sandbox') === 'sandbox',
            'provider' => config('payments.provider', 'sandbox'),
            'note' => config('payments.provider', 'sandbox') === 'sandbox' ? 'Payment provider sozlanmagan.' : 'Payment provider konfiguratsiyasi mavjud.',
        ]);
    }

    public function cancel(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'billing.write');
        $t = $ctx->tenantId;
        $b = H::body($ctx);
        $immediate = !empty($b['immediate']);
        $sub = DB::selectOne("SELECT id FROM subscriptions WHERE tenant_id = ? AND status IN ('active','trialing','grace','past_due') ORDER BY created_at DESC LIMIT 1", [$t]);
        if (!$sub) throw new ApiException(404, 'Активная подписка не найдена', 'not_found');
        if ($immediate) {
            DB::transaction(function () use ($sub, $t) {
                DB::update("UPDATE subscriptions SET status='cancelled', cancelled_at=?, cancel_at_period_end=0 WHERE id=?", [H::now(), $sub->id]);
                DB::update("UPDATE tenants SET status='cancelled', updated_at=? WHERE id=?", [H::now(), $t]);
            });
        } else {
            DB::update('UPDATE subscriptions SET cancel_at_period_end=1 WHERE id=?', [$sub->id]);
        }
        H::audit($ctx, 'billing.cancelled', 'subscription', $sub->id, ['immediate' => $immediate]);
        return H::json(['ok' => true, 'immediate' => $immediate, 'note' => 'Данные сохраняются согласно политике хранения; доступ прекращается после окончания периода.']);
    }

    public function resume(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'billing.write');
        $t = $ctx->tenantId;
        $sub = DB::selectOne('SELECT id FROM subscriptions WHERE tenant_id = ? AND cancel_at_period_end = 1 ORDER BY created_at DESC LIMIT 1', [$t]);
        if (!$sub) throw new ApiException(404, 'Подписка с запланированной отменой не найдена', 'not_found');
        DB::update('UPDATE subscriptions SET cancel_at_period_end = 0 WHERE id = ?', [$sub->id]);
        H::audit($ctx, 'billing.resumed', 'subscription', $sub->id);
        return H::json(['ok' => true]);
    }

    /** Sandbox payment for an open invoice, idempotent by key. */
    public function payInvoice(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'billing.write');
        $t = $ctx->tenantId;
        $invoice = DB::selectOne('SELECT * FROM invoices WHERE id = ? AND tenant_id = ?', [$ctx->params['id'], $t]);
        if (!$invoice) throw new ApiException(404, 'Счёт не найден', 'not_found');
        if ($invoice->status === 'paid') return H::json(['ok' => true, 'alreadyPaid' => true]);
        if (config('payments.provider') === 'click') {
            $this->assertClickConfigured();
            $quote = $this->clickQuoteForInvoice($invoice);
            return H::json(['ok' => true, 'provider' => 'click', 'payment_url' => $this->clickPaymentUrl($invoice->id, (float) $quote['target_amount']), 'click_amount_uzs' => $quote['target_amount'], 'exchange_rate' => $quote['rate'], 'exchange_rate_date' => $quote['rate_date']]);
        }
        $b = H::body($ctx);
        $key = H::str($b['idempotency_key'] ?? null, 'idempotency_key', false, 120);
        if ($key && DB::selectOne('SELECT id FROM payments WHERE idempotency_key = ?', [$key])) {
            return H::json(['ok' => true, 'duplicate' => true, 'message' => 'Платёж уже был обработан (идемпотентность)']);
        }
        DB::transaction(function () use ($t, $invoice, $key, $ctx) {
            DB::update("UPDATE invoices SET status='paid', paid_at=? WHERE id=? AND tenant_id=?", [H::now(), $ctx->params['id'], $t]);
            DB::table('payments')->insert([
                'id' => H::newId('pay'), 'tenant_id' => $t, 'invoice_id' => $ctx->params['id'],
                'provider' => config('payments.provider', 'sandbox'), 'provider_ref' => H::newId('sbx'), 'amount' => $invoice->total,
                'currency' => $invoice->currency, 'status' => 'succeeded', 'idempotency_key' => $key, 'created_at' => H::now(),
            ]);
            DB::table('billing_events')->insert([
                'id' => H::newId('bev'), 'tenant_id' => $t, 'kind' => 'invoice.paid',
                'detail' => $invoice->number . ' · ' . config('payments.provider', 'sandbox'), 'created_at' => H::now(),
            ]);
        });
        H::audit($ctx, 'invoice.paid', 'invoice', $ctx->params['id'], ['amount' => $invoice->total]);
        return H::json(['ok' => true, 'sandbox' => config('payments.provider', 'sandbox') === 'sandbox', 'provider' => config('payments.provider', 'sandbox')]);
    }

    /** Finance module: revenue, receivables, expenses, product margins. */
    public function finance(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'finance.read');
        $t = $ctx->tenantId;
        $revenue = DB::selectOne("SELECT COALESCE(SUM(amount),0) AS v, COUNT(*) AS c FROM orders WHERE tenant_id = ? AND status='fulfilled'", [$t]);
        $receivable = DB::selectOne("SELECT COALESCE(SUM(amount),0) AS v, COUNT(*) AS c FROM orders WHERE tenant_id = ? AND payment_status IN ('unpaid','overdue','partial') AND status NOT IN ('cancelled','refunded')", [$t]);
        $paid = DB::selectOne("SELECT COALESCE(SUM(amount),0) AS v FROM orders WHERE tenant_id = ? AND payment_status='paid' AND status='fulfilled'", [$t]);
        $planned = DB::selectOne("SELECT COALESCE(SUM(amount),0) AS v FROM orders WHERE tenant_id = ? AND status IN ('confirmed','in_progress')", [$t]);
        $byMonth = DB::select("SELECT " . SqlDialect::month('created_at') . " AS ym, COALESCE(SUM(amount),0) AS value, COUNT(*) AS orders FROM orders WHERE tenant_id = ? AND created_at >= " . SqlDialect::ago(12, 'month') . " GROUP BY ym ORDER BY ym", [$t]);
        $margins = DB::select(
            "SELECT oi.name, SUM(oi.qty) AS qty, SUM(oi.total) AS revenue,
                    SUM(oi.qty * COALESCE(p.cost,0)) AS cost,
                    SUM(oi.total) - SUM(oi.qty * COALESCE(p.cost,0)) AS margin
               FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id
               JOIN orders o ON o.id = oi.order_id
              WHERE oi.tenant_id = ? AND o.status = 'fulfilled'
              GROUP BY oi.name ORDER BY revenue DESC LIMIT 10",
            [$t]
        );
        $topClients = DB::select(
            "SELECT org.name, COUNT(o.id) AS orders, COALESCE(SUM(o.amount),0) AS revenue
               FROM orders o JOIN organizations org ON org.id = o.org_id
              WHERE o.tenant_id = ? AND o.status='fulfilled' GROUP BY org.id ORDER BY revenue DESC LIMIT 8",
            [$t]
        );
        $overdue = DB::select(
            "SELECT o.number, o.amount, o.currency, o.created_at, org.name AS org_name
               FROM orders o LEFT JOIN organizations org ON org.id=o.org_id
              WHERE o.tenant_id = ? AND o.payment_status IN ('unpaid','overdue') AND o.status NOT IN ('cancelled','refunded')
              ORDER BY o.created_at LIMIT 10",
            [$t]
        );
        $expenses = DB::selectOne(
            "SELECT COALESCE(SUM(oi.qty * COALESCE(p.cost,0)),0) AS v FROM order_items oi
               LEFT JOIN products p ON p.id = oi.product_id JOIN orders o ON o.id = oi.order_id
              WHERE oi.tenant_id = ? AND o.status='fulfilled'",
            [$t]
        );
        return H::json([
            'revenue' => $revenue->v ?? 0, 'revenueOrders' => (int) ($revenue->c ?? 0),
            'receivable' => $receivable->v ?? 0, 'receivableOrders' => (int) ($receivable->c ?? 0),
            'paid' => $paid->v ?? 0, 'planned' => $planned->v ?? 0,
            'expenses' => $expenses->v ?? 0,
            'grossProfit' => ($revenue->v ?? 0) - ($expenses->v ?? 0),
            'byMonth' => $byMonth, 'margins' => $margins, 'topClients' => $topClients, 'overdue' => $overdue,
            'disclaimer' => 'Счёт в Nexio CRM не является юридически значимым электронным документом до подключения ЭДО.',
        ]);
    }

    private function assertClickConfigured(): void
    {
        $click = config('payments.click', []);
        foreach (['merchant_id', 'service_id', 'return_url'] as $key) {
            if (empty($click[$key])) {
                throw new ApiException(503, 'Click konfiguratsiyasi to‘liq emas', 'payment_not_configured');
            }
        }
    }

    private function clickPaymentUrl(string $invoiceId, float $amount): string
    {
        $click = config('payments.click');
        return 'https://my.click.uz/services/pay?' . http_build_query([
            'service_id' => $click['service_id'], 'merchant_id' => $click['merchant_id'],
            'amount' => number_format($amount, 2, '.', ''), 'transaction_param' => $invoiceId,
            'return_url' => $click['return_url'],
        ]);
    }

    private function clickQuoteForInvoice(object $invoice): array
    {
        $items = H::parseJson($invoice->line_items, []);
        foreach (is_array($items) ? $items : [] as $item) {
            if (($item['kind'] ?? null) === 'click_conversion' && is_array($item['click_quote'] ?? null)) {
                return $item['click_quote'];
            }
        }
        $quote = (new CbuExchangeRateService())->quote((float) $invoice->total, (string) $invoice->currency);
        $items[] = ['kind' => 'click_conversion', 'amount' => 0, 'click_quote' => $quote];
        DB::table('invoices')->where('id', $invoice->id)->update(['line_items' => json_encode($items, JSON_UNESCAPED_UNICODE)]);
        return $quote;
    }

    private function invoiceView(object $invoice): array
    {
        $items = H::parseJson($invoice->line_items, []);
        $quote = null;
        foreach (is_array($items) ? $items : [] as $item) {
            if (($item['kind'] ?? null) === 'click_conversion' && is_array($item['click_quote'] ?? null)) $quote = $item['click_quote'];
        }
        return [...(array) $invoice, 'line_items' => array_values(array_filter(is_array($items) ? $items : [], fn ($item) => ($item['kind'] ?? null) !== 'click_conversion')), 'click_amount_uzs' => $quote['target_amount'] ?? null, 'exchange_rate' => $quote['rate'] ?? null, 'exchange_rate_date' => $quote['rate_date'] ?? null];
    }
}
