#!/bin/bash

# Test the attendance endpoint from browser perspective

cd /home/u454362045/domains/dokterkuklinik.com/public_html

echo "🌐 Testing attendance endpoint from browser perspective..."

echo "📋 1. Test with AJAX headers (like JavaScript would):"
curl -H "Accept: application/json" \
     -H "Content-Type: application/json" \
     -H "X-Requested-With: XMLHttpRequest" \
     -w "HTTP Status: %{http_code}\nContent-Type: %{content_type}\n" \
     -s https://dokterkuklinik.com/api/v2/dashboards/dokter/attendance | head -10

echo ""
echo "📋 2. Test with browser User-Agent:"
curl -H "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36" \
     -H "Accept: application/json, text/plain, */*" \
     -w "HTTP Status: %{http_code}\n" \
     -s https://dokterkuklinik.com/api/v2/dashboards/dokter/attendance | head -5

echo ""
echo "📋 3. Test if the route exists for GET vs POST:"
echo "GET /api/v2/dashboards/dokter/attendance:"
curl -X GET -w "Status: %{http_code} " -s https://dokterkuklinik.com/api/v2/dashboards/dokter/attendance | head -c 50
echo ""

echo ""
echo "📋 4. Check the actual route registration:"
php artisan route:list | grep "dokter/attendance"

echo ""
echo "📋 5. Check for any route conflicts:"
php artisan route:list | grep "attendance" | grep -v "paramedis" | head -10

echo ""
echo "📋 6. Test a working API endpoint for comparison:"
echo "Testing /api/v2/dashboards/dokter/test (if exists):"
curl -w "Status: %{http_code} " -s https://dokterkuklinik.com/api/v2/dashboards/dokter/test | head -c 50
echo ""

echo ""
echo "📋 7. Test simple endpoint that should work:"
echo "Testing root API:"
curl -w "Status: %{http_code} " -s https://dokterkuklinik.com/api | head -c 50
echo ""

echo ""
echo "📋 8. Check if there's an issue with the API middleware:"
echo "Checking middleware on dokter routes..."
php artisan route:list | grep "dokter.*attendance" -A 2 -B 2

echo ""
echo "📋 9. Direct PHP test of controller method:"
cat > test-controller-direct.php << 'EOF'
<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';

try {
    // Test if controller can be instantiated
    $controller = new App\Http\Controllers\Api\V2\Dashboards\DokterDashboardController();
    echo "✅ Controller instantiated successfully\n";
    
    // Check if method exists
    if (method_exists($controller, 'getAttendance')) {
        echo "✅ getAttendance method exists\n";
    } else {
        echo "❌ getAttendance method NOT found\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
EOF

php test-controller-direct.php
rm test-controller-direct.php

echo ""
echo "📋 10. Summary:"
echo "If attendance endpoint returns HTML redirect = Working correctly"
echo "If attendance endpoint returns 500 = Still has issues"
echo "JavaScript should handle 302 redirects properly"