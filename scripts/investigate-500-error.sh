#!/bin/bash

# Investigate the persistent 500 server error

cd /home/u454362045/domains/dokterkuklinik.com/public_html

echo "🔍 Investigating persistent 500 server error..."

echo "📋 1. Testing attendance endpoint directly:"
curl -w "HTTP Status: %{http_code}\nResponse Time: %{time_total}s\n" -s https://dokterkuklinik.com/api/v2/dashboards/dokter/attendance | head -10

echo ""
echo "📋 2. Checking latest Laravel logs (last 50 lines):"
if [ -f storage/logs/laravel.log ]; then
    echo "=== LATEST LARAVEL ERRORS ==="
    tail -50 storage/logs/laravel.log
else
    echo "❌ Laravel log file not found at storage/logs/laravel.log"
fi

echo ""
echo "📋 3. Checking PHP error logs:"
if [ -f /home/u454362045/domains/dokterkuklinik.com/logs/error_log ]; then
    echo "=== LATEST PHP ERRORS ==="
    tail -20 /home/u454362045/domains/dokterkuklinik.com/logs/error_log
else
    echo "❌ PHP error log not found"
fi

echo ""
echo "📋 4. Testing simple Laravel endpoint:"
# Create a simple test route to isolate the issue
echo "Creating test route..."
cat >> routes/web.php << 'EOF'

// Temporary test route
Route::get('/test-500-debug', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'Laravel is working',
        'timestamp' => now(),
        'database_test' => 'skipped'
    ]);
});
EOF

echo "Testing simple Laravel route:"
curl -w "HTTP Status: %{http_code}\n" -s https://dokterkuklinik.com/test-500-debug | head -5

echo ""
echo "📋 5. Testing with database connection:"
cat > test-db-connection.php << 'EOF'
<?php
try {
    require 'vendor/autoload.php';
    $app = require 'bootstrap/app.php';
    
    // Test database connection
    $pdo = new PDO(
        "mysql:host=127.0.0.1;dbname=u454362045_klinik_app_db",
        "u454362045_klinik_app_usr",
        "KlinikApp2025!"
    );
    
    $result = $pdo->query("SELECT 1")->fetch();
    echo json_encode([
        'database_connection' => 'success',
        'test_query' => $result[0] ?? 'failed'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'database_connection' => 'failed',
        'error' => $e->getMessage()
    ]);
}
EOF

echo "Testing database connection:"
php test-db-connection.php
rm test-db-connection.php

echo ""
echo "📋 6. Testing attendance endpoint with artisan:"
php artisan route:list | grep attendance | head -5

echo ""
echo "📋 7. Check if controller file has syntax errors:"
php -l app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php

echo ""
echo "📋 8. Check server configuration:"
echo "PHP Version: $(php -v | head -1)"
echo "Laravel Version: $(php artisan --version)"
echo "Environment: $(grep APP_ENV= .env | cut -d'=' -f2)"

echo ""
echo "📋 9. Clear all caches and test again:"
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan view:clear

echo "Testing after cache clear:"
curl -w "HTTP Status: %{http_code}\n" -s https://dokterkuklinik.com/api/v2/dashboards/dokter/attendance | head -3

# Clean up test route
sed -i '/\/\/ Temporary test route/,+8d' routes/web.php

echo ""
echo "📋 10. Summary of findings will be above ☝️"