# 📖 راهنمای کامل نصب و راه‌اندازی سامانه میز کار هوشمند

## 🎯 هدف این راهنما
این راهنما به شما کمک می‌کند تا پروژه را به صورت کامل روی سیستم لوکال با XAMPP نصب و اجرا کنید.

---

## 📋 فهرست مطالب
1. [پیش‌نیازها](#پیش‌نیازها)
2. [نصب XAMPP](#نصب-xampp)
3. [نصب Composer](#نصب-composer)
4. [کلون کردن پروژه](#کلون-کردن-پروژه)
5. [نصب وابستگی‌ها](#نصب-وابستگی‌ها)
6. [تنظیمات دیتابیس](#تنظیمات-دیتابیس)
7. [اجرای مایگریشن‌ها](#اجرای-مایگریشن‌ها)
8. [راه‌اندازی سرور](#راه‌اندازی-سرور)
9. [تست پروژه](#تست-پروژه)
10. [پیکربندی هاست دانلود](#پیکربندی-هاست-دانلود)

---

## پیش‌نیازها

### نرم‌افزارهای مورد نیاز:
- ✅ Windows 10/11 (64-bit)
- ✅ XAMPP 8.0+ (شامل Apache, MySQL, PHP)
- ✅ Composer 2.0+
- ✅ Git

### حداقل سخت‌افزار:
- RAM: 4GB (8GB پیشنهاد می‌شود)
- فضای ذخیره‌سازی: 2GB
- پردازنده: Dual Core

---

## نصب XAMPP

### مرحله ۱: دانلود XAMPP
1. به آدرس https://www.apachefriends.org/ بروید
2. نسخه مناسب برای ویندوز را دانلود کنید
3. **مهم:** نسخه با PHP 8.0 یا بالاتر را انتخاب کنید

### مرحله ۲: نصب XAMPP
1. فایل نصبی را اجرا کنید
2. مسیر نصب را `C:\xampp` انتخاب کنید
3. کامپوننت‌های زیر را انتخاب کنید:
   - ✅ Apache
   - ✅ MySQL
   - ✅ PHP
   - ✅ phpMyAdmin
4. نصب را تکمیل کنید

### مرحله ۳: اجرای XAMPP Control Panel
1. XAMPP Control Panel را باز کنید
2. دکمه Start را کنار Apache بزنید
3. دکمه Start را کنار MySQL بزنید
4. هر دو باید سبز شوند

---

## نصب Composer

### مرحله ۱: دانلود Composer
1. به آدرس https://getcomposer.org/download/ بروید
2. نسخه Windows Installer را دانلود کنید

### مرحله ۲: نصب Composer
1. فایل نصبی را اجرا کنید
2. مسیر `C:\xampp\php\php.exe` را انتخاب کنید
3. گزینه "Add to PATH" را تیک بزنید
4. نصب را تکمیل کنید

### مرحله ۳: بررسی نصب Composer
```bash
# CMD را باز کنید و دستور زیر را وارد کنید
composer --version
```
باید نسخه Composer نمایش داده شود.

---

## کلون کردن پروژه

### روش اول: با Git
```bash
# 1. باز کردن CMD
# 2. رفتن به پوشه htdocs
cd C:\xampp\htdocs

# 3. کلون کردن پروژه
git clone <URL_REPOSITORY> smart-work-system

# 4. ورود به پوشه پروژه
cd smart-work-system
```

### روش دوم: دانلود ZIP
1. پروژه را از گیت‌هاب دانلود کنید
2. فایل ZIP را استخراج کنید
3. پوشه را به `C:\xampp\htdocs\smart-work-system` منتقل کنید

---

## نصب وابستگی‌ها

### مرحله ۱: باز کردن CMD در پوشه پروژه
```bash
cd C:\xampp\htdocs\smart-work-system
```

### مرحله ۲: نصب وابستگی‌های Composer
```bash
composer install --no-interaction --prefer-dist
```

**نکته:** این مرحله ممکن است چند دقیقه طول بکشد.

### مرحله ۳: بررسی خطاها
اگر خطای extension دریافت کردید:
1. فایل `C:\xampp\php\php.ini` را با Notepad باز کنید
2. خطوط زیر را پیدا کرده و `;` ابتدای آن‌ها را حذف کنید:
```ini
extension=pdo_mysql
extension=mbstring
extension=openssl
extension=curl
extension=gd
extension=fileinfo
extension=intl
```
3. فایل را ذخیره کرده و Apache را ریستارت کنید

---

## تنظیمات دیتابیس

### مرحله ۱: ایجاد دیتابیس
1. مرورگر را باز کنید
2. به آدرس http://localhost/phpmyadmin بروید
3. روی تب "New" کلیک کنید
4. اطلاعات زیر را وارد کنید:
   - Database name: `smart_work_db`
   - Collation: `utf8mb4_unicode_ci`
5. دکمه Create را بزنید

### مرحله ۲: کپی فایل .env
```bash
copy .env.example .env
```

### مرحله ۳: ویرایش فایل .env
فایل `.env` را با Notepad باز کنید و تغییرات زیر را اعمال کنید:

```env
APP_NAME="Smart Work System"
APP_LOCALE=fa
APP_FALLBACK_LOCALE=en

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=smart_work_db
DB_USERNAME=root
DB_PASSWORD=

DOWNLOAD_HOST_URL=http://localhost/downloads
DOWNLOAD_HOST_PATH=C:/xampp/htdocs/downloads
```

### مرحله ۴: تولید کلید اپلیکیشن
```bash
php artisan key:generate
```

---

## اجرای مایگریشن‌ها

### مرحله ۱: اجرای مایگریشن‌ها
```bash
php artisan migrate
```

**خروجی موفق:**
```
INFO  All migrations completed successfully.
```

### مرحله ۲: اجرای Seederها
```bash
php artisan db:seed
```

این دستور سطوح کاربری (پایه، پیشرو، ممتاز) را ایجاد می‌کند.

### مرحله ۳: ایجاد لینک Storage
```bash
php artisan storage:link
```

---

## راه‌اندازی سرور

### روش اول: Laravel Development Server (پیشنهادی)
```bash
php artisan serve
```

**خروجی:**
```
Starting development server...
http://127.0.0.1:8000
```

### روش دوم: استفاده از Apache
1. پروژه در آدرس زیر قابل دسترسی است:
   ```
   http://localhost/smart-work-system/public
   ```

2. برای حذف `/public` از URL:
   - فایل `.htaccess` را از `public` به روت پروژه کپی کنید
   - یا از روش اول استفاده کنید

---

## تست پروژه

### ۱. تست صفحه اصلی
به آدرس http://localhost:8000 بروید
باید صفحه خوش‌آمدگویی لاراول را ببینید

### ۲. تست API با Postman
1. Postman را نصب کنید
2. فایل `postman/smart_work_api_collection.json` را ایمپورت کنید
3. متغیرهای Environment را تنظیم کنید:
   - base_url: `http://localhost:8000/api`
4. درخواست ثبت‌نام را ارسال کنید

### ۳. تست پنل مدیریت
1. به آدرس http://localhost:8000/admin بروید
2. با اطلاعات زیر وارد شوید:
   - Email: `admin@smartwork.com`
   - Password: `password123`

### ۴. ایجاد کاربر تستی
```bash
php artisan make:user --phone=09123456789 --password=test123
```

---

## پیکربندی هاست دانلود

### برای محیط لوکال:

#### مرحله ۱: ایجاد پوشه دانلود
```bash
mkdir C:\xampp\htdocs\downloads
```

#### مرحله ۲: تنظیم مجوزها
1. روی پوشه downloads راست‌کلیک کنید
2. Properties > Security را باز کنید
3. کاربر Everyone را اضافه کنید
4. دسترسی Full Control بدهید

#### مرحله ۳: تست آپلود
1. وارد پنل مدیریت شوید
2. به بخش تصاویر بروید
3. یک تصویر آپلود کنید
4. باید در پوشه downloads ذخیره شود

### برای محیط Production:

```env
DOWNLOAD_HOST_URL=https://cdn.yoursite.com
DOWNLOAD_HOST_PATH=/home/user/public_html/cdn
```

---

## 🐛 رفع مشکلات رایج

### مشکل ۱: خطای "Connection refused"
**علت:** MySQL اجرا نیست
**راه‌حل:** 
1. XAMPP Control Panel را باز کنید
2. MySQL را Start کنید

### مشکل ۲: خطای "Class not found"
**راه‌حل:**
```bash
composer dump-autoload
```

### مشکل ۳: خطای دیتابیس "Access denied"
**راه‌حل:**
1. فایل `.env` را بررسی کنید
2. مطمئن شوید DB_USERNAME=root و DB_PASSWORD خالی است
3. در phpMyAdmin بررسی کنید که دیتابیس ساخته شده باشد

### مشکل ۴: خطای "Maximum execution time"
**راه‌حل:**
در فایل `C:\xampp\php\php.ini`:
```ini
max_execution_time = 300
upload_max_filesize = 50M
post_max_size = 50M
memory_limit = 256M
```
سپس Apache را ریستارت کنید

### مشکل ۵: کار نکردن آپلود
**راه‌حل:**
```bash
php artisan storage:link
```
و بررسی مجوزهای پوشه storage

---

## 📞 پشتیبانی

### لاگ‌گیری
برای مشاهده لاگ‌ها:
```bash
type storage\logs\laravel.log
```

### حالت دیباگ
در فایل `.env`:
```env
APP_DEBUG=true
LOG_LEVEL=debug
```

---

## ✅ چک‌لیست نهایی

- [ ] XAMPP نصب و اجرا شده است
- [ ] Composer نصب شده است
- [ ] پروژه کلون شده است
- [ ] وابستگی‌ها نصب شده‌اند
- [ ] دیتابیس ساخته شده است
- [ ] فایل .env تنظیم شده است
- [ ] مایگریشن‌ها اجرا شده‌اند
- [ ] Seederها اجرا شده‌اند
- [ ] لینک storage ایجاد شده است
- [ ] سرور در حال اجرا است
- [ ] پنل مدیریت قابل دسترسی است
- [ ] API با Postman تست شده است

---

## 🎉 تبریک!

پروژه با موفقیت نصب و راه‌اندازی شد!

### دسترسی‌ها:
- **API Base URL:** http://localhost:8000/api
- **پنل مدیریت:** http://localhost:8000/admin
- **ادمین:** admin@smartwork.com / password123

### مستندات بیشتر:
- README.md - مستندات کامل
- QUICK_START.md - راهنمای سریع
- postman/ - کالکشن Postman

---

**موفق باشید! 🚀**
