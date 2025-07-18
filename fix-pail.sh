#!/bin/bash

echo "🔧 Pail Fix Script"
echo "=================="

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Not in Laravel directory. Please run this from Laravel root."
    exit 1
fi

echo "1. Checking Laravel version..."
php artisan --version

echo "2. Checking Pail installation..."
if composer show laravel/pail > /dev/null 2>&1; then
    echo "✅ Laravel Pail is installed"
    echo "Pail version: $(composer show laravel/pail | grep version)"
else
    echo "❌ Laravel Pail is not installed"
    echo "Installing Laravel Pail..."
    composer require --dev laravel/pail
fi

echo "3. Checking service providers..."
if [ -f "bootstrap/providers.php" ]; then
    echo "Service providers in bootstrap/providers.php:"
    cat bootstrap/providers.php
else
    echo "❌ bootstrap/providers.php not found"
fi

echo "4. Clearing all caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo "5. Regenerating autoload files..."
composer dump-autoload

echo "6. Publishing Pail assets (if needed)..."
php artisan vendor:publish --tag=pail-config --force 2>/dev/null || echo "Pail config already published or not needed"

echo "7. Testing Pail command..."
if php artisan pail --help > /dev/null 2>&1; then
    echo "✅ Pail command works"
else
    echo "❌ Pail command failed"
    echo "Trying alternative fix..."
    composer update
    php artisan optimize:clear
    composer dump-autoload
fi

echo "8. Final test..."
php artisan --version
php artisan pail --help > /dev/null 2>&1 && echo "✅ Pail is working!" || echo "❌ Pail still has issues"

echo "✅ Pail fix completed!" 