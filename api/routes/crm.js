import { audit, body, json, newId, notFound, now, oneOf, paginate, requireAuth, requirePermission, str, } from '../core';
import { isSuppressed } from './ai';
const ORDER_COLS = {
    organizations: { name: 'name', created: 'created_at', industry: 'industry' },
    contacts: { name: 'first_name', created: 'created_at', company: 'org_id' },
    leads: { created: 'created_at', score: 'score', value: 'value', status: 'status' },
    deals: { created: 'created_at', amount: 'amount', close: 'expected_close_at', probability: 'probability' },
};
function orderBy(table, url, fallback) {
    const key = url.searchParams.get('sort') || '';
    const dir = (url.searchParams.get('dir') || 'desc').toLowerCase() === 'asc' ? 'ASC' : 'DESC';
    const col = ORDER_COLS[table]?.[key];
    return col ? `${col} ${dir}` : fallback;
}
async function exists(ctx, table, id) {
    const row = await ctx.env.DB.prepare(`SELECT 1 AS x FROM ${table} WHERE id = ? AND tenant_id = ?`).bind(id, ctx.tenantId).first();
    return !!row;
}
export const crmRoutes = {
    // ── Organizations (companies) ────────────────────────────────────────────
    async listOrganizations(ctx) {
        requireAuth(ctx);
        const t = ctx.tenantId;
        const { limit, page, offset } = paginate(ctx.url);
        const q = ctx.url.searchParams.get('q');
        const status = ctx.url.searchParams.get('status');
        const tier = ctx.url.searchParams.get('tier');
        const where = ['o.tenant_id = ?'];
        const binds = [t];
        if (q) {
            where.push('(o.name LIKE ? OR o.email LIKE ? OR o.industry LIKE ?)');
            binds.push(`%${q}%`, `%${q}%`, `%${q}%`);
        }
        if (status) {
            where.push('o.status = ?');
            binds.push(status);
        }
        if (tier) {
            where.push('o.tier = ?');
            binds.push(tier);
        }
        const sql = `SELECT o.*, u.name AS owner_name, u.avatar_color AS owner_color,
                   (SELECT COUNT(*) FROM contacts c WHERE c.org_id = o.id) AS contacts_count,
                   (SELECT COUNT(*) FROM deals d WHERE d.org_id = o.id AND d.status='open') AS open_deals,
                   (SELECT COALESCE(SUM(d.amount),0) FROM deals d WHERE d.org_id = o.id AND d.status='won') AS won_value,
                   (SELECT COALESCE(SUM(ord.amount),0) FROM orders ord WHERE ord.org_id = o.id AND ord.status='fulfilled') AS revenue,
                   (SELECT MAX(a.created_at) FROM activities a WHERE a.entity_id = o.id) AS last_activity
              FROM organizations o LEFT JOIN users u ON u.id = o.owner_id
             WHERE ${where.join(' AND ')}
             ORDER BY ${orderBy('organizations', ctx.url, 'o.created_at DESC')} LIMIT ? OFFSET ?`;
        const rows = await ctx.env.DB.prepare(sql).bind(...binds, limit, offset).all();
        const total = await ctx.env.DB.prepare(`SELECT COUNT(*) AS c FROM organizations o WHERE ${where.join(' AND ')}`).bind(...binds).first();
        return json({ data: rows.results, total: total?.c ?? 0, page, limit });
    },
    async getOrganization(ctx) {
        requireAuth(ctx);
        const t = ctx.tenantId;
        const id = ctx.params.id;
        const org = await ctx.env.DB.prepare('SELECT * FROM organizations WHERE id = ? AND tenant_id = ?').bind(id, t).first();
        if (!org)
            throw notFound('Компания не найдена');
        const [contacts, deals, orders, activity, projects, tickets] = await Promise.all([
            ctx.env.DB.prepare('SELECT * FROM contacts WHERE org_id = ? ORDER BY is_primary DESC, first_name').bind(id).all(),
            ctx.env.DB.prepare('SELECT d.*, s.name AS stage_name, s.color AS stage_color FROM deals d LEFT JOIN pipeline_stages s ON s.id = d.stage_id WHERE d.org_id = ? ORDER BY d.created_at DESC LIMIT 20').bind(id).all(),
            ctx.env.DB.prepare('SELECT * FROM orders WHERE org_id = ? ORDER BY created_at DESC LIMIT 10').bind(id).all(),
            ctx.env.DB.prepare("SELECT id, kind, subject, actor_name, created_at FROM activities WHERE tenant_id=? AND ((entity_type='organization' AND entity_id=?) OR entity_id IN (SELECT id FROM deals WHERE org_id=?)) ORDER BY created_at DESC LIMIT 20").bind(t, id, id).all(),
            ctx.env.DB.prepare('SELECT id, name, status, progress, due_date FROM projects WHERE org_id = ? LIMIT 10').bind(id).all(),
            ctx.env.DB.prepare('SELECT id, number, subject, status, priority FROM tickets WHERE org_id = ? LIMIT 10').bind(id).all(),
        ]);
        return json({ ...org, tags: JSON.parse(org.tags || '[]'), contacts: contacts.results, deals: deals.results, orders: orders.results, activity: activity.results, projects: projects.results, tickets: tickets.results });
    },
    async createOrganization(ctx) {
        requireAuth(ctx);
        requirePermission(ctx, 'crm.write');
        const b = await body(ctx);
        const id = newId('org');
        await ctx.env.DB.prepare(`INSERT INTO organizations (id,tenant_id,name,industry,website,email,phone,country,city,address,size,status,tier,owner_id,tags,created_at,updated_at)
       VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)`).bind(id, ctx.tenantId, str(b.name, 'name', { max: 200 }), str(b.industry, 'industry', { required: false, max: 120 }), str(b.website, 'website', { required: false, max: 200 }), str(b.email, 'email', { required: false, max: 190 }), str(b.phone, 'phone', { required: false, max: 40 }), str(b.country, 'country', { required: false, max: 80 }), str(b.city, 'city', { required: false, max: 80 }), str(b.address, 'address', { required: false, max: 250 }), str(b.size, 'size', { required: false, max: 40 }), oneOf(b.status, 'status', ['active', 'prospect', 'churned'], 'prospect'), oneOf(b.tier, 'tier', ['vip', 'standard', 'basic'], 'standard'), b.owner_id ?? ctx.user.id, JSON.stringify(Array.isArray(b.tags) ? b.tags : []), now(), now()).run();
        await audit(ctx, 'organization.created', 'organization', id, { name: b.name });
        return json({ id }, 201);
    },
    async updateOrganization(ctx) {
        requireAuth(ctx);
        requirePermission(ctx, 'crm.write');
        if (!(await exists(ctx, 'organizations', ctx.params.id)))
            throw notFound('Компания не найдена');
        const b = await body(ctx);
        await ctx.env.DB.prepare('UPDATE organizations SET name=COALESCE(?,name), industry=?, website=?, email=?, phone=?, country=?, city=?, address=?, size=?, status=COALESCE(?,status), tier=COALESCE(?,tier), owner_id=?, notes=?, updated_at=? WHERE id=? AND tenant_id=?').bind(str(b.name, 'name', { required: false, max: 200 }), str(b.industry, 'industry', { required: false, max: 120 }), str(b.website, 'website', { required: false, max: 200 }), str(b.email, 'email', { required: false, max: 190 }), str(b.phone, 'phone', { required: false, max: 40 }), str(b.country, 'country', { required: false, max: 80 }), str(b.city, 'city', { required: false, max: 80 }), str(b.address, 'address', { required: false, max: 250 }), str(b.size, 'size', { required: false, max: 40 }), b.status ?? null, b.tier ?? null, b.owner_id ?? null, str(b.notes, 'notes', { required: false, max: 2000 }), now(), ctx.params.id, ctx.tenantId).run();
        await audit(ctx, 'organization.updated', 'organization', ctx.params.id, null);
        return json({ ok: true });
    },
    async deleteOrganization(ctx) {
        requireAuth(ctx);
        requirePermission(ctx, 'crm.delete');
        if (!(await exists(ctx, 'organizations', ctx.params.id)))
            throw notFound('Компания не найдена');
        await ctx.env.DB.prepare('DELETE FROM organizations WHERE id = ? AND tenant_id = ?').bind(ctx.params.id, ctx.tenantId).run();
        await audit(ctx, 'organization.deleted', 'organization', ctx.params.id, null);
        return json({ ok: true });
    },
    // ── Contacts ─────────────────────────────────────────────────────────────
    async listContacts(ctx) {
        requireAuth(ctx);
        const t = ctx.tenantId;
        const { limit, page, offset } = paginate(ctx.url);
        const q = ctx.url.searchParams.get('q');
        const status = ctx.url.searchParams.get('status');
        const orgId = ctx.url.searchParams.get('org_id');
        const where = ['c.tenant_id = ?'];
        const binds = [t];
        if (q) {
            where.push('(c.first_name LIKE ? OR c.last_name LIKE ? OR c.email LIKE ? OR c.position LIKE ?)');
            binds.push(`%${q}%`, `%${q}%`, `%${q}%`, `%${q}%`);
        }
        if (status) {
            where.push('c.status = ?');
            binds.push(status);
        }
        if (orgId) {
            where.push('c.org_id = ?');
            binds.push(orgId);
        }
        const rows = await ctx.env.DB.prepare(`SELECT c.*, o.name AS org_name, u.name AS owner_name FROM contacts c
         LEFT JOIN organizations o ON o.id = c.org_id
         LEFT JOIN users u ON u.id = c.owner_id
        WHERE ${where.join(' AND ')}
        ORDER BY ${orderBy('contacts', ctx.url, 'c.created_at DESC')} LIMIT ? OFFSET ?`).bind(...binds, limit, offset).all();
        const total = await ctx.env.DB.prepare(`SELECT COUNT(*) AS c FROM contacts c WHERE ${where.join(' AND ')}`).bind(...binds).first();
        return json({ data: rows.results.map((r) => ({ ...r, tags: JSON.parse(r.tags || '[]') })), total: total?.c ?? 0, page, limit });
    },
    async createContact(ctx) {
        requireAuth(ctx);
        requirePermission(ctx, 'crm.write');
        const b = await body(ctx);
        const id = newId('con');
        const phoneVal = str(b.phone, 'phone', { required: false, max: 40 });
        await ctx.env.DB.prepare('INSERT INTO contacts (id,tenant_id,org_id,first_name,last_name,position,email,phone,telegram,source,status,is_primary,consent_marketing,consent_calls,owner_id,tags,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)').bind(id, ctx.tenantId, b.org_id ?? null, str(b.first_name, 'first_name', { max: 80 }), str(b.last_name, 'last_name', { required: false, max: 80 }), str(b.position, 'position', { required: false, max: 120 }), str(b.email, 'email', { required: false, max: 190 }), phoneVal, str(b.telegram, 'telegram', { required: false, max: 60 }), str(b.source, 'source', { required: false, max: 60 }), oneOf(b.status, 'status', ['active', 'lead', 'inactive', 'do_not_contact'], 'active'), b.is_primary ? 1 : 0, b.consent_marketing ? 1 : 0, b.consent_calls ? 1 : 0, b.owner_id ?? ctx.user.id, JSON.stringify(Array.isArray(b.tags) ? b.tags : []), now(), now()).run();
        if (phoneVal && (await isSuppressed(ctx, phoneVal))) {
            await ctx.env.DB.prepare('UPDATE contacts SET status = ? WHERE id = ?').bind('do_not_contact', id).run();
        }
        await audit(ctx, 'contact.created', 'contact', id, null);
        return json({ id }, 201);
    },
    async updateContact(ctx) {
        requireAuth(ctx);
        requirePermission(ctx, 'crm.write');
        if (!(await exists(ctx, 'contacts', ctx.params.id)))
            throw notFound('Контакт не найден');
        const b = await body(ctx);
        await ctx.env.DB.prepare('UPDATE contacts SET first_name=COALESCE(?,first_name), last_name=?, position=?, email=?, phone=?, telegram=?, source=?, status=COALESCE(?,status), org_id=?, consent_marketing=COALESCE(?,consent_marketing), consent_calls=COALESCE(?,consent_calls), owner_id=?, updated_at=? WHERE id=? AND tenant_id=?').bind(str(b.first_name, 'first_name', { required: false, max: 80 }), str(b.last_name, 'last_name', { required: false, max: 80 }), str(b.position, 'position', { required: false, max: 120 }), str(b.email, 'email', { required: false, max: 190 }), str(b.phone, 'phone', { required: false, max: 40 }), str(b.telegram, 'telegram', { required: false, max: 60 }), str(b.source, 'source', { required: false, max: 60 }), b.status ?? null, b.org_id ?? null, b.consent_marketing === undefined ? null : (b.consent_marketing ? 1 : 0), b.consent_calls === undefined ? null : (b.consent_calls ? 1 : 0), b.owner_id ?? null, now(), ctx.params.id, ctx.tenantId).run();
        await audit(ctx, 'contact.updated', 'contact', ctx.params.id, null);
        return json({ ok: true });
    },
    async deleteContact(ctx) {
        requireAuth(ctx);
        requirePermission(ctx, 'crm.delete');
        if (!(await exists(ctx, 'contacts', ctx.params.id)))
            throw notFound('Контакт не найден');
        await ctx.env.DB.prepare('DELETE FROM contacts WHERE id = ? AND tenant_id = ?').bind(ctx.params.id, ctx.tenantId).run();
        await audit(ctx, 'contact.deleted', 'contact', ctx.params.id, null);
        return json({ ok: true });
    },
};
