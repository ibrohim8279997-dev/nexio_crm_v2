<?php
namespace App\Http\Controllers\Api\V1;

use App\Services\WebhookService;
use App\Support\ApiException;
use App\Support\Ctx;
use App\Support\H;
use App\Support\OutboundEndpoint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/** Developer integrations: API keys (scoped bearer tokens) and outbound webhooks. Managing them requires integrations.write. */
class IntegrationsController
{
    /** Scopes an API key may carry — never team, billing, settings or key-management rights. */
    public const KEY_SCOPES = ['crm.read', 'crm.write', 'deals.write', 'orders.write', 'projects.write', 'tasks.write', 'finance.read', 'finance.write'];

    private function guard(Ctx $ctx, bool $write): void
    {
        $ctx->auth(); H::requireTenant($ctx);
        if ($ctx->role === 'api_key') throw new ApiException(403, 'API-ключом нельзя управлять интеграциями', 'forbidden');
        H::requirePermission($ctx, $write ? 'integrations.write' : 'settings.read');
    }

    // ── API keys ─────────────────────────────────────────────────────────────
    public function listKeys(Ctx $ctx)
    {
        $this->guard($ctx, false);
        $rows = DB::table('api_keys')->where('tenant_id', $ctx->tenantId)->whereNull('parent_id')->orderByDesc('created_at')->get(['id', 'name', 'prefix', 'scopes', 'last_used_at', 'expires_at', 'revoked_at', 'created_at']);
        return H::json(['data' => $rows->map(fn ($k) => [...(array) $k, 'scopes' => H::parseJson($k->scopes, []),
            'status' => $k->revoked_at ? 'revoked' : ($k->expires_at && $k->expires_at < H::now() ? 'expired' : 'active')])->all(), 'available_scopes' => self::KEY_SCOPES]);
    }

    public function createKey(Ctx $ctx)
    {
        $this->guard($ctx, true);
        $user = $ctx->auth();
        $b = H::body($ctx);
        $scopes = array_values(array_unique(is_array($b['scopes'] ?? null) ? $b['scopes'] : []));
        if (!$scopes) throw new ApiException(400, 'Выберите хотя бы одно право', 'bad_request');
        foreach ($scopes as $s) if (!in_array($s, self::KEY_SCOPES, true)) throw new ApiException(422, "Право «{$s}» недоступно для API-ключей", 'bad_request');
        // A key can never exceed what its creator may do.
        foreach ($scopes as $s) H::requirePermission($ctx, $s);
        $days = isset($b['expires_in_days']) && $b['expires_in_days'] !== '' ? (int) H::num($b['expires_in_days'], 'expires_in_days') : null;
        if ($days !== null && ($days < 1 || $days > 730)) throw new ApiException(400, 'Срок действия: от 1 до 730 дней', 'bad_request');
        if (DB::table('api_keys')->where('tenant_id', $ctx->tenantId)->whereNull('parent_id')->whereNull('revoked_at')->count() >= 25) throw new ApiException(402, 'Достигнут лимит активных ключей (25)', 'quota_exceeded');

        $token = 'nxk_' . bin2hex(random_bytes(24));
        $id = H::newId('key');
        DB::table('api_keys')->insert([
            'id' => $id, 'tenant_id' => $ctx->tenantId, 'name' => H::str($b['name'] ?? null, 'name', true, 120), 'prefix' => substr($token, 0, 12),
            'key_hash' => hash('sha256', $token), 'scopes' => json_encode($scopes), 'expires_at' => $days ? gmdate('Y-m-d H:i:s', time() + $days * 86400) : null,
            'created_by' => $user['id'], 'created_at' => H::now(),
        ]);
        H::audit($ctx, 'api_key.created', 'api_key', $id, ['scopes' => $scopes]);
        return H::json(['id' => $id, 'token' => $token, 'note' => 'Токен показан один раз — сохраните его сейчас.'], 201);
    }

    public function revokeKey(Ctx $ctx)
    {
        $this->guard($ctx, true);
        $n = DB::table('api_keys')->where('id', $ctx->params['id'])->where('tenant_id', $ctx->tenantId)->whereNull('revoked_at')->update(['revoked_at' => H::now()]);
        if (!$n) throw new ApiException(404, 'Ключ не найден', 'not_found');
        // OAuth2 access tokens minted from this key die with it.
        DB::table('api_keys')->where('parent_id', $ctx->params['id'])->where('tenant_id', $ctx->tenantId)->whereNull('revoked_at')->update(['revoked_at' => H::now()]);
        H::audit($ctx, 'api_key.revoked', 'api_key', (string) $ctx->params['id']);
        return H::json(['ok' => true]);
    }

    // ── webhooks ─────────────────────────────────────────────────────────────
    private function endpoint(Ctx $ctx, string $id): object
    {
        $e = DB::table('webhook_endpoints')->where('id', $id)->where('tenant_id', $ctx->tenantId)->first();
        if (!$e) throw new ApiException(404, 'Webhook не найден', 'not_found');
        return $e;
    }

    private function events(mixed $raw): array
    {
        $ev = array_values(array_unique(is_array($raw) ? $raw : []));
        if (!$ev) throw new ApiException(400, 'Выберите хотя бы одно событие', 'bad_request');
        foreach ($ev as $e) if ($e !== '*' && !in_array($e, WebhookService::EVENTS, true)) throw new ApiException(422, "Неизвестное событие «{$e}»", 'bad_request');
        return $ev;
    }

    public function listWebhooks(Ctx $ctx)
    {
        $this->guard($ctx, false);
        $since = gmdate('Y-m-d H:i:s', time() - 86400);
        $rows = DB::table('webhook_endpoints')->where('tenant_id', $ctx->tenantId)->orderByDesc('created_at')->get(['id', 'url', 'events', 'is_active', 'created_at']);
        $stats = DB::table('webhook_deliveries')->where('tenant_id', $ctx->tenantId)->where('created_at', '>=', $since)->selectRaw('endpoint_id, status, COUNT(*) AS c')->groupBy('endpoint_id', 'status')->get()->groupBy('endpoint_id');
        return H::json(['data' => $rows->map(function ($r) use ($stats) {
            $s = collect($stats[$r->id] ?? [])->pluck('c', 'status');
            return [...(array) $r, 'events' => H::parseJson($r->events, []), 'is_active' => (bool) $r->is_active, 'last_24h' => ['success' => (int) ($s['success'] ?? 0), 'pending' => (int) ($s['pending'] ?? 0), 'dead' => (int) ($s['dead'] ?? 0)]];
        })->all(), 'available_events' => WebhookService::EVENTS]);
    }

    public function createWebhook(Ctx $ctx)
    {
        $this->guard($ctx, true);
        $user = $ctx->auth();
        $b = H::body($ctx);
        $target = OutboundEndpoint::parse((string) H::str($b['url'] ?? null, 'url', true, 500));
        if (DB::table('webhook_endpoints')->where('tenant_id', $ctx->tenantId)->count() >= 20) throw new ApiException(402, 'Достигнут лимит webhook (20)', 'quota_exceeded');
        $secret = WebhookService::newSecret();
        $id = H::newId('whk');
        DB::table('webhook_endpoints')->insert([
            'id' => $id, 'tenant_id' => $ctx->tenantId, 'url' => $target['url'], 'secret' => Crypt::encryptString($secret),
            'events' => json_encode($this->events($b['events'] ?? null)), 'is_active' => 1, 'created_by' => $user['id'], 'created_at' => H::now(),
        ]);
        H::audit($ctx, 'webhook.created', 'webhook', $id, ['url' => $target['url']]);
        return H::json(['id' => $id, 'secret' => $secret, 'note' => 'Секрет показан один раз. Им подписывается каждый запрос (HMAC-SHA256).'], 201);
    }

    public function updateWebhook(Ctx $ctx)
    {
        $this->guard($ctx, true);
        $e = $this->endpoint($ctx, (string) $ctx->params['id']);
        $b = H::body($ctx);
        $upd = [];
        if (array_key_exists('url', $b)) $upd['url'] = OutboundEndpoint::parse((string) H::str($b['url'], 'url', true, 500))['url'];
        if (array_key_exists('events', $b)) $upd['events'] = json_encode($this->events($b['events']));
        if (array_key_exists('is_active', $b)) $upd['is_active'] = $b['is_active'] ? 1 : 0;
        if ($upd) DB::table('webhook_endpoints')->where('id', $e->id)->update($upd);
        H::audit($ctx, 'webhook.updated', 'webhook', $e->id);
        return H::json(['ok' => true]);
    }

    public function deleteWebhook(Ctx $ctx)
    {
        $this->guard($ctx, true);
        $e = $this->endpoint($ctx, (string) $ctx->params['id']);
        DB::transaction(function () use ($e) {
            DB::table('webhook_deliveries')->where('endpoint_id', $e->id)->delete();
            DB::table('webhook_endpoints')->where('id', $e->id)->delete();
        });
        H::audit($ctx, 'webhook.deleted', 'webhook', $e->id);
        return H::json(['ok' => true]);
    }

    public function rotateSecret(Ctx $ctx)
    {
        $this->guard($ctx, true);
        $e = $this->endpoint($ctx, (string) $ctx->params['id']);
        $secret = WebhookService::newSecret();
        DB::table('webhook_endpoints')->where('id', $e->id)->update(['secret' => Crypt::encryptString($secret)]);
        H::audit($ctx, 'webhook.secret_rotated', 'webhook', $e->id);
        return H::json(['secret' => $secret, 'note' => 'Старый секрет больше не действует.']);
    }

    public function testWebhook(Ctx $ctx)
    {
        $this->guard($ctx, true);
        $e = $this->endpoint($ctx, (string) $ctx->params['id']);
        $id = WebhookService::enqueue((string) $ctx->tenantId, $e->id, 'ping', ['message' => 'Nexio CRM test event']);
        return H::json(['delivery_id' => $id, 'status' => WebhookService::deliver($id)]);
    }

    public function deliveries(Ctx $ctx)
    {
        $this->guard($ctx, false);
        $e = $this->endpoint($ctx, (string) $ctx->params['id']);
        [$limit, $page, $offset] = H::paginate($ctx);
        $q = DB::table('webhook_deliveries')->where('tenant_id', $ctx->tenantId)->where('endpoint_id', $e->id);
        if ($s = $ctx->query('status')) $q->where('status', (string) $s);
        $total = (clone $q)->count();
        $rows = $q->orderByDesc('created_at')->offset($offset)->limit($limit)->get(['id', 'event', 'status', 'attempts', 'response_code', 'error', 'next_attempt_at', 'created_at', 'delivered_at']);
        return H::json(['data' => $rows, 'total' => $total, 'page' => $page, 'limit' => $limit]);
    }

    /** Dead-letter replay: reset the attempt counter and deliver again. */
    public function replay(Ctx $ctx)
    {
        $this->guard($ctx, true);
        $d = DB::table('webhook_deliveries')->where('id', $ctx->params['id'])->where('tenant_id', $ctx->tenantId)->first();
        if (!$d) throw new ApiException(404, 'Доставка не найдена', 'not_found');
        DB::table('webhook_deliveries')->where('id', $d->id)->update(['status' => 'pending', 'attempts' => 0, 'error' => null, 'next_attempt_at' => H::now()]);
        return H::json(['status' => WebhookService::deliver($d->id)]);
    }
}
