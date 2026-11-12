import { CONTACT_EMAIL, OPERATOR_PLACEHOLDER, type LocalizedDoc } from './types'

const E = CONTACT_EMAIL
export const privacy: LocalizedDoc = {
  uz: {
    title: 'Maxfiylik siyosati',
    sections: [
      { heading: '1. Biz kimmiz va bu siyosat kimga tegishli', paragraphs: [
        `Nexio CRM (sayt, veb-ilova, iOS va Android ilovalari) operatori — Nexio Lab ${OPERATOR_PLACEHOLDER.uz}. Savollar va so'rovlar: ${E}.`,
        "Ikki rolni farqlaymiz. Hisobingiz, kirish va xavfsizlik ma'lumotlari bo'yicha Nexio Lab operator hisoblanadi. CRM'ga kompaniya kiritgan ma'lumotlar (mijozlar, kontaktlar, bitimlar, hujjatlar, qo'ng'iroqlar) bo'yicha kompaniya-mijoz operator, Nexio Lab esa uning topshirig'i bilan ishlov beruvchi (qarang: Ma'lumotlarni qayta ishlash shartnomasi, DPA).",
        "Siyosat O'zbekiston Respublikasining «Shaxsga doir ma'lumotlar to'g'risida»gi qonuniga muvofiq tuzilgan. Xizmatdan MDH davlatlaridagi foydalanuvchilar ham foydalanishi mumkin; ularga o'z mamlakatlarining majburiy qoidalari ham qo'llanishi mumkin."] },
      { heading: "2. Qanday ma'lumotlarni qayta ishlaymiz", paragraphs: [
        "Hisob: ism, ish emaili, parol xeshi (parolning o'zi saqlanmaydi), rol, kompaniya nomi, til va mavzu sozlamalari, ikki bosqichli autentifikatsiya sirlari (shifrlangan).",
        "Texnik: IP-manzil, brauzer/qurilma turi (User-Agent), kirish vaqti, sessiya identifikatori, xavfsizlik jurnali (audit), push-bildirishnoma tokeni (mobil ilovada), rozilik tarixi (siyosat versiyasi, vaqt, IP).",
        "CRM ma'lumotlari: kompaniya kiritadigan kontaktlar, bitimlar, buyurtmalar, vazifalar, hujjatlar, yozishmalar. Ularning qonuniyligi uchun kompaniya javobgar.",
        "AI va telefoniya (faqat yoqilganda): agent sozlamalari, so'rovlar, bilimlar bazasi, telefon raqami, rozilik asosi, qo'ng'iroq natijasi, xulosa, transkript va (kompaniya yoqsa) yozuv.",
        "To'lov: to'lov holati va summasi. Karta ma'lumotlari bizga o'tmaydi — ular to'lov provayderida (Click, Payme) qayta ishlanadi.",
        "Ilova ruxsatlari: kamera va galereya (faqat siz hujjat/rasm biriktirganda), bildirishnomalar, Face ID/barmoq izi. Biometrik ma'lumotlar qurilmangizdan chiqmaydi: ilova faqat «tasdiqlandi / rad etildi» natijasini oladi."] },
      { heading: "3. Maqsadlar va huquqiy asoslar", paragraphs: [
        "Xizmatni taqdim etish va himoya qilish, autentifikatsiya, kompaniyalar ma'lumotlarini bir-biridan ajratish, qo'llab-quvvatlash, hisob-kitob, suiiste'molning oldini olish. Asoslar: shartnomani bajarish, sizning roziligingiz, xavfsizlikdagi qonuniy manfaat va qonun talablari.",
        "Ro'yxatdan o'tishda Shartlar va ushbu siyosatga rozilik alohida belgilanadi va isbot uchun saqlanadi. Rozilikni istalgan vaqtda qaytarib olishingiz mumkin (hisobni o'chirish orqali yoki so'rov bilan); bu avvalgi ishlov berishning qonuniyligiga ta'sir qilmaydi.",
        "Reklama va xatti-harakatlarni kuzatish uchun ma'lumotlaringizdan foydalanmaymiz va ularni sotmaymiz."] },
      { heading: "4. Ma'lumotlar kimga uzatiladi", paragraphs: [
        "Faqat xizmat ishlashi uchun zarur bo'lgan hajmda: elektron pochta yuboruvchi xizmat (tasdiqlash va parolni tiklash xatlari); Google Firebase Cloud Messaging (mobil push — qurilma tokeni va xabar matni); Telegram (agar bildirishnomalarni Telegram'ga ulagan bo'lsangiz); Click va Payme (to'lovlar); kompaniya ulagan telefoniya va WhatsApp provayderlari.",
        "AI-provayder (hozir GroqCloud, AQSh) sukut bo'yicha o'chiq. Kompaniya administratori AI'ni yoqqandagina, kerakli minimal so'rov qismi unga yuboriladi; yoqishdan oldin tizim chet elga uzatish haqida ogohlantiradi. AI so'rovlariga alohida toifadagi ma'lumotlarni kiritmang.",
        "Ma'lumotlar sud, prokuratura va boshqa vakolatli organlarga faqat qonunda belgilangan asosda beriladi."] },
      { heading: "5. Ma'lumotlar qayerda saqlanadi", paragraphs: [
        "Asosiy ma'lumotlar bazasi, zaxira nusxalar va bot serveri O'zbekiston hududidagi serverlarda joylashgan. Fuqarolarning shaxsga doir ma'lumotlarini mahalliy serverlarda saqlash talabiga amal qilamiz.",
        "Faqat 4-bandda sanalgan xizmatlar (pochta, push, Telegram, AI) ma'lumotning cheklangan qismini chet elda qayta ishlashi mumkin. Biz ularni shartnoma va texnik choralar bilan cheklaymiz va faqat zarur minimumni yuboramiz."] },
      { heading: "6. Saqlash muddatlari", paragraphs: [
        "Faol ma'lumotlar hisob yoki shartnoma amal qilguncha saqlanadi. Sessiya — 14 kungacha (chiqishda yoki parol almashtirilganda o'chiriladi); parolni tiklash havolasi — 1 soat; xavfsizlik jurnali — 12 oygacha.",
        "Hisobni o'chirishni so'raganingizda shaxsiy hisobingiz 30 kun ichida anonimlashtiriladi (shu muddat ichida tizimga kirsangiz, o'chirish bekor bo'ladi). Zaxira nusxalar 90 kun ichida aylanishdan chiqariladi. Majburiy buxgalteriya yozuvlari qonun talab qilgan muddat saqlanadi.",
        "CRM ma'lumotlari va qo'ng'iroq yozuvlarining saqlash muddatini kompaniya-mijoz belgilaydi (tavsiya: yozuvlar uchun 90 kun); kompaniya so'roviga ko'ra ular o'chiriladi."] },
      { heading: "7. Xavfsizlik", paragraphs: [
        "Kompaniyalar ma'lumotlari bir-biridan ajratilgan, kirish rollar bo'yicha cheklangan, parollar xeshlanadi, ikki bosqichli autentifikatsiya mavjud, sessiya cookie'si HttpOnly, mobil ilovada sessiya Keychain/Keystore'da saqlanadi, aloqa kanali shifrlangan, urinishlar cheklangan, muhim harakatlar jurnalga yoziladi.",
        "Mutlaq xavfsizlikni kafolatlab bo'lmaydi. Jiddiy hodisa yuz bersa, ta'sirlangan tomonlarga asossiz kechiktirmasdan xabar beramiz."] },
      { heading: "8. Sizning huquqlaringiz", paragraphs: [
        `Siz ma'lumotlaringizga kirish, to'g'rilash, nusxa olish, cheklash, e'tiroz bildirish, rozilikni qaytarib olish va o'chirishni talab qilish huquqiga egasiz. Ilova ichida: Sozlamalar → Xavfsizlik → «Hisobni o'chirish». Boshqa so'rovlar: ${E}. Shaxsingizni tekshirib, 30 kun ichida javob beramiz.`,
        "CRM ma'lumotlari bo'yicha so'rovni avvalo o'z kompaniyangizga yuboring: ularning operatori kompaniya. Biz kompaniyaga yordam beramiz. Huquqlaringiz buzilgan deb hisoblasangiz, vakolatli davlat organiga murojaat qilishingiz mumkin."] },
      { heading: "9. Mobil ilovalar", paragraphs: [
        "Ilova reklama identifikatorini o'qimaydi, uchinchi tomon analitika va reklama SDK'larini o'z ichiga olmaydi va sizni boshqa ilovalar bo'ylab kuzatmaydi. Push-bildirishnomalarni istalgan vaqtda qurilma sozlamalarida o'chirishingiz mumkin. Ilova ichida hisobni o'chirish mavjud."] },
      { heading: "10. Yosh chegarasi", paragraphs: ["Xizmat 16 yoshdan oshgan foydalanuvchilar uchun mo'ljallangan. Bola ma'lumoti yig'ilganini bilib qolsak, uni o'chiramiz."] },
      { heading: "11. O'zgarishlar", paragraphs: ["Jiddiy o'zgarishlar yangi versiya va kuchga kirish sanasi bilan e'lon qilinadi. Yangi rozilik talab qilinsa, tizim uni alohida so'raydi. Cookie va brauzer xotirasi: «Cookie siyosati» hujjatiga qarang."] },
    ],
  },
  ru: {
    title: 'Политика конфиденциальности',
    sections: [
      { heading: '1. Кто мы и на кого распространяется политика', paragraphs: [
        `Оператор Nexio CRM (сайт, веб-приложение, приложения для iOS и Android) — Nexio Lab ${OPERATOR_PLACEHOLDER.ru}. Вопросы и запросы: ${E}.`,
        'Мы различаем две роли. По данным вашего аккаунта, входа и безопасности оператором является Nexio Lab. По данным, которые компания-клиент вносит в CRM (клиенты, контакты, сделки, документы, звонки), оператором является компания, а Nexio Lab — обработчиком по её поручению (см. Соглашение об обработке данных, DPA).',
        'Политика составлена с учётом закона Республики Узбекистан «О персональных данных». Сервисом могут пользоваться пользователи из стран СНГ; к ним также могут применяться обязательные правила их страны.'] },
      { heading: '2. Какие данные мы обрабатываем', paragraphs: [
        'Аккаунт: имя, рабочий email, хеш пароля (сам пароль не хранится), роль, название компании, настройки языка и темы, секреты двухфакторной аутентификации (в зашифрованном виде).',
        'Технические: IP-адрес, тип браузера/устройства (User-Agent), время входа, идентификатор сессии, журнал безопасности (аудит), токен push-уведомлений (в мобильном приложении), история согласий (версия политики, время, IP).',
        'CRM-данные: контакты, сделки, заказы, задачи, документы, переписка, которые вносит компания. За законность их внесения отвечает компания.',
        'AI и телефония (только если включены): настройки агента, запросы, база знаний, номер телефона, основание согласия, результат звонка, резюме, транскрипт и (если включила компания) запись.',
        'Платежи: статус и сумма платежа. Данные банковской карты до нас не доходят: их обрабатывает платёжный провайдер (Click, Payme).',
        'Разрешения приложения: камера и галерея (только когда вы прикрепляете документ или фото), уведомления, Face ID/отпечаток. Биометрия не покидает устройство: приложение получает только результат «подтверждено / отклонено».'] },
      { heading: '3. Цели и правовые основания', paragraphs: [
        'Предоставление и защита сервиса, аутентификация, изоляция данных компаний, поддержка, биллинг, предотвращение злоупотреблений. Основания: исполнение договора, ваше согласие, законный интерес в безопасности и требования закона.',
        'При регистрации согласие с Условиями и настоящей политикой отмечается отдельно и сохраняется как доказательство. Вы можете отозвать согласие в любой момент (удалением аккаунта или запросом); это не влияет на законность прежней обработки.',
        'Мы не используем ваши данные для рекламы и слежки за поведением и не продаём их.'] },
      { heading: '4. Кому передаются данные', paragraphs: [
        'Только в объёме, необходимом для работы сервиса: почтовый сервис (письма подтверждения и сброса пароля); Google Firebase Cloud Messaging (мобильные push — токен устройства и текст уведомления); Telegram (если вы подключили уведомления в Telegram); Click и Payme (платежи); телефония и WhatsApp-провайдеры, которых подключила компания.',
        'AI-провайдер (сейчас GroqCloud, США) по умолчанию выключен. Только когда администратор компании включает AI, ему передаётся минимально необходимая часть запроса; перед включением система предупреждает о передаче за рубеж. Не помещайте в AI-запросы специальные категории данных.',
        'Суду, прокуратуре и другим уполномоченным органам данные передаются только по основаниям, установленным законом.'] },
      { heading: '5. Где хранятся данные', paragraphs: [
        'Основная база данных, резервные копии и сервер бота находятся на серверах на территории Узбекистана. Мы соблюдаем требование о хранении персональных данных граждан на локальных серверах.',
        'Только сервисы из раздела 4 (почта, push, Telegram, AI) могут обрабатывать ограниченную часть данных за рубежом. Мы ограничиваем их договорами и техническими мерами и передаём только необходимый минимум.'] },
      { heading: '6. Сроки хранения', paragraphs: [
        'Активные данные хранятся, пока действует аккаунт или договор. Сессия — до 14 дней (удаляется при выходе или смене пароля); ссылка сброса пароля — 1 час; журнал безопасности — до 12 месяцев.',
        'После запроса на удаление личный аккаунт обезличивается в течение 30 дней (вход в этот срок отменяет удаление). Резервные копии выводятся из ротации в течение 90 дней. Обязательные бухгалтерские записи хранятся столько, сколько требует закон.',
        'Сроки хранения CRM-данных и записей звонков определяет компания-клиент (рекомендуемый срок для записей — 90 дней); по запросу компании они удаляются.'] },
      { heading: '7. Безопасность', paragraphs: [
        'Данные компаний изолированы друг от друга, доступ ограничен ролями, пароли хранятся в виде хеша, доступна двухфакторная аутентификация, сессионная cookie имеет флаг HttpOnly, в мобильном приложении сессия хранится в Keychain/Keystore, канал связи шифруется, попытки входа ограничиваются, значимые действия журналируются.',
        'Абсолютная безопасность не гарантируется. О существенном инциденте мы уведомим затронутые стороны без неоправданной задержки.'] },
      { heading: '8. Ваши права', paragraphs: [
        `Вы вправе получить доступ к данным, исправить их, получить копию, ограничить обработку, возразить, отозвать согласие и потребовать удаления. В приложении: Настройки → Безопасность → «Удалить аккаунт». Остальные запросы: ${E}. Мы проверим личность и ответим в течение 30 дней.`,
        'По CRM-данным обращайтесь сначала в свою компанию: оператор этих данных — она, мы ей помогаем. Если вы считаете, что ваши права нарушены, вы можете обратиться в уполномоченный государственный орган.'] },
      { heading: '9. Мобильные приложения', paragraphs: [
        'Приложение не читает рекламный идентификатор, не содержит сторонних SDK аналитики и рекламы и не отслеживает вас в других приложениях. Push-уведомления можно отключить в настройках устройства. Удаление аккаунта доступно прямо в приложении.'] },
      { heading: '10. Возраст', paragraphs: ['Сервис предназначен для пользователей старше 16 лет. Если мы узнаем, что собраны данные ребёнка, мы их удалим.'] },
      { heading: '11. Изменения', paragraphs: ['Существенные изменения публикуются с новой версией и датой вступления в силу. Если требуется новое согласие, сервис запросит его отдельно. О cookie и памяти браузера см. «Политику использования cookie».'] },
    ],
  },
  en: {
    title: 'Privacy Policy',
    sections: [
      { heading: '1. Who we are and who this applies to', paragraphs: [
        `The operator of Nexio CRM (website, web app, iOS and Android apps) is Nexio Lab ${OPERATOR_PLACEHOLDER.en}. Questions and requests: ${E}.`,
        'We act in two roles. For your account, sign-in and security data, Nexio Lab is the controller. For data a customer company enters into the CRM (clients, contacts, deals, documents, calls), the company is the controller and Nexio Lab is its processor (see the Data Processing Agreement, DPA).',
        'This policy is drafted under the Law of the Republic of Uzbekistan "On Personal Data". Users from CIS countries may also use the service; mandatory rules of their own country may apply to them as well.'] },
      { heading: '2. Data we process', paragraphs: [
        'Account: name, work email, password hash (the password itself is never stored), role, company name, language and theme settings, two-factor secrets (encrypted).',
        'Technical: IP address, browser/device type (User-Agent), sign-in time, session identifier, security (audit) log, push-notification token (mobile app), consent history (policy version, time, IP).',
        'CRM data: contacts, deals, orders, tasks, documents and messages entered by the company, which is responsible for their lawfulness.',
        'AI and telephony (only when enabled): agent settings, prompts, knowledge base, phone number, consent basis, call outcome, summary, transcript and, if the company enables it, a recording.',
        'Payments: payment status and amount. Card data never reaches us; it is processed by the payment provider (Click, Payme).',
        'App permissions: camera and photo library (only when you attach a document or photo), notifications, Face ID/fingerprint. Biometrics never leave your device: the app only receives "confirmed / rejected".'] },
      { heading: '3. Purposes and legal bases', paragraphs: [
        'Providing and protecting the service, authentication, isolating company data, support, billing, preventing abuse. Bases: performance of the contract, your consent, legitimate interest in security, and legal obligations.',
        'At sign-up, consent to the Terms and to this policy is ticked separately and stored as proof. You can withdraw consent at any time (by deleting your account or by request); this does not affect the lawfulness of earlier processing.',
        'We do not use your data for advertising or behavioural tracking, and we do not sell it.'] },
      { heading: '4. Who receives data', paragraphs: [
        'Only as far as needed to run the service: an email delivery service (verification and password-reset emails); Google Firebase Cloud Messaging (mobile push: device token and notification text); Telegram (if you connected Telegram notifications); Click and Payme (payments); telephony and WhatsApp providers connected by the company.',
        'The AI provider (currently GroqCloud, USA) is off by default. Only when a company administrator turns AI on is the minimum necessary part of a request sent to it, and the system warns about the transfer abroad before it is enabled. Do not put special categories of data into AI prompts.',
        'Data is disclosed to courts, prosecutors and other competent authorities only on grounds set by law.'] },
      { heading: '5. Where data is stored', paragraphs: [
        "The primary database, backups and the bot server are located on servers in Uzbekistan. We comply with the requirement to store citizens' personal data on local servers.",
        'Only the services in section 4 (email, push, Telegram, AI) may process a limited part of the data abroad. We bind them by contract and technical measures and send only the necessary minimum.'] },
      { heading: '6. Retention', paragraphs: [
        'Active data is kept while the account or contract is active. Sessions last up to 14 days (removed on sign-out or password change); password-reset links, 1 hour; the security log, up to 12 months.',
        'After a deletion request your personal account is anonymised within 30 days (signing in during that time cancels the deletion). Backups leave rotation within 90 days. Mandatory accounting records are kept as long as the law requires.',
        "The customer company decides how long CRM data and call recordings are kept (recommended for recordings: 90 days); they are deleted on the company's request."] },
      { heading: '7. Security', paragraphs: [
        'Company data is isolated from other companies, access is limited by roles, passwords are stored as hashes, two-factor authentication is available, the session cookie is HttpOnly, the mobile app keeps the session in the Keychain/Keystore, traffic is encrypted, sign-in attempts are rate-limited, and significant actions are logged.',
        'Absolute security cannot be guaranteed. In case of a material incident we will notify affected parties without undue delay.'] },
      { heading: '8. Your rights', paragraphs: [
        `You may request access, correction, a copy, restriction, object, withdraw consent and request deletion. In the app: Settings → Security → "Delete account". Other requests: ${E}. We verify your identity and reply within 30 days.`,
        'For CRM data, contact your company first: it is the controller and we assist it. If you believe your rights were violated, you may contact the competent state authority.'] },
      { heading: '9. Mobile apps', paragraphs: [
        'The app does not read the advertising identifier, contains no third-party analytics or advertising SDKs, and does not track you across other apps. You can turn push notifications off in device settings. Account deletion is available inside the app.'] },
      { heading: '10. Age', paragraphs: ["The service is intended for users aged 16 and over. If we learn that a child's data was collected, we will delete it."] },
      { heading: '11. Changes', paragraphs: ['Material changes are published with a new version and effective date. If new consent is needed, the service asks for it separately. For cookies and browser storage see the Cookie Policy.'] },
    ],
  },
}
