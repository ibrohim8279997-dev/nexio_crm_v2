<?php
namespace App\Services;

use App\Support\H;
use Illuminate\Support\Facades\DB;

/**
 * Marks an invoice paid exactly once and applies what it bought (plan activation or plugin purchase).
 * Idempotent per (provider, provider_ref): a redelivered provider callback never pays twice.
 */
class InvoiceSettlement
{
    /** @return bool true when this call settled the invoice, false when it was already settled. */
    public function settle(object $invoice, string $provider, string $providerRef, float $amountUzs): bool
    {
        $items = H::parseJson($invoice->line_items, []);
        $settled = false;
        DB::transaction(function () use ($invoice, $provider, $providerRef, $amountUzs, &$settled) {
            if (DB::table('payments')->where('provider', $provider)->where('provider_ref', $providerRef)->exists()) return;
            if (DB::table('invoices')->where('id', $invoice->id)->where('status', 'paid')->exists()) return;
            DB::table('invoices')->where('id', $invoice->id)->update(['status' => 'paid', 'paid_at' => H::now()]);
            DB::table('payments')->insert([
                'id' => H::newId('pay'), 'tenant_id' => $invoice->tenant_id, 'invoice_id' => $invoice->id, 'provider' => $provider,
                'provider_ref' => $providerRef, 'amount' => $amountUzs, 'currency' => 'UZS', 'status' => 'succeeded', 'created_at' => H::now(),
            ]);
            DB::table('billing_events')->insert(['id' => H::newId('bev'), 'tenant_id' => $invoice->tenant_id, 'kind' => 'invoice.paid', 'detail' => $provider . ':' . $invoice->number, 'created_at' => H::now()]);
            $settled = true;
        });
        if (!$settled) return false;

        foreach (is_array($items) ? $items : [] as $item) {
            if (($item['kind'] ?? null) === 'subscription_plan' && !empty($item['plan_code'])) {
                $this->activateSubscription((string) $invoice->tenant_id, (string) $item['plan_code'], (string) ($item['interval'] ?? 'month'), (string) $invoice->id);
                break;
            }
            if (($item['kind'] ?? null) === 'plugin_purchase' && !empty($item['plugin_id'])) {
                DB::table('plugin_purchases')->insertOrIgnore([
                    'id' => H::newId('ppur'), 'tenant_id' => $invoice->tenant_id, 'plugin_id' => $item['plugin_id'],
                    'amount' => $invoice->total, 'currency' => $invoice->currency, 'status' => 'active',
                    'created_at' => H::now(), 'updated_at' => H::now(),
                ]);
                break;
            }
        }
        return true;
    }

    private function activateSubscription(string $tenantId, string $planCode, string $interval, string $invoiceId): void
    {
        $plan = DB::table('plans')->where('code', $planCode)->first();
        if (!$plan) return;
        $interval = $interval === 'year' ? 'year' : 'month';
        DB::transaction(function () use ($tenantId, $plan, $interval, $invoiceId) {
            DB::table('subscriptions')->where('tenant_id', $tenantId)->whereIn('status', ['active', 'trialing', 'past_due', 'grace'])->update([
                'status' => 'cancelled', 'cancelled_at' => H::now(), 'cancel_at_period_end' => 0,
            ]);
            DB::table('subscriptions')->insert([
                'id' => H::newId('sub'), 'tenant_id' => $tenantId, 'plan_id' => $plan->id,
                'status' => 'active', 'seats' => $plan->seats, 'amount' => $interval === 'year' ? $plan->price_yearly : $plan->price_monthly,
                'currency' => $plan->currency, 'interval' => $interval, 'period_start' => H::now(),
                'period_end' => gmdate('Y-m-d H:i:s', time() + ($interval === 'year' ? 365 : 30) * 86400), 'created_at' => H::now(),
            ]);
            DB::table('tenants')->where('id', $tenantId)->update(['plan_id' => $plan->id, 'status' => 'active', 'updated_at' => H::now()]);
            (new SubscriptionEntitlementService())->apply($tenantId, $plan->code);
            DB::table('billing_events')->insert(['id' => H::newId('bev'), 'tenant_id' => $tenantId, 'kind' => 'subscription.activated', 'detail' => $invoiceId . ':' . $plan->code, 'created_at' => H::now()]);
        });
    }
}
