import { jsx as _jsx } from "react/jsx-runtime";
import { createContext, useContext, useEffect, useMemo, useState } from 'react';
const messages = {
    en: {
        sales: 'Sales', work: 'Work', clients: 'Clients', finance: 'Finance & data', company: 'Company',
        dashboard: 'Dashboard', leads: 'Leads', deals: 'Deals', companies: 'Companies', contacts: 'Contacts', orders: 'Orders', catalog: 'Catalog',
        projects: 'Projects', tasks: 'Tasks', communications: 'Communications', documents: 'Documents', financePage: 'Finance', analytics: 'Analytics', automations: 'Automations', integrations: 'Integrations',
        team: 'Team', services: 'Order from Nexio', billing: 'Subscription', settings: 'Settings', aiCalls: 'AI calls', aiAnalytics: 'AI analytics',
        upgrade: 'Upgrade to Pro', viewPlans: 'View plans', notifications: 'Notifications', markAllRead: 'Mark all as read', noNotifications: 'No new notifications', logout: 'Log out', closeMenu: 'Close menu', menu: 'Menu',
        language: 'Interface language', timezone: 'Time zone', save: 'Save changes', localization: 'Localization', companyProfile: 'Company profile',
    },
    ru: {
        sales: 'Продажи', work: 'Работа', clients: 'Клиенты', finance: 'Финансы и данные', company: 'Компания',
        dashboard: 'Дашборд', leads: 'Лиды', deals: 'Сделки', companies: 'Компании', contacts: 'Контакты', orders: 'Заказы', catalog: 'Каталог',
        projects: 'Проекты', tasks: 'Задачи', communications: 'Коммуникации', documents: 'Документы', financePage: 'Финансы', analytics: 'Аналитика', automations: 'Автоматизации', integrations: 'Интеграции',
        team: 'Команда', services: 'Заказать у Nexio', billing: 'Подписка', settings: 'Настройки', aiCalls: 'AI-звонки', aiAnalytics: 'AI-аналитика',
        upgrade: 'Перейти на Pro', viewPlans: 'Смотреть тарифы', notifications: 'Уведомления', markAllRead: 'Прочитать всё', noNotifications: 'Новых уведомлений нет', logout: 'Выйти', closeMenu: 'Закрыть меню', menu: 'Меню',
        language: 'Язык интерфейса', timezone: 'Часовой пояс', save: 'Сохранить изменения', localization: 'Локализация', companyProfile: 'Профиль компании',
    },
    uz: {
        sales: 'Savdo', work: 'Ish', clients: 'Mijozlar', finance: 'Moliya va ma’lumotlar', company: 'Kompaniya',
        dashboard: 'Boshqaruv paneli', leads: 'Lidlar', deals: 'Bitimlar', companies: 'Kompaniyalar', contacts: 'Kontaktlar', orders: 'Buyurtmalar', catalog: 'Katalog',
        projects: 'Loyihalar', tasks: 'Vazifalar', communications: 'Kommunikatsiyalar', documents: 'Hujjatlar', financePage: 'Moliya', analytics: 'Analitika', automations: 'Avtomatlashtirish', integrations: 'Integratsiyalar',
        team: 'Jamoa', services: 'Nexio’dan buyurtma', billing: 'Obuna', settings: 'Sozlamalar', aiCalls: 'AI qo‘ng‘iroqlar', aiAnalytics: 'AI analitika',
        upgrade: 'Pro tarifga o‘tish', viewPlans: 'Tariflarni ko‘rish', notifications: 'Bildirishnomalar', markAllRead: 'Barchasini o‘qilgan deb belgilash', noNotifications: 'Yangi bildirishnomalar yo‘q', logout: 'Chiqish', closeMenu: 'Menyuni yopish', menu: 'Menyu',
        language: 'Interfeys tili', timezone: 'Vaqt zonasi', save: 'O‘zgarishlarni saqlash', localization: 'Mahalliylashtirish', companyProfile: 'Kompaniya profili',
    },
};
const Context = createContext(null);
export function I18nProvider({ children }) {
    const [locale, setLocaleState] = useState(() => {
        const saved = localStorage.getItem('nexio_locale');
        return saved === 'en' || saved === 'uz' || saved === 'ru' ? saved : 'ru';
    });
    const setLocale = (next) => { setLocaleState(next); localStorage.setItem('nexio_locale', next); };
    useEffect(() => { document.documentElement.lang = locale; }, [locale]);
    const value = useMemo(() => ({ locale, setLocale, t: (key) => messages[locale][key] ?? messages.en[key] ?? key }), [locale]);
    return _jsx(Context.Provider, { value: value, children: children });
}
export function useI18n() {
    const value = useContext(Context);
    if (!value)
        throw new Error('useI18n must be used inside I18nProvider');
    return value;
}
