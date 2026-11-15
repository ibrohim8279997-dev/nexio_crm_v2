import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from 'react'

export type Locale = 'en' | 'ru' | 'uz'

const common: Record<Locale, Record<string, string>> = {
  en: {
    sales: 'Sales', work: 'Work', clients: 'Clients', finance: 'Finance & data', company: 'Company', dashboard: 'Dashboard',
    leads: 'Leads', deals: 'Deals', companies: 'Companies', contacts: 'Contacts', orders: 'Orders', catalog: 'Catalog', projects: 'Projects', tasks: 'Tasks', myDay: 'My day', communications: 'Communications', helpdesk: 'Helpdesk', documents: 'Documents', financePage: 'Finance', analytics: 'Analytics', automations: 'Automations', integrations: 'Integrations', marketplace: 'Marketplace', plugins: 'Plugin manager', aiControl: 'AI control center', aiCalls: 'AI calls', aiAnalytics: 'AI analytics', team: 'Team', services: 'Nexio services', billing: 'Subscription', settings: 'Settings', platformSuperadmin: 'Platform admin', upgrade: 'Upgrade to Pro', viewPlans: 'View plans', notifications: 'Notifications', markAllRead: 'Mark all as read', noNotifications: 'No new notifications', logout: 'Log out', closeMenu: 'Close menu', menu: 'Menu', language: 'Interface language', timezone: 'Time zone', save: 'Save changes', localization: 'Localization', companyProfile: 'Company profile', darkTheme: 'Dark', lightTheme: 'Light', features: 'Features', pricing: 'Pricing', security: 'Security', documentsNav: 'Documents', login: 'Log in', startFree: 'Start free', workspace: 'Open workspace', heroTitle: 'Manage sales, processes and AI agents', heroText: 'Nexio CRM is the workspace for leads, deals, orders, projects, finance and analytics — all in one place.', freeTrial: '14-day free trial', noCard: 'No credit card', readyFunnel: 'Ready-to-use funnel',
    dashboardTitle: "Here's what's happening with your business", dashboardSubtitle: 'Track performance, manage your tasks, and stay on top of everything in one place.', totalRevenue: 'Total revenue', newLeads: 'New leads', activeDeals: 'Active deals', conversionRate: 'Conversion rate', forecast: 'Forecast', salesRevenue: 'Sales & revenue', pipelineByStage: 'Pipeline by stage', myTasks: 'My tasks', aiAgentActivity: 'AI agent activity', recentLeads: 'Recent leads', recentOrders: 'Recent orders', noData: 'No data yet', retry: 'Retry',
    siteBanners: 'Site banners', siteAnalytics: 'Site analytics',
    inventory: 'Inventory', pluginsHeading: 'Plugins', primaryNavigation: 'Main navigation', platformNavigation: 'Platform navigation', platformWorkspace: 'Go to workspace', platformPanel: 'Nexio Lab control panel', close: 'Close', companySelect: 'Select a company', roleLabel: 'Role', upgradeDescription: 'Unlock AI agents, voice calls, and advanced analytics.',
  },
  ru: {
    sales: 'Продажи', work: 'Работа', clients: 'Клиенты', finance: 'Финансы и данные', company: 'Компания', dashboard: 'Дашборд', leads: 'Лиды', deals: 'Сделки', companies: 'Компании', contacts: 'Контакты', orders: 'Заказы', catalog: 'Каталог', projects: 'Проекты', tasks: 'Задачи', myDay: 'Мой день', communications: 'Коммуникации', helpdesk: 'Поддержка', documents: 'Документы', financePage: 'Финансы', analytics: 'Аналитика', automations: 'Автоматизация', integrations: 'Интеграции', marketplace: 'Маркетплейс', plugins: 'Менеджер плагинов', aiControl: 'AI-центр управления', aiCalls: 'AI-звонки', aiAnalytics: 'AI-аналитика', team: 'Команда', services: 'Услуги Nexio', billing: 'Подписка', settings: 'Настройки', platformSuperadmin: 'Администратор платформы', upgrade: 'Перейти на Pro', viewPlans: 'Смотреть тарифы', notifications: 'Уведомления', markAllRead: 'Прочитать всё', noNotifications: 'Новых уведомлений нет', logout: 'Выйти', closeMenu: 'Закрыть меню', menu: 'Меню', language: 'Язык интерфейса', timezone: 'Часовой пояс', save: 'Сохранить изменения', localization: 'Локализация', companyProfile: 'Профиль компании', darkTheme: 'Тёмная', lightTheme: 'Светлая', features: 'Возможности', pricing: 'Тарифы', security: 'Безопасность', documentsNav: 'Документы', login: 'Войти', startFree: 'Начать бесплатно', workspace: 'Открыть рабочее пространство', heroTitle: 'Управляйте продажами, процессами и AI-агентами', heroText: 'Nexio CRM — рабочее пространство для лидов, сделок, заказов, проектов, финансов и аналитики в одном месте.', freeTrial: '14 дней бесплатно', noCard: 'Без карты', readyFunnel: 'Воронка настроена сразу',
    dashboardTitle: 'Что происходит в вашем бизнесе', dashboardSubtitle: 'Контролируйте показатели, задачи и важные процессы в одном месте.', totalRevenue: 'Общая выручка', newLeads: 'Новые лиды', activeDeals: 'Активные сделки', conversionRate: 'Конверсия', forecast: 'Прогноз', salesRevenue: 'Продажи и выручка', pipelineByStage: 'Воронка по этапам', myTasks: 'Мои задачи', aiAgentActivity: 'Активность AI-агентов', recentLeads: 'Последние лиды', recentOrders: 'Последние заказы', noData: 'Данных пока нет', retry: 'Повторить',
    siteBanners: 'Баннеры сайта', siteAnalytics: 'Аналитика сайта',
    inventory: 'Склад', pluginsHeading: 'Плагины', primaryNavigation: 'Основная навигация', platformNavigation: 'Навигация платформы', platformWorkspace: 'В рабочее пространство', platformPanel: 'Панель оператора Nexio Lab', close: 'Закрыть', companySelect: 'Выберите компанию', roleLabel: 'Роль', upgradeDescription: 'Откройте AI-агентов, голосовые звонки и расширенную аналитику.',
  },
  uz: {
    sales: 'Savdo', work: 'Ish', clients: 'Mijozlar', finance: 'Moliya va ma’lumotlar', company: 'Kompaniya', dashboard: 'Boshqaruv paneli', leads: 'Lidlar', deals: 'Bitimlar', companies: 'Kompaniyalar', contacts: 'Kontaktlar', orders: 'Buyurtmalar', catalog: 'Katalog', projects: 'Loyihalar', tasks: 'Vazifalar', myDay: 'Bugungi ishlar', communications: 'Kommunikatsiyalar', helpdesk: 'Qo‘llab-quvvatlash', documents: 'Hujjatlar', financePage: 'Moliya', analytics: 'Analitika', automations: 'Avtomatlashtirish', integrations: 'Integratsiyalar', marketplace: 'Marketplace', plugins: 'Pluginlar boshqaruvi', aiControl: 'AI boshqaruv markazi', aiCalls: 'AI qo‘ng‘iroqlar', aiAnalytics: 'AI analitika', team: 'Jamoa', services: 'Nexio xizmatlari', billing: 'Obuna', settings: 'Sozlamalar', platformSuperadmin: 'Platforma administratori', upgrade: 'Pro tarifga o‘tish', viewPlans: 'Tariflarni ko‘rish', notifications: 'Bildirishnomalar', markAllRead: 'Barchasini o‘qilgan deb belgilash', noNotifications: 'Yangi bildirishnomalar yo‘q', logout: 'Chiqish', closeMenu: 'Menyuni yopish', menu: 'Menyu', language: 'Interfeys tili', timezone: 'Vaqt zonasi', save: 'O‘zgarishlarni saqlash', localization: 'Mahalliylashtirish', companyProfile: 'Kompaniya profili', darkTheme: 'To‘q', lightTheme: 'Yorug‘', features: 'Imkoniyatlar', pricing: 'Tariflar', security: 'Xavfsizlik', documentsNav: 'Hujjatlar', login: 'Kirish', startFree: 'Bepul boshlash', workspace: 'Ish maydonini ochish', heroTitle: 'Savdo, jarayonlar va AI agentlarini boshqaring', heroText: 'Nexio CRM — lidlar, bitimlar, buyurtmalar, loyihalar, moliya va analitikani bir joyga jamlagan ish maydoni.', freeTrial: '14 kun bepul', noCard: 'Karta kerak emas', readyFunnel: 'Voronka darhol tayyor',
    dashboardTitle: 'Biznesingizda nimalar bo‘layotgani', dashboardSubtitle: 'Natijalar, vazifalar va muhim jarayonlarni bir joyda boshqaring.', totalRevenue: 'Jami daromad', newLeads: 'Yangi lidlar', activeDeals: 'Faol bitimlar', conversionRate: 'Konversiya', forecast: 'Prognoz', salesRevenue: 'Savdo va daromad', pipelineByStage: 'Bosqichlar bo‘yicha voronka', myTasks: 'Vazifalarim', aiAgentActivity: 'AI agentlar faolligi', recentLeads: 'So‘nggi lidlar', recentOrders: 'So‘nggi buyurtmalar', noData: 'Hali ma’lumot yo‘q', retry: 'Qayta urinish',
    siteBanners: 'Sayt bannerlari', siteAnalytics: 'Sayt analitikasi',
    inventory: 'Ombor', pluginsHeading: 'Plaginlar', primaryNavigation: 'Asosiy navigatsiya', platformNavigation: 'Platforma navigatsiyasi', platformWorkspace: 'Ish maydoniga o‘tish', platformPanel: 'Nexio Lab operator paneli', close: 'Yopish', companySelect: 'Kompaniyani tanlang', roleLabel: 'Rol', upgradeDescription: 'AI agentlar, ovozli qo‘ng‘iroqlar va kengaytirilgan analitikani yoqing.',
  },
}

interface I18nContext { locale: Locale; setLocale: (locale: Locale) => void; t: (key: string) => string }
const Context = createContext<I18nContext | null>(null)

export function I18nProvider({ children }: { children: ReactNode }) {
  const [locale, setLocaleState] = useState<Locale>(() => {
    const saved = localStorage.getItem('nexio_locale')
    return saved === 'en' || saved === 'uz' || saved === 'ru' ? saved : 'uz'
  })
  const setLocale = useCallback((next: Locale) => {
    setLocaleState(next)
    localStorage.setItem('nexio_locale', next)
  }, [])
  useEffect(() => { document.documentElement.lang = locale }, [locale])
  const value = useMemo(() => ({ locale, setLocale, t: (key: string) => common[locale][key] ?? common.en[key] ?? key }), [locale])
  return <Context.Provider value={value}>{children}</Context.Provider>
}

export function useI18n() {
  const value = useContext(Context)
  if (!value) throw new Error('useI18n must be used inside I18nProvider')
  return value
}

/** Page-scoped translations: `Dict` is `{ uz, ru, en }`, each a flat key → text map ({name} placeholders are filled from `vars`). */
export type Dict = Record<Locale, Record<string, string>>
export function useTr(dict: Dict) {
  const { locale } = useI18n()
  return useCallback((key: string, vars?: Record<string, string | number>) => {
    let text = dict[locale][key] ?? dict.en[key] ?? key
    if (vars) for (const [k, v] of Object.entries(vars)) text = text.split(`{${k}}`).join(String(v))
    return text
  }, [dict, locale])
}
