#!/bin/bash

# Debug the 500 error in attendance endpoint

cd /home/u454362045/domains/dokterkuklinik.com/public_html

echo "🔍 Debugging 500 error in attendance endpoint..."

echo "📋 Checking Laravel logs..."
if [ -f storage/logs/laravel.log ]; then
    echo "Recent Laravel errors:"
    tail -20 storage/logs/laravel.log
    echo ""
else
    echo "❌ Laravel log file not found"
fi

echo "🔍 Checking if attendance endpoint exists..."
grep -n "getAttendance" app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php

echo ""
echo "🔍 Checking route registration..."
grep -A 3 -B 3 "attendance.*DokterDashboardController" routes/api.php

echo ""
echo "🔍 Testing endpoint with verbose curl..."
curl -v https://dokterkuklinik.com/api/v2/dashboards/dokter/attendance 2>&1 | head -20

echo ""
echo "🧪 Testing artisan route list..."
php artisan route:list | grep attendance | head -10

echo ""
echo "🔍 Checking PHP syntax..."
php -l app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php

echo ""
echo "📊 Current Laravel status..."
php artisan --version
echo "Environment: $(grep APP_ENV= .env | cut -d'=' -f2)"
echo "Debug mode: $(grep APP_DEBUG= .env | cut -d'=' -f2)"

echo ""
echo "🗄️ Testing database connection..."
php artisan migrate:status | head -5

echo ""
echo "🧹 Clearing caches to ensure clean state..."
php artisan config:clear
php artisan route:clear
php artisan cache:clear

echo ""
echo "🔍 Testing endpoint again after cache clear..."
curl -I https://dokterkuklinik.com/api/v2/dashboards/dokter/attendance 2>/dev/null | head -3