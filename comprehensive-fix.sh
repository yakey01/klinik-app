#!/bin/bash

echo "🔧 Comprehensive Laravel Fix"
echo "============================"

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Not in Laravel directory. Please run this from Laravel root."
    exit 1
fi

echo "1. 🔧 Fixing Database Issues..."
echo "=============================="
# Create database file
mkdir -p database
touch database/database.sqlite
chmod 664 database/database.sqlite

# Update .env with correct database path
sed -i '/DB_CONNECTION=/d' .env
sed -i '/DB_DATABASE=/d' .env
echo "DB_CONNECTION=sqlite" >> .env
echo "DB_DATABASE=$(pwd)/database/database.sqlite" >> .env

echo "2. 🧹 Clearing All Caches..."
echo "============================"
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear

echo "3. 🔧 Fixing Pail Issues..."
echo "=========================="
if composer show laravel/pail > /dev/null 2>&1; then
    echo "✅ Laravel Pail is installed"
else
    echo "Installing Laravel Pail..."
    composer require --dev laravel/pail
fi

echo "4. 🔄 Regenerating Autoload..."
echo "============================="
composer dump-autoload

echo "5. 🗄️ Running Database Migrations..."
echo "==================================="
php artisan migrate --force

echo "6. 🔐 Fixing Permissions..."
echo "=========================="
chmod -R 755 storage
chmod -R 755 database
chmod -R 755 bootstrap/cache

echo "7. 🧪 Testing Everything..."
echo "=========================="
echo "Testing Laravel:"
php artisan --version

echo "Testing Database:"
php artisan migrate:status

echo "Testing Pail:"
php artisan pail --help > /dev/null 2>&1 && echo "✅ Pail works" || echo "❌ Pail has issues"

echo "8. 📋 Final Configuration Check..."
echo "================================="
echo "Database path: $(grep DB_DATABASE .env | cut -d'=' -f2)"
echo "Database file exists: $(test -f database/database.sqlite && echo 'Yes' || echo 'No')"
echo "Storage permissions: $(ls -ld storage | awk '{print $1}')"
echo "Cache permissions: $(ls -ld bootstrap/cache | awk '{print $1}')"

echo ""
echo "✅ Comprehensive fix completed!"
echo ""
echo "🎯 Next Steps:"
echo "=============="
echo "1. Test your website: https://dokterkuklinik.com"
echo "2. Check Laravel logs: tail -f storage/logs/laravel.log"
echo "3. If issues persist, run: ./troubleshoot.sh" 