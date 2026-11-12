import type { LocalizedDoc } from './types'

export const cookies: LocalizedDoc = {
  uz: {
    title: 'Cookie siyosati',
    sections: [
      { heading: '1. Majburiy cookie', paragraphs: ["Nexio CRM faqat bitta cookie qo'yadi: nexio_session. U tizimga xavfsiz kirish uchun zarur, reklama yoki saytlararo kuzatuv uchun ishlatilmaydi. Atributlari: HttpOnly (JavaScript o'qiy olmaydi), SameSite=Lax, HTTPS'da Secure; muddati — 14 kungacha, chiqishda o'chiriladi."] },
      { heading: "2. Brauzer xotirasi (localStorage)", paragraphs: ["Qulaylik uchun qurilmangizda quyidagi sozlamalar saqlanadi (serverga yuborilmaydi): nexio_locale — til; nexio_theme — yorug'/qorong'i mavzu (tanlamaguningizcha tizim sozlamasiga ergashadi); nexio_quote_filters — takliflar ro'yxati filtri; nexio_telegram_prompt_seen — Telegram taklifi ko'rsatilgani. Ularni brauzer sozlamalarida o'chirishingiz mumkin."] },
      { heading: '3. Mobil ilova', paragraphs: ["Ilovada cookie o'rniga sessiya identifikatori qurilmaning himoyalangan xotirasida (iOS Keychain / Android Keystore) saqlanadi va faqat bizning serverimizga yuboriladi. Reklama identifikatori ishlatilmaydi."] },
      { heading: '4. Analitika va rozilik', paragraphs: ["Hozir uchinchi tomon analitika va reklama cookie'lari yo'q, shuning uchun rozilik banneri ko'rsatilmaydi. Kelajakda analitika qo'shilsa, u sukut bo'yicha o'chiq bo'ladi, faqat sizning roziligingizdan keyin yoqiladi (rozilikni istalgan vaqtda o'zgartirish mumkin) va ushbu siyosat yangilanadi."] },
      { heading: '5. Boshqarish', paragraphs: ["Cookie'ni brauzer sozlamalarida o'chirishingiz mumkin. Majburiy cookie bloklansa, CRM'ga kirish ishlamaydi."] },
    ],
  },
  ru: {
    title: 'Политика использования cookie',
    sections: [
      { heading: '1. Обязательная cookie', paragraphs: ['Nexio CRM устанавливает одну cookie: nexio_session. Она нужна для безопасного входа и не используется для рекламы или межсайтового отслеживания. Атрибуты: HttpOnly (JavaScript её не читает), SameSite=Lax, Secure на HTTPS; срок — до 14 дней, при выходе удаляется.'] },
      { heading: '2. Память браузера (localStorage)', paragraphs: ['Для удобства на вашем устройстве хранятся настройки (на сервер не отправляются): nexio_locale — язык; nexio_theme — светлая/тёмная тема (пока вы не выбрали, следует настройке системы); nexio_quote_filters — фильтры списка предложений; nexio_telegram_prompt_seen — показано ли предложение подключить Telegram. Их можно удалить в настройках браузера.'] },
      { heading: '3. Мобильное приложение', paragraphs: ['Вместо cookie идентификатор сессии хранится в защищённом хранилище устройства (iOS Keychain / Android Keystore) и отправляется только на наш сервер. Рекламный идентификатор не используется.'] },
      { heading: '4. Аналитика и согласие', paragraphs: ['Сейчас сторонние аналитические и рекламные cookie не применяются, поэтому баннер согласия не показывается. Если аналитика будет добавлена, она будет отключена по умолчанию, включится только после вашего согласия (его можно изменить в любой момент), а эта политика будет обновлена.'] },
      { heading: '5. Управление', paragraphs: ['Вы можете удалить cookie в настройках браузера. После блокировки обязательной cookie вход в CRM работать не будет.'] },
    ],
  },
  en: {
    title: 'Cookie Policy',
    sections: [
      { heading: '1. The essential cookie', paragraphs: ['Nexio CRM sets one cookie: nexio_session. It is required for secure sign-in and is not used for advertising or cross-site tracking. Attributes: HttpOnly (JavaScript cannot read it), SameSite=Lax, Secure on HTTPS; lifetime up to 14 days, removed on sign-out.'] },
      { heading: '2. Browser storage (localStorage)', paragraphs: ['For convenience your device stores these settings (never sent to the server): nexio_locale, your language; nexio_theme, light/dark theme (follows your system setting until you choose); nexio_quote_filters, the filters of the proposals list; nexio_telegram_prompt_seen, whether the Telegram prompt was shown. You can clear them in your browser settings.'] },
      { heading: '3. Mobile app', paragraphs: ['Instead of a cookie, the session identifier is kept in the device secure storage (iOS Keychain / Android Keystore) and is sent only to our server. The advertising identifier is not used.'] },
      { heading: '4. Analytics and consent', paragraphs: ['We do not currently use third-party analytics or advertising cookies, so no consent banner is shown. If analytics is added, it will be off by default, switched on only after your consent (which you can change at any time), and this policy will be updated.'] },
      { heading: '5. Managing cookies', paragraphs: ['You can delete cookies in your browser settings. If the essential cookie is blocked, signing in to the CRM will not work.'] },
    ],
  },
}
