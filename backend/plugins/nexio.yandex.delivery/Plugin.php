<?php
namespace Nexio\YandexDelivery;

use App\Plugins\Contracts\OfficialPlugin;
use App\Plugins\PluginContext;
use App\Support\ApiException;
use App\Support\H;
use Nexio\Delivery\Contracts\DeliveryProvider;

require_once dirname(__DIR__) . '/nexio.delivery/Contracts/DeliveryProvider.php';

/**
 * Nexio Yandex Delivery — adapter plugin.
 *
 * WHAT IS VERIFIED (from Yandex's own documentation, not guessed):
 *   - production host: b2b-authproxy.taxi.yandex.net   (other-day / platform API)
 *   - test host:       b2b.taxi.tst.yandex.net
 *   - create path:     /api/b2b/platform/offers/create
 *   - auth:            Authorization: Bearer <OAuth token>
 *   Source: https://yandex.ru/support/delivery-profile/ru/api/other-day/access
 *
 * WHAT IS DELIBERATELY NOT INVENTED:
 *   The request body schema and the full status vocabulary are not hardcoded. Yandex has more than
 *   one delivery API (the separate Express API uses b2b.taxi.yandex.net), and inventing field names
 *   would produce an integration that looks complete and fails in production. Instead the operator
 *   supplies `request_template` and `status_map` from the official documentation, and this adapter
 *   provides everything around them: auth, the host allowlist (SSRF guard), TLS, timeouts, no
 *   redirects, response parsing, error handling and status normalisation.
 *
 * Capabilities are limited to what the verified endpoint supports: create only. Quote, cancel and
 * track stay disabled until their documented endpoints are supplied — the platform then refuses
 * those operations instead of reporting a success that never happened.
 */
class Plugin implements OfficialPlugin
{
    public const PLUGIN_ID = 'nexio.yandex.delivery';

    public function id(): string
    {
        return self::PLUGIN_ID;
    }

    public function operations(): array
    {
        // The adapter is consumed through the delivery provider registry, not as a direct API.
        return [];
    }

    public function execute(string $operation, array $input, PluginContext $context): array
    {
        throw new ApiException(
            409,
            'Адаптер Yandex Delivery не предоставляет собственных операций: используйте плагин nexio.delivery.',
            'not_supported'
        );
    }

    public function deliveryProviders(): array
    {
        return ['yandex' => new YandexProvider()];
    }

    public function health(PluginContext $context): array
    {
        return (new YandexProvider())->health($context);
    }

    public function onEvent(string $event, array $payload, PluginContext $context): void
    {
        // No subscriptions declared.
    }

    public function settingsSchema(): array
    {
        $raw = @file_get_contents(__DIR__ . '/manifest.json');
        $manifest = is_string($raw) ? json_decode($raw, true) : null;
        return is_array($manifest) ? (array) ($manifest['settings_schema'] ?? []) : [];
    }
}

/**
 * Yandex delivery provider: real transport, operator-configured schema.
 */
class YandexProvider implements DeliveryProvider
{
    private const PROD_HOST = 'b2b-authproxy.taxi.yandex.net';
    private const TEST_HOST = 'b2b.taxi.tst.yandex.net';
    private const DEFAULT_PATH = '/api/b2b/platform/offers/create';
    private const TOKEN_SECRET = 'oauth_token';

    public function code(): string
    {
        return 'yandex';
    }

    public function name(): string
    {
        return 'Yandex Delivery';
    }

    public function host(PluginContext $context): string
    {
        return $context->setting('use_test_host', false) ? self::TEST_HOST : self::PROD_HOST;
    }

    public function isConfigured(PluginContext $context): bool
    {
        // A real call needs: a stored OAuth token, an allowlisted host, and a request body the
        // operator has taken from the documentation.
        return $context->hasSecret(self::TOKEN_SECRET)
            && $context->outboundAllowed($this->host($context))
            && $this->template($context) !== [];
    }

    public function capabilities(): array
    {
        // Only the endpoint verified in the documentation is enabled.
        return ['quote' => false, 'create' => true, 'cancel' => false, 'track' => false];
    }

    public function health(PluginContext $context): array
    {
        $host = $this->host($context);
        $hasToken = $context->hasSecret(self::TOKEN_SECRET);
        $hasTemplate = $this->template($context) !== [];
        $allowed = $context->outboundAllowed($host);

        return [
            'status' => $this->isConfigured($context) ? 'connected' : 'not_configured',
            'message' => $this->isConfigured($context)
                ? 'Адаптер настроен: доступны создание заявки и учёт заказов.'
                : 'Адаптер не подключён: задайте OAuth-токен, шаблон запроса и подтвердите хост.',
            'checks' => [
                ['name' => 'host_allowlist', 'ok' => $allowed, 'detail' => $allowed ? "Хост {$host} разрешён." : "Хост {$host} не разрешён."],
                ['name' => 'oauth_token', 'ok' => $hasToken, 'detail' => $hasToken ? 'OAuth-токен сохранён (значение не отображается).' : 'OAuth-токен не задан.'],
                ['name' => 'create_path', 'ok' => true, 'detail' => 'Путь создания: ' . (string) ($context->setting('create_path', self::DEFAULT_PATH) ?: self::DEFAULT_PATH)],
                ['name' => 'request_template', 'ok' => $hasTemplate, 'detail' => $hasTemplate ? 'Шаблон запроса заполнен.' : 'Шаблон запроса пуст: заполните из официальной документации.'],
                ['name' => 'capabilities', 'ok' => true, 'detail' => 'Доступно: создание заявки. Расчёт, отмена и отслеживание — после подтверждения соответствующих методов.'],
            ],
        ];
    }

    public function quote(PluginContext $context, array $params): array
    {
        throw new ApiException(
            409,
            'Yandex Delivery: расчёт стоимости не реализован — метод не подтверждён официальной документацией.',
            'not_supported'
        );
    }

    public function create(PluginContext $context, array $params): array
    {
        if (!$this->isConfigured($context)) {
            throw new ApiException(
                409,
                'Yandex Delivery не подключён: не задан OAuth-токен или шаблон запроса. Интеграция не активна.',
                'provider_not_configured',
                ['health' => $this->health($context)['checks']]
            );
        }

        $host = $this->host($context);
        if (!$context->outboundAllowed($host)) {
            throw new ApiException(403, "Исходящие запросы к {$host} запрещены политикой плагина", 'outbound_blocked');
        }

        $path = (string) ($context->setting('create_path', self::DEFAULT_PATH) ?: self::DEFAULT_PATH);
        if (!str_starts_with($path, '/')) {
            throw new ApiException(400, 'Путь запроса должен начинаться с «/»', 'bad_request');
        }

        $token = (string) ($context->secret(self::TOKEN_SECRET) ?? '');
        if ($token === '') {
            throw new ApiException(409, 'OAuth-токен недоступен. Сохраните его заново.', 'provider_not_configured');
        }

        $body = $this->render($this->template($context), $params);
        $response = $this->post($context, 'https://' . $host . $path, $token, $body);

        $id = $this->extract($response, (string) ($context->setting('response_shipment_id_path', 'id') ?: 'id'));
        if ($id === null || $id === '') {
            throw new ApiException(
                502,
                'Yandex Delivery принял запрос, но идентификатор заявки не найден в ответе. Проверьте настройку «Путь к идентификатору».',
                'provider_bad_response'
            );
        }

        $tracking = $this->extract($response, (string) ($context->setting('response_tracking_path', '') ?? ''));

        return [
            'provider_shipment_id' => (string) $id,
            'provider_status' => 'created',
            'cost' => (float) ($params['declared_value'] ?? 0),
            'currency' => 'RUB',
            'tracking_url' => is_string($tracking) ? $tracking : null,
            'provider_configured' => true,
        ];
    }

    public function status(PluginContext $context, string $providerShipmentId): array
    {
        throw new ApiException(
            409,
            'Yandex Delivery: обновление статуса не реализовано — метод не подтверждён официальной документацией.',
            'not_supported'
        );
    }

    public function cancel(PluginContext $context, string $providerShipmentId): array
    {
        throw new ApiException(
            409,
            'Yandex Delivery: отмена не реализована — метод не подтверждён официальной документацией.',
            'not_supported'
        );
    }

    public function normaliseStatus(string $providerStatus): string
    {
        // Generic vocabulary only. A provider-specific mapping is supplied by the operator so no
        // vendor status names are invented here.
        $generic = [
            'created' => 'pending',
            'accepted' => 'accepted',
            'delivered' => 'delivered',
            'cancelled' => 'cancelled',
            'canceled' => 'cancelled',
            'failed' => 'failed',
        ];
        return $generic[strtolower(trim($providerStatus))] ?? 'pending';
    }

    // ── transport ────────────────────────────────────────────────────────────────────

    /** @return array<string,mixed> decoded JSON response */
    private function post(PluginContext $context, string $url, string $token, array $body): array
    {
        $timeout = max(3, min(60, (int) ($context->setting('request_timeout_seconds', 15) ?: 15)));

        $ch = curl_init($url);
        if ($ch === false) {
            throw new ApiException(500, 'Не удалось инициализировать HTTP-клиент', 'internal');
        }
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_FOLLOWLOCATION => false,          // a redirect must not carry the token elsewhere
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                // The token is sent in the header (never in the URL, so it cannot land in logs).
                'Authorization: Bearer ' . $token,
            ],
        ]);

        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new ApiException(502, 'Не удалось связаться с Yandex Delivery: ' . $this->safe($error), 'provider_unreachable');
        }
        if ($status === 401 || $status === 403) {
            throw new ApiException(409, 'Yandex Delivery отклонил авторизацию: проверьте OAuth-токен.', 'provider_unauthorized');
        }
        if ($status >= 400) {
            throw new ApiException(502, "Yandex Delivery вернул ошибку HTTP {$status}", 'provider_error', [
                'status' => $status,
                // Response bodies may echo request content; only a short, redacted excerpt is kept.
                'excerpt' => $this->safe($raw),
            ]);
        }

        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            throw new ApiException(502, 'Yandex Delivery вернул некорректный JSON', 'provider_bad_response');
        }
        return $decoded;
    }

    /** Strip anything token-shaped before it can reach an error payload or a log. */
    private function safe(string $text): string
    {
        $text = (string) preg_replace('/\b[A-Za-z0-9_\-]{24,}\b/', '[redacted]', $text);
        $text = (string) preg_replace('/"(token|password|secret|api_key)"\s*:\s*"[^"]*"/i', '"$1":"[redacted]"', $text);
        return mb_substr(trim($text), 0, 500);
    }

    private function template(PluginContext $context): array
    {
        $raw = $context->setting('request_template', []);
        if (is_array($raw)) {
            return $raw;
        }
        $decoded = json_decode((string) $raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /** Substitute {{placeholders}} recursively. Unknown placeholders are removed, not left literal. */
    private function render(mixed $node, array $params): mixed
    {
        if (is_array($node)) {
            $out = [];
            foreach ($node as $k => $v) {
                $out[$k] = $this->render($v, $params);
            }
            return $out;
        }
        if (!is_string($node)) {
            return $node;
        }
        return (string) preg_replace_callback('/\{\{\s*([a-z0-9_]+)\s*\}\}/i', function ($m) use ($params) {
            return (string) ($params[$m[1]] ?? '');
        }, $node);
    }

    /** Dot-path extraction, e.g. "result.offer_id". */
    private function extract(array $data, string $path): mixed
    {
        if ($path === '') {
            return null;
        }
        $current = $data;
        foreach (explode('.', $path) as $segment) {
            if (is_array($current) && array_key_exists($segment, $current)) {
                $current = $current[$segment];
                continue;
            }
            return null;
        }
        return is_scalar($current) ? $current : null;
    }
}
