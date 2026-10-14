<?php
namespace App\Plugins;

use Illuminate\Support\Facades\DB;

/**
 * Everything a plugin is allowed to know about the caller.
 *
 * A plugin receives this object instead of the request, the container or the database handle.
 * It therefore has no way to reach another company's data: every accessor below is scoped to the
 * tenant captured at construction time, and the tenant can only be set from trusted server state.
 *
 * There is deliberately no `query()`, no `db()` and no filesystem accessor.
 */
class PluginContext
{
    public function __construct(
        public readonly string $tenantId,
        public readonly string $pluginId,
        public readonly ?string $installationId = null,
        public readonly ?string $connectionId = null,
        public readonly ?string $userId = null,
        public readonly ?string $planId = null,
    ) {}

    /** A non-secret setting, falling back to the connection-specific value. */
    public function setting(string $key, mixed $default = null): mixed
    {
        if (!$this->installationId) {
            return $default;
        }
        $row = DB::selectOne(
            'SELECT value, value_type FROM plugin_settings
              WHERE tenant_id = ? AND installation_id = ? AND key = ?
                AND (connection_id = ? OR connection_id IS NULL)
              ORDER BY (connection_id IS NULL) LIMIT 1',
            [$this->tenantId, $this->installationId, $key, $this->connectionId]
        );
        if (!$row) {
            return $default;
        }
        return match ($row->value_type) {
            'number' => is_numeric($row->value) ? $row->value + 0 : $default,
            'boolean' => in_array((string) $row->value, ['1', 'true', 'yes'], true),
            'json' => \App\Support\H::parseJson($row->value, $default),
            default => $row->value,
        };
    }

    /** @return array<string,mixed> */
    public function settings(): array
    {
        if (!$this->installationId) {
            return [];
        }
        $rows = DB::select(
            'SELECT key, value, value_type FROM plugin_settings WHERE tenant_id = ? AND installation_id = ?',
            [$this->tenantId, $this->installationId]
        );
        $out = [];
        foreach ($rows as $r) {
            $out[$r->key] = match ($r->value_type) {
                'number' => is_numeric($r->value) ? $r->value + 0 : null,
                'boolean' => in_array((string) $r->value, ['1', 'true', 'yes'], true),
                'json' => \App\Support\H::parseJson($r->value, null),
                default => $r->value,
            };
        }
        return $out;
    }

    /** Decrypted credential for internal use inside a plugin. Never returned to a client. */
    public function secret(string $key): ?string
    {
        return SecretsVault::reveal($this->tenantId, $this->pluginId, $this->connectionId, $key);
    }

    public function hasSecret(string $key): bool
    {
        return SecretsVault::has($this->tenantId, $this->pluginId, $this->connectionId, $key);
    }

    /** The connection row this invocation is bound to, tenant-checked. */
    public function connection(): ?object
    {
        if (!$this->connectionId) {
            return null;
        }
        return DB::selectOne(
            'SELECT * FROM plugin_connections WHERE id = ? AND tenant_id = ? AND plugin_id = ?',
            [$this->connectionId, $this->tenantId, $this->pluginId]
        );
    }

    /** @return array<string,mixed> */
    public function connectionConfig(): array
    {
        $c = $this->connection();
        return $c ? \App\Support\H::parseJson($c->config, []) : [];
    }

    /** How many connections exist for this installation (used to auto-select a single one). */
    public function connections(): array
    {
        if (!$this->installationId) {
            return [];
        }
        return DB::select(
            'SELECT id, name, provider, status, mode FROM plugin_connections WHERE tenant_id = ? AND installation_id = ? ORDER BY name',
            [$this->tenantId, $this->installationId]
        );
    }

    public function timezone(): string
    {
        $row = DB::selectOne('SELECT timezone FROM tenants WHERE id = ?', [$this->tenantId]);
        return (string) ($row->timezone ?: 'UTC');
    }

    /** @return string[] Outbound hosts this plugin may contact. Empty means "no network". */
    public function allowedHosts(): array
    {
        // Requested explicitly so tests never inherit a stray config value.
        // The default `[]` makes `outboundAllowed()` fail closed and is only overridden in
        // config/nexio.php (where hosts are confirmed against real documentation).
        return config("nexio.plugins.allowed_outbound_hosts.{$this->pluginId}", []);
    }

    /**
     * SSRF guard for plugin adapters. Only hosts on the plugin's allowlist are reachable;
     * an empty allowlist means the plugin must not make outbound calls at all.
     */
    public function outboundAllowed(string $host): bool
    {
        $host = strtolower(trim($host));
        if ($host === '') {
            return false;
        }
        foreach ($this->allowedHosts() as $allowed) {
            $allowed = strtolower(ltrim($allowed, '.'));
            if ($host === $allowed || str_ends_with($host, '.' . $allowed)) {
                return true;
            }
        }
        return false;
    }
}
