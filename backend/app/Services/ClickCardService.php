<?php

namespace App\Services;

use App\Support\ApiException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Click Merchant API card tokens: enroll a card (SMS-confirmed), charge by token, delete a token.
 *
 * Card data policy: the card number and expiry pass through this class once, in memory, to be forwarded to Click.
 * They are never logged, stored or put in an exception message. The CVV is not collected at all: Click's
 * token flow does not use it, and PCI DSS forbids keeping it after authorisation.
 *
 * NOTE: endpoint paths and field names follow Click's Merchant API as documented for card tokens
 * (card_token/request, /verify, /payment, DELETE /card_token/{service}/{token}). Verify them against the
 * current docs and the test cabinet before enabling in production.
 */
class ClickCardService
{
    public const PAID = 'paid';
    public const DECLINED = 'declined';
    public const UNKNOWN = 'unknown';   // timeout / 5xx: the money may or may not have moved

    public function configured(): bool
    {
        foreach (['service_id', 'secret_key', 'merchant_user_id'] as $k) {
            if (empty(config("payments.click.{$k}"))) return false;
        }
        return true;
    }

    /** Step 1: Click sends an SMS code to the number linked to the card. @return array{token:string,phone:?string} */
    public function requestToken(string $cardNumber, string $expiry): array
    {
        $r = $this->call('POST', 'card_token/request', [
            'service_id' => (int) config('payments.click.service_id'),
            'card_number' => $cardNumber,
            'expire_date' => $expiry,
            'temporary' => 0,
        ]);
        if (empty($r['card_token'])) throw new ApiException(502, 'Click karta tokenini qaytarmadi', 'card_provider_error');
        return ['token' => (string) $r['card_token'], 'phone' => isset($r['phone_number']) ? (string) $r['phone_number'] : null];
    }

    /** Step 2: confirm with the SMS code. Returns the masked card number. */
    public function verifyToken(string $token, string $smsCode): ?string
    {
        $r = $this->call('POST', 'card_token/verify', [
            'service_id' => (int) config('payments.click.service_id'),
            'card_token' => $token,
            'sms_code' => (int) $smsCode,
        ]);
        return isset($r['card_number']) ? (string) $r['card_number'] : null;
    }

    /**
     * Charges a verified token. $reference is our invoice id: Click echoes it back and it ties the payment to the invoice.
     * @return array{outcome:string,payment_id:?string,error:?string}
     */
    public function charge(string $token, float $amountUzs, string $reference): array
    {
        try {
            $r = $this->call('POST', 'card_token/payment', [
                'service_id' => (int) config('payments.click.service_id'),
                'card_token' => $token,
                'amount' => (float) number_format($amountUzs, 2, '.', ''),
                'transaction_parameter' => $reference,
            ], false);
        } catch (ApiException $e) {
            // Network failure or a 5xx: the charge may have gone through. Never treat this as a plain decline.
            if ($e->errorCode === 'card_provider_unreachable') return ['outcome' => self::UNKNOWN, 'payment_id' => null, 'error' => $e->getMessage()];
            return ['outcome' => self::DECLINED, 'payment_id' => null, 'error' => $e->getMessage()];
        }
        $code = (int) ($r['error_code'] ?? -1);
        if ($code === 0 && !empty($r['payment_id'])) return ['outcome' => self::PAID, 'payment_id' => (string) $r['payment_id'], 'error' => null];
        return ['outcome' => self::DECLINED, 'payment_id' => null, 'error' => (string) ($r['error_note'] ?? ('Click xato kodi ' . $code))];
    }

    public function deleteToken(string $token): void
    {
        try {
            $this->call('DELETE', 'card_token/' . (int) config('payments.click.service_id') . '/' . rawurlencode($token), null);
        } catch (\Throwable $e) {
            // The card is removed from our side regardless; a stale token at Click is harmless without our secret.
            Log::warning('Click card token delete failed', ['exception' => $e::class]);
        }
    }

    /** @param array<string,mixed>|null $body */
    private function call(string $method, string $path, ?array $body, bool $throwOnErrorCode = true): array
    {
        if (!$this->configured()) throw new ApiException(503, 'Click karta to‘lovi sozlanmagan', 'payment_not_configured');
        $ts = (string) time();
        $headers = [
            'Accept' => 'application/json',
            'Auth' => config('payments.click.merchant_user_id') . ':' . sha1($ts . config('payments.click.secret_key')) . ':' . $ts,
        ];
        try {
            $http = Http::withHeaders($headers)->acceptJson()->connectTimeout(5)->timeout(20);
            $url = rtrim((string) config('payments.click.api_base'), '/') . '/' . ltrim($path, '/');
            $res = $method === 'DELETE' ? $http->delete($url) : $http->asJson()->post($url, $body ?? []);
        } catch (\Throwable $e) {
            // Exception text can carry the request body (card number) in some HTTP clients: log the class only.
            Log::warning('Click card API unreachable', ['exception' => $e::class, 'path' => $path]);
            throw new ApiException(503, 'Click bilan aloqa o‘rnatilmadi', 'card_provider_unreachable');
        }
        if ($res->serverError()) {
            Log::warning('Click card API 5xx', ['status' => $res->status(), 'path' => $path]);
            throw new ApiException(503, 'Click vaqtincha ishlamayapti', 'card_provider_unreachable');
        }
        $json = $res->json();
        $json = is_array($json) ? $json : [];
        $code = (int) ($json['error_code'] ?? ($res->successful() ? 0 : -1));
        if ($throwOnErrorCode && $code !== 0) {
            throw new ApiException(422, $this->friendly($code, (string) ($json['error_note'] ?? '')), 'card_rejected');
        }
        return $json;
    }

    private function friendly(int $code, string $note): string
    {
        return match ($code) {
            -301, -302 => 'Karta ma’lumotlari noto‘g‘ri yoki karta faol emas',
            -303 => 'SMS kod noto‘g‘ri',
            -304 => 'SMS kod muddati tugagan, kartani qaytadan kiriting',
            -5017, -5014 => 'Kartada mablag‘ yetarli emas yoki karta bloklangan',
            default => $note !== '' ? mb_substr($note, 0, 140) : 'Karta rad etildi',
        };
    }
}
