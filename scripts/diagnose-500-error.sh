#!/bin/bash

# Comprehensive 500 error diagnosis after database credential update

cd /home/u454362045/domains/dokterkuklinik.com/public_html

echo "🔍 Diagnosing 500 server error after database credential update..."

echo "📋 1. Current database configuration:"
grep "DB_" .env | head -6

echo ""
echo "📋 2. Test direct database connection with current credentials:"
DB_HOST=$(grep "DB_HOST" .env | cut -d'=' -f2)
DB_PORT=$(grep "DB_PORT" .env | cut -d'=' -f2)
DB_DATABASE=$(grep "DB_DATABASE" .env | cut -d'=' -f2)
DB_USERNAME=$(grep "DB_USERNAME" .env | cut -d'=' -f2)
DB_PASSWORD=$(grep "DB_PASSWORD" .env | cut -d'=' -f2)

echo "Testing connection: $DB_USERNAME@$DB_HOST:$DB_PORT -> $DB_DATABASE"

if mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "USE $DB_DATABASE; SELECT 1;" 2>/dev/null; then
    echo "✅ Database connection: SUCCESS"
    
    echo ""
    echo "📋 3. Test Laravel database connection:"
    php artisan migrate:status 2>&1 | head -10
    
    echo ""
    echo "📋 4. Check if required tables exist:"
    mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "
    USE $DB_DATABASE; 
    SHOW TABLES LIKE 'users';
    SHOW TABLES LIKE 'sessions';
    SELECT COUNT(*) as user_count FROM users;
    " 2>&1
    
else
    echo "❌ Database connection: FAILED"
    echo "Error details:"
    mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "USE $DB_DATABASE; SELECT 1;" 2>&1
fi

echo ""
echo "📋 5. Check Laravel error logs:"
echo "Recent Laravel errors:"
if [ -f storage/logs/laravel.log ]; then
    tail -20 storage/logs/laravel.log | grep -E "(SQLSTATE|Exception|Error|Fatal)" || echo "No recent database errors in Laravel log"
else
    echo "Laravel log file not found"
fi

echo ""
echo "📋 6. Check PHP error logs:"
if [ -f /home/u454362045/domains/dokterkuklinik.com/logs/error_log ]; then
    echo "Recent PHP errors:"
    tail -20 /home/u454362045/domains/dokterkuklinik.com/logs/error_log | grep -E "(Fatal|Error|Exception)" || echo "No recent PHP errors"
else
    echo "PHP error log not found at expected location"
fi

echo ""
echo "📋 7. Test PHP configuration:"
php -r "
echo 'PHP Version: ' . PHP_VERSION . PHP_EOL;
echo 'Memory Limit: ' . ini_get('memory_limit') . PHP_EOL;
echo 'Max Execution Time: ' . ini_get('max_execution_time') . PHP_EOL;
echo 'Error Reporting: ' . ini_get('error_reporting') . PHP_EOL;
echo 'Display Errors: ' . ini_get('display_errors') . PHP_EOL;
"

echo ""
echo "📋 8. Test Laravel application bootstrap:"
php -r "
try {
    require 'vendor/autoload.php';
    \$app = require 'bootstrap/app.php';
    echo 'Laravel bootstrap: ✅ SUCCESS' . PHP_EOL;
    
    // Test database connection through Laravel
    \$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
    \$kernel->bootstrap();
    
    echo 'Laravel kernel bootstrap: ✅ SUCCESS' . PHP_EOL;
    
} catch (Exception \$e) {
    echo 'Laravel bootstrap error: ❌ ' . \$e->getMessage() . PHP_EOL;
    echo 'Stack trace: ' . \$e->getTraceAsString() . PHP_EOL;
}
"

echo ""
echo "📋 9. Test specific login endpoint with detailed error reporting:"
echo "Testing login endpoint directly:"

# Enable PHP error reporting for this test
php -d display_errors=1 -d error_reporting=E_ALL -r "
try {
    require 'vendor/autoload.php';
    \$app = require 'bootstrap/app.php';
    \$kernel = \$app->make(Illuminate\Contracts\Http\Kernel::class);
    
    // Create a test request to the login endpoint
    \$request = Illuminate\Http\Request::create('/api/v2/auth/login', 'POST', [
        'login' => 'test@test.com',
        'password' => 'test',
        'device_id' => 'test'
    ], [], [], [
        'HTTP_ACCEPT' => 'application/json',
        'CONTENT_TYPE' => 'application/json'
    ]);
    
    \$response = \$kernel->handle(\$request);
    echo 'Login endpoint response status: ' . \$response->getStatusCode() . PHP_EOL;
    echo 'Response content: ' . \$response->getContent() . PHP_EOL;
    
} catch (Exception \$e) {
    echo 'Login endpoint error: ❌ ' . \$e->getMessage() . PHP_EOL;
    echo 'File: ' . \$e->getFile() . ':' . \$e->getLine() . PHP_EOL;
}
"

echo ""
echo "📋 10. Check web server error logs:"
if [ -f /var/log/apache2/error.log ]; then
    echo "Apache error log (last 10 lines):"
    tail -10 /var/log/apache2/error.log 2>/dev/null || echo "Cannot read Apache error log"
elif [ -f /var/log/nginx/error.log ]; then
    echo "Nginx error log (last 10 lines):"
    tail -10 /var/log/nginx/error.log 2>/dev/null || echo "Cannot read Nginx error log"
else
    echo "Web server error logs not found in standard locations"
fi

echo ""
echo "📋 11. Check .env file permissions and syntax:"
echo "File permissions:"
ls -la .env

echo ""
echo "Check for hidden characters or syntax issues:"
cat -A .env | grep "DB_" | head -6

echo ""
echo "📋 12. Clear all caches and test again:"
php artisan config:clear 2>&1
php artisan cache:clear 2>&1
php artisan route:clear 2>&1
php artisan view:clear 2>&1

echo ""
echo "📋 13. Final live test of login endpoint:"
curl -X POST \
     -H "Accept: application/json" \
     -H "Content-Type: application/json" \
     -d '{"login":"admin","password":"admin","device_id":"test"}' \
     -w "\nHTTP Status: %{http_code}\n" \
     -v https://dokterkuklinik.com/api/v2/auth/login 2>&1

echo ""
echo "🏁 500 error diagnosis complete!"