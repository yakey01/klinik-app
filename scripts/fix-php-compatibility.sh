#!/bin/bash

echo "🔧 Fixing PHP compatibility issues..."

# Check PHP version
PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo "Current PHP version: $PHP_VERSION"

# Create composer config to ignore platform requirements
cat > composer.json.local << EOF
{
    "config": {
        "platform": {
            "php": "8.2.29"
        },
        "ignore-platform-reqs": true
    }
}
EOF

# Install dependencies with platform requirements ignored
echo "📦 Installing dependencies with platform requirements ignored..."
composer install --no-dev --ignore-platform-reqs --optimize-autoloader --no-scripts

# Clear all caches
echo "🧹 Clearing all caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Set proper permissions
echo "🔐 Setting permissions..."
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

echo "✅ PHP compatibility fix completed!" 