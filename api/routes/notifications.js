import { json, newId, now, requireAuth } from '../core';
export const notificationRoutes = {
    async telegramWebhook(ctx) {
        const update = await ctx.req.json().catch(() => null);
        const message = update?.message;
        const match = typeof message?.text === 'string' ? message.text.match(/^\/start\s+([A-Za-z0-9_-]{16,80})$/) : null;
        if (!match || !message?.chat?.id)
            return json({ ok: true });
        const link = await ctx.env.DB.prepare('SELECT id FROM user_telegram_links WHERE link_token=?').bind(match[1]).first();
        if (!link)
            return json({ ok: true });
        await ctx.env.DB.prepare('UPDATE user_telegram_links SET chat_id=?,username=?,linked_at=? WHERE id=?').bind(String(message.chat.id), message.from?.username ?? null, now(), link.id).run();
        if (ctx.env.TELEGRAM_BOT_TOKEN)
            await fetch(`https://api.telegram.org/bot${ctx.env.TELEGRAM_BOT_TOKEN}/sendMessage`, { method: 'POST', headers: { 'content-type': 'application/json' }, body: JSON.stringify({ chat_id: message.chat.id, text: 'Nexio CRM Telegram bilan muvaffaqiyatli ulandi.' }) }).catch(() => undefined);
        return json({ ok: true });
    },
    async list(ctx) {
        requireAuth(ctx);
        const rows = await ctx.env.DB.prepare('SELECT id,type,title,body,action_url,read_at,created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50').bind(ctx.user.id).all();
        const unread = await ctx.env.DB.prepare('SELECT COUNT(*) AS count FROM notifications WHERE user_id = ? AND read_at IS NULL').bind(ctx.user.id).first();
        return json({ data: rows.results, unread: Number(unread?.count ?? 0) });
    },
    async read(ctx) {
        requireAuth(ctx);
        await ctx.env.DB.prepare('UPDATE notifications SET read_at=? WHERE id=? AND user_id=?').bind(now(), ctx.params.id, ctx.user.id).run();
        return json({ ok: true });
    },
    async readAll(ctx) {
        requireAuth(ctx);
        await ctx.env.DB.prepare('UPDATE notifications SET read_at=? WHERE user_id=? AND read_at IS NULL').bind(now(), ctx.user.id).run();
        return json({ ok: true });
    },
    async telegramLink(ctx) {
        requireAuth(ctx);
        const token = crypto.randomUUID().replaceAll('-', '');
        await ctx.env.DB.prepare(`INSERT INTO user_telegram_links (id,user_id,chat_id,link_token,created_at) VALUES (?,?,?,?,?)
      ON CONFLICT(user_id) DO UPDATE SET link_token=excluded.link_token, chat_id=''`).bind(newId('tgl'), ctx.user.id, '', token, now()).run();
        const username = String(ctx.env.TELEGRAM_BOT_USERNAME ?? '').replace(/^@/, '');
        return json({ configured: !!username, url: username ? `https://t.me/${username}?start=${token}` : null });
    },
};
