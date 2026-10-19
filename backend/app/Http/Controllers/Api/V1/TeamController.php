<?php
namespace App\Http\Controllers\Api\V1;

use App\Support\ApiException;
use App\Support\Ctx;
use App\Support\H;
use App\Support\Rbac;
use Illuminate\Support\Facades\DB;
use App\Services\NotificationDispatcher;

/** Team & RBAC plus tenant settings, pipeline stages and the audit log. */
class TeamController
{
    public function listTeam(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'team.read');
        $t = $ctx->tenantId;
        $rows = DB::select(
            "SELECT m.id AS membership_id, m.role, m.org_id, m.contact_id, o.name AS org_name,
                    CASE WHEN c.id IS NOT NULL THEN TRIM(c.first_name || ' ' || COALESCE(c.last_name, '')) END AS contact_name,
                    m.department, m.title, m.status AS member_status, m.created_at AS joined_at,
                    u.id, u.name, u.email, u.avatar_color, u.last_login_at, u.mfa_enabled,
                    (SELECT COUNT(*) FROM deals d WHERE d.owner_id = u.id AND d.tenant_id = m.tenant_id AND d.status='open') AS open_deals,
                    (SELECT COUNT(*) FROM tasks k WHERE k.assignee_id = u.id AND k.tenant_id = m.tenant_id AND k.status!='done') AS open_tasks,
                    (SELECT COALESCE(SUM(d.amount),0) FROM deals d WHERE d.owner_id = u.id AND d.tenant_id = m.tenant_id AND d.status='won') AS won_value
               FROM tenant_memberships m JOIN users u ON u.id = m.user_id LEFT JOIN organizations o ON o.id = m.org_id
                    LEFT JOIN contacts c ON c.id = m.contact_id AND c.tenant_id = m.tenant_id
              WHERE m.tenant_id = ? ORDER BY m.role, u.name",
            [$t]
        );
        $invites = DB::select("SELECT id, email, role, status, expires_at, created_at FROM invitations WHERE tenant_id = ? AND role != 'password_reset' ORDER BY created_at DESC", [$t]);
        $seats = DB::selectOne(
            "SELECT p.seats AS limit_value, (SELECT COUNT(*) FROM tenant_memberships WHERE tenant_id = ? AND status='active') AS used
               FROM subscriptions s JOIN plans p ON p.id = s.plan_id WHERE s.tenant_id = ? ORDER BY s.created_at DESC LIMIT 1",
            [$t, $t]
        );
        return H::json([
            'data' => $rows, 'invites' => $invites, 'roles' => Rbac::ROLES,
            'seats' => $seats ?? ['used' => count($rows), 'limit_value' => 5],
        ]);
    }

    /** Role → permission matrix. Readable by anyone with team.read so admins can audit access. */
    public function permissions(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'team.read');
        return H::json([
            'permissions' => Rbac::PERMISSIONS,
            'matrix' => array_map(fn ($r) => [
                'role' => $r,
                'permissions' => array_values(array_filter(Rbac::PERMISSIONS, fn ($p) => Rbac::can($r, $p))),
            ], Rbac::ROLES),
        ]);
    }

    public function invite(Ctx $ctx)
    {
        $user = $ctx->auth();
        H::requirePermission($ctx, 'team.write');
        $t = $ctx->tenantId;
        $b = H::body($ctx);
        $email = strtolower(H::str($b['email'] ?? null, 'email', true, 190));
        $telegramChatId = H::str($b['telegram_chat_id'] ?? null, 'telegram_chat_id', false, 80);
        $role = H::oneOf($b['role'] ?? null, 'role', Rbac::ROLES);
        $orgId = $role === 'client_portal_user' ? H::str($b['org_id'] ?? null, 'org_id', true, 64) : null;
        $contactId = $role === 'client_portal_user' ? H::str($b['contact_id'] ?? null, 'contact_id', true, 64) : null;
        if ($role === 'client_portal_user' && (!$orgId || !$contactId)) {
            throw new ApiException(400, 'Portal klienti uchun kompaniyani tanlang', 'bad_request');
        }
        if ($orgId && !DB::selectOne('SELECT id FROM organizations WHERE id = ? AND tenant_id = ?', [$orgId, $t])) {
            throw new ApiException(404, 'Kompaniya topilmadi', 'not_found');
        }
        if ($contactId && !DB::selectOne('SELECT id FROM contacts WHERE id = ? AND org_id = ? AND tenant_id = ?', [$contactId, $orgId, $t])) {
            throw new ApiException(404, 'Client topilmadi yoki kompaniyaga tegishli emas', 'not_found');
        }
        if ($role === 'tenant_owner' && $ctx->role !== 'tenant_owner') {
            throw new ApiException(400, 'Только владелец компании может назначить роль владельца', 'bad_request');
        }
        $sub = DB::selectOne(
            "SELECT p.seats AS limit_value, (SELECT COUNT(*) FROM tenant_memberships WHERE tenant_id = ? AND status='active') AS used
               FROM subscriptions s JOIN plans p ON p.id = s.plan_id WHERE s.tenant_id = ? ORDER BY s.created_at DESC LIMIT 1",
            [$t, $t]
        );
        if ($sub && $sub->used >= $sub->limit_value) {
            throw new ApiException(402, "Достигнут лимит мест тарифа ({$sub->used}/{$sub->limit_value}). Повысьте тариф, чтобы добавить сотрудников.", 'quota_exceeded', (array) $sub);
        }
        $existingUser = DB::selectOne('SELECT id FROM users WHERE email = ?', [$email]);
        if ($existingUser && DB::selectOne('SELECT 1 FROM tenant_memberships WHERE tenant_id = ? AND user_id = ?', [$t, $existingUser->id])) {
            throw new ApiException(409, 'Пользователь уже состоит в этой компании', 'conflict');
        }
        $token = H::newToken();
        $id = H::newId('inv');
        DB::table('invitations')->insert([
            'id' => $id, 'tenant_id' => $t, 'email' => $email, 'role' => $role, 'org_id' => $orgId, 'contact_id' => $contactId, 'token' => $token,
            'invited_by' => $user['id'], 'status' => 'pending',
            'expires_at' => gmdate('Y-m-d H:i:s', time() + 7 * 86400), 'created_at' => H::now(),
        ]);
        H::audit($ctx, 'user.invited', 'invitation', $id, ['email' => $email, 'role' => $role, 'org_id' => $orgId]);
        $inviteUrl = rtrim((string) config('nexio.frontend_url'), '/') . "/invite/{$token}";
        $dispatcher = new NotificationDispatcher();
        $emailed = $dispatcher->email($email, 'Nexio CRM taklifi', "Siz Nexio CRMga taklif qilindingiz: {$inviteUrl}");
        $telegramSent = false;
        $bot = ltrim((string) config('telegram.bot_username', ''), '@');
        if ($telegramChatId && $bot) {
            $telegramSent = $dispatcher->telegram($telegramChatId, "Nexio CRM taklifi:\n{$inviteUrl}");
        }
        return H::json(['id' => $id, 'inviteUrl' => $inviteUrl, 'telegramInviteUrl' => $bot ? "https://t.me/{$bot}?start=invite_{$token}" : null, 'emailed' => $emailed, 'telegramSent' => $telegramSent], 201);
    }

    public function revokeInvite(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'team.write');
        DB::update("UPDATE invitations SET status='revoked' WHERE id=? AND tenant_id=? AND status='pending'", [$ctx->params['id'], $ctx->tenantId]);
        H::audit($ctx, 'invitation.revoked', 'invitation', $ctx->params['id']);
        return H::json(['ok' => true]);
    }

    public function updateMember(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'roles.write');
        $t = $ctx->tenantId;
        $b = H::body($ctx);
        $member = DB::selectOne('SELECT id, user_id, role, org_id, contact_id FROM tenant_memberships WHERE id = ? AND tenant_id = ?', [$ctx->params['id'], $t]);
        if (!$member) throw new ApiException(404, 'Участник не найден', 'not_found');
        $owners = DB::selectOne("SELECT COUNT(*) AS c FROM tenant_memberships WHERE tenant_id = ? AND role = 'tenant_owner'", [$t]);
        if ($member->role === 'tenant_owner' && $owners->c <= 1 && !empty($b['role']) && $b['role'] !== 'tenant_owner') {
            throw new ApiException(400, 'У компании должен остаться хотя бы один владелец', 'bad_request');
        }
        $role = !empty($b['role']) ? H::oneOf($b['role'], 'role', Rbac::ROLES) : null;
        $status = !empty($b['status']) ? H::oneOf($b['status'], 'status', ['active', 'suspended']) : null;
        $finalRole = $role ?? $member->role;
        $orgId = $finalRole === 'client_portal_user' ? (H::str($b['org_id'] ?? null, 'org_id', false, 64) ?: $member->org_id) : null;
        $contactId = $finalRole === 'client_portal_user' ? (H::str($b['contact_id'] ?? null, 'contact_id', false, 64) ?: $member->contact_id) : null;
        if ($finalRole === 'client_portal_user' && !$orgId) throw new ApiException(400, 'Portal klienti uchun kompaniyani tanlang', 'bad_request');
        if ($orgId && !DB::selectOne('SELECT id FROM organizations WHERE id = ? AND tenant_id = ?', [$orgId, $t])) throw new ApiException(404, 'Kompaniya topilmadi', 'not_found');
        if ($contactId && !DB::selectOne('SELECT id FROM contacts WHERE id = ? AND org_id = ? AND tenant_id = ?', [$contactId, $orgId, $t])) throw new ApiException(404, 'Client topilmadi yoki kompaniyaga tegishli emas', 'not_found');
        DB::update('UPDATE tenant_memberships SET role=COALESCE(?,role), org_id=?, contact_id=?, department=?, title=?, status=COALESCE(?,status) WHERE id=? AND tenant_id=?', [
            $role,
            $orgId,
            $contactId,
            H::str($b['department'] ?? null, 'department', false, 80),
            H::str($b['title'] ?? null, 'title', false, 80),
            $status, $ctx->params['id'], $t,
        ]);
        H::audit($ctx, 'role.changed', 'membership', $ctx->params['id'], ['role' => $role, 'status' => $status]);
        return H::json(['ok' => true]);
    }

    public function removeMember(Ctx $ctx)
    {
        $user = $ctx->auth();
        H::requirePermission($ctx, 'team.write');
        $t = $ctx->tenantId;
        $member = DB::selectOne('SELECT id, user_id, role FROM tenant_memberships WHERE id = ? AND tenant_id = ?', [$ctx->params['id'], $t]);
        if (!$member) throw new ApiException(404, 'Участник не найден', 'not_found');
        if ($member->user_id === $user['id']) throw new ApiException(400, 'Нельзя удалить себя из компании', 'bad_request');
        if ($member->role === 'tenant_owner') {
            $owners = DB::selectOne("SELECT COUNT(*) AS c FROM tenant_memberships WHERE tenant_id = ? AND role = 'tenant_owner'", [$t]);
            if ($owners->c <= 1) throw new ApiException(400, 'У компании должен остаться хотя бы один владелец', 'bad_request');
        }
        DB::delete('DELETE FROM tenant_memberships WHERE id = ? AND tenant_id = ?', [$ctx->params['id'], $t]);
        H::audit($ctx, 'member.removed', 'membership', $ctx->params['id']);
        return H::json(['ok' => true]);
    }

    // ── Settings ─────────────────────────────────────────────────────────────
    public function getSettings(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'settings.read');
        $t = $ctx->tenantId;
        $tenant = DB::selectOne('SELECT id,name,slug,legal_name,logo_text,brand_color,locale,timezone,currency,country,industry,status FROM tenants WHERE id = ?', [$t]);
        $settings = DB::selectOne('SELECT settings FROM tenant_settings WHERE tenant_id = ?', [$t]);
        $flags = DB::select('SELECT key, enabled FROM feature_flags WHERE tenant_id = ?', [$t]);
        $stages = DB::select('SELECT id,key,name,position,probability,kind,color FROM pipeline_stages WHERE tenant_id = ? ORDER BY position', [$t]);
        $usage = DB::select('SELECT metric, quantity, limit_value, period FROM usage_records WHERE tenant_id = ? ORDER BY recorded_at DESC LIMIT 12', [$t]);
        $flagMap = [];
        foreach ($flags as $f) $flagMap[$f->key] = (bool) $f->enabled;
        return H::json([
            'tenant' => $tenant,
            'settings' => $settings ? H::parseJson($settings->settings, []) : [],
            'flags' => $flagMap, 'stages' => $stages, 'usage' => $usage,
        ]);
    }

    public function updateSettings(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'settings.write');
        $t = $ctx->tenantId;
        $b = H::body($ctx);
        DB::update('UPDATE tenants SET name=COALESCE(?,name), legal_name=?, logo_text=?, brand_color=COALESCE(?,brand_color), locale=COALESCE(?,locale), timezone=COALESCE(?,timezone), currency=COALESCE(?,currency), industry=?, updated_at=? WHERE id=?', [
            H::str($b['name'] ?? null, 'name', false, 160),
            H::str($b['legal_name'] ?? null, 'legal_name', false, 200),
            H::str($b['logo_text'] ?? null, 'logo_text', false, 20),
            H::str($b['brand_color'] ?? null, 'brand_color', false, 9),
            !empty($b['locale']) ? H::oneOf($b['locale'], 'locale', ['ru', 'uz', 'en']) : null,
            H::str($b['timezone'] ?? null, 'timezone', false, 60),
            H::str($b['currency'] ?? null, 'currency', false, 8),
            H::str($b['industry'] ?? null, 'industry', false, 120),
            H::now(), $t,
        ]);
        if (!empty($b['settings']) && is_array($b['settings'])) {
            $cur = DB::selectOne('SELECT settings FROM tenant_settings WHERE tenant_id = ?', [$t]);
            $merged = array_merge($cur ? H::parseJson($cur->settings, []) : [], $b['settings']);
            DB::statement(
                'INSERT INTO tenant_settings (tenant_id, settings, updated_at) VALUES (?,?,?)
                 ON CONFLICT(tenant_id) DO UPDATE SET settings=excluded.settings, updated_at=excluded.updated_at',
                [$t, json_encode($merged, JSON_UNESCAPED_UNICODE), H::now()]
            );
        }
        H::audit($ctx, 'settings.updated', 'tenant', $t);
        return H::json(['ok' => true]);
    }

    /** Pipeline stage editing is restricted to owners/admins because it changes data semantics. */
    public function updateStage(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'settings.write');
        $b = H::body($ctx);
        if (!DB::selectOne('SELECT id FROM pipeline_stages WHERE id = ? AND tenant_id = ?', [$ctx->params['id'], $ctx->tenantId])) {
            throw new ApiException(404, 'Стадия не найдена', 'not_found');
        }
        DB::update('UPDATE pipeline_stages SET name=COALESCE(?,name), probability=COALESCE(?,probability), color=COALESCE(?,color) WHERE id=? AND tenant_id=?', [
            H::str($b['name'] ?? null, 'name', false, 80),
            H::num($b['probability'] ?? null, 'probability', false, 0, 100),
            H::str($b['color'] ?? null, 'color', false, 9),
            $ctx->params['id'], $ctx->tenantId,
        ]);
        H::audit($ctx, 'pipeline.stage_updated', 'pipeline_stage', $ctx->params['id']);
        return H::json(['ok' => true]);
    }

    public function addStage(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'settings.write');
        $t = $ctx->tenantId;
        $b = H::body($ctx);
        $pipeline = DB::selectOne('SELECT id FROM pipelines WHERE tenant_id = ? ORDER BY is_default DESC LIMIT 1', [$t]);
        if (!$pipeline) throw new ApiException(404, 'Воронка не найдена', 'not_found');
        $pos = DB::selectOne('SELECT COALESCE(MAX(position),-1)+1 AS p FROM pipeline_stages WHERE pipeline_id = ?', [$pipeline->id]);
        $id = H::newId('stg');
        $key = H::str($b['key'] ?? null, 'key', false, 40) ?? 'stage_' . ($pos->p ?? 0);
        DB::table('pipeline_stages')->insert([
            'id' => $id, 'tenant_id' => $t, 'pipeline_id' => $pipeline->id, 'key' => $key,
            'name' => H::str($b['name'] ?? null, 'name', true, 80),
            'position' => $pos->p ?? 0,
            'probability' => H::num($b['probability'] ?? null, 'probability', false, 0, 100) ?? 50,
            'kind' => H::oneOf($b['kind'] ?? null, 'kind', ['open', 'won', 'lost'], 'open'),
            'color' => H::str($b['color'] ?? null, 'color', false, 9) ?? '#2563EB',
        ]);
        H::audit($ctx, 'pipeline.stage_created', 'pipeline_stage', $id);
        return H::json(['id' => $id], 201);
    }

    public function removeStage(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'settings.write');
        $t = $ctx->tenantId;
        $used = DB::selectOne('SELECT COUNT(*) AS c FROM deals WHERE stage_id = ? AND tenant_id = ?', [$ctx->params['id'], $t]);
        if ($used->c > 0) {
            throw new ApiException(409, "Стадия используется в {$used->c} сделках — сначала перенесите их", 'conflict');
        }
        DB::delete('DELETE FROM pipeline_stages WHERE id = ? AND tenant_id = ?', [$ctx->params['id'], $t]);
        H::audit($ctx, 'pipeline.stage_deleted', 'pipeline_stage', $ctx->params['id']);
        return H::json(['ok' => true]);
    }

    public function auditLog(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'audit.read');
        $t = $ctx->tenantId;
        $limit = min(200, (int) ($ctx->query('limit') ?: 60));
        $rows = DB::select(
            'SELECT a.id, a.action, a.target, a.target_id, a.actor_name, a.meta, a.ip, a.created_at, u.avatar_color
               FROM audit_logs a LEFT JOIN users u ON u.id = a.actor_id
              WHERE a.tenant_id = ? ORDER BY a.created_at DESC LIMIT ?',
            [$t, $limit]
        );
        return H::json(['data' => $rows]);
    }
}
