<?php
namespace App\Plugins;

use App\Support\ApiException;
use App\Support\Ctx;
use App\Support\H;
use Illuminate\Support\Facades\DB;

/**
 * Plugin permission engine.
 *
 * Two separate facts, never conflated:
 *   - DECLARED  — what the plugin's manifest asks for (untrusted input, stored for review);
 *   - GRANTED   — what Super Admin has actually approved (the only thing that opens a capability).
 *
 * A plugin that declares `crm.clients.export` gains nothing until a platform operator grants it.
 *
 * Revocation: `generation` is bumped on every grant/revoke. Decisions are NEVER cached — every
 * `allows()` call re-reads the database — so a revoked permission stops working immediately,
 * including inside long-lived workers that re-check before each sensitive operation.
 */
class PermissionEngine
{
    /** Effective (granted) permissions for a plugin. Fresh read on every call — no cache. */
    public static function effective(string $pluginId): array
    {
        $rows = DB::select(
            'SELECT permission FROM plugin_permissions WHERE plugin_id = ? AND is_granted = 1 AND revoked_at IS NULL ORDER BY permission',
            [$pluginId]
        );
        return array_map(fn ($r) => (string) $r->permission, $rows);
    }

    public static function allows(string $pluginId, string $permission): bool
    {
        $row = DB::selectOne(
            'SELECT is_granted, revoked_at FROM plugin_permissions WHERE plugin_id = ? AND permission = ? LIMIT 1',
            [$pluginId, $permission]
        );
        return $row !== null && (int) $row->is_granted === 1 && $row->revoked_at === null;
    }

    /** Hard gate used by PluginRuntime before any capability is exercised. */
    public static function require(string $pluginId, string $permission): void
    {
        if (!self::allows($pluginId, $permission)) {
            throw new ApiException(
                403,
                "Разрешение «{$permission}» не выдано плагину {$pluginId}",
                'permission_not_granted',
                ['permission' => $permission, 'plugin_id' => $pluginId]
            );
        }
    }

    /** Current generation, for callers that want to detect that a decision changed. */
    public static function generation(string $pluginId): int
    {
        $row = DB::selectOne('SELECT COALESCE(MAX(generation),0) AS g FROM plugin_permissions WHERE plugin_id = ?', [$pluginId]);
        return (int) ($row->g ?? 0);
    }

    /**
     * Record the declared permissions from a validated manifest.
     * Existing grants are preserved; a permission that is no longer declared is revoked.
     */
    public static function syncDeclared(string $pluginId, array $permissions): void
    {
        $now = H::now();
        $set = array_values(array_unique(array_filter($permissions, 'is_string')));

        $existing = DB::select('SELECT permission, is_granted FROM plugin_permissions WHERE plugin_id = ?', [$pluginId]);
        $have = [];
        foreach ($existing as $r) {
            $have[$r->permission] = (int) $r->is_granted;
        }

        foreach ($set as $perm) {
            $sensitive = (Manifest::PERMISSION_CATALOG[$perm] ?? false) ? 1 : 0;
            if (isset($have[$perm])) {
                DB::update(
                    'UPDATE plugin_permissions SET is_declared=1, is_sensitive=?, updated_at=? WHERE plugin_id=? AND permission=?',
                    [$sensitive, $now, $pluginId, $perm]
                );
                continue;
            }
            DB::insert(
                'INSERT INTO plugin_permissions (id, plugin_id, permission, scope, is_declared, is_granted, is_sensitive, generation, created_at, updated_at)
                 VALUES (?,?,?,?,1,0,?,1,?,?)',
                [H::newId('plp'), $pluginId, $perm, 'tenant', $sensitive, $now, $now]
            );
        }

        // Declarations dropped from the manifest must not keep an old grant alive.
        foreach ($have as $perm => $granted) {
            if (!in_array($perm, $set, true)) {
                DB::update(
                    'UPDATE plugin_permissions SET is_declared=0, is_granted=0, revoked_at=?, generation=generation+1, updated_at=? WHERE plugin_id=? AND permission=?',
                    [$now, $now, $pluginId, $perm]
                );
            }
        }
    }

    public static function grant(Ctx $admin, string $pluginId, string $permission): void
    {
        if (!array_key_exists($permission, Manifest::PERMISSION_CATALOG)) {
            throw new ApiException(400, "Неизвестное разрешение «{$permission}»", 'bad_request');
        }
        $row = DB::selectOne('SELECT id FROM plugin_permissions WHERE plugin_id = ? AND permission = ? LIMIT 1', [$pluginId, $permission]);
        $now = H::now();
        if ($row) {
            DB::update(
                'UPDATE plugin_permissions SET is_granted=1, revoked_at=NULL, granted_by=?, granted_at=?, generation=generation+1, updated_at=? WHERE id=?',
                [$admin->user['id'] ?? null, $now, $now, $row->id]
            );
            return;
        }
        DB::insert(
            'INSERT INTO plugin_permissions (id, plugin_id, permission, scope, is_declared, is_granted, is_sensitive, generation, granted_by, granted_at, created_at, updated_at)
             VALUES (?,?,?,?,0,1,?,1,?,?,?,?)',
            [H::newId('plp'), $pluginId, $permission, 'tenant', (Manifest::PERMISSION_CATALOG[$permission] ?? false) ? 1 : 0, $admin->user['id'] ?? null, $now, $now, $now]
        );
    }

    public static function revoke(Ctx $admin, string $pluginId, string $permission): void
    {
        $now = H::now();
        DB::update(
            'UPDATE plugin_permissions SET is_granted=0, revoked_at=?, generation=generation+1, updated_at=? WHERE plugin_id=? AND permission=?',
            [$now, $now, $pluginId, $permission]
        );
    }

    /** Full declared/granted picture for the Super Admin review screen. */
    public static function listFor(string $pluginId): array
    {
        $rows = DB::select(
            'SELECT permission, is_declared, is_granted, is_sensitive, generation, granted_at, revoked_at
               FROM plugin_permissions WHERE plugin_id = ? ORDER BY is_sensitive DESC, permission',
            [$pluginId]
        );
        return array_map(fn ($r) => [
            'permission' => $r->permission,
            'declared' => (bool) $r->is_declared,
            'granted' => (bool) $r->is_granted,
            'sensitive' => (bool) $r->is_sensitive,
            'generation' => (int) $r->generation,
            'granted_at' => $r->granted_at,
            'revoked_at' => $r->revoked_at,
        ], $rows);
    }

    /** The catalog offered in the Super Admin UI. */
    public static function catalog(): array
    {
        $out = [];
        foreach (Manifest::PERMISSION_CATALOG as $perm => $sensitive) {
            $out[] = ['permission' => $perm, 'sensitive' => (bool) $sensitive];
        }
        return $out;
    }
}
