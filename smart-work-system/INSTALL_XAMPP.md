# راهنمای کامل نصب و راه‌اندازی روی XAMPP

## پیش‌نیازها

### 1. نصب XAMPP
- دانلود XAMPP از https://www.apachefriends.org/
- نصب در مسیر `C:\xampp`
- فعال‌سازی Apache و MySQL از کنترل پنل XAMPP

### 2. نصب Composer
- دانلود از https://getcomposer.org/
- نصب با تنظیمات پیش‌فرض

### 3. نصب Git (اختیاری)
- دانلود از https://git-scm.com/

---

## مراحل نصب پروژه

### مرحله 1: کپی فایل‌ها
```bash
# کپی تمام فایل‌های پروژه به پوشه htdocs
xcopy /E /I /Y "مسیر_پروژه" "C:\xampp\htdocs\smart-work-system"
```

### مرحله 2: تنظیم دیتابیس
1. باز کردن phpMyAdmin (http://localhost/phpmyadmin)
2. ایجاد دیتابیس جدید:
   - نام: `smart_work_db`
   - Encoding: `utf8mb4_unicode_ci`

### مرحله 3: تنظیم فایل .env
```bash
cd C:\xampp\htdocs\smart-work-system
copy .env.example .env
```

ویرایش فایل `.env`:
```env
APP_NAME="Smart Work System"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost/smart-work-system

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=smart_work_db
DB_USERNAME=root
DB_PASSWORD=

# هاست دانلود تصاویر
DOWNLOAD_HOST_URL=http://localhost/smart-work-system/storage
DOWNLOAD_HOST_PATH=storage/app/public

# یا برای هاست خارجی:
# DOWNLOAD_HOST_URL=https://dl.yoursite.com
# DOWNLOAD_HOST_PATH=/home/yoursite/public_html/dl
```

### مرحله 4: نصب وابستگی‌ها
```bash
cd C:\xampp\htdocs\smart-work-system
composer install --no-dev --optimize-autoloader
```

### مرحله 5: تولید کلید برنامه
```bash
php artisan key:generate
```

### مرحله 6: اجرای مایگریشن‌ها
```bash
php artisan migrate --force
```

### مرحله 7: Seed دیتابیس
```bash
php artisan db:seed --force
```

### مرحله 8: ایجاد لینک نمادین برای فایل‌ها
```bash
php artisan storage:link
```

### مرحله 9: تنظیم مجوزها (در صورت نیاز)
```bash
# در ویندوز معمولاً نیاز نیست
# در لینوکس:
chmod -R 775 storage bootstrap/cache
```

---

## اجرای پروژه

### روش 1: استفاده از Apache
```
http://localhost/smart-work-system
```

### روش 2: استفاده از Artisan Server
```bash
php artisan serve --host=127.0.0.1 --port=8000
```
سپس باز کنید:
```
http://127.0.0.1:8000
```

---

## تست API

### دریافت توکن ورود:
```bash
curl -X POST http://localhost/smart-work-system/api/auth/login ^
  -H "Content-Type: application/json" ^
  -d "{\"phone\":\"09123456789\",\"password\":\"password\"}"
```

### دسترسی به پنل مدیریت:
```
http://localhost/smart-work-system/admin
```
- ایمیل: `admin@example.com`
- رمز: `password`

---

## انتقال به هاست اصلی

### 1. آماده‌سازی برای انتقال
```bash
# تولید کلید نهایی
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 2. آپلود فایل‌ها
- آپلود تمام فایل‌ها به جز:
  - `vendor/` (در هاست composer install اجرا شود)
  - `.env` (تنظیمات هاست اصلی را وارد کنید)
  - `node_modules/`
  - `.git/`

### 3. تنظیمات هاست اصلی
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yoursite.com

DB_DATABASE=dbname_hast
DB_USERNAME=user_hast
DB_PASSWORD=pass_hast

DOWNLOAD_HOST_URL=https://dl.yoursite.com
DOWNLOAD_HOST_PATH=/home/user/public_html/dl
```

### 4. اجرای دستورات در هاست
```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## عیب‌یابی

### خطای Migration
```bash
php artisan migrate:rollback
php artisan migrate:fresh --seed
```

### خطای Permission
```bash
# در ویندوز، CMD را به عنوان Administrator اجرا کنید
# در لینوکس:
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### خطای Composer
```bash
composer clear-cache
composer update
```

### ریست کامل دیتابیس
```bash
php artisan migrate:fresh --seed
```

---

## اطلاعات ورود پیش‌فرض

### ادمین:
- ایمیل: `admin@example.com`
- رمز: `password`
- تلفن: `09123456789`

### کاربر تست:
- ایمیل: `test@example.com`
- رمز: `password`
- تلفن: `09123456788`

---

## نکات مهم

1. **هاست دانلود**: برای بارگزاری تصاویر، باید یک هاست دانلود جداگانه تنظیم کنید
2. **PHP Version**: حداقل PHP 8.1 مورد نیاز است
3. **Extensions**: اطمینان حاصل کنید extensionهای زیر فعال هستند:
   - pdo_mysql
   - mbstring
   - xml
   - curl
   - zip

4. **Memory Limit**: در php.ini مقدار memory_limit را حداقل روی 256M تنظیم کنید

---

## پشتیبانی

برای گزارش مشکلات یا سوالات، مستندات پروژه را مطالعه کنید.
