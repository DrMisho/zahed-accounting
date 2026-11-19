# نظام "زاهد" المحاسبي - مشروع Laravel 11 متكامل

نظام محاسبي وإدارة مخزون سحابي متعدد المحلات (Multi-tenant SaaS) مبني بأحدث إصدار من **Laravel 11** وواجهات **Blade** مع **Tailwind CSS**.

## التقنيات المستخدمة:
- **إطار العمل:** Laravel 11 (PHP 8.2+)
- **نظام التوثيق والمصادقة:** Laravel Breeze (Blade Views)
- **إدارة الأدوار والصلاحيات:** Spatie Laravel Permission (`spatie/laravel-permission`)
- **عزل البيانات البرمجي:** Eloquent Global Scope (`TenantScope`) على مستوى قاعدة البيانات المشتركة (Single DB - Shared Schema) بواسطة `shop_id`
- **تعدد العملات:** دعم الدولار الأمريكي (USD) والليرة السورية (SYP) مع معالجة لحظية لأسعار الصرف والمعادل بالدولار
- **قاعدة البيانات:** SQLite (بيئة التطوير افتراضياً) أو MySQL (بيئة الإنتاج)

---

## خطوات التشغيل على جهازك (Local Development Setup):

### 1. تثبيت حزم الـ PHP والـ JavaScript:
```bash
composer install
npm install
```

### 2. إعداد ملف البيئة (.env):
```bash
cp .env.example .env
php artisan key:generate
```

### 3. إنشاء قاعدة بيانات SQLite (أو ضبط MySQL في .env):
```bash
touch database/database.sqlite
```

### 4. تشغيل الـ Migrations وتعبئة البيانات التجريبية (Seeding):
```bash
php artisan migrate:fresh --seed
```
*ملاحظة:* سيقوم الـ Seeder بإنشاء أدوار Spatie (`super_admin`, `shop_manager`)، وإنشاء متجرين مع بضاعة وفواتير وحركات صندوق للتجربة فوراً!

### 5. تشغيل السيرفر المحلي:
في نافذة تيرمينال أولى:
```bash
php artisan serve
```
وفي نافذة تيرمينال ثانية لتجميع ملفات الـ CSS/JS:
```bash
npm run dev
```

افتح المتصفح على: `http://localhost:8000`

---

## بيانات تسجيل الدخول التجريبية (Default Credentials):

### 1. حساب السوبر أدمن (مالك المنصة):
- **البريد الإلكتروني:** `admin@zahed-saas.com`
- **كلمة المرور:** `password`
- **الصلاحيات:** إدارة كافة المحلات، تجديد الاشتراكات، مراقبة إيرادات المنصة، والدخول لأي متجر.

### 2. حساب مدير المحل 1 (محلات الأمانة للأجهزة):
- **البريد الإلكتروني:** `khaled@amana-shop.com`
- **كلمة المرور:** `password`
- **shop_id:** `shop_amana`

### 3. حساب مدير المحل 2 (شركة النور الغذائية):
- **البريد الإلكتروني:** `tareq@nour-trading.com`
- **كلمة المرور:** `password`
- **shop_id:** `shop_nour`

---

## هيكل الملفات الرئيسي:
- `app/Models/Scopes/TenantScope.php`: عزل استعلامات المحل أوتوماتيكياً `WHERE shop_id = auth()->user()->shop_id`.
- `app/Services/AccountingService.php`: كافة المعادلات المحاسبية المذكورة في وثيقة الـ SRS بدقة (الصندوق، كشوفات الحساب، جرد المخزون ومتوسط التكلفة WAC، وصافي الربح التقديري).
- `app/Models/User.php`: يستخدم Trait `HasRoles` من مكتبة Spatie.
- `resources/views/`: قوالب Blade للوحة التحكم، الفواتير، الصندوق، المخزون، والتقارير.
