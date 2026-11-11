<?php

namespace App\Services;

use App\Support\H;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Operational health snapshot shared by GET /api/v1/platform/health and `nexio:health:alert`.
 * Each check returns {name, ok, detail}; overall status is "ok" only when every check passes.
 */
class HealthChecks
{
    public const HEARTBEAT_KEY = 'nexio:scheduler:heartbeat';

    public static function run(): array
    {
        $checks = array_values(array_filter([self::database(), self::replica(), self::scheduler(), self::queue(), self::webhooks(), self::disk(), self::backup()]));
        $failed = array_values(array_filter($checks, fn ($c) => !$c['ok']));
        return [
            'status' => $failed ? 'degraded' : 'ok',
            'time' => gmdate('c'),
            'checks' => $checks,
            'failed' => array_map(fn ($c) => $c['name'], $failed),
        ];
    }

    private static function c(string $name, bool $ok, string $detail, array $extra = []): array
    {
        return ['name' => $name, 'ok' => $ok, 'detail' => $detail] + $extra;
    }

    private static function database(): array
    {
        try {
            $t = microtime(true);
            DB::selectOne('SELECT 1 AS ok');
            $ms = (int) round((microtime(true) - $t) * 1000);
            return self::c('database', $ms < 1500, "javob {$ms} ms", ['latency_ms' => $ms]);
        } catch (\Throwable $e) {
            return self::c('database', false, 'ulanib bo‘lmadi');
        }
    }

    /** Standby database: alive, not running instead of the main one, copied within the last 15 minutes. Null when disabled. */
    private static function replica(): ?array
    {
        if (!DbReplica::enabled()) return null;
        $s = DbReplica::status();
        if ($s['code'] !== 0) return self::c('replica', false, $s['message']);
        $last = DbReplica::lastSync();
        $age = $last ? time() - (int) $last['at'] : null;
        if ($age === null || $age > 900) return self::c('replica', false, $age === null ? 'zaxiraga hali nusxa olinmagan' : "oxirgi nusxa {$age} soniya oldin");
        return $last['errors'] ? self::c('replica', false, 'nusxalashda xatolar: ' . implode(', ', array_keys($last['errors']))) : self::c('replica', true, "oxirgi nusxa {$age} soniya oldin");
    }

    /** The scheduler stamps a heartbeat every minute; a stale stamp means cron is not running. */
    private static function scheduler(): array
    {
        $last = (int) Cache::get(self::HEARTBEAT_KEY, 0);
        if ($last === 0) return self::c('scheduler', false, 'heartbeat hali yozilmagan (cron: schedule:run ishlayaptimi?)');
        $age = time() - $last;
        return self::c('scheduler', $age <= 300, "oxirgi heartbeat {$age} soniya oldin", ['age_seconds' => $age]);
    }

    private static function queue(): array
    {
        try {
            $pending = Schema::hasTable('jobs') ? (int) DB::table('jobs')->count() : 0;
            $failed = Schema::hasTable('failed_jobs') ? (int) DB::table('failed_jobs')->count() : 0;
            $oldest = Schema::hasTable('jobs') ? DB::table('jobs')->min('available_at') : null;
            $lag = $oldest ? max(0, time() - (int) $oldest) : 0;
            $ok = $failed < 20 && $lag < 900;
            return self::c('queue', $ok, "kutayotgan {$pending}, muvaffaqiyatsiz {$failed}, kechikish {$lag}s", ['pending' => $pending, 'failed' => $failed, 'lag_seconds' => $lag]);
        } catch (\Throwable) {
            return self::c('queue', false, 'navbat jadvallarini o‘qib bo‘lmadi');
        }
    }

    private static function webhooks(): array
    {
        try {
            if (!Schema::hasTable('webhook_deliveries')) return self::c('webhooks', true, 'jadval yo‘q');
            $dead = (int) DB::table('webhook_deliveries')->where('status', 'dead')->where('created_at', '>=', gmdate('Y-m-d H:i:s', time() - 86400))->count();
            return self::c('webhooks', $dead < 25, "24 soatda dead: {$dead}", ['dead_24h' => $dead]);
        } catch (\Throwable) {
            return self::c('webhooks', false, 'webhook_deliveries o‘qilmadi');
        }
    }

    private static function disk(): array
    {
        $path = storage_path();
        $free = @disk_free_space($path);
        $total = @disk_total_space($path);
        if (!$free || !$total) return self::c('disk', true, 'o‘lchab bo‘lmadi');
        $pct = round($free / $total * 100, 1);
        return self::c('disk', $pct >= 10 && is_writable($path), "bo‘sh {$pct}%", ['free_percent' => $pct]);
    }

    /** A backup older than 36h (daily schedule + slack) is a failed backup. */
    private static function backup(): array
    {
        $meta = BackupService::lastBackup();
        if (!$meta) return self::c('backup', false, 'hali backup olinmagan');
        $age = time() - (int) $meta['time'];
        return self::c('backup', $age <= 36 * 3600, 'oxirgi backup ' . round($age / 3600, 1) . ' soat oldin (' . $meta['file'] . ')', ['age_hours' => round($age / 3600, 1)]);
    }
}
