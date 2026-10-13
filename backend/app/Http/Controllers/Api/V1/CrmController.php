<?php
namespace App\Http\Controllers\Api\V1;

use App\Support\ApiException;
use App\Support\Ctx;
use App\Support\H;
use Illuminate\Support\Facades\DB;

class CrmController
{
    private const ORDER_COLS = [
        'organizations' => ['name' => 'name', 'created' => 'created_at', 'industry' => 'industry'],
        'contacts' => ['name' => 'first_name', 'created' => 'created_at', 'company' => 'org_id'],
        'leads' => ['created' => 'created_at', 'score' => 'score', 'value' => 'value', 'status' => 'status'],
        'deals' => ['created' => 'created_at', 'amount' => 'amount', 'close' => 'expected_close_at', 'probability' => 'probability'],
    ];

    public static function orderBy(string $table, Ctx $ctx, string $fallback): string
    {
        $key = (string) $ctx->query('sort', '');
        $dir = strtolower((string) $ctx->query('dir', 'desc')) === 'asc' ? 'ASC' : 'DESC';
        $col = self::ORDER_COLS[$table][$key] ?? null;
        return $col ? "{$col} {$dir}" : $fallback;
    }

    private function exists(Ctx $ctx, string $table, string $id): bool
    {
        return (bool) DB::selectOne("SELECT 1 AS x FROM {$table} WHERE id = ? AND tenant_id = ?", [$id, $ctx->tenantId]);
    }

    // ── Organizations (companies) ────────────────────────────────────────────
    public function listOrganizations(Ctx $ctx)
    {
        $ctx->auth();
        $t = $ctx->tenantId;
        [$limit, $page, $offset] = H::paginate($ctx);
        $q = $ctx->query('q'); $status = $ctx->query('status'); $tier = $ctx->query('tier');
        $where = ['o.tenant_id = ?']; $binds = [$t];
        if ($q) { $where[] = '(o.name LIKE ? OR o.email LIKE ? OR o.industry LIKE ?)'; array_push($binds, "%{$q}%", "%{$q}%", "%{$q}%"); }
        if ($status) { $where[] = 'o.status = ?'; $binds[] = $status; }
        if ($tier) { $where[] = 'o.tier = ?'; $binds[] = $tier; }
        $w = implode(' AND ', $where);
        $order = self::orderBy('organizations', $ctx, 'o.created_at DESC');
        $rows = DB::select(
            "SELECT o.*, u.name AS owner_name, u.avatar_color AS owner_color,
                    (SELECT COUNT(*) FROM contacts c WHERE c.org_id = o.id) AS contacts_count,
                    (SELECT COUNT(*) FROM deals d WHERE d.org_id = o.id AND d.status='open') AS open_deals,
                    (SELECT COALESCE(SUM(d.amount),0) FROM deals d WHERE d.org_id = o.id AND d.status='won') AS won_value,
                    (SELECT COALESCE(SUM(ord.amount),0) FROM orders ord WHERE ord.org_id = o.id AND ord.status='fulfilled') AS revenue,
                    (SELECT MAX(a.created_at) FROM activities a WHERE a.entity_id = o.id) AS last_activity
               FROM organizations o LEFT JOIN users u ON u.id = o.owner_id
              WHERE {$w} ORDER BY {$order} LIMIT ? OFFSET ?",
            [...$binds, $limit, $offset]
        );
        $total = DB::selectOne("SELECT COUNT(*) AS c FROM organizations o WHERE {$w}", $binds);
        return H::json(['data' => $rows, 'total' => (int) ($total->c ?? 0), 'page' => $page, 'limit' => $limit]);
    }

    public function getOrganization(Ctx $ctx)
    {
        $ctx->auth();
        $t = $ctx->tenantId;
        $id = $ctx->params['id'];
        $org = DB::selectOne('SELECT * FROM organizations WHERE id = ? AND tenant_id = ?', [$id, $t]);
        if (!$org) throw new ApiException(404, 'Компания не найдена', 'not_found');
        $contacts = DB::select('SELECT * FROM contacts WHERE org_id = ? ORDER BY is_primary DESC, first_name', [$id]);
        $deals = DB::select('SELECT d.*, s.name AS stage_name, s.color AS stage_color FROM deals d LEFT JOIN pipeline_stages s ON s.id = d.stage_id WHERE d.org_id = ? ORDER BY d.created_at DESC LIMIT 20', [$id]);
        $orders = DB::select('SELECT * FROM orders WHERE org_id = ? ORDER BY created_at DESC LIMIT 10', [$id]);
        $activity = DB::select("SELECT id, kind, subject, actor_name, created_at FROM activities WHERE tenant_id=? AND ((entity_type='organization' AND entity_id=?) OR entity_id IN (SELECT id FROM deals WHERE org_id=?)) ORDER BY created_at DESC LIMIT 20", [$t, $id, $id]);
        $projects = DB::select('SELECT id, name, status, progress, due_date FROM projects WHERE org_id = ? LIMIT 10', [$id]);
        $tickets = DB::select('SELECT id, number, subject, status, priority FROM tickets WHERE org_id = ? LIMIT 10', [$id]);
        return H::json([
            ...(array) $org, 'tags' => H::parseJson($org->tags, []),
            'contacts' => $contacts, 'deals' => $deals, 'orders' => $orders,
            'activity' => $activity, 'projects' => $projects, 'tickets' => $tickets,
        ]);
    }

    public function createOrganization(Ctx $ctx)
    {
        $user = $ctx->auth();
        H::requirePermission($ctx, 'crm.write');
        $b = H::body($ctx);
        $id = H::newId('org');
        DB::table('organizations')->insert([
            'id' => $id, 'tenant_id' => $ctx->tenantId,
            'name' => H::str($b['name'] ?? null, 'name', true, 200),
            'industry' => H::str($b['industry'] ?? null, 'industry', false, 120),
            'website' => H::str($b['website'] ?? null, 'website', false, 200),
            'email' => H::str($b['email'] ?? null, 'email', false, 190),
            'phone' => H::str($b['phone'] ?? null, 'phone', false, 40),
            'country' => H::str($b['country'] ?? null, 'country', false, 80),
            'city' => H::str($b['city'] ?? null, 'city', false, 80),
            'address' => H::str($b['address'] ?? null, 'address', false, 250),
            'size' => H::str($b['size'] ?? null, 'size', false, 40),
            'status' => H::oneOf($b['status'] ?? null, 'status', ['active', 'prospect', 'churned'], 'prospect'),
            'tier' => H::oneOf($b['tier'] ?? null, 'tier', ['vip', 'standard', 'basic'], 'standard'),
            'owner_id' => $b['owner_id'] ?? $user['id'],
            'tags' => json_encode(is_array($b['tags'] ?? null) ? $b['tags'] : [], JSON_UNESCAPED_UNICODE),
            'created_at' => H::now(), 'updated_at' => H::now(),
        ]);
        H::audit($ctx, 'organization.created', 'organization', $id, ['name' => $b['name'] ?? null]);
        return H::json(['id' => $id], 201);
    }

    public function updateOrganization(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'crm.write');
        if (!$this->exists($ctx, 'organizations', $ctx->params['id'])) throw new ApiException(404, 'Компания не найдена', 'not_found');
        $b = H::body($ctx);
        DB::update(
            'UPDATE organizations SET name=COALESCE(?,name), industry=?, website=?, email=?, phone=?, country=?, city=?, address=?, size=?, status=COALESCE(?,status), tier=COALESCE(?,tier), owner_id=?, notes=?, updated_at=? WHERE id=? AND tenant_id=?',
            [
                H::str($b['name'] ?? null, 'name', false, 200),
                H::str($b['industry'] ?? null, 'industry', false, 120),
                H::str($b['website'] ?? null, 'website', false, 200),
                H::str($b['email'] ?? null, 'email', false, 190),
                H::str($b['phone'] ?? null, 'phone', false, 40),
                H::str($b['country'] ?? null, 'country', false, 80),
                H::str($b['city'] ?? null, 'city', false, 80),
                H::str($b['address'] ?? null, 'address', false, 250),
                H::str($b['size'] ?? null, 'size', false, 40),
                $b['status'] ?? null, $b['tier'] ?? null, $b['owner_id'] ?? null,
                H::str($b['notes'] ?? null, 'notes', false, 2000),
                H::now(), $ctx->params['id'], $ctx->tenantId,
            ]
        );
        H::audit($ctx, 'organization.updated', 'organization', $ctx->params['id']);
        return H::json(['ok' => true]);
    }

    public function deleteOrganization(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'crm.delete');
        if (!$this->exists($ctx, 'organizations', $ctx->params['id'])) throw new ApiException(404, 'Компания не найдена', 'not_found');
        DB::delete('DELETE FROM organizations WHERE id = ? AND tenant_id = ?', [$ctx->params['id'], $ctx->tenantId]);
        H::audit($ctx, 'organization.deleted', 'organization', $ctx->params['id']);
        return H::json(['ok' => true]);
    }

    // ── Contacts ─────────────────────────────────────────────────────────────
    public function listContacts(Ctx $ctx)
    {
        $ctx->auth();
        $t = $ctx->tenantId;
        [$limit, $page, $offset] = H::paginate($ctx);
        $q = $ctx->query('q'); $status = $ctx->query('status'); $orgId = $ctx->query('org_id');
        $where = ['c.tenant_id = ?']; $binds = [$t];
        if ($q) { $where[] = '(c.first_name LIKE ? OR c.last_name LIKE ? OR c.email LIKE ? OR c.position LIKE ?)'; array_push($binds, "%{$q}%", "%{$q}%", "%{$q}%", "%{$q}%"); }
        if ($status) { $where[] = 'c.status = ?'; $binds[] = $status; }
        if ($orgId) { $where[] = 'c.org_id = ?'; $binds[] = $orgId; }
        $w = implode(' AND ', $where);
        $order = self::orderBy('contacts', $ctx, 'c.created_at DESC');
        $rows = DB::select(
            "SELECT c.*, o.name AS org_name, u.name AS owner_name FROM contacts c
               LEFT JOIN organizations o ON o.id = c.org_id
               LEFT JOIN users u ON u.id = c.owner_id
              WHERE {$w} ORDER BY {$order} LIMIT ? OFFSET ?",
            [...$binds, $limit, $offset]
        );
        $total = DB::selectOne("SELECT COUNT(*) AS c FROM contacts c WHERE {$w}", $binds);
        $data = array_map(fn ($r) => [...(array) $r, 'tags' => H::parseJson($r->tags, [])], $rows);
        return H::json(['data' => $data, 'total' => (int) ($total->c ?? 0), 'page' => $page, 'limit' => $limit]);
    }

    public function createContact(Ctx $ctx)
    {
        $user = $ctx->auth();
        H::requirePermission($ctx, 'crm.write');
        $b = H::body($ctx);
        $id = H::newId('con');
        $phone = H::str($b['phone'] ?? null, 'phone', false, 40);
        DB::table('contacts')->insert([
            'id' => $id, 'tenant_id' => $ctx->tenantId, 'org_id' => $b['org_id'] ?? null,
            'first_name' => H::str($b['first_name'] ?? null, 'first_name', true, 80),
            'last_name' => H::str($b['last_name'] ?? null, 'last_name', false, 80),
            'position' => H::str($b['position'] ?? null, 'position', false, 120),
            'email' => H::str($b['email'] ?? null, 'email', false, 190),
            'phone' => $phone,
            'telegram' => H::str($b['telegram'] ?? null, 'telegram', false, 60),
            'source' => H::str($b['source'] ?? null, 'source', false, 60),
            'status' => H::oneOf($b['status'] ?? null, 'status', ['active', 'lead', 'inactive', 'do_not_contact'], 'active'),
            'is_primary' => !empty($b['is_primary']) ? 1 : 0,
            'consent_marketing' => !empty($b['consent_marketing']) ? 1 : 0,
            'consent_calls' => !empty($b['consent_calls']) ? 1 : 0,
            'owner_id' => $b['owner_id'] ?? $user['id'],
            'tags' => json_encode(is_array($b['tags'] ?? null) ? $b['tags'] : [], JSON_UNESCAPED_UNICODE),
            'created_at' => H::now(), 'updated_at' => H::now(),
        ]);
        if ($phone && DB::selectOne('SELECT 1 AS x FROM suppression_list WHERE tenant_id = ? AND phone = ?', [$ctx->tenantId, $phone])) {
            DB::table('contacts')->where('id', $id)->update(['status' => 'do_not_contact']);
        }
        H::audit($ctx, 'contact.created', 'contact', $id);
        return H::json(['id' => $id], 201);
    }

    public function updateContact(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'crm.write');
        if (!$this->exists($ctx, 'contacts', $ctx->params['id'])) throw new ApiException(404, 'Контакт не найден', 'not_found');
        $b = H::body($ctx);
        DB::update(
            'UPDATE contacts SET first_name=COALESCE(?,first_name), last_name=?, position=?, email=?, phone=?, telegram=?, source=?, status=COALESCE(?,status), org_id=?, consent_marketing=COALESCE(?,consent_marketing), consent_calls=COALESCE(?,consent_calls), owner_id=?, updated_at=? WHERE id=? AND tenant_id=?',
            [
                H::str($b['first_name'] ?? null, 'first_name', false, 80),
                H::str($b['last_name'] ?? null, 'last_name', false, 80),
                H::str($b['position'] ?? null, 'position', false, 120),
                H::str($b['email'] ?? null, 'email', false, 190),
                H::str($b['phone'] ?? null, 'phone', false, 40),
                H::str($b['telegram'] ?? null, 'telegram', false, 60),
                H::str($b['source'] ?? null, 'source', false, 60),
                $b['status'] ?? null, $b['org_id'] ?? null,
                !array_key_exists('consent_marketing', $b) ? null : (!empty($b['consent_marketing']) ? 1 : 0),
                !array_key_exists('consent_calls', $b) ? null : (!empty($b['consent_calls']) ? 1 : 0),
                $b['owner_id'] ?? null, H::now(), $ctx->params['id'], $ctx->tenantId,
            ]
        );
        H::audit($ctx, 'contact.updated', 'contact', $ctx->params['id']);
        return H::json(['ok' => true]);
    }

    public function deleteContact(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'crm.delete');
        if (!$this->exists($ctx, 'contacts', $ctx->params['id'])) throw new ApiException(404, 'Контакт не найден', 'not_found');
        DB::delete('DELETE FROM contacts WHERE id = ? AND tenant_id = ?', [$ctx->params['id'], $ctx->tenantId]);
        H::audit($ctx, 'contact.deleted', 'contact', $ctx->params['id']);
        return H::json(['ok' => true]);
    }
}
