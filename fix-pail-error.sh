#!/bin/bash

echo "🔧 Fixing Pail Service Provider Error"
echo "====================================="

cd domains/dokterkuklinik.com/public_html

echo "1. Removing Pail service provider from config..."
# Remove Pail service provider from config/app.php
sed -i '/Laravel\\Pail\\PailServiceProvider/d' config/app.php

echo "2. Clearing all caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo "3. Regenerating autoload files..."
composer dump-autoload

echo "4. Checking for other missing service providers..."
php artisan config:cache

echo "5. Testing Laravel..."
php artisan --version

echo "✅ Pail error fix completed!"
echo "🌐 Test your website: https://dokterkuklinik.com" 