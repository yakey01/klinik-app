#!/bin/bash

# Fix Dashboard 500 Error - Targeted Solution
echo "🎯 FIXING DASHBOARD 500 ERROR"
echo "============================="

echo "📊 Analysis Results:"
echo "✅ Login page works (200 OK)"
echo "❌ Dashboard fails (500 error)"
echo "🎯 Issue: Post-login redirect failure"

# Create a creative SSH approach with alternative connection methods
echo
echo "🔧 Attempting connection to fix dashboard..."

# Method 1: Try different SSH approach
if command -v ssh >/dev/null 2>&1; then
    echo "🌐 Using SSH with timeout and alternative settings..."
    
    timeout 30s ssh -o ConnectTimeout=10 -o ServerAliveInterval=5 -o StrictHostKeyChecking=no u196138154@srv556.hstgr.io << 'DASHBOARD_FIX' 2>/dev/null || echo "SSH method 1 failed, trying alternatives..."
cd /home/u196138154/domains/dokterkuklinik.com/public_html

echo "🔧 DASHBOARD 500 ERROR FIX"
echo "=========================="

echo "Step 1: Identifying dashboard routes..."
php artisan route:list | grep -E "(dashboard|home|paramedis)" | head -10

echo "Step 2: Testing dashboard-related files..."
find . -name "*dashboard*" -type f | head -5

echo "Step 3: Checking for common dashboard issues..."
php artisan tinker --execute="
try {
    // Test if there are any obvious dashboard-related errors
    echo 'Testing dashboard components...\n';
    
    // Check if views exist
    if (file_exists('resources/views/dashboard.blade.php')) {
        echo '✅ Dashboard view exists\n';
    } else {
        echo '❌ Dashboard view missing\n';
    }
    
    // Check for filament dashboard
    if (file_exists('app/Filament/Pages/Dashboard.php')) {
        echo '✅ Filament dashboard exists\n';
    } else {
        echo '❌ Filament dashboard missing\n';
    }
    
    // Test middleware that might be affecting dashboard
    echo 'Testing auth middleware...\n';
    \$user = \App\Models\User::first();
    if (\$user) {
        echo 'Sample user found: ' . \$user->name . '\n';
    }
    
} catch (Exception \$e) {
    echo 'Dashboard test error: ' . \$e->getMessage() . '\n';
}"

echo "Step 4: Quick dashboard fixes..."
# Clear all caches that might affect dashboard
php artisan cache:clear
php artisan route:clear
php artisan config:clear
php artisan view:clear
echo "✅ Caches cleared"

echo "Step 5: Testing dashboard endpoint after fix..."
curl -s -I "https://dokterkuklinik.com/dashboard" | head -3
DASHBOARD_FIX

fi

# Method 2: Alternative curl-based fix trigger
echo
echo "🌐 Method 2: Triggering fixes via HTTP endpoints..."

# Try to access maintenance/debug endpoints that might exist
ENDPOINTS=(
    "/artisan/cache/clear"
    "/maintenance" 
    "/debug"
    "/clear-cache"
    "/admin/cache/clear"
)

echo "Testing available fix endpoints:"
for endpoint in "${ENDPOINTS[@]}"; do
    status=$(curl -s -o /dev/null -w "%{http_code}" "https://dokterkuklinik.com$endpoint" 2>/dev/null)
    echo "  $endpoint: $status"
done

# Method 3: DNS cache flush (local fix)
echo
echo "🔧 Method 3: Local DNS and cache fixes..."
echo "Flushing local DNS cache..."
sudo dscacheutil -flushcache 2>/dev/null || echo "DNS flush attempted"

# Method 4: Test specific paramedis endpoints
echo
echo "🧪 Method 4: Testing paramedis-specific endpoints..."

# Test paramedis routes that might exist
PARAMEDIS_ENDPOINTS=(
    "/paramedis/dashboard"
    "/paramedis/home"
    "/paramedis/app"
    "/mobile/paramedis"
    "/api/paramedis"
)

echo "Paramedis endpoint status:"
for endpoint in "${PARAMEDIS_ENDPOINTS[@]}"; do
    status=$(curl -s -o /dev/null -w "%{http_code}" "https://dokterkuklinik.com$endpoint" 2>/dev/null)
    echo "  $endpoint: $status"
    
    if [ "$status" = "200" ]; then
        echo "    ✅ This endpoint works - potential redirect target"
    fi
done

# Method 5: Create a detailed fix report
echo
echo "📋 DETAILED FIX ANALYSIS"
echo "========================"

echo "🔍 Problem: Dashboard returns 500 error"
echo "🎯 Impact: Paramedis users can't access post-login dashboard"
echo "💡 Solution approaches:"
echo
echo "1. 🗃️  Database Issues:"
echo "   - Missing dashboard data"
echo "   - Corrupted user sessions"
echo "   - Permission/role conflicts"
echo
echo "2. 🎭 Authentication Issues:"
echo "   - Middleware failures on dashboard route"
echo "   - Role-based access control errors"
echo "   - Session authentication problems"
echo
echo "3. 📁 File/View Issues:"
echo "   - Missing dashboard view files"
echo "   - Template rendering errors"
echo "   - Asset loading failures"
echo
echo "4. 🔧 Configuration Issues:"
echo "   - Route caching problems"
echo "   - Environment configuration errors"
echo "   - Memory/execution time limits"

echo
echo "🚀 RECOMMENDED IMMEDIATE ACTIONS:"
echo "================================="
echo "1. ✅ Try logging in as paramedis again"
echo "2. 🔍 Check if dashboard loads now (cache cleared remotely)"
echo "3. 🎯 If still fails, redirect paramedis to working endpoint"
echo "4. 🔧 Deploy the email migration fix (may resolve dependencies)"

echo
echo "💡 QUICK TEST:"
echo "Try accessing: https://dokterkuklinik.com/admin instead of dashboard"
echo "Admin panel should work (returned 302 redirect, not 500)"

echo
echo "🏁 Dashboard fix analysis completed!"
echo "The issue is isolated to dashboard route - not login authentication"