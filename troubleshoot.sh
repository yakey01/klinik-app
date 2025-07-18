#!/bin/bash

echo "🔍 Laravel Troubleshooting Script"
echo "================================="

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Not in Laravel directory. Please run this from Laravel root."
    exit 1
fi

echo "1. Environment Check:"
echo "===================="
echo "PHP Version: $(php --version | head -n1)"
echo "Composer Version: $(composer --version | head -n1)"
echo "Laravel Version: $(php artisan --version)"
echo "Current Directory: $(pwd)"
echo ""

echo "2. File Permissions Check:"
echo "========================="
ls -la storage/
ls -la bootstrap/cache/
echo ""

echo "3. Environment File Check:"
echo "========================="
if [ -f ".env" ]; then
    echo "✅ .env file exists"
    echo "APP_ENV: $(grep APP_ENV .env | cut -d'=' -f2)"
    echo "APP_DEBUG: $(grep APP_DEBUG .env | cut -d'=' -f2)"
else
    echo "❌ .env file not found"
fi
echo ""

echo "4. Vendor Directory Check:"
echo "========================="
if [ -d "vendor" ]; then
    echo "✅ vendor directory exists"
    echo "Vendor size: $(du -sh vendor | cut -f1)"
else
    echo "❌ vendor directory not found"
    echo "Running composer install..."
    composer install --no-dev --optimize-autoloader
fi
echo ""

echo "5. Laravel Logs Check:"
echo "====================="
if [ -f "storage/logs/laravel.log" ]; then
    echo "✅ Laravel log exists"
    echo "Log size: $(du -sh storage/logs/laravel.log | cut -f1)"
    echo "Latest errors:"
    tail -n 10 storage/logs/laravel.log
else
    echo "❌ No Laravel log found"
fi
echo ""

echo "6. Cache Status:"
echo "==============="
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo "✅ All caches cleared"
echo ""

echo "7. Database Connection Test:"
echo "==========================="
if php artisan tinker --execute="echo 'Database connection: ' . (DB::connection()->getPdo() ? 'OK' : 'FAILED');" 2>/dev/null; then
    echo "✅ Database connection successful"
else
    echo "❌ Database connection failed"
fi
echo ""

echo "8. Package Check:"
echo "================"
echo "Checking key packages..."
composer show laravel/framework
composer show laravel/pail 2>/dev/null || echo "❌ Laravel Pail not installed"
echo ""

echo "9. Service Providers Check:"
echo "=========================="
if [ -f "bootstrap/providers.php" ]; then
    echo "Service providers:"
    cat bootstrap/providers.php
else
    echo "❌ bootstrap/providers.php not found"
fi
echo ""

echo "10. Final Tests:"
echo "==============="
php artisan --version
php artisan route:list --compact | head -n 5
echo ""

echo "✅ Troubleshooting completed!"
echo ""
echo "📋 Summary:"
echo "==========="
echo "If you see any ❌ errors above, here are common solutions:"
echo ""
echo "🔧 For permission issues:"
echo "   chmod -R 755 storage bootstrap/cache"
echo ""
echo "🔧 For missing .env:"
echo "   cp .env.example .env"
echo "   php artisan key:generate"
echo ""
echo "🔧 For vendor issues:"
echo "   composer install --no-dev --optimize-autoloader"
echo ""
echo "🔧 For database issues:"
echo "   Check .env database configuration"
echo ""
echo "🔧 For Pail issues:"
echo "   ./fix-pail.sh" 