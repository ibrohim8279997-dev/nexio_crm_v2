<?php
namespace App\Http\Controllers\Api\V1;

use App\Services\NotificationDispatcher;
use App\Services\WebhookService;
use App\Support\ApiException;
use App\Support\Ctx;
use App\Support\H;
use Illuminate\Support\Facades\DB;

class OpsController
{
    private const ORDER_STATES = ['draft', 'confirmed', 'in_progress', 'fulfilled', 'cancelled', 'refunded'];
    private const PAY_STATES = ['unpaid', 'partial', 'paid', 'overdue'];
    private const PROJECT_STATES = ['planning', 'active', 'on_hold', 'review', 'done', 'cancelled'];
    private const TASK_STATES = ['todo', 'in_progress', 'review', 'done'];
    private const PRIORITIES = ['low', 'medium', 'high', 'critical'];

    private function owned(Ctx $ctx, string $table, string $id): bool
    {
        return (bool) DB::selectOne("SELECT 1 AS x FROM {$table} WHERE id = ? AND tenant_id = ?", [$id, $ctx->tenantId]);
    }

    // ── Orders ───────────────────────────────────────────────────────────────
    public function listOrders(Ctx $ctx)
    {
        $ctx->auth();
        $t = $ctx->tenantId;
        [$limit, $page, $offset] = H::paginate($ctx);
        $status = $ctx->query('status'); $pay = $ctx->query('payment_status'); $q = $ctx->query('q');
        $where = ['o.tenant_id = ?']; $binds = [$t];
        if ($status) { $where[] = 'o.status = ?'; $binds[] = $status; }
        if ($pay) { $where[] = 'o.payment_status = ?'; $binds[] = $pay; }
        if ($q) { $where[] = '(o.number LIKE ? OR org.name LIKE ?)'; array_push($binds, "%{$q}%", "%{$q}%"); }
        $w = implode(' AND ', $where);
        $rows = DB::select(
            "SELECT o.*, org.name AS org_name, u.name AS created_by_name,
                    (SELECT COUNT(*) FROM order_items i WHERE i.order_id = o.id) AS items_count
               FROM orders o LEFT JOIN organizations org ON org.id = o.org_id LEFT JOIN users u ON u.id = o.created_by
              WHERE {$w} ORDER BY o.created_at DESC LIMIT ? OFFSET ?",
            [...$binds, $limit, $offset]
        );
        $total = DB::selectOne("SELECT COUNT(*) AS c FROM orders o LEFT JOIN organizations org ON org.id=o.org_id WHERE {$w}", $binds);
        $stats = DB::selectOne(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(CASE WHEN status='draft' THEN 1 ELSE 0 END),0) AS draft,
                    COALESCE(SUM(CASE WHEN status='confirmed' THEN 1 ELSE 0 END),0) AS confirmed,
                    COALESCE(SUM(CASE WHEN status='in_progress' THEN 1 ELSE 0 END),0) AS in_progress,
                    COALESCE(SUM(CASE WHEN status='fulfilled' THEN 1 ELSE 0 END),0) AS fulfilled,
                    COALESCE(SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END),0) AS cancelled,
                    COALESCE(SUM(CASE WHEN status='fulfilled' THEN amount END),0) AS fulfilled_value,
                    COALESCE(SUM(CASE WHEN status IN ('confirmed','in_progress','fulfilled')
                                       AND payment_status IN ('unpaid','overdue','partial') THEN amount END),0) AS receivable
               FROM orders WHERE tenant_id = ?",
            [$t]
        );
        return H::json(['data' => $rows, 'total' => (int) ($total->c ?? 0), 'page' => $page, 'limit' => $limit, 'stats' => $stats]);
    }

    public function getOrder(Ctx $ctx)
    {
        $ctx->auth();
        $t = $ctx->tenantId;
        $order = DB::selectOne(
            "SELECT o.*, org.name AS org_name, org.email AS org_email, u.name AS created_by_name
               FROM orders o LEFT JOIN organizations org ON org.id=o.org_id LEFT JOIN users u ON u.id=o.created_by
              WHERE o.id = ? AND o.tenant_id = ?",
            [$ctx->params['id'], $t]
        );
        if (!$order) throw new ApiException(404, 'Заказ не найден', 'not_found');
        $items = DB::select('SELECT * FROM order_items WHERE order_id = ?', [$ctx->params['id']]);
        $deal = $order->deal_id ? DB::selectOne('SELECT id, title, status FROM deals WHERE id = ?', [$order->deal_id]) : null;
        $project = DB::selectOne('SELECT id, name, status, progress FROM projects WHERE order_id = ?', [$ctx->params['id']]);
        return H::json([...(array) $order, 'snapshot' => $order->snapshot ? H::parseJson($order->snapshot, null) : null, 'items' => $items, 'deal' => $deal, 'project' => $project]);
    }

    public function createOrder(Ctx $ctx)
    {
        $user = $ctx->auth();
        H::requirePermission($ctx, 'orders.write');
        H::requireTenant($ctx);
        $t = $ctx->tenantId;
        $b = H::body($ctx);
        $id = H::newId('ord');
        $year = (int) gmdate('Y');
        $countRow = DB::selectOne('SELECT COUNT(*) AS c FROM orders WHERE tenant_id = ?', [$t]);
        $number = sprintf('ORD-%d-%s', $year, substr((string) (1000 + (int) ($countRow->c ?? 0) + 1), -4));

        $rawItems = is_array($b['items'] ?? null) ? $b['items'] : [];
        $items = [];
        foreach ($rawItems as $it) {
            $prod = !empty($it['product_id'])
                ? DB::selectOne('SELECT id, name, price FROM products WHERE id = ? AND tenant_id = ?', [$it['product_id'], $t])
                : null;
            $qty = H::num($it['qty'] ?? null, 'qty', false, 0) ?? 1;
            $price = H::num($it['price'] ?? null, 'price', false, 0) ?? $prod->price ?? 0;
            $items[] = [
                'product_id' => $prod->id ?? null,
                'name' => $prod->name ?? (string) ($it['name'] ?? 'Позиция'),
                'qty' => $qty, 'price' => $price, 'total' => $qty * $price,
            ];
        }
        if ($items === []) {
            throw new ApiException(400, 'Заказ должен содержать хотя бы одну позицию', 'bad_request');
        }
        $amount = array_sum(array_column($items, 'total'));

        DB::table('orders')->insert([
            'id' => $id, 'tenant_id' => $t, 'number' => $number,
            'org_id' => H::tenantRef($ctx, $b['org_id'] ?? null, 'organizations', 'kompaniya'),
            'contact_id' => H::tenantRef($ctx, $b['contact_id'] ?? null, 'contacts', 'kontakt'),
            'deal_id' => H::tenantRef($ctx, $b['deal_id'] ?? null, 'deals', 'bitim'),
            'status' => H::oneOf($b['status'] ?? null, 'status', self::ORDER_STATES, 'draft'),
            'amount' => $amount,
            'currency' => H::str($b['currency'] ?? null, 'currency', false, 8) ?? 'UZS',
            'source' => H::oneOf($b['source'] ?? null, 'source', ['manual', 'integration', 'portal', 'ai_agent'], 'manual'),
            'payment_status' => H::oneOf($b['payment_status'] ?? null, 'payment_status', self::PAY_STATES, 'unpaid'),
            'notes' => H::str($b['notes'] ?? null, 'notes', false, 2000),
            'created_by' => $user['id'], 'created_at' => H::now(), 'updated_at' => H::now(),
        ]);
        foreach ($items as $i) {
            DB::table('order_items')->insert([
                'id' => H::newId('oit'), 'tenant_id' => $t, 'order_id' => $id,
                'product_id' => $i['product_id'], 'name' => $i['name'],
                'qty' => $i['qty'], 'price' => $i['price'], 'total' => $i['total'],
            ]);
        }
        H::audit($ctx, 'order.created', 'order', $id, ['number' => $number, 'amount' => $amount]);
        (new NotificationDispatcher())->notifyAdmins($t, 'info', 'Yangi buyurtma', "{$number}: {$amount}", '/app/orders');
        return H::json(['id' => $id, 'number' => $number, 'amount' => $amount], 201);
    }

    /** Confirmation freezes the commercial terms into a snapshot. */
    public function confirmOrder(Ctx $ctx)
    {
        $user = $ctx->auth();
        H::requirePermission($ctx, 'orders.write');
        $t = $ctx->tenantId;
        $order = DB::selectOne('SELECT id, number, status, amount, currency, created_by FROM orders WHERE id = ? AND tenant_id = ?', [$ctx->params['id'], $t]);
        if (!$order) throw new ApiException(404, 'Заказ не найден', 'not_found');
        if ($order->status !== 'draft') throw new ApiException(400, 'Подтвердить можно только черновик заказа', 'bad_request');
        $items = DB::select('SELECT name, qty, price, total FROM order_items WHERE order_id = ?', [$ctx->params['id']]);
        if ($items === []) throw new ApiException(400, 'Нельзя подтвердить заказ без позиций', 'bad_request');
        $snapshot = ['terms' => 'Оплата 50% предоплата, 50% по факту', 'currency' => $order->currency, 'tax_rate' => 12, 'captured_at' => H::now(), 'items' => $items];
        DB::transaction(function () use ($ctx, $t, $snapshot, $user) {
            DB::update("UPDATE orders SET status='confirmed', confirmed_at=?, snapshot=?, updated_at=? WHERE id=? AND tenant_id=?", [H::now(), json_encode($snapshot, JSON_UNESCAPED_UNICODE), H::now(), $ctx->params['id'], $t]);
            DB::table('activities')->insert([
                'id' => H::newId('act'), 'tenant_id' => $t, 'kind' => 'system',
                'subject' => 'Заказ подтверждён, условия зафиксированы', 'entity_type' => 'order',
                'entity_id' => $ctx->params['id'], 'actor_id' => $user['id'], 'actor_name' => $user['name'], 'created_at' => H::now(),
            ]);
        });
        H::audit($ctx, 'order.confirmed', 'order', $ctx->params['id'], ['amount' => $order->amount]);
        if ($order->created_by !== $user['id']) {
            (new NotificationDispatcher())->notifyMember($t, $order->created_by, 'success', 'Buyurtma tasdiqlandi', (string) $order->number, '/app/orders');
        }
        WebhookService::dispatch((string) $t, 'order.confirmed', ['order_id' => $order->id, 'number' => $order->number, 'amount' => (float) $order->amount, 'currency' => $order->currency]);
        return H::json(['ok' => true, 'snapshot' => $snapshot]);
    }

    public function updateOrderStatus(Ctx $ctx)
    {
        $user = $ctx->auth();
        H::requirePermission($ctx, 'orders.write');
        $order = DB::selectOne('SELECT number, created_by FROM orders WHERE id = ? AND tenant_id = ?', [$ctx->params['id'], $ctx->tenantId]);
        if (!$order) throw new ApiException(404, 'Заказ не найден', 'not_found');
        $b = H::body($ctx);
        $status = H::oneOf($b['status'] ?? null, 'status', self::ORDER_STATES);
        $pay = !empty($b['payment_status']) ? H::oneOf($b['payment_status'], 'payment_status', self::PAY_STATES) : null;
        DB::update('UPDATE orders SET status=?, payment_status=COALESCE(?,payment_status), updated_at=? WHERE id=? AND tenant_id=?', [$status, $pay, H::now(), $ctx->params['id'], $ctx->tenantId]);
        H::audit($ctx, 'order.status_changed', 'order', $ctx->params['id'], ['status' => $status, 'payment_status' => $pay]);
        if ($order->created_by !== $user['id']) {
            (new NotificationDispatcher())->notifyMember($ctx->tenantId, $order->created_by, 'info', 'Buyurtma holati o‘zgardi', "{$order->number}: {$status}", '/app/orders');
        }
        return H::json(['ok' => true]);
    }

    /** Order → project. Idempotent: an existing project for the order is returned. */
    public function createProjectFromOrder(Ctx $ctx)
    {
        $user = $ctx->auth();
        H::requirePermission($ctx, 'projects.write');
        $t = $ctx->tenantId;
        $order = DB::selectOne('SELECT * FROM orders WHERE id = ? AND tenant_id = ?', [$ctx->params['id'], $t]);
        if (!$order) throw new ApiException(404, 'Заказ не найден', 'not_found');
        $existing = DB::selectOne('SELECT id, name FROM projects WHERE order_id = ?', [$ctx->params['id']]);
        if ($existing) return H::json(['id' => $existing->id, 'name' => $existing->name, 'existing' => true]);
        $id = H::newId('prj');
        DB::table('projects')->insert([
            'id' => $id, 'tenant_id' => $t, 'name' => "Проект по заказу {$order->number}",
            'org_id' => $order->org_id, 'order_id' => $order->id, 'status' => 'planning',
            'priority' => 'medium', 'progress' => 0, 'budget' => $order->amount, 'currency' => $order->currency,
            'manager_id' => $user['id'], 'start_date' => gmdate('Y-m-d'),
            'due_date' => gmdate('Y-m-d', time() + 60 * 86400),
            'description' => "Реализация заказа {$order->number}.", 'created_at' => H::now(), 'updated_at' => H::now(),
        ]);
        DB::update('UPDATE orders SET project_id = ?, updated_at = ? WHERE id = ?', [$id, H::now(), $ctx->params['id']]);
        foreach (['Аналитика и ТЗ', 'Дизайн', 'Разработка', 'Тестирование', 'Запуск'] as $i => $name) {
            DB::table('project_milestones')->insert([
                'id' => H::newId('ms'), 'tenant_id' => $t, 'project_id' => $id, 'name' => $name,
                'status' => $i === 0 ? 'in_progress' : 'pending',
                'due_date' => gmdate('Y-m-d', time() + ($i + 1) * 14 * 86400), 'position' => $i,
            ]);
        }
        H::audit($ctx, 'project.created_from_order', 'project', $id, ['orderId' => $ctx->params['id']]);
        (new NotificationDispatcher())->notifyMember($t, $user['id'], 'info', 'Yangi loyiha', "Проект по заказу {$order->number}", "/app/projects/{$id}");
        return H::json(['id' => $id, 'existing' => false], 201);
    }

    // ── Projects ─────────────────────────────────────────────────────────────
    public function listProjects(Ctx $ctx)
    {
        $ctx->auth();
        $t = $ctx->tenantId;
        [$limit, $page, $offset] = H::paginate($ctx);
        $status = $ctx->query('status'); $q = $ctx->query('q');
        $where = ['p.tenant_id = ?']; $binds = [$t];
        if ($status) { $where[] = 'p.status = ?'; $binds[] = $status; }
        if ($q) { $where[] = '(p.name LIKE ? OR org.name LIKE ?)'; array_push($binds, "%{$q}%", "%{$q}%"); }
        $w = implode(' AND ', $where);
        $rows = DB::select(
            "SELECT p.*, org.name AS org_name, u.name AS manager_name, u.avatar_color AS manager_color,
                    (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id) AS tasks_count,
                    (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id AND t.status='done') AS tasks_done,
                    (SELECT COUNT(*) FROM project_milestones m WHERE m.project_id = p.id AND m.status='done') AS milestones_done,
                    (SELECT COUNT(*) FROM project_milestones m WHERE m.project_id = p.id) AS milestones_total
               FROM projects p LEFT JOIN organizations org ON org.id=p.org_id LEFT JOIN users u ON u.id=p.manager_id
              WHERE {$w} ORDER BY p.created_at DESC LIMIT ? OFFSET ?",
            [...$binds, $limit, $offset]
        );
        $total = DB::selectOne("SELECT COUNT(*) AS c FROM projects p LEFT JOIN organizations org ON org.id=p.org_id WHERE {$w}", $binds);
        $stats = DB::selectOne(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(CASE WHEN status='active' THEN 1 ELSE 0 END),0) AS active,
                    COALESCE(SUM(CASE WHEN status='done' THEN 1 ELSE 0 END),0) AS done,
                    COALESCE(SUM(CASE WHEN status='on_hold' THEN 1 ELSE 0 END),0) AS on_hold,
                    COALESCE(SUM(CASE WHEN status='planning' THEN 1 ELSE 0 END),0) AS planning,
                    COALESCE(SUM(budget),0) AS budget,
                    COALESCE(AVG(progress),0) AS avg_progress
               FROM projects WHERE tenant_id = ?",
            [$t]
        );
        $data = array_map(function ($project) {
            $totalItems = (int) $project->tasks_count + (int) $project->milestones_total;
            $completedItems = (int) $project->tasks_done + (int) $project->milestones_done;
            return [
                ...(array) $project,
                'progress' => $totalItems > 0 ? (int) round($completedItems / $totalItems * 100) : (int) $project->progress,
            ];
        }, $rows);
        return H::json(['data' => $data, 'total' => (int) ($total->c ?? 0), 'page' => $page, 'limit' => $limit, 'stats' => $stats]);
    }

    public function getProject(Ctx $ctx)
    {
        $ctx->auth();
        $t = $ctx->tenantId;
        $p = DB::selectOne(
            "SELECT p.*, org.name AS org_name, u.name AS manager_name FROM projects p
               LEFT JOIN organizations org ON org.id=p.org_id LEFT JOIN users u ON u.id=p.manager_id
              WHERE p.id = ? AND p.tenant_id = ?",
            [$ctx->params['id'], $t]
        );
        if (!$p) throw new ApiException(404, 'Проект не найден', 'not_found');
        $milestones = DB::select('SELECT * FROM project_milestones WHERE project_id = ? ORDER BY position', [$ctx->params['id']]);
        $tasks = DB::select('SELECT t.*, u.name AS assignee_name, u.avatar_color AS assignee_color FROM tasks t LEFT JOIN users u ON u.id=t.assignee_id WHERE t.project_id = ? ORDER BY t.due_date', [$ctx->params['id']]);
        $activity = DB::select('SELECT id, kind, subject, actor_name, created_at FROM activities WHERE tenant_id=? AND entity_id=? ORDER BY created_at DESC LIMIT 20', [$t, $ctx->params['id']]);
        $totalItems = count($milestones) + count($tasks);
        $completedItems = count(array_filter($milestones, fn ($milestone) => $milestone->status === 'done'))
            + count(array_filter($tasks, fn ($task) => $task->status === 'done'));
        return H::json([
            ...(array) $p,
            'progress' => $totalItems > 0 ? (int) round($completedItems / $totalItems * 100) : (int) $p->progress,
            'milestones' => $milestones, 'tasks' => $tasks, 'activity' => $activity,
        ]);
    }

    public function createProject(Ctx $ctx)
    {
        $user = $ctx->auth();
        H::requirePermission($ctx, 'projects.write');
        H::requireTenant($ctx);
        $b = H::body($ctx);
        $id = H::newId('prj');
        $managerId = H::memberRef($ctx, $b['manager_id'] ?? null, 'loyiha menejeri') ?? $user['id'];
        DB::table('projects')->insert([
            'id' => $id, 'tenant_id' => $ctx->tenantId,
            'name' => H::str($b['name'] ?? null, 'name', true, 200),
            'org_id' => H::tenantRef($ctx, $b['org_id'] ?? null, 'organizations', 'kompaniya'),
            'order_id' => H::tenantRef($ctx, $b['order_id'] ?? null, 'orders', 'buyurtma'),
            'status' => H::oneOf($b['status'] ?? null, 'status', self::PROJECT_STATES, 'planning'),
            'priority' => H::oneOf($b['priority'] ?? null, 'priority', self::PRIORITIES, 'medium'),
            'progress' => H::num($b['progress'] ?? null, 'progress', false, 0, 100) ?? 0,
            'budget' => H::num($b['budget'] ?? null, 'budget', false, 0) ?? 0,
            'currency' => H::str($b['currency'] ?? null, 'currency', false, 8) ?? 'UZS',
            'manager_id' => $managerId,
            'start_date' => H::str($b['start_date'] ?? null, 'start_date', false, 30),
            'due_date' => H::str($b['due_date'] ?? null, 'due_date', false, 30),
            'description' => H::str($b['description'] ?? null, 'description', false, 4000),
            'created_at' => H::now(), 'updated_at' => H::now(),
        ]);
        H::audit($ctx, 'project.created', 'project', $id, ['name' => $b['name'] ?? null]);
        (new NotificationDispatcher())->notifyMember($ctx->tenantId, $managerId, 'info', 'Yangi loyiha', (string) $b['name'], "/app/projects/{$id}");
        return H::json(['id' => $id], 201);
    }

    public function updateProject(Ctx $ctx)
    {
        $user = $ctx->auth();
        H::requirePermission($ctx, 'projects.write');
        $project = DB::selectOne('SELECT name, status, manager_id FROM projects WHERE id = ? AND tenant_id = ?', [$ctx->params['id'], $ctx->tenantId]);
        if (!$project) throw new ApiException(404, 'Проект не найден', 'not_found');
        $b = H::body($ctx);
        $manager = H::memberRef($ctx, $b['manager_id'] ?? null, 'loyiha menejeri');
        DB::update(
            'UPDATE projects SET name=COALESCE(?,name), status=COALESCE(?,status), priority=COALESCE(?,priority), progress=COALESCE(?,progress), budget=COALESCE(?,budget), manager_id=?, due_date=?, description=?, updated_at=? WHERE id=? AND tenant_id=?',
            [
                H::str($b['name'] ?? null, 'name', false, 200), $b['status'] ?? null, $b['priority'] ?? null,
                H::num($b['progress'] ?? null, 'progress', false, 0, 100),
                H::num($b['budget'] ?? null, 'budget', false, 0),
                $manager,
                H::str($b['due_date'] ?? null, 'due_date', false, 30),
                H::str($b['description'] ?? null, 'description', false, 4000),
                H::now(), $ctx->params['id'], $ctx->tenantId,
            ]
        );
        H::audit($ctx, 'project.updated', 'project', $ctx->params['id']);
        $url = "/app/projects/{$ctx->params['id']}";
        if ($manager && $manager !== $project->manager_id) {
            (new NotificationDispatcher())->notifyMember($ctx->tenantId, $manager, 'info', 'Loyiha sizga biriktirildi', (string) $project->name, $url);
        } elseif (!empty($b['status']) && $b['status'] !== $project->status && $project->manager_id !== $user['id']) {
            (new NotificationDispatcher())->notifyMember($ctx->tenantId, $project->manager_id, 'info', 'Loyiha holati o‘zgardi', "{$project->name}: {$b['status']}", $url);
        }
        return H::json(['ok' => true]);
    }

    public function addMilestone(Ctx $ctx)
    {
        $user = $ctx->auth();
        H::requirePermission($ctx, 'projects.write');
        $project = DB::selectOne('SELECT name, manager_id FROM projects WHERE id = ? AND tenant_id = ?', [$ctx->params['id'], $ctx->tenantId]);
        if (!$project) throw new ApiException(404, 'Проект не найден', 'not_found');
        $b = H::body($ctx);
        $id = H::newId('ms');
        $pos = DB::selectOne('SELECT COALESCE(MAX(position),-1)+1 AS p FROM project_milestones WHERE project_id = ?', [$ctx->params['id']]);
        DB::table('project_milestones')->insert([
            'id' => $id, 'tenant_id' => $ctx->tenantId, 'project_id' => $ctx->params['id'],
            'name' => H::str($b['name'] ?? null, 'name', true, 200),
            'status' => H::oneOf($b['status'] ?? null, 'status', ['pending', 'in_progress', 'done'], 'pending'),
            'due_date' => H::str($b['due_date'] ?? null, 'due_date', false, 30),
            'position' => $pos->p ?? 0,
        ]);
        if ($project->manager_id !== $user['id']) {
            (new NotificationDispatcher())->notifyMember($ctx->tenantId, $project->manager_id, 'info', 'Loyihaga yangi bosqich qo‘shildi', "{$project->name}: {$b['name']}", "/app/projects/{$ctx->params['id']}");
        }
        return H::json(['id' => $id], 201);
    }

    public function setMilestoneStatus(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'projects.write');
        $b = H::body($ctx);
        $status = H::oneOf($b['status'] ?? null, 'status', ['pending', 'in_progress', 'done']);
        DB::update('UPDATE project_milestones SET status=? WHERE id=? AND tenant_id=?', [$status, $ctx->params['id'], $ctx->tenantId]);
        return H::json(['ok' => true]);
    }

    // ── Tasks ────────────────────────────────────────────────────────────────
    public function listTasks(Ctx $ctx)
    {
        $user = $ctx->auth();
        $t = $ctx->tenantId;
        [$limit, $page, $offset] = H::paginate($ctx);
        $status = $ctx->query('status'); $assignee = $ctx->query('assignee_id');
        $projectId = $ctx->query('project_id'); $q = $ctx->query('q'); $scope = $ctx->query('scope');
        $where = ['t.tenant_id = ?']; $binds = [$t];
        if ($status) { $where[] = 't.status = ?'; $binds[] = $status; }
        if ($assignee) { $where[] = 't.assignee_id = ?'; $binds[] = $assignee; }
        if ($projectId) { $where[] = 't.project_id = ?'; $binds[] = $projectId; }
        if ($q) { $where[] = 't.title LIKE ?'; $binds[] = "%{$q}%"; }
        if ($scope === 'mine') { $where[] = 't.assignee_id = ?'; $binds[] = $user['id']; }
        if ($scope === 'overdue') { $where[] = "t.status != 'done' AND t.due_date < CURRENT_DATE"; }
        $w = implode(' AND ', $where);
        $rows = DB::select(
            "SELECT t.*, u.name AS assignee_name, u.avatar_color AS assignee_color, p.name AS project_name
               FROM tasks t LEFT JOIN users u ON u.id=t.assignee_id LEFT JOIN projects p ON p.id=t.project_id
              WHERE {$w}
              ORDER BY CASE t.status WHEN 'in_progress' THEN 0 WHEN 'todo' THEN 1 WHEN 'review' THEN 2 ELSE 3 END,
                       CASE t.priority WHEN 'critical' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END, t.due_date
              LIMIT ? OFFSET ?",
            [...$binds, $limit, $offset]
        );
        $total = DB::selectOne("SELECT COUNT(*) AS c FROM tasks t WHERE {$w}", $binds);
        $stats = DB::selectOne(
            "SELECT COUNT(*) AS total, COALESCE(SUM(CASE WHEN status='todo' THEN 1 ELSE 0 END),0) AS todo,
                    COALESCE(SUM(CASE WHEN status='in_progress' THEN 1 ELSE 0 END),0) AS in_progress,
                    COALESCE(SUM(CASE WHEN status='review' THEN 1 ELSE 0 END),0) AS review,
                    COALESCE(SUM(CASE WHEN status='done' THEN 1 ELSE 0 END),0) AS done,
                    COALESCE(SUM(CASE WHEN status != 'done' AND due_date < CURRENT_DATE THEN 1 ELSE 0 END),0) AS overdue,
                    COALESCE(SUM(CASE WHEN assignee_id = ? AND status != 'done' THEN 1 ELSE 0 END),0) AS mine
               FROM tasks WHERE tenant_id = ?",
            [$user['id'], $t]
        );
        return H::json(['data' => $rows, 'total' => (int) ($total->c ?? 0), 'page' => $page, 'limit' => $limit, 'stats' => $stats]);
    }

    public function createTask(Ctx $ctx)
    {
        $user = $ctx->auth();
        H::requirePermission($ctx, 'tasks.write');
        H::requireTenant($ctx);
        $b = H::body($ctx);
        $id = H::newId('tsk');
        $assigneeId = H::memberRef($ctx, $b['assignee_id'] ?? null, 'ijrochi') ?? $user['id'];
        DB::table('tasks')->insert([
            'id' => $id, 'tenant_id' => $ctx->tenantId,
            'title' => H::str($b['title'] ?? null, 'title', true, 250),
            'description' => H::str($b['description'] ?? null, 'description', false, 4000),
            'project_id' => H::tenantRef($ctx, $b['project_id'] ?? null, 'projects', 'loyiha'),
            'entity_type' => H::str($b['entity_type'] ?? null, 'entity_type', false, 40),
            'entity_id' => H::str($b['entity_id'] ?? null, 'entity_id', false, 60),
            'assignee_id' => $assigneeId,
            'status' => H::oneOf($b['status'] ?? null, 'status', self::TASK_STATES, 'todo'),
            'priority' => H::oneOf($b['priority'] ?? null, 'priority', self::PRIORITIES, 'medium'),
            'due_date' => H::str($b['due_date'] ?? null, 'due_date', false, 30),
            'created_by' => $user['id'], 'created_at' => H::now(), 'updated_at' => H::now(),
        ]);
        H::audit($ctx, 'task.created', 'task', $id);
        (new NotificationDispatcher())->notifyMember($ctx->tenantId, $assigneeId, 'info', 'Yangi vazifa', (string) $b['title'], '/app/tasks');
        return H::json(['id' => $id], 201);
    }

    public function setTaskStatus(Ctx $ctx)
    {
        $user = $ctx->auth();
        H::requirePermission($ctx, 'tasks.write');
        $task = DB::selectOne('SELECT title, created_by FROM tasks WHERE id = ? AND tenant_id = ?', [$ctx->params['id'], $ctx->tenantId]);
        if (!$task) throw new ApiException(404, 'Задача не найдена', 'not_found');
        $b = H::body($ctx);
        $status = H::oneOf($b['status'] ?? null, 'status', self::TASK_STATES);
        // A task cannot start or finish while a task it depends on is still open.
        if ($status !== 'todo') {
            $open = DB::selectOne(
                "SELECT k.title FROM task_dependencies d JOIN tasks k ON k.id = d.depends_on_id
                  WHERE d.tenant_id = ? AND d.task_id = ? AND k.status != 'done' LIMIT 1",
                [$ctx->tenantId, $ctx->params['id']]
            );
            if ($open) throw new ApiException(409, "Сначала завершите задачу «{$open->title}»", 'blocked');
        }
        DB::update('UPDATE tasks SET status=?, completed_at=?, updated_at=? WHERE id=? AND tenant_id=?', [
            $status, $status === 'done' ? H::now() : null, H::now(), $ctx->params['id'], $ctx->tenantId,
        ]);
        if ($status === 'done') {
            DB::table('activities')->insert([
                'id' => H::newId('act'), 'tenant_id' => $ctx->tenantId, 'kind' => 'task',
                'subject' => 'Задача выполнена', 'entity_type' => 'task', 'entity_id' => $ctx->params['id'],
                'actor_id' => $user['id'], 'actor_name' => $user['name'], 'created_at' => H::now(),
            ]);
            if ($task->created_by !== $user['id']) {
                (new NotificationDispatcher())->notifyMember($ctx->tenantId, $task->created_by, 'success', 'Vazifa bajarildi', "{$task->title} — {$user['name']}", '/app/tasks');
            }
        }
        return H::json(['ok' => true]);
    }

    public function updateTask(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'tasks.write');
        $task = DB::selectOne('SELECT title, assignee_id FROM tasks WHERE id = ? AND tenant_id = ?', [$ctx->params['id'], $ctx->tenantId]);
        if (!$task) throw new ApiException(404, 'Задача не найдена', 'not_found');
        $b = H::body($ctx);
        $assignee = H::memberRef($ctx, $b['assignee_id'] ?? null, 'ijrochi');
        DB::update('UPDATE tasks SET title=COALESCE(?,title), description=?, priority=COALESCE(?,priority), due_date=?, assignee_id=?, updated_at=? WHERE id=? AND tenant_id=?', [
            H::str($b['title'] ?? null, 'title', false, 250),
            H::str($b['description'] ?? null, 'description', false, 4000),
            H::oneOf($b['priority'] ?? null, 'priority', self::PRIORITIES, null),
            H::str($b['due_date'] ?? null, 'due_date', false, 30),
            $assignee,
            H::now(), $ctx->params['id'], $ctx->tenantId,
        ]);
        if ($assignee && $assignee !== $task->assignee_id) {
            (new NotificationDispatcher())->notifyMember($ctx->tenantId, $assignee, 'info', 'Vazifa sizga biriktirildi', (string) ($b['title'] ?? $task->title), '/app/tasks');
        }
        return H::json(['ok' => true]);
    }

    public function removeTask(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'tasks.write');
        if (!$this->owned($ctx, 'tasks', $ctx->params['id'])) throw new ApiException(404, 'Задача не найдена', 'not_found');
        DB::delete('DELETE FROM tasks WHERE id=? AND tenant_id=?', [$ctx->params['id'], $ctx->tenantId]);
        return H::json(['ok' => true]);
    }
}
