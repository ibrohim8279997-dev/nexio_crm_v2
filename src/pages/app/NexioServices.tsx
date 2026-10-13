import { useState } from 'react'
import { CheckCircle2, Clock, Loader2, Send, ShoppingBag, Sparkles } from 'lucide-react'
import { Badge, Button, Card, Grid, PageHeader, QueryState, SectionCard, StatCard, StatusPill } from '../../components/ui/core'
import { DataTable } from '../../components/ui/table'
import { Field, Input, Select, Textarea } from '../../components/ui/form'
import { Modal } from '../../components/ui/overlay'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api } from '../../lib/api'
import { num, shortDate, label } from '../../lib/format'
import { useCan } from '../../lib/auth'
import { useToast } from '../../components/ui/toast'

interface Req { id: string; service: string; title: string; description: string | null; budget: number; currency: string; status: string; priority: string; created_at: string; updated_at: string }
interface Data { data: Req[]; stats: { total: number; in_progress: number; delivered: number; submitted: number }; disclosure: string }

const SERVICES = [
  { code: 'crm', title: 'Внедрение CRM', text: 'Настройка воронок, ролей, импорт данных и обучение команды.', price: 'от $1 800' },
  { code: 'website', title: 'Разработка сайта', text: 'Корпоративный сайт или лендинг с аналитикой и SEO-основой.', price: 'от $2 400' },
  { code: 'android', title: 'Android-приложение', text: 'Мобильное приложение для продаж и полевых сотрудников.', price: 'от $3 500' },
  { code: 'windows', title: 'Windows-приложение', text: 'Десктоп-клиент для касс, складов и внутренних служб.', price: 'от $4 000' },
  { code: 'api', title: 'API-интеграция', text: 'Обмен данными с 1С, ERP, телефонией и платёжными системами.', price: 'от $900' },
  { code: 'ai_agent', title: 'AI-агент', text: 'Голосовой или текстовый агент для обработки лидов.', price: 'от $1 500' },
  { code: 'support', title: 'Поддержка', text: 'Пакет технической поддержки и доработок по SLA.', price: 'от $350/мес' },
]

export default function NexioServices() {
  const [open, setOpen] = useState(false)
  const [form, setForm] = useState({ service: 'crm', title: '', description: '', budget: '', priority: 'normal' })
  const can = useCan()
  const toast = useToast()

  const q = useApi<Data>(['nexio-services'], '/api/v1/nexio-services')
  const create = useApiMutation((payload: Record<string, unknown>) => api.post<{ id: string }>('/api/v1/nexio-services', payload), {
    invalidate: [['nexio-services'], ['platform']],
    success: 'Заявка отправлена в Nexio Lab',
    onDone: () => { setOpen(false); setForm({ service: 'crm', title: '', description: '', budget: '', priority: 'normal' }); toast.info('Что дальше', 'Заявка попадает во внутреннюю очередь Nexio Lab. Доступ к вашему рабочему пространству при этом не открывается.') },
  })

  const s = q.data?.stats

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader title="Услуги Nexio Lab" subtitle="Заказ внедрения, разработки и AI-агентов у команды Nexio Lab. Заявка уходит во внутреннюю очередь, данные компании остаются у вас."
        breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'Услуги' }]}
        actions={can('services.request') ? <Button onClick={() => setOpen(true)}><ShoppingBag className="size-4" /> Заказать услугу</Button> : undefined} />

      <Grid cols={4}>
        <StatCard label="Всего заявок" value={num(s?.total ?? 0)} icon={<ShoppingBag className="size-4" />} />
        <StatCard label="Ожидают оценки" value={num(s?.submitted ?? 0)} tone="warning" icon={<Clock className="size-4" />} />
        <StatCard label="В работе" value={num(s?.in_progress ?? 0)} tone="primary" icon={<Loader2 className="size-4" />} />
        <StatCard label="Сдано" value={num(s?.delivered ?? 0)} tone="success" icon={<CheckCircle2 className="size-4" />} />
      </Grid>

      <SectionCard title="Каталог услуг" subtitle="Фиксированные направления с ориентировочной стоимостью">
        <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
          {SERVICES.map((svc) => (
            <div key={svc.code} className="flex flex-col rounded-xl border border-line p-3.5">
              <div className="flex items-center gap-2">
                <span className="inline-flex size-8 items-center justify-center rounded-lg bg-violet-50 text-ai"><Sparkles className="size-4" /></span>
                <p className="text-[12px] font-semibold text-ink">{svc.title}</p>
              </div>
              <p className="mt-2 flex-1 text-[11px] leading-relaxed text-muted">{svc.text}</p>
              <div className="mt-3 flex items-center justify-between">
                <Badge tone="neutral">{svc.price}</Badge>
                {can('services.request') && (
                  <Button size="sm" variant="ghost" onClick={() => { setForm((f) => ({ ...f, service: svc.code, title: svc.title })); setOpen(true) }}>Заказать</Button>
                )}
              </div>
            </div>
          ))}
        </div>
      </SectionCard>

      <SectionCard title="Мои заявки" subtitle={q.data?.disclosure} padded={false}>
        <QueryState loading={q.isLoading} error={q.error} onRetry={() => q.refetch()} skeleton={<Card className="m-4 h-56 animate-pulse" />}>
          <DataTable rows={q.data?.data ?? []} dense
            columns={[
              { key: 'title', header: 'Заявка', render: (r) => (
                <div className="min-w-[240px]">
                  <p className="font-medium text-ink">{r.title}</p>
                  <p className="text-[10px] text-muted">{label(r.service)} · создана {shortDate(r.created_at)}</p>
                </div>
              ) },
              { key: 'budget', header: 'Бюджет', align: 'right', render: (r) => r.budget ? `$${num(r.budget)}` : '—' },
              { key: 'priority', header: 'Приоритет', render: (r) => <StatusPill value={r.priority} /> },
              { key: 'status', header: 'Статус', render: (r) => <StatusPill value={r.status} /> },
              { key: 'updated_at', header: 'Обновлено', render: (r) => shortDate(r.updated_at) },
            ]}
            empty="Заявок пока нет" />
        </QueryState>
      </SectionCard>

      <Modal open={open} onClose={() => setOpen(false)} title="Новая заявка в Nexio Lab"
        description="Опишите задачу — команда оценит объём и сроки. Заявка не создаёт обязательств и не списывает средства."
        footer={<>
          <Button size="sm" variant="secondary" onClick={() => setOpen(false)}>Отмена</Button>
          <Button size="sm" loading={create.isPending} disabled={!form.title}
            onClick={() => create.mutate({ service: form.service, title: form.title, description: form.description, budget: form.budget ? Number(form.budget) : undefined, priority: form.priority, currency: 'USD' })}>
            <Send className="size-3.5" /> Отправить заявку
          </Button>
        </>}>
        <div className="space-y-3">
          <Field label="Направление" htmlFor="svc">
            <Select id="svc" value={form.service} onChange={(e) => setForm((f) => ({ ...f, service: e.target.value }))}>
              {SERVICES.map((s2) => <option key={s2.code} value={s2.code}>{s2.title}</option>)}
            </Select>
          </Field>
          <Field label="Тема" required htmlFor="title">
            <Input id="title" value={form.title} onChange={(e) => setForm((f) => ({ ...f, title: e.target.value }))} placeholder="Например: внедрение CRM для отдела продаж" />
          </Field>
          <Field label="Описание задачи" hint="Цели, объём, сроки, интеграции" htmlFor="desc">
            <Textarea id="desc" rows={4} value={form.description} onChange={(e) => setForm((f) => ({ ...f, description: e.target.value }))} />
          </Field>
          <div className="grid gap-3 sm:grid-cols-2">
            <Field label="Ориентировочный бюджет, USD" htmlFor="budget">
              <Input id="budget" type="number" value={form.budget} onChange={(e) => setForm((f) => ({ ...f, budget: e.target.value }))} placeholder="2400" />
            </Field>
            <Field label="Приоритет" htmlFor="prio">
              <Select id="prio" value={form.priority} onChange={(e) => setForm((f) => ({ ...f, priority: e.target.value }))}>
                <option value="low">Низкий</option><option value="normal">Обычный</option><option value="high">Высокий</option>
              </Select>
            </Field>
          </div>
          <p className="rounded-xl border border-amber-100 bg-amber-50 p-3 text-[11px] leading-relaxed text-amber-800">
            Заявка передаётся во внутреннюю очередь Nexio Lab. Подписчик остаётся владельцем своих данных; доступ к рабочему пространству компании
            операторам не предоставляется без отдельного согласия.
          </p>
        </div>
      </Modal>
    </div>
  )
}
