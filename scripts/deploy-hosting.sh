#!/bin/bash

echo "🚀 Dokterku Hosting Deployment Script"
echo "====================================="

# Set working directory
PROJECT_DIR="/home/u454362045/domains/dokterkuklinik.com/public_html"
cd $PROJECT_DIR

echo "📂 Current directory: $(pwd)"

# Check if we're in the right place
if [ ! -f "artisan" ]; then
    echo "❌ Laravel artisan not found. Wrong directory?"
    exit 1
fi

echo "✅ Laravel project detected"

# Pull latest changes
echo "📥 Pulling latest changes from Git..."
git pull origin main

# Install/update dependencies
echo "📦 Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Clear all caches first
echo "🧹 Clearing all caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Generate application key if needed
if ! grep -q "APP_KEY=" .env || [ "$(grep APP_KEY= .env | cut -d'=' -f2)" = "" ]; then
    echo "🔑 Generating application key..."
    php artisan key:generate --force
fi

# Run migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force

# Install/update NPM and build assets
echo "🎨 Building frontend assets..."
npm ci --production
npm run build

# Set proper permissions
echo "🔐 Setting file permissions..."
chmod -R 755 storage bootstrap/cache
chmod -R 644 storage/logs

# Optimize Laravel for production
echo "⚡ Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Clear any remaining issues
echo "🔧 Final optimizations..."
php artisan optimize
composer dump-autoload --optimize

# Test basic functionality
echo "🧪 Testing application..."
php artisan --version

echo ""
echo "✅ Deployment completed successfully!"
echo "🌐 Site should be live at: https://dokterkuklinik.com"
echo ""
echo "📋 Manual checks:"
echo "- Visit the website"
echo "- Test login functionality" 
echo "- Check admin panel access"
echo ""