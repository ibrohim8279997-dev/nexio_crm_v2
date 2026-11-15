<?php
namespace App\Http\Controllers\Api\V1;

use App\Services\AiActions;
use App\Services\AiCrmAccess;
use App\Services\AiQuota;
use App\Services\GroqAi;
use App\Support\ApiException;
use App\Support\Ctx;
use App\Support\H;
use Illuminate\Support\Facades\DB;

/** AI settings → "CRM access": connect the company's API key once so the AI assistant can read its CRM data. */
class AiCrmAccessController
{
    private function guard(Ctx $ctx, bool $write): void
    {
        $ctx->auth(); H::requireTenant($ctx);
        if ($ctx->role === 'api_key') throw new ApiException(403, 'API kalit bilan bu sozlamani boshqarib bo‘lmaydi', 'forbidden');
        H::requirePermission($ctx, $write ? 'ai.write' : 'ai.read');
    }

    /** Status plus what the AI may do with the key's rights (read always; create lead/contact/task/project per scope). */
    private function view(string $tenantId): array
    {
        $st = AiCrmAccess::status($tenantId);
        return $st + ['actions' => AiActions::capabilities($st['scopes']), 'ai_scopes' => AiActions::AI_SCOPES];
    }

    public function show(Ctx $ctx)
    {
        $this->guard($ctx, false);
        return H::json($this->view($ctx->tenantId));
    }

    /** Which rights an AI key is created with: crm.read always, plus the chosen ones (each must be held by the user too). */
    private function requestedScopes(Ctx $ctx, array $body): array
    {
        $asked = is_array($body['scopes'] ?? null) ? $body['scopes'] : [];
        $scopes = ['crm.read'];
        foreach ($asked as $s) {
            if (!is_string($s) || $s === 'crm.read') continue;
            if (!in_array($s, AiActions::AI_SCOPES, true)) throw new ApiException(422, "AI uchun «{$s}» huquqi mavjud emas", 'validation_failed');
            H::requirePermission($ctx, $s);
            $scopes[] = $s;
        }
        return array_values(array_unique($scopes));
    }

    /** Changes the rights of the key the AI was given (only for keys created here: a hand-made key is edited on the API keys page). */
    public function updateScopes(Ctx $ctx)
    {
        $this->guard($ctx, true);
        H::requirePermission($ctx, 'integrations.write');
        $st = AiCrmAccess::status($ctx->tenantId);
        $key = $st['key_id'] ? DB::table('api_keys')->where('id', $st['key_id'])->where('tenant_id', $ctx->tenantId)->first() : null;
        if (!$key) throw new ApiException(404, 'AI uchun kalit ulanmagan', 'not_found');
        if (!str_starts_with((string) $key->name, 'AI assistant')) {
            throw new ApiException(409, 'Bu kalit qo‘lda yaratilgan: huquqlarni «API kalitlar» sahifasida o‘zgartiring', 'conflict');
        }
        $scopes = $this->requestedScopes($ctx, H::body($ctx));
        DB::table('api_keys')->where('id', $key->id)->update(['scopes' => json_encode($scopes)]);
        H::audit($ctx, 'api_key.scopes_changed', 'api_key', $key->id, ['scopes' => $scopes, 'for' => 'ai']);
        return H::json($this->view($ctx->tenantId));
    }

    /** One-click health check of everything the AI needs: provider, key, rights, plan allowance. */
    public function check(Ctx $ctx)
    {
        $this->guard($ctx, false);
        $groq = new GroqAi();
        $configured = $groq->health()['configured'] || DB::table('platform_provider_configs')->where('provider', 'groq')->where('enabled', 1)->exists();
        $reachable = false;
        if ($configured) { try { $groq->ping(); $reachable = true; } catch (\Throwable) {} }
        $st = AiCrmAccess::status($ctx->tenantId);
        $deny = AiQuota::deny($ctx->tenantId);
        $tenantCounts = ['leads', 'contacts', 'projects', 'tasks', 'orders'];
        $seen = array_combine($tenantCounts, array_map(fn ($t) => DB::table($t)->where('tenant_id', $ctx->tenantId)->count(), $tenantCounts));
        $checks = [
            ['name' => 'provider_configured', 'ok' => $configured],
            ['name' => 'provider_reachable', 'ok' => $reachable],
            ['name' => 'crm_key_active', 'ok' => $st['connected']],
            ['name' => 'allowance', 'ok' => $deny === null, 'detail' => $deny],
            ['name' => 'can_create', 'ok' => count(array_filter(AiActions::capabilities($st['scopes']))) > 0, 'detail' => 'optional'],
        ];
        return H::json(['ok' => !array_filter($checks, fn ($c) => !$c['ok'] && ($c['detail'] ?? '') !== 'optional'), 'checks' => $checks, 'visible_records' => $seen]);
    }

    /** Body: {token}. The pasted key must be this company's own, active, with crm.read. Only its id is stored. */
    public function connect(Ctx $ctx)
    {
        $this->guard($ctx, true);
        if (DB::table('ai_crm_access')->where('tenant_id', $ctx->tenantId)->exists()) {
            throw new ApiException(409, 'AI uchun kalit allaqachon ulangan. Almashtirish uchun avval uzing.', 'already_connected');
        }
        $token = trim((string) (H::body($ctx)['token'] ?? ''));
        if (!str_starts_with($token, 'nxk_')) throw new ApiException(422, 'Kalit nxk_ bilan boshlanishi kerak', 'validation_failed');
        $key = DB::table('api_keys')->where('key_hash', hash('sha256', $token))->where('tenant_id', $ctx->tenantId)->whereNull('parent_id')->first();
        if (!$key || $key->revoked_at || ($key->expires_at && $key->expires_at < H::now())) throw new ApiException(422, 'Kalit topilmadi, bekor qilingan yoki muddati tugagan', 'validation_failed');
        if (!in_array('crm.read', (array) H::parseJson($key->scopes, []), true)) throw new ApiException(422, 'Kalitda «crm.read» huquqi bo‘lishi kerak', 'validation_failed');
        return $this->save($ctx, $key->id, 200);
    }

    /** Creates a key named for the AI (body.scopes: extra rights it may use to create records) and connects it. The token is shown once. */
    public function generate(Ctx $ctx)
    {
        $this->guard($ctx, true);
        H::requirePermission($ctx, 'integrations.write');
        H::requirePermission($ctx, 'crm.read');
        if (DB::table('ai_crm_access')->where('tenant_id', $ctx->tenantId)->exists()) {
            throw new ApiException(409, 'AI uchun kalit allaqachon ulangan. Almashtirish uchun avval uzing.', 'already_connected');
        }
        if (DB::table('api_keys')->where('tenant_id', $ctx->tenantId)->whereNull('parent_id')->whereNull('revoked_at')->count() >= 25) {
            throw new ApiException(402, 'Faol kalitlar limiti (25) tugagan', 'quota_exceeded');
        }
        $scopes = $this->requestedScopes($ctx, H::body($ctx));
        $token = 'nxk_' . bin2hex(random_bytes(24));
        $id = H::newId('key');
        DB::table('api_keys')->insert([
            'id' => $id, 'tenant_id' => $ctx->tenantId, 'name' => count($scopes) > 1 ? 'AI assistant (CRM read + create)' : 'AI assistant (CRM read-only)', 'prefix' => substr($token, 0, 12),
            'key_hash' => hash('sha256', $token), 'scopes' => json_encode($scopes), 'expires_at' => null,
            'created_by' => $ctx->user['id'], 'created_at' => H::now(),
        ]);
        H::audit($ctx, 'api_key.created', 'api_key', $id, ['scopes' => $scopes, 'for' => 'ai']);
        $res = $this->save($ctx, $id, 201)->getData(true);
        return H::json($res + ['token' => $token, 'note' => 'Kalit faqat bir marta ko‘rsatiladi. AI uchun uni saqlash shart emas — u allaqachon ulangan.'], 201);
    }

    public function disconnect(Ctx $ctx)
    {
        $this->guard($ctx, true);
        DB::table('ai_crm_access')->where('tenant_id', $ctx->tenantId)->delete();
        H::audit($ctx, 'ai.crm_access_disconnected', 'tenant', $ctx->tenantId);
        return H::json($this->view($ctx->tenantId));
    }

    private function save(Ctx $ctx, string $keyId, int $status)
    {
        DB::table('ai_crm_access')->insert(['tenant_id' => $ctx->tenantId, 'api_key_id' => $keyId, 'connected_by' => $ctx->user['id'], 'connected_at' => H::now()]);
        H::audit($ctx, 'ai.crm_access_connected', 'api_key', $keyId);
        return H::json($this->view($ctx->tenantId), $status);
    }
}
