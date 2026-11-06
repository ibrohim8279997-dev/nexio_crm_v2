<?php
namespace App\Http\Controllers\Api\V1;

use App\Support\Ctx;
use App\Support\H;
use App\Support\SqlDialect;
use Illuminate\Support\Facades\DB;

class DashboardController
{
    /** Resolves a period preset (or explicit from/to) into an inclusive date range. */
    public static function periodRange(Ctx $ctx): array
    {
        $p = $ctx->query('period') ?: '30d';
        $to = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $from = $to;
        $label = 'Последние 30 дней';
        switch ($p) {
            case 'today': $label = 'Сегодня'; break;
            case '7d': $from = $to->modify('-7 days'); $label = 'Последние 7 дней'; break;
            case '30d': $from = $to->modify('-30 days'); $label = 'Последние 30 дней'; break;
            case 'month': $from = $to->setDate((int) $to->format('Y'), (int) $to->format('n'), 1); $label = 'Текущий месяц'; break;
            case 'quarter':
                $q = intdiv((int) $to->format('n') - 1, 3) * 3 + 1;
                $from = $to->setDate((int) $to->format('Y'), $q, 1);
                $label = 'Текущий квартал';
                break;
            case 'year': $from = $to->setDate((int) $to->format('Y'), 1, 1); $label = 'Текущий год'; break;
            case 'custom':
                $f = $ctx->query('from'); $t = $ctx->query('to');
                if ($f && $t) return ['from' => $f, 'to' => $t, 'label' => "{$f} — {$t}"];
                $from = $to->modify('-30 days');
                break;
        }
        return ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d'), 'label' => $label];
    }

    private function q1(string $sql, array $binds = []): array
    {
        $row = DB::selectOne($sql, $binds);
        return $row ? (array) $row : [];
    }

    public function summary(Ctx $ctx)
    {
        $ctx->auth();
        $t = $ctx->tenantId;
        ['from' => $from, 'to' => $to, 'label' => $label] = self::periodRange($ctx);
        $lo = "{$from} 00:00:00"; $hi = "{$to} 23:59:59";

        $revenue = $this->q1("SELECT COALESCE(SUM(amount),0) AS v, COUNT(*) AS c FROM deals WHERE tenant_id = ? AND status = 'won' AND won_at BETWEEN ? AND ?", [$t, $lo, $hi]);
        $activeDeals = $this->q1("SELECT COUNT(*) AS c, COALESCE(SUM(amount),0) AS v FROM deals WHERE tenant_id = ? AND status = 'open'", [$t]);
        $pipeValue = $this->q1("SELECT COALESCE(SUM(amount * probability / 100.0),0) AS v FROM deals WHERE tenant_id = ? AND status = 'open'", [$t]);
        $newLeads = $this->q1('SELECT COUNT(*) AS c FROM leads WHERE tenant_id = ? AND created_at BETWEEN ? AND ?', [$t, $lo, $hi]);
        $activeLeads = $this->q1("SELECT COUNT(*) AS c FROM leads WHERE tenant_id = ? AND status IN ('new','contacted','qualified')", [$t]);
        $customers = $this->q1("SELECT COUNT(*) AS c FROM organizations WHERE tenant_id = ? AND status = 'active'", [$t]);
        $ordersAgg = $this->q1("SELECT COUNT(*) AS c, COALESCE(SUM(amount),0) AS v FROM orders WHERE tenant_id = ? AND status = 'fulfilled' AND created_at BETWEEN ? AND ?", [$t, $lo, $hi]);
        $overdue = $this->q1('SELECT COUNT(*) AS c FROM tasks WHERE tenant_id = ? AND status != ? AND due_date < ?', [$t, 'done', $to]);
        $openTasks = $this->q1("SELECT COUNT(*) AS c FROM tasks WHERE tenant_id = ? AND status != 'done'", [$t]);
        $lost = $this->q1("SELECT COUNT(*) AS c FROM deals WHERE tenant_id = ? AND status = 'lost' AND updated_at BETWEEN ? AND ?", [$t, $lo, $hi]);

        $wonCount = (int) ($revenue['c'] ?? 0);
        $lostCount = (int) ($lost['c'] ?? 0);
        $conversion = ($wonCount + $lostCount) > 0 ? ($wonCount / ($wonCount + $lostCount)) * 100 : 0;
        $arpu = $wonCount > 0 ? ($revenue['v'] ?? 0) / $wonCount : 0;

        $monthly = DB::select(
            "SELECT " . SqlDialect::month('created_at') . " AS ym,
                    COALESCE(SUM(CASE WHEN status='won' THEN amount END),0) AS won,
                    COALESCE(SUM(CASE WHEN status IN ('open','won') THEN amount END),0) AS pipeline,
                    COUNT(*) AS total,
                    SUM(CASE WHEN status='won' THEN 1 ELSE 0 END) AS won_count
               FROM deals WHERE tenant_id = ? AND created_at >= " . SqlDialect::ago(12, 'month') . "
              GROUP BY ym ORDER BY ym",
            [$t]
        );
        $byStage = DB::select(
            "SELECT s.id, s.name, s.color, s.position, COUNT(d.id) AS count, COALESCE(SUM(d.amount),0) AS value
               FROM pipeline_stages s
               LEFT JOIN deals d ON d.stage_id = s.id AND d.status = 'open'
              WHERE s.tenant_id = ? GROUP BY s.id ORDER BY s.position",
            [$t]
        );
        $bySource = DB::select(
            "SELECT COALESCE(source,'unknown') AS source, COUNT(*) AS count, COALESCE(SUM(amount),0) AS value
               FROM deals WHERE tenant_id = ? AND created_at BETWEEN ? AND ? GROUP BY source ORDER BY count DESC",
            [$t, $lo, $hi]
        );
        $byOwner = DB::select(
            "SELECT u.id, u.name, u.avatar_color, COUNT(d.id) AS deals,
                    COALESCE(SUM(CASE WHEN d.status='won' THEN d.amount END),0) AS won_value,
                    SUM(CASE WHEN d.status='won' THEN 1 ELSE 0 END) AS won_count
               FROM users u
               JOIN tenant_memberships m ON m.user_id = u.id AND m.tenant_id = ?
               LEFT JOIN deals d ON d.owner_id = u.id AND d.created_at >= " . SqlDialect::ago(12, 'month') . "
              GROUP BY u.id ORDER BY won_value DESC",
            [$t]
        );
        $topProducts = DB::select(
            "SELECT oi.name, SUM(oi.qty) AS qty, SUM(oi.total) AS revenue
               FROM order_items oi JOIN orders o ON o.id = oi.order_id
              WHERE oi.tenant_id = ? AND o.status = 'fulfilled'
              GROUP BY oi.name ORDER BY revenue DESC LIMIT 6",
            [$t]
        );
        $ai = DB::selectOne(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) AS completed,
                    SUM(CASE WHEN outcome IN ('interested','qualified') THEN 1 ELSE 0 END) AS positive,
                    SUM(CASE WHEN outcome='opted_out' THEN 1 ELSE 0 END) AS opted_out,
                    COALESCE(SUM(duration_sec),0) AS seconds,
                    COALESCE(SUM(cost),0) AS cost,
                    SUM(CASE WHEN order_id IS NOT NULL THEN 1 ELSE 0 END) AS orders
               FROM ai_calls WHERE tenant_id = ? AND created_at BETWEEN ? AND ?",
            [$t, $lo, $hi]
        );
        $aiAgents = DB::select('SELECT id, name, role, status, autonomy, cost_per_minute FROM ai_agents WHERE tenant_id = ? ORDER BY created_at LIMIT 6', [$t]);
        $err = $this->q1("SELECT COUNT(*) AS c FROM ai_calls WHERE tenant_id = ? AND status = 'failed'", [$t]);
        $ai = $ai ? (array) $ai : [];

        return H::json([
            'period' => ['from' => $from, 'to' => $to, 'label' => $label],
            'kpis' => [
                'revenue' => $revenue['v'] ?? 0,
                'revenueDeals' => $wonCount,
                'activeDeals' => $activeDeals['c'] ?? 0,
                'activeDealsValue' => $activeDeals['v'] ?? 0,
                'activeLeads' => $activeLeads['c'] ?? 0,
                'customers' => $customers['c'] ?? 0,
                'newLeads' => $newLeads['c'] ?? 0,
                'conversion' => round($conversion * 10) / 10,
                'averageDeal' => (int) round($arpu),
                'forecast' => (int) round($pipeValue['v'] ?? 0),
                'openTasks' => $openTasks['c'] ?? 0,
                'overdueTasks' => $overdue['c'] ?? 0,
                'fulfilledOrders' => $ordersAgg['c'] ?? 0,
                'fulfilledValue' => $ordersAgg['v'] ?? 0,
            ],
            'monthly' => $monthly, 'byStage' => $byStage, 'bySource' => $bySource,
            'byOwner' => $byOwner, 'topProducts' => $topProducts,
            'ai' => [
                'total' => $ai['total'] ?? 0, 'completed' => $ai['completed'] ?? 0,
                'positive' => $ai['positive'] ?? 0, 'optedOut' => $ai['opted_out'] ?? 0,
                'minutes' => (int) round(($ai['seconds'] ?? 0) / 60),
                'cost' => round(($ai['cost'] ?? 0) * 100) / 100,
                'orders' => $ai['orders'] ?? 0, 'errors' => $err['c'] ?? 0,
                'agents' => $aiAgents,
            ],
        ]);
    }

    /** Work items for the signed-in user: my tasks, recent orders, activity feed. */
    public function workpanel(Ctx $ctx)
    {
        $user = $ctx->auth();
        $t = $ctx->tenantId;
        $tasks = DB::select(
            "SELECT t.id, t.title, t.status, t.priority, t.due_date, p.name AS project_name
               FROM tasks t LEFT JOIN projects p ON p.id = t.project_id
              WHERE t.tenant_id = ? AND t.status != 'done' AND (t.assignee_id = ? OR t.assignee_id IS NULL)
              ORDER BY CASE t.priority WHEN 'critical' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END, t.due_date
              LIMIT 8",
            [$t, $user['id']]
        );
        $orders = DB::select(
            'SELECT o.id, o.number, o.amount, o.currency, o.status, o.payment_status, o.created_at, org.name AS org_name
               FROM orders o LEFT JOIN organizations org ON org.id = o.org_id
              WHERE o.tenant_id = ? ORDER BY o.created_at DESC LIMIT 6',
            [$t]
        );
        $activity = DB::select('SELECT id, kind, subject, actor_name, created_at FROM activities WHERE tenant_id = ? ORDER BY created_at DESC LIMIT 10', [$t]);
        $recentLeads = DB::select('SELECT id, title, status, score, source, created_at FROM leads WHERE tenant_id = ? ORDER BY created_at DESC LIMIT 5', [$t]);
        return H::json(['tasks' => $tasks, 'orders' => $orders, 'activity' => $activity, 'leads' => $recentLeads]);
    }

    /**
     * Authenticated employee's daily workspace. This deliberately composes existing CRM tables
     * instead of introducing a second task or calendar store.
     */
    public function myDay(Ctx $ctx)
    {
        $user = $ctx->auth();
        $tenant = $ctx->tenantId;
        $timezone = (string) (DB::selectOne('SELECT timezone FROM tenants WHERE id = ?', [$tenant])->timezone ?? 'UTC');
        try { $zone = new \DateTimeZone($timezone ?: 'UTC'); } catch (\Throwable) { $zone = new \DateTimeZone('UTC'); $timezone = 'UTC'; }
        $localNow = new \DateTimeImmutable('now', $zone);
        $today = $localNow->format('Y-m-d');
        $dayStart = $localNow->setTime(0, 0, 0)->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        $dayEnd = $localNow->setTime(23, 59, 59)->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');

        $tasks = DB::select(
            "SELECT t.id, t.title, t.description, t.status, t.priority, t.due_date, t.assignee_id,
                    p.name AS project_name
               FROM tasks t LEFT JOIN projects p ON p.id = t.project_id
              WHERE t.tenant_id = ? AND t.status != 'done'
                AND (t.assignee_id = ? OR t.assignee_id IS NULL)
                AND (t.due_date IS NULL OR t.due_date <= ?)
              ORDER BY CASE WHEN t.due_date IS NULL THEN 1 ELSE 0 END, t.due_date,
                       CASE t.priority WHEN 'critical' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END
              LIMIT 50",
            [$tenant, $user['id'], $dayEnd]
        );

        $calls = DB::select(
            "SELECT c.id, c.phone, c.status, c.outcome, c.started_at, c.duration_sec,
                    a.name AS agent_name, ct.first_name, ct.last_name
               FROM ai_calls c
               LEFT JOIN ai_agents a ON a.id = c.agent_id
               LEFT JOIN contacts ct ON ct.id = c.contact_id
              WHERE c.tenant_id = ? AND c.started_at BETWEEN ? AND ?
              ORDER BY c.started_at LIMIT 50",
            [$tenant, $dayStart, $dayEnd]
        );

        $leads = DB::select(
            "SELECT id, title, status, score, source, owner_id, created_at
               FROM leads WHERE tenant_id = ? AND status IN ('new','contacted','qualified')
              ORDER BY CASE status WHEN 'new' THEN 0 WHEN 'qualified' THEN 1 ELSE 2 END, score DESC, created_at DESC
              LIMIT 25",
            [$tenant]
        );

        $deals = DB::select(
            "SELECT d.id, d.title, d.amount, d.currency, d.status, d.expected_close_at,
                    d.owner_id, o.name AS org_name
               FROM deals d LEFT JOIN organizations o ON o.id = d.org_id
              WHERE d.tenant_id = ? AND d.status = 'open'
                AND d.expected_close_at IS NOT NULL AND d.expected_close_at <= ?
              ORDER BY d.expected_close_at LIMIT 25",
            [$tenant, $dayEnd]
        );

        $suggestions = [];
        if (count($tasks) > 0) {
            $suggestions[] = ['type' => 'task', 'title' => 'Bugungi va kechikkan vazifalarni yopish', 'reason' => count($tasks) . ' ta bajarilmagan vazifa bor.', 'target_id' => $tasks[0]->id, 'ai_generated' => false];
        }
        if (count($leads) > 0) {
            $suggestions[] = ['type' => 'lead', 'title' => 'Yangi leadlarga javob berish', 'reason' => count($leads) . ' ta lead e’tibor kutmoqda.', 'target_id' => $leads[0]->id, 'ai_generated' => false];
        }
        if (count($deals) > 0) {
            $suggestions[] = ['type' => 'deal', 'title' => 'Muddati kelgan dealni yangilash', 'reason' => count($deals) . ' ta dealning kutilgan yopilish sanasi o‘tgan.', 'target_id' => $deals[0]->id, 'ai_generated' => false];
        }

        return H::json([
            'date' => $today,
            'tasks' => $tasks,
            'calls' => array_map(fn ($call) => [...(array) $call, 'contact_name' => trim(($call->first_name ?? '') . ' ' . ($call->last_name ?? '')) ?: null], $calls),
            'leads' => $leads,
            'deals' => $deals,
            'suggestions' => $suggestions,
            'timezone' => $timezone,
        ]);
    }

    /** Full analytics view: funnel, reasons, cycle time, managers, cohorts. */
    public function analytics(Ctx $ctx)
    {
        $ctx->auth();
        $t = $ctx->tenantId;
        ['from' => $from, 'to' => $to, 'label' => $label] = self::periodRange($ctx);
        $lo = "{$from} 00:00:00"; $hi = "{$to} 23:59:59";

        $funnel = DB::select(
            "SELECT s.name, s.color, s.position, COUNT(d.id) AS reached, COALESCE(SUM(d.amount),0) AS value
               FROM pipeline_stages s
               LEFT JOIN deals d ON d.stage_id = s.id AND d.created_at BETWEEN ? AND ?
              WHERE s.tenant_id = ? GROUP BY s.id ORDER BY s.position",
            [$lo, $hi, $t]
        );
        $lostReasons = DB::select(
            "SELECT COALESCE(lost_reason,'Не указано') AS reason, COUNT(*) AS count, COALESCE(SUM(amount),0) AS value
               FROM deals WHERE tenant_id = ? AND status='lost' AND updated_at BETWEEN ? AND ?
              GROUP BY reason ORDER BY count DESC",
            [$t, $lo, $hi]
        );
        $cycle = $this->q1(
            "SELECT AVG(julianday(won_at) - julianday(created_at)) AS days, COUNT(*) AS n
               FROM deals WHERE tenant_id = ? AND status='won' AND won_at IS NOT NULL",
            [$t]
        );
        $managers = DB::select(
            "SELECT u.name, u.avatar_color, COUNT(d.id) AS total,
                    SUM(CASE WHEN d.status='won' THEN 1 ELSE 0 END) AS won,
                    SUM(CASE WHEN d.status='lost' THEN 1 ELSE 0 END) AS lost,
                    COALESCE(SUM(CASE WHEN d.status='won' THEN d.amount END),0) AS revenue
               FROM users u JOIN tenant_memberships m ON m.user_id = u.id AND m.tenant_id = ?
               LEFT JOIN deals d ON d.owner_id = u.id AND d.created_at BETWEEN ? AND ?
              GROUP BY u.id HAVING total > 0 ORDER BY revenue DESC",
            [$t, $lo, $hi]
        );
        $cohorts = DB::select(
            "SELECT " . SqlDialect::month('l.created_at') . " AS ym, COUNT(*) AS leads,
                    SUM(CASE WHEN l.status='converted' THEN 1 ELSE 0 END) AS converted
               FROM leads l WHERE l.tenant_id = ? AND l.created_at >= " . SqlDialect::ago(6, 'month') . "
              GROUP BY ym ORDER BY ym",
            [$t]
        );
        $aiByOutcome = DB::select(
            "SELECT COALESCE(outcome,'unknown') AS outcome, COUNT(*) AS count, COALESCE(AVG(duration_sec),0) AS avg_seconds
               FROM ai_calls WHERE tenant_id = ? AND created_at BETWEEN ? AND ? GROUP BY outcome ORDER BY count DESC",
            [$t, $lo, $hi]
        );
        $aiByHour = DB::select(
            "SELECT " . SqlDialect::hour('started_at') . " AS hour, COUNT(*) AS calls,
                    SUM(CASE WHEN outcome IN ('interested','qualified') THEN 1 ELSE 0 END) AS positive
               FROM ai_calls WHERE tenant_id = ? AND started_at IS NOT NULL GROUP BY hour ORDER BY hour",
            [$t]
        );

        return H::json([
            'period' => ['from' => $from, 'to' => $to, 'label' => $label],
            'funnel' => $funnel, 'lostReasons' => $lostReasons,
            'cycle' => ['days' => isset($cycle['days']) ? round($cycle['days'] * 10) / 10 : 0, 'sample' => $cycle['n'] ?? 0],
            'managers' => $managers, 'cohorts' => $cohorts,
            'aiByOutcome' => $aiByOutcome, 'aiByHour' => $aiByHour,
            'notes' => [
                'Показатели с выборкой меньше 5 записей помечаются как недостаточные для выводов.',
                'Наблюдаемые закономерности не являются доказанной причинностью.',
            ],
        ]);
    }

    /** Global search across tenant-scoped entities (respects tenant isolation). */
    public function search(Ctx $ctx)
    {
        $ctx->auth();
        $t = $ctx->tenantId;
        $q = trim((string) $ctx->query('q', ''));
        if (!$t || mb_strlen($q) < 2) return H::json(['results' => []]);
        $s = H::like($q);
        $orgs = DB::select('SELECT id, name AS title, industry AS subtitle FROM organizations WHERE tenant_id=? AND name LIKE ? LIMIT 5', [$t, $s]);
        $contacts = DB::select("SELECT id, first_name || ' ' || COALESCE(last_name,'') AS title, COALESCE(email,'') AS subtitle FROM contacts WHERE tenant_id=? AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?) LIMIT 5", [$t, $s, $s, $s]);
        $deals = DB::select('SELECT id, title, amount AS subtitle FROM deals WHERE tenant_id=? AND title LIKE ? LIMIT 5', [$t, $s]);
        $leads = DB::select('SELECT id, title, COALESCE(source, status) AS subtitle FROM leads WHERE tenant_id=? AND title LIKE ? LIMIT 5', [$t, $s]);
        $orders = DB::select('SELECT id, number AS title, status AS subtitle FROM orders WHERE tenant_id=? AND number LIKE ? LIMIT 5', [$t, $s]);
        $projects = DB::select('SELECT id, name AS title, status AS subtitle FROM projects WHERE tenant_id=? AND name LIKE ? LIMIT 5', [$t, $s]);
        $activities = DB::select(
            "SELECT id, subject AS title, body AS subtitle, entity_type, entity_id
               FROM activities WHERE tenant_id=? AND (subject LIKE ? OR body LIKE ?) ORDER BY created_at DESC LIMIT 8",
            [$t, $s, $s]
        );
        $calls = DB::select(
            "SELECT id, COALESCE(summary, phone, 'AI call') AS title, transcript, contact_id
               FROM ai_calls WHERE tenant_id=? AND (summary LIKE ? OR transcript LIKE ? OR phone LIKE ?) ORDER BY created_at DESC LIMIT 8",
            [$t, $s, $s, $s]
        );
        // Natural-language queries are split into meaningful terms so “customers who asked
        // for a proposal” can still find a transcript containing only “proposal”.
        $stop = ['the', 'and', 'who', 'for', 'with', 'that', 'this', 'customers', 'mijozlar', 'клиенты', 'которые', 'uchun', 'bilan'];
        $terms = array_values(array_filter(array_unique(preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($q)) ?: []), fn ($term) => mb_strlen($term) >= 3 && !in_array($term, $stop, true)));
        foreach (array_slice($terms, 0, 6) as $term) {
            $termLike = H::like($term);
            $activities = array_merge($activities, DB::select(
                "SELECT id, subject AS title, body AS subtitle, entity_type, entity_id
                   FROM activities WHERE tenant_id=? AND (subject LIKE ? OR body LIKE ?) ORDER BY created_at DESC LIMIT 8",
                [$t, $termLike, $termLike]
            ));
            $calls = array_merge($calls, DB::select(
                "SELECT id, COALESCE(summary, phone, 'AI call') AS title, transcript, contact_id
                   FROM ai_calls WHERE tenant_id=? AND (summary LIKE ? OR transcript LIKE ? OR phone LIKE ?) ORDER BY created_at DESC LIMIT 8",
                [$t, $termLike, $termLike, $termLike]
            ));
        }
        $map = fn (array $rows, string $type) => array_map(fn ($r) => [...(array) $r, 'type' => $type], $rows);
        $evidence = fn (string $text) => mb_substr(preg_replace('/\s+/', ' ', $text) ?? '', 0, 180);
        $activitySeen = [];
        $activityHits = array_values(array_filter(array_map(fn ($r) => [...(array) $r, 'subtitle' => $evidence((string) ($r->subtitle ?? '')), 'type' => 'activity'], $activities), function ($r) use (&$activitySeen) {
            if (isset($activitySeen[$r['id']])) return false;
            $activitySeen[$r['id']] = true;
            return true;
        }));
        $callSeen = [];
        $callHits = array_values(array_filter(array_map(fn ($r) => ['id' => $r->id, 'title' => $r->title, 'subtitle' => $evidence((string) ($r->transcript ?? '')), 'type' => 'call'], $calls), function ($r) use (&$callSeen) {
            if (isset($callSeen[$r['id']])) return false;
            $callSeen[$r['id']] = true;
            return true;
        }));
        return H::json(['results' => array_merge(
            $map($orgs, 'company'), $map($contacts, 'contact'), $map($deals, 'deal'),
            $map($leads, 'lead'), $map($orders, 'order'), $map($projects, 'project'), $activityHits, $callHits,
        )]);
    }
}
