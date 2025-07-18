#!/bin/bash
set -e

echo "🕵️ RAW ERROR DETECTIVE - Bypassing Laravel Error Handling"
echo "========================================================="
echo "This script will bypass Laravel's error handling to catch the raw error"
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

# 1. First, let's see what's in the first few lines of the log (before the stack trace)
log "🔍 Analyzing the full Laravel log for the original error..."
echo ""
echo "=== FULL LOG ANALYSIS ==="
if [ -f "storage/logs/laravel.log" ]; then
    # Get the last complete error entry (look for the actual error message before stack trace)
    info "Looking for the actual error message in the log..."
    grep -B 20 -A 5 "Stack trace:" storage/logs/laravel.log | tail -30 || echo "No 'Stack trace:' found"
    
    echo ""
    info "Looking for exception messages..."
    grep -E "(Exception|Error|Fatal)" storage/logs/laravel.log | tail -5 || echo "No exception messages found"
    
    echo ""
    info "Looking for error patterns..."
    grep -E "(\[error\]|\[critical\]|\[emergency\])" storage/logs/laravel.log | tail -5 || echo "No error patterns found"
    
    echo ""
    info "Getting the complete last error entry..."
    # Find the last timestamp and get everything after it
    LAST_TIMESTAMP=$(grep -o '\[20[0-9][0-9]-[0-9][0-9]-[0-9][0-9] [0-9][0-9]:[0-9][0-9]:[0-9][0-9]\]' storage/logs/laravel.log | tail -1)
    if [ -n "$LAST_TIMESTAMP" ]; then
        info "Last error timestamp: $LAST_TIMESTAMP"
        grep -A 100 "$LAST_TIMESTAMP" storage/logs/laravel.log | head -50
    fi
else
    error "Laravel log file not found"
fi

# 2. Create a simple test PHP script to catch the raw error
log "🧪 Creating raw PHP error detection script..."
cat > test_error.php << 'EOPHP'
<?php
// Raw error detection - bypass Laravel's error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Custom error handler to catch everything
set_error_handler(function($severity, $message, $file, $line) {
    echo "🚨 RAW PHP ERROR CAUGHT:\n";
    echo "Severity: $severity\n";
    echo "Message: $message\n";
    echo "File: $file\n";
    echo "Line: $line\n";
    echo "===================\n";
    return false; // Let PHP handle it too
});

// Custom exception handler
set_exception_handler(function($exception) {
    echo "🚨 RAW EXCEPTION CAUGHT:\n";
    echo "Type: " . get_class($exception) . "\n";
    echo "Message: " . $exception->getMessage() . "\n";
    echo "File: " . $exception->getFile() . "\n";
    echo "Line: " . $exception->getLine() . "\n";
    echo "Previous: " . ($exception->getPrevious() ? $exception->getPrevious()->getMessage() : 'None') . "\n";
    echo "===================\n";
});

try {
    echo "🔍 Starting raw error detection...\n";
    
    // Step 1: Test autoload
    echo "Step 1: Testing autoload...\n";
    require 'vendor/autoload.php';
    echo "✅ Autoload successful\n";
    
    // Step 2: Test bootstrap
    echo "Step 2: Testing bootstrap...\n";
    $app = require 'bootstrap/app.php';
    echo "✅ Bootstrap successful\n";
    
    // Step 3: Test specific Laravel components that might fail
    echo "Step 3: Testing Laravel components...\n";
    
    // Test config
    echo "Testing config...\n";
    $config = $app->make('config');
    echo "✅ Config successful\n";
    
    // Test database
    echo "Testing database...\n";
    $db = $app->make('db');
    echo "✅ Database service successful\n";
    
    // Test actual DB connection
    echo "Testing DB connection...\n";
    $db->connection()->getPdo();
    echo "✅ DB connection successful\n";
    
    // Test view
    echo "Testing view...\n";
    $view = $app->make('view');
    echo "✅ View service successful\n";
    
    // Test route
    echo "Testing route...\n";
    $router = $app->make('router');
    echo "✅ Router service successful\n";
    
    // Step 4: Test request handling
    echo "Step 4: Testing request handling...\n";
    $request = Illuminate\Http\Request::create('/', 'GET');
    echo "✅ Request created\n";
    
    // This is where the error likely occurs
    echo "Testing app->handle()...\n";
    $response = $app->handle($request);
    echo "✅ Request handled successfully\n";
    echo "Response status: " . $response->getStatusCode() . "\n";
    
} catch (Error $e) {
    echo "🚨 FATAL ERROR CAUGHT:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
} catch (Exception $e) {
    echo "🚨 EXCEPTION CAUGHT:\n";
    echo "Type: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    if ($e->getPrevious()) {
        echo "Previous Exception: " . $e->getPrevious()->getMessage() . "\n";
        echo "Previous File: " . $e->getPrevious()->getFile() . "\n";
        echo "Previous Line: " . $e->getPrevious()->getLine() . "\n";
    }
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>
EOPHP

# 3. Run the raw error detection
log "🚨 Running raw error detection..."
echo ""
echo "=== RAW ERROR DETECTION ==="
php test_error.php 2>&1

# 4. Check if there are any issues with service providers
log "🔍 Checking service providers for issues..."
echo ""
echo "=== SERVICE PROVIDER ANALYSIS ==="
find app/Providers -name "*.php" -exec echo "=== {} ===" \; -exec cat {} \; 2>/dev/null || echo "No providers found"

# 5. Check bootstrap files
log "🔍 Checking bootstrap files..."
echo ""
echo "=== BOOTSTRAP FILES ==="
if [ -f "bootstrap/app.php" ]; then
    echo "=== bootstrap/app.php ==="
    cat bootstrap/app.php | head -30
else
    error "bootstrap/app.php not found"
fi

if [ -f "bootstrap/providers.php" ]; then
    echo "=== bootstrap/providers.php ==="
    cat bootstrap/providers.php
else
    info "bootstrap/providers.php not found (normal for Laravel 11)"
fi

# 6. Check config files for issues
log "🔍 Checking config files..."
echo ""
echo "=== CONFIG FILES ==="
for config_file in config/*.php; do
    if [ -f "$config_file" ]; then
        echo "=== $config_file ==="
        # Check for syntax errors
        php -l "$config_file" || echo "❌ Syntax error in $config_file"
    fi
done

# 7. Test with minimal Laravel setup
log "🔍 Testing minimal Laravel setup..."
echo ""
echo "=== MINIMAL LARAVEL TEST ==="
cat > minimal_test.php << 'EOPHP'
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require 'vendor/autoload.php';
    
    // Create minimal app
    $app = new Illuminate\Foundation\Application(
        dirname(__DIR__)
    );
    
    echo "✅ Minimal app created\n";
    
    // Test basic services
    $app->singleton('config', function() {
        return new Illuminate\Config\Repository();
    });
    
    echo "✅ Config service registered\n";
    
} catch (Exception $e) {
    echo "❌ Minimal test failed: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
?>
EOPHP

php minimal_test.php 2>&1

# 8. Clean up test files
rm -f test_error.php minimal_test.php

echo ""
echo "========================================================="
success "🕵️ RAW ERROR DETECTIVE COMPLETE"
echo "========================================================="
echo ""
echo "📋 ANALYSIS SUMMARY:"
echo "   🔍 Raw error detection completed"
echo "   🚨 Check output above for the actual error message"
echo "   🔧 If no clear error found, the issue is likely in routing or Filament"
echo ""
echo "🎯 NEXT STEPS:"
echo "   1. Review the raw error detection output above"
echo "   2. Look for any 'RAW ERROR CAUGHT' or 'EXCEPTION CAUGHT' messages"
echo "   3. Check service provider and config file issues"
echo "   4. If still unclear, the error is likely in route registration"
echo "========================================================="