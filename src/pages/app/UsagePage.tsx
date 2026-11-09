import { Link } from 'react-router-dom'
import { Grid, PageHeader, ProgressBar, QueryState, SectionCard, StatCard } from '../../components/ui/core'
import { useApi } from '../../lib/hooks'
import { num } from '../../lib/format'

interface Limit { used: number; limit: number; unit: string }
interface Usage { plan: string | null; limits: Record<'seats' | 'storage' | 'ai_minutes' | 'api_requests', Limit>; records: Record<string, number> }

const LIMITS: [keyof Usage['limits'], string][] = [['seats', 'Места пользователей'], ['storage', 'Хранилище'], ['ai_minutes', 'Минуты AI (месяц)'], ['api_requests', 'API-запросы (месяц)']]
const RECORDS: [string, string][] = [['contacts', 'Контакты'], ['organizations', 'Компании'], ['leads', 'Лиды'], ['deals', 'Сделки'], ['orders', 'Заказы'], ['projects', 'Проекты'], ['tasks', 'Задачи'], ['files', 'Файлы']]

/** Consumption versus the current plan. Limits are enforced by the server; this page only explains where you stand. */
export default function UsagePage() {
  const q = useApi<Usage>(['usage'], '/api/v1/usage')
  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader title="Использование" subtitle={q.data?.plan ? `Тариф «${q.data.plan}». Лимиты сбрасываются в начале месяца, кроме мест и хранилища.` : 'Потребление ресурсов по тарифу.'}
        breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'Использование' }]}
        actions={<Link to="/app/billing" className="text-[13px] font-semibold text-primary hover:underline">Сменить тариф →</Link>} />
      <QueryState loading={q.isLoading} error={q.error} onRetry={() => q.refetch()}>
        {q.data && (<>
          <SectionCard title="Лимиты тарифа">
            <div className="space-y-5">{LIMITS.map(([k, l]) => {
              const v = q.data!.limits[k]; const pct = v.limit > 0 ? Math.min(100, (v.used / v.limit) * 100) : 0
              return <div key={k}>
                <div className="flex justify-between text-[13px]"><span className="font-medium text-ink">{l}</span><span className="text-muted">{num(v.used, v.used % 1 ? 2 : 0)} / {v.limit > 0 ? num(v.limit) : 'без лимита'} {v.unit}</span></div>
                <ProgressBar className="mt-1.5" value={pct} tone={pct >= 90 ? 'warning' : 'primary'} />
                {pct >= 90 && <p className="mt-1 text-[11px] text-amber-700">Лимит почти исчерпан — новые операции могут быть заблокированы.</p>}
              </div>
            })}</div>
          </SectionCard>
          <SectionCard title="Данные в системе">
            <Grid cols={4}>{RECORDS.map(([k, l]) => <StatCard key={k} label={l} value={num(q.data!.records[k] ?? 0)} />)}</Grid>
          </SectionCard>
        </>)}
      </QueryState>
    </div>
  )
}
