<?php
namespace App\Services;

use App\Support\H;
use Illuminate\Support\Facades\DB;

/**
 * The company's one API key that the AI assistant reads CRM data through. Access = that key's state and scopes:
 * no key, a revoked or expired key, or a key without crm.read means the AI sees no CRM data at all.
 */
final class AiCrmAccess
{
    /** @return array{connected:bool, key_id:?string, key_name:?string, prefix:?string, scopes:list<string>, status:string, connected_at:?string, last_used_at:?string} */
    public static function status(string $tenantId): array
    {
        $a = DB::table('ai_crm_access')->where('tenant_id', $tenantId)->first();
        $empty = ['connected' => false, 'key_id' => null, 'key_name' => null, 'prefix' => null, 'scopes' => [], 'status' => 'not_connected', 'connected_at' => null, 'last_used_at' => null];
        if (!$a) return $empty;
        $k = DB::table('api_keys')->where('id', $a->api_key_id)->where('tenant_id', $tenantId)->first();
        if (!$k) return ['status' => 'key_missing'] + $empty;
        $scopes = (array) H::parseJson($k->scopes, []);
        $state = $k->revoked_at ? 'revoked' : (($k->expires_at && $k->expires_at < H::now()) ? 'expired' : (in_array('crm.read', $scopes, true) ? 'active' : 'no_read_scope'));
        return ['connected' => $state === 'active', 'key_id' => $k->id, 'key_name' => $k->name, 'prefix' => $k->prefix, 'scopes' => $scopes, 'status' => $state,
            'connected_at' => $a->connected_at, 'last_used_at' => $a->last_used_at];
    }

    /** True when the AI may read this company's CRM data. Records the use. */
    public static function allows(string $tenantId): bool
    {
        $st = self::status($tenantId);
        if (!$st['connected']) return false;
        DB::table('ai_crm_access')->where('tenant_id', $tenantId)->update(['last_used_at' => H::now()]);
        return true;
    }
}
