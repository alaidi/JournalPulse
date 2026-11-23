# Journal Pulse — OJS 3.5 theme

[English](#english) · [العربية](#arabic)

<a id="english"></a>
## English

A child theme of the default theme. Implements the "Journal in numbers" variant. Developed from [Journal Metrics](https://github.com/Radkevych/JournalMetrics) by Oleksandr Radkevych.

### Installation
1. Copy the `journalPulse` folder into `plugins/themes/` of your OJS so you get `plugins/themes/journalPulse/version.xml`.
2. Permissions: files must be readable by the web server (`chmod -R a+rX plugins/themes/journalPulse`).
3. Clear the template cache: Administration → Site → "Clear Template Cache" and "Clear Data Caches".
4. Settings → Website → Appearance → Theme → choose "Journal Pulse Theme" → Save.
5. On the same page, set the accent color, tagline, the open access model shown above the journal name (open, gold or diamond), where the home page cover goes (under the journal name, or beside it with the usage numbers taking its place) and its size (normal, up to 320 px tall, or large, up to 480 px), and the "Usage metrics" and "Editorial workflow" toggles.

Upgrading from the theme's former name, `journalMetrics`: install `journalPulse` as above. Installing it copies the theme settings and switches the journals that used `journalMetrics` to it; then delete `plugins/themes/journalMetrics`.

### What it does
- Layout: wide container (up to 1560 px) with no sidebar; header: ISSN, language switcher, user menu (dropdown), journal title, "Submit a manuscript" button and the main menu in one row; on screens up to 992 px the menu becomes a burger. Skip links are invisible and appear only for keyboard users.
- Home page: a single H1 with the cover of the current issue under it (shown when the issue has a cover; it links to the issue), buttons, cards (views, PDF, articles, pages), an editorial workflow stage line with how long each stage usually takes (measured from the journal's own submissions) and a marker where the latest in-progress article currently is (stage only, no title or author), "Most read articles" (3 articles from the last two issues), a strip with the latest announcement, the number of published articles in each section ("Number of Research Articles by Specialty", with its share as a bar), and the full list of articles in the current issue (title, authors, DOI, views, PDF).
- Home page options (Settings → Website → Appearance → Theme): switch most read, articles by specialty, the announcement strip and the issue article list on or off; pick the section backgrounds from four presets (classic, all white, alternating grey, tinted with the accent colour); choose who sees the workflow marker (authors see their own submission and everybody else the journal's latest, or only logged-in authors).
- Display settings behind an "Aa" button in the top bar: text size (five steps) and a light / dark / automatic theme; the automatic theme turns dark at night by the visitor's clock (20:00–07:00 by default). The visitor's choice stays in their browser; the theme options set the default and can hide the buttons.
- Article page: title → publication date → authors → DOI → PDF → abstract → keywords → author bio → references → "How to cite" and citation download; article details and usage are in a side card.
- Login, registration and password reset pages: the form sits on a white sheet over a grey background (full width on phones), with 48 px fields, a visible focus ring and the theme's buttons; the registration page shows its fields in pairs. Every password field has an eye button to show or hide the password. Only the stylesheet changes these pages, not the OJS templates, so captcha, ORCID and plugins that add to these forms keep working.
- Footer: journal name, online and print ISSN and publisher; links to the journal pages and to the pages for authors and readers (pages without text are left out); the journal's own footer text (Settings → Website → Appearance), such as the licence; a bar with the copyright, "Back to top" and the OJS logo.
- SEO: canonical for the home, issue and article pages (separately for each language); JSON-LD (Periodical on the home page, ScholarlyArticle on the article page); semantic markup; a single H1; hreflang and Google Scholar meta tags are left to OJS core and the Google Scholar plugin.

### Editorial workflow stages
The term under each stage is measured from the journal's own submissions of the last two years: the day of the stage by which 80% of the manuscripts had left it (the share OJS uses for its "days to first decision"). A stage starts with the editorial decision that moved the manuscript into it (screening starts at submission), and production ends at publication. The same numbers set the segment widths and are recalculated once a day. A stage with fewer than 5 finished manuscripts uses its default from `STAGE_DAYS` in `JournalPulseThemePlugin.php`. A note under the line says these are typical times, not guarantees. Stage names are in `locale/*/locale.po` (keys `plugins.themes.journalPulse.workflow.*`).

![Editorial workflow line on the home page](docs/editorial-workflow.png)

### Journal information
A "Journal information" section under the top of the home page lists the journal title, ISSN and DOI prefix (from the journal settings) and the fields filled in Settings → Website → Appearance → Theme: indexing, frequency, publication dates, Editor-in-Chief, Managing Editor, organizer, citation analysis, acceptance rate, review speed, a note shown above the login and registration buttons, files to download under that note (templates, forms), and video guides. The Editor-in-Chief and Managing Editor fields take a photo (image button, square picture), shown round beside the name. Put each indexing service, publication date, file and video on its own line, and link each file's name and each video's title to it. Files can be hosted in Settings → Workflow → Publisher Library with Public Access turned on. Empty fields are not shown; untick "Journal information" to hide the section.

![Journal information section on the home page](docs/journal-information.png)

The option "Editors in Journal information" sets how the editors look: **Compact** (default; small photo beside each editor, in one row with the organizer, as above) or **Profile cards** (a card for each editor with a large photo, role, name, affiliation and email; "Organized by" stays a plain line under the cards).

![Editors as profile cards](docs/editors-cards.png)

### Display settings (new in 2.5.1.0)
One "Aa" button in the top bar replaces the six round text size and colour buttons. It opens a small panel: text size (A−, the current size in %, A+) and page colours (Auto, Light, Dark). The panel closes on Esc or a click outside it; Esc returns the focus to the button. Theme options "Text size" and "Day and night" hide either row, and the button disappears when both are off.

![Display settings panel opened from the "Aa" button](docs/display-settings.png)

### Footer (new in 2.5.1.0)
Four columns: the journal name with its online and print ISSN and publisher; "The journal" links; "Authors & readers" links (pages without text are left out); and the journal's own footer text from Settings → Website → Appearance, such as the licence. A bottom bar holds the copyright, "Back to top" and a small OJS logo. On phones the name and the footer text take full rows and the two link columns stay side by side.

![Redesigned footer](docs/footer.png)

### Statistics
Views come from the OJS statistics service (`publicationStats`) and are cached for 1 hour. If statistics are disabled or empty, the metrics will be zero.

### For Google indexing (outside the theme)
Enable the Google Scholar plugin; make sure the journal is "enabled" (not noindex); submit https://example.com/index/sitemap in Search Console; check "Manual actions" and "Security issues".

License: GNU GPL v3.

<a id="arabic"></a>
<div dir="rtl">

## العربية

«نبض المجلة» قالب فرعي مبني على القالب الافتراضي (default). يطبّق نمط «المجلة بالأرقام». وهو مطوَّر عن [Journal Metrics](https://github.com/Radkevych/JournalMetrics) لـ Oleksandr Radkevych.

### التثبيت
1. انسخ مجلد `journalPulse` إلى `plugins/themes/` في نظام OJS لديك ليصبح المسار `plugins/themes/journalPulse/version.xml`.
2. الصلاحيات: يجب أن يتمكن خادم الويب من قراءة الملفات (`chmod -R a+rX plugins/themes/journalPulse`).
3. امسح ذاكرة التخزين المؤقت للقوالب: الإدارة ← الموقع (Administration) ← «Clear Template Cache» و«Clear Data Caches».
4. الإعدادات ← الموقع الإلكتروني ← المظهر (Appearance) ← القالب ← اختر «قالب نبض المجلة» ← حفظ.
5. في الصفحة نفسها، حدّد اللون المميّز والشعار ونموذج الوصول المفتوح الظاهر فوق اسم المجلة (مفتوح أو ذهبي أو ماسي) ومفتاحي «مؤشرات الاستخدام» و«سير العمل التحريري».

الترقية من الاسم السابق للقالب `journalMetrics`: ثبّت `journalPulse` كما سبق. ينسخ التثبيت إعدادات القالب وينقل المجلات التي كانت تستخدم `journalMetrics` إليه؛ ثم احذف `plugins/themes/journalMetrics`.

### ماذا يفعل
- الهيكل: حاوية عريضة (حتى 1560 px) بلا عمود جانبي؛ الترويسة: ISSN، ومبدّل اللغة، وقائمة المستخدم (منسدلة)، واسم المجلة، وزر «إرسال مخطوطة»، والقائمة الرئيسية في سطر واحد؛ على الشاشات حتى 992 px تتحول القائمة إلى زر همبرغر. روابط التخطي مخفية ولا تظهر إلا لمستخدمي لوحة المفاتيح.
- الصفحة الرئيسية: عنوان H1 واحد وتحته غلاف العدد الحالي (يظهر إذا كان للعدد غلاف، ويفتح العدد عند النقر عليه)، وأزرار، وبطاقات (المشاهدات، PDF، المقالات، الصفحات)، وخط مراحل سير العمل التحريري مع المدة المعتادة لكل مرحلة (محسوبة من مخطوطات المجلة نفسها) وإشارة إلى موضع آخر مقالة قيد العمل (المرحلة فقط، دون العنوان أو المؤلف)، و«المقالات الأكثر قراءة» (3 مقالات من آخر عددين)، وشريط بآخر إعلان، وعدد البحوث المنشورة في كل قسم («عدد البحوث حسب التخصص» مع نسبته في شريط)، والقائمة الكاملة لمقالات العدد الحالي (العنوان، المؤلفون، DOI، المشاهدات، PDF).
- خيارات الصفحة الرئيسية (الإعدادات ← الموقع الإلكتروني ← المظهر ← القالب): إظهار أو إخفاء المقالات الأكثر قراءة، والبحوث حسب التخصص، وشريط الإعلان، وقائمة مقالات العدد؛ واختيار خلفيات الأقسام من أربعة أنماط جاهزة (كلاسيكي، أبيض بالكامل، رمادي متناوب، بلمسة من اللون الرئيسي)؛ وتحديد من يرى علامة المرحلة (المؤلف يرى مخطوطته وبقية الزوار أحدث مخطوطة في المجلة، أو المؤلفون المسجَّل دخولهم فقط).
- إعدادات العرض خلف زر «Aa» في الشريط العلوي: حجم النص (خمس درجات) ووضع فاتح / داكن / تلقائي؛ يصبح الوضع التلقائي داكناً ليلاً بحسب ساعة جهاز الزائر (من 20:00 إلى 07:00 افتراضياً). يُحفظ اختيار الزائر في متصفحه؛ وتحدد خيارات القالب الوضع الافتراضي ويمكنها إخفاء الأزرار.
- صفحة المقالة: العنوان ← تاريخ النشر ← المؤلفون ← DOI ← PDF ← الملخص ← الكلمات المفتاحية ← السيرة الذاتية للمؤلف ← قائمة المراجع ← «كيفية الاستشهاد» وتنزيل الاستشهاد؛ بيانات المقالة والاستخدام في بطاقة جانبية.
- صفحات الدخول والتسجيل وإعادة تعيين كلمة المرور: يظهر النموذج على ورقة بيضاء فوق خلفية رمادية (وبعرض الشاشة على الهاتف)، بحقول ارتفاعها 48 px وإطار تركيز واضح وأزرار القالب؛ وتُعرض حقول صفحة التسجيل في أزواج. ولكل حقل كلمة مرور زر على شكل عين لإظهارها أو إخفائها. تتغير هذه الصفحات عبر ملف الأنماط وحده دون استبدال قوالب OJS، فيبقى عمل الـ captcha وORCID والإضافات التي تضيف إلى هذه النماذج كما هو.
- التذييل: اسم المجلة ورقما ISSN الإلكتروني والمطبوع والناشر؛ روابط صفحات المجلة وصفحات المؤلفين والقرّاء (تُحذف الصفحات التي لا نص فيها)؛ نص التذييل الخاص بالمجلة (الإعدادات ← الموقع الإلكتروني ← المظهر) مثل الترخيص؛ وشريط بحقوق النشر و«العودة إلى الأعلى» وشعار OJS.
- تحسين محركات البحث (SEO): رابط canonical للصفحة الرئيسية والعدد والمقالة (لكل لغة على حدة)؛ JSON-LD (Periodical في الرئيسية، وScholarlyArticle في المقالة)؛ ترميز دلالي؛ عنوان H1 واحد؛ أما hreflang ووسوم Google Scholar الوصفية فتبقى من مسؤولية نواة OJS وإضافة Google Scholar.

### مراحل سير العمل التحريري
تُقاس المدة تحت كل مرحلة من مخطوطات المجلة نفسها في السنتين الأخيرتين: اليوم الذي كان 80% من المخطوطات قد غادر المرحلة بحلوله (وهي النسبة التي يعتمدها OJS في إحصائية «أيام حتى القرار الأول»). تبدأ المرحلة بالقرار التحريري الذي نقل المخطوطة إليها (ويبدأ الفحص الأولي بتاريخ التقديم)، وينتهي الإنتاج بالنشر. الأرقام نفسها تحدد عرض المقاطع، ويُعاد حسابها مرة كل يوم. المرحلة التي أنهتها أقل من 5 مخطوطات تستخدم قيمتها الافتراضية من `STAGE_DAYS` في `JournalPulseThemePlugin.php`. وتوضح ملاحظة تحت الخط أن هذه مدد معتادة وليست مضمونة. أسماء المراحل في `locale/*/locale.po` (المفاتيح `plugins.themes.journalPulse.workflow.*`).

![خط مراحل سير العمل التحريري في الصفحة الرئيسية](docs/editorial-workflow-ar.png)

### معلومات المجلة
يعرض قسم «معلومات المجلة» أعلى الصفحة الرئيسية عنوان المجلة ورقم ISSN وبادئة DOI (من إعدادات المجلة)، والحقول التي تُملأ في الإعدادات ← الموقع الإلكتروني ← المظهر ← القالب: الفهرسة، ودورية الصدور، ومواعيد النشر، ورئيس التحرير، ومدير التحرير، والجهة المنظِّمة، وتحليل الاستشهادات، ونسبة القبول، ومدة التحكيم، وملاحظة تظهر فوق زرّي الدخول والتسجيل، وملفات للتنزيل تحت تلك الملاحظة (قوالب، نماذج)، وأدلة الفيديو. يقبل حقلا رئيس التحرير ومدير التحرير صورة شخصية (زر الصورة، صورة مربعة) تظهر دائرية بجانب الاسم. ضع كل جهة فهرسة وكل موعد نشر وكل ملف وكل فيديو في سطر مستقل، واربط اسم كل ملف وعنوان كل فيديو به. يمكن استضافة الملفات في الإعدادات ← سير العمل ← مكتبة الناشر مع تفعيل «الوصول العام». الحقول الفارغة لا تظهر؛ ألغِ تحديد «معلومات المجلة» لإخفاء القسم.

![قسم معلومات المجلة في الصفحة الرئيسية](docs/journal-information-ar.png)

يحدد خيار «المحررون في معلومات المجلة» شكل عرض المحررين: **مضغوط** (الافتراضي؛ صورة صغيرة بجانب كل محرر، في صف واحد مع الجهة المنظِّمة، كما في الصورة أعلاه) أو **بطاقات تعريفية** (بطاقة لكل محرر فيها صورة كبيرة والمنصب والاسم وجهة الانتساب والبريد الإلكتروني؛ وتبقى «الجهة المنظِّمة» سطراً عادياً تحت البطاقات).

![المحررون في بطاقات تعريفية](docs/editors-cards-ar.png)

### إعدادات العرض (جديد في 2.5.1.0)
زر واحد «Aa» في الشريط العلوي يحل محل الأزرار الدائرية الستة لحجم النص والألوان. يفتح لوحة صغيرة فيها: حجم النص (A−، والحجم الحالي بالنسبة المئوية، A+) وألوان الصفحة (تلقائي، فاتح، داكن). تُغلق اللوحة بمفتاح Esc أو بالنقر خارجها، ويعيد Esc التركيز إلى الزر. يُخفي خيارا القالب «حجم النص» و«النهار والليل» أيّاً من الصفين، ويختفي الزر إذا أُطفئ الاثنان.

![لوحة إعدادات العرض مفتوحة من زر «Aa»](docs/display-settings-ar.png)

### التذييل (جديد في 2.5.1.0)
أربعة أعمدة: اسم المجلة ورقما ISSN الإلكتروني والمطبوع والناشر؛ وروابط «المجلة»؛ وروابط «المؤلفون والقرّاء» (تُحذف الصفحات التي لا نص فيها)؛ ونص التذييل الخاص بالمجلة من الإعدادات ← الموقع الإلكتروني ← المظهر، مثل الترخيص. وفي الشريط السفلي حقوق النشر و«العودة إلى الأعلى» وشعار OJS صغير. على الهاتف يأخذ الاسم ونص التذييل سطراً كاملاً، ويبقى عمودا الروابط متجاورين.

![التذييل الجديد](docs/footer-ar.png)

### الإحصاءات
تُؤخذ المشاهدات من خدمة إحصاءات OJS (`publicationStats`) وتُخزَّن مؤقتًا لمدة ساعة واحدة. إذا كانت الإحصاءات معطّلة أو فارغة، فستظهر المؤشرات أصفارًا.

### للفهرسة في Google (خارج القالب)
فعّل إضافة Google Scholar؛ وتأكد من أن المجلة «مفعّلة» (وليست noindex)؛ وأرسل https://example.com/index/sitemap في Search Console؛ وتحقق من «الإجراءات اليدوية» و«مشكلات الأمان».

الترخيص: GNU GPL v3.

</div>
