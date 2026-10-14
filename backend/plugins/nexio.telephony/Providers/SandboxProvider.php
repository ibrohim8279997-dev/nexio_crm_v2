<?php
namespace Nexio\Telephony\Providers;

use App\Plugins\PluginContext;

/**
 * Sandbox telephony provider — the only provider that ships with Nexio.
 *
 * It exists so the plug-in's UX, logging, quotas and event flow can be exercised without a SIP
 * trunk, and it is deliberately incapable of lying:
 *   - isConfigured() is ALWAYS false, so the platform never reports "connected";
 *   - placeCall() returns status "simulated" and provider_configured = false;
 *   - no minutes are charged for a simulated call (the quota is only consumed by real usage);
 *   - health() always says the provider is not configured.
 *
 * Attaching a real trunk means implementing TelephonyProvider and registering it — not enabling
 * this class.
 */
class SandboxProvider implements TelephonyProvider
{
    public function code(): string
    {
        return 'sandbox';
    }

    public function isConfigured(PluginContext $context): bool
    {
        return false;
    }

    public function capabilities(): array
    {
        return [
            'outbound' => false,
            'inbound' => false,
            'recordings' => false,
            'transfer' => false,
            'numbers' => false,
        ];
    }

    public function health(PluginContext $context): array
    {
        return [
            'status' => 'sandbox',
            'message' => 'SIP-провайдер не настроен. Звонки не выходят в телефонную сеть — операции фиксируются как симуляция.',
            'checks' => [
                [
                    'name' => 'provider_credentials',
                    'ok' => false,
                    'detail' => 'Учётные данные SIP-провайдера не заданы в подключении плагина.',
                ],
                [
                    'name' => 'pstn_reachable',
                    'ok' => false,
                    'detail' => 'Без провайдера выход в PSTN невозможен.',
                ],
            ],
        ];
    }

    public function placeCall(PluginContext $context, array $params): array
    {
        return [
            'provider_call_id' => 'sbx_' . bin2hex(random_bytes(6)),
            'status' => 'simulated',
            'provider_configured' => false,
            'started_at' => gmdate('Y-m-d H:i:s'),
            'note' => 'Провайдер не подключён: звонок не был совершён. Запись создана для проверки сценария.',
        ];
    }

    public function endCall(PluginContext $context, string $providerCallId): array
    {
        return [
            'status' => 'simulated',
            'duration_seconds' => 0,
            'ended_at' => gmdate('Y-m-d H:i:s'),
            'hangup_cause' => 'sandbox',
        ];
    }

    public function numbers(PluginContext $context): array
    {
        return [];
    }
}
