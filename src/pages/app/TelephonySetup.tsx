import { useEffect, useState } from 'react'
import { PhoneCall } from 'lucide-react'
import { Badge, Button, QueryState, SectionCard, type Tone } from '../../components/ui/core'
import { Field, Input, Select } from '../../components/ui/form'
import { useToast } from '../../components/ui/toast'
import { useApi } from '../../lib/hooks'
import { api } from '../../lib/api'
import { useCan } from '../../lib/auth'
import { useTr } from '../../lib/i18n'
import { telephonyDict } from '../../i18n/telephony'
import { useQueryClient } from '@tanstack/react-query'

const PLUGIN = 'nexio.telephony'
interface Conn { id: string; name: string; provider: string; status: string; config: Record<string, string | undefined>; secrets?: Array<string | { key: string }> }
const TONE: Record<string, Tone> = { connected: 'success', sandbox: 'warning', not_configured: 'neutral', error: 'danger', revoked: 'neutral' }

/** The company's own telephony account: nothing here is provided by Nexio, every company enters and owns its credentials. */
export function TelephonySetup() {
  const t = useTr(telephonyDict)
  const toast = useToast()
  const qc = useQueryClient()
  const manage = useCan()('integrations.write')
  const q = useApi<{ data: Conn[] }>(['plugin', PLUGIN, 'connections'], `/api/v1/plugins/${PLUGIN}/connections`)
  const conn = q.data?.data[0]
  const hasSecret = (k: string) => (conn?.secrets ?? []).some((s) => (typeof s === 'string' ? s : s.key) === k)
  const [type, setType] = useState<'http' | 'sip'>('http')
  const [f, setF] = useState({ name: '', api_url: '', numbers_path: '', place_call_path: '', sip_server: '', sip_port: '5060', sip_transport: 'udp', sip_username: '', caller_id: '' })
  const [token, setToken] = useState('')
  const [sipPass, setSipPass] = useState('')
  const [busy, setBusy] = useState(false)

  useEffect(() => {
    if (!conn) return
    const c = conn.config ?? {}
    setType(conn.provider === 'sip' ? 'sip' : 'http')
    setF({ name: conn.name, api_url: c.api_url ?? '', numbers_path: c.numbers_path ?? '', place_call_path: c.place_call_path ?? '', sip_server: c.sip_server ?? '', sip_port: c.sip_port ?? '5060', sip_transport: c.sip_transport ?? 'udp', sip_username: c.sip_username ?? '', caller_id: c.caller_id ?? '' })
  }, [conn?.id]) // eslint-disable-line react-hooks/exhaustive-deps
  const set = (k: keyof typeof f) => (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => setF((s) => ({ ...s, [k]: e.target.value }))

  const save = async () => {
    const secretKey = type === 'http' ? 'api_token' : 'sip_password'
    const secret = (type === 'http' ? token : sipPass).trim()
    if (type === 'http' && !/^https:\/\//i.test(f.api_url.trim())) return void toast.error(t('needUrl'))
    if (type === 'http' && !secret && !hasSecret(secretKey)) return void toast.error(t('needToken'))
    if (type === 'sip' && (!f.sip_server.trim() || !f.sip_username.trim())) return void toast.error(t('needSip'))
    const config: Record<string, string> = type === 'http'
      ? { api_url: f.api_url.trim(), ...(f.numbers_path.trim() ? { numbers_path: f.numbers_path.trim() } : {}), ...(f.place_call_path.trim() ? { place_call_path: f.place_call_path.trim() } : {}) }
      : { sip_server: f.sip_server.trim(), sip_port: f.sip_port.trim(), sip_transport: f.sip_transport, sip_username: f.sip_username.trim(), ...(f.caller_id.trim() ? { caller_id: f.caller_id.trim() } : {}) }
    setBusy(true)
    try {
      let id = conn?.id
      if (id) await api.patch(`/api/v1/plugins/connections/${id}`, { name: f.name.trim() || t('connNameDefault'), config })
      else id = (await api.post<{ connection: { id: string } }>(`/api/v1/plugins/${PLUGIN}/connections`, { name: f.name.trim() || t('connNameDefault'), provider: type, config })).connection.id
      if (secret) await api.post(`/api/v1/plugins/connections/${id}/secrets`, { secrets: { [secretKey]: secret } })
      let live = false
      if (type === 'http') { try { await api.patch(`/api/v1/plugins/connections/${id}`, { status: 'connected' }); live = true } catch { /* the provider has not confirmed yet */ } }
      toast.success(type === 'http' ? t(live ? 'savedConnected' : 'savedNotConnected') : t('saved'))
      setToken(''); setSipPass('')
      void qc.invalidateQueries({ queryKey: ['plugin', PLUGIN] }); void qc.invalidateQueries({ queryKey: ['telephony'] })
    } catch (e) { toast.error((e as Error).message) } finally { setBusy(false) }
  }

  return <SectionCard title={t('title')} subtitle={t('subtitle')} action={conn ? <Badge tone={TONE[conn.status] ?? 'neutral'}>{t(`st_${conn.status}`)}</Badge> : <Badge tone="neutral">{t('st_not_configured')}</Badge>}>
    <QueryState loading={q.isLoading} error={q.error} onRetry={() => q.refetch()}>
      <div className="space-y-4">
        <ol className="list-decimal space-y-0.5 rounded-xl bg-subtle p-3 pl-8 text-xs text-muted"><li className="font-bold text-ink list-none -ml-5">{t('howTitle')}</li><li>{t('how1')}</li><li>{t('how2')}</li><li>{t('how3')}</li></ol>
        <div className="grid gap-3 sm:grid-cols-2">
          <Field label={t('type')} hint={t(type === 'http' ? 't_http_hint' : 't_sip_hint')}><Select value={type} disabled={!manage || !!conn} onChange={(e) => setType(e.target.value as 'http' | 'sip')}><option value="http">{t('t_http')}</option><option value="sip">{t('t_sip')}</option></Select></Field>
          <Field label={t('connName')}><Input value={f.name} placeholder={t('connNameDefault')} disabled={!manage} onChange={set('name')} /></Field>
          {type === 'http' ? <>
            <Field label={t('apiUrl')}><Input value={f.api_url} placeholder={t('apiUrlPh')} disabled={!manage} onChange={set('api_url')} /></Field>
            <Field label={t('apiToken')} hint={t('tokenHint')}><Input type="password" autoComplete="new-password" value={token} disabled={!manage} placeholder={hasSecret('api_token') ? t('tokenSaved') : ''} onChange={(e) => setToken(e.target.value)} /></Field>
            <Field label={t('numbersPath')} hint={t('advanced')}><Input value={f.numbers_path} placeholder="/numbers" disabled={!manage} onChange={set('numbers_path')} /></Field>
            <Field label={t('callPath')} hint={t('advanced')}><Input value={f.place_call_path} placeholder="/calls" disabled={!manage} onChange={set('place_call_path')} /></Field>
          </> : <>
            <Field label={t('sipServer')}><Input value={f.sip_server} placeholder="sip.provider.uz" disabled={!manage} onChange={set('sip_server')} /></Field>
            <Field label={t('sipPort')}><Input type="number" min={1} max={65535} value={f.sip_port} disabled={!manage} onChange={set('sip_port')} /></Field>
            <Field label={t('sipTransport')}><Select value={f.sip_transport} disabled={!manage} onChange={set('sip_transport')}><option value="udp">UDP</option><option value="tcp">TCP</option><option value="tls">TLS</option></Select></Field>
            <Field label={t('callerId')}><Input value={f.caller_id} placeholder="+998 71 000 00 00" disabled={!manage} onChange={set('caller_id')} /></Field>
            <Field label={t('sipUser')}><Input value={f.sip_username} autoComplete="off" disabled={!manage} onChange={set('sip_username')} /></Field>
            <Field label={t('sipPass')} hint={t('tokenHint')}><Input type="password" autoComplete="new-password" value={sipPass} disabled={!manage} placeholder={hasSecret('sip_password') ? t('tokenSaved') : ''} onChange={(e) => setSipPass(e.target.value)} /></Field>
          </>}
        </div>
        {manage ? <Button loading={busy} onClick={() => void save()}><PhoneCall className="size-4" /> {t('save')}</Button> : <p className="text-xs text-muted">{t('noRights')}</p>}
      </div>
    </QueryState>
  </SectionCard>
}
