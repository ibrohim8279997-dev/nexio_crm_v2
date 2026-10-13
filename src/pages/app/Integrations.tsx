import { useState } from 'react'
import { AlertTriangle, CheckCircle2, Copy, Plug, RefreshCw, ShieldCheck, Webhook } from 'lucide-react'
import { Badge, Button, PageHeader, QueryState, SectionCard, StatCard, StatusPill } from '../../components/ui/core'
import { Field, Input } from '../../components/ui/form'
import { Modal } from '../../components/ui/overlay'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api } from '../../lib/api'
import { num, relative } from '../../lib/format'
import { useCan } from '../../lib/auth'
import { useToast } from '../../components/ui/toast'

interface Integration {
  id: string; provider: string; name: string; status: string; mode: string; config: Record<string, unknown>
  last_sync_at: string | null; last_error: string | null; connected_at: string | null
}
interface IntegrationsResponse {
  data: Integration[]; webhooks: { id: string; event: string; status: string; attempts: number; created_at: string }[]
  hookStats: { total: number; delivered: number; failing: number } | null
  apiUsage: { quantity: number; limit_value: number; period: string } | null
}

const EVENTS = ['lead.created', 'deal.stage_changed', 'order.confirmed', 'invoice.paid', 'task.completed', 'ai.call.completed']

export default function Integrations() {
  const [docsOpen, setDocsOpen] = useState(false)
  const can = useCan()
  const toast = useToast()
  const q = useApi<IntegrationsResponse>(['integrations'], '/api/v1/integrations')
  const toggle = useApiMutation(({ id, status }: { id: string; status: string }) => api.post<{ mode: string; note: string }>(`/api/v1/integrations/${id}/toggle`, { status }), {
    invalidate: [['integrations']],
    onDone: (r) => toast.info(r.mode === 'sandbox' ? 'Sandbox-режим' : 'Интеграция подключена', r.note),
  })

  const connected = (q.data?.data ?? []).filter((i) => i.status === 'connected').length
  const sandbox = (q.data?.data ?? []).filter((i) => i.mode === 'sandbox').length

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader
        title="Интеграции и API"
        subtitle="Каталог коннекторов, статус подключения, вебхуки с HMAC-подписью, лимиты API и мониторинг доставки событий."
        breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'Интеграции' }]}
        actions={<Button variant="secondary" onClick={() => setDocsOpen(true)}><ShieldCheck className="size-4" /> API и вебхуки</Button>}
      />

      <div className="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
        <StatCard label="Коннекторов" value={num(q.data?.data.length)} hint="в каталоге" icon={<Plug className="size-4" />} />
        <StatCard label="Подключено" value={num(connected)} hint="активные соединения" icon={<CheckCircle2 className="size-4" />} tone="success" />
        <StatCard label="Sandbox" value={num(sandbox)} hint="без реальных ключей" icon={<AlertTriangle className="size-4" />} tone="warning" />
        <StatCard label="Доставка вебхуков" value={`${num(q.data?.hookStats?.delivered ?? 0)}/${num(q.data?.hookStats?.total ?? 0)}`} hint="успешных событий" icon={<Webhook className="size-4" />} tone="primary" />
      </div>

      <SectionCard title="Каталог интеграций" subtitle="Переключение доступно только при наличии разрешения integrations.write" padded={false}>
        <QueryState loading={q.isLoading} error={q.error} empty={(q.data?.data.length ?? 0) === 0} emptyTitle="Коннекторов нет" onRetry={() => q.refetch()}>
          <ul className="divide-y divide-line">
            {q.data?.data.map((i) => (
              <li key={i.id} className="flex flex-wrap items-center gap-3 px-4 py-3.5">
                <span className="inline-flex size-9 shrink-0 items-center justify-center rounded-[10px] bg-blue-50 text-primary"><Plug className="size-4" /></span>
                <div className="min-w-0 flex-1">
                  <div className="flex flex-wrap items-center gap-2">
                    <p className="text-[13px] font-semibold text-ink">{i.name}</p>
                    <StatusPill value={i.status} />
                    <Badge tone={i.mode === 'live' ? 'success' : 'warning'}>{i.mode}</Badge>
                  </div>
                  <p className="mt-0.5 text-[11px] text-muted">
                    {i.connected_at ? `подключено ${relative(i.connected_at)}` : 'не подключено'}
                    {i.last_sync_at ? ` · синхронизация ${relative(i.last_sync_at)}` : ''}
                    {i.last_error ? ` · ошибка: ${i.last_error}` : ''}
                  </p>
                </div>
                {can('integrations.write') && (
                  <Button size="sm" variant="secondary" loading={toggle.isPending}
                    onClick={() => toggle.mutate({ id: i.id, status: i.status === 'connected' ? 'available' : 'connected' })}>
                    {i.status === 'connected' ? 'Отключить' : <><Plug className="size-3.5" /> Подключить</>}
                  </Button>
                )}
              </li>
            ))}
          </ul>
        </QueryState>
      </SectionCard>

      <div className="grid gap-4 xl:grid-cols-2">
        <SectionCard title="Доставка вебхуков" subtitle="Последние 25 событий" padded={false}>
          <QueryState loading={q.isLoading} error={q.error} empty={(q.data?.webhooks.length ?? 0) === 0} emptyTitle="Событий не было" onRetry={() => q.refetch()}>
            <ul className="divide-y divide-line">
              {q.data?.webhooks.map((w) => (
                <li key={w.id} className="flex items-center gap-3 px-4 py-2.5">
                  <span className={`size-2 shrink-0 rounded-full ${w.status === 'delivered' ? 'bg-success' : w.status === 'retrying' ? 'bg-warning' : 'bg-danger'}`} />
                  <code className="min-w-0 flex-1 truncate text-[11px] text-ink">{w.event}</code>
                  <span className="text-[10px] text-muted">{w.attempts} попыт.</span>
                  <span className="text-[10px] text-muted">{relative(w.created_at)}</span>
                </li>
              ))}
            </ul>
          </QueryState>
        </SectionCard>

        <SectionCard title="Лимиты API" subtitle="Потребление за текущий период">
          <div className="space-y-4">
            <div>
              <div className="flex items-center justify-between text-[12px]">
                <span className="text-muted">API-запросы</span>
                <span className="font-semibold text-ink">
                  {num(q.data?.apiUsage?.quantity)} / {num(q.data?.apiUsage?.limit_value)}
                </span>
              </div>
              <div className="mt-1.5 h-2 overflow-hidden rounded-full bg-slate-100">
                <div className="h-full rounded-full bg-primary" style={{ width: `${Math.min(100, ((q.data?.apiUsage?.quantity ?? 0) / Math.max(1, q.data?.apiUsage?.limit_value ?? 1)) * 100)}%` }} />
              </div>
              <p className="mt-1.5 text-[11px] text-muted">Период: {q.data?.apiUsage?.period ?? '—'}. При превышении сервер отвечает предсказуемой ошибкой 402.</p>
            </div>
            <div className="rounded-xl bg-bg p-3 text-[11px] leading-relaxed text-muted">
              Все запросы выполняются в контексте компании: tenant определяется на сервере из сессии или токена.
              Передача tenant_id в параметрах не даёт доступа к чужому пространству.
            </div>
          </div>
        </SectionCard>
      </div>

      <ApiDocsModal open={docsOpen} onClose={() => setDocsOpen(false)} />
    </div>
  )
}

function ApiDocsModal({ open, onClose }: { open: boolean; onClose: () => void }) {
  const base = typeof window !== 'undefined' ? window.location.origin : ''
  const sample = `curl -X POST ${base}/api/v1/leads \\
  -H "Authorization: Bearer <token>" \\
  -H "Content-Type: application/json" \\
  -H "Idempotency-Key: 6f3c…" \\
  -d '{"title":"Заявка с сайта","email":"client@example.uz"}'`

  return (
    <Modal open={open} onClose={onClose} title="API v1 и вебхуки" description="REST /api/v1 с версионированием, пагинацией, rate limit и идемпотентностью для платежей и заказов." size="lg"
      footer={<Button variant="secondary" size="sm" onClick={onClose}>Закрыть</Button>}>
      <div className="space-y-5">
        <div>
          <h3 className="text-[13px] font-semibold text-ink">Пример запроса</h3>
          <div className="mt-2 flex items-start gap-2 rounded-xl bg-slate-900 p-3">
            <pre className="min-w-0 flex-1 overflow-x-auto text-[11px] leading-relaxed text-slate-100">{sample}</pre>
            <Button size="sm" variant="secondary" onClick={() => { void navigator.clipboard.writeText(sample) }} aria-label="Скопировать"><Copy className="size-3.5" /></Button>
          </div>
        </div>

        <div>
          <h3 className="text-[13px] font-semibold text-ink">События вебхуков</h3>
          <div className="mt-2 flex flex-wrap gap-1.5">
            {EVENTS.map((e) => <code key={e} className="rounded-md border border-line bg-bg px-2 py-1 text-[11px] text-ink">{e}</code>)}
          </div>
          <p className="mt-2 text-[11px] leading-relaxed text-muted">
            Каждая доставка подписывается HMAC-SHA256 с timestamp; повторная доставка того же события не создаёт
            дубликат операции благодаря idempotency key. Неуспешные доставки повторяются с backoff.
          </p>
        </div>

        <div>
          <h3 className="text-[13px] font-semibold text-ink">Генерация токена</h3>
          <p className="mt-1 text-[11px] leading-relaxed text-muted">
            Токены создаются в разделе «Настройки → API» с ограничением по scope и сроку действия. Значение токена
            показывается один раз и хранится на сервере только в виде хеша.
          </p>
          <Field label="Название токена" className="mt-3"><Input placeholder="Сайт компании — веб-форма" /></Field>
          <Button size="sm" className="mt-3" onClick={() => alert('Создание API-токена выполняется оператором платформы: в этой среде внешние ключи не выдаются.')}>
            <RefreshCw className="size-3.5" /> Создать токен
          </Button>
        </div>
      </div>
    </Modal>
  )
}
