<?php
namespace App\Plugins;

/**
 * Minimal, dependency-free semantic version handling.
 *
 * Deliberately not a general-purpose semver library: it implements exactly the operations the
 * plugin system needs (compare, satisfies) and always normalises to three numeric parts, so a
 * malformed version fails validation instead of producing surprising comparisons.
 */
class Semver
{
    /** @return array{0:int,1:int,2:int}|null */
    public static function parse(string $version): ?array
    {
        $v = trim($version);
        if (!preg_match('/^v?(\d+)(?:\.(\d+))?(?:\.(\d+))?(?:[-+][0-9A-Za-z.\-]+)?$/', $v, $m)) {
            return null;
        }
        return [(int) $m[1], (int) ($m[2] ?? 0), (int) ($m[3] ?? 0)];
    }

    public static function isValid(string $version): bool
    {
        return self::parse($version) !== null;
    }

    public static function normalise(string $version): string
    {
        $p = self::parse($version);
        return $p === null ? $version : $p[0] . '.' . $p[1] . '.' . $p[2];
    }

    /** -1 | 0 | 1 */
    public static function compare(string $a, string $b): int
    {
        $pa = self::parse($a) ?? [0, 0, 0];
        $pb = self::parse($b) ?? [0, 0, 0];
        for ($i = 0; $i < 3; $i++) {
            if ($pa[$i] !== $pb[$i]) {
                return $pa[$i] < $pb[$i] ? -1 : 1;
            }
        }
        return 0;
    }

    /**
     * Satisfies a comma/space separated constraint list, e.g. ">=1.0.0 <2.0.0", "^1.2.0", "~1.2.0", "*".
     * Multiple constraints are ANDed. An unparseable constraint never matches (fail closed).
     */
    public static function satisfies(string $version, string $constraint): bool
    {
        $constraint = trim($constraint);
        if ($constraint === '' || $constraint === '*') {
            return true;
        }
        $parts = preg_split('/[\s,]+/', $constraint, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach ($parts as $part) {
            if (!self::satisfiesOne($version, $part)) {
                return false;
            }
        }
        return true;
    }

    private static function satisfiesOne(string $version, string $c): bool
    {
        if (str_starts_with($c, '^')) {
            $base = self::parse(substr($c, 1));
            if ($base === null) return false;
            // Caret: >= base and < next major (0.x behaves as < next minor).
            $upper = $base[0] > 0
                ? ($base[0] + 1) . '.0.0'
                : '0.' . ($base[1] + 1) . '.0';
            return self::compare($version, implode('.', $base)) >= 0 && self::compare($version, $upper) < 0;
        }
        if (str_starts_with($c, '~')) {
            $base = self::parse(substr($c, 1));
            if ($base === null) return false;
            $upper = $base[0] . '.' . ($base[1] + 1) . '.0';
            return self::compare($version, implode('.', $base)) >= 0 && self::compare($version, $upper) < 0;
        }
        if (preg_match('/^(>=|<=|>|<|=|==|!=)\s*(.+)$/', $c, $m)) {
            $rhs = self::parse(trim($m[2]));
            if ($rhs === null) return false;
            $cmp = self::compare($version, implode('.', $rhs));
            return match ($m[1]) {
                '>=', '=>' => $cmp >= 0,
                '<=' => $cmp <= 0,
                '>' => $cmp > 0,
                '<' => $cmp < 0,
                '=', '==' => $cmp === 0,
                '!=' => $cmp !== 0,
                default => false,
            };
        }
        // Bare version means exact match.
        $exact = self::parse($c);
        return $exact !== null && self::compare($version, implode('.', $exact)) === 0;
    }

    /** Highest of a list of versions, or null when empty/invalid. */
    public static function highest(array $versions): ?string
    {
        $best = null;
        foreach ($versions as $v) {
            if (!self::isValid((string) $v)) continue;
            if ($best === null || self::compare((string) $v, $best) > 0) {
                $best = (string) $v;
            }
        }
        return $best;
    }
}
