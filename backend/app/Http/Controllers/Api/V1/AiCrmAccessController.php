<?php
namespace App\Http\Controllers\Api\V1;

use App\Services\AiCrmAccess;
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

    public function show(Ctx $ctx)
    {
        $this->guard($ctx, false);
        return H::json(AiCrmAccess::status($ctx->tenantId));
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

    /** Creates a read-only key named for the AI and connects it in one step. The token is shown once. */
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
        $token = 'nxk_' . bin2hex(random_bytes(24));
        $id = H::newId('key');
        DB::table('api_keys')->insert([
            'id' => $id, 'tenant_id' => $ctx->tenantId, 'name' => 'AI assistant (CRM read-only)', 'prefix' => substr($token, 0, 12),
            'key_hash' => hash('sha256', $token), 'scopes' => json_encode(['crm.read']), 'expires_at' => null,
            'created_by' => $ctx->user['id'], 'created_at' => H::now(),
        ]);
        H::audit($ctx, 'api_key.created', 'api_key', $id, ['scopes' => ['crm.read'], 'for' => 'ai']);
        $res = $this->save($ctx, $id, 201)->getData(true);
        return H::json($res + ['token' => $token, 'note' => 'Kalit faqat bir marta ko‘rsatiladi. AI uchun uni saqlash shart emas — u allaqachon ulangan.'], 201);
    }

    public function disconnect(Ctx $ctx)
    {
        $this->guard($ctx, true);
        DB::table('ai_crm_access')->where('tenant_id', $ctx->tenantId)->delete();
        H::audit($ctx, 'ai.crm_access_disconnected', 'tenant', $ctx->tenantId);
        return H::json(AiCrmAccess::status($ctx->tenantId));
    }

    private function save(Ctx $ctx, string $keyId, int $status)
    {
        DB::table('ai_crm_access')->insert(['tenant_id' => $ctx->tenantId, 'api_key_id' => $keyId, 'connected_by' => $ctx->user['id'], 'connected_at' => H::now()]);
        H::audit($ctx, 'ai.crm_access_connected', 'api_key', $keyId);
        return H::json(AiCrmAccess::status($ctx->tenantId), $status);
    }
}
