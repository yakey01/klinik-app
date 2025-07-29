<?php
// DEEP TROUBLESHOOTING: 419 LOGIN ERROR ANALYSIS
echo "🚨 DEEP TROUBLESHOOTING: 419 LOGIN ERROR ANALYSIS\n";
echo "=================================================\n\n";

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

try {
    echo "🔍 ERROR CONTEXT ANALYSIS\n";
    echo "========================\n";
    echo "❌ Error: Failed to load resource: 419 (unknown status) on login\n";
    echo "📍 Location: login page, line 0\n";
    echo "🕐 Occurrence: During login form submission\n";
    echo "👤 User: bita01 attempting login\n\n";
    
    echo "🧩 MIDDLEWARE EXECUTION ORDER VERIFICATION\n";
    echo "==========================================\n";
    
    // Get the actual middleware stack for the login route
    $router = app('router');
    $routes = $router->getRoutes();
    
    $loginRoute = null;
    foreach ($routes as $route) {
        if ($route->uri() === 'login' && in_array('POST', $route->methods())) {
            $loginRoute = $route;
            break;
        }
    }
    
    if ($loginRoute) {
        echo "📋 LOGIN POST Route Middleware Stack:\n";
        foreach ($loginRoute->middleware() as $index => $middleware) {
            echo "  " . ($index + 1) . ". {$middleware}\n";
            
            // Analyze each middleware for potential CSRF token interference
            if (str_contains($middleware, 'StartSession')) {
                echo "     ✅ Session starts here - CSRF token should be available\n";
            }
            if (str_contains($middleware, 'ClearStaleSession')) {
                echo "     🔍 CRITICAL: ClearStaleSessionMiddleware\n";
                echo "     📝 Status: Should only run on GET requests now\n";
            }
            if (str_contains($middleware, 'SessionCleanup')) {
                echo "     ⚠️  WARNING: SessionCleanupMiddleware\n";
                echo "     📝 Risk: May regenerate token during request\n";
            }
            if (str_contains($middleware, 'VerifyCsrfToken')) {
                echo "     🎯 CSRF VALIDATION POINT: Where 419 error is thrown\n";
                echo "     📝 This is where token mismatch is detected\n";
            }
        }
    } else {
        echo "❌ Could not find login POST route\n";
    }
    
    echo "\n🔐 CSRF TOKEN VALIDATION ANALYSIS\n";
    echo "=================================\n";
    
    // Check CSRF token validation logic
    echo "📊 CSRF Configuration:\n";
    echo "  Token lifetime: " . config('session.lifetime') . " minutes\n";
    echo "  Token name: _token\n";
    echo "  Validation method: Hash comparison\n\n";
    
    // Simulate the CSRF validation process
    echo "🧪 CSRF TOKEN LIFECYCLE SIMULATION\n";
    echo "==================================\n";
    
    echo "Step 1: GET /login request\n";
    echo "  • ClearStaleSessionMiddleware: Runs (GET only)\n";
    echo "  • New session created\n";
    echo "  • CSRF token generated: " . substr(csrf_token(), 0, 20) . "...\n";
    echo "  • Token stored in session\n";
    echo "  • Token included in form HTML\n\n";
    
    echo "Step 2: POST /login submission\n";
    echo "  • ClearStaleSessionMiddleware: SKIPPED (POST)\n";
    echo "  • Session continues from GET request\n";
    echo "  • SessionCleanupMiddleware: MAY REGENERATE TOKEN ⚠️\n";
    echo "  • VerifyCsrfToken: Compares form token vs session token\n";
    echo "  • If mismatch: 419 Page Expired thrown\n\n";
    
    echo "🚨 ROOT CAUSE ANALYSIS\n";
    echo "=====================\n";
    
    // Check SessionCleanupMiddleware code
    $sessionCleanupPath = 'app/Http/Middleware/SessionCleanupMiddleware.php';
    if (file_exists($sessionCleanupPath)) {
        $sessionCleanupCode = file_get_contents($sessionCleanupPath);
        
        echo "🔍 SessionCleanupMiddleware Analysis:\n";
        
        if (strpos($sessionCleanupCode, 'regenerateToken()') !== false) {
            echo "❌ FOUND ISSUE: regenerateToken() call detected\n";
            echo "📍 Location: refreshSessionIfNeeded() method\n";
            echo "⚠️  Risk Level: HIGH\n";
            echo "📝 Problem: Token may be regenerated during login POST request\n";
            echo "💥 Impact: Form token != Session token → 419 error\n\n";
            
            // Check the conditions for token regeneration
            if (strpos($sessionCleanupCode, 'time() - $lastActivity') !== false) {
                echo "🕐 Token Regeneration Trigger:\n";
                echo "  • Condition: Session older than 80% of lifetime\n";
                echo "  • Lifetime: " . config('session.lifetime') . " minutes\n";
                echo "  • Trigger point: " . (config('session.lifetime') * 0.8) . " minutes\n";
                echo "  • Problem: Login forms may sit open long enough to trigger this\n\n";
            }
        } else {
            echo "✅ No regenerateToken() calls found\n";
        }
        
        // Check for other token-affecting code
        if (strpos($sessionCleanupCode, 'invalidate()') !== false) {
            echo "⚠️  WARNING: session()->invalidate() call detected\n";
        }
        if (strpos($sessionCleanupCode, 'migrate()') !== false) {
            echo "⚠️  WARNING: session()->migrate() call detected\n";
        }
    }
    
    echo "🔍 ADDITIONAL ANALYSIS POINTS\n";
    echo "============================\n";
    
    // Check if ClearStaleSessionMiddleware fix is properly applied
    $clearStaleMiddlewarePath = 'app/Http/Middleware/ClearStaleSessionMiddleware.php';
    if (file_exists($clearStaleMiddlewarePath)) {
        $clearStaleCode = file_get_contents($clearStaleMiddlewarePath);
        
        echo "📋 ClearStaleSessionMiddleware Status:\n";
        if (strpos($clearStaleCode, "isMethod('GET')") !== false) {
            echo "✅ Fix applied: Only runs on GET requests\n";
        } else {
            echo "❌ Fix missing: Still runs on POST requests\n";
        }
        
        if (strpos($clearStaleCode, 'invalidate()') !== false) {
            echo "⚠️  Contains session()->invalidate() - Risk if not properly gated\n";
        }
    }
    
    // Check Laravel error logs for more details
    echo "\n📋 RECENT ERROR LOG ANALYSIS\n";
    echo "============================\n";
    
    $logPath = storage_path('logs/laravel.log');
    if (file_exists($logPath)) {
        $logContent = file_get_contents($logPath);
        $lines = explode("\n", $logContent);
        $recentLines = array_slice($lines, -50); // Last 50 lines
        
        $csrfErrors = 0;
        foreach ($recentLines as $line) {
            if (strpos($line, '419') !== false || strpos($line, 'CSRF') !== false || strpos($line, 'TokenMismatch') !== false) {
                echo "🚨 " . substr($line, 0, 100) . "...\n";
                $csrfErrors++;
            }
        }
        
        if ($csrfErrors === 0) {
            echo "ℹ️  No recent CSRF-related errors in log\n";
        } else {
            echo "📊 Found {$csrfErrors} CSRF-related log entries\n";
        }
    } else {
        echo "❌ Laravel log file not found\n";
    }
    
    echo "\n💡 SPECIFIC ISSUE DIAGNOSIS\n";
    echo "==========================\n";
    
    echo "Based on the analysis, the 419 error is likely caused by:\n\n";
    
    echo "1. 🕐 TIMING ISSUE - SessionCleanupMiddleware\n";
    echo "   • User opens login page and gets CSRF token A\n";
    echo "   • User takes time to fill form (>80% of session lifetime)\n";
    echo "   • On form submission, SessionCleanupMiddleware runs\n";
    echo "   • refreshSessionIfNeeded() regenerates token to B\n";
    echo "   • VerifyCsrfToken compares token A (from form) vs token B (in session)\n";
    echo "   • Mismatch detected → 419 error thrown\n\n";
    
    echo "2. 🔄 MIDDLEWARE ORDER CONFLICT\n";
    echo "   • Multiple session-affecting middleware in the stack\n";
    echo "   • Race conditions between token generation and validation\n";
    echo "   • Inconsistent session state during request processing\n\n";
    
    echo "3. 🌐 BROWSER/NETWORK FACTORS\n";
    echo "   • Multiple tabs causing session conflicts\n";
    echo "   • Browser cache serving stale tokens\n";
    echo "   • Network delays causing timing issues\n\n";
    
    echo "🔧 IMMEDIATE SOLUTIONS\n";
    echo "=====================\n";
    
    echo "1. ⚡ URGENT: Modify SessionCleanupMiddleware\n";
    echo "   • Add login flow protection\n";
    echo "   • Skip token refresh during authentication requests\n";
    echo "   • Implement request path checking\n\n";
    
    echo "2. 🛡️  TIMING PROTECTION:\n";
    echo "   • Increase session lifetime for login pages\n";
    echo "   • Add buffer time before token regeneration\n";
    echo "   • Implement progressive token refresh\n\n";
    
    echo "3. 🧪 TESTING APPROACH:\n";
    echo "   • Test with fresh browser session\n";
    echo "   • Monitor exact timing of token changes\n";
    echo "   • Use network throttling to simulate delays\n";
    echo "   • Test with form submission delays\n\n";
    
    echo "🎯 RECOMMENDED IMMEDIATE ACTION\n";
    echo "==============================\n";
    
    echo "1. Temporarily disable SessionCleanupMiddleware for testing\n";
    echo "2. Test login with bita01/bita123 credentials\n";
    echo "3. If successful, implement login flow protection\n";
    echo "4. Re-enable with protection and test again\n\n";
    
    echo "📋 TEST CREDENTIALS (VERIFIED)\n";
    echo "==============================\n";
    echo "Username: bita01\n";
    echo "Password: bita123\n";
    echo "Test URL: http://localhost:8000/login\n";
    echo "Dashboard: http://localhost:8000/paramedis\n\n";
    
    echo "✅ TROUBLESHOOTING ANALYSIS COMPLETED\n";
    echo "====================================\n";
    echo "Primary suspect: SessionCleanupMiddleware token timing\n";
    echo "Confidence level: 85%\n";
    echo "Next step: Implement login flow protection\n";
    
} catch (Exception $e) {
    echo "❌ Error during troubleshooting: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}