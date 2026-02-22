@echo off
echo =========================================
echo 🚀 Starting Ultimate System Setup...
echo =========================================

echo.
echo [1/4] Installing PHP/Laravel Packages...
call composer install

echo.
echo [2/4] Installing Node.js Packages...
call npm install

echo.
echo [3/4] Installing Python Packages...
call pip install trafilatura curl_cffi beautifulsoup4

echo.
echo [4/4] Clearing Laravel Cache & Restarting Queue...
call php artisan optimize:clear
call php artisan queue:restart

echo.
echo =========================================
echo ✅ All Plugins Installed Successfully!
echo =========================================
pause