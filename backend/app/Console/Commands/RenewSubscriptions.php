<?php

namespace App\Console\Commands;

use App\Services\CardBillingService;
use App\Services\ClickCardService;
use App\Support\BillingPeriod;
use App\Support\H;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Subscription lifecycle, run hourly:
 *   1. 3 days before a free (lgota) period granted by the platform ends, and 3 days before a paid period ends: warn.
 *   2. Cancelled-at-period-end subscriptions are closed.
 *   3. Periods that ended (always the 1st of a month 00:00 Asia/Tashkent): charge the cards, primary first.
 *      A free period that ended is converted to paid and the rest of that month is charged.
 *   4. Unpaid subscriptions are retried once a day during the grace window, then access is closed (data kept).
 * Permanent platform grants (complimentary, open-ended) are never charged.
 */
class RenewSubscriptions extends Command
{
    protected $signature = 'nexio:billing:renew';
    protected $description = 'Warn, charge saved cards, retry and lock overdue subscriptions';

    public function handle(): int
    {
        $billing = new CardBillingService();
        $this->warnEndingLgota($billing);
        $this->warnUpcomingRenewals($billing);
        $this->closeCancelled();
        // Without Click credentials nothing can be charged: do not push every company into overdue and lock it.
        if (!(new ClickCardService())->configured()) {
            $this->warn('Click card billing is not configured: charging and locking skipped');
            return self::SUCCESS;
        }
        $this->chargeDue($billing);
        $locked = $billing->lockExpired();
        if ($locked) $this->warn("Access closed for {$locked} subscription(s)");
        return self::SUCCESS;
    }

    private function noticeDays(): int
    {
        return max(1, (int) config('payments.billing.lgota_notice_days', 3));
    }

    /** Free period granted by the platform ends within 3 days: tell the company what will be charged. */
    private function warnEndingLgota(CardBillingService $billing): void
    {
        $rows = DB::table('subscriptions as s')->join('plans as p', 'p.id', '=', 's.plan_id')
            ->whereIn('s.status', ['active', 'trialing'])->where('s.complimentary', 1)->whereNull('s.lgota_notified_at')
            ->whereBetween('s.period_end', [H::now(), gmdate('Y-m-d H:i:s', time() + $this->noticeDays() * 86400)])
            ->get(['s.id', 's.tenant_id', 's.period_end', 'p.name as plan_name', 'p.price_monthly', 'p.currency']);
        foreach ($rows as $s) {
            if (BillingPeriod::isOpenEnded($s->period_end)) continue;
            $hasCard = (bool) $billing->cards((string) $s->tenant_id);
            $date = gmdate('d.m.Y', strtotime($s->period_end . ' UTC'));
            $billing->tell((string) $s->tenant_id, 'warning', 'Bepul davr tugashiga ' . $this->noticeDays() . ' kun qoldi',
                "{$s->plan_name} tarifidagi bepul davr {$date} da tugaydi. Shundan keyin shu oy uchun to‘lov kartadan yechiladi, keyin har oyning 1-sanasida ({$s->price_monthly} {$s->currency} / oy). "
                . ($hasCard ? 'Karta ulangan.' : 'Hozircha karta ulanmagan: Billing bo‘limida karta qo‘shing.'));
            DB::table('subscriptions')->where('id', $s->id)->update(['lgota_notified_at' => H::now()]);
        }
    }

    /** Paid subscription renews within 3 days. */
    private function warnUpcomingRenewals(CardBillingService $billing): void
    {
        $rows = DB::table('subscriptions as s')->join('plans as p', 'p.id', '=', 's.plan_id')
            ->where('s.status', 'active')->where('s.complimentary', 0)->where('s.cancel_at_period_end', 0)
            ->whereBetween('s.period_end', [H::now(), gmdate('Y-m-d H:i:s', time() + $this->noticeDays() * 86400)])
            ->get(['s.tenant_id', 's.period_end', 'p.name as plan_name', 'p.price_monthly', 'p.currency']);
        foreach ($rows as $s) {
            $cards = $billing->cards((string) $s->tenant_id);
            $date = gmdate('d.m.Y', strtotime($s->period_end . ' UTC'));
            $billing->tell((string) $s->tenant_id, 'info', 'Tarif yangilanishi yaqin',
                "{$s->plan_name} tarifi {$date} da avtomatik yangilanadi. " . ($cards ? 'To‘lov ulangan kartadan yechiladi.' : 'Karta ulanmagan, to‘lov yechilmaydi: Billing bo‘limida karta qo‘shing.'));
        }
    }

    private function closeCancelled(): void
    {
        $rows = DB::table('subscriptions')->whereIn('status', ['active', 'trialing', 'past_due'])->where('cancel_at_period_end', 1)->where('period_end', '<=', H::now())->get();
        foreach ($rows as $s) {
            $keep = gmdate('Y-m-d H:i:s', time() + max(1, (int) config('payments.billing.data_retention_days', 90)) * 86400);
            DB::table('subscriptions')->where('id', $s->id)->update(['status' => 'cancelled', 'cancelled_at' => H::now(), 'blocked_at' => H::now(), 'data_retain_until' => $keep]);
            DB::table('tenants')->where('id', $s->tenant_id)->update(['status' => 'cancelled', 'updated_at' => H::now()]);
            DB::table('billing_events')->insert(['id' => H::newId('bev'), 'tenant_id' => $s->tenant_id, 'kind' => 'subscription.cancelled_at_period_end', 'detail' => 'retain_until:' . $keep, 'created_at' => H::now()]);
        }
    }

    private function chargeDue(CardBillingService $billing): void
    {
        $due = DB::table('subscriptions')->whereIn('status', ['active', 'trialing', 'past_due', 'grace'])
            ->where('cancel_at_period_end', 0)->whereNull('blocked_at')->whereNull('charge_state')
            ->where('period_end', '<=', H::now())->get();
        foreach ($due as $s) {
            // Permanent grants are never charged; a grant with an end date is converted to paid at its end.
            if ($s->complimentary) {
                if (BillingPeriod::isOpenEnded($s->period_end)) continue;
                DB::table('subscriptions')->where('id', $s->id)->update(['complimentary' => 0, 'grant_note' => null]);
                $s->complimentary = 1;   // renew() still treats this first charge as partial (rest of the month)
            }
            // At most one attempt per ~day: claim it atomically so overlapping runs never double-charge.
            $claimed = DB::table('subscriptions')->where('id', $s->id)
                ->where(fn ($q) => $q->whereNull('last_charge_attempt_at')->orWhere('last_charge_attempt_at', '<', gmdate('Y-m-d H:i:s', time() - 23 * 3600)))
                ->update(['last_charge_attempt_at' => H::now()]);
            if (!$claimed) continue;
            try {
                $r = $billing->renew($s);
                $this->line("{$s->tenant_id}: {$r['outcome']}");
            } catch (\Throwable $e) {
                // One tenant's failure (e.g. FX rate unavailable) must not stop the others.
                $this->warn("{$s->tenant_id}: " . $e::class);
                DB::table('billing_events')->insert(['id' => H::newId('bev'), 'tenant_id' => $s->tenant_id, 'kind' => 'subscription.renewal_error', 'detail' => $e::class, 'created_at' => H::now()]);
            }
        }
    }
}
