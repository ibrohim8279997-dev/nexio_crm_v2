<?php
namespace App\Http\Controllers\Api\V1;

use App\Support\ApiException;
use App\Support\Ctx;
use App\Support\H;
use Illuminate\Support\Facades\DB;

/**
 * Client portal API.
 *
 * Hard rule: a portal session only ever reads rows that belong to the single
 * customer organization its membership is linked to. No client-supplied id can widen it.
 */
class PortalController
{
    private function portalOrg(Ctx $ctx): object
    {
        $user = $ctx->auth();
        if (!$ctx->tenantId) throw new ApiException(403, 'Портал доступен только пользователям, привязанным к компании', 'forbidden');
        $m = DB::selectOne('SELECT org_id, contact_id, role FROM tenant_memberships WHERE tenant_id = ? AND user_id = ? AND status = ?', [$ctx->tenantId, $user['id'], 'active']);
        if (!$m) throw new ApiException(403, 'Нет активного доступа к порталу', 'forbidden');
        if (!$m->org_id) throw new ApiException(403, 'Учётная запись не привязана к организации заказчика', 'forbidden');
        if (!$m->contact_id) throw new ApiException(403, 'Portal akkaunti kompaniya va CRM clientiga biriktirilmagan', 'forbidden');
        $org = DB::selectOne('SELECT id, name, industry, tier, status, city, country FROM organizations WHERE id = ? AND tenant_id = ?', [$m->org_id, $ctx->tenantId]);
        if (!$org) throw new ApiException(404, 'Организация не найдена', 'not_found');
        $org->contact_id = $m->contact_id;
        return $org;
    }

    /** Everything the customer sees about their own account in one read. */
    public function overview(Ctx $ctx)
    {
        $user = $ctx->auth();
        $org = $this->portalOrg($ctx);
        $t = $ctx->tenantId;

        $deals = DB::select('SELECT id, title, status, amount, currency, probability, expected_close_at, created_at FROM deals WHERE tenant_id = ? AND org_id = ? AND contact_id = ? ORDER BY created_at DESC LIMIT 50', [$t, $org->id, $org->contact_id]);
        $orders = DB::select('SELECT id, number, status, amount, currency, payment_status, created_at, confirmed_at FROM orders WHERE tenant_id = ? AND org_id = ? AND contact_id = ? ORDER BY created_at DESC LIMIT 50', [$t, $org->id, $org->contact_id]);
        $projects = DB::select('SELECT id, name, status, progress, priority, budget, currency, start_date, due_date, description FROM projects WHERE tenant_id = ? AND org_id = ? AND order_id IN (SELECT id FROM orders WHERE tenant_id = ? AND contact_id = ?) ORDER BY created_at DESC LIMIT 30', [$t, $org->id, $t, $org->contact_id]);
        $milestones = DB::select('SELECT m.project_id, m.name, m.status, m.due_date, m.position FROM project_milestones m JOIN projects p ON p.id = m.project_id WHERE m.tenant_id = ? AND p.org_id = ? AND p.order_id IN (SELECT id FROM orders WHERE tenant_id = ? AND contact_id = ?) ORDER BY m.position', [$t, $org->id, $t, $org->contact_id]);
        $docs = DB::select('SELECT id, name, kind, version, size_kb, status, created_at FROM documents WHERE tenant_id = ? AND org_id = ? AND order_id IN (SELECT id FROM orders WHERE tenant_id = ? AND contact_id = ?) ORDER BY created_at DESC LIMIT 30', [$t, $org->id, $t, $org->contact_id]);
        $tickets = DB::select('SELECT id, number, subject, priority, status, channel, sla_due_at, created_at, updated_at FROM tickets WHERE tenant_id = ? AND org_id = ? AND requester = ? ORDER BY created_at DESC LIMIT 40', [$t, $org->id, $user['email']]);
        $messages = DB::select("SELECT id, channel, direction, subject, body, status, created_at FROM communications WHERE tenant_id = ? AND org_id = ? AND contact_id = ? AND channel = 'portal' ORDER BY created_at DESC LIMIT 40", [$t, $org->id, $org->contact_id]);
        $requests = DB::select('SELECT id, service, title, description, budget, currency, status, priority, created_at, updated_at FROM nexio_service_requests WHERE tenant_id = ? AND created_by = ? ORDER BY created_at DESC LIMIT 30', [$t, $user['id']]);
        $tenant = DB::selectOne('SELECT name, currency FROM tenants WHERE id = ?', [$t]);

        $invoiceTotal = array_sum(array_map(fn ($r) => (float) $r->amount, $orders));
        $outstanding = array_sum(array_map(fn ($r) => $r->payment_status !== 'paid' ? (float) $r->amount : 0.0, $orders));
        $activeProjects = count(array_filter($projects, fn ($p) => !in_array($p->status, ['done', 'cancelled'], true)));
        $openTickets = count(array_filter($tickets, fn ($k) => !in_array($k->status, ['resolved', 'closed'], true)));

        return H::json([
            'org' => $org,
            'supplier' => ['name' => $tenant->name ?? 'Nexio CRM', 'currency' => $tenant->currency ?? 'UZS'],
            'deals' => $deals, 'orders' => $orders, 'projects' => $projects, 'milestones' => $milestones,
            'documents' => $docs, 'tickets' => $tickets, 'messages' => $messages,
            'serviceRequests' => $requests,
            'summary' => [
                'ordersTotal' => $invoiceTotal, 'outstanding' => $outstanding,
                'activeProjects' => $activeProjects, 'openTickets' => $openTickets,
                'orders' => count($orders), 'projects' => count($projects),
            ],
            'services' => [
                ['code' => 'website', 'label' => 'Разработка сайта'],
                ['code' => 'crm', 'label' => 'Внедрение CRM'],
                ['code' => 'android', 'label' => 'Android-приложение'],
                ['code' => 'windows', 'label' => 'Windows-приложение'],
                ['code' => 'api', 'label' => 'API-интеграция'],
                ['code' => 'ai_agent', 'label' => 'AI-агент'],
                ['code' => 'support', 'label' => 'Поддержка'],
            ],
            'note' => 'В портале видны только данные вашей организации: заказы, проекты, документы и обращения.',
        ]);
    }

    /** Customer can order an additional service; it lands in the Nexio Lab queue. */
    public function createServiceRequest(Ctx $ctx)
    {
        $user = $ctx->auth();
        $org = $this->portalOrg($ctx);
        $b = H::body($ctx);
        $service = H::oneOf($b['service'] ?? null, 'service', ['website', 'crm', 'android', 'windows', 'api', 'ai_agent', 'support']);
        $title = H::str($b['title'] ?? null, 'title', true, 160);
        $description = H::str($b['description'] ?? null, 'description', false, 2000);
        $budget = H::num($b['budget'] ?? null, 'budget', false, 0) ?? 0;
        $id = H::newId('nsr');
        DB::table('nexio_service_requests')->insert([
            'id' => $id, 'tenant_id' => $ctx->tenantId, 'service' => $service, 'title' => $title,
            'description' => $description, 'budget' => $budget, 'currency' => 'USD', 'status' => 'submitted',
            'priority' => 'normal', 'assignee_id' => null, 'created_by' => $user['id'],
            'created_at' => H::now(), 'updated_at' => H::now(),
        ]);
        DB::table('communications')->insert([
            'id' => H::newId('com'), 'tenant_id' => $ctx->tenantId, 'channel' => 'portal', 'direction' => 'inbound',
            'subject' => "Новая заявка: {$title}", 'body' => $description, 'org_id' => $org->id,
            'contact_id' => $org->contact_id, 'user_id' => $user['id'], 'status' => 'received', 'created_at' => H::now(),
        ]);
        H::audit($ctx, 'portal.service_requested', 'nexio_service_request', $id, ['service' => $service, 'title' => $title, 'org' => $org->id]);
        return H::json(['ok' => true, 'id' => $id, 'note' => 'Заявка отправлена в Nexio Lab. Статус будет виден в портале.'], 201);
    }

    /** Customer can raise a support ticket; SLA clock and priority are set by the supplier. */
    public function createTicket(Ctx $ctx)
    {
        $user = $ctx->auth();
        $org = $this->portalOrg($ctx);
        $b = H::body($ctx);
        $subject = H::str($b['subject'] ?? null, 'subject', true, 160);
        $description = H::str($b['description'] ?? null, 'description', false, 2000);
        $priority = H::oneOf($b['priority'] ?? null, 'priority', ['low', 'normal', 'high', 'urgent'], 'normal');
        $seq = DB::selectOne('SELECT COUNT(*) AS c FROM tickets WHERE tenant_id = ?', [$ctx->tenantId]);
        $id = H::newId('tkt');
        $slaHours = $priority === 'urgent' ? 1 : ($priority === 'high' ? 4 : ($priority === 'normal' ? 24 : 48));
        $slaDue = gmdate('Y-m-d H:i:s', time() + $slaHours * 3600);
        DB::table('tickets')->insert([
            'id' => $id, 'tenant_id' => $ctx->tenantId,
            'number' => 'TK-' . (1000 + (int) ($seq->c ?? 0) + 1),
            'subject' => $subject, 'org_id' => $org->id, 'requester' => $user['email'],
            'channel' => 'portal', 'priority' => $priority, 'status' => 'open', 'sla_due_at' => $slaDue,
            'assignee_id' => null, 'body' => $description, 'created_at' => H::now(), 'updated_at' => H::now(),
        ]);
        H::audit($ctx, 'portal.ticket_created', 'ticket', $id, ['org' => $org->id]);
        return H::json(['ok' => true, 'id' => $id, 'sla_due_at' => $slaDue, 'note' => 'Обращение создано, срок реакции зависит от приоритета.'], 201);
    }

    /** Portal message thread with the supplier's team. */
    public function sendMessage(Ctx $ctx)
    {
        $user = $ctx->auth();
        $org = $this->portalOrg($ctx);
        $b = H::body($ctx);
        $text = H::str($b['body'] ?? null, 'body', true, 2000);
        $id = H::newId('com');
        DB::table('communications')->insert([
            'id' => $id, 'tenant_id' => $ctx->tenantId, 'channel' => 'portal', 'direction' => 'inbound',
            'subject' => H::str($b['subject'] ?? null, 'subject', false, 160), 'body' => $text,
            'org_id' => $org->id, 'contact_id' => $org->contact_id, 'user_id' => $user['id'],
            'status' => 'received', 'created_at' => H::now(),
        ]);
        H::audit($ctx, 'portal.message_sent', 'communication', $id, ['org' => $org->id]);
        return H::json(['ok' => true, 'id' => $id], 201);
    }
}
