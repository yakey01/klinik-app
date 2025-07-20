#!/bin/bash

# Fix .env syntax error and missing variables

cd /home/u454362045/domains/dokterkuklinik.com/public_html

echo "🔧 Fixing .env syntax error..."

# Backup current .env
cp .env .env.broken.backup

# Fix syntax errors in .env - remove problematic characters
sed -i 's/&//g' .env
sed -i 's/\r//g' .env  # Remove Windows line endings
sed -i '/^$/d' .env     # Remove empty lines
sed -i 's/^[[:space:]]*$//' .env  # Remove whitespace-only lines

# Ensure critical variables are set
if ! grep -q "^APP_NAME=" .env; then
    echo 'APP_NAME="KLINIK DOKTERKU"' >> .env
fi

if ! grep -q "^APP_ENV=" .env; then
    echo 'APP_ENV=production' >> .env
fi

if ! grep -q "^APP_DEBUG=" .env; then
    echo 'APP_DEBUG=false' >> .env
fi

if ! grep -q "^APP_URL=" .env; then
    echo 'APP_URL=https://dokterkuklinik.com' >> .env
fi

if ! grep -q "^DB_CONNECTION=" .env; then
    echo 'DB_CONNECTION=mysql' >> .env
fi

if ! grep -q "^SESSION_DRIVER=" .env; then
    echo 'SESSION_DRIVER=file' >> .env
fi

if ! grep -q "^CACHE_STORE=" .env; then
    echo 'CACHE_STORE=file' >> .env
fi

echo "🔑 Generating APP_KEY..."
php artisan key:generate --force

echo "🧹 Clearing caches..."
php artisan config:clear
php artisan cache:clear

echo "✅ Testing Laravel..."
php artisan --version

echo "🌐 Testing website..."
curl -I https://dokterkuklinik.com/paramedis