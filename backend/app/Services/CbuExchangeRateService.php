<?php

namespace App\Services;

use App\Support\ApiException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CbuExchangeRateService
{
    public function quote(float $amount, string $currency): array
    {
        $currency = strtoupper(trim($currency));
        if ($currency === 'UZS') {
            return ['source_currency' => 'UZS', 'source_amount' => round($amount, 2), 'target_currency' => 'UZS', 'target_amount' => round($amount, 2), 'rate' => 1, 'nominal' => 1, 'rate_date' => gmdate('Y-m-d'), 'source' => 'local'];
        }
        if ($currency !== 'USD') throw new ApiException(422, 'Click uchun faqat UZS va USD valyutalari qo‘llanadi', 'unsupported_currency');

        $rate = Cache::remember('cbu.exchange.USD', now()->addHours(6), function () {
            $response = Http::acceptJson()->timeout((int) config('payments.cbu.timeout', 8))->retry(2, 150)->get(rtrim((string) config('payments.cbu.url'), '/') . '/USD/');
            if (!$response->successful()) throw new ApiException(503, 'CBU valyuta kursini olishning imkoni bo‘lmadi', 'exchange_rate_unavailable');
            $payload = $response->json();
            $row = is_array($payload) && isset($payload[0]) ? $payload[0] : $payload;
            $numericRate = (float) str_replace(',', '.', (string) (is_array($row) ? ($row['Rate'] ?? $row['rate'] ?? 0) : 0));
            $nominal = max(1, (float) str_replace(',', '.', (string) (is_array($row) ? ($row['Nominal'] ?? $row['nominal'] ?? 1) : 1)));
            if ($numericRate <= 0) throw new ApiException(503, 'CBU USD kursi noto‘g‘ri qaytdi', 'exchange_rate_invalid');
            return ['rate' => $numericRate, 'nominal' => $nominal, 'rate_date' => (string) (is_array($row) ? ($row['Date'] ?? $row['date'] ?? gmdate('Y-m-d')) : gmdate('Y-m-d')), 'source' => 'cbu.uz'];
        });

        $target = round(($amount * $rate['rate']) / $rate['nominal'], 2);
        if ($target <= 0) throw new ApiException(422, 'Click uchun hisoblangan summa nolga teng', 'invalid_amount');
        return ['source_currency' => 'USD', 'source_amount' => round($amount, 2), 'target_currency' => 'UZS', 'target_amount' => $target, 'rate' => $rate['rate'], 'nominal' => $rate['nominal'], 'rate_date' => $rate['rate_date'], 'source' => $rate['source']];
    }
}
