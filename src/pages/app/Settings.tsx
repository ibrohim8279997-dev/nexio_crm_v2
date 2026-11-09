import { useEffect, useState } from 'react'
import { Building2, Download, GripVertical, History, Plus, Save, Sliders, Trash2, Workflow } from 'lucide-react'
import { Badge, Button, Card, Grid, PageHeader, QueryState, SectionCard, StatCard, Tabs } from '../../components/ui/core'
import { DataTable } from '../../components/ui/table'
import { Checkbox, Field, Input, Select } from '../../components/ui/form'
import { ConfirmDialog } from '../../components/ui/overlay'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api } from '../../lib/api'
import { dateTime, label, num } from '../../lib/format'
import { useAuth, useCan } from '../../lib/auth'
import { useToast } from '../../components/ui/toast'
import { useI18n, type Locale } from '../../lib/i18n'
import MfaCard from './MfaCard'
import CustomFieldsAdmin from './CustomFieldsAdmin'
import { TIMEZONES, formatUtcOffset } from '../../lib/timezones'

interface Stage { id: string; key: string; name: string; position: number; probability: number; kind: string; color: string }
interface SettingsData {
  tenant: { id: string; name: string; slug: string; legal_name: string | null; logo_text: string | null; logo_url?: string | null; brand_color: string; locale: string; timezone: string; currency: string; country: string; industry: string | null; status: string }
  settings: { work_hours?: string; week_start?: string; default_pipeline?: string; tax_rate?: number; date_format?: string }
  flags: Record<string, boolean>
  stages: Stage[]
  usage: { metric: string; quantity: number; limit_value: number; period: string }[]
}
interface AuditRow { id: string; action: string; target: string; target_id: string | null; actor_name: string; meta: string | null; ip: string | null; created_at: string }

const FLAG_LABELS: Record<string, string> = {
  ai_voice: 'AI-звонки', ai_sandbox: 'Sandbox AI-провайдер', client_portal: 'Портал заказчика',
  helpdesk: 'Helpdesk', automations: 'Автоматизации', webhooks: 'Вебхуки',
  multi_currency: 'Мультивалютность', advanced_analytics: 'Расширенная аналитика', audit_log: 'Журнал аудита', data_export: 'Экспорт данных',
}

export default function SettingsPage() {
  const [tab, setTab] = useState<'company' | 'pipeline' | 'flags' | 'fields' | 'security' | 'audit'>('company')
  const [form, setForm] = useState<Partial<SettingsData['tenant']>>({})
  const [prefs, setPrefs] = useState<Record<string, unknown>>({})
  const [newStage, setNewStage] = useState({ name: '', probability: '50' })
  const [delStage, setDelStage] = useState<Stage | null>(null)
  const can = useCan()
  const toast = useToast()
  const { user, refresh } = useAuth()
  const { setLocale } = useI18n()

  const q = useApi<SettingsData>(['settings'], '/api/v1/settings')
  const audit = useApi<{ data: AuditRow[] }>(['settings', 'audit'], '/api/v1/settings/audit', { enabled: tab === 'audit' })

  useEffect(() => {
    if (q.data) { setForm(q.data.tenant); setPrefs(q.data.settings ?? {}) }
  }, [q.data])

  const save = useApiMutation((payload: Record<string, unknown>) => api.patch('/api/v1/settings', payload), {
    invalidate: [['settings'], ['auth']], success: 'Настройки сохранены',
    onDone: () => { refresh(); toast.info('Применено', 'Часовой пояс, валюта и локаль влияют на форматирование дат и сумм в интерфейсе.') },
  })
  const updateStage = useApiMutation(({ id, payload }: { id: string; payload: Record<string, unknown> }) => api.patch(`/api/v1/settings/stages/${id}`, payload), { invalidate: [['settings']], success: 'Стадия обновлена' })
  const addStage = useApiMutation((payload: Record<string, unknown>) => api.post('/api/v1/settings/stages', payload), {
    invalidate: [['settings']], success: 'Стадия добавлена', onDone: () => setNewStage({ name: '', probability: '50' }),
  })
  const removeStage = useApiMutation((id: string) => api.del(`/api/v1/settings/stages/${id}`), {
    invalidate: [['settings']], success: 'Стадия удалена', onDone: () => setDelStage(null),
  })

  const t = q.data?.tenant

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader title="Настройки рабочего пространства" subtitle="Профиль компании, локализация, воронки, модули и журнал аудита. Каждое изменение сохраняется на сервере и попадает в аудит."
        breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'Настройки' }]}
        actions={<Badge tone="primary">{user?.name}</Badge>} />

      <Grid cols={4}>
        <StatCard label="Компания" value={t?.name ?? '—'} hint={t?.slug} icon={<Building2 className="size-4" />} />
        <StatCard label="Тариф" value={label(q.data?.tenant.status)} hint={`Валюта ${t?.currency ?? 'UZS'}`} icon={<Sliders className="size-4" />} tone="primary" />
        <StatCard label="Стадий воронки" value={num(q.data?.stages.length ?? 0)} icon={<Workflow className="size-4" />} />
        <StatCard label="Модулей включено" value={num(Object.values(q.data?.flags ?? {}).filter(Boolean).length)} hint={`из ${Object.keys(q.data?.flags ?? {}).length}`} icon={<Sliders className="size-4" />} tone="ai" />
      </Grid>

      <SectionCard padded={false}>
        <div className="border-b border-line px-4 py-3">
          <Tabs tabs={[{ value: 'company', label: 'Компания и локализация' }, { value: 'pipeline', label: 'Воронки', count: q.data?.stages.length }, { value: 'flags', label: 'Модули' }, { value: 'fields', label: 'Доп. поля' }, { value: 'security', label: 'Безопасность' }, { value: 'audit', label: 'Журнал аудита' }]}
            value={tab} onChange={setTab} />
        </div>

        <div className="p-4 sm:p-5">
          <QueryState loading={q.isLoading} error={q.error} onRetry={() => q.refetch()} skeleton={<Card className="h-64 animate-pulse" />}>
            {tab === 'company' && (
              <div className="grid gap-5 lg:grid-cols-3">
                <div className="space-y-4 lg:col-span-2">
                  <div className="grid gap-3 sm:grid-cols-2">
                    <Field label="Название компании" htmlFor="name"><Input id="name" value={form.name ?? ''} onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))} /></Field>
                    <Field label="Юридическое название" htmlFor="legal"><Input id="legal" value={form.legal_name ?? ''} onChange={(e) => setForm((f) => ({ ...f, legal_name: e.target.value }))} /></Field>
                    <Field label="Отрасль" htmlFor="industry"><Input id="industry" value={form.industry ?? ''} onChange={(e) => setForm((f) => ({ ...f, industry: e.target.value }))} /></Field>
                    <Field label="Logo rasmi URL yoki data URL" htmlFor="logo-url"><Input id="logo-url" value={form.logo_url ?? ''} onChange={(e) => setForm((f) => ({ ...f, logo_url: e.target.value }))} placeholder="https://.../logo.png" /></Field>
                    <Field label="Рабочие часы" hint="Влияет на SLA и напоминания" htmlFor="hours">
                      <Input id="hours" value={(prefs.work_hours as string) ?? '09:00–18:00'} onChange={(e) => setPrefs((p) => ({ ...p, work_hours: e.target.value }))} />
                    </Field>
                    <Field label="Ставка налога, %" htmlFor="tax">
                      <Input id="tax" type="number" value={String(prefs.tax_rate ?? 12)} onChange={(e) => setPrefs((p) => ({ ...p, tax_rate: Number(e.target.value) }))} />
                    </Field>
                  </div>
                  {can('settings.write') && (
                    <Button loading={save.isPending}
                      onClick={() => save.mutate({ ...form, settings: prefs })}><Save className="size-3.5" /> Сохранить изменения</Button>
                  )}
                </div>

                <div className="space-y-3">
                  <p className="text-[12px] font-semibold text-ink">Локализация</p>
                  <Field label="Язык интерфейса" htmlFor="locale">
                    <Select id="locale" value={form.locale ?? 'ru'} disabled={!can('settings.write')} onChange={(e) => { const next = e.target.value as Locale; setLocale(next); setForm((f) => ({ ...f, locale: next })) }}>
                      <option value="ru">Русский</option><option value="uz">O‘zbekcha</option><option value="en">English</option>
                    </Select>
                  </Field>
                  <Field label="Валюта" htmlFor="cur">
                    <Select id="cur" value={form.currency ?? 'UZS'} disabled={!can('settings.write')} onChange={(e) => setForm((f) => ({ ...f, currency: e.target.value }))}>
                      <option value="UZS">UZS — сум</option><option value="USD">USD — доллар</option><option value="EUR">EUR — евро</option><option value="RUB">RUB — рубль</option>
                    </Select>
                  </Field>
                  <Field label="Часовой пояс" htmlFor="tz">
                    <Select id="tz" value={form.timezone ?? 'Asia/Tashkent'} disabled={!can('settings.write')} onChange={(e) => setForm((f) => ({ ...f, timezone: e.target.value }))}>
                      {TIMEZONES.map((zone) => <option key={zone.name} value={zone.name}>{zone.name} ({formatUtcOffset(zone.offset)})</option>)}
                    </Select>
                  </Field>
                  <div className="rounded-xl bg-bg p-3 text-[11px] text-muted">
                    Данные изолированы по компании: запросы ограничены на сервере, интерфейс не может расширить область видимости.
                  </div>
                </div>
              </div>
            )}

            {tab === 'pipeline' && (
              <div className="space-y-4">
                <ul className="divide-y divide-line rounded-xl border border-line">
                  {q.data?.stages.map((s) => (
                    <li key={s.id} className="flex flex-wrap items-center gap-3 px-3.5 py-3">
                      <GripVertical className="size-4 shrink-0 text-slate-300" />
                      <span className="size-3 shrink-0 rounded-full" style={{ background: s.color }} />
                      <Input className="w-full max-w-[220px]" defaultValue={s.name} disabled={!can('settings.write')}
                        onBlur={(e) => e.target.value !== s.name && updateStage.mutate({ id: s.id, payload: { name: e.target.value } })} />
                      <Input className="w-24" type="number" defaultValue={s.probability} disabled={!can('settings.write')}
                        onBlur={(e) => Number(e.target.value) !== s.probability && updateStage.mutate({ id: s.id, payload: { probability: Number(e.target.value) } })} />
                      <span className="text-[11px] text-muted">вероятность, %</span>
                      <Badge tone={s.kind === 'won' ? 'success' : s.kind === 'lost' ? 'danger' : 'primary'}>{label(s.kind)}</Badge>
                      {can('settings.write') && (
                        <Button size="sm" variant="ghost" className="ml-auto" onClick={() => setDelStage(s)}
                          disabled={s.kind !== 'open'} title={s.kind !== 'open' ? 'Системные стадии удалить нельзя' : 'Удалить'}>
                          <Trash2 className="size-3.5" />
                        </Button>
                      )}
                    </li>
                  ))}
                </ul>
                {can('settings.write') && (
                  <div className="flex flex-wrap items-end gap-3 rounded-xl bg-bg p-3">
                    <Field label="Новая стадия" htmlFor="stage-name" className="min-w-[200px]">
                      <Input id="stage-name" value={newStage.name} onChange={(e) => setNewStage((s) => ({ ...s, name: e.target.value }))} placeholder="Например: Юридическая проверка" />
                    </Field>
                    <Field label="Вероятность, %" htmlFor="stage-prob">
                      <Input id="stage-prob" type="number" className="w-28" value={newStage.probability} onChange={(e) => setNewStage((s) => ({ ...s, probability: e.target.value }))} />
                    </Field>
                    <Button size="sm" disabled={!newStage.name} loading={addStage.isPending}
                      onClick={() => addStage.mutate({ name: newStage.name, probability: Number(newStage.probability), kind: 'open' })}>
                      <Plus className="size-3.5" /> Добавить стадию
                    </Button>
                  </div>
                )}
                <p className="text-[11px] text-muted">Стадии используются сделками: удаление возможно только если в стадии нет сделок.</p>
              </div>
            )}

            {tab === 'flags' && (
              <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                {Object.entries(q.data?.flags ?? {}).map(([key, value]) => (
                  <label key={key} className="flex items-start gap-3 rounded-xl border border-line p-3.5">
                    <input type="checkbox" checked={value} disabled={!can('settings.write')} className="mt-0.5 size-4 rounded border-line"
                      onChange={async (e) => { await api.patch('/api/v1/settings', { settings: { [`flag_${key}`]: e.target.checked } }); q.refetch() }} />
                    <div>
                      <p className="text-[12px] font-medium text-ink">{FLAG_LABELS[key] ?? key}</p>
                      <p className="text-[10px] text-muted">{value ? 'включён' : 'выключен'} · ключ: {key}</p>
                    </div>
                  </label>
                ))}
                <p className="text-[11px] leading-relaxed text-muted sm:col-span-2 xl:col-span-3">
                  Флаги управляют доступностью модулей, но серверные проверки прав остаются главными: отключение флага закрывает функции,
                  включение не даёт прав, которых нет у роли.
                </p>
              </div>
            )}

            {tab === 'fields' && <CustomFieldsAdmin />}
            {tab === 'security' && <div className="max-w-2xl"><MfaCard /></div>}

            {tab === 'audit' && (
              <div className="space-y-3">
                <div className="flex flex-wrap items-center gap-2">
                  <Badge tone="neutral"><History className="size-3" /> последние {audit.data?.data.length ?? 0} записей</Badge>
                  <a href="/api/v1/export?entity=deals" download>
                    <Button size="sm" variant="secondary" disabled={!can('export.data')}><Download className="size-3.5" /> Экспорт сделок (CSV)</Button>
                  </a>
                </div>
                <DataTable rows={audit.data?.data ?? []} dense
                  columns={[
                    { key: 'action', header: 'Действие', render: (r) => <span className="font-mono text-[11px] text-ink">{r.action}</span> },
                    { key: 'actor_name', header: 'Автор' },
                    { key: 'target', header: 'Объект', render: (r) => <span className="text-[11px]">{r.target}{r.target_id ? ` · ${r.target_id.slice(0, 12)}` : ''}</span> },
                    { key: 'ip', header: 'IP', render: (r) => <span className="font-mono text-[10px] text-muted">{r.ip ?? '—'}</span> },
                    { key: 'created_at', header: 'Когда', render: (r) => dateTime(r.created_at) },
                  ]}
                  empty="Записей аудита нет" />
              </div>
            )}
          </QueryState>
        </div>
      </SectionCard>

      <SectionCard title="Использование лимитов тарифа" subtitle="Данные сервера, влияющие на доступность действий">
        <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
          {q.data?.usage.map((u, i) => (
            <div key={i} className="rounded-xl border border-line p-3">
              <p className="text-[11px] font-medium text-ink">{label(u.metric)}</p>
              <p className="mt-1 text-[18px] font-bold text-ink">{num(u.quantity)}</p>
              <p className="text-[10px] text-muted">лимит {num(u.limit_value)} · период {u.period}</p>
              {u.limit_value > 0 && u.quantity / u.limit_value > 0.9 && <Badge tone="warning">лимит почти исчерпан</Badge>}
            </div>
          ))}
          {(q.data?.usage.length ?? 0) === 0 && <p className="text-[12px] text-muted">Данных о потреблении нет.</p>}
        </div>
      </SectionCard>

      <SectionCard title="Опасная зона" subtitle="Действия, влияющие на данные компании">
        <div className="flex flex-wrap gap-2">
          <Button variant="secondary" size="sm" disabled={!can('export.data')}><Download className="size-3.5" /> Запросить выгрузку всех данных</Button>
          <Button variant="secondary" size="sm" disabled title="Требуется подтверждение владельца и истечение периода хранения">Удалить рабочее пространство</Button>
        </div>
        <p className="mt-2 text-[11px] leading-relaxed text-muted">
          Удаление не выполняется мгновенно: сначала применяется объявленная политика хранения, экспорт данных и подтверждение владельца.
          Приостановка компании оператором платформы блокирует доступ, но не удаляет данные.
        </p>
      </SectionCard>

      <ConfirmDialog open={!!delStage} onClose={() => setDelStage(null)} danger loading={removeStage.isPending}
        onConfirm={() => delStage && removeStage.mutate(delStage.id)} confirmLabel="Удалить стадию"
        title={`Удалить стадию «${delStage?.name ?? ''}»?`}
        description="Стадия исчезнет из воронки. Если в ней есть сделки, сервер вернёт ошибку и предложит перенести их." />
      <span className="hidden"><Checkbox label="" /></span>
    </div>
  )
}
