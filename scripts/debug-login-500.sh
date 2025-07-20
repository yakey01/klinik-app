#!/bin/bash

# Debug 500 error on login endpoint

cd /home/u454362045/domains/dokterkuklinik.com/public_html

echo "🔍 Debugging 500 error on login endpoint..."

echo "📋 1. Testing login endpoint directly:"
curl -w "HTTP Status: %{http_code}\nResponse Time: %{time_total}s\n" -s https://dokterkuklinik.com/login | head -10

echo ""
echo "📋 2. Testing login with different methods:"
echo "GET /login:"
curl -X GET -w "Status: %{http_code} " -s https://dokterkuklinik.com/login | head -c 100
echo ""

echo "POST /login (simulated form):"
curl -X POST -w "Status: %{http_code} " -s https://dokterkuklinik.com/login | head -c 100
echo ""

echo ""
echo "📋 3. Check login routes:"
php artisan route:list | grep -i login | head -10

echo ""
echo "📋 4. Check latest Laravel errors (focusing on login):"
if [ -f storage/logs/laravel.log ]; then
    echo "=== LATEST LOGIN-RELATED ERRORS ==="
    tail -100 storage/logs/laravel.log | grep -A 10 -B 10 -i "login\|auth"
else
    echo "❌ Laravel log file not found"
fi

echo ""
echo "📋 5. Test authentication endpoints:"
echo "Testing /api/auth endpoints:"
curl -w "Status: %{http_code} " -s https://dokterkuklinik.com/api/auth | head -c 50
echo ""

echo "Testing unified auth:"
curl -w "Status: %{http_code} " -s https://dokterkuklinik.com/api/v2/auth/login | head -c 50
echo ""

echo ""
echo "📋 6. Check auth middleware configuration:"
grep -r "auth" app/Http/Middleware/ | head -5

echo ""
echo "📋 7. Check if login controller exists and has syntax errors:"
if [ -f app/Http/Controllers/Auth/UnifiedAuthController.php ]; then
    echo "Checking UnifiedAuthController syntax:"
    php -l app/Http/Controllers/Auth/UnifiedAuthController.php
else
    echo "❌ UnifiedAuthController not found"
fi

echo ""
echo "📋 8. Check authentication configuration:"
echo "Auth config:"
grep -A 5 "'defaults'" config/auth.php 2>/dev/null || echo "Could not read auth config"

echo ""
echo "📋 9. Test database connection for authentication:"
echo "Testing user authentication table:"
php -r "
try {
    require 'vendor/autoload.php';
    \$app = require 'bootstrap/app.php';
    
    // Test if we can connect to users table
    \$pdo = new PDO('mysql:host=127.0.0.1;dbname=u454362045_klinik_app_db', 'u454362045_klinik_app_usr', 'KlinikApp2025!');
    \$result = \$pdo->query('SELECT COUNT(*) FROM users LIMIT 1')->fetch();
    echo 'Users table accessible: ' . \$result[0] . ' users found' . PHP_EOL;
} catch (Exception \$e) {
    echo 'Database error: ' . \$e->getMessage() . PHP_EOL;
}
" 2>/dev/null || echo "Could not test database"

echo ""
echo "📋 10. Check session configuration:"
echo "Session driver: $(grep SESSION_DRIVER= .env | cut -d'=' -f2)"
echo "Session lifetime: $(grep SESSION_LIFETIME= .env | cut -d'=' -f2)"

echo ""
echo "📋 11. Test if the error is in the login page view:"
echo "Checking if login blade template exists:"
find resources/views -name "*login*" -type f | head -5

echo ""
echo "📋 12. Clear caches and test login again:"
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo "Testing login after cache clear:"
curl -w "Status: %{http_code} " -s https://dokterkuklinik.com/login | head -c 50
echo ""

echo ""
echo "🎯 Summary: Login endpoint investigation complete"