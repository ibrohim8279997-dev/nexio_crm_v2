import { audit, badRequest, body, json, newId, notFound, now, notifyUser, num, oneOf, paginate, requireAuth, requirePermission, str, } from '../core';
const ORDER_STATES = ['draft', 'confirmed', 'in_progress', 'fulfilled', 'cancelled', 'refunded'];
const PAY_STATES = ['unpaid', 'partial', 'paid', 'overdue'];
const PROJECT_STATES = ['planning', 'active', 'on_hold', 'review', 'done', 'cancelled'];
const TASK_STATES = ['todo', 'in_progress', 'review', 'done'];
const PRIORITIES = ['low', 'medium', 'high', 'critical'];
async function owned(ctx, table, id) {
    return !!(await ctx.env.DB.prepare(`SELECT 1 AS x FROM ${table} WHERE id = ? AND tenant_id = ?`).bind(id, ctx.tenantId).first());
}
export const orderRoutes = {
    async list(ctx) {
        requireAuth(ctx);
        const t = ctx.tenantId;
        const { limit, page, offset } = paginate(ctx.url);
        const status = ctx.url.searchParams.get('status');
        const pay = ctx.url.searchParams.get('payment_status');
        const q = ctx.url.searchParams.get('q');
        const where = ['o.tenant_id = ?'];
        const binds = [t];
        if (status) {
            where.push('o.status = ?');
            binds.push(status);
        }
        if (pay) {
            where.push('o.payment_status = ?');
            binds.push(pay);
        }
        if (q) {
            where.push('(o.number LIKE ? OR org.name LIKE ?)');
            binds.push(`%${q}%`, `%${q}%`);
        }
        const rows = await ctx.env.DB.prepare(`SELECT o.*, org.name AS org_name, u.name AS created_by_name,
              (SELECT COUNT(*) FROM order_items i WHERE i.order_id = o.id) AS items_count
         FROM orders o LEFT JOIN organizations org ON org.id = o.org_id LEFT JOIN users u ON u.id = o.created_by
        WHERE ${where.join(' AND ')} ORDER BY o.created_at DESC LIMIT ? OFFSET ?`).bind(...binds, limit, offset).all();
        const total = await ctx.env.DB.prepare(`SELECT COUNT(*) AS c FROM orders o LEFT JOIN organizations org ON org.id=o.org_id WHERE ${where.join(' AND ')}`).bind(...binds).first();
        const stats = await ctx.env.DB.prepare(`SELECT COUNT(*) AS total,
              SUM(CASE WHEN status='draft' THEN 1 ELSE 0 END) AS draft,
              SUM(CASE WHEN status='confirmed' THEN 1 ELSE 0 END) AS confirmed,
              SUM(CASE WHEN status='in_progress' THEN 1 ELSE 0 END) AS in_progress,
              SUM(CASE WHEN status='fulfilled' THEN 1 ELSE 0 END) AS fulfilled,
              SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) AS cancelled,
              COALESCE(SUM(CASE WHEN status='fulfilled' THEN amount END),0) AS fulfilled_value,
              COALESCE(SUM(CASE WHEN payment_status IN ('unpaid','overdue','partial') THEN amount END),0) AS receivable
         FROM orders WHERE tenant_id = ?`).bind(t).first();
        return json({ data: rows.results, total: total?.c ?? 0, page, limit, stats });
    },
    async get(ctx) {
        requireAuth(ctx);
        const t = ctx.tenantId;
        const order = await ctx.env.DB.prepare(`SELECT o.*, org.name AS org_name, org.email AS org_email, u.name AS created_by_name
         FROM orders o LEFT JOIN organizations org ON org.id=o.org_id LEFT JOIN users u ON u.id=o.created_by
        WHERE o.id = ? AND o.tenant_id = ?`).bind(ctx.params.id, t).first();
        if (!order)
            throw notFound('Заказ не найден');
        const items = await ctx.env.DB.prepare('SELECT * FROM order_items WHERE order_id = ?').bind(ctx.params.id).all();
        const deal = order.deal_id ? await ctx.env.DB.prepare('SELECT id, title, status FROM deals WHERE id = ?').bind(order.deal_id).first() : null;
        const project = await ctx.env.DB.prepare('SELECT id, name, status, progress FROM projects WHERE order_id = ?').bind(ctx.params.id).first();
        return json({ ...order, snapshot: order.snapshot ? JSON.parse(order.snapshot) : null, items: items.results, deal, project });
    },
    async create(ctx) {
        requireAuth(ctx);
        requirePermission(ctx, 'orders.write');
        const t = ctx.tenantId;
        const b = await body(ctx);
        const id = newId('ord');
        const year = new Date().getFullYear();
        const countRow = await ctx.env.DB.prepare('SELECT COUNT(*) AS c FROM orders WHERE tenant_id = ?').bind(t).first();
        const number = `ORD-${year}-${String(1000 + (countRow?.c ?? 0) + 1).slice(-4)}`;
        const rawItems = Array.isArray(b.items) ? b.items : [];
        const items = [];
        for (const it of rawItems) {
            const prod = it.product_id ? await ctx.env.DB.prepare('SELECT id, name, price FROM products WHERE id = ? AND tenant_id = ?').bind(it.product_id, t).first() : null;
            const qty = num(it.qty, 'qty', { min: 0 }) ?? 1;
            const price = num(it.price, 'price', { min: 0 }) ?? prod?.price ?? 0;
            items.push({ product_id: prod?.id ?? null, name: prod?.name ?? String(it.name ?? 'Позиция'), qty, price, total: qty * price });
        }
        const amount = items.reduce((a, i) => a + i.total, 0);
        await ctx.env.DB.prepare('INSERT INTO orders (id,tenant_id,number,org_id,contact_id,deal_id,status,amount,currency,source,payment_status,notes,created_by,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)').bind(id, t, number, b.org_id ?? null, b.contact_id ?? null, b.deal_id ?? null, oneOf(b.status, 'status', ORDER_STATES, 'draft'), amount, str(b.currency, 'currency', { required: false, max: 8 }) ?? 'UZS', oneOf(b.source, 'source', ['manual', 'integration', 'portal', 'ai_agent'], 'manual'), oneOf(b.payment_status, 'payment_status', PAY_STATES, 'unpaid'), str(b.notes, 'notes', { required: false, max: 2000 }), ctx.user.id, now(), now()).run();
        if (items.length) {
            await ctx.env.DB.batch(items.map((i) => ctx.env.DB.prepare('INSERT INTO order_items (id,tenant_id,order_id,product_id,name,qty,price,total) VALUES (?,?,?,?,?,?,?,?)')
                .bind(newId('oit'), t, id, i.product_id, i.name, i.qty, i.price, i.total)));
        }
        await audit(ctx, 'order.created', 'order', id, { number, amount });
        return json({ id, number, amount }, 201);
    },
    /** Confirmation freezes the commercial terms into a snapshot. */
    async confirm(ctx) {
        requireAuth(ctx);
        requirePermission(ctx, 'orders.write');
        const t = ctx.tenantId;
        const order = await ctx.env.DB.prepare('SELECT id, status, amount, currency FROM orders WHERE id = ? AND tenant_id = ?').bind(ctx.params.id, t).first();
        if (!order)
            throw notFound('Заказ не найден');
        if (order.status !== 'draft')
            throw badRequest('Подтвердить можно только черновик заказа');
        const items = await ctx.env.DB.prepare('SELECT name, qty, price, total FROM order_items WHERE order_id = ?').bind(ctx.params.id).all();
        const snapshot = { terms: 'Оплата 50% предоплата, 50% по факту', currency: order.currency, tax_rate: 12, captured_at: now(), items: items.results };
        await ctx.env.DB.batch([
            ctx.env.DB.prepare("UPDATE orders SET status='confirmed', confirmed_at=?, snapshot=?, updated_at=? WHERE id=? AND tenant_id=?").bind(now(), JSON.stringify(snapshot), now(), ctx.params.id, t),
            ctx.env.DB.prepare('INSERT INTO activities (id,tenant_id,kind,subject,entity_type,entity_id,actor_id,actor_name,created_at) VALUES (?,?,?,?,?,?,?,?,?)')
                .bind(newId('act'), t, 'system', 'Заказ подтверждён, условия зафиксированы', 'order', ctx.params.id, ctx.user.id, ctx.user.name, now()),
        ]);
        await audit(ctx, 'order.confirmed', 'order', ctx.params.id, { amount: order.amount });
        return json({ ok: true, snapshot });
    },
    async updateStatus(ctx) {
        requireAuth(ctx);
        requirePermission(ctx, 'orders.write');
        if (!(await owned(ctx, 'orders', ctx.params.id)))
            throw notFound('Заказ не найден');
        const b = await body(ctx);
        const status = oneOf(b.status, 'status', ORDER_STATES);
        const pay = b.payment_status ? oneOf(b.payment_status, 'payment_status', PAY_STATES) : null;
        await ctx.env.DB.prepare('UPDATE orders SET status=?, payment_status=COALESCE(?,payment_status), updated_at=? WHERE id=? AND tenant_id=?')
            .bind(status, pay, now(), ctx.params.id, ctx.tenantId).run();
        await audit(ctx, 'order.status_changed', 'order', ctx.params.id, { status, payment_status: pay });
        return json({ ok: true });
    },
    /** Order → project. Idempotent: an existing project for the order is returned. */
    async createProject(ctx) {
        requireAuth(ctx);
        requirePermission(ctx, 'projects.write');
        const t = ctx.tenantId;
        const order = await ctx.env.DB.prepare('SELECT * FROM orders WHERE id = ? AND tenant_id = ?').bind(ctx.params.id, t).first();
        if (!order)
            throw notFound('Заказ не найден');
        const existing = await ctx.env.DB.prepare('SELECT id, name FROM projects WHERE order_id = ?').bind(ctx.params.id).first();
        if (existing)
            return json({ id: existing.id, name: existing.name, existing: true });
        const id = newId('prj');
        await ctx.env.DB.prepare('INSERT INTO projects (id,tenant_id,name,org_id,order_id,status,priority,progress,budget,currency,manager_id,start_date,due_date,description,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)').bind(id, t, `Проект по заказу ${order.number}`, order.org_id, order.id, 'planning', 'medium', 0, order.amount, order.currency, ctx.user.id, new Date().toISOString().slice(0, 10), new Date(Date.now() + 60 * 864e5).toISOString().slice(0, 10), `Реализация заказа ${order.number}.`, now(), now()).run();
        await ctx.env.DB.batch([
            ctx.env.DB.prepare('UPDATE orders SET project_id = ?, updated_at = ? WHERE id = ?').bind(id, now(), ctx.params.id),
            ...['Аналитика и ТЗ', 'Дизайн', 'Разработка', 'Тестирование', 'Запуск'].map((name, i) => ctx.env.DB.prepare('INSERT INTO project_milestones (id,tenant_id,project_id,name,status,due_date,position) VALUES (?,?,?,?,?,?,?)')
                .bind(newId('ms'), t, id, name, i === 0 ? 'in_progress' : 'pending', new Date(Date.now() + (i + 1) * 14 * 864e5).toISOString().slice(0, 10), i)),
        ]);
        await audit(ctx, 'project.created_from_order', 'project', id, { orderId: ctx.params.id });
        return json({ id, existing: false }, 201);
    },
};
export const projectRoutes = {
    async list(ctx) {
        requireAuth(ctx);
        const t = ctx.tenantId;
        const { limit, page, offset } = paginate(ctx.url);
        const status = ctx.url.searchParams.get('status');
        const q = ctx.url.searchParams.get('q');
        const where = ['p.tenant_id = ?'];
        const binds = [t];
        if (status) {
            where.push('p.status = ?');
            binds.push(status);
        }
        if (q) {
            where.push('(p.name LIKE ? OR org.name LIKE ?)');
            binds.push(`%${q}%`, `%${q}%`);
        }
        const rows = await ctx.env.DB.prepare(`SELECT p.*, org.name AS org_name, u.name AS manager_name, u.avatar_color AS manager_color,
              (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id) AS tasks_count,
              (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id AND t.status='done') AS tasks_done,
              (SELECT COUNT(*) FROM project_milestones m WHERE m.project_id = p.id AND m.status='done') AS milestones_done,
              (SELECT COUNT(*) FROM project_milestones m WHERE m.project_id = p.id) AS milestones_total
         FROM projects p LEFT JOIN organizations org ON org.id=p.org_id LEFT JOIN users u ON u.id=p.manager_id
        WHERE ${where.join(' AND ')} ORDER BY p.created_at DESC LIMIT ? OFFSET ?`).bind(...binds, limit, offset).all();
        const total = await ctx.env.DB.prepare(`SELECT COUNT(*) AS c FROM projects p LEFT JOIN organizations org ON org.id=p.org_id WHERE ${where.join(' AND ')}`).bind(...binds).first();
        const stats = await ctx.env.DB.prepare(`SELECT COUNT(*) AS total,
              SUM(CASE WHEN status='active' THEN 1 ELSE 0 END) AS active,
              SUM(CASE WHEN status='done' THEN 1 ELSE 0 END) AS done,
              SUM(CASE WHEN status='on_hold' THEN 1 ELSE 0 END) AS on_hold,
              SUM(CASE WHEN status='planning' THEN 1 ELSE 0 END) AS planning,
              COALESCE(SUM(budget),0) AS budget,
              COALESCE(AVG(progress),0) AS avg_progress
         FROM projects WHERE tenant_id = ?`).bind(t).first();
        return json({ data: rows.results, total: total?.c ?? 0, page, limit, stats });
    },
    async get(ctx) {
        requireAuth(ctx);
        const t = ctx.tenantId;
        const p = await ctx.env.DB.prepare(`SELECT p.*, org.name AS org_name, u.name AS manager_name FROM projects p
         LEFT JOIN organizations org ON org.id=p.org_id LEFT JOIN users u ON u.id=p.manager_id
        WHERE p.id = ? AND p.tenant_id = ?`).bind(ctx.params.id, t).first();
        if (!p)
            throw notFound('Проект не найден');
        const [milestones, tasks, activity] = await Promise.all([
            ctx.env.DB.prepare('SELECT * FROM project_milestones WHERE project_id = ? ORDER BY position').bind(ctx.params.id).all(),
            ctx.env.DB.prepare(`SELECT t.*, u.name AS assignee_name, u.avatar_color AS assignee_color FROM tasks t LEFT JOIN users u ON u.id=t.assignee_id WHERE t.project_id = ? ORDER BY t.due_date`).bind(ctx.params.id).all(),
            ctx.env.DB.prepare("SELECT id, kind, subject, actor_name, created_at FROM activities WHERE tenant_id=? AND entity_id=? ORDER BY created_at DESC LIMIT 20").bind(t, ctx.params.id).all(),
        ]);
        return json({ ...p, milestones: milestones.results, tasks: tasks.results, activity: activity.results });
    },
    async create(ctx) {
        requireAuth(ctx);
        requirePermission(ctx, 'projects.write');
        const b = await body(ctx);
        const id = newId('prj');
        await ctx.env.DB.prepare('INSERT INTO projects (id,tenant_id,name,org_id,order_id,status,priority,progress,budget,currency,manager_id,start_date,due_date,description,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)').bind(id, ctx.tenantId, str(b.name, 'name', { max: 200 }), b.org_id ?? null, b.order_id ?? null, oneOf(b.status, 'status', PROJECT_STATES, 'planning'), oneOf(b.priority, 'priority', PRIORITIES, 'medium'), num(b.progress, 'progress', { min: 0, max: 100 }) ?? 0, num(b.budget, 'budget', { min: 0 }) ?? 0, str(b.currency, 'currency', { required: false, max: 8 }) ?? 'UZS', b.manager_id ?? ctx.user.id, str(b.start_date, 'start_date', { required: false, max: 30 }), str(b.due_date, 'due_date', { required: false, max: 30 }), str(b.description, 'description', { required: false, max: 4000 }), now(), now()).run();
        await audit(ctx, 'project.created', 'project', id, { name: b.name });
        return json({ id }, 201);
    },
    async update(ctx) {
        requireAuth(ctx);
        requirePermission(ctx, 'projects.write');
        if (!(await owned(ctx, 'projects', ctx.params.id)))
            throw notFound('Проект не найден');
        const b = await body(ctx);
        await ctx.env.DB.prepare('UPDATE projects SET name=COALESCE(?,name), status=COALESCE(?,status), priority=COALESCE(?,priority), progress=COALESCE(?,progress), budget=COALESCE(?,budget), manager_id=?, due_date=?, description=?, updated_at=? WHERE id=? AND tenant_id=?').bind(str(b.name, 'name', { required: false, max: 200 }), b.status ?? null, b.priority ?? null, num(b.progress, 'progress', { min: 0, max: 100 }), num(b.budget, 'budget', { min: 0 }), b.manager_id ?? null, str(b.due_date, 'due_date', { required: false, max: 30 }), str(b.description, 'description', { required: false, max: 4000 }), now(), ctx.params.id, ctx.tenantId).run();
        await audit(ctx, 'project.updated', 'project', ctx.params.id, null);
        return json({ ok: true });
    },
    async addMilestone(ctx) {
        requireAuth(ctx);
        requirePermission(ctx, 'projects.write');
        if (!(await owned(ctx, 'projects', ctx.params.id)))
            throw notFound('Проект не найден');
        const b = await body(ctx);
        const id = newId('ms');
        const pos = await ctx.env.DB.prepare('SELECT COALESCE(MAX(position),-1)+1 AS p FROM project_milestones WHERE project_id = ?').bind(ctx.params.id).first();
        await ctx.env.DB.prepare('INSERT INTO project_milestones (id,tenant_id,project_id,name,status,due_date,position) VALUES (?,?,?,?,?,?,?)')
            .bind(id, ctx.tenantId, ctx.params.id, str(b.name, 'name', { max: 200 }), oneOf(b.status, 'status', ['pending', 'in_progress', 'done'], 'pending'), str(b.due_date, 'due_date', { required: false, max: 30 }), pos?.p ?? 0).run();
        return json({ id }, 201);
    },
    async setMilestoneStatus(ctx) {
        requireAuth(ctx);
        requirePermission(ctx, 'projects.write');
        const b = await body(ctx);
        const status = oneOf(b.status, 'status', ['pending', 'in_progress', 'done']);
        await ctx.env.DB.prepare('UPDATE project_milestones SET status=? WHERE id=? AND tenant_id=?').bind(status, ctx.params.id, ctx.tenantId).run();
        return json({ ok: true });
    },
};
export const taskRoutes = {
    async list(ctx) {
        requireAuth(ctx);
        const t = ctx.tenantId;
        const { limit, page, offset } = paginate(ctx.url);
        const status = ctx.url.searchParams.get('status');
        const assignee = ctx.url.searchParams.get('assignee_id');
        const projectId = ctx.url.searchParams.get('project_id');
        const q = ctx.url.searchParams.get('q');
        const scope = ctx.url.searchParams.get('scope');
        const where = ['t.tenant_id = ?'];
        const binds = [t];
        if (status) {
            where.push('t.status = ?');
            binds.push(status);
        }
        if (assignee) {
            where.push('t.assignee_id = ?');
            binds.push(assignee);
        }
        if (projectId) {
            where.push('t.project_id = ?');
            binds.push(projectId);
        }
        if (q) {
            where.push('t.title LIKE ?');
            binds.push(`%${q}%`);
        }
        if (scope === 'mine') {
            where.push('t.assignee_id = ?');
            binds.push(ctx.user.id);
        }
        if (scope === 'overdue') {
            where.push("t.status != 'done' AND t.due_date < date('now')");
        }
        const rows = await ctx.env.DB.prepare(`SELECT t.*, u.name AS assignee_name, u.avatar_color AS assignee_color, p.name AS project_name
         FROM tasks t LEFT JOIN users u ON u.id=t.assignee_id LEFT JOIN projects p ON p.id=t.project_id
        WHERE ${where.join(' AND ')}
        ORDER BY CASE t.status WHEN 'in_progress' THEN 0 WHEN 'todo' THEN 1 WHEN 'review' THEN 2 ELSE 3 END,
                 CASE t.priority WHEN 'critical' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END, t.due_date
        LIMIT ? OFFSET ?`).bind(...binds, limit, offset).all();
        const total = await ctx.env.DB.prepare(`SELECT COUNT(*) AS c FROM tasks t WHERE ${where.join(' AND ')}`).bind(...binds).first();
        const stats = await ctx.env.DB.prepare(`SELECT COUNT(*) AS total, SUM(CASE WHEN status='todo' THEN 1 ELSE 0 END) AS todo,
              SUM(CASE WHEN status='in_progress' THEN 1 ELSE 0 END) AS in_progress,
              SUM(CASE WHEN status='review' THEN 1 ELSE 0 END) AS review,
              SUM(CASE WHEN status='done' THEN 1 ELSE 0 END) AS done,
              SUM(CASE WHEN status != 'done' AND due_date < date('now') THEN 1 ELSE 0 END) AS overdue,
              SUM(CASE WHEN assignee_id = ? AND status != 'done' THEN 1 ELSE 0 END) AS mine
         FROM tasks WHERE tenant_id = ?`).bind(ctx.user.id, t).first();
        return json({ data: rows.results, total: total?.c ?? 0, page, limit, stats });
    },
    async create(ctx) {
        requireAuth(ctx);
        requirePermission(ctx, 'tasks.write');
        const b = await body(ctx);
        const id = newId('tsk');
        await ctx.env.DB.prepare('INSERT INTO tasks (id,tenant_id,title,description,project_id,entity_type,entity_id,assignee_id,status,priority,due_date,created_by,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)').bind(id, ctx.tenantId, str(b.title, 'title', { max: 250 }), str(b.description, 'description', { required: false, max: 4000 }), b.project_id ?? null, str(b.entity_type, 'entity_type', { required: false, max: 40 }), str(b.entity_id, 'entity_id', { required: false, max: 60 }), b.assignee_id ?? ctx.user.id, oneOf(b.status, 'status', TASK_STATES, 'todo'), oneOf(b.priority, 'priority', PRIORITIES, 'medium'), str(b.due_date, 'due_date', { required: false, max: 30 }), ctx.user.id, now(), now()).run();
        await audit(ctx, 'task.created', 'task', id, null);
        await notifyUser(ctx, String(b.assignee_id ?? ctx.user.id), 'task', 'Yangi vazifa', String(b.title), '/app/tasks');
        return json({ id }, 201);
    },
    async setStatus(ctx) {
        requireAuth(ctx);
        requirePermission(ctx, 'tasks.write');
        if (!(await owned(ctx, 'tasks', ctx.params.id)))
            throw notFound('Задача не найдена');
        const b = await body(ctx);
        const status = oneOf(b.status, 'status', TASK_STATES);
        await ctx.env.DB.prepare('UPDATE tasks SET status=?, completed_at=?, updated_at=? WHERE id=? AND tenant_id=?')
            .bind(status, status === 'done' ? now() : null, now(), ctx.params.id, ctx.tenantId).run();
        if (status === 'done') {
            await ctx.env.DB.prepare('INSERT INTO activities (id,tenant_id,kind,subject,entity_type,entity_id,actor_id,actor_name,created_at) VALUES (?,?,?,?,?,?,?,?,?)')
                .bind(newId('act'), ctx.tenantId, 'task', 'Задача выполнена', 'task', ctx.params.id, ctx.user.id, ctx.user.name, now()).run();
        }
        return json({ ok: true });
    },
    async update(ctx) {
        requireAuth(ctx);
        requirePermission(ctx, 'tasks.write');
        if (!(await owned(ctx, 'tasks', ctx.params.id)))
            throw notFound('Задача не найдена');
        const b = await body(ctx);
        await ctx.env.DB.prepare('UPDATE tasks SET title=COALESCE(?,title), description=?, priority=COALESCE(?,priority), due_date=?, assignee_id=?, updated_at=? WHERE id=? AND tenant_id=?')
            .bind(str(b.title, 'title', { required: false, max: 250 }), str(b.description, 'description', { required: false, max: 4000 }), b.priority ?? null, str(b.due_date, 'due_date', { required: false, max: 30 }), b.assignee_id ?? null, now(), ctx.params.id, ctx.tenantId).run();
        return json({ ok: true });
    },
    async remove(ctx) {
        requireAuth(ctx);
        requirePermission(ctx, 'tasks.write');
        if (!(await owned(ctx, 'tasks', ctx.params.id)))
            throw notFound('Задача не найдена');
        await ctx.env.DB.prepare('DELETE FROM tasks WHERE id=? AND tenant_id=?').bind(ctx.params.id, ctx.tenantId).run();
        return json({ ok: true });
    },
};
