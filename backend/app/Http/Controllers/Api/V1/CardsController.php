<?php
namespace App\Http\Controllers\Api\V1;

use App\Services\CardBillingService;
use App\Services\ClickCardService;
use App\Support\ApiException;
use App\Support\BillingPeriod;
use App\Support\Ctx;
use App\Support\H;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Company payment cards (Click card tokens) and the billing status shown as a banner in the CRM.
 * The card number is forwarded to Click once and discarded; only the provider token (encrypted) and the masked number are kept.
 * The CVV is never accepted: the token flow does not need it and storing it is prohibited (PCI DSS 3.3).
 */
class CardsController
{
    private function latestSub(string $tenantId): ?object
    {
        return DB::table('subscriptions')->where('tenant_id', $tenantId)->orderByDesc('created_at')->first();
    }

    private function view(object $c): array
    {
        return ['id' => $c->id, 'masked' => $c->masked, 'last4' => $c->last4, 'expiry' => $c->expiry, 'is_primary' => (bool) $c->is_primary,
            'status' => $c->status, 'last_error' => $c->last_error, 'last_used_at' => $c->last_used_at, 'created_at' => $c->created_at];
    }

    public function list(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'billing.read');
        H::requireTenant($ctx);
        $rows = DB::table('payment_cards')->where('tenant_id', $ctx->tenantId)->where('status', '!=', 'removed')
            ->orderByDesc('is_primary')->orderBy('created_at')->get();
        return H::json([
            'data' => $rows->map(fn ($c) => $this->view($c))->all(),
            'configured' => (new ClickCardService())->configured(),
            'max' => (int) config('payments.billing.max_cards', 5),
        ]);
    }

    /** Step 1: send the card to Click; Click texts a code to the number linked to the card. */
    public function request(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'billing.write');
        H::requireTenant($ctx);
        $t = $ctx->tenantId;
        $b = H::body($ctx);
        $number = preg_replace('/\D+/', '', (string) ($b['card_number'] ?? ''));
        $digits = preg_replace('/\D+/', '', (string) ($b['expiry'] ?? ''));
        if (strlen($number) < 16 || strlen($number) > 19 || !$this->luhn($number)) throw new ApiException(422, 'Karta raqami noto‘g‘ri', 'invalid_card');
        if (strlen($digits) !== 4) throw new ApiException(422, 'Amal qilish muddati MM/YY ko‘rinishida bo‘lishi kerak', 'invalid_card');
        $mm = (int) substr($digits, 0, 2); $yy = (int) substr($digits, 2, 2);
        if ($mm < 1 || $mm > 12 || ($yy + 2000) * 12 + $mm < (int) gmdate('Y') * 12 + (int) gmdate('n')) throw new ApiException(422, 'Karta muddati tugagan', 'invalid_card');
        if (!(new ClickCardService())->configured()) throw new ApiException(503, 'Karta orqali to‘lov hali sozlanmagan', 'payment_not_configured');
        if (DB::table('payment_cards')->where('tenant_id', $t)->where('status', '!=', 'removed')->count() >= (int) config('payments.billing.max_cards', 5)) {
            throw new ApiException(409, 'Kartalar soni chegarasiga yetdi', 'too_many_cards');
        }
        // Card-testing defence: a handful of enrollment attempts per company per hour.
        $bucket = 'card:enroll:' . $t . ':' . gmdate('YmdH');
        $n = (int) Cache::get($bucket, 0);
        if ($n >= 8) throw new ApiException(429, 'Juda ko‘p urinish. Bir soatdan keyin qayta urinib ko‘ring', 'rate_limited');
        Cache::put($bucket, $n + 1, 3600);

        $r = (new ClickCardService())->requestToken($number, sprintf('%02d%02d', $mm, $yy));
        $enrollId = H::newId('enr');
        Cache::put("card:enroll:{$t}:{$enrollId}", Crypt::encryptString(json_encode(['token' => $r['token'], 'expiry' => sprintf('%02d%02d', $mm, $yy), 'tries' => 0])), 600);
        return H::json(['enroll_id' => $enrollId, 'phone' => $r['phone'], 'expires_in' => 600]);
    }

    /** Step 2: confirm with the SMS code and save the card. If the subscription is overdue it is charged right away. */
    public function verify(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'billing.write');
        H::requireTenant($ctx);
        $t = $ctx->tenantId;
        $b = H::body($ctx);
        $enrollId = H::str($b['enroll_id'] ?? null, 'enroll_id', true, 60);
        $sms = preg_replace('/\D+/', '', (string) ($b['sms_code'] ?? ''));
        if (strlen($sms) < 4 || strlen($sms) > 8) throw new ApiException(422, 'SMS kodni kiriting', 'invalid_sms');
        $key = "card:enroll:{$t}:{$enrollId}";
        $raw = Cache::get($key);
        if (!$raw) throw new ApiException(410, 'Tasdiqlash muddati tugadi, kartani qaytadan kiriting', 'enroll_expired');
        $state = json_decode(Crypt::decryptString($raw), true);
        if (($state['tries'] ?? 0) >= 5) { Cache::forget($key); throw new ApiException(429, 'SMS kod ko‘p marta noto‘g‘ri kiritildi, kartani qaytadan kiriting', 'rate_limited'); }
        $state['tries'] = ($state['tries'] ?? 0) + 1;
        Cache::put($key, Crypt::encryptString(json_encode($state)), 600);

        $masked = (new ClickCardService())->verifyToken((string) $state['token'], $sms) ?: '****';
        Cache::forget($key);
        $last4 = substr(preg_replace('/\D+/', '', $masked) ?: '0000', -4);
        if (DB::table('payment_cards')->where('tenant_id', $t)->where('status', '!=', 'removed')->where('masked', $masked)->exists()) {
            (new ClickCardService())->deleteToken((string) $state['token']);
            throw new ApiException(409, 'Bu karta allaqachon qo‘shilgan', 'duplicate_card');
        }
        $hasPrimary = DB::table('payment_cards')->where('tenant_id', $t)->where('status', 'active')->where('is_primary', 1)->exists();
        $id = H::newId('card');
        DB::table('payment_cards')->insert([
            'id' => $id, 'tenant_id' => $t, 'added_by' => $ctx->user['id'] ?? null, 'provider' => 'click',
            'token_enc' => Crypt::encryptString((string) $state['token']), 'masked' => mb_substr($masked, 0, 32), 'last4' => $last4,
            'expiry' => $state['expiry'] ?? null, 'is_primary' => $hasPrimary ? 0 : 1, 'status' => 'active', 'created_at' => H::now(), 'updated_at' => H::now(),
        ]);
        H::audit($ctx, 'billing.card_added', 'card', $id, ['last4' => $last4]);

        // A card added while the subscription is overdue (or access is closed) settles it immediately.
        $charged = null;
        $sub = $this->latestSub($t);
        if ($sub && $this->isOverdue($sub) && !$sub->charge_state) $charged = $this->chargeNow($sub);
        return H::json(['ok' => true, 'card' => $this->view(DB::table('payment_cards')->where('id', $id)->first()), 'charged' => $charged], 201);
    }

    public function makePrimary(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'billing.write');
        H::requireTenant($ctx);
        $t = $ctx->tenantId;
        $card = DB::table('payment_cards')->where('id', $ctx->params['id'])->where('tenant_id', $t)->where('status', 'active')->first();
        if (!$card) throw new ApiException(404, 'Karta topilmadi', 'not_found');
        DB::transaction(function () use ($t, $card) {
            DB::table('payment_cards')->where('tenant_id', $t)->update(['is_primary' => 0]);
            DB::table('payment_cards')->where('id', $card->id)->update(['is_primary' => 1, 'updated_at' => H::now()]);
        });
        H::audit($ctx, 'billing.card_primary', 'card', $card->id, ['last4' => $card->last4]);
        return H::json(['ok' => true]);
    }

    public function remove(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'billing.write');
        H::requireTenant($ctx);
        $t = $ctx->tenantId;
        $card = DB::table('payment_cards')->where('id', $ctx->params['id'])->where('tenant_id', $t)->where('status', '!=', 'removed')->first();
        if (!$card) throw new ApiException(404, 'Karta topilmadi', 'not_found');
        $sub = $this->latestSub($t);
        $others = DB::table('payment_cards')->where('tenant_id', $t)->where('status', 'active')->where('id', '!=', $card->id)->count();
        $paying = $sub && in_array($sub->status, ['active', 'trialing', 'past_due', 'grace'], true) && !$sub->complimentary && !$sub->cancel_at_period_end;
        if ($paying && $others === 0) {
            throw new ApiException(409, 'Oxirgi kartani o‘chirib bo‘lmaydi: avval boshqa karta qo‘shing yoki obunani bekor qiling', 'last_card');
        }
        try {
            $token = Crypt::decryptString($card->token_enc);
            (new ClickCardService())->deleteToken($token);
        } catch (\Throwable $e) {
            // Undecryptable token: nothing to delete at Click.
        }
        DB::transaction(function () use ($t, $card) {
            DB::table('payment_cards')->where('id', $card->id)->update(['status' => 'removed', 'is_primary' => 0, 'token_enc' => '', 'updated_at' => H::now()]);
            if ($card->is_primary) {
                $next = DB::table('payment_cards')->where('tenant_id', $t)->where('status', 'active')->orderBy('created_at')->first();
                if ($next) DB::table('payment_cards')->where('id', $next->id)->update(['is_primary' => 1]);
            }
        });
        H::audit($ctx, 'billing.card_removed', 'card', $card->id, ['last4' => $card->last4]);
        return H::json(['ok' => true]);
    }

    /** "Pay now" for an overdue subscription using the saved cards. */
    public function payNow(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'billing.write');
        H::requireTenant($ctx);
        $sub = $this->latestSub($ctx->tenantId);
        if (!$sub || !$this->isOverdue($sub)) throw new ApiException(409, 'To‘lanishi kerak bo‘lgan qarz yo‘q', 'nothing_due');
        if ($sub->charge_state) throw new ApiException(409, 'Oldingi to‘lov holati tekshirilmoqda', 'charge_pending_review');
        $result = $this->chargeNow($sub);
        if (!$result['ok']) throw new ApiException(422, $result['error'] ?: 'To‘lov amalga oshmadi', 'charge_failed');
        return H::json(['ok' => true, 'charged' => $result]);
    }

    /** Status for the in-app banner: any member may read it, it carries no card details. */
    public function status(Ctx $ctx)
    {
        $ctx->auth();
        H::requireTenant($ctx);
        $t = $ctx->tenantId;
        $sub = $this->latestSub($t);
        $out = ['state' => 'ok', 'has_card' => DB::table('payment_cards')->where('tenant_id', $t)->where('status', 'active')->exists()];
        if (!$sub) return H::json($out);
        $out += ['period_end' => $sub->period_end, 'complimentary' => (bool) $sub->complimentary, 'grace_until' => $sub->grace_until,
            'blocked_at' => $sub->blocked_at, 'data_retain_until' => $sub->data_retain_until, 'review' => (bool) $sub->charge_state];
        $soon = strtotime($sub->period_end . ' UTC') - time() <= max(1, (int) config('payments.billing.lgota_notice_days', 3)) * 86400;
        if ($sub->blocked_at) $out['state'] = 'blocked';
        elseif ($sub->status === 'past_due' || $sub->grace_until) $out['state'] = 'past_due';
        elseif ($sub->complimentary && !BillingPeriod::isOpenEnded($sub->period_end) && $soon) $out['state'] = 'lgota_ending';
        elseif (!$sub->complimentary && $sub->status !== 'cancelled' && $soon && !$out['has_card']) $out['state'] = 'no_card';
        return H::json($out);
    }

    private function isOverdue(object $sub): bool
    {
        return !$sub->complimentary && in_array($sub->status, ['active', 'trialing', 'past_due', 'grace'], true)
            && (strtotime($sub->period_end . ' UTC') <= time() || $sub->status === 'past_due');
    }

    private function chargeNow(object $sub): array
    {
        if (!Cache::add('paynow:' . $sub->id, 1, 30)) throw new ApiException(429, 'To‘lov jarayonda, biroz kuting', 'rate_limited');
        DB::table('subscriptions')->where('id', $sub->id)->update(['last_charge_attempt_at' => H::now()]);
        $fresh = DB::table('subscriptions')->where('id', $sub->id)->first();
        $r = (new CardBillingService())->renew($fresh, true);
        return ['ok' => (bool) $r['ok'], 'outcome' => $r['outcome'], 'error' => $r['error']];
    }

    private function luhn(string $n): bool
    {
        $sum = 0; $alt = false;
        for ($i = strlen($n) - 1; $i >= 0; $i--) {
            $d = (int) $n[$i];
            if ($alt) { $d *= 2; if ($d > 9) $d -= 9; }
            $sum += $d; $alt = !$alt;
        }
        return $sum % 10 === 0;
    }
}
