# Journal Metrics — OJS 3.5 theme

[English](#english) · [العربية](#arabic) · [Українська](#ukrainian)

<a id="english"></a>
## English

A child theme of the default theme. Implements the "Journal in numbers" variant.

### Installation
1. Copy the `journalMetrics` folder into `plugins/themes/` of your OJS so you get `plugins/themes/journalMetrics/version.xml`.
2. Permissions: files must be readable by the web server (`chmod -R a+rX plugins/themes/journalMetrics`).
3. Clear the template cache: Administration → Site → "Clear Template Cache" and "Clear Data Caches".
4. Settings → Website → Appearance → Theme → choose "Journal Metrics Theme" → Save.
5. On the same page, set the accent color, tagline, and the "Usage metrics" and "Editorial workflow" toggles.

### What it does
- Layout: wide container (up to 1560 px) with no sidebar; header: ISSN, language switcher, user menu (dropdown), journal title, "Submit a manuscript" button and the main menu in one row; on screens up to 992 px the menu becomes a burger. Skip links are invisible and appear only for keyboard users.
- Home page: a single H1, buttons, cards (views, PDF, articles, pages), an editorial workflow stage line marking where the latest in-progress article currently is (stage only, no title or author), "Most read articles" (3 articles from the last two issues), a strip with the latest announcement, and the full list of articles in the current issue (title, authors, DOI, views, PDF).
- Article page: title → publication date → authors → DOI → PDF → abstract → keywords → author bio → references → "How to cite" and citation download; article details and usage are in a side card.
- SEO: canonical for the home, issue and article pages (separately for each language); JSON-LD (Periodical on the home page, ScholarlyArticle on the article page); semantic markup; a single H1; hreflang and Google Scholar meta tags are left to OJS core and the Google Scholar plugin.

### Changing the editorial workflow stages
Stage names and durations are in `locale/uk/locale.po` and `locale/en/locale.po` (keys `plugins.themes.journalMetrics.workflow.*`). Segment widths are set by the `flex-grow` numbers in `templates/frontend/pages/indexJournal.tpl`.

### Statistics
Views come from the OJS statistics service (`publicationStats`) and are cached for 1 hour. If statistics are disabled or empty, the metrics will be zero.

### For Google indexing (outside the theme)
Enable the Google Scholar plugin; make sure the journal is "enabled" (not noindex); submit https://jrnls.ivet.edu.ua/index/sitemap in Search Console; check "Manual actions" and "Security issues".

License: GNU GPL v3.

<a id="arabic"></a>
<div dir="rtl">

## العربية

قالب فرعي مبني على القالب الافتراضي (default). يطبّق نمط «المجلة بالأرقام».

### التثبيت
1. انسخ مجلد `journalMetrics` إلى `plugins/themes/` في نظام OJS لديك ليصبح المسار `plugins/themes/journalMetrics/version.xml`.
2. الصلاحيات: يجب أن يتمكن خادم الويب من قراءة الملفات (`chmod -R a+rX plugins/themes/journalMetrics`).
3. امسح ذاكرة التخزين المؤقت للقوالب: الإدارة ← الموقع (Administration) ← «Clear Template Cache» و«Clear Data Caches».
4. الإعدادات ← الموقع الإلكتروني ← المظهر (Appearance) ← القالب ← اختر «قالب Journal Metrics» ← حفظ.
5. في الصفحة نفسها، حدّد اللون المميّز والشعار ومفتاحي «مؤشرات الاستخدام» و«سير العمل التحريري».

### ماذا يفعل
- الهيكل: حاوية عريضة (حتى 1560 px) بلا عمود جانبي؛ الترويسة: ISSN، ومبدّل اللغة، وقائمة المستخدم (منسدلة)، واسم المجلة، وزر «إرسال مخطوطة»، والقائمة الرئيسية في سطر واحد؛ على الشاشات حتى 992 px تتحول القائمة إلى زر همبرغر. روابط التخطي مخفية ولا تظهر إلا لمستخدمي لوحة المفاتيح.
- الصفحة الرئيسية: عنوان H1 واحد، وأزرار، وبطاقات (المشاهدات، PDF، المقالات، الصفحات)، وخط مراحل سير العمل التحريري مع إشارة إلى موضع آخر مقالة قيد العمل (المرحلة فقط، دون العنوان أو المؤلف)، و«المقالات الأكثر قراءة» (3 مقالات من آخر عددين)، وشريط بآخر إعلان، والقائمة الكاملة لمقالات العدد الحالي (العنوان، المؤلفون، DOI، المشاهدات، PDF).
- صفحة المقالة: العنوان ← تاريخ النشر ← المؤلفون ← DOI ← PDF ← الملخص ← الكلمات المفتاحية ← السيرة الذاتية للمؤلف ← قائمة المراجع ← «كيفية الاستشهاد» وتنزيل الاستشهاد؛ بيانات المقالة والاستخدام في بطاقة جانبية.
- تحسين محركات البحث (SEO): رابط canonical للصفحة الرئيسية والعدد والمقالة (لكل لغة على حدة)؛ JSON-LD (Periodical في الرئيسية، وScholarlyArticle في المقالة)؛ ترميز دلالي؛ عنوان H1 واحد؛ أما hreflang ووسوم Google Scholar الوصفية فتبقى من مسؤولية نواة OJS وإضافة Google Scholar.

### تغيير مراحل سير العمل التحريري
أسماء المراحل ومددها موجودة في `locale/uk/locale.po` و`locale/en/locale.po` (المفاتيح `plugins.themes.journalMetrics.workflow.*`). يُحدَّد عرض المقاطع بقيم `flex-grow` في `templates/frontend/pages/indexJournal.tpl`.

### الإحصاءات
تُؤخذ المشاهدات من خدمة إحصاءات OJS (`publicationStats`) وتُخزَّن مؤقتًا لمدة ساعة واحدة. إذا كانت الإحصاءات معطّلة أو فارغة، فستظهر المؤشرات أصفارًا.

### للفهرسة في Google (خارج القالب)
فعّل إضافة Google Scholar؛ وتأكد من أن المجلة «مفعّلة» (وليست noindex)؛ وأرسل https://jrnls.ivet.edu.ua/index/sitemap في Search Console؛ وتحقق من «الإجراءات اليدوية» و«مشكلات الأمان».

الترخيص: GNU GPL v3.

</div>

<a id="ukrainian"></a>
## Українська

Дочірня тема до теми за замовчуванням (default). Реалізує варіант «Журнал у цифрах».

### Встановлення
1. Скопіюйте папку `journalMetrics` у `plugins/themes/` вашого OJS, щоб вийшло `plugins/themes/journalMetrics/version.xml`.
2. Права: файли читаються веб-сервером (`chmod -R a+rX plugins/themes/journalMetrics`).
3. Очистіть кеш шаблонів: Адміністрування → Сайт (Administration) → «Clear Template Cache» та «Clear Data Caches».
4. Налаштування → Вебсайт → Вигляд (Appearance) → Тема → оберіть «Тема Journal Metrics» → Зберегти.
5. Тут же задайте акцентний колір, слоган, перемикачі «Показники використання» та «Редакційний процес».

### Що робить
- Каркас: широкий контейнер (до 1560 px) без бокової колонки; шапка: ISSN, перемикач мови, меню користувача (випадне), назва журналу, кнопка «Подати рукопис» і основне меню в один рядок; на екранах до 992 px меню стає бургером. Skip-посилання невидимі, з'являються лише для користувачів клавіатури.
- Головна: єдиний H1, кнопки, картки (перегляди, PDF, статті, сторінки), лінія етапів редакційного процесу з позначкою, де зараз остання стаття в роботі (лише етап, без назви й автора), «Найчитаніші статті» (3 статті з двох останніх випусків), смуга з останнім оголошенням, повний список статей поточного випуску (назва, автори, DOI, перегляди, PDF).
- Сторінка статті: назва → дата публікації → автори → DOI → PDF → анотація → ключові слова → біографія автора → список літератури → «Як цитувати» і завантаження цитування; відомості про статтю й використання — у бічній картці.
- SEO: canonical для головної, випуску, статті (для кожної мови окремо); JSON-LD (Periodical на головній, ScholarlyArticle на статті); семантична розмітка; один H1; hreflang і мета-теги Google Scholar лишаються за ядром OJS та плагіном Google Scholar.

### Зміна етапів редакційного процесу
Назви й терміни етапів — у `locale/uk/locale.po` та `locale/en/locale.po` (ключі `plugins.themes.journalMetrics.workflow.*`). Ширину сегментів задають числа `flex-grow` у `templates/frontend/pages/indexJournal.tpl`.

### Статистика
Перегляди беруться зі сервісу статистики OJS (`publicationStats`) і кешуються на 1 годину. Якщо статистика вимкнена або порожня, показники будуть нулями.

### Для індексації в Google (поза темою)
Увімкніть плагін Google Scholar; перевірте, що журнал «увімкнений» (не noindex); подайте https://jrnls.ivet.edu.ua/index/sitemap у Search Console; перевірте «Ручні дії» та «Проблеми безпеки».

Ліцензія: GNU GPL v3.
