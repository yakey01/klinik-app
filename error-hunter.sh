#!/bin/bash
set -e

echo "🎯 ERROR HUNTER - Finding the Real Error"
echo "========================================="
echo "This script will hunt down the actual error causing the stack trace"
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
info() { echo -e "${YELLOW}ℹ️  INFO:${NC} $1"; }

log "🔍 Hunting for the real error..."

# 1. Enable Laravel debug mode temporarily
log "🔧 Enabling debug mode for detailed error reporting..."
cp .env .env.backup
sed -i 's/APP_DEBUG=false/APP_DEBUG=true/' .env
sed -i 's/APP_ENV=production/APP_ENV=local/' .env
success "Debug mode enabled"

# 2. Clear ALL caches to force fresh error display
log "🧹 Clearing all caches for fresh error display..."
rm -rf bootstrap/cache/*
rm -rf storage/framework/cache/*
rm -rf storage/framework/views/*
rm -rf storage/framework/sessions/*
rm -rf storage/logs/*
success "All caches cleared"

# 3. Test direct PHP execution to catch the error
log "🧪 Testing direct PHP execution to catch error..."
echo ""
echo "=== DIRECT PHP TEST ==="
php -r "
set_error_handler(function(\$severity, \$message, \$file, \$line) {
    echo \"PHP Error: \$message in \$file:\$line\n\";
});

try {
    require 'vendor/autoload.php';
    echo '✅ Autoload: SUCCESS\n';
    
    \$app = require 'bootstrap/app.php';
    echo '✅ Bootstrap: SUCCESS\n';
    
    // Test request handling
    \$request = Illuminate\Http\Request::create('/', 'GET');
    \$response = \$app->handle(\$request);
    echo '✅ Request Handling: SUCCESS (Status: ' . \$response->getStatusCode() . ')\n';
    
} catch (Error \$e) {
    echo '❌ PHP Error: ' . \$e->getMessage() . '\n';
    echo 'File: ' . \$e->getFile() . ':' . \$e->getLine() . '\n';
    echo 'Trace: ' . \$e->getTraceAsString() . '\n';
} catch (Exception \$e) {
    echo '❌ Exception: ' . \$e->getMessage() . '\n';
    echo 'File: ' . \$e->getFile() . ':' . \$e->getLine() . '\n';
    echo 'Trace: ' . \$e->getTraceAsString() . '\n';
}
" 2>&1

# 4. Test with curl to see what the web server returns
log "🌐 Testing with curl to see actual web response..."
echo ""
echo "=== CURL TEST ==="
curl -s -v https://dokterkuklinik.com 2>&1 | head -20 || echo "Curl test failed"

# 5. Check Laravel's error handling configuration
log "🔍 Checking Laravel error handling configuration..."
echo ""
echo "=== ERROR HANDLING CONFIG ==="
php -r "
require 'vendor/autoload.php';
\$app = require 'bootstrap/app.php';

// Check if error handling is properly configured
\$handler = \$app->make('Illuminate\Contracts\Debug\ExceptionHandler');
echo 'Exception Handler: ' . get_class(\$handler) . '\n';

// Check app configuration
echo 'App Debug: ' . (\$app->hasDebugModeEnabled() ? 'true' : 'false') . '\n';
echo 'App Environment: ' . \$app->environment() . '\n';
"

# 6. Check if specific Filament issues
log "🛡️  Checking for Filament-specific issues..."
echo ""
echo "=== FILAMENT DIAGNOSTICS ==="

# Check for Filament service providers
echo "Checking service providers..."
find . -name "*.php" -path "*/Providers/*" -exec grep -l "Filament" {} \; | head -5

# Check for remaining Shield references
echo "Checking for remaining Shield references..."
find . -name "*.php" -exec grep -l "FilamentShield\|filament-shield" {} \; 2>/dev/null | head -5 || echo "No Shield references found"

# 7. Check database connection issues
log "🗄️  Testing database connection..."
echo ""
echo "=== DATABASE CONNECTION ==="
php -r "
try {
    \$pdo = new PDO('mysql:host=localhost;dbname=u454362045_u45436245_kli', 'u454362045_u45436245_kli', 'KlinikApp2025!');
    echo '✅ PDO Connection: SUCCESS\n';
    
    // Test through Laravel
    require 'vendor/autoload.php';
    \$app = require 'bootstrap/app.php';
    \$db = \$app->make('db');
    \$users = \$db->table('users')->count();
    echo '✅ Laravel DB: SUCCESS (' . \$users . ' users)\n';
    
} catch (Exception \$e) {
    echo '❌ Database Error: ' . \$e->getMessage() . '\n';
}
"

# 8. Check for missing critical files
log "📁 Checking for missing critical files..."
echo ""
echo "=== CRITICAL FILES CHECK ==="
critical_files=(
    "public/index.php"
    "bootstrap/app.php"
    "vendor/autoload.php"
    "artisan"
    ".env"
)

for file in "${critical_files[@]}"; do
    if [ -f "$file" ]; then
        echo "✅ $file exists"
    else
        echo "❌ $file MISSING"
    fi
done

# 9. Check PHP configuration that might cause issues
log "⚙️  Checking PHP configuration..."
echo ""
echo "=== PHP CONFIGURATION ==="
php -r "
echo 'Memory Limit: ' . ini_get('memory_limit') . '\n';
echo 'Max Execution Time: ' . ini_get('max_execution_time') . '\n';
echo 'Max Input Vars: ' . ini_get('max_input_vars') . '\n';
echo 'Upload Max Size: ' . ini_get('upload_max_filesize') . '\n';
echo 'Post Max Size: ' . ini_get('post_max_size') . '\n';
echo 'Display Errors: ' . ini_get('display_errors') . '\n';
echo 'Error Reporting: ' . ini_get('error_reporting') . '\n';
"

# 10. Create a minimal test route to isolate the issue
log "🛤️  Creating minimal test route..."
echo ""
echo "=== MINIMAL ROUTE TEST ==="
php -r "
require 'vendor/autoload.php';
\$app = require 'bootstrap/app.php';

// Test basic route
\$router = \$app->make('router');
\$router->get('/test', function() {
    return 'Test route works!';
});

try {
    \$request = Illuminate\Http\Request::create('/test', 'GET');
    \$response = \$app->handle(\$request);
    echo '✅ Test Route: SUCCESS - ' . \$response->getContent() . '\n';
} catch (Exception \$e) {
    echo '❌ Test Route: ERROR - ' . \$e->getMessage() . '\n';
}
"

# 11. Final attempt: Force display errors
log "🚨 Final attempt: Force display errors..."
echo ""
echo "=== FORCE ERROR DISPLAY ==="
php -d display_errors=1 -d error_reporting=E_ALL -r "
require 'vendor/autoload.php';
\$app = require 'bootstrap/app.php';
\$request = Illuminate\Http\Request::create('/', 'GET');
\$response = \$app->handle(\$request);
echo 'Response Status: ' . \$response->getStatusCode() . '\n';
echo 'Response Headers: ' . json_encode(\$response->headers->all()) . '\n';
echo 'Response Content Length: ' . strlen(\$response->getContent()) . '\n';
echo 'Response Preview: ' . substr(\$response->getContent(), 0, 200) . '...\n';
"

echo ""
echo "========================================="
success "🎯 ERROR HUNTING COMPLETE"
echo "========================================="
echo ""
echo "📋 ANALYSIS SUMMARY:"
echo "   🔍 Check the output above for the actual error message"
echo "   🌐 Debug mode enabled for detailed error reporting"
echo "   🧹 All caches cleared for fresh error display"
echo "   🚨 If still no clear error, check web server error logs"
echo ""
echo "🔧 NEXT STEPS:"
echo "   1. Visit https://dokterkuklinik.com to see detailed error"
echo "   2. Check browser developer console for JS errors"
echo "   3. Review the specific error message now displayed"
echo "========================================="