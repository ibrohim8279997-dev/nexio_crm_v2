<?php
namespace App\Plugins;

use App\Support\ApiException;
use Illuminate\Support\Facades\DB;

/**
 * Plugin version selection and the publication state machine.
 *
 * Publication and installation are separate operations (brief §14): approving a version does not
 * move any customer onto it. A company only receives a new version when it explicitly updates,
 * and only to a version that is PUBLISHED and compatible with the running Nexio version.
 */
class VersionManager
{
    /** Allowed transitions. Anything else is refused, so a version can never skip review. */
    public const TRANSITIONS = [
        'DRAFT' => ['SUBMITTED', 'RETIRED'],
        'SUBMITTED' => ['UNDER_REVIEW', 'REJECTED', 'DRAFT'],
        'UNDER_REVIEW' => ['TESTING', 'REJECTED', 'APPROVED'],
        'TESTING' => ['APPROVED', 'REJECTED'],
        'APPROVED' => ['PUBLISHED', 'REJECTED', 'RETIRED'],
        'PUBLISHED' => ['DEPRECATED', 'RETIRED'],
        'DEPRECATED' => ['RETIRED', 'PUBLISHED'],
        'REJECTED' => ['DRAFT'],
        'RETIRED' => [],
    ];

    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[strtoupper($from)] ?? [], true);
    }

    public static function assertTransition(string $from, string $to): void
    {
        if (!self::canTransition($from, $to)) {
            throw new ApiException(
                409,
                "Недопустимый переход версии: {$from} → {$to}",
                'invalid_transition',
                ['from' => $from, 'to' => $to, 'allowed' => self::TRANSITIONS[strtoupper($from)] ?? []]
            );
        }
    }

    public static function find(string $versionId): ?object
    {
        return DB::selectOne('SELECT * FROM plugin_versions WHERE id = ?', [$versionId]);
    }

    /** Tenant-scoped fetch used by the Marketplace: only published, non-retired versions. */
    public static function findPublished(string $pluginId, string $version): ?object
    {
        return DB::selectOne(
            "SELECT * FROM plugin_versions WHERE plugin_id = ? AND version = ? AND status IN ('PUBLISHED','DEPRECATED')",
            [$pluginId, $version]
        );
    }

    /** The version a tenant would get on install: highest PUBLISHED, Nexio-compatible version. */
    public static function bestPublished(string $pluginId): ?object
    {
        $rows = DB::select(
            "SELECT * FROM plugin_versions WHERE plugin_id = ? AND status = 'PUBLISHED'",
            [$pluginId]
        );
        $best = null;
        foreach ($rows as $row) {
            if (!self::isCompatibleWithNexio((string) $row->min_nexio_version)) {
                continue;
            }
            if ($best === null || Semver::compare((string) $row->version, (string) $best->version) > 0) {
                $best = $row;
            }
        }
        return $best;
    }

    public static function isCompatibleWithNexio(string $minNexioVersion): bool
    {
        $running = (string) config('nexio.plugins.nexio_version', '2.0.0');
        return Semver::compare($running, $minNexioVersion) >= 0;
    }

    /**
     * The highest published version this company is allowed to run, honouring both the Nexio
     * version and any previously running version constraint.
     */
    public static function upgradeTarget(string $pluginId, ?string $fromVersion): ?object
    {
        $best = self::bestPublished($pluginId);
        if (!$best) {
            return null;
        }
        if ($fromVersion !== null && Semver::compare((string) $best->version, $fromVersion) <= 0) {
            return null; // nothing newer
        }
        return $best;
    }

    /** Rollback target: the highest published version strictly below the running one. */
    public static function rollbackTarget(string $pluginId, string $fromVersion): ?object
    {
        $rows = DB::select(
            "SELECT * FROM plugin_versions WHERE plugin_id = ? AND status IN ('PUBLISHED','DEPRECATED')",
            [$pluginId]
        );
        $best = null;
        foreach ($rows as $row) {
            if (Semver::compare((string) $row->version, $fromVersion) >= 0) {
                continue;
            }
            if (!self::isCompatibleWithNexio((string) $row->min_nexio_version)) {
                continue;
            }
            if ($best === null || Semver::compare((string) $row->version, (string) $best->version) > 0) {
                $best = $row;
            }
        }
        return $best;
    }

    public static function listFor(string $pluginId): array
    {
        return DB::select(
            'SELECT id, plugin_id, version, status, changelog, checksum, package_size, min_nexio_version,
                    has_migrations, submitted_at, reviewed_at, published_at, review_notes
               FROM plugin_versions WHERE plugin_id = ? ORDER BY created_at DESC',
            [$pluginId]
        );
    }

    /** Older published versions are marked DEPRECATED once a newer one is published. */
    public static function deprecateOlderThan(string $pluginId, string $publishedVersion): void
    {
        $rows = DB::select(
            "SELECT id, version FROM plugin_versions WHERE plugin_id = ? AND status = 'PUBLISHED'",
            [$pluginId]
        );
        foreach ($rows as $row) {
            if (Semver::compare((string) $row->version, $publishedVersion) < 0) {
                DB::update("UPDATE plugin_versions SET status='DEPRECATED', updated_at=? WHERE id=?", [\App\Support\H::now(), $row->id]);
            }
        }
    }
}
