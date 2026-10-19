import { createContext, useContext, useEffect, useMemo, useState, type ReactNode } from 'react'

export type Locale = 'en' | 'ru' | 'uz'
const common: Record<Locale, Record<string, string>> = {
  en: { sales:'Sales', work:'Work', clients:'Clients', finance:'Finance & data', company:'Company', dashboard:'Dashboard', leads:'Leads', deals:'Deals', companies:'Companies', contacts:'Contacts', orders:'Orders', catalog:'Catalog', projects:'Projects', tasks:'Tasks', communications:'Communications', documents:'Documents', financePage:'Finance', analytics:'Analytics', automations:'Automations', integrations:'Integrations', team:'Team', services:'Order from Nexio', billing:'Subscription', settings:'Settings', aiCalls:'AI calls', aiAnalytics:'AI analytics', upgrade:'Upgrade to Pro', viewPlans:'View plans', notifications:'Notifications', markAllRead:'Mark all as read', noNotifications:'No new notifications', logout:'Log out', closeMenu:'Close menu', menu:'Menu', language:'Interface language', timezone:'Time zone', save:'Save changes', localization:'Localization', companyProfile:'Company profile', darkTheme:'Dark', lightTheme:'Light', features:'Features', pricing:'Pricing', security:'Security', documentsNav:'Documents', login:'Log in', startFree:'Start free', workspace:'Open workspace', heroTitle:'Manage sales, processes and AI agents', heroText:'Nexio CRM is the workspace for leads, deals, orders, projects, finance and analytics — all in one place.', freeTrial:'14-day free trial', noCard:'No credit card', readyFunnel:'Ready-to-use funnel' },
  ru: { sales:'Продажи', work:'Работа', clients:'Клиенты', finance:'Финансы и данные', company:'Компания', dashboard:'Дашборд', leads:'Лиды', deals:'Сделки', companies:'Компании', contacts:'Контакты', orders:'Заказы', catalog:'Каталог', projects:'Проекты', tasks:'Задачи', communications:'Коммуникации', documents:'Документы', financePage:'Финансы', analytics:'Аналитика', automations:'Автоматизация', integrations:'Интеграции', team:'Команда', services:'Заказать у Nexio', billing:'Подписка', settings:'Настройки', aiCalls:'AI-звонки', aiAnalytics:'AI-аналитика', upgrade:'Перейти на Pro', viewPlans:'Смотреть тарифы', notifications:'Уведомления', markAllRead:'Прочитать всё', noNotifications:'Новых уведомлений нет', logout:'Выйти', closeMenu:'Закрыть меню', menu:'Меню', language:'Язык интерфейса', timezone:'Часовой пояс', save:'Сохранить изменения', localization:'Локализация', companyProfile:'Профиль компании', darkTheme:'Тёмная', lightTheme:'Светлая', features:'Возможности', pricing:'Тарифы', security:'Безопасность', documentsNav:'Документы', login:'Войти', startFree:'Начать бесплатно', workspace:'Открыть рабочее пространство', heroTitle:'Управляйте продажами, процессами и AI-агентами', heroText:'Nexio CRM — рабочее пространство для лидов, сделок, заказов, проектов, финансов и аналитики в одном месте.', freeTrial:'14 дней бесплатно', noCard:'Без карты', readyFunnel:'Воронка настроена сразу' },
  uz: { sales:'Savdo', work:'Ish', clients:'Mijozlar', finance:'Moliya va ma’lumotlar', company:'Kompaniya', dashboard:'Boshqaruv paneli', leads:'Lidlar', deals:'Bitimlar', companies:'Kompaniyalar', contacts:'Kontaktlar', orders:'Buyurtmalar', catalog:'Katalog', projects:'Loyihalar', tasks:'Vazifalar', communications:'Kommunikatsiyalar', documents:'Hujjatlar', financePage:'Moliya', analytics:'Analitika', automations:'Avtomatlashtirish', integrations:'Integratsiyalar', team:'Jamoa', services:'Nexio’dan buyurtma', billing:'Obuna', settings:'Sozlamalar', aiCalls:'AI qo‘ng‘iroqlar', aiAnalytics:'AI analitika', upgrade:'Pro tarifga o‘tish', viewPlans:'Tariflarni ko‘rish', notifications:'Bildirishnomalar', markAllRead:'Barchasini o‘qilgan deb belgilash', noNotifications:'Yangi bildirishnomalar yo‘q', logout:'Chiqish', closeMenu:'Menyuni yopish', menu:'Menyu', language:'Interfeys tili', timezone:'Vaqt zonasi', save:'O‘zgarishlarni saqlash', localization:'Mahalliylashtirish', companyProfile:'Kompaniya profili', darkTheme:'To‘q', lightTheme:'Yorug‘', features:'Imkoniyatlar', pricing:'Tariflar', security:'Xavfsizlik', documentsNav:'Hujjatlar', login:'Kirish', startFree:'Bepul boshlash', workspace:'Ish maydonini ochish', heroTitle:'Savdo, jarayonlar va AI agentlarini boshqaring', heroText:'Nexio CRM — lidlar, bitimlar, buyurtmalar, loyihalar, moliya va analitikani bir joyga jamlagan ish maydoni.', freeTrial:'14 kun bepul', noCard:'Karta kerak emas', readyFunnel:'Voronka darhol tayyor' },
}

interface I18nContext { locale: Locale; setLocale: (locale: Locale) => void; t: (key: string) => string }
const Context = createContext<I18nContext | null>(null)

export function I18nProvider({ children }: { children: ReactNode }) {
  const [locale, setLocaleState] = useState<Locale>(() => {
    const saved = localStorage.getItem('nexio_locale')
    return saved === 'en' || saved === 'uz' || saved === 'ru' ? saved : 'ru'
  })
  const setLocale = (next: Locale) => { setLocaleState(next); localStorage.setItem('nexio_locale', next) }
  useEffect(() => { document.documentElement.lang = locale }, [locale])
  const value = useMemo(() => ({ locale, setLocale, t: (key: string) => common[locale][key] ?? common.en[key] ?? key }), [locale])
  return <Context.Provider value={value}>{children}</Context.Provider>
}

export function useI18n() {
  const value = useContext(Context)
  if (!value) throw new Error('useI18n must be used inside I18nProvider')
  return value
}
