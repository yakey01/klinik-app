#!/bin/bash

# Completely rebuild .env file to eliminate all syntax errors

cd /home/u454362045/domains/dokterkuklinik.com/public_html

echo "🔧 Completely rebuilding .env file..."

# Backup current broken .env
cp .env .env.broken.$(date +%Y%m%d_%H%M%S)

# Extract database password from broken .env if possible
DB_PASS=""
if [ -f .env ]; then
    DB_PASS=$(grep "DB_PASSWORD" .env | head -1 | cut -d'=' -f2- | sed 's/^["'"'"']//' | sed 's/["'"'"']$//' | tr -d '\r\n' || echo "")
fi

# Create completely clean .env
cat > .env << 'EOF'
APP_NAME="KLINIK DOKTERKU"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://dokterkuklinik.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=u454362045_klinik_app_db
DB_USERNAME=u454362045_klinik_app_usr
DB_PASSWORD=PLACEHOLDER_PASSWORD

SESSION_DRIVER=file
SESSION_LIFETIME=120
CACHE_STORE=file
QUEUE_CONNECTION=sync

LOG_CHANNEL=single
LOG_LEVEL=error

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local

MAIL_MAILER=log
EOF

# Update database password if we found one
if [ ! -z "$DB_PASS" ]; then
    sed -i "s/PLACEHOLDER_PASSWORD/$DB_PASS/" .env
    echo "✅ Database password preserved: ${DB_PASS:0:3}***"
else
    echo "⚠️ No database password found"
fi

echo "🔑 Generating new APP_KEY..."
php artisan key:generate --force

echo "🧹 Clearing all caches..."
php artisan config:clear 2>/dev/null || echo "Config clear skipped"
php artisan cache:clear 2>/dev/null || echo "Cache clear skipped"
php artisan view:clear 2>/dev/null || echo "View clear skipped"
php artisan route:clear 2>/dev/null || echo "Route clear skipped"

echo "📝 Setting permissions..."
chmod 644 .env

echo "✅ Testing new .env..."
php -l .env && echo "✅ .env syntax OK" || echo "❌ .env syntax still broken"

echo "🚀 Testing Laravel..."
php artisan --version && echo "✅ Laravel working" || echo "❌ Laravel still broken"

echo "🌐 Testing endpoints..."
curl -I https://dokterkuklinik.com/login 2>/dev/null | head -1
curl -I https://dokterkuklinik.com/paramedis 2>/dev/null | head -1

echo "📊 Final .env status:"
echo "APP_KEY: $(grep '^APP_KEY=' .env | cut -d'=' -f2 | head -c 20)..."
echo "DB_CONNECTION: $(grep '^DB_CONNECTION=' .env | cut -d'=' -f2)"
echo "SESSION_DRIVER: $(grep '^SESSION_DRIVER=' .env | cut -d'=' -f2)"