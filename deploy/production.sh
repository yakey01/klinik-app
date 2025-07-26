#!/bin/bash

# Production Deployment Script
set -e

echo "🚀 Starting production deployment..."

# Maintenance mode
php artisan down --message="Upgrading system. Please check back in a few minutes." --retry=60

# Backup database
echo "💾 Backing up database..."
php artisan backup:run --only-db || echo "Backup command not available, skipping..."

# Git pull
echo "📥 Pulling latest changes..."
git pull origin main

# Install dependencies
echo "📦 Installing dependencies..."
composer install --no-dev --optimize-autoloader

# Build assets
echo "🎨 Building assets..."
npm ci
npm run build

# Database
echo "🗄️ Running migrations..."
php artisan migrate --force

# Clear caches
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Rebuild caches
echo "⚡ Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Queue restart
echo "🔄 Restarting queues..."
php artisan queue:restart

# Maintenance mode off
php artisan up

echo "✅ Deployment completed successfully!"