#!/bin/bash

echo "🚀 Quick Database Fix"
echo "===================="

echo "1. Creating database file..."
mkdir -p database
touch database/database.sqlite
chmod 664 database/database.sqlite

echo "2. Setting correct .env configuration..."
# Remove old database config
sed -i '/DB_CONNECTION=/d' .env
sed -i '/DB_DATABASE=/d' .env

# Add correct database config
echo "DB_CONNECTION=sqlite" >> .env
echo "DB_DATABASE=$(pwd)/database/database.sqlite" >> .env

echo "3. Clearing config cache..."
php artisan config:clear

echo "4. Running migrations..."
php artisan migrate --force

echo "5. Testing database..."
php artisan migrate:status

echo "✅ Quick database fix completed!"
echo ""
echo "Current database path: $(grep DB_DATABASE .env | cut -d'=' -f2)"
echo "Database file exists: $(test -f database/database.sqlite && echo 'Yes' || echo 'No')" 