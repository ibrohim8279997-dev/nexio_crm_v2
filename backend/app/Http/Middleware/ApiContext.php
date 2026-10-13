<?php
namespace App\Http\Middleware;

use App\Support\ApiException;
use App\Support\Ctx;
use App\Support\H;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the caller into a tenant-scoped Ctx, enforces same-origin on mutations,
 * rate-limits credential endpoints and confines portal sessions to the portal surface.
 * Ported from api/worker.ts + loadSession() in api/core.ts.
 */
class ApiContext
{
    public const COOKIE = 'nexio_session';

    public function handle(Request $request, Closure $next): Response
    {
        $method = strtoupper($request->method());
        $isMutation = !in_array($method, ['GET', 'HEAD', 'OPTIONS'], true);

        // CSRF: browsers send Origin on cross-site mutations. Reject mismatched origins.
        if ($isMutation) {
            $origin = $request->header('Origin');
            if ($origin && rtrim($origin, '/') !== rtrim($request->getSchemeAndHttpHost(), '/')) {
                return H::json(['error' => ['code' => 'csrf', 'message' => 'Запрос с другого источника отклонён']], 403);
            }
        }

        if (in_array($request->path(), ['api/v1/auth/login', 'api/v1/auth/register'], true) || str_contains($request->path(), 'forgot-password')) {
            $key = 'rl:auth:' . $request->ip();
            $count = (int) Cache::get($key, 0);
            if ($count >= 20) {
                throw new ApiException(429, 'Слишком много попыток входа. Подождите минуту.', 'rate_limited');
            }
            Cache::put($key, $count + 1, 60);
        }

        $ctx = new Ctx($request);
        app()->instance(Ctx::class, $ctx);
        $this->loadSession($ctx, $request);

        // A client-portal session is confined to the portal surface. Enforced before routing
        // so no internal endpoint can leak supplier data to a customer account.
        if ($ctx->role === 'client_portal_user') {
            $path = $request->path();
            if (!str_starts_with($path, 'api/v1/portal') && !str_starts_with($path, 'api/v1/auth')) {
                throw new ApiException(403, 'Пользователю портала доступны только данные его организации в разделе портала', 'forbidden');
            }
        }

        return $next($request);
    }

    private function loadSession(Ctx $ctx, Request $request): void
    {
        $sid = $request->cookie(self::COOKIE);
        if (!$sid) return;

        $row = DB::selectOne(
            'SELECT s.id AS sid, s.tenant_id AS session_tenant, s.expires_at,
                    u.id, u.email, u.name, u.avatar_color, u.is_platform_admin, u.platform_role
               FROM sessions s JOIN users u ON u.id = s.user_id
              WHERE s.id = ?',
            [$sid]
        );
        if (!$row) return;
        if (strtotime($row->expires_at . ' UTC') < time()) return;

        $ctx->sessionId = $row->sid;
        $ctx->user = [
            'id' => $row->id,
            'email' => $row->email,
            'name' => $row->name,
            'avatar_color' => $row->avatar_color,
            'is_platform_admin' => (int) $row->is_platform_admin,
            'platform_role' => $row->platform_role,
        ];

        // Resolve tenant context: the session tenant must still be an active membership.
        if ($row->session_tenant) {
            $m = DB::selectOne(
                "SELECT role FROM tenant_memberships WHERE tenant_id = ? AND user_id = ? AND status = 'active'",
                [$row->session_tenant, $row->id]
            );
            if ($m) {
                $ctx->tenantId = $row->session_tenant;
                $ctx->role = $m->role;
            } else {
                $first = DB::selectOne(
                    "SELECT tenant_id, role FROM tenant_memberships WHERE user_id = ? AND status = 'active' ORDER BY created_at LIMIT 1",
                    [$row->id]
                );
                if ($first) {
                    $ctx->tenantId = $first->tenant_id;
                    $ctx->role = $first->role;
                }
            }
        }
    }
}
