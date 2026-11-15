import { useEffect, useState } from 'react'
import { CheckCircle2, Copy, KeyRound, Link2Off, ShieldCheck, Sparkles, XCircle } from 'lucide-react'
import { Badge, Button, QueryState, SectionCard } from '../../components/ui/core'
import { Field, Input } from '../../components/ui/form'
import { useToast } from '../../components/ui/toast'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api } from '../../lib/api'
import { shortDate, relative } from '../../lib/format'
import { useCan } from '../../lib/auth'
import { useTr } from '../../lib/i18n'
import { aiAccessDict } from '../../i18n/aiAccess'

interface Access { connected: boolean; key_name: string | null; prefix: string | null; scopes: string[]; status: string; connected_at: string | null; last_used_at: string | null; actions?: Record<string, boolean>; ai_scopes?: string[] }
interface Check { ok: boolean; checks: { name: string; ok: boolean; detail?: string | null }[]; visible_records: Record<string, number> }
const CREATE_RIGHTS = ['crm.write', 'tasks.write', 'projects.write'] as const

/** AI settings → the company's single CRM API key the AI reads data through. */
export function AiCrmAccessCard() {
  const t = useTr(aiAccessDict)
  const toast = useToast()
  const can = useCan()
  const allowed = can('ai.write') && can('integrations.write')
  const q = useApi<Access>(['ai', 'crm-access'], '/api/v1/ai/crm-access')
  const [token, setToken] = useState('')
  const [shown, setShown] = useState<string | null>(null)
  const [rights, setRights] = useState<string[]>([])
  const [result, setResult] = useState<Check | null>(null)
  const done = { invalidate: [['ai', 'crm-access']] }
  const connect = useApiMutation(() => api.post<Access>('/api/v1/ai/crm-access', { token: token.trim() }), { ...done, success: t('connected'), onDone: () => setToken('') })
  const generate = useApiMutation(() => api.post<Access & { token: string }>('/api/v1/ai/crm-access/generate', { scopes: rights }), { ...done, success: t('created'), onDone: (r) => setShown(r.token) })
  const disconnect = useApiMutation(() => api.del<Access>('/api/v1/ai/crm-access'), { ...done, success: t('disconnected'), onDone: () => setShown(null) })
  const saveRights = useApiMutation(() => api.patch<Access>('/api/v1/ai/crm-access/scopes', { scopes: rights }), { ...done, success: t('rightsSaved') })
  const check = useApiMutation(() => api.get<Check>('/api/v1/ai/crm-access/check'), { onDone: (r) => setResult(r) })
  const a = q.data
  useEffect(() => { if (a?.scopes) setRights(a.scopes.filter((x) => (CREATE_RIGHTS as readonly string[]).includes(x))) }, [a?.scopes?.join(',')])  // eslint-disable-line react-hooks/exhaustive-deps
  const toggle = (s: string) => setRights((cur) => (cur.includes(s) ? cur.filter((x) => x !== s) : [...cur, s]))
  const RightsPicker = ({ disabled }: { disabled?: boolean }) => <fieldset className="space-y-1.5" disabled={disabled}>
    <legend className="text-xs font-semibold text-ink">{t('rights')}</legend>
    <p className="text-xs text-muted">{t('rightsHint')}</p>
    {CREATE_RIGHTS.map((s) => <label key={s} className="flex items-center gap-2 text-sm text-ink">
      <input type="checkbox" checked={rights.includes(s)} disabled={disabled || !can(s)} onChange={() => toggle(s)} />{t(`r_${s.replace('.', '_')}`)} <span className="font-mono text-[11px] text-muted">{s}</span></label>)}
  </fieldset>
  const linked = !!a && a.status !== 'not_connected'

  return <SectionCard title={t('title')} subtitle={t('subtitle')}
    action={a ? <Badge tone={a.connected ? 'success' : linked ? 'warning' : 'neutral'}>{a.connected ? t('on') : linked ? t('problem') : t('off')}</Badge> : undefined}>
    <QueryState loading={q.isLoading} error={q.error} onRetry={() => q.refetch()}>
      {a && !linked && <div className="space-y-4">
        <p className="text-sm text-muted">{t('offText')}</p>
        {!allowed ? <p className="text-xs text-muted">{t('needRights')}</p> : <div className="grid gap-4 lg:grid-cols-2">
          <div className="space-y-2 rounded-xl border border-line p-3">
            <p className="flex items-center gap-1.5 text-sm font-semibold text-ink"><Sparkles className="size-4 text-primary" />{t('generate')}</p>
            <p className="text-xs text-muted">{t('generateHint')}</p>
            <RightsPicker />
            <Button size="sm" loading={generate.isPending} onClick={() => generate.mutate(undefined as never)}><KeyRound className="size-4" /> {t('generate')}</Button>
          </div>
          <div className="space-y-2 rounded-xl border border-line p-3">
            <p className="text-sm font-semibold text-ink">{t('haveKey')}</p>
            <Field label={t('pasteLabel')} hint={t('pasteHint')}><Input type="password" autoComplete="off" value={token} placeholder={t('pastePh')} onChange={(e) => setToken(e.target.value)} /></Field>
            <Button size="sm" variant="secondary" loading={connect.isPending} disabled={!token.trim().startsWith('nxk_')} onClick={() => connect.mutate(undefined as never)}>{t('connect')}</Button>
          </div>
        </div>}
      </div>}

      {a && linked && <div className="space-y-3">
        {!a.connected && <p role="alert" className="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">{t(`st_${a.status}`)}</p>}
        <dl className="grid gap-2 text-sm sm:grid-cols-2">
          <div><dt className="text-xs text-muted">{t('key')}</dt><dd className="font-medium text-ink">{a.key_name ?? '—'} <span className="font-mono text-xs text-muted">{a.prefix}…</span></dd></div>
          <div><dt className="text-xs text-muted">{t('scopes')}</dt><dd className="flex flex-wrap gap-1">{a.scopes.map((s) => <Badge key={s} tone="neutral">{s}</Badge>)}</dd></div>
          <div><dt className="text-xs text-muted">{t('since')}</dt><dd className="text-ink">{shortDate(a.connected_at)}</dd></div>
          <div><dt className="text-xs text-muted">{t('lastUsed')}</dt><dd className="text-ink">{a.last_used_at ? relative(a.last_used_at) : t('never')}</dd></div>
        </dl>
        {a.status === 'active' && allowed && (a.key_name ?? '').startsWith('AI assistant') && <div className="space-y-2 rounded-xl border border-line p-3">
          <RightsPicker />
          <Button size="sm" variant="secondary" loading={saveRights.isPending} onClick={() => saveRights.mutate(undefined as never)}>{t('saveRights')}</Button>
        </div>}
        {a.status === 'active' && !(a.key_name ?? '').startsWith('AI assistant') && <p className="text-xs text-muted">{t('manualKey')}</p>}
        {shown && <div className="rounded-xl border border-emerald-200 bg-emerald-50 p-3">
          <p className="text-xs text-emerald-900">{t('tokenOnce')}</p>
          <div className="mt-2 flex items-center gap-2"><code className="min-w-0 flex-1 break-all rounded bg-white px-2 py-1 font-mono text-xs">{shown}</code>
            <Button size="sm" variant="secondary" onClick={() => { void navigator.clipboard?.writeText(shown).then(() => toast.success(t('copied'))) }}><Copy className="size-3.5" /> {t('copy')}</Button></div>
        </div>}
        {allowed && <Button size="sm" variant="ghost" loading={disconnect.isPending} onClick={() => { if (window.confirm(t('confirmDisconnect'))) disconnect.mutate(undefined as never) }}><Link2Off className="size-4" /> {t('disconnect')}</Button>}
      </div>}

      <div className="mt-4 space-y-2">
        <Button size="sm" variant="secondary" loading={check.isPending} onClick={() => check.mutate(undefined as never)}><CheckCircle2 className="size-4" /> {check.isPending ? t('checking') : t('check')}</Button>
        {result && <div className="rounded-xl border border-line p-3 text-sm">
          <p className={`font-semibold ${result.ok ? 'text-emerald-700' : 'text-amber-800'}`}>{result.ok ? t('allOk') : t('someProblem')}</p>
          <ul className="mt-1 space-y-0.5">{result.checks.map((c) => <li key={c.name} className="flex items-center gap-1.5 text-xs text-ink">{c.ok ? <CheckCircle2 className="size-3.5 text-emerald-600" /> : <XCircle className="size-3.5 text-amber-600" />}{t(`c_${c.name}`)}{!c.ok && c.detail && c.detail !== 'optional' ? <span className="text-muted"> — {c.detail}</span> : null}</li>)}</ul>
          <p className="mt-2 text-xs text-muted">{t('visible')}: {Object.entries(result.visible_records).map(([k, v]) => `${k} ${v}`).join(' · ')}</p>
        </div>}
      </div>

      <div className="mt-4 rounded-xl bg-subtle p-3"><p className="flex items-center gap-1.5 text-xs font-bold text-ink"><ShieldCheck className="size-3.5" />{t('howTitle')}</p>
        <ul className="mt-1 list-disc space-y-0.5 pl-5 text-xs text-muted"><li>{t('how1')}</li><li>{t('how2')}</li><li>{t('how3')}</li><li>{t('how4')}</li></ul></div>
    </QueryState>
  </SectionCard>
}
