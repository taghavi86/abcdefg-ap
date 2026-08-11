@echo off
echo ========================================
echo   نصب سامانه ميز کار هوشمند
echo ========================================
echo.

REM بررسي نصب بودن Composer
where composer >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo خطا: Composer نصب نيست!
    echo لطفاً از https://getcomposer.org/ دانلود و نصب کنيد.
    pause
    exit /b 1
)

REM بررسي نصب بودن PHP
where php >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo خطا: PHP نصب نيست يا در PATH قرار ندارد!
    echo مطمئن شويد XAMPP نصب و اجرا باشد.
    pause
    exit /b 1
)

echo [1/7] بررسي نسخه PHP...
php -v | findstr /C:"PHP 8"
if %ERRORLEVEL% NEQ 0 (
    echo هشدار: نسخه PHP ممکن است قديمي باشد (نسخه 8.0 يا بالاتر نياز است)
)

echo.
echo [2/7] نصب وابستگي‌هاي Composer...
call composer install --no-interaction --prefer-dist
if %ERRORLEVEL% NEQ 0 (
    echo خطا در نصب وابستگي‌ها!
    pause
    exit /b 1
)

echo.
echo [3/7] کپي کردن فايل .env...
if not exist .env (
    copy .env.example .env
    echo فايل .env ايجاد شد.
) else (
    echo فايل .env از قبل وجود دارد.
)

echo.
echo [4/7] توليد کليد اپليکيشن...
call php artisan key:generate
if %ERRORLEVEL% NEQ 0 (
    echo خطا در توليد کليد!
    pause
    exit /b 1
)

echo.
echo [5/7] اجراي مايگريشن‌ها...
echo توجه: مطمئن شويد ديتابيس smart_work_db در phpMyAdmin ايجاد شده باشد.
echo.
set /p "continue=آيا مايجريشن‌ها اجرا شوند؟ (Y/N): "
if /i "%continue%"=="Y" (
    call php artisan migrate --force
    if %ERRORLEVEL% NEQ 0 (
        echo خطا در اجراي مايگريشن‌ها!
        echo لطفاً تنظيمات ديتابيس را در فايل .env بررسي کنيد.
        pause
        exit /b 1
    )
    
    echo.
    echo [6/7] اجراي Seederها...
    call php artisan db:seed --force
    if %ERRORLEVEL% NEQ 0 (
        echo خطا در اجراي Seederها!
        pause
        exit /b 1
    )
) else (
    echo اجراي مايگريشن‌ها لغو شد.
)

echo.
echo [7/7] ايجاد لينک نمادين storage...
call php artisan storage:link
if %ERRORLEVEL% NEQ 0 (
    echo هشدار: ايجاد لينک نمادين با خطا مواجه شد (ممکن است از قبل ايجاد شده باشد)
)

echo.
echo ========================================
echo   نصب با موفقيت انجام شد!
echo ========================================
echo.
echo مراحل بعدي:
echo 1. فايل .env را ويرایش و تنظيمات ديتابيس را وارد کنيد
echo 2. دستورات زيرا اجرا کنيد:
echo    php artisan serve
echo.
echo سپس به آدرس http://localhost:8000 مراجعه کنيد
echo.
echo پنل مديريت:
echo آدرس: http://localhost:8000/admin
echo ايميل: admin@smartwork.com
echo رمز عبور: password123
echo.
pause
