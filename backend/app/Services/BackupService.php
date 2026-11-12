<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

/**
 * Database backup. pgsql -> pg_dump, mysql -> mysqldump, sqlite -> file copy. Files are gzipped,
 * pruned to the newest N, and the last success is recorded in last.json for the health check.
 * Credentials are passed via the process environment, never on the command line.
 */
class BackupService
{
    public static function dir(): string
    {
        $dir = storage_path('app/backups');
        if (!is_dir($dir)) @mkdir($dir, 0750, true);
        return $dir;
    }

    public static function lastBackup(): ?array
    {
        $f = self::dir() . '/last.json';
        $d = is_file($f) ? json_decode((string) file_get_contents($f), true) : null;
        return is_array($d) && isset($d['time'], $d['file']) ? $d : null;
    }

    /** @return array{file:string,bytes:int,driver:string} */
    public static function run(int $keep = 90): array
    {
        $conn = (string) config('database.default');
        $cfg = (array) config("database.connections.{$conn}");
        $driver = (string) ($cfg['driver'] ?? $conn);
        $stamp = gmdate('Ymd-His');
        $base = self::dir() . "/nexio-{$stamp}";

        if ($driver === 'sqlite') {
            $src = (string) ($cfg['database'] ?? '');
            if ($src === '' || $src === ':memory:' || !is_file($src)) throw new \RuntimeException('SQLite fayli topilmadi');
            $out = $base . '.sqlite.gz';
            $gz = gzopen($out, 'wb9');
            gzwrite($gz, (string) file_get_contents($src));
            gzclose($gz);
        } elseif ($driver === 'pgsql' || $driver === 'mysql' || $driver === 'mariadb') {
            $out = $base . '.sql.gz';
            if ($driver === 'pgsql') {
                $cmd = ['pg_dump', '--no-owner', '--no-privileges', '-h', (string) $cfg['host'], '-p', (string) ($cfg['port'] ?? 5432), '-U', (string) $cfg['username'], (string) $cfg['database']];
                $env = ['PGPASSWORD' => (string) ($cfg['password'] ?? '')];
            } else {
                $cmd = ['mysqldump', '--single-transaction', '-h', (string) $cfg['host'], '-P', (string) ($cfg['port'] ?? 3306), '-u', (string) $cfg['username'], (string) $cfg['database']];
                $env = ['MYSQL_PWD' => (string) ($cfg['password'] ?? '')];
            }
            $gz = gzopen($out, 'wb9');
            $p = new Process($cmd, null, $env, null, 1800);
            $p->run(function ($type, $buffer) use ($gz) { if ($type === Process::OUT) gzwrite($gz, $buffer); });
            gzclose($gz);
            if (!$p->isSuccessful()) {
                @unlink($out);
                throw new \RuntimeException('Dump muvaffaqiyatsiz: ' . substr(trim($p->getErrorOutput()), 0, 300));
            }
        } else {
            throw new \RuntimeException("Qo‘llab-quvvatlanmaydigan DB drayveri: {$driver}");
        }
        @chmod($out, 0640);

        $files = glob(self::dir() . '/nexio-*.gz') ?: [];
        rsort($files);
        foreach (array_slice($files, max(1, $keep)) as $old) @unlink($old);

        $meta = ['time' => time(), 'file' => basename($out), 'bytes' => (int) filesize($out), 'driver' => $driver];
        file_put_contents(self::dir() . '/last.json', json_encode($meta));
        return $meta;
    }
}
