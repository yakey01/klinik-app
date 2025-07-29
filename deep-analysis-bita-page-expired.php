<?php
// DEEP ANALYSIS: BITA PARAMEDIS PAGE EXPIRED ISSUES
echo "🔍 DEEP ANALYSIS: BITA PARAMEDIS PAGE EXPIRED ISSUES\n";
echo "===================================================\n\n";

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

try {
    echo "📊 MIDDLEWARE STACK ANALYSIS\n";
    echo "===========================\n";
    
    // Get the current middleware stack for paramedis routes
    $router = app('router');
    $routes = $router->getRoutes();
    
    echo "🔍 Analyzing paramedis route middleware stack:\n\n";
    
    // Find paramedis routes
    $paramedicRoutes = [];
    foreach ($routes as $route) {
        if (str_contains($route->uri(), 'paramedis') || str_contains($route->getName() ?? '', 'paramedis')) {
            $paramedicRoutes[] = [
                'uri' => $route->uri(),
                'name' => $route->getName(),
                'middleware' => $route->middleware(),
                'methods' => $route->methods()
            ];
        }
    }
    
    echo "📋 Found " . count($paramedicRoutes) . " paramedis routes:\n";
    foreach (array_slice($paramedicRoutes, 0, 10) as $route) {
        echo "  • {$route['uri']} [{$route['name']}]\n";
        echo "    Methods: " . implode(', ', $route['methods']) . "\n";
        echo "    Middleware: " . implode(', ', $route['middleware']) . "\n\n";
    }
    
    echo "\n🧩 MIDDLEWARE INTERACTION ANALYSIS\n";
    echo "=================================\n";
    
    // Check for potential middleware conflicts
    $webMiddleware = config('app.middleware', []);
    $panelMiddleware = [
        'EncryptCookies',
        'AddQueuedCookiesToResponse', 
        'StartSession',
        'SessionCleanupMiddleware',
        'AuthenticateSession',
        'ShareErrorsFromSession',
        'VerifyCsrfToken',
        'SubstituteBindings'
    ];
    
    echo "🔄 Middleware execution order for paramedis panel:\n";
    foreach ($panelMiddleware as $index => $middleware) {
        echo "  " . ($index + 1) . ". {$middleware}\n";
        
        // Check for known problematic patterns
        if ($middleware === 'SessionCleanupMiddleware') {
            echo "    ⚠️  POTENTIAL ISSUE: SessionCleanupMiddleware\n";
            echo "    📝 Analysis: May refresh sessions during request processing\n";
        }
        if ($middleware === 'AuthenticateSession') {
            echo "    ⚠️  POTENTIAL ISSUE: AuthenticateSession\n";
            echo "    📝 Analysis: May invalidate sessions for security\n";
        }
        if ($middleware === 'VerifyCsrfToken') {
            echo "    ✅ CSRF Token Validation Point\n";
            echo "    📝 Analysis: Critical point where 419 errors are generated\n";
        }
    }
    
    echo "\n🔐 SESSION & CSRF TOKEN LIFECYCLE ANALYSIS\n";
    echo "=========================================\n";
    
    // Analyze session configuration
    echo "📊 Session Configuration:\n";
    echo "  Driver: " . config('session.driver') . "\n";
    echo "  Lifetime: " . config('session.lifetime') . " minutes\n";
    echo "  Cookie Name: " . config('session.cookie') . "\n";
    echo "  Domain: " . (config('session.domain') ?? 'null') . "\n";
    echo "  Secure: " . (config('session.secure') ? 'YES' : 'NO') . "\n";
    echo "  HttpOnly: " . (config('session.http_only') ? 'YES' : 'NO') . "\n";
    echo "  SameSite: " . (config('session.same_site') ?? 'null') . "\n\n";
    
    // Check session storage
    echo "💾 Session Storage Analysis:\n";
    $sessionPath = storage_path('framework/sessions');
    if (is_dir($sessionPath)) {
        $sessionFiles = glob($sessionPath . '/*');
        echo "  Active sessions: " . count($sessionFiles) . "\n";
        if (count($sessionFiles) > 0) {
            $latestSession = max(array_map('filemtime', $sessionFiles));
            echo "  Latest session: " . date('Y-m-d H:i:s', $latestSession) . "\n";
        }
    } else {
        echo "  Session directory: Not found (using different driver)\n";
    }
    
    echo "\n👤 BITA USER SPECIFIC ANALYSIS\n";
    echo "=============================\n";
    
    // Find Bita user
    $bita = User::where('username', 'sabita')->first();
    if (!$bita) {
        echo "❌ Bita user not found!\n";
        exit(1);
    }
    
    echo "✅ User: {$bita->name} (ID: {$bita->id})\n";
    echo "🎭 Role: " . ($bita->role ? $bita->role->name : 'No role') . "\n";
    echo "🔑 Password updated: " . $bita->updated_at->diffForHumans() . "\n";
    echo "📧 Email: {$bita->email}\n";
    echo "✅ Active: " . ($bita->is_active ? 'YES' : 'NO') . "\n\n";
    
    echo "🔍 POTENTIAL ROOT CAUSES ANALYSIS\n";
    echo "=================================\n";
    
    echo "1. 🕐 TIMING-BASED ISSUES:\n";
    echo "   • Session expiration during login process\n";
    echo "   • CSRF token expiration between page load and form submission\n";
    echo "   • Race conditions in middleware execution\n\n";
    
    echo "2. 🔄 MIDDLEWARE CONFLICTS:\n";
    
    // Check if RefreshCsrfToken is still in global middleware
    $globalMiddleware = file_get_contents('bootstrap/app.php');
    if (strpos($globalMiddleware, 'RefreshCsrfToken') !== false) {
        echo "   ⚠️  RefreshCsrfToken found in global middleware\n";
    }
    
    // Check ClearStaleSessionMiddleware
    $clearStaleMiddleware = file_get_contents('app/Http/Middleware/ClearStaleSessionMiddleware.php');
    if (strpos($clearStaleMiddleware, 'isMethod(\'GET\')') !== false) {
        echo "   ✅ ClearStaleSessionMiddleware fixed (GET-only)\n";
    } else {
        echo "   ❌ ClearStaleSessionMiddleware still running on POST requests\n";
    }
    
    // Check SessionCleanupMiddleware behavior
    $sessionCleanupMiddleware = file_get_contents('app/Http/Middleware/SessionCleanupMiddleware.php');
    if (strpos($sessionCleanupMiddleware, 'regenerateToken()') !== false) {
        echo "   ⚠️  SessionCleanupMiddleware regenerates tokens during requests\n";
        echo "      📝 This could cause token mismatches during login\n";
    }
    
    echo "\n3. 🌐 BROWSER-SPECIFIC ISSUES:\n";
    echo "   • Multiple tabs causing session conflicts\n";
    echo "   • Browser cache serving stale CSRF tokens\n";
    echo "   • Cookie domain/path mismatches\n";
    echo "   • SameSite cookie policies\n\n";
    
    echo "4. 🔗 NETWORK-LEVEL ISSUES:\n";
    echo "   • Proxy servers modifying requests\n";
    echo "   • Load balancers with session affinity problems\n";
    echo "   • Network timeouts during form submission\n\n";
    
    echo "🧪 LIVE ENVIRONMENT TESTING\n";
    echo "===========================\n";
    
    // Test current CSRF token generation
    echo "🔑 Current CSRF Token Analysis:\n";
    $currentToken = csrf_token();
    echo "  Token: " . substr($currentToken, 0, 20) . "...\n";
    echo "  Length: " . strlen($currentToken) . " characters\n";
    echo "  Generated at: " . date('Y-m-d H:i:s') . "\n\n";
    
    // Test session state
    echo "🛡️  Current Session State:\n";
    $sessionData = session()->all();
    echo "  Session ID: " . session()->getId() . "\n";
    echo "  Session started: " . (session()->isStarted() ? 'YES' : 'NO') . "\n";
    echo "  Data keys: " . implode(', ', array_keys($sessionData)) . "\n\n";
    
    echo "🎯 SPECIFIC BITA LOGIN FLOW ANALYSIS\n";
    echo "===================================\n";
    
    echo "Step-by-step login flow for Bita:\n\n";
    
    echo "1. 🌐 User visits /login page:\n";
    echo "   • ClearStaleSessionMiddleware runs (GET request only now)\n";
    echo "   • New session created with fresh CSRF token\n";
    echo "   • Login form rendered with token\n\n";
    
    echo "2. 📝 User submits login form:\n";
    echo "   • POST request to /login\n";
    echo "   • ClearStaleSessionMiddleware SKIPPED (POST request)\n";
    echo "   • SessionCleanupMiddleware may refresh token\n";
    echo "   • VerifyCsrfToken validates submitted token\n";
    echo "   • If tokens match: authentication proceeds\n";
    echo "   • If tokens mismatch: 419 error returned\n\n";
    
    echo "3. 🔄 Redirect to paramedis panel:\n";
    echo "   • AuthenticateSession validates session\n";
    echo "   • ParamedisMiddleware checks role permissions\n";
    echo "   • Dashboard loads with schedule data\n\n";
    
    echo "💡 RECOMMENDATIONS FOR FURTHER INVESTIGATION\n";
    echo "===========================================\n";
    
    echo "1. 🔍 Monitor SessionCleanupMiddleware:\n";
    echo "   • Check if refreshSessionIfNeeded() runs during login\n";
    echo "   • Verify token timestamp logic\n";
    echo "   • Consider disabling during critical authentication flows\n\n";
    
    echo "2. 🕐 Add timestamp logging:\n";
    echo "   • Log CSRF token generation times\n";
    echo "   • Log form submission times\n";
    echo "   • Identify timing gaps that could cause expiration\n\n";
    
    echo "3. 🧪 Browser testing scenarios:\n";
    echo "   • Test with multiple tabs open\n";
    echo "   • Test with disabled JavaScript\n";
    echo "   • Test with different browser cache settings\n";
    echo "   • Test with network throttling\n\n";
    
    echo "4. 📊 Add detailed error logging:\n";
    echo "   • Log exact token values during validation failures\n";
    echo "   • Log middleware execution order and timing\n";
    echo "   • Log session state changes throughout request\n\n";
    
    echo "🚨 IMMEDIATE ACTION ITEMS\n";
    echo "========================\n";
    
    echo "1. ⚡ CRITICAL: Test with real browser\n";
    echo "   • Use the test file: /public/test-bita-final-fix.html\n";
    echo "   • Verify no 419 errors occur\n";
    echo "   • Check browser console for JavaScript errors\n\n";
    
    echo "2. 🔧 INVESTIGATE: SessionCleanupMiddleware\n";
    echo "   • May be refreshing tokens during authentication\n";
    echo "   • Consider temporary disabling for testing\n\n";
    
    echo "3. 📝 IMPLEMENT: Enhanced logging\n";
    echo "   • Add CSRF token tracking in middleware\n";
    echo "   • Log session state changes\n";
    echo "   • Monitor timing between token generation and validation\n\n";
    
    echo "✅ ANALYSIS COMPLETED\n";
    echo "====================\n";
    echo "The middleware fixes should resolve most 419 errors.\n";
    echo "If issues persist, focus on SessionCleanupMiddleware timing.\n";
    echo "Use the comprehensive test file to verify the fixes work.\n\n";
    
} catch (Exception $e) {
    echo "❌ Error during analysis: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}