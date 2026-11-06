<?php
namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request as RequestFacade;

/** Shared helpers, ported from api/core.ts (validation, crypto, pagination, audit). */
class H
{
    public const SESSION_TTL = 60 * 60 * 24 * 14;
    public const SECURITY_HEADERS = [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'DENY',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
    ];

    public static function json(mixed $data, int $status = 200, array $headers = []): JsonResponse
    {
        return response()->json($data, $status, array_merge([
            'Cache-Control' => 'no-store',
            ...self::SECURITY_HEADERS,
        ], $headers), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    // ── time & ids ──────────────────────────────────────────────────────────
    public static function now(): string
    {
        return gmdate('Y-m-d H:i:s');
    }

    public static function newId(string $prefix): string
    {
        return $prefix . '_' . bin2hex(random_bytes(8));
    }

    public static function newToken(): string
    {
        return substr(bin2hex(random_bytes(32)), 0, 48);
    }

    public static function randomColor(string $seed): string
    {
        $palette = ['#2563EB', '#8B5CF6', '#0EA5E9', '#10B981', '#F59E0B', '#EC4899', '#14B8A6', '#F97316'];
        $h = 0;
        for ($i = 0, $n = strlen($seed); $i < $n; $i++) {
            $h = (($h * 31) + ord($seed[$i])) & 0xFFFFFFFF;
        }
        return $palette[$h % count($palette)];
    }

    // ── passwords (PBKDF2-SHA256, 120k — identical to the Worker) ───────────
    public static function hashPassword(string $password): string
    {
        $salt = random_bytes(16);
        $bits = hash_pbkdf2('sha256', $password, $salt, 120000, 32, true);
        return 'pbkdf2$120000$' . bin2hex($salt) . '$' . base64_encode($bits);
    }

    public static function verifyPassword(string $password, string $stored): bool
    {
        // Users created through Laravel's User model, Tinker or an external admin flow may use
        // bcrypt/Argon hashes. Keep accepting them while new Nexio accounts continue using the
        // Worker-compatible PBKDF2 format below.
        if (str_starts_with($stored, '$2y$') || str_starts_with($stored, '$2b$') || str_starts_with($stored, '$argon2')) {
            return password_verify($password, $stored);
        }
        $parts = explode('$', $stored);
        if (count($parts) !== 4 || $parts[0] !== 'pbkdf2') return false;
        $iterations = (int) $parts[1];
        if ($iterations < 10000 || strlen($parts[2]) !== 32) return false;
        $salt = hex2bin($parts[2]);
        if ($salt === false) return false;
        $bits = hash_pbkdf2('sha256', $password, $salt, $iterations, 32, true);
        $expected = base64_decode($parts[3], true);
        if ($expected === false) return false;
        return hash_equals($expected, $bits);
    }

    // ── validation ──────────────────────────────────────────────────────────
    public static function body(Ctx $ctx): array
    {
        $ct = (string) $ctx->request->header('Content-Type');
        if (!str_contains($ct, 'application/json')) return [];
        $raw = $ctx->request->getContent();
        if ($raw === '' || $raw === false) return [];
        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ApiException(400, 'Некорректный JSON в теле запроса', 'bad_request');
        }
        return is_array($decoded) ? $decoded : [];
    }

    public static function str(mixed $v, string $field, bool $required = true, int $max = 500): ?string
    {
        if ($v === null || $v === '' || (is_array($v) && $v === [])) {
            if ($required) throw new ApiException(400, "Поле «{$field}» обязательно", 'bad_request');
            return null;
        }
        $s = trim((string) $v);
        if (mb_strlen($s) > $max) {
            throw new ApiException(400, "Поле «{$field}» длиннее {$max} символов", 'bad_request');
        }
        return $s;
    }

    public static function num(mixed $v, string $field, bool $required = false, float $min = -1e15, float $max = 1e15): int|float|null
    {
        if ($v === null || $v === '') {
            if ($required) throw new ApiException(400, "Поле «{$field}» обязательно", 'bad_request');
            return null;
        }
        if (!is_numeric($v)) {
            throw new ApiException(400, "Поле «{$field}» должно быть числом", 'bad_request');
        }
        $n = (float) $v;
        return $n === floor($n) && abs($n) < PHP_INT_MAX ? (int) $n : $n;
    }

    /** Mirrors oneOf(): the 4th argument is the default; omitted → required. */
    public static function oneOf(mixed $v, string $field, array $allowed, mixed $default = null): mixed
    {
        $hasDefault = func_num_args() >= 4;
        if ($v === null || $v === '') {
            if ($hasDefault) return $default;
            throw new ApiException(400, "Поле «{$field}» обязательно", 'bad_request');
        }
        $s = is_string($v) ? $v : (string) $v;
        if (!in_array($s, $allowed, true)) {
            throw new ApiException(400, "Недопустимое значение поля «{$field}»", 'bad_request');
        }
        return $s;
    }

    // ── references from request input ───────────────────────────────────────
    /** A row of this tenant, or null when nothing was chosen. Missing and foreign ids look alike on purpose. */
    public static function tenantRef(Ctx $ctx, mixed $v, string $table, string $label): ?string
    {
        $id = self::refId($v, $label);
        if ($id !== null && !DB::table($table)->where('id', $id)->where('tenant_id', $ctx->tenantId)->exists()) {
            throw self::invalidRef($label);
        }
        return $id;
    }

    /** An active member of this tenant, or null when nobody was chosen. */
    public static function memberRef(Ctx $ctx, mixed $v, string $label): ?string
    {
        $id = self::refId($v, $label);
        if ($id !== null && !DB::table('tenant_memberships')->where('user_id', $id)->where('tenant_id', $ctx->tenantId)->where('status', 'active')->exists()) {
            throw self::invalidRef($label);
        }
        return $id;
    }

    /** A select left on "none" posts '', which is a value to the database: it fails the foreign key. */
    private static function refId(mixed $v, string $label): ?string
    {
        if ($v !== null && !is_string($v) && !is_int($v)) throw self::invalidRef($label);
        $id = trim((string) $v);
        return $id === '' ? null : $id;
    }

    private static function invalidRef(string $label): ApiException
    {
        return new ApiException(422, "Tanlangan {$label} ushbu CRMga tegishli emas. Ro‘yxatdan qayta tanlang.", 'invalid_reference');
    }

    /** @return array{0:int,1:int,2:int} [limit, page, offset] */
    public static function paginate(Ctx $ctx, int $defaultLimit = 25): array
    {
        $limit = (int) $ctx->query('limit', 0) ?: $defaultLimit;
        $limit = max(1, min(200, $limit));
        $page = max(1, (int) $ctx->query('page', 0) ?: 1);
        return [$limit, $page, ($page - 1) * $limit];
    }

    public static function like(?string $raw): string
    {
        return '%' . str_replace(['%', '_'], '', (string) $raw) . '%';
    }

    public static function parseJson(mixed $s, mixed $fallback = []): mixed
    {
        if (!is_string($s) || $s === '') return $fallback;
        $d = json_decode($s, true);
        return json_last_error() === JSON_ERROR_NONE ? $d : $fallback;
    }

    // ── permissions ─────────────────────────────────────────────────────────
    public static function requirePermission(Ctx $ctx, string $permission): void
    {
        if (!empty($ctx->user['is_platform_admin'])) return;
        if (!Rbac::can($ctx->role, $permission)) {
            throw new ApiException(403, "Требуется разрешение «{$permission}»", 'forbidden');
        }
    }

    /** Non-throwing permission probe, used to shape API responses (never to enforce access). */
    public static function can(Ctx $ctx, string $permission): bool
    {
        if (!$ctx->user) {
            return false;
        }
        if (!empty($ctx->user['is_platform_admin'])) {
            return true;
        }
        return Rbac::can($ctx->role, $permission);
    }

    /** Platform operators pass permission checks without a tenant, but tenant records cannot exist without one. */
    public static function requireTenant(Ctx $ctx): void
    {
        if (!$ctx->tenantId) throw new ApiException(422, 'CRM tenant tanlanmagan', 'tenant_required');
    }

    public static function requirePlatform(Ctx $ctx): void
    {
        $ctx->auth();
        if (empty($ctx->user['is_platform_admin'])) {
            throw new ApiException(403, 'Доступ только для операторов платформы Nexio Lab', 'forbidden');
        }
    }

    // ── audit ───────────────────────────────────────────────────────────────
    public static function audit(Ctx $ctx, string $action, string $target, ?string $targetId = null, mixed $meta = null): void
    {
        DB::table('audit_logs')->insert([
            'id' => self::newId('aud'),
            'tenant_id' => $ctx->tenantId,
            'actor_id' => $ctx->user['id'] ?? null,
            'actor_name' => $ctx->user['name'] ?? 'system',
            'action' => $action,
            'target' => $target,
            'target_id' => $targetId,
            'meta' => $meta === null ? null : json_encode($meta, JSON_UNESCAPED_UNICODE),
            'ip' => $ctx->request->ip(),
            'created_at' => self::now(),
        ]);
        (new \App\Services\NotificationDispatcher())->fromAudit($ctx->tenantId, $ctx->user['id'] ?? null, $action, $meta);
    }

    /** Resolves the current request context (bound as a singleton by the middleware). */
    public static function ctx(): Ctx
    {
        return app(Ctx::class);
    }
}
