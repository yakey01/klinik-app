#!/bin/bash

echo "🔍 Hosting Troubleshooting Script"
echo "================================="

echo "1. Checking PHP version..."
php --version

echo "2. Checking Composer version..."
composer --version

echo "3. Checking Laravel version..."
php artisan --version

echo "4. Checking current directory..."
pwd
ls -la

echo "5. Checking if .env file exists..."
if [ -f ".env" ]; then
    echo "✅ .env file exists"
    echo "APP_ENV: $(grep APP_ENV .env | cut -d'=' -f2)"
    echo "APP_DEBUG: $(grep APP_DEBUG .env | cut -d'=' -f2)"
else
    echo "❌ .env file not found"
fi

echo "6. Checking storage permissions..."
ls -la storage/
ls -la bootstrap/cache/

echo "7. Checking if vendor directory exists..."
if [ -d "vendor" ]; then
    echo "✅ vendor directory exists"
else
    echo "❌ vendor directory not found"
    echo "Running composer install..."
    composer install --no-dev --optimize-autoloader
fi

echo "8. Checking Laravel logs..."
if [ -f "storage/logs/laravel.log" ]; then
    echo "Latest Laravel errors:"
    tail -n 20 storage/logs/laravel.log
else
    echo "No Laravel log file found"
fi

echo "9. Testing basic Laravel commands..."
php artisan config:clear
php artisan cache:clear

echo "10. Checking for Pail specifically..."
if composer show laravel/pail > /dev/null 2>&1; then
    echo "✅ Laravel Pail is installed"
    echo "Pail version: $(composer show laravel/pail | grep version)"
else
    echo "❌ Laravel Pail is not installed"
fi

echo "11. Checking service providers..."
echo "Service providers in bootstrap/providers.php:"
cat bootstrap/providers.php

echo "12. Testing Pail command..."
php artisan pail --help > /dev/null 2>&1 && echo "✅ Pail command works" || echo "❌ Pail command failed"

echo ""
echo "📋 Summary:"
echo "==========="
echo "If you see errors above, here are common solutions:"
echo ""
echo "🔧 For Pail errors:"
echo "   - Run: composer require --dev laravel/pail"
echo "   - Run: php artisan vendor:publish --tag=pail-config"
echo "   - Run: composer dump-autoload"
echo ""
echo "🔧 For general Laravel errors:"
echo "   - Run: php artisan optimize:clear"
echo "   - Run: composer install --no-dev --optimize-autoloader"
echo "   - Check file permissions: chmod -R 755 storage bootstrap/cache"
echo ""
echo "🔧 For hosting-specific issues:"
echo "   - Make sure APP_ENV=production in .env"
echo "   - Make sure APP_DEBUG=false in .env"
echo "   - Check if your hosting supports the required PHP extensions" 