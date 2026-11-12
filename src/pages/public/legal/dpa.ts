import type { LocalizedDoc } from './types'

export const dpa: LocalizedDoc = {
  uz: {
    title: "Ma'lumotlarni qayta ishlash shartnomasi (DPA)",
    sections: [
      { heading: '1. Tomonlar va rollar', paragraphs: ["Kompaniya-mijoz CRM ma'lumotlari bo'yicha operator, Nexio Lab esa ishlov beruvchi. Nexio Lab ma'lumotlarni faqat mijozning hujjatlashtirilgan ko'rsatmalari asosida va xizmat ko'rsatish maqsadida qayta ishlaydi."] },
      { heading: '2. Predmet va toifalar', paragraphs: ["Predmet: CRM xizmatini taqdim etish. Ma'lumotlar: mijoz kiritgan kontaktlar, kompaniyalar, bitimlar, buyurtmalar, hujjatlar, yozishmalar, (yoqilsa) qo'ng'iroq yozuvlari va transkriptlar. Sub'ektlar: mijozning xodimlari, mijozlari va hamkorlari. Qonuniy asos va rozilik uchun mijoz javobgar."] },
      { heading: '3. Maxfiylik va xavfsizlik', paragraphs: ["Kirish faqat vakolatli shaxslarga, eng kam imtiyoz tamoyili bo'yicha beriladi. Ijarachilar ma'lumotlari bir-biridan ajratilgan; sessiyalar, ikki bosqichli autentifikatsiya, audit jurnali, kunlik zaxira nusxa (90 kun saqlanadi) va hodisalarga javob berish tartibi qo'llanadi."] },
      { heading: '4. Sub-ishlov beruvchilar', paragraphs: ["Asosiy infratuzilma O'zbekistonda joylashgan. Sub-ishlov beruvchilar: elektron pochta xizmati, Google Firebase Cloud Messaging (push), Telegram, Click/Payme (to'lov), mijoz ulagan telefoniya/WhatsApp provayderlari va (mijoz yoqsa) AI-provayder (hozir GroqCloud). Nexio Lab ularning majburiyatlarini shartnoma bilan ta'minlaydi va ro'yxatdagi o'zgarishlar haqida mijozga oldindan xabar beradi."] },
      { heading: "5. Yordam va hodisalar", paragraphs: ["Nexio Lab mijozga sub'ekt so'rovlariga javob berishda, xavflarni baholashda va o'chirish/qaytarish talablarini bajarishda yordam beradi. Shaxsga doir ma'lumotlar buzilishi aniqlansa, mijozga asossiz kechiktirmasdan, imkon qadar 72 soat ichida xabar beradi."] },
      { heading: "6. Tugatish", paragraphs: ["Shartnoma tugagach, mijoz so'roviga ko'ra ma'lumotlar qaytariladi yoki 30 kun ichida o'chiriladi; zaxira nusxalar 90 kun ichida aylanishdan chiqariladi, qonun talab qilgan yozuvlar bundan mustasno."] },
    ],
  },
  ru: {
    title: 'Соглашение об обработке данных (DPA)',
    sections: [
      { heading: '1. Стороны и роли', paragraphs: ['Компания-клиент является оператором CRM-данных, Nexio Lab — обработчиком. Nexio Lab обрабатывает данные только по документированным инструкциям клиента и для оказания сервиса.'] },
      { heading: '2. Предмет и категории', paragraphs: ['Предмет: предоставление CRM-сервиса. Данные: внесённые клиентом контакты, компании, сделки, заказы, документы, переписка, (если включено) записи звонков и транскрипты. Субъекты: сотрудники, клиенты и партнёры клиента. За законное основание и согласия отвечает клиент.'] },
      { heading: '3. Конфиденциальность и безопасность', paragraphs: ['Доступ предоставляется только уполномоченным лицам по принципу минимальных привилегий. Данные арендаторов изолированы; применяются управление сессиями, двухфакторная аутентификация, журнал аудита, ежедневное резервное копирование (хранится 90 дней) и процедура реагирования на инциденты.'] },
      { heading: '4. Субобработчики', paragraphs: ['Основная инфраструктура находится в Узбекистане. Субобработчики: почтовый сервис, Google Firebase Cloud Messaging (push), Telegram, Click/Payme (платежи), телефония/WhatsApp-провайдеры, которых подключил клиент, и (если клиент включил) AI-провайдер (сейчас GroqCloud). Nexio Lab обеспечивает их обязательства договорами и заранее уведомляет клиента об изменениях списка.'] },
      { heading: '5. Помощь и инциденты', paragraphs: ['Nexio Lab помогает клиенту отвечать на запросы субъектов, оценивать риски и исполнять требования по удалению и возврату данных. О нарушении защиты персональных данных клиент уведомляется без неоправданной задержки, по возможности в течение 72 часов.'] },
      { heading: '6. Прекращение', paragraphs: ['После окончания договора данные по запросу клиента возвращаются или удаляются в течение 30 дней; резервные копии выводятся из ротации в течение 90 дней, за исключением записей, которые обязан хранить закон.'] },
    ],
  },
  en: {
    title: 'Data Processing Agreement (DPA)',
    sections: [
      { heading: '1. Parties and roles', paragraphs: ['The customer company is the controller of CRM data and Nexio Lab is the processor. Nexio Lab processes data only on the customer\'s documented instructions and to provide the service.'] },
      { heading: '2. Subject matter and categories', paragraphs: ['Subject matter: providing the CRM service. Data: contacts, companies, deals, orders, documents, messages entered by the customer and, if enabled, call recordings and transcripts. Data subjects: the customer\'s employees, clients and partners. The customer is responsible for the lawful basis and consents.'] },
      { heading: '3. Confidentiality and security', paragraphs: ['Access is limited to authorised persons on a least-privilege basis. Tenant data is isolated; session management, two-factor authentication, audit logging, daily backups (kept for 90 days) and an incident-response procedure apply.'] },
      { heading: '4. Sub-processors', paragraphs: ['The core infrastructure is in Uzbekistan. Sub-processors: an email service, Google Firebase Cloud Messaging (push), Telegram, Click/Payme (payments), telephony/WhatsApp providers connected by the customer and, if the customer enables it, an AI provider (currently GroqCloud). Nexio Lab binds them by contract and gives the customer advance notice of changes to the list.'] },
      { heading: '5. Assistance and incidents', paragraphs: ['Nexio Lab helps the customer respond to data-subject requests, assess risks and meet deletion and return obligations. The customer is notified of a personal-data breach without undue delay, where possible within 72 hours.'] },
      { heading: '6. Termination', paragraphs: ['After the contract ends, data is returned or deleted within 30 days on the customer\'s request; backups leave rotation within 90 days, except records the law requires to be kept.'] },
    ],
  },
}
