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
    public const SESSION_HEADER = 'X-Nexio-Session';

    public function handle(Request $request, Closure $next): Response
    {
        $method = strtoupper($request->method());
        $isMutation = !in_array($method, ['GET', 'HEAD', 'OPTIONS'], true);

        // CSRF: browsers send Origin on cross-site mutations. Reject origins other than the API
        // itself and the frontend origins allowed in config/cors.php.
        // api/public/* is the embeddable lead form: it is cross-origin by design and does its own per-form origin allowlist.
        if ($isMutation && !str_starts_with($request->path(), 'api/public/')) {
            $origin = $request->header('Origin');
            $allowed = array_merge([rtrim($request->getSchemeAndHttpHost(), '/')], (array) config('cors.allowed_origins', []));
            if ($origin && !in_array(rtrim($origin, '/'), $allowed, true)) {
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
        $ctx->params = $request->route()?->parameters() ?? [];
        app()->instance(Ctx::class, $ctx);
        if (!$this->loadApiKey($ctx, $request)) $this->loadSession($ctx, $request);

        $this->enforceBillingLock($ctx, $request);

        // A client-portal session is confined to the portal surface. Enforced before routing
        // so no internal endpoint can leak supplier data to a customer account.
        if ($ctx->role === 'client_portal_user') {
            $path = $request->path();
            if (!str_starts_with($path, 'api/v1/portal') && !str_starts_with($path, 'api/v1/auth')) {
                throw new ApiException(403, 'Пользователю портала доступны только данные его организации в разделе портала', 'forbidden');
            }
        }

        $response = $next($request);
        foreach (H::SECURITY_HEADERS as $name => $value) {
            if (!$response->headers->has($name)) $response->headers->set($name, $value);
        }
        return $response;
    }

    /**
     * A company whose subscription went unpaid past the grace window is locked out (its data is kept).
     * Billing, auth and the profile stay reachable so the company can add a card and pay to get back in.
     * Platform operators are never locked.
     */
    private function enforceBillingLock(Ctx $ctx, Request $request): void
    {
        if (!$ctx->tenantId || !empty($ctx->user['is_platform_admin'])) return;
        $path = $request->path();
        if (str_starts_with($path, 'api/v1/billing') || str_starts_with($path, 'api/v1/auth')) return;
        $blocked = DB::table('subscriptions')->where('tenant_id', $ctx->tenantId)->orderByDesc('created_at')->value('blocked_at');
        if ($blocked) {
            throw new ApiException(402, 'Доступ приостановлен из-за неоплаты. Добавьте карту в разделе «Billing», данные сохранены.', 'payment_required');
        }
    }

    /**
     * Resource → [read scope, write scope]. API keys reach only these CRM/finance resources:
     * auth, team, settings, billing, plugins, platform and key management are never exposed to a key.
     */
    private const KEY_RESOURCES = [
        'leads' => ['crm.read', 'crm.write'], 'contacts' => ['crm.read', 'crm.write'], 'organizations' => ['crm.read', 'crm.write'],
        'deals' => ['crm.read', 'deals.write'], 'pipeline' => ['crm.read', 'deals.write'], 'orders' => ['crm.read', 'orders.write'],
        'projects' => ['crm.read', 'projects.write'], 'tasks' => ['crm.read', 'tasks.write'], 'products' => ['crm.read', 'crm.write'],
        'notes' => ['crm.read', 'crm.write'], 'activities' => ['crm.read', 'crm.write'],
        'invoices' => ['finance.read', 'finance.write'], 'payments' => ['finance.read', 'finance.write'], 'expenses' => ['finance.read', 'finance.write'],
        'reports' => ['finance.read', 'finance.write'],
    ];

    /** Bearer "nxk_…" authentication. Returns false when the request carries no key (cookie session is used instead). */
    private function loadApiKey(Ctx $ctx, Request $request): bool
    {
        $auth = (string) $request->header('Authorization');
        if (!str_starts_with($auth, 'Bearer nxk_')) return false;
        $key = DB::selectOne('SELECT * FROM api_keys WHERE key_hash = ?', [hash('sha256', substr($auth, 7))]);
        if (!$key || $key->revoked_at || ($key->expires_at && $key->expires_at < H::now())) {
            throw new ApiException(401, 'Недействительный или отозванный API-ключ', 'unauthorized');
        }
        $creator = DB::selectOne(
            "SELECT u.id, u.email, u.name FROM users u JOIN tenant_memberships m ON m.user_id = u.id AND m.tenant_id = ? AND m.status = 'active' WHERE u.id = ?",
            [$key->tenant_id, $key->created_by]
        );
        if (!$creator) throw new ApiException(401, 'Владелец ключа больше не состоит в компании', 'unauthorized');

        $bucket = 'rl:key:' . $key->id . ':' . gmdate('YmdHi');
        $count = (int) Cache::get($bucket, 0);
        if ($count >= 120) throw new ApiException(429, 'Превышен лимит: 120 запросов в минуту на ключ', 'rate_limited');
        Cache::put($bucket, $count + 1, 90);

        $resource = explode('/', $request->path())[2] ?? '';
        if (!isset(self::KEY_RESOURCES[$resource])) throw new ApiException(403, 'Этот раздел недоступен для API-ключей', 'forbidden');
        $scopes = H::parseJson($key->scopes, []);
        $needed = self::KEY_RESOURCES[$resource][in_array($request->method(), ['GET', 'HEAD'], true) ? 0 : 1];
        if (!in_array($needed, $scopes, true)) throw new ApiException(403, "Ключу API не выдано право «{$needed}»", 'forbidden');

        // Records created through a key are attributed to the key's creator, labelled with the key name.
        $ctx->user = ['id' => $creator->id, 'email' => $creator->email, 'name' => 'API: ' . $key->name, 'avatar_color' => '#64748B', 'is_platform_admin' => 0, 'platform_role' => null];
        $ctx->tenantId = $key->tenant_id;
        $ctx->role = 'api_key';
        $ctx->apiScopes = $scopes;
        if (!$key->last_used_at || $key->last_used_at < gmdate('Y-m-d H:i:s', time() - 60)) {
            DB::table('api_keys')->where('id', $key->id)->update(['last_used_at' => H::now()]);
        }
        return true;
    }

    private function loadSession(Ctx $ctx, Request $request): void
    {
        // Native apps (Capacitor) run on another origin where cross-site cookies are unreliable, so they present
        // the same session id in a header. It is the same secret as the cookie and is checked identically below.
        $sid = $request->cookie(self::COOKIE) ?: $request->header(self::SESSION_HEADER);
        if (!is_string($sid) || $sid === '') return;

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
