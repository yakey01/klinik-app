#!/bin/bash

echo "🔧 Fixing environment configuration..."

# Navigate to project directory
cd domains/dokterkuklinik.com/public_html

echo "📝 Checking .env file..."
if [ ! -f .env ]; then
    echo "⚠️  .env file not found, creating from example..."
    cp .env.example .env
fi

echo "🔑 Generating application key..."
php artisan key:generate --force

echo "⚙️  Setting proper permissions..."
chmod 644 .env
chmod -R 755 storage bootstrap/cache
chown -R u454362045:u454362045 storage bootstrap/cache || chown -R u454362045 storage bootstrap/cache

echo "🧹 Clearing all caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

echo "🔍 Checking storage directory..."
if [ ! -d storage/logs ]; then
    mkdir -p storage/logs
fi

if [ ! -d storage/framework/cache ]; then
    mkdir -p storage/framework/cache
fi

if [ ! -d storage/framework/sessions ]; then
    mkdir -p storage/framework/sessions
fi

if [ ! -d storage/framework/views ]; then
    mkdir -p storage/framework/views
fi

echo "✅ Environment fix completed!" 