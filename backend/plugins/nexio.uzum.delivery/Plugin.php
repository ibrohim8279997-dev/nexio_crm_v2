<?php

namespace Nexio\UzumDelivery;

use App\Plugins\Contracts\OfficialPlugin;
use App\Plugins\PluginContext;
use App\Support\ApiException;
use App\Support\OutboundEndpoint;
use Nexio\Delivery\Contracts\DeliveryProvider;

require_once dirname(__DIR__) . '/nexio.delivery/Contracts/DeliveryProvider.php';

/** Uzum adapter. Endpoint and token are supplied per installed connection by the CRM user. */
class Plugin implements OfficialPlugin
{
    public const PLUGIN_ID = 'nexio.uzum.delivery';
    public function id(): string { return self::PLUGIN_ID; }
    public function operations(): array { return []; }
    public function execute(string $operation, array $input, PluginContext $context): array
    {
        throw new ApiException(409, 'Uzum operatsiyalari nexio.delivery oynasi orqali ishlatiladi', 'not_supported');
    }
    public function deliveryProviders(): array { return ['uzum' => new UzumProvider()]; }
    public function health(PluginContext $context): array { return (new UzumProvider())->health($context); }
    public function onEvent(string $event, array $payload, PluginContext $context): void {}
    public function settingsSchema(): array
    {
        $raw = @file_get_contents(__DIR__ . '/manifest.json');
        $manifest = is_string($raw) ? json_decode($raw, true) : null;
        return is_array($manifest) ? (array) ($manifest['settings_schema'] ?? []) : [];
    }
}

class UzumProvider implements DeliveryProvider
{
    public function code(): string { return 'uzum'; }
    public function name(): string { return 'Uzum Delivery'; }

    public function isConfigured(PluginContext $context): bool
    {
        return $this->endpoint($context) !== null && $this->token($context) !== '';
    }

    public function capabilities(): array
    {
        return ['quote' => false, 'create' => true, 'cancel' => false, 'track' => false];
    }

    public function health(PluginContext $context): array
    {
        $endpoint = $this->endpoint($context);
        $token = $this->token($context);
        $configured = $endpoint !== null && $token !== '';
        return [
            'status' => $configured ? 'connected' : 'not_configured',
            'message' => $configured
                ? 'Uzum connection tayyor. Buyurtma siz kiritgan endpointga yuboriladi.'
                : 'Uzum plugin connection ichida HTTPS API URL va API token kiriting.',
            'checks' => [
                ['name' => 'api_url', 'ok' => $endpoint !== null, 'detail' => $endpoint ? 'HTTPS endpoint qabul qilindi.' : 'HTTPS API URL kiritilmagan yoki xavfsiz emas.'],
                ['name' => 'api_token', 'ok' => $token !== '', 'detail' => $token !== '' ? 'Token serverda shifrlangan.' : 'API token kiritilmagan.'],
                ['name' => 'create_transport', 'ok' => $configured, 'detail' => $configured ? 'Kuryer chaqirish transporti tayyor.' : 'Credentiallar kiritilgach transport tayyor bo‘ladi.'],
            ],
        ];
    }

    public function quote(PluginContext $context, array $params): array
    {
        throw new ApiException(409, 'Uzum API uchun quote endpointi connection sozlamasida berilmagan', 'not_supported');
    }

    public function create(PluginContext $context, array $params): array
    {
        if (!$this->isConfigured($context)) throw new ApiException(409, 'Uzum connection sozlanmagan', 'provider_not_configured');
        $config = $context->connectionConfig();
        $template = $this->template($context);
        $body = $template === [] ? $params : $this->render($template, $params);
        $response = $this->request($context, 'POST', (string) ($config['create_path'] ?? '/deliveries'), $body);
        $id = $this->extract($response, (string) ($config['response_shipment_id_path'] ?? 'id'));
        if ($id === null || $id === '') throw new ApiException(502, 'Uzum API javobida delivery ID topilmadi', 'provider_bad_response');
        return [
            'provider_shipment_id' => (string) $id,
            'provider_status' => (string) ($this->extract($response, (string) ($config['response_status_path'] ?? 'status')) ?? 'created'),
            'cost' => (float) ($this->extract($response, (string) ($config['response_cost_path'] ?? 'cost')) ?? 0),
            'currency' => (string) ($config['currency'] ?? 'UZS'),
            'tracking_url' => $this->extract($response, (string) ($config['response_tracking_path'] ?? 'tracking_url')),
            'provider_configured' => true,
        ];
    }

    public function status(PluginContext $context, string $providerShipmentId): array
    {
        throw new ApiException(409, 'Uzum status endpointi connection sozlamasida berilmagan', 'not_supported');
    }
    public function cancel(PluginContext $context, string $providerShipmentId): array
    {
        throw new ApiException(409, 'Uzum cancel endpointi connection sozlamasida berilmagan', 'not_supported');
    }
    public function normaliseStatus(string $providerStatus): string
    {
        return match (strtolower(trim($providerStatus))) {
            'created', 'new', 'pending' => 'pending',
            'accepted', 'assigned' => 'accepted',
            'delivered', 'completed' => 'delivered',
            'cancelled', 'canceled' => 'cancelled',
            'failed', 'rejected' => 'failed',
            default => 'pending',
        };
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
    private function template(PluginContext $context): array
    {
        $raw = $context->setting('request_template', []);
        if (is_array($raw)) return $raw;
        $decoded = json_decode((string) $raw, true);
        return is_array($decoded) ? $decoded : [];
    }
    private function render(mixed $node, array $params): mixed
    {
        if (is_array($node)) { $out = []; foreach ($node as $key => $value) $out[$key] = $this->render($value, $params); return $out; }
        if (!is_string($node)) return $node;
        return (string) preg_replace_callback('/\{\{\s*([a-z0-9_]+)\s*\}\}/i', fn ($m) => (string) ($params[$m[1]] ?? ''), $node);
    }
    private function request(PluginContext $context, string $method, string $path, array $body): array
    {
        $endpoint = $this->endpoint($context); $token = $this->token($context);
        if (!$endpoint || $token === '') throw new ApiException(409, 'Uzum connection sozlanmagan', 'provider_not_configured');
        if (!str_starts_with($path, '/')) $path = '/' . $path;
        $ch = curl_init($endpoint['url'] . $path);
        if ($ch === false) throw new ApiException(502, 'Uzum HTTP klienti ishga tushmadi', 'provider_unreachable');
        $authHeader = (string) ($context->setting('auth_header', 'Authorization') ?: 'Authorization');
        $authPrefix = (string) ($context->setting('auth_prefix', 'Bearer') ?? 'Bearer');
        curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST => $method, CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 30, CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE), CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json', $authHeader . ': ' . trim($authPrefix . ' ' . $token)]]);
        $raw = curl_exec($ch); $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE); $error = curl_error($ch); curl_close($ch);
        if ($raw === false) throw new ApiException(502, 'Uzum API ga ulanib bo‘lmadi: ' . mb_substr($error, 0, 120), 'provider_unreachable');
        if ($status === 401 || $status === 403) throw new ApiException(409, 'Uzum API credentiallarini tekshiring', 'provider_unauthorized');
        if ($status >= 400) throw new ApiException(502, "Uzum API HTTP {$status} xatolik qaytardi", 'provider_error');
        $decoded = json_decode((string) $raw, true);
        return is_array($decoded) ? $decoded : [];
    }
    private function extract(array $data, string $path): mixed
    {
        $value = $data;
        foreach (explode('.', trim($path, '.')) as $segment) { if (!is_array($value) || !array_key_exists($segment, $value)) return null; $value = $value[$segment]; }
        return is_scalar($value) ? $value : null;
    }
}
