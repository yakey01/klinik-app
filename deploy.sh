#!/bin/bash

set -e  # Exit on error

echo "🚀 Starting deployment..."

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: artisan file not found. Are you in the Laravel project directory?"
    exit 1
fi

# Pull latest changes
echo "📡 Pulling latest changes..."
git pull origin main

# Backup database (if needed)
echo "💾 Creating database backup..."
if [ "$DB_CONNECTION" = "mysql" ]; then
    mysqldump -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" > "backup_$(date +%Y%m%d_%H%M%S).sql"
fi

# Install dependencies
echo "📦 Installing dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction
npm ci --production
npm run build

# Clear and cache
echo "🧹 Clearing caches and applying changes..."
php artisan down --message="Updating system, please wait..."

# Run migrations
php artisan migrate --force

# Clear and rebuild caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:clear-cached-components

# Ensure storage link exists
php artisan storage:link

# Set proper permissions
echo "🔐 Setting permissions..."
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

# Restart services
echo "🔄 Restarting services..."
php artisan queue:restart

# Bring application back up
php artisan up

# Optional: Clear OPcache if available
if command -v php &> /dev/null; then
    php -r "if (function_exists('opcache_reset')) opcache_reset();" 2>/dev/null || true
fi

echo "✅ Deployment completed successfully!"
echo "📊 Application status: $(php artisan route:list --columns=uri,name | grep -c '^' || echo 'Unknown') routes loaded"