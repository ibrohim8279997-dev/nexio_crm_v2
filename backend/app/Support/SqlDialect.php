<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/** Small set of date expressions shared by SQLite, PostgreSQL and MySQL. */
final class SqlDialect
{
    private static function driver(): string
    {
        return (string) DB::connection()->getDriverName();
    }

    public static function now(): string
    {
        return self::driver() === 'sqlite' ? "datetime('now')" : 'CURRENT_TIMESTAMP';
    }

    public static function ago(int $amount, string $unit = 'day'): string
    {
        $amount = max(1, $amount);
        return match (self::driver()) {
            'pgsql' => "CURRENT_TIMESTAMP - INTERVAL '{$amount} {$unit}'",
            'mysql', 'mariadb' => "DATE_SUB(CURRENT_TIMESTAMP, INTERVAL {$amount} {$unit})",
            default => "datetime('now','-{$amount} {$unit}s')",
        };
    }

    public static function month(string $column): string
    {
        return match (self::driver()) {
            'pgsql' => "TO_CHAR({$column}, 'YYYY-MM')",
            'mysql', 'mariadb' => "DATE_FORMAT({$column}, '%Y-%m')",
            default => "strftime('%Y-%m', {$column})",
        };
    }

    public static function hour(string $column): string
    {
        return match (self::driver()) {
            'pgsql' => "EXTRACT(HOUR FROM {$column})",
            'mysql', 'mariadb' => "HOUR({$column})",
            default => "CAST(strftime('%H', {$column}) AS INTEGER)",
        };
    }
}
