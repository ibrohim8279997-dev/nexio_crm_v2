<?php
namespace App\Plugins;

use App\Support\ApiException;
use App\Support\Ctx;
use App\Support\H;
use Illuminate\Support\Facades\DB;

/**
 * Encrypted storage for third-party provider credentials.
 *
 * Guarantees:
 *  - ciphertext only is persisted; plaintext never touches the database, the logs or any API response;
 *  - the encryption key comes from the environment (PLUGIN_SECRETS_KEY), never from the database,
 *    never from source control, and there is NO silent fallback to APP_KEY;
 *  - every lookup is tenant-scoped, so another company's credentials are unreachable by id guessing.
 *
 * Cipher: AES-256-GCM (authenticated) via openssl, key = SHA-256 of the configured secret.
 */
class SecretsVault
{
    public const MASK = '••••••••';

    private static function key(): string
    {
        $raw = (string) config('nexio.plugins.secrets_key', '');
        if (trim($raw) === '') {
            // Fail loudly. Substituting a default key would silently produce decryptable-at-rest
            // credentials with a well-known key, which is worse than refusing to store them.
            throw new ApiException(
                500,
                'Хранилище секретов не настроено. Задайте PLUGIN_SECRETS_KEY в окружении сервера.',
                'vault_unconfigured'
            );
        }
        if (str_starts_with($raw, 'base64:')) {
            $bin = base64_decode(substr($raw, 7), true);
            if ($bin === false) {
                throw new ApiException(500, 'PLUGIN_SECRETS_KEY имеет некорректный формат base64', 'vault_unconfigured');
            }
            return hash('sha256', $bin, true);
        }
        if (str_starts_with($raw, 'hex:')) {
            $bin = @hex2bin(substr($raw, 4));
            if ($bin === false) {
                throw new ApiException(500, 'PLUGIN_SECRETS_KEY имеет некорректный hex-формат', 'vault_unconfigured');
            }
            return hash('sha256', $bin, true);
        }
        return hash('sha256', $raw, true);
    }

    private static function encrypt(string $plain): string
    {
        $iv = random_bytes(12);
        $tag = '';
        $ct = openssl_encrypt($plain, 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, $iv, $tag);
        if ($ct === false) {
            throw new ApiException(500, 'Не удалось зашифровать секрет', 'vault_error');
        }
        return json_encode([
            'v' => 1,
            'cipher' => 'aes-256-gcm',
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
            'ct' => base64_encode($ct),
        ], JSON_UNESCAPED_SLASHES) ?: '';
    }

    private static function decrypt(string $payload): ?string
    {
        $d = H::parseJson($payload, null);
        if (!is_array($d) || ($d['cipher'] ?? '') !== 'aes-256-gcm') {
            return null;
        }
        $iv = base64_decode((string) ($d['iv'] ?? ''), true);
        $tag = base64_decode((string) ($d['tag'] ?? ''), true);
        $ct = base64_decode((string) ($d['ct'] ?? ''), true);
        if ($iv === false || $tag === false || $ct === false) {
            return null;
        }
        $plain = openssl_decrypt($ct, 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, $iv, $tag);
        return $plain === false ? null : $plain;
    }

    /** Stable, non-reversible fingerprint used to detect "same value" without decrypting. */
    public static function fingerprint(string $value): string
    {
        return substr(hash_hmac('sha256', $value, self::key()), 0, 32);
    }

    /**
     * Store or rotate a secret. Rotation overwrites in place and records rotated_at.
     * Writing the same value twice is idempotent for the fingerprint but still refreshes the row.
     */
    public static function put(Ctx $ctx, string $pluginId, ?string $connectionId, string $key, string $value): void
    {
        $tenantId = $ctx->tenantId;
        if (!$tenantId) {
            throw new ApiException(403, 'Операция доступна только в контексте компании', 'forbidden');
        }
        if (trim($key) === '' || strlen($key) > 64 || !preg_match('/^[a-z0-9_.\-]+$/i', $key)) {
            throw new ApiException(400, 'Недопустимое имя секрета', 'bad_request');
        }
        if (trim($value) === '') {
            throw new ApiException(400, 'Значение секрета не может быть пустым', 'bad_request');
        }
        if (strlen($value) > 8192) {
            throw new ApiException(400, 'Значение секрета слишком длинное', 'bad_request');
        }

        $now = H::now();
        $existing = self::row($tenantId, $pluginId, $connectionId, $key);
        $data = [
            'ciphertext' => self::encrypt($value),
            'fingerprint' => self::fingerprint($value),
            'rotated_at' => $now,
            'updated_at' => $now,
            'created_by' => $ctx->user['id'] ?? null,
        ];
        if ($existing) {
            DB::update(
                'UPDATE plugin_secrets SET ciphertext=?, fingerprint=?, rotated_at=?, updated_at=? WHERE id=? AND tenant_id=?',
                [$data['ciphertext'], $data['fingerprint'], $now, $now, $existing->id, $tenantId]
            );
            return;
        }
        DB::insert(
            'INSERT INTO plugin_secrets (id, tenant_id, plugin_id, connection_id, key, ciphertext, key_version, fingerprint, created_by, rotated_at, created_at, updated_at)
             VALUES (?,?,?,?,?,?,1,?,?,?,?,?)',
            [H::newId('pls'), $tenantId, $pluginId, $connectionId, $key, $data['ciphertext'], $data['fingerprint'], $ctx->user['id'] ?? null, $now, $now, $now]
        );
    }

    /**
     * Internal use only (adapters). Never expose through an API response.
     */
    public static function reveal(?string $tenantId, string $pluginId, ?string $connectionId, string $key): ?string
    {
        $row = self::row($tenantId, $pluginId, $connectionId, $key);
        return $row ? self::decrypt((string) $row->ciphertext) : null;
    }

    public static function has(?string $tenantId, string $pluginId, ?string $connectionId, string $key): bool
    {
        return self::row($tenantId, $pluginId, $connectionId, $key) !== null;
    }

    public static function revoke(?string $tenantId, string $pluginId, ?string $connectionId = null): int
    {
        if ($connectionId === null) {
            return DB::delete('DELETE FROM plugin_secrets WHERE tenant_id=? AND plugin_id=?', [$tenantId, $pluginId]);
        }
        return DB::delete('DELETE FROM plugin_secrets WHERE tenant_id=? AND plugin_id=? AND connection_id=?', [$tenantId, $pluginId, $connectionId]);
    }

    /** Safe descriptor list — keys and timestamps only, never values. */
    public static function describe(?string $tenantId, string $pluginId, ?string $connectionId): array
    {
        $rows = DB::select(
            'SELECT key, updated_at, rotated_at, created_at FROM plugin_secrets
              WHERE tenant_id=? AND plugin_id=? AND (connection_id IS ? OR connection_id = ?)
              ORDER BY key',
            [$tenantId, $pluginId, $connectionId, $connectionId]
        );
        return array_map(fn ($r) => [
            'key' => $r->key,
            'masked' => self::MASK,
            'has_value' => true,
            'updated_at' => $r->updated_at,
        ], $rows);
    }

    private static function row(?string $tenantId, string $pluginId, ?string $connectionId, string $key): ?object
    {
        return DB::selectOne(
            'SELECT id, ciphertext FROM plugin_secrets
              WHERE tenant_id=? AND plugin_id=? AND (connection_id IS ? OR connection_id = ?) AND key=? LIMIT 1',
            [$tenantId, $pluginId, $connectionId, $connectionId, $key]
        );
    }
}
