<?php
namespace App\Http\Controllers\Api\V1;

use App\Support\ApiException;
use App\Support\Ctx;
use App\Support\H;
use Illuminate\Support\Facades\DB;

/** Ready-made business reports with a date range and CSV export (spreadsheet-formula safe). */
class ReportsController
{
    /** key => [title, description, permission, uses date range] */
    private const CATALOG = [
        'sales_by_manager' => ['Продажи по менеджерам', 'Выигранные сделки: количество, сумма и средний чек по ответственным', 'crm.read', true],
        'pipeline_by_stage' => ['Воронка по стадиям', 'Открытые сделки на каждой стадии и взвешенный прогноз (снимок на сегодня)', 'crm.read', false],
        'leads_by_source' => ['Лиды по источникам', 'Сколько лидов пришло, квалифицировано и конвертировано по каждому источнику', 'crm.read', true],
        'tasks_by_assignee' => ['Задачи по исполнителям', 'Нагрузка команды: статусы задач и просрочка (снимок на сегодня)', 'crm.read', false],
        'revenue_by_month' => ['Поступления по месяцам', 'Оплаты по выставленным счетам', 'finance.read', true],
        'receivables_aging' => ['Дебиторская задолженность', 'Неоплаченные счета по срокам просрочки (снимок на сегодня)', 'finance.read', false],
        'expenses_by_category' => ['Расходы по категориям', 'Учтённые расходы компании за период', 'finance.read', true],
    ];

    public function catalog(Ctx $ctx)
    {
        $ctx->auth(); H::requireTenant($ctx);
        $out = [];
        foreach (self::CATALOG as $key => [$title, $desc, $perm, $ranged]) {
            if (H::can($ctx, $perm)) $out[] = ['key' => $key, 'title' => $title, 'description' => $desc, 'uses_range' => $ranged];
        }
        return H::json(['data' => $out, 'can_export' => H::can($ctx, 'export.data')]);
    }

    /** @return array{0:string,1:string,2:string} [from, to, toExclusive] */
    private function range(Ctx $ctx): array
    {
        $to = (string) ($ctx->query('to') ?: gmdate('Y-m-d'));
        $from = (string) ($ctx->query('from') ?: gmdate('Y-m-d', strtotime($to) - 89 * 86400));
        foreach ([$from, $to] as $d) if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) || !strtotime($d)) throw new ApiException(400, 'Даты в формате ГГГГ-ММ-ДД', 'bad_request');
        if ($from > $to) throw new ApiException(400, 'Начало периода позже конца', 'bad_request');
        if (strtotime($to) - strtotime($from) > 366 * 86400) throw new ApiException(400, 'Максимальный период — 366 дней', 'bad_request');
        return [$from, $to, gmdate('Y-m-d', strtotime($to) + 86400)];
    }

    private function build(Ctx $ctx, string $key): array
    {
        if (!isset(self::CATALOG[$key])) throw new ApiException(404, 'Отчёт не найден', 'not_found');
        H::requirePermission($ctx, self::CATALOG[$key][2]);
        $t = $ctx->tenantId;
        [$from, $to, $end] = self::CATALOG[$key][3] ? $this->range($ctx) : [null, null, null];
        $col = fn (string $k, string $l, string $type = 'text') => ['key' => $k, 'label' => $l, 'type' => $type];

        switch ($key) {
            case 'sales_by_manager':
                $rows = DB::select(
                    "SELECT COALESCE(u.name,'Не назначен') AS manager, COUNT(*) AS deals, COALESCE(SUM(d.amount),0) AS amount, COALESCE(AVG(d.amount),0) AS average
                       FROM deals d LEFT JOIN users u ON u.id = d.owner_id
                      WHERE d.tenant_id = ? AND d.status = 'won' AND COALESCE(d.won_at, d.updated_at) >= ? AND COALESCE(d.won_at, d.updated_at) < ?
                      GROUP BY u.id, u.name ORDER BY amount DESC", [$t, $from, $end]);
                $cols = [$col('manager', 'Менеджер'), $col('deals', 'Сделок', 'number'), $col('amount', 'Сумма', 'money'), $col('average', 'Средний чек', 'money')];
                break;
            case 'pipeline_by_stage':
                $rows = DB::select(
                    "SELECT s.name AS stage, COUNT(d.id) AS deals, COALESCE(SUM(d.amount),0) AS amount, COALESCE(SUM(d.amount * d.probability / 100.0),0) AS weighted
                       FROM pipeline_stages s LEFT JOIN deals d ON d.stage_id = s.id AND d.status = 'open' AND d.tenant_id = s.tenant_id
                      WHERE s.tenant_id = ? GROUP BY s.id, s.name, s.position ORDER BY s.position", [$t]);
                $cols = [$col('stage', 'Стадия'), $col('deals', 'Сделок', 'number'), $col('amount', 'Сумма', 'money'), $col('weighted', 'Взвешенный прогноз', 'money')];
                break;
            case 'leads_by_source':
                $rows = DB::select(
                    "SELECT COALESCE(source,'manual') AS source, COUNT(*) AS leads,
                            SUM(CASE WHEN status IN ('qualified','converted') THEN 1 ELSE 0 END) AS qualified,
                            SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) AS converted
                       FROM leads WHERE tenant_id = ? AND created_at >= ? AND created_at < ? GROUP BY source ORDER BY leads DESC", [$t, $from, $end]);
                $rows = array_map(fn ($r) => [...(array) $r, 'conversion' => $r->leads ? round($r->converted / $r->leads * 100, 1) : 0], $rows);
                $cols = [$col('source', 'Источник'), $col('leads', 'Лидов', 'number'), $col('qualified', 'Квалифицировано', 'number'), $col('converted', 'Конвертировано', 'number'), $col('conversion', 'Конверсия, %', 'percent')];
                break;
            case 'tasks_by_assignee':
                $rows = DB::select(
                    "SELECT COALESCE(u.name,'Не назначен') AS assignee,
                            SUM(CASE WHEN k.status='todo' THEN 1 ELSE 0 END) AS todo, SUM(CASE WHEN k.status='in_progress' THEN 1 ELSE 0 END) AS in_progress,
                            SUM(CASE WHEN k.status='review' THEN 1 ELSE 0 END) AS review, SUM(CASE WHEN k.status='done' THEN 1 ELSE 0 END) AS done,
                            SUM(CASE WHEN k.status != 'done' AND k.due_date IS NOT NULL AND k.due_date < ? THEN 1 ELSE 0 END) AS overdue
                       FROM tasks k LEFT JOIN users u ON u.id = k.assignee_id WHERE k.tenant_id = ? GROUP BY u.id, u.name ORDER BY overdue DESC, todo DESC", [gmdate('Y-m-d'), $t]);
                $cols = [$col('assignee', 'Исполнитель'), $col('todo', 'К выполнению', 'number'), $col('in_progress', 'В работе', 'number'), $col('review', 'На проверке', 'number'), $col('done', 'Готово', 'number'), $col('overdue', 'Просрочено', 'number')];
                break;
            case 'revenue_by_month':
                $rows = DB::select(
                    "SELECT substr(p.paid_at, 1, 7) AS month, i.currency AS currency, COUNT(*) AS payments, COALESCE(SUM(p.amount),0) AS amount
                       FROM client_payments p JOIN client_invoices i ON i.id = p.invoice_id
                      WHERE p.tenant_id = ? AND p.paid_at >= ? AND p.paid_at < ? GROUP BY substr(p.paid_at, 1, 7), i.currency ORDER BY month", [$t, $from, $end]);
                $cols = [$col('month', 'Месяц'), $col('currency', 'Валюта'), $col('payments', 'Платежей', 'number'), $col('amount', 'Сумма', 'money')];
                break;
            case 'receivables_aging':
                $buckets = ['not_due' => ['Не просрочено', 0, 0.0], 'd1_30' => ['1–30 дней', 0, 0.0], 'd31_60' => ['31–60 дней', 0, 0.0], 'd61' => ['Более 60 дней', 0, 0.0]];
                $today = strtotime(gmdate('Y-m-d'));
                foreach (DB::select("SELECT currency, total, paid_amount, due_at FROM client_invoices WHERE tenant_id = ? AND status IN ('sent','partial')", [$t]) as $i) {
                    $late = $i->due_at ? (int) floor(($today - strtotime($i->due_at)) / 86400) : 0;
                    $b = $late <= 0 ? 'not_due' : ($late <= 30 ? 'd1_30' : ($late <= 60 ? 'd31_60' : 'd61'));
                    $buckets[$b][1]++; $buckets[$b][2] += (float) $i->total - (float) $i->paid_amount;
                }
                $rows = array_map(fn ($b) => ['bucket' => $b[0], 'invoices' => $b[1], 'amount' => round($b[2], 2)], array_values($buckets));
                $cols = [$col('bucket', 'Срок'), $col('invoices', 'Счетов', 'number'), $col('amount', 'Остаток к оплате', 'money')];
                break;
            default: // expenses_by_category
                $rows = DB::select(
                    "SELECT category, currency, COUNT(*) AS items, COALESCE(SUM(amount),0) AS amount FROM expenses
                      WHERE tenant_id = ? AND expense_date >= ? AND expense_date <= ? GROUP BY category, currency ORDER BY amount DESC", [$t, $from, $to]);
                $cols = [$col('category', 'Категория'), $col('currency', 'Валюта'), $col('items', 'Записей', 'number'), $col('amount', 'Сумма', 'money')];
        }
        $rows = array_map(fn ($r) => array_map(fn ($v) => is_numeric($v) && !is_string($v) ? $v + 0 : $v, (array) $r), $rows);
        return ['key' => $key, 'title' => self::CATALOG[$key][0], 'from' => $from, 'to' => $to, 'columns' => $cols, 'rows' => array_values($rows)];
    }

    public function run(Ctx $ctx)
    {
        $ctx->auth(); H::requireTenant($ctx);
        return H::json($this->build($ctx, (string) $ctx->params['key']));
    }

    /** A cell starting with = + - @ (or a control character) would run as a formula in Excel/Sheets. */
    public static function csvCell(mixed $v): string
    {
        $s = is_float($v) ? rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.') : (string) $v;
        if (is_string($v) && $s !== '' && (preg_match('/^[=+\-@\t\r]/', $s)) && !is_numeric($s)) $s = "'" . $s;
        return '"' . str_replace('"', '""', $s) . '"';
    }

    public function export(Ctx $ctx)
    {
        $ctx->auth(); H::requireTenant($ctx); H::requirePermission($ctx, 'export.data');
        $r = $this->build($ctx, (string) $ctx->params['key']);
        $lines = [implode(',', array_map(fn ($c) => self::csvCell($c['label']), $r['columns']))];
        foreach ($r['rows'] as $row) $lines[] = implode(',', array_map(fn ($c) => self::csvCell($row[$c['key']] ?? ''), $r['columns']));
        H::audit($ctx, 'report.exported', 'report', $r['key'], ['rows' => count($r['rows'])]);
        $name = 'report-' . $r['key'] . ($r['from'] ? "-{$r['from']}_{$r['to']}" : '-' . gmdate('Y-m-d')) . '.csv';
        return response("\xEF\xBB\xBF" . implode("\r\n", $lines) . "\r\n", 200, [
            'Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="' . $name . '"',
            'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'no-store',
        ]);
    }
}
