import { Link, useParams } from 'react-router-dom'
import { Card } from '../../components/ui/core'

const DOCS: Record<string, { title: string; body: string[] }> = {
  terms: {
    title: 'Условия использования',
    body: [
      'Nexio CRM — проприетарный продукт Nexio Lab. Исходный код не раскрывается компаниям-подписчикам; доступ предоставляется по подписке в рамках оплаченных модулей, ролей и квот.',
      'Компания-подписчик самостоятельно определяет законные основания обработки персональных данных своих клиентов и несёт ответственность за соблюдение применимого законодательства, включая правила обзвона и записи разговоров.',
      'We do not claim absolute security. The platform provides tenant isolation, role-based access, audit logging and encryption of credentials, and expects subscribers to manage their own users responsibly.',
    ],
  },
  privacy: {
    title: 'Политика конфиденциальности',
    body: [
      'Данные арендаторов изолированы. Оператор платформы Nexio Lab не имеет постоянного доступа к содержимому рабочего пространства компании; техническая поддержка возможна только по строго регулируемой процедуре с фиксацией основания и времени доступа в журнале аудита компании.',
      'Секреты и токены интеграций хранятся в защищённом хранилище и не записываются в журналы. Пароли хранятся только в виде производных значений (PBKDF2-SHA256, 120 000 итераций).',
      'Сроки хранения задаются политикой компании: по запросу выполняется экспорт или удаление данных в объявленном порядке, без смешивания данных разных арендаторов.',
    ],
  },
  dpa: {
    title: 'Обработка данных (DPA)',
    body: [
      'Стороны фиксируют роли контролёра и обработчика. Nexio Lab обрабатывает данные по поручению компании-подписчика и только в объёме, необходимом для оказания услуги.',
      'Запись разговоров и транскрипты хранятся только при наличии соответствующего основания и настройки, с явным уведомлением об ИИ и праве отказаться.',
      'Компания может инициировать экспорт или удаление данных; процедура выполняется без раскрытия данных другим арендаторам.',
    ],
  },
  'acceptable-use': {
    title: 'Правила допустимого использования',
    body: [
      'Запрещено использовать платформу для обзвона без законного основания, для обхода списков «не звонить» и для выдачи ИИ-агента за человека.',
      'AI-агент выполняет только разрешённые действия в рамках конфигурации компании и применимого права. Финансово значимые и нестандартные действия требуют одобрения человека.',
      'Платформа не является сервисом, оказывающим юридические или финансовые консультации; счёт в CRM не является юридически значимым электронным документом до подключения ЭДО.',
    ],
  },
}

export default function Legal() {
  const { slug } = useParams()
  const doc = DOCS[slug ?? 'terms'] ?? DOCS.terms
  return (
    <div className="min-h-screen bg-bg">
      <header className="border-b border-line bg-surface">
        <div className="mx-auto flex max-w-3xl items-center justify-between px-4 py-4">
          <Link to="/" className="flex items-center gap-2">
            <span className="inline-flex size-8 items-center justify-center rounded-lg bg-primary text-sm font-extrabold text-white">N</span>
            <span className="text-[15px] font-bold text-ink">Nexio CRM</span>
          </Link>
          <nav className="flex gap-3 text-[12px] text-muted">
            {Object.entries(DOCS).map(([k, d]) => (
              <Link key={k} to={`/legal/${k}`} className="hover:text-primary">{d.title.split(' ')[0]}</Link>
            ))}
          </nav>
        </div>
      </header>
      <main className="mx-auto max-w-3xl px-4 py-10">
        <h1 className="text-[26px] font-extrabold tracking-tight text-ink">{doc.title}</h1>
        <p className="mt-1 text-[12px] text-muted">Nexio Lab · актуально на {new Date().toLocaleDateString('ru-RU')}</p>
        <Card className="mt-6 space-y-4 p-6">
          {doc.body.map((p, i) => <p key={i} className="text-[13px] leading-relaxed text-muted">{p}</p>)}
        </Card>
        <p className="mt-4 text-[11px] text-slate-400">
          Документ носит информационный характер в демонстрационной среде и не заменяет юридически согласованные договоры.
        </p>
      </main>
    </div>
  )
}
