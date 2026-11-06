<?php

namespace Nexio\Telephony\Providers;

use App\Plugins\PluginContext;
use App\Support\ApiException;
use App\Support\H;
use App\Support\OutboundEndpoint;

/**
 * Vendor-neutral HTTP bridge. The company supplies its telephony vendor's HTTPS endpoint and
 * token in the plugin connection; the browser never receives or stores either value.
 */
class HttpTelephonyProvider implements TelephonyProvider
{
    public function code(): string { return 'http'; }

    public function isConfigured(PluginContext $context): bool
    {
        return $this->endpoint($context) !== null && $this->token($context) !== '';
    }

    public function capabilities(): array
    {
        return ['outbound' => true, 'inbound' => false, 'recordings' => false, 'transfer' => false, 'numbers' => true];
    }

    public function health(PluginContext $context): array
    {
        $endpoint = $this->endpoint($context);
        $configured = $endpoint !== null && $this->token($context) !== '';
        return [
            'status' => $configured ? 'connected' : 'not_configured',
            'message' => $configured
                ? 'Telefoniya API credentiallari saqlangan. Test qo‘ng‘irog‘i faqat siz buyruq berganingizda yuboriladi.'
                : 'Telefoniya connection ichida HTTPS API URL va API token kiriting.',
            'checks' => [
                ['name' => 'api_url', 'ok' => $endpoint !== null, 'detail' => $endpoint ? 'HTTPS endpoint qabul qilindi.' : 'HTTPS API URL kiritilmagan yoki xavfsiz emas.'],
                ['name' => 'api_token', 'ok' => $this->token($context) !== '', 'detail' => $this->token($context) !== '' ? 'Token serverda shifrlangan.' : 'API token kiritilmagan.'],
                ['name' => 'outbound_transport', 'ok' => $configured, 'detail' => $configured ? 'Qo‘ng‘iroq yuborish transporti tayyor.' : 'Transport credentiallar tugagach tayyor bo‘ladi.'],
            ],
        ];
    }

    public function placeCall(PluginContext $context, array $params): array
    {
        $response = $this->request($context, 'POST', (string) ($context->connectionConfig()['place_call_path'] ?? '/calls'), [
            'from' => (string) ($params['from'] ?? ''),
            'to' => (string) ($params['to'] ?? ''),
            'contact_id' => $params['contact_id'] ?? null,
            'order_id' => $params['order_id'] ?? null,
        ]);
        $providerId = $this->extract($response, (string) ($context->connectionConfig()['call_id_path'] ?? 'id'));
        if ($providerId === null || $providerId === '') {
            throw new ApiException(502, 'Telefoniya API javobida qo‘ng‘iroq ID topilmadi', 'provider_bad_response');
        }
        return [
            'provider_call_id' => (string) $providerId,
            'status' => $this->status($response, 'queued'),
            'provider_configured' => true,
            'started_at' => gmdate('Y-m-d H:i:s'),
        ];
    }

    public function endCall(PluginContext $context, string $providerCallId): array
    {
        $config = $context->connectionConfig();
        $path = (string) ($config['end_call_path'] ?? '/calls/{id}/end');
        $path = str_replace('{id}', rawurlencode($providerCallId), $path);
        $response = $this->request($context, 'POST', $path, ['call_id' => $providerCallId]);
        return [
            'status' => $this->status($response, 'completed'),
            'duration_seconds' => (int) ($this->extract($response, (string) ($config['duration_path'] ?? 'duration_seconds')) ?? 0),
            'ended_at' => gmdate('Y-m-d H:i:s'),
            'hangup_cause' => (string) ($this->extract($response, 'hangup_cause') ?? 'provider'),
        ];
    }

    public function numbers(PluginContext $context): array
    {
        $config = $context->connectionConfig();
        $response = $this->request($context, 'GET', (string) ($config['numbers_path'] ?? '/numbers'));
        $items = $response['data'] ?? $response['numbers'] ?? $response;
        if (!is_array($items)) return [];
        $out = [];
        foreach ($items as $item) {
            if (!is_array($item)) continue;
            $number = $item['phone_number'] ?? $item['number'] ?? null;
            if ($number) $out[] = ['phone_number' => (string) $number, 'status' => (string) ($item['status'] ?? 'active'), 'capabilities' => (array) ($item['capabilities'] ?? ['outbound' => true])];
        }
        return $out;
    }

    private function endpoint(PluginContext $context): ?array
    {
        try { return OutboundEndpoint::parse((string) ($context->connectionConfig()['api_url'] ?? '')); }
        catch (\Throwable) { return null; }
    }

    private function token(PluginContext $context): string
    {
        return (string) ($context->secret('api_token') ?? $context->secret('api_key') ?? '');
    }

    private function request(PluginContext $context, string $method, string $path, ?array $body = null): array
    {
        $endpoint = $this->endpoint($context);
        $token = $this->token($context);
        if (!$endpoint || $token === '') throw new ApiException(409, 'Telefoniya connection sozlanmagan', 'provider_not_configured');
        if (!str_starts_with($path, '/')) $path = '/' . $path;
        $url = $endpoint['url'] . $path;
        $ch = curl_init($url);
        if ($ch === false) throw new ApiException(502, 'Telefoniya HTTP klienti ishga tushmadi', 'provider_unreachable');
        $headers = ['Accept: application/json', 'Authorization: Bearer ' . $token];
        $options = [CURLOPT_CUSTOMREQUEST => $method, CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 30, CURLOPT_HTTPHEADER => $headers];
        if ($body !== null) { $options[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_UNICODE); $headers[] = 'Content-Type: application/json'; $options[CURLOPT_HTTPHEADER] = $headers; }
        curl_setopt_array($ch, $options);
        $raw = curl_exec($ch); $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE); $error = curl_error($ch); curl_close($ch);
        if ($raw === false) throw new ApiException(502, 'Telefoniya API ga ulanib bo‘lmadi: ' . mb_substr($error, 0, 120), 'provider_unreachable');
        if ($status === 401 || $status === 403) throw new ApiException(409, 'Telefoniya API credentiallarini tekshiring', 'provider_unauthorized');
        if ($status >= 400) throw new ApiException(502, "Telefoniya API HTTP {$status} xatolik qaytardi", 'provider_error');
        $decoded = json_decode((string) $raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function extract(array $data, string $path): mixed
    {
        $value = $data;
        foreach (explode('.', trim($path, '.')) as $segment) { if (!is_array($value) || !array_key_exists($segment, $value)) return null; $value = $value[$segment]; }
        return is_scalar($value) ? $value : null;
    }

    private function status(array $response, string $fallback): string
    {
        $value = strtolower((string) ($response['status'] ?? $response['state'] ?? $fallback));
        return in_array($value, ['queued', 'ringing', 'in_progress', 'completed', 'failed', 'cancelled'], true) ? $value : $fallback;
    }
}
