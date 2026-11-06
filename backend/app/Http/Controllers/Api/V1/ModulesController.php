<?php
namespace App\Http\Controllers\Api\V1;

use App\Support\ApiException;
use App\Support\Ctx;
use App\Support\H;
use App\Support\SqlDialect;
use App\Services\GroqAi;
use App\Services\NotificationDispatcher;
use Illuminate\Support\Facades\DB;

/** Catalog, helpdesk, communications, documents, automations, integrations, Nexio services. */
class ModulesController
{
    private function owned(Ctx $ctx, string $table, string $id): bool
    {
        return (bool) DB::selectOne("SELECT 1 AS x FROM {$table} WHERE id = ? AND tenant_id = ?", [$id, $ctx->tenantId]);
    }

    // ── Catalog ──────────────────────────────────────────────────────────────
    public function listProducts(Ctx $ctx)
    {
        $ctx->auth();
        $t = $ctx->tenantId;
        $q = $ctx->query('q'); $cat = $ctx->query('category');
        $where = ['tenant_id = ?']; $binds = [$t];
        if ($q) { $where[] = '(name LIKE ? OR sku LIKE ?)'; array_push($binds, "%{$q}%", "%{$q}%"); }
        if ($cat) { $where[] = 'category = ?'; $binds[] = $cat; }
        $rows = DB::select('SELECT * FROM products WHERE ' . implode(' AND ', $where) . ' ORDER BY name LIMIT 300', $binds);
        $cats = DB::select('SELECT DISTINCT category FROM products WHERE tenant_id = ? AND category IS NOT NULL ORDER BY category', [$t]);
        $stats = DB::selectOne('SELECT COUNT(*) AS total, SUM(CASE WHEN is_active=1 THEN 1 ELSE 0 END) AS active, COALESCE(AVG(price),0) AS avg_price FROM products WHERE tenant_id = ?', [$t]);
        return H::json(['data' => $rows, 'categories' => array_map(fn ($c) => $c->category, $cats), 'stats' => $stats]);
    }

    public function createProduct(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'finance.write');
        $b = H::body($ctx);
        $id = H::newId('prd');
        DB::table('products')->insert([
            'id' => $id, 'tenant_id' => $ctx->tenantId,
            'name' => H::str($b['name'] ?? null, 'name', true, 200),
            'sku' => H::str($b['sku'] ?? null, 'sku', false, 60),
            'category' => H::str($b['category'] ?? null, 'category', false, 80),
            'description' => H::str($b['description'] ?? null, 'description', false, 2000),
            'price' => H::num($b['price'] ?? null, 'price', false, 0) ?? 0,
            'cost' => H::num($b['cost'] ?? null, 'cost', false, 0) ?? 0,
            'currency' => H::str($b['currency'] ?? null, 'currency', false, 8) ?? 'UZS',
            'billing_type' => H::oneOf($b['billing_type'] ?? null, 'billing_type', ['one_time', 'monthly', 'hourly'], 'one_time'),
            'is_active' => 1, 'created_at' => H::now(),
        ]);
        // When Inventory is enabled, the CRM catalog product is also the inventory product.
        // The tenant check is explicit so one company's catalog can never populate another's stock.
        if (DB::table('plugin_installations')->where('tenant_id', $ctx->tenantId)->where('plugin_id', 'nexio.inventory')->where('status', 'enabled')->exists()) {
            DB::insert(
                'INSERT OR IGNORE INTO inventory_products (id, tenant_id, sku, name, unit, category, cost, min_stock, created_by, created_at, updated_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?)',
                [$id, $ctx->tenantId, (string) ($b['sku'] ?? ''), (string) ($b['name'] ?? ''), (string) ($b['unit'] ?? 'pcs'), $b['category'] ?? null, (float) ($b['cost'] ?? 0), (float) ($b['min_stock'] ?? 0), $ctx->user['id'] ?? null, H::now(), H::now()]
            );
        }
        H::audit($ctx, 'product.created', 'product', $id);
        return H::json(['id' => $id], 201);
    }

    public function updateProduct(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'finance.write');
        if (!$this->owned($ctx, 'products', $ctx->params['id'])) throw new ApiException(404, 'Позиция не найдена', 'not_found');
        $b = H::body($ctx);
        DB::update('UPDATE products SET name=COALESCE(?,name), sku=?, category=?, description=?, price=COALESCE(?,price), cost=COALESCE(?,cost), billing_type=COALESCE(?,billing_type), is_active=COALESCE(?,is_active) WHERE id=? AND tenant_id=?', [
            H::str($b['name'] ?? null, 'name', false, 200),
            H::str($b['sku'] ?? null, 'sku', false, 60),
            H::str($b['category'] ?? null, 'category', false, 80),
            H::str($b['description'] ?? null, 'description', false, 2000),
            H::num($b['price'] ?? null, 'price', false, 0),
            H::num($b['cost'] ?? null, 'cost', false, 0),
            $b['billing_type'] ?? null,
            !array_key_exists('is_active', $b) ? null : (!empty($b['is_active']) ? 1 : 0),
            $ctx->params['id'], $ctx->tenantId,
        ]);
        return H::json(['ok' => true]);
    }

    // ── Helpdesk tickets ─────────────────────────────────────────────────────
    public function listTickets(Ctx $ctx)
    {
        $ctx->auth();
        $t = $ctx->tenantId;
        [$limit, $page, $offset] = H::paginate($ctx);
        $status = $ctx->query('status'); $priority = $ctx->query('priority');
        $where = ['k.tenant_id = ?']; $binds = [$t];
        if ($status) { $where[] = 'k.status = ?'; $binds[] = $status; }
        if ($priority) { $where[] = 'k.priority = ?'; $binds[] = $priority; }
        $w = implode(' AND ', $where);
        $rows = DB::select(
            "SELECT k.*, org.name AS org_name, u.name AS assignee_name, u.avatar_color AS assignee_color,
                    CASE WHEN k.status NOT IN ('resolved','closed') AND k.sla_due_at < " . SqlDialect::now() . " THEN 1 ELSE 0 END AS sla_breached
               FROM tickets k LEFT JOIN organizations org ON org.id=k.org_id LEFT JOIN users u ON u.id=k.assignee_id
              WHERE {$w} ORDER BY CASE k.priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'normal' THEN 2 ELSE 3 END, k.created_at DESC LIMIT ? OFFSET ?",
            [...$binds, $limit, $offset]
        );
        $total = DB::selectOne("SELECT COUNT(*) AS c FROM tickets k WHERE {$w}", $binds);
        $stats = DB::selectOne(
            "SELECT COUNT(*) AS total, COALESCE(SUM(CASE WHEN status='open' THEN 1 ELSE 0 END),0) AS open,
                    COALESCE(SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END),0) AS pending,
                    COALESCE(SUM(CASE WHEN status='resolved' THEN 1 ELSE 0 END),0) AS resolved,
                    COALESCE(SUM(CASE WHEN status='closed' THEN 1 ELSE 0 END),0) AS closed,
                    COALESCE(SUM(CASE WHEN priority IN ('high','urgent') AND status NOT IN ('resolved','closed') THEN 1 ELSE 0 END),0) AS escalated,
                    COALESCE(SUM(CASE WHEN status NOT IN ('resolved','closed') AND sla_due_at < " . SqlDialect::now() . " THEN 1 ELSE 0 END),0) AS breached
               FROM tickets WHERE tenant_id = ?",
            [$t]
        );
        return H::json(['data' => $rows, 'total' => (int) ($total->c ?? 0), 'page' => $page, 'limit' => $limit, 'stats' => $stats]);
    }

    public function createTicket(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'crm.write');
        H::requireTenant($ctx);
        $t = $ctx->tenantId;
        $b = H::body($ctx);
        $id = H::newId('tkt');
        $assigneeId = H::memberRef($ctx, $b['assignee_id'] ?? null, 'ijrochi');
        $cnt = DB::selectOne('SELECT COUNT(*) AS c FROM tickets WHERE tenant_id = ?', [$t]);
        $priority = H::oneOf($b['priority'] ?? null, 'priority', ['low', 'normal', 'high', 'urgent'], 'normal');
        $slaHours = $priority === 'urgent' ? 4 : ($priority === 'high' ? 8 : ($priority === 'normal' ? 24 : 48));
        DB::table('tickets')->insert([
            'id' => $id, 'tenant_id' => $t,
            'number' => 'TCK-' . substr((string) (3000 + (int) ($cnt->c ?? 0) + 1), -4),
            'subject' => H::str($b['subject'] ?? null, 'subject', true, 250),
            'org_id' => H::tenantRef($ctx, $b['org_id'] ?? null, 'organizations', 'kompaniya'),
            'requester' => H::str($b['requester'] ?? null, 'requester', false, 190),
            'channel' => H::oneOf($b['channel'] ?? null, 'channel', ['email', 'telegram', 'portal', 'phone'], 'email'),
            'priority' => $priority, 'status' => 'open',
            'sla_due_at' => gmdate('Y-m-d H:i:s', time() + $slaHours * 3600),
            'assignee_id' => $assigneeId,
            'body' => H::str($b['body'] ?? null, 'body', false, 4000),
            'created_at' => H::now(), 'updated_at' => H::now(),
        ]);
        H::audit($ctx, 'ticket.created', 'ticket', $id, ['priority' => $priority]);
        $notifier = new NotificationDispatcher();
        $subject = (string) $b['subject'];
        if ($assigneeId) {
            $notifier->notifyMember($t, $assigneeId, 'info', 'Yangi murojaat', $subject, '/app/helpdesk');
        } else {
            $notifier->notifyAdmins($t, 'info', 'Yangi murojaat', $subject, '/app/helpdesk');
        }
        return H::json(['id' => $id], 201);
    }

    public function setTicketStatus(Ctx $ctx)
    {
        $user = $ctx->auth();
        H::requirePermission($ctx, 'crm.write');
        $ticket = DB::selectOne('SELECT subject, assignee_id FROM tickets WHERE id = ? AND tenant_id = ?', [$ctx->params['id'], $ctx->tenantId]);
        if (!$ticket) throw new ApiException(404, 'Обращение не найдено', 'not_found');
        $b = H::body($ctx);
        $status = H::oneOf($b['status'] ?? null, 'status', ['open', 'pending', 'resolved', 'closed']);
        DB::update('UPDATE tickets SET status=?, updated_at=? WHERE id=? AND tenant_id=?', [$status, H::now(), $ctx->params['id'], $ctx->tenantId]);
        H::audit($ctx, 'ticket.status_changed', 'ticket', $ctx->params['id'], ['status' => $status]);
        if ($ticket->assignee_id && $ticket->assignee_id !== $user['id']) {
            (new NotificationDispatcher())->notifyMember($ctx->tenantId, $ticket->assignee_id, 'info', 'Murojaat holati o‘zgardi', "{$ticket->subject}: {$status}", '/app/helpdesk');
        }
        return H::json(['ok' => true]);
    }

    // ── Communications ───────────────────────────────────────────────────────
    public function listCommunications(Ctx $ctx)
    {
        $ctx->auth();
        $t = $ctx->tenantId;
        [$limit, $page, $offset] = H::paginate($ctx);
        $channel = $ctx->query('channel');
        $where = ['c.tenant_id = ?']; $binds = [$t];
        if ($channel) { $where[] = 'c.channel = ?'; $binds[] = $channel; }
        $w = implode(' AND ', $where);
        $rows = DB::select(
            "SELECT c.*, org.name AS org_name, u.name AS user_name FROM communications c
               LEFT JOIN organizations org ON org.id=c.org_id LEFT JOIN users u ON u.id=c.user_id
              WHERE {$w} ORDER BY c.created_at DESC LIMIT ? OFFSET ?",
            [...$binds, $limit, $offset]
        );
        $total = DB::selectOne("SELECT COUNT(*) AS c FROM communications c WHERE {$w}", $binds);
        $byChannel = DB::select('SELECT channel, COUNT(*) AS count FROM communications WHERE tenant_id = ? GROUP BY channel ORDER BY count DESC', [$t]);
        return H::json(['data' => $rows, 'total' => (int) ($total->c ?? 0), 'page' => $page, 'limit' => $limit, 'byChannel' => $byChannel]);
    }

    public function sendCommunication(Ctx $ctx)
    {
        $user = $ctx->auth();
        H::requirePermission($ctx, 'crm.write');
        H::requireTenant($ctx);
        $b = H::body($ctx);
        $id = H::newId('com');
        // Validated before dispatching: a refused request must not have sent anything.
        $orgId = H::tenantRef($ctx, $b['org_id'] ?? null, 'organizations', 'kompaniya');
        $contactId = H::tenantRef($ctx, $b['contact_id'] ?? null, 'contacts', 'kontakt');
        $channel = H::oneOf($b['channel'] ?? null, 'channel', ['email', 'telegram', 'whatsapp', 'phone', 'portal'], 'email');
        $subject = H::str($b['subject'] ?? null, 'subject', false, 250);
        $body = H::str($b['body'] ?? null, 'body', false, 8000);
        $to = H::str($b['to'] ?? null, 'to', false, 190);
        $dispatcher = new NotificationDispatcher();
        $delivered = false;
        if ($channel === 'email') $delivered = $to ? $dispatcher->email($to, $subject ?: 'Nexio CRM', $body) : false;
        if ($channel === 'telegram') $delivered = $to ? $dispatcher->telegram($to, $body) : false;
        if ($channel === 'whatsapp') $delivered = $to ? $dispatcher->whatsapp($to, $body) : false;
        $status = in_array($channel, ['phone', 'portal'], true) ? 'recorded' : ($delivered ? 'sent' : 'pending');
        // No outbound provider is configured: the message is recorded rather than reported as delivered.
        DB::table('communications')->insert([
            'id' => $id, 'tenant_id' => $ctx->tenantId,
            'channel' => $channel,
            'direction' => 'outbound',
            'subject' => $subject,
            'body' => $body,
            'org_id' => $orgId, 'contact_id' => $contactId,
            'user_id' => $user['id'], 'status' => $status, 'created_at' => H::now(),
        ]);
        H::audit($ctx, 'communication.sent', 'communication', $id, ['channel' => $channel, 'delivered' => $delivered]);
        return H::json(['id' => $id, 'delivered' => $delivered, 'status' => $status, 'note' => $delivered ? 'Provider xabari yuborildi.' : 'Provider credentiallari yoki qabul qiluvchi manzili sozlanmagan; xabar jurnalga saqlandi.'], 201);
    }

    // ── Documents ────────────────────────────────────────────────────────────
    /** Generate a factual, human-reviewable proposal draft from an owned deal. */
    public function generateProposal(Ctx $ctx)
    {
        $user = $ctx->auth();
        H::requirePermission($ctx, 'crm.write');
        $body = H::body($ctx);
        $dealId = H::str($body['deal_id'] ?? null, 'deal_id');
        $deal = DB::selectOne(
            "SELECT d.*, o.name AS org_name, o.industry, c.first_name, c.last_name, c.email, c.phone
               FROM deals d LEFT JOIN organizations o ON o.id=d.org_id LEFT JOIN contacts c ON c.id=d.contact_id
              WHERE d.id = ? AND d.tenant_id = ?",
            [$dealId, $ctx->tenantId]
        );
        if (!$deal) throw new ApiException(404, 'Deal topilmadi', 'not_found');

        $products = DB::select(
            'SELECT name, description, price, currency, billing_type FROM products WHERE tenant_id = ? AND is_active = 1 ORDER BY name LIMIT 100',
            [$ctx->tenantId]
        );
        $facts = [
            'deal' => ['title' => $deal->title, 'amount' => $deal->amount, 'currency' => $deal->currency, 'probability' => $deal->probability, 'expected_close_at' => $deal->expected_close_at],
            'customer' => ['company' => $deal->org_name, 'industry' => $deal->industry, 'contact' => trim(($deal->first_name ?? '') . ' ' . ($deal->last_name ?? '')), 'email' => $deal->email, 'phone' => $deal->phone],
            'approved_catalog' => array_map(fn ($p) => (array) $p, $products),
        ];
        $prompt = "Siz Nexio CRM commercial proposal draft generatorisiz. Faqat quyidagi VERIFIED_FACTS ichidagi ma'lumotlardan foydalaning. Narx, chegirma, muddat, kafolat, legal shart yoki mahsulot mavjudligini o'ylab topmang. Yetishmayotgan fakt o'rniga [MISSING: ...] yozing. Hujjatni o'zbek tilida Markdown ko'rinishida yozing. Bu faqat DRAFT, yuborilmaydi va binding emas.\n\nVERIFIED_FACTS:\n" . json_encode($facts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $generated = (new GroqAi())->complete($prompt, $body['model'] ?? null);
        $content = trim((string) ($generated['content'] ?? ''));
        if ($content === '') throw new ApiException(502, 'AI proposal matni bo‘sh qaytdi', 'ai_invalid_response');

        $name = H::str($body['name'] ?? null, 'name', false, 250) ?: 'Commercial proposal — ' . $deal->title;
        $versionRow = DB::selectOne('SELECT COALESCE(MAX(version),0) AS v FROM documents WHERE tenant_id=? AND source_deal_id=?', [$ctx->tenantId, $dealId]);
        $nextVersion = (int) ($versionRow->v ?? 0) + 1;
        $id = H::newId('doc');
        DB::table('documents')->insert([
            'id' => $id, 'tenant_id' => $ctx->tenantId, 'name' => $name, 'kind' => 'proposal',
            'org_id' => $deal->org_id, 'order_id' => null, 'version' => $nextVersion, 'size_kb' => (int) ceil(strlen($content) / 1024),
            'status' => 'draft', 'created_by' => $user['id'], 'created_at' => H::now(), 'content' => $content,
            'source_deal_id' => $dealId, 'updated_at' => H::now(),
        ]);
        H::audit($ctx, 'document.proposal_generated', 'document', $id, ['deal_id' => $dealId, 'version' => $nextVersion]);
        return H::json(['id' => $id, 'version' => $nextVersion, 'content' => $content, 'draft' => true, 'model' => $generated['model'] ?? null], 201);
    }

    public function listDocuments(Ctx $ctx)
    {
        $ctx->auth();
        $t = $ctx->tenantId;
        $kind = $ctx->query('kind');
        $where = ['d.tenant_id = ?']; $binds = [$t];
        if ($kind) { $where[] = 'd.kind = ?'; $binds[] = $kind; }
        $rows = DB::select(
            'SELECT d.id, d.tenant_id, d.name, d.kind, d.org_id, d.order_id, d.version, d.size_kb, d.status,
                    d.created_by, d.created_at, d.source_deal_id, d.approved_at, d.approved_by, d.updated_at,
                    org.name AS org_name, u.name AS created_by_name FROM documents d
               LEFT JOIN organizations org ON org.id=d.org_id LEFT JOIN users u ON u.id=d.created_by
              WHERE ' . implode(' AND ', $where) . ' ORDER BY d.created_at DESC LIMIT 200',
            $binds
        );
        $stats = DB::selectOne("SELECT COUNT(*) AS total, SUM(CASE WHEN status='signed' THEN 1 ELSE 0 END) AS signed, SUM(CASE WHEN status='draft' THEN 1 ELSE 0 END) AS draft FROM documents WHERE tenant_id = ?", [$t]);
        return H::json(['data' => $rows, 'stats' => $stats]);
    }

    public function getDocument(Ctx $ctx)
    {
        $ctx->auth();
        $row = DB::selectOne(
            'SELECT d.*, org.name AS org_name, u.name AS created_by_name FROM documents d LEFT JOIN organizations org ON org.id=d.org_id LEFT JOIN users u ON u.id=d.created_by WHERE d.id=? AND d.tenant_id=?',
            [$ctx->params['id'], $ctx->tenantId]
        );
        if (!$row) throw new ApiException(404, 'Dokument topilmadi', 'not_found');
        return H::json(['data' => (array) $row]);
    }

    public function createDocument(Ctx $ctx)
    {
        $user = $ctx->auth();
        H::requirePermission($ctx, 'crm.write');
        H::requireTenant($ctx);
        $b = H::body($ctx);
        $id = H::newId('doc');
        DB::table('documents')->insert([
            'id' => $id, 'tenant_id' => $ctx->tenantId,
            'name' => H::str($b['name'] ?? null, 'name', true, 250),
            'kind' => H::oneOf($b['kind'] ?? null, 'kind', ['proposal', 'contract', 'invoice', 'act', 'other'], 'proposal'),
            'org_id' => H::tenantRef($ctx, $b['org_id'] ?? null, 'organizations', 'kompaniya'),
            'order_id' => H::tenantRef($ctx, $b['order_id'] ?? null, 'orders', 'buyurtma'),
            'version' => 1, 'size_kb' => 0, 'status' => 'draft',
            'created_by' => $user['id'], 'created_at' => H::now(), 'updated_at' => H::now(),
        ]);
        H::audit($ctx, 'document.created', 'document', $id);
        return H::json(['id' => $id], 201);
    }

    public function setDocumentStatus(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'crm.write');
        if (!$this->owned($ctx, 'documents', $ctx->params['id'])) throw new ApiException(404, 'Документ не найден', 'not_found');
        $b = H::body($ctx);
        $status = H::oneOf($b['status'] ?? null, 'status', ['draft', 'approved', 'sent', 'signed', 'archived']);
        $approvedAt = $status === 'approved' ? H::now() : null;
        $approvedBy = $status === 'approved' ? ($ctx->user['id'] ?? null) : null;
        DB::update('UPDATE documents SET status=?, approved_at=?, approved_by=?, updated_at=? WHERE id=? AND tenant_id=?', [$status, $approvedAt, $approvedBy, H::now(), $ctx->params['id'], $ctx->tenantId]);
        H::audit($ctx, 'document.status_changed', 'document', $ctx->params['id'], ['status' => $status]);
        return H::json(['ok' => true]);
    }

    public function updateDocumentContent(Ctx $ctx)
    {
        $user = $ctx->auth();
        H::requirePermission($ctx, 'crm.write');
        if (!$this->owned($ctx, 'documents', $ctx->params['id'])) throw new ApiException(404, 'Dokument topilmadi', 'not_found');
        $body = H::body($ctx);
        $content = H::str($body['content'] ?? null, 'content', true, 60000);
        DB::update('UPDATE documents SET content=?, size_kb=?, name=COALESCE(?,name), updated_at=? WHERE id=? AND tenant_id=?', [
            $content, (int) ceil(strlen($content) / 1024), H::str($body['name'] ?? null, 'name', false, 250), H::now(), $ctx->params['id'], $ctx->tenantId,
        ]);
        H::audit($ctx, 'document.content_updated', 'document', $ctx->params['id'], ['updated_by' => $user['id']]);
        return H::json(['ok' => true]);
    }

    // ── Automations ──────────────────────────────────────────────────────────
    public function listAutomations(Ctx $ctx)
    {
        $ctx->auth();
        $t = $ctx->tenantId;
        $rows = DB::select('SELECT * FROM automations WHERE tenant_id = ? ORDER BY created_at DESC', [$t]);
        $runs = DB::selectOne("SELECT COUNT(*) AS total FROM activities WHERE tenant_id = ? AND kind='system'", [$t]);
        $data = array_map(fn ($r) => [...(array) $r, 'conditions' => H::parseJson($r->conditions, []), 'actions' => H::parseJson($r->actions, [])], $rows);
        return H::json([
            'data' => $data,
            'stats' => [
                'total' => count($rows),
                'active' => count(array_filter($rows, fn ($r) => $r->status === 'active')),
                'runs' => (int) ($runs->total ?? 0),
            ],
        ]);
    }

    public function createAutomation(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'settings.write');
        $b = H::body($ctx);
        $id = H::newId('atm');
        DB::table('automations')->insert([
            'id' => $id, 'tenant_id' => $ctx->tenantId,
            'name' => H::str($b['name'] ?? null, 'name', true, 200),
            'trigger_key' => H::str($b['trigger_key'] ?? null, 'trigger_key', true, 60),
            'conditions' => json_encode($b['conditions'] ?? [], JSON_UNESCAPED_UNICODE),
            'actions' => json_encode($b['actions'] ?? [], JSON_UNESCAPED_UNICODE),
            'status' => 'active', 'runs_count' => 0, 'created_at' => H::now(),
        ]);
        H::audit($ctx, 'automation.created', 'automation', $id);
        return H::json(['id' => $id], 201);
    }

    public function toggleAutomation(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'settings.write');
        if (!$this->owned($ctx, 'automations', $ctx->params['id'])) throw new ApiException(404, 'Автоматизация не найдена', 'not_found');
        $b = H::body($ctx);
        $status = H::oneOf($b['status'] ?? null, 'status', ['active', 'paused']);
        DB::update('UPDATE automations SET status=? WHERE id=? AND tenant_id=?', [$status, $ctx->params['id'], $ctx->tenantId]);
        H::audit($ctx, 'automation.toggled', 'automation', $ctx->params['id'], ['status' => $status]);
        return H::json(['ok' => true]);
    }

    /** Dry-run: returns which of the tenant's current records the rule matches. */
    public function dryRunAutomation(Ctx $ctx)
    {
        $ctx->auth();
        $t = $ctx->tenantId;
        $trigger = (string) $ctx->query('trigger', '');
        $map = [
            'lead.created' => 'SELECT COUNT(*) AS c FROM leads WHERE tenant_id = ?',
            'deal.won' => "SELECT COUNT(*) AS c FROM deals WHERE tenant_id = ? AND status='won'",
            'order.paid' => "SELECT COUNT(*) AS c FROM orders WHERE tenant_id = ? AND payment_status='paid'",
            'no_reply.2d' => "SELECT COUNT(*) AS c FROM deals WHERE tenant_id = ? AND status='open' AND updated_at < " . SqlDialect::ago(2, 'day'),
            'task.overdue' => "SELECT COUNT(*) AS c FROM tasks WHERE tenant_id = ? AND status!='done' AND due_date < CURRENT_DATE",
        ];
        if (!isset($map[$trigger])) return H::json(['matches' => 0, 'note' => 'Триггер не поддерживает предпросмотр']);
        $row = DB::selectOne($map[$trigger], [$t]);
        return H::json(['matches' => (int) ($row->c ?? 0)]);
    }

    // ── Integrations ─────────────────────────────────────────────────────────
    public function listIntegrations(Ctx $ctx)
    {
        $ctx->auth();
        $t = $ctx->tenantId;
        $rows = DB::select('SELECT * FROM integrations WHERE tenant_id = ? ORDER BY provider', [$t]);
        $hooks = DB::select('SELECT id, event, status, attempts, created_at FROM webhook_events WHERE tenant_id = ? ORDER BY created_at DESC LIMIT 25', [$t]);
        $hookStats = DB::selectOne("SELECT COUNT(*) AS total, SUM(CASE WHEN status='delivered' THEN 1 ELSE 0 END) AS delivered, SUM(CASE WHEN status!='delivered' THEN 1 ELSE 0 END) AS failing FROM webhook_events WHERE tenant_id = ?", [$t]);
        $apiUsage = DB::selectOne("SELECT quantity, limit_value, period FROM usage_records WHERE tenant_id = ? AND metric='api_requests' ORDER BY period DESC LIMIT 1", [$t]);
        $data = array_map(fn ($r) => [...(array) $r, 'config' => H::parseJson($r->config, [])], $rows);
        return H::json(['data' => $data, 'webhooks' => $hooks, 'hookStats' => $hookStats, 'apiUsage' => $apiUsage]);
    }

    public function toggleIntegration(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'integrations.write');
        if (!$this->owned($ctx, 'integrations', $ctx->params['id'])) throw new ApiException(404, 'Интеграция не найдена', 'not_found');
        $b = H::body($ctx);
        $status = H::oneOf($b['status'] ?? null, 'status', ['available', 'connected', 'sandbox', 'error']);
        $mode = $status === 'connected' ? 'live' : 'sandbox';
        DB::update('UPDATE integrations SET status=?, mode=?, connected_at=?, last_error=? WHERE id=? AND tenant_id=?', [
            $status, $mode, $status === 'connected' ? H::now() : null, null, $ctx->params['id'], $ctx->tenantId,
        ]);
        H::audit($ctx, 'integration.toggled', 'integration', $ctx->params['id'], ['status' => $status]);
        return H::json(['ok' => true, 'mode' => $mode, 'note' => $mode === 'sandbox' ? 'Sandbox-режим: реальный провайдер не подключён.' : 'Интеграция подключена.']);
    }

    // ── Nexio services ───────────────────────────────────────────────────────
    public function listNexioServices(Ctx $ctx)
    {
        $ctx->auth();
        $t = $ctx->tenantId;
        $rows = DB::select('SELECT * FROM nexio_service_requests WHERE tenant_id = ? ORDER BY created_at DESC LIMIT 100', [$t]);
        $stats = DB::selectOne(
            "SELECT COUNT(*) AS total, SUM(CASE WHEN status='in_progress' THEN 1 ELSE 0 END) AS in_progress,
                    SUM(CASE WHEN status='delivered' THEN 1 ELSE 0 END) AS delivered,
                    SUM(CASE WHEN status='submitted' THEN 1 ELSE 0 END) AS submitted
               FROM nexio_service_requests WHERE tenant_id = ?",
            [$t]
        );
        return H::json([
            'data' => $rows, 'stats' => $stats,
            'disclosure' => 'Заявка передаётся во внутреннюю очередь Nexio Lab. Компания-подписчик остаётся владельцем своих данных, доступ к её рабочему пространству не предоставляется.',
        ]);
    }

    public function createNexioService(Ctx $ctx)
    {
        $user = $ctx->auth();
        H::requirePermission($ctx, 'services.request');
        $b = H::body($ctx);
        $id = H::newId('nsr');
        DB::table('nexio_service_requests')->insert([
            'id' => $id, 'tenant_id' => $ctx->tenantId,
            'service' => H::oneOf($b['service'] ?? null, 'service', ['website', 'crm', 'android', 'windows', 'api', 'ai_agent', 'support']),
            'title' => H::str($b['title'] ?? null, 'title', true, 250),
            'description' => H::str($b['description'] ?? null, 'description', false, 4000),
            'budget' => H::num($b['budget'] ?? null, 'budget', false, 0),
            'currency' => H::str($b['currency'] ?? null, 'currency', false, 8) ?? 'UZS',
            'status' => 'submitted',
            'priority' => H::oneOf($b['priority'] ?? null, 'priority', ['low', 'normal', 'high'], 'normal'),
            'created_by' => $user['id'], 'created_at' => H::now(), 'updated_at' => H::now(),
        ]);
        H::audit($ctx, 'nexio_service.requested', 'nexio_service_request', $id, ['service' => $b['service'] ?? null]);
        return H::json(['id' => $id], 201);
    }
}
