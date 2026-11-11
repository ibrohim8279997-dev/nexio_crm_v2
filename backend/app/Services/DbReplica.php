<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Standby database. The main database is copied table by table into a second database ("replica" connection)
 * on a schedule. If the main one dies or is damaged, `nexio:db:failover` switches the app to the standby
 * (a marker file, not the database, records the switch) and `nexio:db:restore` copies it back.
 */
final class DbReplica
{
    public const NAME = 'replica';

    public static function enabled(): bool
    {
        return (bool) config('replica.enabled');
    }

    public static function primary(): string
    {
        return (string) config('replica.primary');
    }

    public static function markerPath(): string
    {
        return storage_path('app/db-failover.json');
    }

    public static function statePath(): string
    {
        return storage_path('app/replica-state.json');
    }

    public static function failedOver(): bool
    {
        return self::enabled() && is_file(self::markerPath());
    }

    /** Registers the standby connection; while failed over the app runs on it. Called before any query. */
    public static function configure(): void
    {
        if (!self::enabled()) return;
        $base = (array) config('database.connections.' . config('replica.driver'));
        $own = array_filter((array) config('replica.db'), fn ($v) => $v !== null && $v !== '');
        config(['database.connections.' . self::NAME => array_merge($base, ['url' => null], $own)]);
        if (self::failedOver()) config(['database.default' => self::NAME]);
    }

    /** Copying a database into itself would wipe it: refuse when both point at the same place. */
    public static function assertDistinct(): void
    {
        $key = fn (string $c) => implode('|', [
            config("database.connections.{$c}.driver"), config("database.connections.{$c}.host"), config("database.connections.{$c}.port"),
            realpath((string) config("database.connections.{$c}.database")) ?: config("database.connections.{$c}.database"),
        ]);
        if ($key(self::primary()) === $key(self::NAME)) {
            throw new \RuntimeException('Zaxira baza asosiy bazaning o‘zi: REPLICA_DB_DATABASE (yoki _HOST) ni boshqa bazaga o‘rnating.');
        }
    }

    public static function ping(string $connection): bool
    {
        // A cached connection may have gone away: on failure reconnect once before declaring the database dead.
        // (Never purge a working connection first: for in-memory SQLite that would erase the database.)
        foreach ([false, true] as $reconnect) {
            try {
                if ($reconnect) DB::purge($connection);
                DB::connection($connection)->select('SELECT 1');
                return true;
            } catch (\Throwable) {
            }
        }
        return false;
    }

    /** @return string[] */
    public static function tables(string $connection): array
    {
        $names = array_map(fn ($t) => (string) $t['name'], Schema::connection($connection)->getTables());
        $skip = array_map('strtolower', (array) config('replica.exclude'));
        return array_values(array_filter($names, fn ($n) => !in_array(strtolower($n), $skip, true) && !str_starts_with(strtolower($n), 'sqlite_')));
    }

    /** Cheap change marker: row count + newest updated_at/created_at. */
    public static function fingerprint(string $connection, string $table): string
    {
        $schema = Schema::connection($connection);
        $stamp = collect(['updated_at', 'created_at'])->first(fn ($c) => $schema->hasColumn($table, $c));
        $row = DB::connection($connection)->table($table)->selectRaw('COUNT(*) AS c' . ($stamp ? ", MAX({$stamp}) AS m" : ''))->first();
        return $row->c . '|' . ($row->m ?? '');
    }

    /** @return string[] tables whose fingerprint differs (or that the target lacks) */
    public static function differing(string $from, string $to): array
    {
        $have = array_flip(self::tables($to));
        return array_values(array_filter(self::tables($from), fn ($t) => !isset($have[$t]) || self::fingerprint($from, $t) !== self::fingerprint($to, $t)));
    }

    /**
     * Makes $table on $to identical to $from. Returns the number of rows written.
     * Order matters: rows missing at the source are removed first, so a row that was deleted and re-created
     * under a new id cannot collide with a unique index while the new one is written.
     */
    public static function copyTable(string $from, string $to, string $table): int
    {
        $cols = array_values(array_intersect(Schema::connection($from)->getColumnListing($table), Schema::connection($to)->getColumnListing($table)));
        if (!$cols) return 0;
        $src = DB::connection($from);
        $dst = DB::connection($to);
        $page = max(50, (int) config('replica.page', 500));
        $chunk = fn (array $rows) => array_chunk($rows, max(1, intdiv(900, count($cols))));
        $written = 0;

        Schema::connection($to)->disableForeignKeyConstraints();
        try {
            if (!in_array('id', $cols, true)) {   // small link tables without a key: replace as a whole
                $rows = $src->table($table)->get($cols)->map(fn ($r) => (array) $r)->all();
                $dst->transaction(function () use ($dst, $table, $rows, $chunk) {
                    $dst->table($table)->delete();
                    foreach ($chunk($rows) as $part) $dst->table($table)->insert($part);
                });
                return count($rows);
            }

            $keep = [];
            $src->table($table)->select('id')->chunkById(2000, function ($rows) use (&$keep) {
                foreach ($rows as $r) $keep[(string) $r->id] = true;
            }, 'id');
            if ($dst->table($table)->count() > 0) {
                $dst->table($table)->select('id')->chunkById(1000, function ($rows) use ($dst, $table, $keep) {
                    $gone = $rows->pluck('id')->filter(fn ($id) => !isset($keep[(string) $id]))->all();
                    if ($gone) $dst->table($table)->whereIn('id', $gone)->delete();
                }, 'id');
            }

            $update = array_values(array_diff($cols, ['id']));
            $after = null;
            do {
                $q = $src->table($table)->orderBy('id');
                if ($after !== null) $q->where('id', '>', $after);
                $rows = $q->limit($page + 1)->get($cols)->map(fn ($r) => (array) $r)->all();
                $more = count($rows) > $page;
                $rows = array_slice($rows, 0, $page);
                if (!$rows) break;
                $ids = array_column($rows, 'id');
                $existing = $dst->table($table)->whereIn('id', $ids)->get($cols)->mapWithKeys(fn ($r) => [(string) $r->id => self::norm((array) $r)])->all();
                $changed = array_values(array_filter($rows, fn ($r) => ($existing[(string) $r['id']] ?? null) !== self::norm($r)));
                foreach ($chunk($changed) as $part) $update ? $dst->table($table)->upsert($part, ['id'], $update) : $dst->table($table)->insertOrIgnore($part);
                $written += count($changed);
                $after = (string) end($ids);
            } while ($more);
        } finally {
            Schema::connection($to)->enableForeignKeyConstraints();
        }
        return $written;
    }

    private static function norm(array $row): array
    {
        ksort($row);
        return array_map(fn ($v) => $v === null ? null : (is_bool($v) ? (string) (int) $v : (string) $v), $row);
    }

    /**
     * Copies the tables that differ (or all of them with $all). Errors in one table do not stop the others.
     * @return array{tables: array<string,int>, errors: array<string,string>}
     */
    public static function sync(string $from, string $to, bool $all = false, ?array $only = null): array
    {
        self::assertDistinct();
        $tables = $only ?: ($all ? self::tables($from) : self::differing($from, $to));
        $have = array_flip(self::tables($to));
        $done = [];
        $errors = [];
        foreach ($tables as $t) {
            if (!isset($have[$t])) { $errors[$t] = 'jadval nishon bazada yo‘q (migrate --database=' . $to . ')'; continue; }
            try {
                $done[$t] = self::copyTable($from, $to, $t);
            } catch (\Throwable $e) {
                $errors[$t] = $e::class . ': ' . mb_substr($e->getMessage(), 0, 200);
            }
        }
        return ['tables' => $done, 'errors' => $errors];
    }

    public static function recordSync(array $result): void
    {
        @file_put_contents(self::statePath(), json_encode(['at' => time(), 'tables' => count($result['tables']), 'rows' => array_sum($result['tables']), 'errors' => $result['errors']]));
    }

    public static function lastSync(): ?array
    {
        $d = is_file(self::statePath()) ? json_decode((string) file_get_contents(self::statePath()), true) : null;
        return is_array($d) && isset($d['at']) ? $d : null;
    }

    /** @return array{enabled: bool, mode: string, primary_ok: ?bool, replica_ok: ?bool, code: int, message: string} */
    public static function status(): array
    {
        if (!self::enabled()) return ['enabled' => false, 'mode' => 'off', 'primary_ok' => null, 'replica_ok' => null, 'code' => 0, 'message' => 'Zaxira baza o‘chiq (REPLICA_ENABLED=false).'];
        $p = self::ping(self::primary());
        $r = self::ping(self::NAME);
        if (self::failedOver()) {
            return ['enabled' => true, 'mode' => 'failover', 'primary_ok' => $p, 'replica_ok' => $r, 'code' => $r ? 5 : 4,
                'message' => $r ? 'Ilova ZAXIRA bazada ishlayapti (failover). Asosiy baza ' . ($p ? 'tirik: restore + failback qiling.' : 'hali o‘lik.') : 'Failover rejimida, lekin zaxira ham javob bermayapti!'];
        }
        return match (true) {
            $p && $r => ['enabled' => true, 'mode' => 'normal', 'primary_ok' => true, 'replica_ok' => true, 'code' => 0, 'message' => 'Asosiy va zaxira baza tirik.'],
            $p && !$r => ['enabled' => true, 'mode' => 'normal', 'primary_ok' => true, 'replica_ok' => false, 'code' => 2, 'message' => 'Asosiy baza tirik, ZAXIRA javob bermayapti.'],
            !$p && $r => ['enabled' => true, 'mode' => 'normal', 'primary_ok' => false, 'replica_ok' => true, 'code' => 1, 'message' => 'ASOSIY BAZA ISHLAMAYAPTI, zaxira tirik: php artisan nexio:db:failover'],
            default => ['enabled' => true, 'mode' => 'normal', 'primary_ok' => false, 'replica_ok' => false, 'code' => 4, 'message' => 'Ikkala baza ham javob bermayapti!'],
        };
    }
}
