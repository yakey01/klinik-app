#!/bin/bash

echo "🔧 Starting manual deployment fix..."

# Navigate to project directory
cd domains/dokterkuklinik.com/public_html

echo "📥 Pulling latest changes..."
git pull origin main

echo "📦 Updating composer..."
composer self-update --no-interaction

echo "📦 Installing composer dependencies..."
composer install --no-dev --ignore-platform-reqs --optimize-autoloader

echo "🔄 Regenerating autoload files..."
composer dump-autoload --optimize

echo "🔍 Discovering packages..."
php artisan package:discover --ansi

echo "🎨 Upgrading Filament..."
php artisan filament:upgrade

echo "🧹 Clearing all caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

echo "🗄️ Running migrations..."
php artisan migrate --force

echo "🔐 Setting permissions..."
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

echo "✅ Manual deployment fix completed!" 