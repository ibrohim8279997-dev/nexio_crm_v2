<?php
namespace App\Plugins;

use App\Support\ApiException;
use Illuminate\Support\Facades\DB;

/**
 * Declared plugin dependencies.
 *
 * Dependencies are resolved per COMPANY, not globally: plugin B may be installed for tenant X
 * while tenant Y has only plugin A. A missing required dependency blocks the install with a clear,
 * actionable reason instead of half-installing.
 */
class DependencyResolver
{
    /** Dependencies declared by a plugin version (normalised). */
    public static function declared(string $pluginId): array
    {
        // `constraint` is a reserved word in SQLite, so the column is returned unaliased.
        $rows = DB::select(
            'SELECT d.depends_on_plugin_id AS plugin_id, d.version_constraint, d.is_optional,
                    p.name, p.is_published, p.is_blocked
               FROM plugin_dependencies d LEFT JOIN plugins p ON p.id = d.depends_on_plugin_id
              WHERE d.plugin_id = ? ORDER BY d.is_optional, d.depends_on_plugin_id',
            [$pluginId]
        );
        return array_map(fn ($r) => [
            'plugin_id' => (string) $r->plugin_id,
            'constraint' => (string) $r->version_constraint,
            'optional' => (bool) $r->is_optional,
            'name' => $r->name,
            'published' => (bool) $r->is_published,
        ], $rows);
    }

    /** Replace the declared dependency set for a plugin (used on version registration). */
    public static function sync(string $pluginId, array $dependencies): void
    {
        DB::delete('DELETE FROM plugin_dependencies WHERE plugin_id = ?', [$pluginId]);
        foreach ($dependencies as $d) {
            $depId = (string) ($d['plugin_id'] ?? '');
            if ($depId === '' || $depId === $pluginId) {
                continue;
            }
            DB::insert(
                'INSERT INTO plugin_dependencies (id, plugin_id, depends_on_plugin_id, version_constraint, is_optional, created_at)
                 VALUES (?,?,?,?,?,?) ON CONFLICT(plugin_id, depends_on_plugin_id) DO NOTHING',
                [\App\Support\H::newId('pld'), $pluginId, $depId, (string) ($d['constraint'] ?? '*'), !empty($d['optional']) ? 1 : 0, \App\Support\H::now()]
            );
        }
    }

    /**
     * Which required dependencies are not satisfied for this company?
     *
     * @return array{ok:bool,missing:array<int,array{plugin_id:string,constraint:string,optional:bool}>,reason:string}
     */
    public static function checkInstall(string $tenantId, string $pluginId): array
    {
        $missing = [];
        foreach (self::declared($pluginId) as $dep) {
            if ($dep['optional']) {
                continue;
            }
            $installation = DB::selectOne(
                "SELECT i.status, v.version
                   FROM plugin_installations i LEFT JOIN plugin_versions v ON v.id = i.version_id
                  WHERE i.tenant_id = ? AND i.plugin_id = ? AND i.status IN ('installed','enabled')",
                [$tenantId, $dep['plugin_id']]
            );
            if (!$installation) {
                $missing[] = $dep;
                continue;
            }
            $installedVersion = (string) ($installation->version ?? '0.0.0');
            if ($dep['constraint'] !== '*' && !Semver::satisfies($installedVersion, $dep['constraint'])) {
                $missing[] = [...$dep, 'reason' => "Установлена версия {$installedVersion}, требуется {$dep['constraint']}"];
            }
        }

        return [
            'ok' => $missing === [],
            'missing' => $missing,
            'reason' => $missing === []
                ? ''
                : 'Сначала установите: ' . implode(', ', array_map(fn ($m) => $m['plugin_id'], $missing)),
        ];
    }

    /** Plugins that depend on this one — blocks uninstall while they are installed. */
    public static function dependents(string $tenantId, string $pluginId): array
    {
        $rows = DB::select(
            "SELECT d.plugin_id, p.name
               FROM plugin_dependencies d
               JOIN plugin_installations i ON i.plugin_id = d.plugin_id AND i.tenant_id = ?
               LEFT JOIN plugins p ON p.id = d.plugin_id
              WHERE d.depends_on_plugin_id = ? AND d.is_optional = 0",
            [$tenantId, $pluginId]
        );
        return array_map(fn ($r) => ['plugin_id' => (string) $r->plugin_id, 'name' => $r->name], $rows);
    }

    /** An uninstall is refused while a required dependent is still installed. */
    public static function assertUninstallable(string $tenantId, string $pluginId): void
    {
        $blockers = self::dependents($tenantId, $pluginId);
        if ($blockers !== []) {
            $names = implode(', ', array_map(fn ($b) => $b['plugin_id'], $blockers));
            throw new ApiException(
                409,
                "Нельзя удалить плагин: от него зависят установленные плагины ({$names})",
                'dependency_required',
                ['blockers' => $blockers]
            );
        }
    }
}
