import { useState } from 'react'
import { Link } from 'react-router-dom'
import { Download, FileText, HardDrive, Trash2 } from 'lucide-react'
import { PageHeader, ProgressBar, QueryState, SectionCard } from '../../components/ui/core'
import { DataTable, Pagination, type Column } from '../../components/ui/table'
import { Select, SearchInput } from '../../components/ui/form'
import { ConfirmDialog } from '../../components/ui/overlay'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api, download } from '../../lib/api'
import { relative } from '../../lib/format'
import { useCan } from '../../lib/auth'

interface FileRow { id: string; entity_type: string; entity_id: string; name: string; size: number; uploaded_by_name: string | null; created_at: string; entity_label: string | null; entity_link: string | null }
interface Resp { data: FileRow[]; total: number; page: number; limit: number; storage: { used_bytes: number; limit_bytes: number } }

const TYPES: [string, string][] = [['lead', 'Лиды'], ['contact', 'Контакты'], ['organization', 'Компании'], ['deal', 'Сделки'], ['order', 'Заказы'], ['project', 'Проекты'], ['task', 'Задачи']]
const typeLabel = (t: string) => TYPES.find(([v]) => v === t)?.[1] ?? t
export const fmtBytes = (b: number) => (b >= 1073741824 ? `${(b / 1073741824).toFixed(2)} ГБ` : b >= 1048576 ? `${(b / 1048576).toFixed(1)} МБ` : `${Math.max(1, Math.round(b / 1024))} КБ`)

/** Every file across the company, with a link to the record it belongs to. */
export default function FilesPage() {
  const can = useCan()
  const [q, setQ] = useState(''); const [type, setType] = useState(''); const [page, setPage] = useState(1)
  const [del, setDel] = useState<FileRow | null>(null)
  const list = useApi<Resp>(['files', q, type, page], `/api/v1/files?q=${encodeURIComponent(q)}&entity_type=${type}&page=${page}&limit=25`)
  const remove = useApiMutation((id: string) => api.del(`/api/v1/attachments/${id}`), { invalidate: [['files'], ['attachments']], success: 'Файл удалён', onDone: () => setDel(null) })
  const s = list.data?.storage
  const pct = s && s.limit_bytes > 0 ? Math.min(100, (s.used_bytes / s.limit_bytes) * 100) : 0

  const columns: Column<FileRow>[] = [
    { key: 'name', header: 'Файл', render: (f) => <div className="flex min-w-0 items-center gap-2"><FileText className="size-4 shrink-0 text-muted" /><span className="truncate font-medium text-ink">{f.name}</span></div> },
    { key: 'rec', header: 'Привязан к', render: (f) => f.entity_link
      ? <Link to={f.entity_link} className="text-primary hover:underline" onClick={(e) => e.stopPropagation()}>{typeLabel(f.entity_type)}: {f.entity_label ?? '—'}</Link>
      : <span className="text-muted">{typeLabel(f.entity_type)}</span> },
    { key: 'size', header: 'Размер', align: 'right', render: (f) => <span className="text-muted">{fmtBytes(f.size)}</span> },
    { key: 'by', header: 'Загрузил', hideOnMobile: true, render: (f) => <span className="text-muted">{f.uploaded_by_name ?? '—'}</span> },
    { key: 'when', header: 'Когда', align: 'right', hideOnMobile: true, render: (f) => <span className="text-muted">{relative(f.created_at)}</span> },
    { key: 'act', header: '', align: 'right', render: (f) => (
      <div className="flex justify-end gap-1" onClick={(e) => e.stopPropagation()}>
        <button type="button" onClick={() => download(`/api/v1/attachments/${f.id}/download`)} className="rounded p-1.5 hover:bg-subtle" aria-label={`Скачать ${f.name}`}><Download className="size-4" /></button>
        {can('crm.write') && <button type="button" onClick={() => setDel(f)} className="rounded p-1.5 hover:bg-subtle" aria-label={`Удалить ${f.name}`}><Trash2 className="size-4" /></button>}
      </div>) },
  ]

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader title="Файлы" subtitle="Все документы компании в одном месте. Загрузка — в карточках записей."
        breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'Файлы' }]} />
      {s && (
        <SectionCard title="Хранилище" action={<HardDrive className="size-4 text-muted" />}>
          <div className="flex justify-between text-xs"><span>{fmtBytes(s.used_bytes)}</span><span>{s.limit_bytes > 0 ? `из ${fmtBytes(s.limit_bytes)}` : 'без лимита'}</span></div>
          <ProgressBar className="mt-1.5" value={pct} tone={pct > 90 ? 'warning' : 'primary'} />
        </SectionCard>
      )}
      <SectionCard title="Все файлы" subtitle={list.data ? `${list.data.total} шт.` : undefined} padded={false}
        action={<div className="flex flex-wrap gap-2">
          <SearchInput value={q} onChange={(v) => { setQ(v); setPage(1) }} placeholder="Поиск по имени…" className="w-full sm:w-52" />
          <Select value={type} onChange={(e) => { setType(e.target.value); setPage(1) }} aria-label="Тип записи"><option value="">Все записи</option>{TYPES.map(([v, l]) => <option key={v} value={v}>{l}</option>)}</Select>
        </div>}>
        <QueryState loading={list.isLoading} error={list.error} empty={(list.data?.data.length ?? 0) === 0} emptyTitle="Файлов нет" emptyDescription="Откройте карточку лида, клиента, сделки или проекта и загрузите файл." onRetry={() => list.refetch()}>
          <DataTable columns={columns} rows={list.data?.data ?? []} />
          {list.data && <Pagination page={list.data.page} limit={list.data.limit} total={list.data.total} onPage={setPage} />}
        </QueryState>
      </SectionCard>
      <ConfirmDialog open={!!del} onClose={() => setDel(null)} onConfirm={() => del && remove.mutate(del.id)} loading={remove.isPending} danger
        title="Удалить файл?" description={<>«{del?.name}» будет удалён безвозвратно.</>} confirmLabel="Удалить" />
    </div>
  )
}
