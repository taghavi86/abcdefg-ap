# 🚀 راهنمای سریع نصب و اجرا

## نصب آسان (ویندوز)

### روش اول: استفاده از فایل نصب خودکار
1. دانلود و نصب XAMPP (نسخه PHP 8.0+)
2. دانلود و نصب Composer
3. کپی کردن پروژه در پوشه `C:\xampp\htdocs`
4. اجرای فایل `install.bat` با دابل‌کلیک
5. دنبال کردن مراحل نصب

### روش دوم: نصب دستی

```bash
# 1. ورود به پوشه پروژه
cd C:\xampp\htdocs\smart-work-system

# 2. نصب وابستگی‌ها
composer install

# 3. کپی فایل .env
copy .env.example .env

# 4. تولید کلید
php artisan key:generate

# 5. ایجاد دیتابیس در phpMyAdmin
# نام دیتابیس: smart_work_db

# 6. ویرایش .env و تنظیمات دیتابیس
# DB_DATABASE=smart_work_db
# DB_USERNAME=root
# DB_PASSWORD=

# 7. اجرای مایگریشن‌ها
php artisan migrate

# 8. اجرای Seederها
php artisan db:seed

# 9. ایجاد لینک storage
php artisan storage:link

# 10. اجرای سرور
php artisan serve
```

## 🎯 دسترسی به سیستم

### API
- آدرس: http://localhost:8000/api
- مستندات کامل در README.md

### پنل مدیریت
- آدرس: http://localhost:8000/admin
- ایمیل: admin@smartwork.com
- رمز: password123

## 📱 تست API با Flutter

```dart
// نمونه کد اتصال به API
final baseUrl = 'http://localhost:8000/api';

// ثبت‌نام
final response = await http.post(
  Uri.parse('$baseUrl/auth/register'),
  body: {
    'phone': '09123456789',
    'national_code': '1234567890',
    'name': 'نام کاربر',
    'password': 'رمز عبور',
  },
);

// ورود
final loginResponse = await http.post(
  Uri.parse('$baseUrl/auth/login'),
  body: {
    'phone': '09123456789',
    'password': 'رمز عبور',
  },
);

// دریافت توکن و ذخیره برای درخواست‌های بعدی
final token = loginResponse.body['token'];
```

## ⚠️ مشکلات رایج و راه‌حل

### خطای "Connection refused"
- مطمئن شوید Apache و MySQL در XAMPP اجرا هستند
- پورت 3306 را بررسی کنید

### خطای "Class not found"
```bash
composer dump-autoload
```

### خطای دیتابیس
- دیتابیس `smart_work_db` را در phpMyAdmin ایجاد کنید
- فایل `.env` را بررسی کنید

### کار نکردن آپلود تصاویر
```bash
php artisan storage:link
```

## 📞 پشتیبانی

برای راهنمایی بیشتر:
- مطالعه فایل README.md
- بررسی لاگ‌ها در `storage/logs/laravel.log`

---
**موفق باشید! 🎉**
