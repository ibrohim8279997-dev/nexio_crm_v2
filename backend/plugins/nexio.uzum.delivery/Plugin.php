<?php
namespace Nexio\UzumDelivery;

use App\Plugins\Contracts\OfficialPlugin;
use App\Plugins\PluginContext;
use App\Support\ApiException;
use Nexio\Delivery\Contracts\DeliveryProvider;

require_once dirname(__DIR__) . '/nexio.delivery/Contracts/DeliveryProvider.php';

/**
 * Nexio Uzum Delivery — adapter plugin.
 *
 * STATUS: NOT CONNECTED, and it says so.
 *
 * Research outcome (recorded so nobody has to re-derive it): no public, verifiable developer
 * documentation for an Uzum Tezkor / Uzum Delivery order-creation API could be found. Access
 * appears to be granted through a merchant contract and a partner onboarding process, not
 * self-service. Therefore:
 *
 *   - this adapter makes NO outbound network calls at all (its host allowlist is empty);
 *   - no endpoint, path, header or request schema is guessed or hardcoded;
 *   - every operation returns a precise, actionable error instead of a fake success;
 *   - the connection stays in status "not_configured" and the UI shows it as such.
 *
 * To make it work someone must supply: the official documentation, the API base host, the auth
 * scheme and merchant credentials. Until then the plugin is installable (so it appears in the
 * catalog and can be assigned per plan) but unusable, which is the honest state.
 */
class Plugin implements OfficialPlugin
{
    public const PLUGIN_ID = 'nexio.uzum.delivery';

    public function id(): string
    {
        return self::PLUGIN_ID;
    }

    public function operations(): array
    {
        // No operation is exposed: there is nothing this adapter can execute yet.
        return [];
    }

    public function execute(string $operation, array $input, PluginContext $context): array
    {
        throw new ApiException(
            409,
            'Интеграция с Uzum Delivery не подключена: официальная документация API и учётные данные недоступны.',
            'integration_not_available'
        );
    }

    /** Register the provider so the delivery layer can list it as "installed but not configured". */
    public function deliveryProviders(): array
    {
        return ['uzum' => new UzumProvider()];
    }

    public function health(PluginContext $context): array
    {
        return (new UzumProvider())->health($context);
    }

    public function onEvent(string $event, array $payload, PluginContext $context): void
    {
        // No subscriptions are declared in the manifest, so this is never called.
    }

    public function settingsSchema(): array
    {
        $raw = @file_get_contents(__DIR__ . '/manifest.json');
        $manifest = is_string($raw) ? json_decode($raw, true) : null;
        return is_array($manifest) ? (array) ($manifest['settings_schema'] ?? []) : [];
    }
}

/**
 * The provider object handed to nexio.delivery. Every method refuses honestly.
 */
class UzumProvider implements DeliveryProvider
{
    private const REASON = 'Uzum Delivery: официальная документация API не подтверждена, интеграция не подключена.';

    public function code(): string
    {
        return 'uzum';
    }

    public function name(): string
    {
        return 'Uzum Delivery';
    }

    public function isConfigured(PluginContext $context): bool
    {
        // Deliberately always false: there is no verified API to be configured against.
        return false;
    }

    public function capabilities(): array
    {
        return ['quote' => false, 'create' => false, 'cancel' => false, 'track' => false];
    }

    public function health(PluginContext $context): array
    {
        $enabled = (bool) $context->setting('integration_enabled', false);
        $hasHost = trim((string) ($context->setting('api_base_host', '') ?? '')) !== '';
        $hasMerchant = trim((string) ($context->setting('merchant_id', '') ?? '')) !== '';

        return [
            'status' => 'not_configured',
            'message' => self::REASON,
            'checks' => [
                ['name' => 'official_documentation', 'ok' => false, 'detail' => 'Публичная документация API Uzum Tezkor не найдена.'],
                ['name' => 'host_allowlist', 'ok' => false, 'detail' => 'Хост API не добавлен в allowlist исходящих запросов, сетевые вызовы запрещены.'],
                ['name' => 'merchant_credentials', 'ok' => $hasMerchant, 'detail' => $hasMerchant ? 'Идентификатор мерчанта заполнен.' : 'Идентификатор мерчанта не задан.'],
                ['name' => 'api_base_host', 'ok' => $hasHost, 'detail' => $hasHost ? 'Хост указан.' : 'Хост API не указан.'],
                ['name' => 'integration_enabled', 'ok' => $enabled, 'detail' => $enabled ? 'Интеграция помечена как включённая.' : 'Интеграция отключена.'],
            ],
            'next_steps' => array_values(array_filter([
                'Получить официальную документацию API у курьерской службы.',
                'Получить договор и учётные данные мерчанта.',
                'Добавить хост API в allowlist плагина (config/nexio.php) и реализовать транспорт адаптера.',
            ])),
        ];
    }

    public function quote(PluginContext $context, array $params): array
    {
        throw new ApiException(409, self::REASON, 'integration_not_available');
    }

    public function create(PluginContext $context, array $params): array
    {
        throw new ApiException(409, self::REASON, 'integration_not_available');
    }

    public function status(PluginContext $context, string $providerShipmentId): array
    {
        throw new ApiException(409, self::REASON, 'integration_not_available');
    }

    public function cancel(PluginContext $context, string $providerShipmentId): array
    {
        throw new ApiException(409, self::REASON, 'integration_not_available');
    }

    public function normaliseStatus(string $providerStatus): string
    {
        // No verified vocabulary exists, so nothing is invented: unknown stays "pending".
        return 'pending';
    }
}
