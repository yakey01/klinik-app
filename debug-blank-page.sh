#!/bin/bash

echo "🔍 Debugging Blank Page Issue"
echo "============================="

cd domains/dokterkuklinik.com/public_html

echo "1. Enabling error display..."
# Add error display to index.php temporarily
cp public/index.php public/index.php.backup
sed -i '1i ini_set("display_errors", 1); ini_set("display_startup_errors", 1); error_reporting(E_ALL);' public/index.php

echo "2. Checking .env file..."
if [ -f .env ]; then
    echo "   .env exists"
    grep -E "APP_DEBUG|APP_ENV" .env
else
    echo "   ❌ .env missing - creating from example..."
    cp .env.example .env
    php artisan key:generate
fi

echo "3. Checking Laravel version..."
php artisan --version

echo "4. Testing basic PHP..."
php -r "echo 'PHP is working';"

echo "5. Checking file permissions..."
ls -la public/index.php
ls -la storage/
ls -la bootstrap/cache/

echo "6. Clearing all caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo "7. Checking for missing dependencies..."
composer install --no-dev --optimize-autoloader

echo "8. Testing Laravel routes..."
php artisan route:list --compact

echo "✅ Debug script completed!"
echo "🌐 Now test: https://dokterkuklinik.com"
echo "📝 Check for error messages on the page" 