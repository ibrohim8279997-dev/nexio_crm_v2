<?php
namespace App\Http\Controllers\Api\V1;

use App\Plugins\PluginAudit;
use App\Plugins\PluginRuntime;
use App\Support\ApiException;
use App\Support\Ctx;
use App\Support\H;
use Illuminate\Support\Facades\DB;

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

    /**
     * Each CRM user calls from their own line. Without an explicit `from` the caller's assigned number is used;
     * an explicit `from` must be the caller's own number unless the caller manages the team.
     */
    public function telephonyPlaceCall(Ctx $ctx)
    {
        $uid = (string) ($ctx->user['id'] ?? '');
        $own = $uid === '' ? null : DB::table('telephony_numbers')
            ->where('tenant_id', $ctx->tenantId)->where('assigned_user_id', $uid)->where('status', 'active')->value('phone_number');
        $from = trim((string) (H::body($ctx)['from'] ?? ''));
        $merge = [];
        if ($from === '') {
            if ($own) $merge['from'] = $own;
        } elseif ($from !== $own && !H::can($ctx, 'team.write')) {
            throw new ApiException(403, 'Можно звонить только с назначенного вам номера', 'forbidden');
        }
        return H::json($this->withIdempotency($ctx, 'nexio.telephony', 'place_call', $merge), 201);
    }

    /** Registers a number or extension by hand (when no provider sync is available). */
    public function telephonyCreateNumber(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'team.write');
        PluginRuntime::invoke($ctx, 'nexio.telephony', 'list_numbers', []);   // install + plan entitlement guard
        $b = H::body($ctx);
        $phone = trim((string) ($b['phone_number'] ?? ''));
        if (!preg_match('/^[0-9+()\- ]{3,32}$/', $phone)) throw new ApiException(422, 'Некорректный номер или внутренний номер', 'validation');
        if (DB::table('telephony_numbers')->where('tenant_id', $ctx->tenantId)->where('phone_number', $phone)->exists()) {
            throw new ApiException(409, 'Такой номер уже добавлен', 'conflict');
        }
        $id = H::newId('tln');
        $now = H::now();
        DB::table('telephony_numbers')->insert([
            'id' => $id, 'tenant_id' => $ctx->tenantId, 'plugin_id' => 'nexio.telephony', 'connection_id' => null,
            'phone_number' => $phone, 'provider' => 'manual', 'status' => 'active', 'capabilities' => '{}',
            'label' => H::str($b['label'] ?? null, 'label', false, 80), 'created_by' => $ctx->user['id'] ?? null,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        H::audit($ctx, 'telephony.number_added', 'telephony_number', $id, ['phone_number' => $phone]);
        return H::json(['id' => $id, 'phone_number' => $phone], 201);
    }

    /** Assigns a number to one active team member (assigned_user_id) or frees it (null). A user holds one line. */
    public function telephonyAssignNumber(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'team.write');
        PluginRuntime::invoke($ctx, 'nexio.telephony', 'list_numbers', []);
        $row = DB::table('telephony_numbers')->where('id', (string) ($ctx->params['id'] ?? ''))->where('tenant_id', $ctx->tenantId)->first();
        if (!$row) throw new ApiException(404, 'Номер не найден', 'not_found');
        $userId = H::memberRef($ctx, H::body($ctx)['assigned_user_id'] ?? null, 'assigned_user_id');
        if ($userId !== null && DB::table('telephony_numbers')->where('tenant_id', $ctx->tenantId)
                ->where('assigned_user_id', $userId)->where('id', '!=', $row->id)->where('status', 'active')->exists()) {
            throw new ApiException(409, 'У этого сотрудника уже есть номер. Сначала освободите его.', 'conflict');
        }
        DB::table('telephony_numbers')->where('id', $row->id)->update(['assigned_user_id' => $userId, 'updated_at' => H::now()]);
        H::audit($ctx, 'telephony.number_assigned', 'telephony_number', $row->id, ['user_id' => $userId, 'previous' => $row->assigned_user_id]);
        return H::json(['ok' => true, 'id' => $row->id, 'assigned_user_id' => $userId]);
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
        $res = PluginRuntime::invoke($ctx, 'nexio.telephony', 'list_numbers', []);
        $extra = DB::table('telephony_numbers as n')->leftJoin('users as u', 'u.id', '=', 'n.assigned_user_id')
            ->where('n.tenant_id', $ctx->tenantId)->get(['n.id', 'n.assigned_user_id', 'n.label', 'u.name as assigned_user_name'])->keyBy('id');
        $res['data'] = array_map(function ($n) use ($extra) {
            $e = $extra[$n['id']] ?? null;
            return $n + ['assigned_user_id' => $e->assigned_user_id ?? null, 'assigned_user_name' => $e->assigned_user_name ?? null, 'label' => $e->label ?? null];
        }, $res['data'] ?? []);
        return H::json($res);
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

    private function withIdempotency(Ctx $ctx, string $pluginId, string $operation, array $merge = []): array
    {
        $body = array_merge(H::body($ctx), $merge);
        $key = $this->idempotencyFromHeader($ctx);
        if ($key === null && isset($body['idempotency_key']) && is_string($body['idempotency_key'])) {
            $key = $body['idempotency_key'];
        }
        unset($body['idempotency_key']);
        return PluginRuntime::invoke($ctx, $pluginId, $operation, $body, $key);
    }
}
