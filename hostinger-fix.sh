#!/bin/bash

echo "🔧 Starting comprehensive Hostinger fix..."

# Navigate to project directory
cd domains/dokterkuklinik.com/public_html

echo "📥 Pulling latest changes..."
git pull origin main

echo "📦 Updating composer..."
composer self-update --no-interaction

echo "📦 Installing dependencies..."
composer install --no-dev --ignore-platform-reqs --optimize-autoloader

echo "🔄 Regenerating autoload files..."
composer dump-autoload --optimize

echo "📝 Checking .env file..."
if [ ! -f .env ]; then
    echo "⚠️  .env file not found, creating from example..."
    cp .env.example .env
fi

echo "🔑 Generating application key..."
php artisan key:generate --force

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

echo "🔍 Creating storage directories..."
mkdir -p storage/logs
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/app/public

echo "🔐 Setting permissions..."
chmod 644 .env
chmod -R 755 storage bootstrap/cache
chown -R u454362045:u454362045 storage bootstrap/cache || chown -R u454362045 storage bootstrap/cache

echo "📋 Testing application..."
php artisan about

echo "✅ Hostinger fix completed!" 