<?php

namespace App\Services;

use App\Support\BillingPeriod;
use App\Support\H;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Subscription charging with saved cards.
 *
 *  - Cards are tried in order: the primary one first, then the extra ones.
 *  - If every card fails the subscription goes past_due with a grace window; the company is warned in the CRM,
 *    by email and in Telegram. When the window ends access is closed (blocked_at) but data is kept.
 *  - A charge whose result is unknown (timeout) is never retried automatically and never falls through to the next card.
 */
class CardBillingService
{
    public function __construct(
        private ClickCardService $click = new ClickCardService(),
        private NotificationDispatcher $notifier = new NotificationDispatcher(),
    ) {}

    /** @return list<object> usable cards, primary first. */
    public function cards(string $tenantId): array
    {
        return DB::table('payment_cards')->where('tenant_id', $tenantId)->where('status', 'active')
            ->orderByDesc('is_primary')->orderBy('created_at')->get()->all();
    }

    /**
     * Charges an open invoice with the company's cards and settles it on success.
     * @return array{ok:bool,outcome:string,error:?string,card_id:?string}
     */
    public function chargeInvoice(object $invoice): array
    {
        $cards = $this->cards((string) $invoice->tenant_id);
        if (!$cards) return ['ok' => false, 'outcome' => ClickCardService::DECLINED, 'error' => 'Karta ulanmagan', 'card_id' => null];

        $quote = (new CbuExchangeRateService())->quote((float) $invoice->total, (string) $invoice->currency);
        $amountUzs = (float) $quote['target_amount'];
        $lastError = null;
        foreach ($cards as $card) {
            try {
                $token = Crypt::decryptString($card->token_enc);
            } catch (\Throwable $e) {
                $this->markCard($card->id, 'failed', 'Karta tokenini o‘qib bo‘lmadi');
                continue;
            }
            $r = $this->click->charge($token, $amountUzs, (string) $invoice->id);
            if ($r['outcome'] === ClickCardService::PAID) {
                (new InvoiceSettlement())->settle($invoice, 'click_card', (string) $r['payment_id'], $amountUzs);
                DB::table('payment_cards')->where('id', $card->id)->update(['last_used_at' => H::now(), 'last_error' => null, 'updated_at' => H::now()]);
                return ['ok' => true, 'outcome' => ClickCardService::PAID, 'error' => null, 'card_id' => $card->id];
            }
            if ($r['outcome'] === ClickCardService::UNKNOWN) {
                return ['ok' => false, 'outcome' => ClickCardService::UNKNOWN, 'error' => $r['error'], 'card_id' => $card->id];
            }
            $lastError = $r['error'];
            DB::table('payment_cards')->where('id', $card->id)->update(['last_error' => mb_substr((string) $lastError, 0, 200), 'updated_at' => H::now()]);
            DB::table('billing_events')->insert(['id' => H::newId('bev'), 'tenant_id' => $invoice->tenant_id, 'kind' => 'card.charge_declined', 'detail' => $card->masked . ' · ' . $invoice->number, 'created_at' => H::now()]);
        }
        return ['ok' => false, 'outcome' => ClickCardService::DECLINED, 'error' => $lastError ?? 'Karta rad etildi', 'card_id' => null];
    }

    /** Opens (or reuses) the renewal invoice for one period of a subscription. */
    public function renewalInvoice(object $sub, object $plan, string $interval, float $price, float $total, string $label): object
    {
        $key = $sub->id . ':' . $sub->period_end;
        $existing = DB::table('invoices')->where('tenant_id', $sub->tenant_id)->where('status', 'open')
            ->where('line_items', 'like', '%"renewal_key":"' . $key . '"%')->first();
        if ($existing) return $existing;
        $id = H::newId('inv');
        DB::table('invoices')->insert([
            'id' => $id, 'tenant_id' => $sub->tenant_id, 'subscription_id' => $sub->id,
            'number' => 'INV-' . gmdate('Y') . '-' . strtoupper(substr(H::newId('n'), -5)),
            'amount' => $price, 'tax' => 0, 'total' => $total, 'currency' => $plan->currency, 'status' => 'open',
            'line_items' => json_encode([
                ['label' => $label, 'qty' => 1, 'amount' => $total],
                ['kind' => 'subscription_renewal', 'sub_id' => $sub->id, 'plan_code' => $plan->code, 'interval' => $interval, 'renewal_key' => $key],
            ], JSON_UNESCAPED_UNICODE),
            'issued_at' => H::now(), 'due_at' => H::now(), 'created_at' => H::now(),
        ]);
        return DB::table('invoices')->where('id', $id)->first();
    }

    /**
     * One renewal attempt for a subscription whose period has ended. Returns the charge result.
     * @return array{ok:bool,outcome:string,error:?string}
     */
    public function renew(object $sub, bool $force = false): array
    {
        $planId = $sub->plan_id;
        $interval = $sub->interval === 'year' ? 'year' : 'month';
        if (!empty($sub->pending_plan_code)) {
            $pending = DB::table('plans')->where('code', $sub->pending_plan_code)->first();
            if ($pending) { $planId = $pending->id; $interval = $sub->pending_interval === 'year' ? 'year' : 'month'; }
        }
        $plan = DB::table('plans')->where('id', $planId)->first();
        if (!$plan) return ['ok' => false, 'outcome' => ClickCardService::DECLINED, 'error' => 'Tarif topilmadi'];

        $price = (float) ($interval === 'year' ? $plan->price_yearly : $plan->price_monthly);
        // A trial, a just-ended free period or a legacy period that does not end on the 1st pays only for what is left of the month.
        $partial = $interval === 'month' && ($sub->status === 'trialing' || !empty($sub->complimentary) || !BillingPeriod::isBoundary($sub->period_end));
        $total = $partial ? round($price * BillingPeriod::monthFractionLeft(), 2) : $price;
        if ($total <= 0) {
            // Nothing to collect (free plan or the month is over): just move the period forward.
            $invoice = $this->renewalInvoice($sub, $plan, $interval, $price, 0, $plan->name . ' · bepul davr');
            (new InvoiceSettlement())->settle($invoice, 'internal', 'zero:' . $invoice->id, 0);
            return ['ok' => true, 'outcome' => ClickCardService::PAID, 'error' => null];
        }
        $label = $plan->name . ' · ' . ($interval === 'year' ? 'yillik' : 'oylik') . ' obuna' . ($partial ? ' (oy oxirigacha)' : '');
        $invoice = $this->renewalInvoice($sub, $plan, $interval, $price, $total, $label);
        $r = $this->chargeInvoice($invoice);
        if ($r['ok']) {
            $this->tell((string) $sub->tenant_id, 'success', 'To‘lov qabul qilindi', $plan->name . ' tarifi uchun ' . number_format($total, 2, '.', ' ') . ' ' . $plan->currency . ' yechildi.');
            return $r;
        }
        if ($r['outcome'] === ClickCardService::UNKNOWN) {
            DB::table('subscriptions')->where('id', $sub->id)->update(['charge_state' => 'unknown']);
            DB::table('billing_events')->insert(['id' => H::newId('bev'), 'tenant_id' => $sub->tenant_id, 'kind' => 'card.charge_unknown', 'detail' => $invoice->number, 'created_at' => H::now()]);
            (new PlatformAlerts())->notify('Karta to‘lovi natijasi noma’lum', "Invoice {$invoice->number}: Click javobi kelmadi. Qayta urinilmaydi, Click kabinetida qo‘lda tekshiring.", "/platform/tenants/{$sub->tenant_id}", 'error');
            $this->tell((string) $sub->tenant_id, 'warning', 'To‘lov tekshirilmoqda', 'Click javobi kelmadi. To‘lov holati tekshirilguncha qayta yechilmaydi.');
            return $r;
        }
        $this->fail($sub, $invoice, (string) $r['error']);
        return $r;
    }

    /** Every card failed: start (or continue) the grace window and warn the company. */
    private function fail(object $sub, object $invoice, string $error): void
    {
        $grace = $sub->grace_until ?: gmdate('Y-m-d H:i:s', time() + max(1, (int) config('payments.billing.grace_days', 7)) * 86400);
        DB::table('subscriptions')->where('id', $sub->id)->update(['status' => 'past_due', 'grace_until' => $grace]);
        DB::table('tenants')->where('id', $sub->tenant_id)->where('status', '!=', 'suspended')->update(['status' => 'past_due', 'updated_at' => H::now()]);
        DB::table('billing_events')->insert(['id' => H::newId('bev'), 'tenant_id' => $sub->tenant_id, 'kind' => 'subscription.renewal_failed', 'detail' => $invoice->number . ' · ' . mb_substr($error, 0, 120), 'created_at' => H::now()]);

        $last = $sub->last_dunning_at ? strtotime($sub->last_dunning_at . ' UTC') : 0;
        if (time() - $last < 47 * 3600) return;    // warn on the first failure, then every other day
        DB::table('subscriptions')->where('id', $sub->id)->update(['last_dunning_at' => H::now()]);
        $until = gmdate('d.m.Y', strtotime($grace . ' UTC'));
        $this->tell((string) $sub->tenant_id, 'error', 'Tarif to‘lovi yechilmadi',
            "Kartadan to‘lov yechib bo‘lmadi ({$error}). {$until} gacha to‘lang, aks holda CRMga kirish yopiladi. Ma’lumotlaringiz saqlanadi. Karta qo‘shing yoki tekshiring: Billing bo‘limi.");
    }

    /** Closes access for subscriptions whose grace window ended. Data is kept for the retention period. */
    public function lockExpired(): int
    {
        $rows = DB::table('subscriptions')->where('status', 'past_due')->whereNull('blocked_at')
            ->where('complimentary', 0)->whereNotNull('grace_until')->where('grace_until', '<=', H::now())->get();
        foreach ($rows as $sub) {
            $keep = gmdate('Y-m-d H:i:s', time() + max(1, (int) config('payments.billing.data_retention_days', 90)) * 86400);
            DB::table('subscriptions')->where('id', $sub->id)->update(['blocked_at' => H::now(), 'data_retain_until' => $keep]);
            DB::table('tenants')->where('id', $sub->tenant_id)->update(['status' => 'suspended', 'updated_at' => H::now()]);
            DB::table('billing_events')->insert(['id' => H::newId('bev'), 'tenant_id' => $sub->tenant_id, 'kind' => 'subscription.access_blocked', 'detail' => 'retain_until:' . $keep, 'created_at' => H::now()]);
            $this->tell((string) $sub->tenant_id, 'error', 'CRMga kirish yopildi',
                'To‘lov muddatida amalga oshmadi. Ma’lumotlaringiz ' . gmdate('d.m.Y', strtotime($keep . ' UTC') ) . ' gacha saqlanadi. Kartani qo‘shib yoki to‘lab, kirishni darhol tiklashingiz mumkin.');
        }
        return $rows->count();
    }

    /** In-app + Telegram (via the dispatcher) to owners and admins, plus email. */
    public function tell(string $tenantId, string $type, string $title, string $body): void
    {
        try {
            $this->notifier->notifyAdmins($tenantId, $type, $title, $body, '/app/billing');
            $emails = DB::table('tenant_memberships as tm')->join('users as u', 'u.id', '=', 'tm.user_id')
                ->where('tm.tenant_id', $tenantId)->where('tm.status', 'active')->whereIn('tm.role', ['tenant_owner', 'tenant_admin'])
                ->pluck('u.email');
            foreach ($emails as $email) {
                if (!empty($email)) $this->notifier->email((string) $email, $title, $body);
            }
        } catch (\Throwable $e) {
            Log::warning('Billing notice failed', ['tenant' => $tenantId, 'exception' => $e::class]);
        }
    }

    private function markCard(string $id, string $status, string $error): void
    {
        DB::table('payment_cards')->where('id', $id)->update(['status' => $status, 'last_error' => $error, 'updated_at' => H::now()]);
    }
}
