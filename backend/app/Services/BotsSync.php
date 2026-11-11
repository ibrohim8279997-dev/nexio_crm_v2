<?php

namespace App\Services;

use App\Support\BotsSignature;
use App\Support\BotsTables;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/** Bot-server side: keeps this server's database a mirror of the site's, table by table. */
final class BotsSync
{
    private const QUEUED = 'bots.sync.queued';

    private function request(string $method, string $path, array $query = [], ?array $json = null): PendingRequest
    {
        $body = $json === null ? '' : (string) json_encode($json);
        $req = \Illuminate\Support\Facades\Http::connectTimeout(5)->timeout(60)->acceptJson()
            ->withHeaders(BotsSignature::headers($method, $path, $query, $body));
        return $json === null ? $req : $req->withBody($body, 'application/json');
    }

    private function url(string $path): string
    {
        return rtrim((string) config('bots.site_url'), '/') . $path;
    }

    private function get(string $path, array $query = []): array
    {
        ksort($query);
        $res = $this->request('GET', $path, $query)->get($this->url($path), $query);
        if (!$res->successful()) throw new \RuntimeException("Site answered HTTP {$res->status()} for {$path}");
        return (array) $res->json();
    }

    /** @return array<string, array{count: int, fp: string}> */
    public function remoteTables(): array
    {
        $out = [];
        foreach ($this->get('/api/bots/tables')['tables'] ?? [] as $t) $out[(string) $t['name']] = ['count' => (int) $t['count'], 'fp' => (string) $t['fp']];
        return $out;
    }

    /** Link changes are written on the site (the owner of the data); returns false for an unknown/expired token. */
    public function remoteBind(string $token, string $chatId, ?string $username): bool
    {
        $path = '/api/bots/telegram/link';
        $res = $this->request('POST', $path, [], ['token' => $token, 'chat_id' => $chatId, 'username' => $username])->post($this->url($path));
        if ($res->status() === 404) return false;
        if (!$res->successful()) throw new \RuntimeException("Site answered HTTP {$res->status()}");
        $this->pull(['user_telegram_links']);   // see the new link at once instead of waiting for the push
        return true;
    }

    public function remoteUnbind(string $chatId): void
    {
        $path = '/api/bots/telegram/unlink';
        $res = $this->request('POST', $path, [], ['chat_id' => $chatId])->post($this->url($path));
        if (!$res->successful()) throw new \RuntimeException("Site answered HTTP {$res->status()}");
        $this->pull(['user_telegram_links']);
    }

    /** Pulls the given tables (or all, when empty). Concurrent calls queue their tables for the running one. */
    public function pull(array $tables = []): void
    {
        $queue = Cache::get(self::QUEUED, []);
        Cache::put(self::QUEUED, array_values(array_unique([...$queue, ...($tables ?: ['*'])])), 3600);
        $lock = Cache::lock('bots.sync', 1800);
        if (!$lock->get()) return;   // the running worker will pick the queued tables up
        try {
            while ($batch = Cache::pull(self::QUEUED, [])) {
                try {
                    $remote = $this->remoteTables();
                } catch (\Throwable $e) {
                    Log::warning('Bots sync: site unreachable, the 5-minute reconcile will retry', ['error' => $e->getMessage()]);
                    return;
                }
                $names = in_array('*', $batch, true) ? array_keys($remote) : array_values(array_intersect($batch, array_keys($remote)));
                foreach ($names as $name) {
                    try {
                        $this->pullTable($name, $remote[$name]['fp']);
                    } catch (\Throwable $e) {
                        Log::error('Bots table sync failed', ['table' => $name, 'error' => $e->getMessage()]);
                        Cache::forget("bots.fp.{$name}");   // the reconcile retries it
                    }
                }
            }
        } finally {
            $lock->release();
        }
    }

    /** Pulls only tables whose count/last-change differs from what was mirrored last time. */
    public function reconcile(): int
    {
        $stale = [];
        foreach ($this->remoteTables() as $name => $info) {
            if (Cache::get("bots.fp.{$name}") !== $info['fp']) $stale[] = $name;
        }
        if ($stale) $this->pull($stale);
        return count($stale);
    }

    private function pullTable(string $table, string $fingerprint): void
    {
        if (BotsTables::excluded($table) || !Schema::hasTable($table)) {
            Log::warning('Bots sync: table missing locally, run migrations on the bot server', ['table' => $table]);
            return;
        }
        $cols = Schema::getColumnListing($table);   // tolerate schema drift: only mirror columns both sides have
        $hasId = in_array('id', $cols, true);
        $limit = (int) config('bots.page', 1000);
        $seen = [];
        $all = [];
        $cursor = null;
        Schema::disableForeignKeyConstraints();
        try {
            do {
                $page = $this->get("/api/bots/tables/{$table}/rows", array_filter(['after' => $hasId ? $cursor : null, 'offset' => $hasId ? null : $cursor, 'limit' => $limit], fn ($v) => $v !== null));
                $rows = array_map(fn ($r) => array_intersect_key((array) $r, array_flip($cols)), (array) ($page['rows'] ?? []));
                if ($hasId) {
                    $this->upsert($table, $cols, $rows);
                    foreach ($rows as $r) $seen[(string) $r['id']] = true;
                } else {
                    array_push($all, ...$rows);
                }
                $cursor = $page['next'] ?? null;
            } while ($cursor !== null);

            if ($hasId) {
                $this->prune($table, $seen);
            } else {
                DB::transaction(function () use ($table, $cols, $all) {
                    DB::table($table)->delete();
                    $this->insertChunks($table, $cols, $all);
                });
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
        Cache::forever("bots.fp.{$table}", $fingerprint);   // fingerprint taken before the pull: a change during it is re-pulled next time
    }

    private function upsert(string $table, array $cols, array $rows): void
    {
        $update = array_values(array_diff($cols, ['id']));
        foreach (array_chunk($rows, max(1, intdiv(900, max(1, count($cols))))) as $chunk) {
            $update ? DB::table($table)->upsert($chunk, ['id'], $update) : DB::table($table)->insertOrIgnore($chunk);
        }
    }

    private function insertChunks(string $table, array $cols, array $rows): void
    {
        foreach (array_chunk($rows, max(1, intdiv(900, max(1, count($cols))))) as $chunk) DB::table($table)->insert($chunk);
    }

    /** Removes local rows the site no longer has. */
    private function prune(string $table, array $seen): void
    {
        DB::table($table)->chunkById(1000, function ($rows) use ($table, $seen) {
            $gone = $rows->pluck('id')->filter(fn ($id) => !isset($seen[(string) $id]))->all();
            if ($gone) DB::table($table)->whereIn('id', $gone)->delete();
        }, 'id');
    }
}
