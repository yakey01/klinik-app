#!/bin/bash

# Rollback Script
set -e

echo "⏮️ Starting rollback..."

# Maintenance mode
php artisan down --message="Rolling back to previous version. Please wait." --retry=60

# Restore database backup
echo "💾 Restoring database backup..."
php artisan backup:restore --latest || echo "Backup restore not available, skipping..."

# Git reset to previous commit
echo "🔄 Reverting to previous commit..."
git reset --hard HEAD~1

# Restore composer dependencies
echo "📦 Restoring dependencies..."
composer install --no-dev --optimize-autoloader

# Clear and rebuild caches
echo "⚡ Rebuilding caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Maintenance mode off
php artisan up

echo "✅ Rollback completed!"