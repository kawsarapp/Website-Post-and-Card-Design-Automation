# 🚀 VPS / Live Server Deployment Guide
**Project: Multi-Site News Scraper Automation (Python + Puppeteer + Laravel)**

This guide explains everything you need to install on a fresh Linux VPS (Ubuntu/AlmaLinux) to ensure your web scraper and Residential Proxies work perfectly.

---

## 1. System Requirements (Ubuntu 20.04 / 22.04 / 24.04)
Run these commands first to install all the system-level libraries required by Node.js, Puppeteer (Chromium engine), and Python:

```bash
sudo apt update && sudo apt upgrade -y

# Basic Utils
sudo apt install -y curl wget git unzip software-properties-common

# Python 3 & Pip
sudo apt install -y python3 python3-pip python3-venv

# Puppeteer (Headless Chrome) Dependencies for Linux
sudo apt install -y libnss3 libatk-bridge2.0-0 libx11-xcb1 \
libxcomposite1 libxcursor1 libxdamage1 libxi6 libxtst6 \
libnss3 libcups2 libxss1 libxrandr2 libasound2 libatk1.0-0 \
libgtk-3-0 libgbm1
```

---

## 2. Install Project Dependencies

Go to your project folder (e.g. `/var/www/scraper`) and run these commands to install the 3 native technologies used in this project.

### PHP (Laravel)
```bash
composer install --optimize-autoloader --no-dev
php artisan key:generate
php artisan migrate
php artisan storage:link
php artisan config:cache
```

### Node.js (Puppeteer)
```bash
# Verify node is installed (v18+ recommended)
node -v

# Install Node modules for Puppeteer and Stealth Plugins
npm install
npm install puppeteer puppeteer-extra puppeteer-extra-plugin-stealth puppeteer-extra-plugin-adblocker proxy-chain
```

### Python (curl_cffi)
```bash
# Install Python extraction modules
pip3 install curl_cffi bs4 trafilatura
```

---

## 3. Storage & Execution Permissions
For Laravel to create HTML templates, save Python JSON strings, and dump Puppeteer HTML traces, the storage folder and script files must have correct permissions:

```bash
# Give full permission to Laravel's storage
sudo chmod -R 775 storage bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache

# Make Python/Puppeteer scripts executable
chmod +x scraper.py
chmod +x fetch_list.py
chmod +x scraper-engine.js
```

---

## 4. Troubleshooting Future Errors

### A. Python `ModuleNotFoundError: No module named 'curl_cffi'`
If you run multiple Python versions, make sure the system's global Python has the tools:
```bash
sudo python3 -m pip install curl_cffi bs4 trafilatura
```
Or define `PYTHON_PATH` in your `.env` file pointing exactly to where pip installed it.

### B. Node `🔥 NODE FATAL: Execution context was destroyed` or `UnhandledPromiseRejection`
It means Puppeteer needs to be updated or basic Chromium libraries are missing from Ubuntu. Run:
```bash
npx puppeteer browsers install chrome
```

### C. Laravel Proxy Fails / Old Proxy Used
The scraper heavily utilizes Database settings instead of `.env` for Proxy connectivity. If proxy ever fails:
1. Ensure your dashboard has `proxy.smartproxy.net` set with `Port 3121`.
2. Clear Laravel Queue Cache:
```bash
php artisan queue:restart
php artisan config:clear
```
