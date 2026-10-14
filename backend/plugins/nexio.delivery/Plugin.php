<?php
namespace Nexio\Delivery;

use App\Plugins\Contracts\OfficialPlugin;
use App\Plugins\PluginContext;
use App\Support\ApiException;
use App\Support\H;
use Illuminate\Support\Facades\DB;
use Nexio\Delivery\Contracts\DeliveryProvider;
use Nexio\Delivery\Providers\ProviderRegistry;

require_once __DIR__ . '/Contracts/DeliveryProvider.php';
require_once __DIR__ . '/Providers/SandboxProvider.php';
require_once __DIR__ . '/Providers/ProviderRegistry.php';

/**
 * Nexio Delivery — the universal delivery layer.
 *
 * Owns the whole delivery lifecycle in provider-neutral terms and normalises every provider status
 * onto the universal set: pending, accepted, courier_assigned, picked_up, in_transit, delivered,
 * cancelled, failed. Concrete carriers live in adapter plugins that depend on this one, so no
 * carrier-specific logic is duplicated here.
 */
class Plugin implements OfficialPlugin
{
    public const PLUGIN_ID = 'nexio.delivery';

    public const UNIVERSAL_STATUSES = ['pending', 'accepted', 'courier_assigned', 'picked_up', 'in_transit', 'delivered', 'cancelled', 'failed'];

    /** Statuses from which cancellation is impossible. */
    private const TERMINAL = ['delivered', 'cancelled', 'failed'];

    public function id(): string
    {
        return self::PLUGIN_ID;
    }

    public function operations(): array
    {
        return [
            'quote' => [
                'permission' => 'delivery.read',
                'metric' => 'delivery_quotes',
                'requires_connection' => false,
                'description' => 'Рассчитать стоимость доставки',
            ],
            'create' => [
                'permission' => 'delivery.create',
                'metric' => 'delivery_orders',
                'requires_connection' => false,
                'description' => 'Создать доставку',
            ],
            'list' => [
                'permission' => 'delivery.read',
                'metric' => null,
                'requires_connection' => false,
                'description' => 'Список доставок',
            ],
            'get' => [
                'permission' => 'delivery.read',
                'metric' => null,
                'requires_connection' => false,
                'description' => 'Карточка доставки',
            ],
            'refresh_status' => [
                'permission' => 'delivery.track',
                'metric' => null,
                'requires_connection' => false,
                'description' => 'Обновить статус у провайдера',
            ],
            'cancel' => [
                'permission' => 'delivery.cancel',
                'metric' => 'delivery_cancellations',
                'requires_connection' => false,
                'description' => 'Отменить доставку',
            ],
            'provider_health' => [
                'permission' => 'delivery.read',
                'metric' => null,
                'requires_connection' => false,
                'description' => 'Состояние провайдера доставки',
            ],
            'list_providers' => [
                'permission' => 'delivery.read',
                'metric' => null,
                'requires_connection' => false,
                'description' => 'Доступные провайдеры',
            ],
        ];
    }

    public function execute(string $operation, array $input, PluginContext $context): array
    {
        return match ($operation) {
            'quote' => $this->quote($input, $context),
            'create' => $this->create($input, $context),
            'list' => $this->listShipments($input, $context),
            'get' => $this->getShipment($input, $context),
            'refresh_status' => $this->refreshStatus($input, $context),
            'cancel' => $this->cancel($input, $context),
            'provider_health' => $this->health($context),
            'list_providers' => $this->listProviders($context),
            default => throw new ApiException(404, "Операция «{$operation}» не поддерживается", 'unknown_operation'),
        };
    }

    // ── operations ───────────────────────────────────────────────────────────────────

    private function quote(array $input, PluginContext $context): array
    {
        $params = $this->addressParams($input, $context);
        $provider = $this->providerFor($input, $context);

        if (!$provider->capabilities()['quote']) {
            throw new ApiException(409, "Провайдер «{$provider->name()}» не поддерживает расчёт стоимости", 'not_supported');
        }

        $quote = $provider->quote($context, $params);
        return [...$quote, 'provider' => $provider->code(), 'provider_configured' => $provider->isConfigured($context)];
    }

    private function create(array $input, PluginContext $context): array
    {
        $params = $this->addressParams($input, $context, true);
        $provider = $this->providerFor($input, $context);

        if (!$provider->capabilities()['create']) {
            throw new ApiException(409, "Провайдер «{$provider->name()}» не поддерживает создание доставки", 'not_supported');
        }

        $orderId = $this->nullableId($input['order_id'] ?? null);
        if ($orderId !== null && !$this->owns($context, 'orders', $orderId)) {
            throw new ApiException(404, 'Заказ не найден', 'not_found');
        }

        $result = $provider->create($context, $params);
        $providerStatus = (string) $result['provider_status'];
        $universal = $this->normalise($provider, $providerStatus);
        $now = H::now();
        $shipmentId = H::newId('dlv');

        DB::insert(
            'INSERT INTO delivery_shipments
               (id, tenant_id, plugin_id, connection_id, order_id, provider, provider_shipment_id, status, provider_status,
                address_from, address_to, recipient_name, recipient_phone, cost, currency, tracking_url, provider_configured,
                history, created_by, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [
                $shipmentId, $context->tenantId, self::PLUGIN_ID, $context->connectionId, $orderId,
                $provider->code(), (string) $result['provider_shipment_id'], $universal, $providerStatus,
                $params['address_from'] ?? null, $params['address_to'] ?? null,
                $params['recipient_name'] ?? null, $params['recipient_phone'] ?? null,
                (float) ($result['cost'] ?? 0), (string) ($result['currency'] ?? 'UZS'),
                $result['tracking_url'] ?? null,
                !empty($result['provider_configured']) ? 1 : 0,
                json_encode([['provider_status' => $providerStatus, 'status' => $universal, 'at' => $now]], JSON_UNESCAPED_UNICODE),
                $context->userId, $now, $now,
            ]
        );

        return [
            'shipment_id' => $shipmentId,
            'order_id' => $orderId,
            'provider' => $provider->code(),
            'provider_configured' => !empty($result['provider_configured']),
            'simulated' => empty($result['provider_configured']),
            'status' => $universal,
            'provider_status' => $providerStatus,
            'cost' => (float) ($result['cost'] ?? 0),
            'currency' => (string) ($result['currency'] ?? 'UZS'),
            'tracking_url' => $result['tracking_url'] ?? null,
            'note' => $result['note'] ?? null,
            '_events' => empty($result['provider_configured']) ? [] : [
                [
                    'event' => 'delivery.created',
                    'payload' => ['shipment_id' => $shipmentId, 'order_id' => $orderId, 'status' => $universal, 'provider' => $provider->code()],
                    'idempotency_key' => 'delivery.created:' . $shipmentId,
                ],
            ],
        ];
    }

    private function listShipments(array $input, PluginContext $context): array
    {
        $limit = min(200, max(1, (int) ($input['limit'] ?? 50)));
        $where = ['s.tenant_id = ?'];
        $binds = [$context->tenantId];

        if (!empty($input['status'])) {
            $status = (string) $input['status'];
            if (!in_array($status, self::UNIVERSAL_STATUSES, true)) {
                throw new ApiException(400, 'Недопустимый статус', 'bad_request');
            }
            $where[] = 's.status = ?';
            $binds[] = $status;
        }
        if (!empty($input['order_id'])) {
            $where[] = 's.order_id = ?';
            $binds[] = (string) $input['order_id'];
        }
        $binds[] = $limit;

        $rows = DB::select(
            'SELECT s.id, s.order_id, s.provider, s.provider_shipment_id, s.status, s.provider_status,
                    s.address_to, s.recipient_name, s.cost, s.currency, s.tracking_url,
                    s.provider_configured, s.last_error, s.created_at
               FROM delivery_shipments s
              WHERE ' . implode(' AND ', $where) . '
              ORDER BY s.created_at DESC LIMIT ?',
            $binds
        );

        return [
            'data' => array_map(fn ($r) => [
                'id' => $r->id,
                'order_id' => $r->order_id,
                'provider' => $r->provider,
                'status' => $r->status,
                'provider_status' => $r->provider_status,
                'address_to' => $r->address_to,
                'recipient_name' => $r->recipient_name,
                'cost' => (float) $r->cost,
                'currency' => $r->currency,
                'tracking_url' => $r->tracking_url,
                'provider_configured' => (bool) $r->provider_configured,
                'last_error' => $r->last_error,
                'created_at' => $r->created_at,
            ], $rows),
            'total' => (int) (DB::selectOne('SELECT COUNT(*) AS c FROM delivery_shipments WHERE tenant_id = ?', [$context->tenantId])->c ?? 0),
        ];
    }

    private function getShipment(array $input, PluginContext $context): array
    {
        $row = $this->shipmentRow($context, (string) ($input['shipment_id'] ?? ''));
        return ['data' => [...(array) $row, 'history' => H::parseJson($row->history, [])]];
    }

    private function refreshStatus(array $input, PluginContext $context): array
    {
        $row = $this->shipmentRow($context, (string) ($input['shipment_id'] ?? ''));
        $provider = $this->providerByCode($context, (string) $row->provider);

        if (!$provider->capabilities()['track']) {
            throw new ApiException(409, "Провайдер «{$provider->name()}» не поддерживает отслеживание", 'not_supported');
        }

        try {
            $result = $provider->status($context, (string) $row->provider_shipment_id);
        } catch (ApiException $e) {
            DB::update('UPDATE delivery_shipments SET last_error=?, updated_at=? WHERE id=?', [mb_substr($e->getMessage(), 0, 300), H::now(), $row->id]);
            throw $e;
        }

        return $this->applyProviderStatus($context, $row, $provider, (string) $result['provider_status']);
    }

    private function cancel(array $input, PluginContext $context): array
    {
        $row = $this->shipmentRow($context, (string) ($input['shipment_id'] ?? ''));
        if (in_array((string) $row->status, self::TERMINAL, true)) {
            throw new ApiException(409, "Доставку в статусе «{$row->status}» отменить нельзя", 'conflict');
        }

        $provider = $this->providerByCode($context, (string) $row->provider);
        if (!$provider->capabilities()['cancel']) {
            throw new ApiException(409, "Провайдер «{$provider->name()}» не поддерживает отмену", 'not_supported');
        }

        $result = $provider->cancel($context, (string) $row->provider_shipment_id);
        $applied = $this->applyProviderStatus($context, $row, $provider, (string) $result['provider_status']);

        return [...$applied, 'cancelled' => true];
    }

    private function listProviders(PluginContext $context): array
    {
        $out = [[
            'code' => 'sandbox',
            'name' => 'Sandbox (симуляция)',
            'configured' => false,
            'capabilities' => ProviderRegistry::sandbox()->capabilities(),
            'installed' => true,
        ]];

        foreach (ProviderRegistry::adaptersFor($context->tenantId) as $code => $provider) {
            $out[] = [
                'code' => $code,
                'name' => $provider->name(),
                'configured' => $provider->isConfigured($context),
                'capabilities' => $provider->capabilities(),
                'installed' => true,
            ];
        }

        return ['data' => $out, 'default' => ProviderRegistry::requestedFor($context)];
    }

    // ── internals ────────────────────────────────────────────────────────────────────

    /** Persist a provider status change, appending to the history trail. */
    private function applyProviderStatus(PluginContext $context, object $row, DeliveryProvider $provider, string $providerStatus): array
    {
        $universal = $this->normalise($provider, $providerStatus);
        $history = H::parseJson($row->history, []);
        $history[] = ['provider_status' => $providerStatus, 'status' => $universal, 'at' => H::now()];
        // Keep the trail bounded so a chatty provider cannot grow the row without limit.
        if (count($history) > 50) {
            $history = array_slice($history, -50);
        }

        DB::update(
            'UPDATE delivery_shipments SET status=?, provider_status=?, history=?, last_error=NULL, updated_at=? WHERE id=? AND tenant_id=?',
            [$universal, $providerStatus, json_encode($history, JSON_UNESCAPED_UNICODE), H::now(), $row->id, $context->tenantId]
        );

        $events = [];
        if ((string) $row->provider_configured === '1' && $universal !== (string) $row->status) {
            $events[] = [
                'event' => $universal === 'delivered' ? 'delivery.completed' : 'delivery.updated',
                'payload' => ['shipment_id' => $row->id, 'order_id' => $row->order_id, 'status' => $universal],
                'idempotency_key' => 'delivery.status:' . $row->id . ':' . $universal,
            ];
        }

        return [
            'shipment_id' => $row->id,
            'status' => $universal,
            'provider_status' => $providerStatus,
            'previous_status' => (string) $row->status,
            'history' => $history,
            '_events' => $events,
        ];
    }

    /**
     * Normalise a provider status. The database mapping table wins so an operator can correct a
     * mapping without a deploy; the provider's own mapping is the fallback.
     */
    private function normalise(DeliveryProvider $provider, string $providerStatus): string
    {
        $row = DB::selectOne(
            'SELECT universal_status FROM delivery_status_map WHERE provider = ? AND provider_status = ? LIMIT 1',
            [$provider->code(), $providerStatus]
        );
        $universal = $row ? (string) $row->universal_status : $provider->normaliseStatus($providerStatus);

        return in_array($universal, self::UNIVERSAL_STATUSES, true) ? $universal : 'pending';
    }

    private function providerFor(array $input, PluginContext $context): DeliveryProvider
    {
        $requested = (string) ($input['provider'] ?? '');
        if ($requested === '') {
            $requested = ProviderRegistry::requestedFor($context);
        }
        return ProviderRegistry::resolve($context, $requested);
    }

    /** A stored shipment may reference a provider that is since disabled: report it clearly. */
    private function providerByCode(PluginContext $context, string $code): DeliveryProvider
    {
        foreach (ProviderRegistry::adaptersFor($context->tenantId) as $candidate => $provider) {
            if ($candidate === $code) {
                return $provider;
            }
        }
        if ($code === 'sandbox') {
            return ProviderRegistry::sandbox();
        }
        throw new ApiException(
            409,
            "Провайдер «{$code}», создавший эту доставку, больше не подключён к компании. Включите соответствующий плагин.",
            'provider_unavailable',
            ['provider' => $code]
        );
    }

    private function addressParams(array $input, PluginContext $context, bool $requireRecipient = false): array
    {
        $from = trim((string) ($input['address_from'] ?? '')) ?: trim((string) ($context->setting('origin_address', '') ?? ''));
        $to = trim((string) ($input['address_to'] ?? ''));

        if ($to === '') {
            throw new ApiException(400, 'Укажите адрес получателя', 'bad_request');
        }
        if ($requireRecipient && trim((string) ($input['recipient_name'] ?? '')) === '') {
            throw new ApiException(400, 'Укажите получателя', 'bad_request');
        }

        return [
            'address_from' => $from !== '' ? mb_substr($from, 0, 500) : null,
            'address_to' => mb_substr($to, 0, 500),
            'recipient_name' => $input['recipient_name'] ?? null,
            // Only the fields the carrier needs — never the whole client card.
            'recipient_phone' => isset($input['recipient_phone']) ? mb_substr((string) $input['recipient_phone'], 0, 32) : null,
            'distance_km' => isset($input['distance_km']) ? (float) $input['distance_km'] : 0.0,
            'weight_kg' => isset($input['weight_kg']) ? (float) $input['weight_kg'] : null,
            'declared_value' => isset($input['declared_value']) ? (float) $input['declared_value'] : null,
        ];
    }

    private function shipmentRow(PluginContext $context, string $shipmentId): object
    {
        if ($shipmentId === '') {
            throw new ApiException(400, 'Не указана доставка', 'bad_request');
        }
        $row = DB::selectOne('SELECT * FROM delivery_shipments WHERE id = ? AND tenant_id = ?', [$shipmentId, $context->tenantId]);
        if (!$row) {
            throw new ApiException(404, 'Доставка не найдена', 'not_found');
        }
        return $row;
    }

    private function nullableId(mixed $value): ?string
    {
        $v = is_string($value) ? trim($value) : '';
        return $v === '' ? null : $v;
    }

    private function owns(PluginContext $context, string $table, string $id): bool
    {
        if ($table !== 'orders') {
            return false;
        }
        return (bool) DB::selectOne("SELECT 1 AS x FROM orders WHERE id = ? AND tenant_id = ?", [$id, $context->tenantId]);
    }

    // ── events / health / schema ─────────────────────────────────────────────────────

    public function onEvent(string $event, array $payload, PluginContext $context): void
    {
        // Auto-creation is opt-in and off by default, so an order event never silently dispatches
        // a shipment (and never consumes delivery_orders quota) without the company asking for it.
        if ($event !== 'order.created' || !$context->setting('auto_create_on_order', false)) {
            return;
        }
        $orderId = (string) ($payload['order_id'] ?? '');
        if ($orderId === '' || !$this->owns($context, 'orders', $orderId)) {
            return;
        }
        // Duplicate protection: one shipment per order per provider.
        $existing = DB::selectOne(
            'SELECT id FROM delivery_shipments WHERE tenant_id = ? AND order_id = ? AND provider = ? LIMIT 1',
            [$context->tenantId, $orderId, ProviderRegistry::requestedFor($context)]
        );
        if ($existing) {
            return;
        }

        $address = (string) ($payload['address'] ?? '');
        if ($address === '') {
            return;
        }

        $provider = ProviderRegistry::resolve($context, ProviderRegistry::requestedFor($context));
        $result = $provider->create($context, ['address_to' => $address, 'recipient_name' => $payload['client_name'] ?? null]);
        $universal = $this->normalise($provider, (string) $result['provider_status']);
        $now = H::now();

        DB::insert(
            'INSERT INTO delivery_shipments
               (id, tenant_id, plugin_id, connection_id, order_id, provider, provider_shipment_id, status, provider_status,
                address_from, address_to, recipient_name, cost, currency, provider_configured, history, created_by, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [
                H::newId('dlv'), $context->tenantId, self::PLUGIN_ID, $context->connectionId, $orderId,
                $provider->code(), (string) $result['provider_shipment_id'], $universal, (string) $result['provider_status'],
                $context->setting('origin_address', null), $address, $payload['client_name'] ?? null,
                (float) ($result['cost'] ?? 0), (string) ($result['currency'] ?? 'UZS'),
                !empty($result['provider_configured']) ? 1 : 0,
                json_encode([['provider_status' => (string) $result['provider_status'], 'status' => $universal, 'at' => $now]], JSON_UNESCAPED_UNICODE),
                $context->userId, $now, $now,
            ]
        );
    }

    public function health(PluginContext $context): array
    {
        $provider = ProviderRegistry::resolve($context, ProviderRegistry::requestedFor($context));
        $health = $provider->health($context);
        return [...$health, 'provider' => $provider->code(), 'capabilities' => $provider->capabilities()];
    }

    public function settingsSchema(): array
    {
        $raw = @file_get_contents(__DIR__ . '/manifest.json');
        $manifest = is_string($raw) ? json_decode($raw, true) : null;
        return is_array($manifest) ? (array) ($manifest['settings_schema'] ?? []) : [];
    }
}
