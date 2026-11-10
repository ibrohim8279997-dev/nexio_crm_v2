<?php

namespace App\Http\Controllers;

use App\Support\H;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * OAuth2 client_credentials grant (RFC 6749 §4.4) for server-to-server integrations.
 *
 * The client is an existing API key: client_id = key id, client_secret = the key's token. The
 * response is a short-lived access token that carries the same or fewer scopes and is accepted by
 * the same Bearer middleware as a normal key. Revoking the parent key revokes its access tokens.
 */
class OAuthController
{
    public const TTL = 3600;

    public function token(Request $request)
    {
        $ip = $request->ip();
        $bucket = 'rl:oauth:' . $ip . ':' . gmdate('YmdHi');
        $n = (int) Cache::get($bucket, 0);
        if ($n >= 30) return $this->err('rate_limited', 'Too many requests', 429);
        Cache::put($bucket, $n + 1, 90);

        // RFC 6749: credentials may come in the body or as HTTP Basic.
        $clientId = (string) $request->input('client_id', '');
        $secret = (string) $request->input('client_secret', '');
        if ($clientId === '' && $request->getUser() !== null) {
            $clientId = (string) $request->getUser();
            $secret = (string) $request->getPassword();
        }
        if ($request->input('grant_type') !== 'client_credentials') return $this->err('unsupported_grant_type', 'Only client_credentials is supported');
        if ($clientId === '' || $secret === '') return $this->err('invalid_request', 'client_id and client_secret are required');

        $key = DB::table('api_keys')->where('id', $clientId)->whereNull('parent_id')->first();
        if (!$key || !hash_equals((string) $key->key_hash, hash('sha256', $secret)) || $key->revoked_at || ($key->expires_at && $key->expires_at < H::now())) {
            return $this->err('invalid_client', 'Client authentication failed', 401);
        }

        $granted = H::parseJson($key->scopes, []);
        $requested = trim((string) $request->input('scope', ''));
        $scopes = $granted;
        if ($requested !== '') {
            $scopes = array_values(array_unique(preg_split('/\s+/', $requested) ?: []));
            if (array_diff($scopes, $granted)) return $this->err('invalid_scope', 'Requested scope exceeds the client grant');
        }

        // A client can hold a bounded number of live tokens; expired ones are pruned here.
        DB::table('api_keys')->where('parent_id', $key->id)->where('expires_at', '<', H::now())->delete();
        if (DB::table('api_keys')->where('parent_id', $key->id)->count() >= 50) return $this->err('rate_limited', 'Too many active access tokens', 429);

        $token = 'nxk_' . bin2hex(random_bytes(24));
        $expires = gmdate('Y-m-d H:i:s', time() + self::TTL);
        DB::table('api_keys')->insert([
            'id' => H::newId('key'), 'tenant_id' => $key->tenant_id, 'name' => 'oauth:' . $key->name, 'prefix' => substr($token, 0, 12),
            'key_hash' => hash('sha256', $token), 'scopes' => json_encode($scopes), 'expires_at' => $expires,
            'created_by' => $key->created_by, 'created_at' => H::now(), 'parent_id' => $key->id,
        ]);

        return response()->json([
            'access_token' => $token, 'token_type' => 'Bearer', 'expires_in' => self::TTL, 'scope' => implode(' ', $scopes),
        ])->withHeaders(['Cache-Control' => 'no-store', 'Pragma' => 'no-cache']);
    }

    private function err(string $code, string $description, int $status = 400)
    {
        return response()->json(['error' => $code, 'error_description' => $description], $status)->withHeaders(['Cache-Control' => 'no-store']);
    }
}
