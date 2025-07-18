#!/bin/bash
set -e

echo "🚨 EMERGENCY DIAGNOSTIC SCRIPT"
echo "================================"
echo "Performing rapid diagnosis of Laravel application errors"
echo ""

# Color functions
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log() { echo -e "${BLUE}[$(date '+%H:%M:%S')]${NC} $1"; }
error() { echo -e "${RED}❌ ERROR:${NC} $1"; }
success() { echo -e "${GREEN}✅ SUCCESS:${NC} $1"; }
warning() { echo -e "${YELLOW}⚠️  WARNING:${NC} $1"; }

log "🔍 Checking Laravel application status..."

# 1. Check if application can bootstrap
echo ""
echo "=== LARAVEL BOOTSTRAP TEST ==="
php -r "
try {
    require 'vendor/autoload.php';
    \$app = require 'bootstrap/app.php';
    echo '✅ Laravel Bootstrap: SUCCESS\n';
} catch (Exception \$e) {
    echo '❌ Laravel Bootstrap: ERROR\n';
    echo 'Error: ' . \$e->getMessage() . '\n';
    echo 'File: ' . \$e->getFile() . ':' . \$e->getLine() . '\n';
    echo 'Trace: ' . \$e->getTraceAsString() . '\n';
}
"

# 2. Check Laravel log for recent errors
echo ""
echo "=== LATEST ERROR FROM LARAVEL LOG ==="
if [ -f "storage/logs/laravel.log" ]; then
    echo "📋 Last 20 lines of Laravel log:"
    tail -20 storage/logs/laravel.log
    echo ""
    echo "🔍 Latest ERROR entries:"
    grep -E "(ERROR|CRITICAL|EMERGENCY)" storage/logs/laravel.log | tail -3 || echo "No recent errors found"
else
    warning "Laravel log file not found"
fi

# 3. Check environment configuration
echo ""
echo "=== ENVIRONMENT CONFIGURATION ==="
echo "APP_ENV: $(grep APP_ENV .env | cut -d'=' -f2)"
echo "APP_DEBUG: $(grep APP_DEBUG .env | cut -d'=' -f2)"
echo "APP_KEY exists: $(grep -q 'APP_KEY=base64:' .env && echo 'YES' || echo 'NO')"
echo "Database configured: $(grep -q 'DB_DATABASE' .env && echo 'YES' || echo 'NO')"

# 4. Test database connection
echo ""
echo "=== DATABASE CONNECTION TEST ==="
php -r "
try {
    \$pdo = new PDO('mysql:host=localhost;dbname=u454362045_u45436245_kli', 'u454362045_u45436245_kli', 'KlinikApp2025!');
    \$stmt = \$pdo->query('SELECT COUNT(*) FROM users');
    \$count = \$stmt->fetchColumn();
    echo '✅ Database Connection: SUCCESS (' . \$count . ' users)\n';
    
    // Test for username column
    \$stmt = \$pdo->query('DESCRIBE users');
    \$columns = \$stmt->fetchAll();
    \$hasUsername = false;
    foreach (\$columns as \$column) {
        if (\$column['Field'] === 'username') {
            \$hasUsername = true;
            break;
        }
    }
    echo '✅ Username column exists: ' . (\$hasUsername ? 'YES' : 'NO') . '\n';
    
} catch (Exception \$e) {
    echo '❌ Database Connection: ERROR - ' . \$e->getMessage() . '\n';
}
"

# 5. Check specific Laravel routes
echo ""
echo "=== ROUTE TESTING ==="
php -r "
try {
    require 'vendor/autoload.php';
    \$app = require 'bootstrap/app.php';
    
    // Test home route
    \$request = Illuminate\Http\Request::create('/', 'GET');
    \$response = \$app->handle(\$request);
    echo '✅ Home Route: SUCCESS (Status: ' . \$response->getStatusCode() . ')\n';
    
    // Test login route
    \$request = Illuminate\Http\Request::create('/admin', 'GET');
    \$response = \$app->handle(\$request);
    echo '✅ Admin Route: SUCCESS (Status: ' . \$response->getStatusCode() . ')\n';
    
} catch (Exception \$e) {
    echo '❌ Route Test: ERROR - ' . \$e->getMessage() . '\n';
    echo 'File: ' . \$e->getFile() . ':' . \$e->getLine() . '\n';
}
"

# 6. Check file permissions
echo ""
echo "=== FILE PERMISSIONS ==="
echo "Storage writable: $([ -w storage ] && echo 'YES' || echo 'NO')"
echo "Bootstrap cache writable: $([ -w bootstrap/cache ] && echo 'YES' || echo 'NO')"
echo "Public directory exists: $([ -d public ] && echo 'YES' || echo 'NO')"

# 7. Check vendor autoload
echo ""
echo "=== VENDOR AUTOLOAD ==="
php -r "
try {
    require 'vendor/autoload.php';
    echo '✅ Vendor Autoload: SUCCESS\n';
    
    // Check specific problematic classes
    if (class_exists('Composer\InstalledVersions')) {
        echo '✅ Composer\InstalledVersions: EXISTS\n';
    } else {
        echo '❌ Composer\InstalledVersions: MISSING\n';
    }
    
} catch (Exception \$e) {
    echo '❌ Vendor Autoload: ERROR - ' . \$e->getMessage() . '\n';
}
"

# 8. Memory and execution limits
echo ""
echo "=== PHP CONFIGURATION ==="
echo "PHP Memory Limit: $(php -r 'echo ini_get("memory_limit");')"
echo "PHP Max Execution Time: $(php -r 'echo ini_get("max_execution_time");')"
echo "PHP Version: $(php -v | head -1)"

# 9. Recent web server error logs (if accessible)
echo ""
echo "=== WEB SERVER ERROR LOG ==="
if [ -f "/var/log/apache2/error.log" ]; then
    echo "📋 Recent Apache errors:"
    tail -5 /var/log/apache2/error.log 2>/dev/null || echo "Cannot access Apache log"
elif [ -f "/var/log/nginx/error.log" ]; then
    echo "📋 Recent Nginx errors:"
    tail -5 /var/log/nginx/error.log 2>/dev/null || echo "Cannot access Nginx log"
else
    warning "Web server error logs not accessible"
fi

# 10. Quick fix suggestions
echo ""
echo "=== QUICK FIX ACTIONS ==="
log "🔧 Applying quick fixes..."

# Clear all caches
rm -rf bootstrap/cache/*
rm -rf storage/framework/cache/*
rm -rf storage/framework/views/*
success "Caches cleared"

# Ensure proper permissions
chmod -R 777 storage/
chmod -R 755 bootstrap/cache/
success "Permissions fixed"

# Test after quick fixes
echo ""
echo "=== POST-FIX TEST ==="
php -r "
try {
    require 'vendor/autoload.php';
    \$app = require 'bootstrap/app.php';
    echo '✅ Post-Fix Test: SUCCESS\n';
} catch (Exception \$e) {
    echo '❌ Post-Fix Test: ERROR - ' . \$e->getMessage() . '\n';
    echo 'File: ' . \$e->getFile() . ':' . \$e->getLine() . '\n';
}
"

echo ""
echo "================================"
success "🎯 EMERGENCY DIAGNOSTIC COMPLETE"
echo "================================"
echo ""
echo "📋 SUMMARY:"
echo "   🌐 Website: https://dokterkuklinik.com"
echo "   🔍 Check Laravel logs for detailed error information"
echo "   🔧 Quick fixes applied (cache clearing, permissions)"
echo ""
echo "🚨 If issues persist, the error is likely in:"
echo "   1. Service provider configuration"
echo "   2. Database connection issues"
echo "   3. Missing or corrupted vendor files"
echo "   4. Environment configuration problems"
echo "================================"