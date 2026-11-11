<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

final class BotsTables
{
    public static function excluded(string $table): bool
    {
        $table = strtolower($table);
        return in_array($table, array_map('strtolower', (array) config('bots.exclude')), true) || str_starts_with($table, 'sqlite_');
    }

    /** @return string[] */
    public static function all(): array
    {
        $names = array_map(fn ($t) => (string) $t['name'], Schema::getTables());
        return array_values(array_filter($names, fn ($n) => !self::excluded($n)));
    }
}
