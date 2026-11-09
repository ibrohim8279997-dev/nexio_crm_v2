<?php
namespace App\Http\Controllers\Api\V1;

use App\Services\NotificationDispatcher;
use App\Support\ApiException;
use App\Support\Ctx;
use App\Support\H;
use Illuminate\Support\Facades\DB;

class SalesController
{
    private const ORDER_COLS = [
        'leads' => ['created' => 'created_at', 'score' => 'score', 'value' => 'value', 'status' => 'status'],
        'deals' => ['created' => 'created_at', 'amount' => 'amount', 'close' => 'expected_close_at', 'probability' => 'probability'],
    ];

    private function exists(Ctx $ctx, string $table, string $id): bool
    {
        return (bool) DB::selectOne("SELECT 1 AS x FROM {$table} WHERE id = ? AND tenant_id = ?", [$id, $ctx->tenantId]);
    }

    private function order(string $table, Ctx $ctx, string $fallback): string
    {
        $key = (string) $ctx->query('sort', '');
        $dir = strtolower((string) $ctx->query('dir', 'desc')) === 'asc' ? 'ASC' : 'DESC';
        $col = self::ORDER_COLS[$table][$key] ?? null;
        return $col ? "{$col} {$dir}" : $fallback;
    }

    // ── Leads ────────────────────────────────────────────────────────────────
    public function listLeads(Ctx $ctx)
    {
        $ctx->auth();
        $t = $ctx->tenantId;
        [$limit, $page, $offset] = H::paginate($ctx);
        $q = $ctx->query('q'); $status = $ctx->query('status'); $source = $ctx->query('source');
        $where = ['l.tenant_id = ?']; $binds = [$t];
        if ($q) { $where[] = '(l.title LIKE ? OR l.contact_name LIKE ? OR l.email LIKE ?)'; array_push($binds, "%{$q}%", "%{$q}%", "%{$q}%"); }
        if ($status) { $where[] = 'l.status = ?'; $binds[] = $status; }
        if ($source) { $where[] = 'l.source = ?'; $binds[] = $source; }
        $w = implode(' AND ', $where);
        $order = $this->order('leads', $ctx, 'l.created_at DESC');
        $rows = DB::select(
            "SELECT l.*, u.name AS owner_name, u.avatar_color AS owner_color FROM leads l LEFT JOIN users u ON u.id = l.owner_id
              WHERE {$w} ORDER BY {$order} LIMIT ? OFFSET ?",
            [...$binds, $limit, $offset]
        );
        $total = DB::selectOne("SELECT COUNT(*) AS c FROM leads l WHERE {$w}", $binds);
        $stats = DB::selectOne(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN status IN ('new','contacted','qualified') THEN 1 ELSE 0 END) AS active,
                    SUM(CASE WHEN status='qualified' THEN 1 ELSE 0 END) AS qualified,
                    SUM(CASE WHEN status='converted' THEN 1 ELSE 0 END) AS converted,
                    SUM(CASE WHEN status='disqualified' THEN 1 ELSE 0 END) AS disqualified,
                    COALESCE(SUM(CASE WHEN status IN ('new','contacted','qualified') THEN value END),0) AS pipeline_value
               FROM leads WHERE tenant_id = ?",
            [$t]
        );
        $bySource = DB::select("SELECT COALESCE(source,'manual') AS source, COUNT(*) AS count FROM leads WHERE tenant_id = ? GROUP BY source ORDER BY count DESC", [$t]);
        return H::json(['data' => $rows, 'total' => (int) ($total->c ?? 0), 'page' => $page, 'limit' => $limit, 'stats' => $stats, 'bySource' => $bySource]);
    }

    public function getLead(Ctx $ctx)
    {
        $ctx->auth();
        $t = $ctx->tenantId;
        $lead = DB::selectOne(
            'SELECT l.*, u.name AS owner_name, u.avatar_color AS owner_color, o.name AS org_name
               FROM leads l LEFT JOIN users u ON u.id = l.owner_id LEFT JOIN organizations o ON o.id = l.org_id
              WHERE l.id = ? AND l.tenant_id = ?',
            [$ctx->params['id'], $t]
        );
        if (!$lead) throw new ApiException(404, 'Лид не найден', 'not_found');
        $activity = DB::select("SELECT id,kind,subject,body,actor_name,created_at FROM activities WHERE tenant_id=? AND entity_type='lead' AND entity_id=? ORDER BY created_at DESC LIMIT 50", [$t, $ctx->params['id']]);
        $tasks = DB::select("SELECT id, title, status, priority, due_date FROM tasks WHERE tenant_id=? AND entity_type='lead' AND entity_id=? ORDER BY created_at DESC", [$t, $ctx->params['id']]);
        return H::json([...(array) $lead, 'activity' => $activity, 'tasks' => $tasks]);
    }

    public function createLead(Ctx $ctx)
    {
        $user = $ctx->auth();
        H::requirePermission($ctx, 'crm.write');
        H::requireTenant($ctx);
        $b = H::body($ctx);
        $id = H::newId('lead');
        $ownerId = H::memberRef($ctx, $b['owner_id'] ?? null, 'mas’ul xodim') ?? $user['id'];
        DB::table('leads')->insert([
            'id' => $id, 'tenant_id' => $ctx->tenantId,
            'title' => H::str($b['title'] ?? null, 'title', true, 250),
            'contact_name' => H::str($b['contact_name'] ?? null, 'contact_name', false, 160),
            'org_id' => H::tenantRef($ctx, $b['org_id'] ?? null, 'organizations', 'kompaniya'),
            'email' => H::str($b['email'] ?? null, 'email', false, 190),
            'phone' => H::str($b['phone'] ?? null, 'phone', false, 40),
            'source' => H::str($b['source'] ?? null, 'source', false, 60) ?? 'manual',
            'status' => H::oneOf($b['status'] ?? null, 'status', ['new', 'contacted', 'qualified', 'converted', 'disqualified'], 'new'),
            'score' => H::num($b['score'] ?? null, 'score', false, 0, 100) ?? 40,
            'value' => H::num($b['value'] ?? null, 'value', false, 0) ?? 0,
            'currency' => H::str($b['currency'] ?? null, 'currency', false, 8) ?? 'UZS',
            'owner_id' => $ownerId,
            'notes' => H::str($b['notes'] ?? null, 'notes', false, 2000),
            'created_at' => H::now(), 'updated_at' => H::now(),
        ]);
        DB::table('activities')->insert([
            'id' => H::newId('act'), 'tenant_id' => $ctx->tenantId, 'kind' => 'system',
            'subject' => 'Создан лид «' . ($b['title'] ?? '') . '»', 'entity_type' => 'lead', 'entity_id' => $id,
            'actor_id' => $user['id'], 'actor_name' => $user['name'], 'created_at' => H::now(),
        ]);
        H::audit($ctx, 'lead.created', 'lead', $id);
        (new NotificationDispatcher())->notifyMember($ctx->tenantId, $ownerId, 'info', 'Yangi lid', (string) $b['title'], '/app/leads');
        return H::json(['id' => $id], 201);
    }

    public function updateLead(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'crm.write');
        $lead = DB::selectOne('SELECT title, owner_id FROM leads WHERE id = ? AND tenant_id = ?', [$ctx->params['id'], $ctx->tenantId]);
        if (!$lead) throw new ApiException(404, 'Лид не найден', 'not_found');
        $b = H::body($ctx);
        $owner = H::memberRef($ctx, $b['owner_id'] ?? null, 'mas’ul xodim');
        DB::update(
            'UPDATE leads SET title=COALESCE(?,title), contact_name=?, email=?, phone=?, source=?, status=COALESCE(?,status), score=COALESCE(?,score), value=COALESCE(?,value), owner_id=?, notes=?, updated_at=? WHERE id=? AND tenant_id=?',
            [
                H::str($b['title'] ?? null, 'title', false, 250),
                H::str($b['contact_name'] ?? null, 'contact_name', false, 160),
                H::str($b['email'] ?? null, 'email', false, 190),
                H::str($b['phone'] ?? null, 'phone', false, 40),
                H::str($b['source'] ?? null, 'source', false, 60),
                $b['status'] ?? null,
                H::num($b['score'] ?? null, 'score', false, 0, 100),
                H::num($b['value'] ?? null, 'value', false, 0),
                $owner,
                H::str($b['notes'] ?? null, 'notes', false, 2000),
                H::now(), $ctx->params['id'], $ctx->tenantId,
            ]
        );
        H::audit($ctx, 'lead.updated', 'lead', $ctx->params['id']);
        if ($owner && $owner !== $lead->owner_id) {
            (new NotificationDispatcher())->notifyMember($ctx->tenantId, $owner, 'info', 'Lid sizga biriktirildi', (string) $lead->title, '/app/leads');
        }
        return H::json(['ok' => true]);
    }

    public function disqualifyLead(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'crm.write');
        if (!$this->exists($ctx, 'leads', $ctx->params['id'])) throw new ApiException(404, 'Лид не найден', 'not_found');
        DB::update("UPDATE leads SET status='disqualified', updated_at=? WHERE id=? AND tenant_id=?", [H::now(), $ctx->params['id'], $ctx->tenantId]);
        H::audit($ctx, 'lead.disqualified', 'lead', $ctx->params['id']);
        return H::json(['ok' => true]);
    }

    /** Lead → deal conversion. All rows change in one transaction so it is atomic. */
    public function convertLead(Ctx $ctx)
    {
        $user = $ctx->auth();
        H::requirePermission($ctx, 'deals.write');
        $t = $ctx->tenantId;
        $lead = DB::selectOne('SELECT * FROM leads WHERE id = ? AND tenant_id = ?', [$ctx->params['id'], $t]);
        if (!$lead) throw new ApiException(404, 'Лид не найден', 'not_found');
        $b = H::body($ctx);
        $stage = DB::selectOne('SELECT id, probability, pipeline_id FROM pipeline_stages WHERE tenant_id = ? ORDER BY position LIMIT 1', [$t]);
        if (!$stage) throw new ApiException(404, 'Воронка не настроена', 'not_found');
        $dealId = H::newId('deal');
        DB::transaction(function () use ($ctx, $t, $lead, $b, $stage, $dealId, $user) {
            DB::table('deals')->insert([
                'id' => $dealId, 'tenant_id' => $t,
                'title' => H::str($b['title'] ?? null, 'title', false, 250) ?? $lead->title,
                'org_id' => $lead->org_id,
                'amount' => H::num($b['amount'] ?? null, 'amount', false, 0) ?? $lead->value,
                'currency' => $lead->currency, 'probability' => $stage->probability,
                'pipeline_id' => $stage->pipeline_id, 'stage_id' => $stage->id, 'status' => 'open',
                'source' => $lead->source, 'owner_id' => $user['id'],
                'expected_close_at' => gmdate('Y-m-d', time() + 30 * 86400),
                'created_at' => H::now(), 'updated_at' => H::now(),
            ]);
            DB::update("UPDATE leads SET status='converted', converted_deal_id=?, updated_at=? WHERE id=?", [$dealId, H::now(), $lead->id]);
            DB::table('activities')->insert([
                'id' => H::newId('act'), 'tenant_id' => $t, 'kind' => 'system',
                'subject' => 'Лид конвертирован в сделку', 'entity_type' => 'deal', 'entity_id' => $dealId,
                'actor_id' => $user['id'], 'actor_name' => $user['name'], 'created_at' => H::now(),
            ]);
        });
        H::audit($ctx, 'lead.converted', 'lead', $lead->id, ['dealId' => $dealId]);
        (new NotificationDispatcher())->notifyMember($t, $user['id'], 'success', 'Lid bitimga aylantirildi', (string) $lead->title, '/app/deals');
        return H::json(['dealId' => $dealId], 201);
    }

    // ── Deals & pipeline board ───────────────────────────────────────────────
    public function pipeline(Ctx $ctx)
    {
        $ctx->auth();
        $t = $ctx->tenantId;
        $pipeline = DB::selectOne('SELECT * FROM pipelines WHERE tenant_id = ? ORDER BY is_default DESC LIMIT 1', [$t]);
        if (!$pipeline) return H::json(['pipeline' => null, 'stages' => []]);
        $stages = DB::select('SELECT * FROM pipeline_stages WHERE pipeline_id = ? ORDER BY position', [$pipeline->id]);
        $deals = DB::select(
            "SELECT d.id, d.title, d.amount, d.currency, d.probability, d.status, d.stage_id, d.expected_close_at, d.created_at, d.is_ai_sourced,
                    o.name AS org_name, u.name AS owner_name, u.avatar_color AS owner_color
               FROM deals d LEFT JOIN organizations o ON o.id = d.org_id LEFT JOIN users u ON u.id = d.owner_id
              WHERE d.tenant_id = ? AND d.status = 'open' ORDER BY d.amount DESC LIMIT 400",
            [$t]
        );
        $board = array_map(function ($s) use ($deals) {
            $items = array_values(array_filter($deals, fn ($d) => $d->stage_id === $s->id));
            return [...(array) $s, 'deals' => $items, 'total' => array_sum(array_map(fn ($d) => $d->amount, $items)), 'count' => count($items)];
        }, $stages);
        return H::json(['pipeline' => $pipeline, 'stages' => $board]);
    }

    public function listDeals(Ctx $ctx)
    {
        $ctx->auth();
        $t = $ctx->tenantId;
        [$limit, $page, $offset] = H::paginate($ctx);
        $q = $ctx->query('q'); $status = $ctx->query('status');
        $stage = $ctx->query('stage_id'); $owner = $ctx->query('owner_id');
        $where = ['d.tenant_id = ?']; $binds = [$t];
        if ($q) { $where[] = '(d.title LIKE ? OR o.name LIKE ?)'; array_push($binds, "%{$q}%", "%{$q}%"); }
        if ($status) { $where[] = 'd.status = ?'; $binds[] = $status; }
        if ($stage) { $where[] = 'd.stage_id = ?'; $binds[] = $stage; }
        if ($owner) { $where[] = 'd.owner_id = ?'; $binds[] = $owner; }
        $w = implode(' AND ', $where);
        $order = $this->order('deals', $ctx, 'd.created_at DESC');
        $rows = DB::select(
            "SELECT d.*, o.name AS org_name, u.name AS owner_name, u.avatar_color AS owner_color, s.name AS stage_name, s.color AS stage_color
               FROM deals d LEFT JOIN organizations o ON o.id=d.org_id LEFT JOIN users u ON u.id=d.owner_id LEFT JOIN pipeline_stages s ON s.id=d.stage_id
              WHERE {$w} ORDER BY {$order} LIMIT ? OFFSET ?",
            [...$binds, $limit, $offset]
        );
        $total = DB::selectOne("SELECT COUNT(*) AS c FROM deals d LEFT JOIN organizations o ON o.id=d.org_id WHERE {$w}", $binds);
        return H::json(['data' => $rows, 'total' => (int) ($total->c ?? 0), 'page' => $page, 'limit' => $limit]);
    }

    public function getDeal(Ctx $ctx)
    {
        $ctx->auth();
        $t = $ctx->tenantId;
        $deal = DB::selectOne(
            "SELECT d.*, o.name AS org_name, c.first_name || ' ' || COALESCE(c.last_name,'') AS contact_name, u.name AS owner_name, s.name AS stage_name, s.color AS stage_color
               FROM deals d LEFT JOIN organizations o ON o.id=d.org_id LEFT JOIN contacts c ON c.id=d.contact_id
               LEFT JOIN users u ON u.id=d.owner_id LEFT JOIN pipeline_stages s ON s.id=d.stage_id
              WHERE d.id = ? AND d.tenant_id = ?",
            [$ctx->params['id'], $t]
        );
        if (!$deal) throw new ApiException(404, 'Сделка не найдена', 'not_found');
        $activity = DB::select("SELECT id,kind,subject,body,actor_name,created_at FROM activities WHERE tenant_id=? AND entity_type='deal' AND entity_id=? ORDER BY created_at DESC LIMIT 25", [$t, $ctx->params['id']]);
        $orders = DB::select('SELECT id, number, amount, status, payment_status FROM orders WHERE deal_id = ?', [$ctx->params['id']]);
        $tasks = DB::select("SELECT id, title, status, priority, due_date FROM tasks WHERE tenant_id=? AND entity_type='deal' AND entity_id=?", [$t, $ctx->params['id']]);
        return H::json([...(array) $deal, 'activity' => $activity, 'orders' => $orders, 'tasks' => $tasks]);
    }

    public function createDeal(Ctx $ctx)
    {
        $user = $ctx->auth();
        H::requirePermission($ctx, 'deals.write');
        H::requireTenant($ctx);
        $t = $ctx->tenantId;
        $b = H::body($ctx);
        $ownerId = H::memberRef($ctx, $b['owner_id'] ?? null, 'mas’ul xodim') ?? $user['id'];
        $stageId = H::tenantRef($ctx, $b['stage_id'] ?? null, 'pipeline_stages', 'bosqich');
        if (!$stageId) {
            $first = DB::selectOne('SELECT id FROM pipeline_stages WHERE tenant_id = ? ORDER BY position LIMIT 1', [$t]);
            $stageId = $first->id ?? null;
        }
        $stage = $stageId ? DB::selectOne('SELECT probability, pipeline_id FROM pipeline_stages WHERE id = ? AND tenant_id = ?', [$stageId, $t]) : null;
        $id = H::newId('deal');
        DB::table('deals')->insert([
            'id' => $id, 'tenant_id' => $t,
            'title' => H::str($b['title'] ?? null, 'title', true, 250),
            'org_id' => H::tenantRef($ctx, $b['org_id'] ?? null, 'organizations', 'kompaniya'),
            'contact_id' => H::tenantRef($ctx, $b['contact_id'] ?? null, 'contacts', 'kontakt'),
            'pipeline_id' => $stage->pipeline_id ?? null, 'stage_id' => $stageId,
            'amount' => H::num($b['amount'] ?? null, 'amount', false, 0) ?? 0,
            'currency' => H::str($b['currency'] ?? null, 'currency', false, 8) ?? 'UZS',
            'probability' => H::num($b['probability'] ?? null, 'probability', false, 0, 100) ?? $stage->probability ?? 10,
            'status' => H::oneOf($b['status'] ?? null, 'status', ['open', 'won', 'lost'], 'open'),
            'source' => H::str($b['source'] ?? null, 'source', false, 60),
            'owner_id' => $ownerId,
            'expected_close_at' => H::str($b['expected_close_at'] ?? null, 'expected_close_at', false, 30),
            'created_at' => H::now(), 'updated_at' => H::now(),
        ]);
        DB::table('activities')->insert([
            'id' => H::newId('act'), 'tenant_id' => $t, 'kind' => 'system', 'subject' => 'Сделка создана',
            'entity_type' => 'deal', 'entity_id' => $id, 'actor_id' => $user['id'],
            'actor_name' => $user['name'], 'created_at' => H::now(),
        ]);
        H::audit($ctx, 'deal.created', 'deal', $id, ['title' => $b['title'] ?? null]);
        (new NotificationDispatcher())->notifyMember($t, $ownerId, 'info', 'Yangi bitim', (string) $b['title'], '/app/deals');
        return H::json(['id' => $id], 201);
    }

    /** Stage transitions open or close the deal according to the target stage kind. */
    public function moveDeal(Ctx $ctx)
    {
        $user = $ctx->auth();
        H::requirePermission($ctx, 'deals.write');
        $t = $ctx->tenantId;
        $b = H::body($ctx);
        $stageId = H::str($b['stage_id'] ?? null, 'stage_id');
        $deal = DB::selectOne('SELECT id, title, status, owner_id FROM deals WHERE id = ? AND tenant_id = ?', [$ctx->params['id'], $t]);
        if (!$deal) throw new ApiException(404, 'Сделка не найдена', 'not_found');
        $stage = DB::selectOne('SELECT id, name, probability, kind FROM pipeline_stages WHERE id = ? AND tenant_id = ?', [$stageId, $t]);
        if (!$stage) throw new ApiException(404, 'Стадия не найдена', 'not_found');

        $status = $stage->kind === 'won' ? 'won' : ($stage->kind === 'lost' ? 'lost' : 'open');
        DB::update(
            "UPDATE deals SET stage_id=?, probability=?, status=?,
                won_at = CASE WHEN ?='won' THEN ? ELSE won_at END,
                lost_at = CASE WHEN ?='lost' THEN ? ELSE lost_at END, updated_at=? WHERE id=? AND tenant_id=?",
            [$stageId, $stage->probability, $status, $status, H::now(), $status, H::now(), H::now(), $ctx->params['id'], $t]
        );
        DB::table('activities')->insert([
            'id' => H::newId('act'), 'tenant_id' => $t, 'kind' => 'stage_change',
            'subject' => "Стадия изменена на «{$stage->name}»", 'entity_type' => 'deal',
            'entity_id' => $ctx->params['id'], 'actor_id' => $user['id'],
            'actor_name' => $user['name'], 'created_at' => H::now(),
        ]);
        H::audit($ctx, 'deal.stage_changed', 'deal', $ctx->params['id'], ['to' => $stage->name, 'status' => $status]);
        if ($status !== 'open' && $status !== $deal->status) {
            $won = $status === 'won';
            (new NotificationDispatcher())->notifyMember($t, $deal->owner_id, $won ? 'success' : 'warning', $won ? 'Bitim yutildi' : 'Bitim yo‘qotildi', (string) $deal->title, '/app/deals');
        } elseif ($deal->owner_id !== $user['id']) {
            (new NotificationDispatcher())->notifyMember($t, $deal->owner_id, 'info', 'Bitim bosqichi o‘zgardi', "{$deal->title}: {$stage->name}", '/app/deals');
        }
        return H::json(['ok' => true, 'status' => $status, 'stage' => $stage->name]);
    }

    public function updateDeal(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'deals.write');
        if (!$this->exists($ctx, 'deals', $ctx->params['id'])) throw new ApiException(404, 'Сделка не найдена', 'not_found');
        $b = H::body($ctx);
        DB::update(
            'UPDATE deals SET title=COALESCE(?,title), org_id=?, contact_id=?, amount=COALESCE(?,amount), currency=COALESCE(?,currency), probability=COALESCE(?,probability), owner_id=?, expected_close_at=?, lost_reason=?, updated_at=? WHERE id=? AND tenant_id=?',
            [
                H::str($b['title'] ?? null, 'title', false, 250),
                H::tenantRef($ctx, $b['org_id'] ?? null, 'organizations', 'kompaniya'),
                H::tenantRef($ctx, $b['contact_id'] ?? null, 'contacts', 'kontakt'),
                H::num($b['amount'] ?? null, 'amount', false, 0),
                H::str($b['currency'] ?? null, 'currency', false, 8),
                H::num($b['probability'] ?? null, 'probability', false, 0, 100),
                H::memberRef($ctx, $b['owner_id'] ?? null, 'mas’ul xodim'),
                H::str($b['expected_close_at'] ?? null, 'expected_close_at', false, 30),
                H::str($b['lost_reason'] ?? null, 'lost_reason', false, 200),
                H::now(), $ctx->params['id'], $ctx->tenantId,
            ]
        );
        H::audit($ctx, 'deal.updated', 'deal', $ctx->params['id']);
        return H::json(['ok' => true]);
    }

    public function deleteDeal(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'crm.delete');
        if (!$this->exists($ctx, 'deals', $ctx->params['id'])) throw new ApiException(404, 'Сделка не найдена', 'not_found');
        DB::delete('DELETE FROM deals WHERE id = ? AND tenant_id = ?', [$ctx->params['id'], $ctx->tenantId]);
        H::audit($ctx, 'deal.deleted', 'deal', $ctx->params['id']);
        return H::json(['ok' => true]);
    }

    public function addActivity(Ctx $ctx)
    {
        $user = $ctx->auth();
        H::requirePermission($ctx, 'crm.write');
        $b = H::body($ctx);
        $id = H::newId('act');
        DB::table('activities')->insert([
            'id' => $id, 'tenant_id' => $ctx->tenantId,
            'kind' => H::oneOf($b['kind'] ?? null, 'kind', ['call', 'email', 'note', 'meeting', 'task', 'system'], 'note'),
            'subject' => H::str($b['subject'] ?? null, 'subject', true, 250),
            'body' => H::str($b['body'] ?? null, 'body', false, 4000),
            'entity_type' => H::str($b['entity_type'] ?? null, 'entity_type', false, 40),
            'entity_id' => H::str($b['entity_id'] ?? null, 'entity_id', false, 60),
            'actor_id' => $user['id'], 'actor_name' => $user['name'], 'created_at' => H::now(),
        ]);
        return H::json(['id' => $id], 201);
    }

    /** CSV export. Every export is audited, including the row count. */
    public function exportCsv(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'export.data');
        $t = $ctx->tenantId;
        $kind = $ctx->query('kind') ?: 'deals';
        $cfg = [
            'deals' => ['SELECT title, amount, currency, probability, status, created_at FROM deals WHERE tenant_id = ? ORDER BY created_at DESC', ['Название', 'Сумма', 'Валюта', 'Вероятность', 'Статус', 'Создано']],
            'leads' => ['SELECT title, contact_name, email, phone, source, status, score, created_at FROM leads WHERE tenant_id = ? ORDER BY created_at DESC', ['Название', 'Контакт', 'Email', 'Телефон', 'Источник', 'Статус', 'Скор', 'Создано']],
            'organizations' => ['SELECT name, industry, email, phone, city, status, tier FROM organizations WHERE tenant_id = ? ORDER BY name', ['Название', 'Отрасль', 'Email', 'Телефон', 'Город', 'Статус', 'Уровень']],
            'contacts' => ['SELECT first_name, last_name, position, email, phone, status FROM contacts WHERE tenant_id = ? ORDER BY first_name', ['Имя', 'Фамилия', 'Должность', 'Email', 'Телефон', 'Статус']],
            'orders' => ['SELECT number, amount, currency, status, payment_status, created_at FROM orders WHERE tenant_id = ? ORDER BY created_at DESC', ['Номер', 'Сумма', 'Валюта', 'Статус', 'Оплата', 'Создан']],
        ];
        if (!isset($cfg[$kind])) throw new ApiException(404, 'Неизвестный тип экспорта', 'not_found');
        [$sql, $headers] = $cfg[$kind];
        $rows = DB::select($sql, [$t]);
        $esc = fn ($v) => '"' . str_replace('"', '""', (string) ($v ?? '')) . '"';
        $lines = [implode(',', array_map($esc, $headers))];
        foreach ($rows as $r) {
            $lines[] = implode(',', array_map($esc, array_values((array) $r)));
        }
        $csv = "\u{FEFF}" . implode("\n", $lines);
        H::audit($ctx, 'export.csv', $kind, null, ['rows' => count($rows)]);
        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"nexio-{$kind}.csv\"",
            'Cache-Control' => 'no-store',
        ]);
    }
}
