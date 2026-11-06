<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Middleware\ApiContext;
use App\Services\NotificationDispatcher;
use App\Support\ApiException;
use App\Support\Ctx;
use App\Support\H;
use Illuminate\Support\Facades\DB;

class AuthController
{
    private const DEFAULT_STAGES = [
        ['new', 'Новый лид', 10, 'open', '#94A3B8'],
        ['qualified', 'Квалификация', 25, 'open', '#5AA0FA'],
        ['contact', 'Контакт', 40, 'open', '#3B82F6'],
        ['negotiation', 'Переговоры', 60, 'open', '#2563EB'],
        ['proposal', 'Предложение', 75, 'open', '#1D4ED8'],
        ['agreed', 'Согласовано', 90, 'open', '#0EA5E9'],
        ['order', 'Заказ', 95, 'won', '#10B981'],
        ['done', 'Выполнено', 100, 'won', '#059669'],
    ];

    private function sessionCookie(string $id, int $maxAge, bool $secure): string
    {
        return ApiContext::COOKIE . "={$id}; Path=/; HttpOnly; SameSite=Lax; Max-Age={$maxAge}" . ($secure ? '; Secure' : '');
    }

    private function clearCookie(): string
    {
        return ApiContext::COOKIE . '=; Path=/; HttpOnly; SameSite=Lax; Max-Age=0';
    }

    private function plusDays(int $d): string
    {
        return gmdate('Y-m-d H:i:s', time() + $d * 86400);
    }

    private function createSession(Ctx $ctx, string $userId, ?string $tenantId): string
    {
        $id = H::newToken();
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'ip' => $ctx->request->ip(),
            'user_agent' => mb_substr((string) $ctx->request->header('User-Agent'), 0, 250),
            'expires_at' => gmdate('Y-m-d H:i:s', time() + H::SESSION_TTL),
            'created_at' => H::now(),
        ]);
        return $id;
    }

    /** Creates a tenant workspace with its default pipeline, trial subscription and settings. */
    public function provisionTenant(string $name, string $ownerUserId, string $ownerName): string
    {
        $tenantId = H::newId('tnt');
        $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($name)) ?? '', '-');
        if ($slug === '') $slug = 'company';
        if (DB::selectOne('SELECT 1 FROM tenants WHERE slug = ?', [$slug])) {
            $slug .= '-' . substr(bin2hex(random_bytes(3)), 0, 4);
        }
        $plan = DB::selectOne("SELECT id, seats, price_monthly, currency FROM plans WHERE code = 'starter'");
        $ts = H::now();

        DB::table('tenants')->insert([
            'id' => $tenantId, 'name' => $name, 'slug' => $slug, 'legal_name' => $name,
            'logo_text' => mb_substr(strtoupper(explode(' ', $name)[0]), 0, 12),
            'brand_color' => '#2563EB', 'locale' => 'ru', 'timezone' => 'Asia/Tashkent',
            'currency' => 'UZS', 'country' => 'UZ', 'status' => 'trial',
            'plan_id' => $plan->id ?? null, 'trial_ends_at' => $this->plusDays(14),
            'created_at' => $ts, 'updated_at' => $ts,
        ]);
        DB::table('tenant_settings')->insert([
            'tenant_id' => $tenantId,
            'settings' => json_encode(['work_hours' => '09:00-18:00', 'week_start' => 'mon', 'tax_rate' => 12, 'date_format' => 'DD.MM.YYYY']),
            'updated_at' => $ts,
        ]);
        DB::table('tenant_memberships')->insert([
            'id' => H::newId('mem'), 'tenant_id' => $tenantId, 'user_id' => $ownerUserId,
            'role' => 'tenant_owner', 'status' => 'active', 'created_at' => $ts,
        ]);

        $pipelineId = H::newId('pip');
        DB::table('pipelines')->insert([
            'id' => $pipelineId, 'tenant_id' => $tenantId, 'name' => 'Sales Pipeline',
            'is_default' => 1, 'created_at' => $ts,
        ]);
        foreach (self::DEFAULT_STAGES as $i => [$key, $sname, $prob, $kind, $color]) {
            DB::table('pipeline_stages')->insert([
                'id' => H::newId('stg'), 'tenant_id' => $tenantId, 'pipeline_id' => $pipelineId,
                'key' => $key, 'name' => $sname, 'position' => $i, 'probability' => $prob,
                'kind' => $kind, 'color' => $color,
            ]);
        }
        foreach (['ai_agents' => 0, 'voice_minutes' => 0, 'custom_domain' => 0, 'automations' => 0] as $k => $v) {
            DB::table('feature_flags')->insert([
                'id' => H::newId('ff'), 'tenant_id' => $tenantId, 'key' => $k,
                'enabled' => $v, 'created_at' => $ts,
            ]);
        }
        if ($plan) {
            DB::table('subscriptions')->insert([
                'id' => H::newId('sub'), 'tenant_id' => $tenantId, 'plan_id' => $plan->id,
                'status' => 'trialing', 'seats' => $plan->seats, 'amount' => $plan->price_monthly,
                'currency' => $plan->currency ?? 'USD', 'interval' => 'month',
                'period_start' => $ts, 'period_end' => $this->plusDays(14), 'created_at' => $ts,
            ]);
        }
        DB::table('audit_logs')->insert([
            'id' => H::newId('aud'), 'tenant_id' => $tenantId, 'actor_id' => $ownerUserId,
            'actor_name' => $ownerName, 'action' => 'tenant.created', 'target' => 'tenant',
            'meta' => json_encode(['name' => $name], JSON_UNESCAPED_UNICODE), 'created_at' => $ts,
        ]);
        return $tenantId;
    }

    public function register(Ctx $ctx)
    {
        $b = H::body($ctx);
        $name = H::str($b['name'] ?? null, 'name', true, 120);
        $email = strtolower(trim(H::str($b['email'] ?? null, 'email', true, 190)));
        $password = H::str($b['password'] ?? null, 'password', true, 200);
        $company = H::str($b['company'] ?? null, 'company', true, 160);
        if (!preg_match('/^[^@\s]+@[^@\s]+\.[^@\s]+$/', $email)) {
            throw new ApiException(400, 'Некорректный email', 'bad_request');
        }
        if (mb_strlen($password) < 8) {
            throw new ApiException(400, 'Пароль должен содержать минимум 8 символов', 'bad_request');
        }
        if (DB::selectOne('SELECT 1 FROM users WHERE email = ?', [$email])) {
            throw new ApiException(409, 'Пользователь с таким email уже зарегистрирован', 'conflict');
        }

        $userId = H::newId('usr');
        $ts = H::now();
        $color = H::randomColor($email);
        $verifyToken = H::newToken();
        DB::table('users')->insert([
            'id' => $userId, 'email' => $email, 'password_hash' => H::hashPassword($password),
            'name' => $name, 'avatar_color' => $color, 'is_platform_admin' => 0,
            'email_verify_token' => $verifyToken,
            'created_at' => $ts, 'updated_at' => $ts,
        ]);
        $tenantId = $this->provisionTenant($company, $userId, $name);
        $this->sendVerification($email, $verifyToken);
        $sid = $this->createSession($ctx, $userId, $tenantId);

        return H::json([
            'user' => ['id' => $userId, 'email' => $email, 'name' => $name, 'avatar_color' => $color, 'is_platform_admin' => 0],
            'tenantId' => $tenantId, 'role' => 'tenant_owner',
        ], 201, ['Set-Cookie' => $this->sessionCookie($sid, H::SESSION_TTL, $ctx->request->isSecure())]);
    }

    public function login(Ctx $ctx)
    {
        $b = H::body($ctx);
        $email = strtolower(H::str($b['email'] ?? null, 'email', true, 190));
        $password = H::str($b['password'] ?? null, 'password', true, 200);
        // Older accounts may have been created before email normalization was added.
        // Match them case-insensitively while keeping the canonical lowercase value for new accounts.
        $u = DB::selectOne('SELECT id, email, name, password_hash, avatar_color, is_platform_admin FROM users WHERE LOWER(TRIM(email)) = ?', [$email]);
        // Uniform failure message so the endpoint does not reveal which emails exist.
        if (!$u || !H::verifyPassword($password, $u->password_hash)) {
            throw new ApiException(401, 'Неверный email или пароль', 'unauthorized');
        }
        $m = DB::selectOne("SELECT tenant_id, role FROM tenant_memberships WHERE user_id = ? AND status = 'active' ORDER BY created_at LIMIT 1", [$u->id]);
        $sid = $this->createSession($ctx, $u->id, $m->tenant_id ?? null);
        DB::table('users')->where('id', $u->id)->update(['last_login_at' => H::now()]);
        if ($m) {
            DB::table('audit_logs')->insert([
                'id' => H::newId('aud'), 'tenant_id' => $m->tenant_id, 'actor_id' => $u->id,
                'actor_name' => $u->name, 'action' => 'login.success', 'target' => 'session',
                'created_at' => H::now(),
            ]);
        }
        return H::json([
            'user' => ['id' => $u->id, 'email' => $u->email, 'name' => $u->name, 'avatar_color' => $u->avatar_color, 'is_platform_admin' => (int) $u->is_platform_admin],
            'tenantId' => $m->tenant_id ?? null, 'role' => $m->role ?? null,
        ], 200, ['Set-Cookie' => $this->sessionCookie($sid, H::SESSION_TTL, $ctx->request->isSecure())]);
    }

    private function sendVerification(string $email, string $token): bool
    {
        $url = rtrim((string) config('nexio.frontend_url'), '/') . "/verify-email/{$token}";
        return (new NotificationDispatcher())->email($email, 'Nexio CRM: emailni tasdiqlang', "Emailingizni tasdiqlash uchun havolani oching:\n{$url}");
    }

    public function verifyEmail(Ctx $ctx)
    {
        $b = H::body($ctx);
        $token = H::str($b['token'] ?? null, 'token', true, 80);
        $updated = DB::table('users')->where('email_verify_token', $token)->update(['email_verified_at' => H::now(), 'email_verify_token' => null]);
        if (!$updated) throw new ApiException(400, 'Havola yaroqsiz yoki allaqachon ishlatilgan', 'bad_request');
        return H::json(['ok' => true]);
    }

    public function resendVerification(Ctx $ctx)
    {
        $user = $ctx->auth();
        $row = DB::table('users')->where('id', $user['id'])->first(['email', 'email_verified_at']);
        if ($row->email_verified_at) return H::json(['ok' => true, 'sent' => false]);
        $token = H::newToken();
        DB::table('users')->where('id', $user['id'])->update(['email_verify_token' => $token]);
        return H::json(['ok' => true, 'sent' => $this->sendVerification((string) $row->email, $token)]);
    }

    public function logout(Ctx $ctx)
    {
        if ($ctx->sessionId) DB::table('sessions')->where('id', $ctx->sessionId)->delete();
        return H::json(['ok' => true], 200, ['Set-Cookie' => $this->clearCookie()]);
    }

    /** Current user + every tenant they belong to, for the company switcher. */
    public function me(Ctx $ctx)
    {
        if (!$ctx->user) {
            return H::json(['user' => null, 'tenants' => [], 'tenant' => null, 'role' => null, 'isPlatformAdmin' => false]);
        }
        $tenants = DB::select(
            "SELECT t.id, t.name, t.slug, t.brand_color, t.logo_text, t.status, t.currency, t.timezone, t.locale,
                    m.role, s.status AS subscription_status
               FROM tenant_memberships m
               JOIN tenants t ON t.id = m.tenant_id
               LEFT JOIN subscriptions s ON s.tenant_id = t.id AND s.status IN ('active','trialing','grace','past_due')
              WHERE m.user_id = ? AND m.status = 'active'
              ORDER BY t.name",
            [$ctx->user['id']]
        );
        $current = $ctx->tenantId
            ? DB::selectOne('SELECT id, name, slug, brand_color, logo_text, locale, timezone, currency, status, legal_name FROM tenants WHERE id = ?', [$ctx->tenantId])
            : null;
        // Without a mail provider the user could never finish verifying, so nothing is asked of them.
        $verified = DB::table('users')->where('id', $ctx->user['id'])->value('email_verified_at');
        return H::json([
            'user' => $ctx->user, 'role' => $ctx->role, 'tenant' => $current,
            'tenants' => $tenants, 'isPlatformAdmin' => (bool) $ctx->user['is_platform_admin'],
            'emailVerificationRequired' => !$verified && (new NotificationDispatcher())->mailConfigured(),
        ]);
    }

    public function switchTenant(Ctx $ctx)
    {
        $user = $ctx->auth();
        $b = H::body($ctx);
        $tenantId = H::str($b['tenantId'] ?? null, 'tenantId');
        $m = DB::selectOne("SELECT role FROM tenant_memberships WHERE tenant_id = ? AND user_id = ? AND status = 'active'", [$tenantId, $user['id']]);
        // Platform operators may switch into any tenant for audited support work.
        if (!$m && empty($user['is_platform_admin'])) {
            throw new ApiException(403, 'У вас нет доступа к этой компании', 'forbidden');
        }
        if ($ctx->sessionId) DB::table('sessions')->where('id', $ctx->sessionId)->update(['tenant_id' => $tenantId]);
        DB::table('audit_logs')->insert([
            'id' => H::newId('aud'), 'tenant_id' => $tenantId, 'actor_id' => $user['id'],
            'actor_name' => $user['name'], 'action' => 'tenant.switched', 'target' => 'session',
            'created_at' => H::now(),
        ]);
        return H::json(['ok' => true, 'tenantId' => $tenantId, 'role' => $m->role ?? 'read_only']);
    }

    /** Token-based password reset. Always reports success to avoid account enumeration. */
    public function forgotPassword(Ctx $ctx)
    {
        $b = H::body($ctx);
        $email = strtolower(H::str($b['email'] ?? null, 'email', true, 190));
        $u = DB::selectOne('SELECT id FROM users WHERE email = ?', [$email]);
        $devToken = null;
        if ($u) {
            $token = H::newToken();
            $devToken = $token;
            $resetUrl = rtrim((string) config('nexio.frontend_url'), '/') . "/reset-password/{$token}";
            (new NotificationDispatcher())->email($email, 'Nexio CRM: parolni tiklash', "Parolni tiklash uchun havolani oching (1 soat amal qiladi):\n{$resetUrl}");
            DB::table('invitations')->insert([
                'id' => H::newId('inv'), 'tenant_id' => null, 'email' => $email,
                'role' => 'password_reset', 'token' => $token, 'status' => 'pending',
                'expires_at' => gmdate('Y-m-d H:i:s', time() + 3600), 'created_at' => H::now(),
            ]);
        }
        $env = config('app.env');
        return H::json([
            'ok' => true,
            'message' => 'Если аккаунт существует, ссылка для сброса отправлена.',
            'developmentToken' => $env === 'production' ? null : $devToken,
        ]);
    }

    public function resetPassword(Ctx $ctx)
    {
        $b = H::body($ctx);
        $token = H::str($b['token'] ?? null, 'token');
        $password = H::str($b['password'] ?? null, 'password', true, 200);
        if (mb_strlen($password) < 8) {
            throw new ApiException(400, 'Пароль должен содержать минимум 8 символов', 'bad_request');
        }
        $inv = DB::selectOne("SELECT id, email FROM invitations WHERE token = ? AND role = 'password_reset' AND status = 'pending'", [$token]);
        if (!$inv) throw new ApiException(400, 'Ссылка недействительна или истекла', 'bad_request');
        $u = DB::selectOne('SELECT id FROM users WHERE email = ?', [$inv->email]);
        if (!$u) throw new ApiException(400, 'Ссылка недействительна или истекла', 'bad_request');
        DB::transaction(function () use ($u, $inv, $password) {
            DB::table('users')->where('id', $u->id)->update(['password_hash' => H::hashPassword($password), 'updated_at' => H::now()]);
            DB::table('invitations')->where('id', $inv->id)->update(['status' => 'accepted']);
            DB::table('sessions')->where('user_id', $u->id)->delete();
        });
        return H::json(['ok' => true]);
    }

    public function inviteInfo(Ctx $ctx)
    {
        $inv = DB::selectOne(
            "SELECT id, tenant_id, email, role, org_id, contact_id FROM invitations WHERE token = ? AND status = 'pending' AND expires_at > ?",
            [$ctx->params['token'], H::now()]
        );
        if (!$inv) throw new ApiException(410, 'Приглашение недействительно или истекло', 'gone');
        $tenant = DB::selectOne('SELECT name FROM tenants WHERE id = ?', [$inv->tenant_id]);
        return H::json(['email' => $inv->email, 'role' => $inv->role, 'tenantName' => $tenant->name ?? '']);
    }

    public function acceptInvite(Ctx $ctx)
    {
        $inv = DB::selectOne(
            "SELECT id, tenant_id, email, role, org_id, contact_id FROM invitations WHERE token = ? AND status = 'pending' AND expires_at > ?",
            [$ctx->params['token'], H::now()]
        );
        if (!$inv) throw new ApiException(410, 'Приглашение недействительно или истекло', 'gone');
        $b = H::body($ctx);
        $user = DB::selectOne('SELECT id, name, email, avatar_color, is_platform_admin FROM users WHERE email = ?', [$inv->email]);
        if (!$user) {
            $name = H::str($b['name'] ?? null, 'name', true, 120);
            $password = H::str($b['password'] ?? null, 'password', true, 200);
            if (mb_strlen($password) < 8) {
                throw new ApiException(400, 'Пароль должен содержать минимум 8 символов', 'bad_request');
            }
            $id = H::newId('usr');
            $color = H::randomColor($inv->email);
            DB::table('users')->insert([
                'id' => $id, 'email' => $inv->email, 'password_hash' => H::hashPassword($password),
                'name' => $name, 'avatar_color' => $color, 'is_platform_admin' => 0,
                'email_verified_at' => H::now(), // the invite link was delivered to this address
                'created_at' => H::now(), 'updated_at' => H::now(),
            ]);
            $user = (object) ['id' => $id, 'name' => $name, 'email' => $inv->email, 'avatar_color' => $color, 'is_platform_admin' => 0];
        }
        if (!DB::selectOne('SELECT 1 FROM tenant_memberships WHERE tenant_id = ? AND user_id = ?', [$inv->tenant_id, $user->id])) {
            DB::table('tenant_memberships')->insert([
                'id' => H::newId('mem'), 'tenant_id' => $inv->tenant_id, 'user_id' => $user->id,
                'role' => $inv->role, 'status' => 'active', 'org_id' => $inv->org_id ?? null, 'contact_id' => $inv->contact_id ?? null, 'created_at' => H::now(),
            ]);
        }
        DB::table('invitations')->where('id', $inv->id)->update(['status' => 'accepted']);
        (new NotificationDispatcher())->notifyAdmins($inv->tenant_id, 'info', 'Yangi xodim qo‘shildi', "{$user->name} ({$user->email}) taklifni qabul qildi.", '/app/team');
        $sid = $this->createSession($ctx, $user->id, $inv->tenant_id);
        return H::json([
            'ok' => true, 'user' => ['id' => $user->id, 'email' => $user->email, 'name' => $user->name],
            'tenantId' => $inv->tenant_id, 'role' => $inv->role,
        ], 200, ['Set-Cookie' => $this->sessionCookie($sid, H::SESSION_TTL, $ctx->request->isSecure())]);
    }
}
