#!/bin/bash

# Remote 500 Error Analysis using HTTP requests
echo "🌐 REMOTE 500 ERROR ANALYSIS"
echo "============================="

BASE_URL="https://dokterkuklinik.com"

echo "🔍 Testing production endpoints..."

echo
echo "📋 Step 1: Login Page Accessibility"
echo "-----------------------------------"
echo "Testing main login page:"
curl -s -I "$BASE_URL/login" | head -5

echo "Response body size:"
curl -s -o /dev/null -w "Status: %{http_code} | Size: %{size_download} bytes | Time: %{time_total}s\n" "$BASE_URL/login"

echo
echo "📋 Step 2: Testing Login POST Endpoint"
echo "--------------------------------------"
echo "Getting CSRF token..."

# Get login page and extract CSRF token
LOGIN_PAGE=$(curl -s -c cookies.tmp "$BASE_URL/login")
CSRF_TOKEN=$(echo "$LOGIN_PAGE" | grep -o 'name="_token" value="[^"]*"' | cut -d'"' -f4 | head -1)

if [ -n "$CSRF_TOKEN" ]; then
    echo "✅ CSRF token obtained: ${CSRF_TOKEN:0:20}..."
    
    echo "Testing paramedis login POST:"
    curl -v -X POST \
        -H "Content-Type: application/x-www-form-urlencoded" \
        -H "Accept: text/html,application/xhtml+xml" \
        -b cookies.tmp -c cookies.tmp \
        -d "_token=$CSRF_TOKEN" \
        -d "identifier=naning" \
        -d "password=naning" \
        -d "role=paramedis" \
        "$BASE_URL/login" 2>&1 | head -30
else
    echo "❌ Failed to get CSRF token"
    echo "Login page content (first 500 chars):"
    echo "$LOGIN_PAGE" | head -c 500
fi

echo
echo "📋 Step 3: Testing Alternative Endpoints"
echo "----------------------------------------"
echo "Testing admin panel:"
curl -s -I "$BASE_URL/admin" | head -3

echo "Testing home page:"
curl -s -I "$BASE_URL/" | head -3

echo "Testing API endpoints:"
curl -s -I "$BASE_URL/api/health" | head -3

echo
echo "📋 Step 4: DNS and Network Analysis"
echo "-----------------------------------"
echo "DNS resolution:"
nslookup dokterkuklinik.com | head -10

echo "Ping test:"
ping -c 3 dokterkuklinik.com | head -5

echo
echo "📋 Step 5: SSL Certificate Check"
echo "--------------------------------"
echo "SSL certificate info:"
openssl s_client -connect dokterkuklinik.com:443 -servername dokterkuklinik.com < /dev/null 2>/dev/null | openssl x509 -noout -dates

echo
echo "📋 Step 6: Error Pattern Analysis"
echo "---------------------------------"
echo "Testing for common 500 error patterns..."

# Test different endpoints to see which ones return 500
ENDPOINTS=(
    "/login"
    "/admin"
    "/admin/login" 
    "/api/user"
    "/dashboard"
    "/paramedis"
    "/dokter"
)

echo "Endpoint status check:"
for endpoint in "${ENDPOINTS[@]}"; do
    status=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL$endpoint")
    echo "  $endpoint: $status"
done

echo
echo "📋 Step 7: Server Headers Analysis"
echo "----------------------------------"
echo "Server information from headers:"
curl -s -I "$BASE_URL/login" | grep -E "(Server|X-Powered-By|PHP|Laravel)"

echo
echo "📋 Step 8: Robots.txt and Public Files"
echo "--------------------------------------"
echo "Checking robots.txt:"
curl -s "$BASE_URL/robots.txt" | head -5

echo "Checking .env exposure (should be protected):"
curl -s -I "$BASE_URL/.env" | head -2

echo
echo "🎯 REMOTE ANALYSIS SUMMARY"
echo "=========================="
echo "✅ HTTP endpoint testing completed"
echo "🔍 Key findings to check:"
echo "   - Login page HTTP status"
echo "   - POST request response"
echo "   - Server headers and version"
echo "   - SSL certificate validity"
echo "   - DNS resolution issues"
echo
echo "💡 If login page returns 500, the issue is server-side"
echo "💡 If login page loads but POST fails, it's authentication specific"

# Cleanup
rm -f cookies.tmp

echo
echo "🏁 Remote analysis completed!"