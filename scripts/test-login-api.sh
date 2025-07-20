#!/bin/bash

# Test login API endpoint specifically

cd /home/u454362045/domains/dokterkuklinik.com/public_html

echo "🧪 Testing login API endpoints specifically..."

echo "📋 1. Test login API endpoint with AJAX headers:"
curl -H "Accept: application/json" \
     -H "Content-Type: application/json" \
     -H "X-Requested-With: XMLHttpRequest" \
     -w "HTTP Status: %{http_code}\nContent-Type: %{content_type}\n" \
     -s https://dokterkuklinik.com/api/v2/auth/login | head -5

echo ""
echo "📋 2. Test login API with POST (simulated login attempt):"
curl -X POST \
     -H "Accept: application/json" \
     -H "Content-Type: application/json" \
     -d '{"email":"test@test.com","password":"test"}' \
     -w "HTTP Status: %{http_code}\n" \
     -s https://dokterkuklinik.com/api/v2/auth/login | head -5

echo ""
echo "📋 3. Test unified login endpoint:"
curl -X POST \
     -H "Accept: application/json" \
     -H "Content-Type: application/x-www-form-urlencoded" \
     -d "email=test@test.com&password=test" \
     -w "HTTP Status: %{http_code}\n" \
     -s https://dokterkuklinik.com/login | head -5

echo ""
echo "📋 4. Check if login controllers have syntax errors:"
echo "Checking AuthController:"
if [ -f app/Http/Controllers/Api/V2/Auth/AuthController.php ]; then
    php -l app/Http/Controllers/Api/V2/Auth/AuthController.php
else
    echo "❌ Api V2 AuthController not found"
fi

echo ""
echo "Checking UnifiedAuthController:"
if [ -f app/Http/Controllers/Auth/UnifiedAuthController.php ]; then
    php -l app/Http/Controllers/Auth/UnifiedAuthController.php
else
    echo "❌ UnifiedAuthController not found"
fi

echo ""
echo "📋 5. Check Laravel errors for auth/login issues:"
if [ -f storage/logs/laravel.log ]; then
    echo "Recent auth/login errors:"
    tail -50 storage/logs/laravel.log | grep -i -A 3 -B 3 "login\|auth\|500"
else
    echo "❌ No Laravel log file"
fi

echo ""
echo "📋 6. Test if the issue is CSRF related:"
echo "Testing login page for CSRF token:"
curl -s https://dokterkuklinik.com/login | grep -o 'csrf[^"]*' | head -3

echo ""
echo "📋 7. Test simple endpoints to isolate the issue:"
echo "Testing /api health:"
curl -w "Status: %{http_code} " -s https://dokterkuklinik.com/api | head -c 30
echo ""

echo "Testing root domain:"
curl -w "Status: %{http_code} " -s https://dokterkuklinik.com/ | head -c 30
echo ""

echo ""
echo "📋 8. Check database connection specifically for auth:"
php -r "
try {
    require 'vendor/autoload.php';
    \$app = require 'bootstrap/app.php';
    
    \$pdo = new PDO('mysql:host=127.0.0.1;dbname=u454362045_klinik_app_db', 'u454362045_klinik_app_usr', 'KlinikApp2025!');
    
    // Test if users table exists and is accessible
    \$users = \$pdo->query('SELECT COUNT(*) FROM users LIMIT 1')->fetch();
    echo 'Users table: ' . \$users[0] . ' records' . PHP_EOL;
    
    // Test if sessions table exists (needed for login)
    \$sessions = \$pdo->query('SELECT COUNT(*) FROM sessions LIMIT 1')->fetch();
    echo 'Sessions table: ' . \$sessions[0] . ' records' . PHP_EOL;
    
} catch (Exception \$e) {
    echo 'Database error: ' . \$e->getMessage() . PHP_EOL;
}
"

echo ""
echo "🎯 Login API testing complete"