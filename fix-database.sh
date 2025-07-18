#!/bin/bash

echo "🔧 Database Fix Script"
echo "====================="

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Not in Laravel directory. Please run this from Laravel root."
    exit 1
fi

echo "1. Checking current .env configuration..."
if [ -f ".env" ]; then
    echo "Current database configuration:"
    grep -E "DB_CONNECTION|DB_DATABASE" .env
else
    echo "❌ .env file not found"
    exit 1
fi

echo ""
echo "2. Creating database directory..."
mkdir -p database
mkdir -p storage/database

echo "3. Creating SQLite database file..."
touch database/database.sqlite
chmod 664 database/database.sqlite

echo "4. Setting correct permissions..."
chmod -R 755 storage
chmod -R 755 database

echo "5. Updating .env with correct database path..."
# Backup current .env
cp .env .env.backup

# Update .env with correct database configuration
sed -i '/DB_CONNECTION=/d' .env
sed -i '/DB_DATABASE=/d' .env

echo "DB_CONNECTION=sqlite" >> .env
echo "DB_DATABASE=$(pwd)/database/database.sqlite" >> .env

echo "6. Clearing config cache..."
php artisan config:clear

echo "7. Testing database connection..."
if php artisan tinker --execute="echo 'Database connection: ' . (DB::connection()->getPdo() ? 'OK' : 'FAILED');" 2>/dev/null; then
    echo "✅ Database connection successful"
else
    echo "❌ Database connection failed"
    echo "Trying alternative database path..."
    
    # Try alternative path
    echo "DB_DATABASE=/home/u454362045/domains/dokterkuklinik.com/public_html/database/database.sqlite" > .env.temp
    grep -v "DB_DATABASE" .env >> .env.temp
    mv .env.temp .env
    
    php artisan config:clear
    
    if php artisan tinker --execute="echo 'Database connection: ' . (DB::connection()->getPdo() ? 'OK' : 'FAILED');" 2>/dev/null; then
        echo "✅ Database connection successful with alternative path"
    else
        echo "❌ Database connection still failed"
    fi
fi

echo "8. Running migrations..."
php artisan migrate --force

echo "9. Checking migration status..."
php artisan migrate:status

echo "10. Creating basic .env configuration if needed..."
if ! grep -q "APP_NAME" .env; then
    echo "APP_NAME=Dokterku" >> .env
fi

if ! grep -q "APP_ENV" .env; then
    echo "APP_ENV=production" >> .env
fi

if ! grep -q "APP_DEBUG" .env; then
    echo "APP_DEBUG=false" >> .env
fi

if ! grep -q "APP_URL" .env; then
    echo "APP_URL=https://dokterkuklinik.com" >> .env
fi

if ! grep -q "APP_KEY" .env; then
    echo "Generating new APP_KEY..."
    php artisan key:generate
fi

echo ""
echo "✅ Database fix completed!"
echo ""
echo "📋 Database Configuration:"
echo "========================="
echo "DB_CONNECTION: $(grep DB_CONNECTION .env | cut -d'=' -f2)"
echo "DB_DATABASE: $(grep DB_DATABASE .env | cut -d'=' -f2)"
echo "Database file exists: $(test -f database/database.sqlite && echo 'Yes' || echo 'No')"
echo "Database size: $(test -f database/database.sqlite && du -h database/database.sqlite | cut -f1 || echo 'N/A')" 