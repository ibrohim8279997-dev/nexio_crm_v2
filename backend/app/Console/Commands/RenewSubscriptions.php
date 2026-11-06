<?php

namespace App\Console\Commands;

use App\Services\NotificationDispatcher;
use App\Support\H;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RenewSubscriptions extends Command
{
    protected $signature = 'nexio:billing:renew';
    protected $description = 'Create renewal notices for due subscriptions';

    public function handle(): int
    {
        $notifications = new NotificationDispatcher();
        $reminders = DB::table('subscriptions as s')->join('plans as p', 'p.id', '=', 's.plan_id')
            ->whereIn('s.status', ['active', 'trialing'])->whereBetween('s.period_end', [now(), now()->addDays(3)])
            ->get(['s.tenant_id', 's.period_end', 'p.name as plan_name']);
        foreach ($reminders as $subscription) {
            $members = DB::table('tenant_memberships as tm')->join('users as u', 'u.id', '=', 'tm.user_id')
                ->where('tm.tenant_id', $subscription->tenant_id)->where('tm.status', 'active')->get(['u.id', 'u.email']);
            foreach ($members as $member) {
                $title = 'Tarif muddati tugashiga oz qoldi';
                $body = $subscription->plan_name . ' tarifi ' . $subscription->period_end . ' da yangilanadi.';
                $recentlySent = DB::table('notifications')->where('user_id', $member->id)->where('type', 'warning')
                    ->where('title', $title)->where('body', $body)->where('created_at', '>=', now()->subDay())->exists();
                if ($recentlySent) continue;
                $notifications->notify((string) $member->id, (string) $subscription->tenant_id, 'warning', $title, $body, '/app/billing');
                if (!empty($member->email)) $notifications->email((string) $member->email, $title, $body);
            }
        }
        $rows = DB::table('subscriptions as s')->join('plans as p', 'p.id', '=', 's.plan_id')
            ->whereIn('s.status', ['active', 'past_due', 'grace'])->where('s.period_end', '<=', now())
            ->select('s.*', 'p.name as plan_name')->get();
        foreach ($rows as $s) {
            try {
                throw new \RuntimeException('Avtomatik yangilash uchun Click orqali yangi invoice to‘lovi kerak');
            } catch (\Throwable $e) {
                DB::table('subscriptions')->where('id', $s->id)->update(['status' => 'past_due']);
                DB::table('billing_events')->insert(['id' => H::newId('bev'), 'tenant_id' => $s->tenant_id, 'kind' => 'subscription.renewal_failed', 'detail' => $e->getMessage(), 'created_at' => H::now()]);
                $members = DB::table('tenant_memberships as tm')->join('users as u', 'u.id', '=', 'tm.user_id')
                    ->where('tm.tenant_id', $s->tenant_id)->where('tm.status', 'active')->get(['u.id', 'u.email']);
                foreach ($members as $member) {
                    $title = 'Tarif avtomatik yangilanmadi';
                    $body = $s->plan_name . ' uchun Click orqali yangi to‘lovni amalga oshiring.';
                    $notifications->notify((string) $member->id, (string) $s->tenant_id, 'error', $title, $body, '/app/billing');
                    if (!empty($member->email)) $notifications->email((string) $member->email, $title, $body);
                }
                $this->warn("Failed {$s->tenant_id}: {$e->getMessage()}");
            }
        }
        return self::SUCCESS;
    }
}
