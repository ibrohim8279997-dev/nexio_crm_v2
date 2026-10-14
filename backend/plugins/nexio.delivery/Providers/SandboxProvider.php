<?php
namespace Nexio\Delivery\Providers;

use App\Plugins\PluginContext;
use App\Support\ApiException;
use App\Support\H;
use Illuminate\Support\Facades\DB;
use Nexio\Delivery\Contracts\DeliveryProvider;

/**
 * Sandbox delivery provider — the built-in simulated carrier.
 *
 * It lets the whole delivery flow (quote → create → track → cancel) be exercised end to end
 * without a carrier contract. It is honest about what it is:
 *   - isConfigured() is always false, so no UI ever shows "подключено";
 *   - create() returns provider_configured = false and a note stating nothing was dispatched;
 *   - the simulated courier advances deterministically with elapsed time so tracking can be
 *     demonstrated and tested.
 */
class SandboxProvider implements DeliveryProvider
{
    /** Universal statuses, identical to the ones seeded in delivery_status_map. */
    public const UNIVERSAL = ['pending', 'accepted', 'courier_assigned', 'picked_up', 'in_transit', 'delivered', 'cancelled', 'failed'];

    public function code(): string
    {
        return 'sandbox';
    }

    public function name(): string
    {
        return 'Sandbox (симуляция)';
    }

    public function isConfigured(PluginContext $context): bool
    {
        return false;
    }

    public function capabilities(): array
    {
        return ['quote' => true, 'create' => true, 'cancel' => true, 'track' => true];
    }

    public function health(PluginContext $context): array
    {
        return [
            'status' => 'sandbox',
            'message' => 'Провайдер доставки не подключён. Доставки создаются в режиме симуляции и не передаются курьерской службе.',
            'checks' => [
                ['name' => 'carrier_contract', 'ok' => false, 'detail' => 'Договор с курьерской службой не подключён.'],
                ['name' => 'carrier_credentials', 'ok' => false, 'detail' => 'Учётные данные провайдера не заданы.'],
            ],
        ];
    }

    public function quote(PluginContext $context, array $params): array
    {
        $currency = (string) ($context->setting('default_currency', 'UZS') ?: 'UZS');
        // A simple, clearly-labelled estimate — not a carrier tariff.
        $base = 15000.0;
        $perKm = 1200.0;
        $distance = (float) ($params['distance_km'] ?? 0);

        return [
            'cost' => $base + ($perKm * max(0, $distance)),
            'currency' => $currency,
            'eta_minutes' => 60,
            'breakdown' => [
                'base' => $base,
                'distance' => $perKm * max(0, $distance),
                'note' => 'Ориентировочный расчёт симуляции, не тариф курьерской службы.',
            ],
        ];
    }

    public function create(PluginContext $context, array $params): array
    {
        $currency = (string) ($context->setting('default_currency', 'UZS') ?: 'UZS');
        $quote = $this->quote($context, $params);

        return [
            'provider_shipment_id' => 'sbx_' . bin2hex(random_bytes(6)),
            'provider_status' => 'created',
            'cost' => $quote['cost'],
            'currency' => $currency,
            'tracking_url' => null,
            'provider_configured' => false,
            'note' => 'Симуляция: доставка не передана курьерской службе.',
        ];
    }

    /**
     * Simulated progression. Deterministic in elapsed time so tests can assert a lifecycle
     * without sleeping for an hour.
     */
    public function status(PluginContext $context, string $providerShipmentId): array
    {
        $row = DB::selectOne(
            'SELECT created_at, status, provider_status FROM delivery_shipments
              WHERE tenant_id = ? AND provider_shipment_id = ?',
            [$context->tenantId, $providerShipmentId]
        );
        if (!$row) {
            throw new ApiException(404, 'Отправление не найдено у провайдера', 'not_found');
        }
        if (in_array((string) $row->status, ['cancelled', 'delivered', 'failed'], true)) {
            return ['provider_status' => (string) $row->provider_status];
        }

        $elapsed = max(0, time() - (int) strtotime((string) $row->created_at . ' UTC'));
        $providerStatus = match (true) {
            $elapsed < 120 => 'accepted',
            $elapsed < 600 => 'courier',
            $elapsed < 1800 => 'picked_up',
            $elapsed < 3600 => 'in_transit',
            default => 'delivered',
        };

        return ['provider_status' => $providerStatus];
    }

    public function cancel(PluginContext $context, string $providerShipmentId): array
    {
        $row = DB::selectOne(
            'SELECT status FROM delivery_shipments WHERE tenant_id = ? AND provider_shipment_id = ?',
            [$context->tenantId, $providerShipmentId]
        );
        if (!$row) {
            throw new ApiException(404, 'Отправление не найдено у провайдера', 'not_found');
        }
        if ((string) $row->status === 'delivered') {
            throw new ApiException(409, 'Доставленное отправление нельзя отменить', 'conflict');
        }
        return ['provider_status' => 'cancelled'];
    }

    public function normaliseStatus(string $providerStatus): string
    {
        $map = [
            'created' => 'pending',
            'accepted' => 'accepted',
            'courier' => 'courier_assigned',
            'courier_assigned' => 'courier_assigned',
            'picked_up' => 'picked_up',
            'in_transit' => 'in_transit',
            'delivered' => 'delivered',
            'cancelled' => 'cancelled',
            'canceled' => 'cancelled',
            'failed' => 'failed',
        ];
        $key = strtolower(trim($providerStatus));
        return $map[$key] ?? 'pending';
    }

    /** Status history entry appended to delivery_shipments.history. */
    public function historyEntry(string $providerStatus, string $universal): array
    {
        return ['provider_status' => $providerStatus, 'status' => $universal, 'at' => H::now()];
    }
}
