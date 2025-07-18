#!/bin/bash

echo "🔧 Fixing Pail Service Provider Error"
echo "====================================="

# Navigate to the correct directory (adjust this path for your hosting)
# cd domains/dokterkuklinik.com/public_html

echo "1. Checking current Laravel version..."
php artisan --version

echo "2. Checking if Pail package is installed..."
if composer show laravel/pail > /dev/null 2>&1; then
    echo "✅ Laravel Pail is installed"
else
    echo "❌ Laravel Pail is not installed"
    echo "Installing Laravel Pail..."
    composer require --dev laravel/pail
fi

echo "3. Checking service providers..."
echo "Current providers in bootstrap/providers.php:"
cat bootstrap/providers.php

echo "4. Checking if PailServiceProvider exists in composer.lock..."
if grep -q "Laravel\\\\Pail\\\\PailServiceProvider" composer.lock; then
    echo "✅ PailServiceProvider found in composer.lock"
else
    echo "❌ PailServiceProvider not found in composer.lock"
fi

echo "5. Clearing all caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo "6. Regenerating autoload files..."
composer dump-autoload

echo "7. Publishing Pail assets (if needed)..."
php artisan vendor:publish --tag=pail-config --force 2>/dev/null || echo "Pail config already published or not needed"

echo "8. Checking for other missing service providers..."
php artisan config:cache

echo "9. Testing Laravel..."
php artisan --version

echo "10. Testing Pail command..."
php artisan pail --help > /dev/null 2>&1 && echo "✅ Pail command works" || echo "❌ Pail command failed"

echo "✅ Pail error fix completed!"
echo "🌐 Test your website: https://dokterkuklinik.com"
echo ""
echo "📝 If you still have issues, try:"
echo "   - composer update"
echo "   - php artisan optimize:clear"
echo "   - Check your .env file for APP_DEBUG=true" 