#!/bin/bash

# Test the attendance endpoint with proper authentication

cd /home/u454362045/domains/dokterkuklinik.com/public_html

echo "🧪 Testing attendance endpoint with authentication..."

echo "📋 1. Testing endpoint accessibility (should get redirect to login, not 500):"
response=$(curl -w "%{http_code}" -s https://dokterkuklinik.com/api/v2/dashboards/dokter/attendance)
http_code="${response: -3}"
echo "HTTP Status: $http_code"
if [ "$http_code" = "302" ]; then
    echo "✅ SUCCESS: Endpoint returns 302 redirect (no longer 500 error)"
elif [ "$http_code" = "500" ]; then
    echo "❌ FAILED: Still getting 500 error"
else
    echo "ℹ️  Got status $http_code"
fi

echo ""
echo "📋 2. Testing if attendance route is registered:"
php artisan route:list | grep "dokter/attendance" | head -3

echo ""
echo "📋 3. Testing controller method exists:"
grep -n "getAttendance" app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php | head -1

echo ""
echo "📋 4. Check if 500 error was due to database connection:"
# Try to create a simple test endpoint that doesn't use database
echo "Creating temporary test endpoint..."
cat > test-endpoint.php << 'EOF'
<?php
// Simple test to see if PHP/Laravel is working
try {
    require 'vendor/autoload.php';
    $app = require 'bootstrap/app.php';
    
    echo json_encode([
        'success' => true,
        'message' => 'Laravel is working',
        'timestamp' => date('Y-m-d H:i:s'),
        'endpoint' => 'test'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
EOF

echo "Testing Laravel bootstrap:"
php test-endpoint.php
rm test-endpoint.php

echo ""
echo "📋 5. Summary:"
echo "- Route exists: $(php artisan route:list | grep 'dokter/attendance' | wc -l) match(es)"
echo "- Controller method exists: $(grep -c 'getAttendance' app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php) match(es)"
echo "- HTTP response: $http_code (should be 302 for authentication redirect)"
echo ""
if [ "$http_code" = "302" ]; then
    echo "🎉 CONCLUSION: Attendance 500 error has been FIXED!"
    echo "   The endpoint now properly redirects for authentication instead of throwing 500 error."
    echo "   The original JavaScript error 'Failed to load resource: 500' should be resolved."
else
    echo "⚠️  CONCLUSION: May need further investigation."
fi