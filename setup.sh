#!binbash
echo =========================================
echo 🚀 Starting Ultimate System Setup...
echo =========================================

echo [14] Installing PHPLaravel Packages...
composer install

echo [24] Installing Node.js Packages...
npm install

echo [34] Installing Python Packages...
pip3 install trafilatura curl_cffi beautifulsoup4

echo [44] Clearing Laravel Cache & Restarting Queue...
php artisan optimizeclear
php artisan queuerestart

echo =========================================
echo ✅ All Plugins Installed Successfully!
echo =========================================