<?php
namespace App\Http\Controllers\Api\V1;

use App\Plugins\PluginAudit;
use App\Plugins\PluginRuntime;
use App\Support\ApiException;
use App\Support\Ctx;
use App\Support\H;

/**
 * Runtime surface for plugin capabilities.
 *
 * Every call goes through PluginRuntime, which enforces — in this order — tenant context, install
 * state, plan entitlement, plugin load, the declared operation list, connection ownership,
 * the plugin permission and the quota reservation. A controller cannot skip a step, and a plugin
 * cannot expose an operation it did not declare.
 *
 * The generic `/{plugin}/operations/{operation}` route exists so new official plugins need no new
 * controller. The named routes below are thin, discoverable aliases for the plugin screens.
 */
class PluginOperationsController
{
    /** Generic, fully-guarded invocation. */
    public function invoke(Ctx $ctx)
    {
        $pluginId = (string) ($ctx->params['id'] ?? '');
        $operation = (string) ($ctx->params['operation'] ?? '');
        $body = H::body($ctx);

        $idempotencyKey = null;
        $header = $ctx->request->header('Idempotency-Key');
        if (is_string($header) && $header !== '') {
            $idempotencyKey = $header;
        } elseif (isset($body['idempotency_key']) && is_string($body['idempotency_key'])) {
            $idempotencyKey = $body['idempotency_key'];
        }

        unset($body['idempotency_key']);

        // The browser cannot choose how many AI tokens to reserve. Reserve a
        // server-side upper bound (prompt estimate + configured completion cap)
        // before the provider is called, so the plugin cannot cross its plan limit.
        if ($pluginId === 'nexio.ai.assistant' && $operation === 'ask') {
            $promptLength = mb_strlen((string) ($body['prompt'] ?? ''));
            $body['amount'] = max(1, (int) ceil($promptLength / 4) + (int) config('ai.max_tokens', 2048));
        }

        return H::json(PluginRuntime::invoke($ctx, $pluginId, $operation, $body, $idempotencyKey));
    }

    // ── delivery (nexio.delivery) ────────────────────────────────────────────────────

    public function deliveryQuote(Ctx $ctx)
    {
        return H::json(PluginRuntime::invoke($ctx, 'nexio.delivery', 'quote', H::body($ctx)));
    }

    public function deliveryCreate(Ctx $ctx)
    {
        return H::json($this->withIdempotency($ctx, 'nexio.delivery', 'create'), 201);
    }

    public function deliveryList(Ctx $ctx)
    {
        $params = array_filter([
            'status' => $ctx->query('status'),
            'order_id' => $ctx->query('order_id'),
            'limit' => $ctx->query('limit'),
        ], fn ($v) => $v !== null && $v !== '');
        return H::json(PluginRuntime::invoke($ctx, 'nexio.delivery', 'list', $params));
    }

    public function deliveryGet(Ctx $ctx)
    {
        return H::json(PluginRuntime::invoke($ctx, 'nexio.delivery', 'get', ['shipment_id' => (string) ($ctx->params['id'] ?? '')]));
    }

    public function deliveryRefresh(Ctx $ctx)
    {
        return H::json(PluginRuntime::invoke($ctx, 'nexio.delivery', 'refresh_status', ['shipment_id' => (string) ($ctx->params['id'] ?? '')]));
    }

    public function deliveryCancel(Ctx $ctx)
    {
        return H::json(PluginRuntime::invoke($ctx, 'nexio.delivery', 'cancel', ['shipment_id' => (string) ($ctx->params['id'] ?? '')], $this->idempotencyFromHeader($ctx)));
    }

    public function deliveryProviders(Ctx $ctx)
    {
        return H::json(PluginRuntime::invoke($ctx, 'nexio.delivery', 'list_providers', []));
    }

    // ── telephony (nexio.telephony) ──────────────────────────────────────────────────

    public function telephonyPlaceCall(Ctx $ctx)
    {
        return H::json($this->withIdempotency($ctx, 'nexio.telephony', 'place_call'), 201);
    }

    public function telephonyEndCall(Ctx $ctx)
    {
        $body = H::body($ctx);
        return H::json(PluginRuntime::invoke($ctx, 'nexio.telephony', 'end_call', $body, $this->idempotencyFromHeader($ctx)));
    }

    public function telephonyCalls(Ctx $ctx)
    {
        $params = array_filter([
            'direction' => $ctx->query('direction'),
            'contact_id' => $ctx->query('contact_id'),
            'order_id' => $ctx->query('order_id'),
            'limit' => $ctx->query('limit'),
        ], fn ($v) => $v !== null && $v !== '');
        return H::json(PluginRuntime::invoke($ctx, 'nexio.telephony', 'list_calls', $params));
    }

    public function telephonyCall(Ctx $ctx)
    {
        return H::json(PluginRuntime::invoke($ctx, 'nexio.telephony', 'get_call', ['call_id' => (string) ($ctx->params['id'] ?? '')]));
    }

    public function telephonyRecording(Ctx $ctx)
    {
        return H::json(PluginRuntime::invoke($ctx, 'nexio.telephony', 'get_recording', ['call_id' => (string) ($ctx->params['id'] ?? '')]));
    }

    public function telephonyNumbers(Ctx $ctx)
    {
        return H::json(PluginRuntime::invoke($ctx, 'nexio.telephony', 'list_numbers', []));
    }

    public function telephonySyncNumbers(Ctx $ctx)
    {
        return H::json(PluginRuntime::invoke($ctx, 'nexio.telephony', 'sync_numbers', H::body($ctx)));
    }

    public function telephonyProvider(Ctx $ctx)
    {
        return H::json(PluginRuntime::invoke($ctx, 'nexio.telephony', 'provider_health', []));
    }

    // ── helpers ──────────────────────────────────────────────────────────────────────

    /**
     * An Idempotency-Key header (or body field) makes a retry charge the quota once. Without one a
     * fresh key is generated per request, which is the correct default for a distinct user action.
     */
    private function idempotencyFromHeader(Ctx $ctx): ?string
    {
        $header = $ctx->request->header('Idempotency-Key');
        return is_string($header) && $header !== '' ? $header : null;
    }

    private function withIdempotency(Ctx $ctx, string $pluginId, string $operation): array
    {
        $body = H::body($ctx);
        $key = $this->idempotencyFromHeader($ctx);
        if ($key === null && isset($body['idempotency_key']) && is_string($body['idempotency_key'])) {
            $key = $body['idempotency_key'];
        }
        unset($body['idempotency_key']);
        return PluginRuntime::invoke($ctx, $pluginId, $operation, $body, $key);
    }
}
