<?php

namespace App\Http\Controllers;

use App\Support\H;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\CbuExchangeRateService;
use App\Services\SubscriptionEntitlementService;

class ClickWebhookController
{
    public function handle(Request $request)
    {
        $data = $request->all();
        $serviceId = (string) ($data['service_id'] ?? '');
        $secret = (string) config('payments.click.secret_key');
        $signature = (string) ($data['sign_string'] ?? '');
        $expected = md5(
            (string) ($data['click_trans_id'] ?? '') .
            $serviceId . $secret .
            (string) ($data['merchant_trans_id'] ?? '') .
            (string) ($data['amount'] ?? '') .
            (string) ($data['action'] ?? '') .
            (string) ($data['sign_time'] ?? '')
        );
        if ($secret === '' || $serviceId === '' || $serviceId !== (string) config('payments.click.service_id') || !hash_equals(strtolower($expected), strtolower($signature))) {
            return response()->json(['error' => -1, 'error_note' => 'SIGN CHECK FAILED']);
        }

        $invoice = DB::table('invoices')->where('id', (string) ($data['merchant_trans_id'] ?? ''))->first();
        if (!$invoice) return response()->json(['error' => -5, 'error_note' => 'Invoice not found']);
        $items = json_decode((string) $invoice->line_items, true) ?: [];
        $quote = null;
        foreach ($items as $item) {
            if (($item['kind'] ?? null) === 'click_conversion' && is_array($item['click_quote'] ?? null)) {
                $quote = $item['click_quote'];
                break;
            }
        }
        $quote ??= (new CbuExchangeRateService())->quote((float) $invoice->total, (string) $invoice->currency);
        $amount = (float) ($data['amount'] ?? 0);
        if (abs($amount - (float) $quote['target_amount']) > 0.01) return response()->json(['error' => -2, 'error_note' => 'Incorrect amount']);
        $action = (int) ($data['action'] ?? -1);
        if ($action === 0) return response()->json(['click_trans_id' => $data['click_trans_id'], 'merchant_trans_id' => $invoice->id, 'merchant_prepare_id' => $invoice->id, 'error' => 0, 'error_note' => 'Success']);
        if ($action !== 1) return response()->json(['error' => -3, 'error_note' => 'Action not found']);

        $wasPaid = $invoice->status === 'paid';

        DB::transaction(function () use ($invoice, $data, $amount) {
            $existing = DB::table('payments')->where('provider', 'click')->where('provider_ref', (string) $data['click_trans_id'])->exists();
            if ($existing) return;
            if (DB::table('invoices')->where('id', $invoice->id)->where('status', 'paid')->exists()) return;
            DB::table('invoices')->where('id', $invoice->id)->update(['status' => 'paid', 'paid_at' => H::now()]);
            DB::table('payments')->updateOrInsert(
                ['provider' => 'click', 'provider_ref' => (string) $data['click_trans_id']],
                ['id' => H::newId('pay'), 'tenant_id' => $invoice->tenant_id, 'invoice_id' => $invoice->id, 'amount' => $amount, 'currency' => 'UZS', 'status' => 'succeeded', 'created_at' => H::now()]
            );
            DB::table('billing_events')->insert(['id' => H::newId('bev'), 'tenant_id' => $invoice->tenant_id, 'kind' => 'invoice.paid', 'detail' => 'click:' . $invoice->number, 'created_at' => H::now()]);
        });
        if (!$wasPaid) {
            foreach ($items as $item) {
                if (($item['kind'] ?? null) === 'subscription_renewal' && !empty($item['sub_id'])) {
                    (new \App\Services\InvoiceSettlement())->renewSubscription((string) $invoice->tenant_id, (string) $item['sub_id'], (string) $invoice->id);
                    break;
                }
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
        }
        return response()->json(['click_trans_id' => $data['click_trans_id'], 'merchant_trans_id' => $invoice->id, 'merchant_confirm_id' => $invoice->id, 'error' => 0, 'error_note' => 'Success']);
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
                'period_end' => \App\Support\BillingPeriod::nextBoundary($interval), 'created_at' => H::now(),
            ]);
            DB::table('tenants')->where('id', $tenantId)->update(['plan_id' => $plan->id, 'status' => 'active', 'updated_at' => H::now()]);
            (new SubscriptionEntitlementService())->apply($tenantId, $plan->code);
            DB::table('billing_events')->insert(['id' => H::newId('bev'), 'tenant_id' => $tenantId, 'kind' => 'subscription.activated', 'detail' => $invoiceId . ':' . $plan->code, 'created_at' => H::now()]);
        });
    }
}
