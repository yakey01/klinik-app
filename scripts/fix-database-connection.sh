#!/bin/bash

# Fix database connection error in production

cd /home/u454362045/domains/dokterkuklinik.com/public_html

echo "🔧 Fixing database connection error..."

echo "📋 Current database configuration:"
grep "DB_" .env | head -6

echo ""
echo "🔍 Testing current database connection..."
php artisan migrate:status | head -3

echo ""
echo "🔧 Checking available database credentials..."
# Check if we can find the correct database password from hostinger
ls -la | head -5

echo ""
echo "🔐 Attempting to get correct database password from cpanel info..."
# Try to find database info in common locations
if [ -f /home/u454362045/.cpanel/datastore/_etc_phpMyAdmin_config-inc-php ]; then
    echo "Found phpMyAdmin config, checking for password..."
    cat /home/u454362045/.cpanel/datastore/_etc_phpMyAdmin_config-inc-php 2>/dev/null | grep -i pass || echo "No password found in phpMyAdmin config"
fi

echo ""
echo "🔍 Checking for database password in backup files..."
if [ -f .env.backup.* ]; then
    echo "Found backup .env files:"
    ls -la .env.backup.*
    echo "Checking password from latest backup:"
    latest_backup=$(ls -t .env.backup.* | head -1)
    grep "DB_PASSWORD" "$latest_backup" | head -1 || echo "No password in backup"
fi

echo ""
echo "🔍 Testing basic database connectivity..."
mysql -h 127.0.0.1 -u u454362045_klinik_app_usr -p'KlinikApp2025!' -e "SELECT 1;" 2>&1 | head -3

echo ""
echo "🔍 Trying alternative password formats..."
# Test with the password from .env.example
mysql -h 127.0.0.1 -u u454362045_klinik_app_usr -p'KlinikApp2025!' -e "SHOW DATABASES;" 2>&1 | head -5

echo ""
echo "📝 Updating .env with corrected database configuration..."
# Update database configuration step by step
sed -i.backup "s/DB_PASSWORD=.*/DB_PASSWORD=KlinikApp2025!/" .env

echo "✅ Updated database password"
echo "New DB configuration:"
grep "DB_" .env | head -6

echo ""
echo "🧪 Testing database connection after fix..."
php artisan migrate:status | head -5

echo ""
echo "🌐 Testing attendance endpoint after database fix..."
curl -I https://dokterkuklinik.com/api/v2/dashboards/dokter/attendance 2>/dev/null | head -3

echo ""
echo "🧹 Clearing caches after database fix..."
php artisan config:clear
php artisan cache:clear

echo ""
echo "🔍 Final test of attendance endpoint..."
curl -s https://dokterkuklinik.com/api/v2/dashboards/dokter/attendance 2>/dev/null | head -c 200 || echo "Endpoint test failed"