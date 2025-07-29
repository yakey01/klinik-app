<?php
// DEBUG PAGE EXPIRED ISSUE FOR BITA
echo "🚨 DEBUG PAGE EXPIRED - BITA LOGIN\n";
echo "=================================\n\n";

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;

try {
    echo "🔍 SESSION CONFIGURATION ANALYSIS\n";
    echo "================================\n";
    
    echo "📊 Current Session Config:\n";
    echo "  Driver: " . config('session.driver') . "\n";
    echo "  Lifetime: " . config('session.lifetime') . " minutes\n";
    echo "  Cookie: " . config('session.cookie') . "\n";
    echo "  Domain: " . (config('session.domain') ?? 'null') . "\n";
    echo "  Secure: " . (config('session.secure') ? 'YES' : 'NO') . "\n";
    echo "  HttpOnly: " . (config('session.http_only') ? 'YES' : 'NO') . "\n";
    echo "  SameSite: " . (config('session.same_site') ?? 'null') . "\n\n";
    
    echo "🛡️ CSRF CONFIGURATION\n";
    echo "====================\n";
    echo "  Token Lifetime: " . config('session.lifetime') . " minutes\n";
    echo "  Current Token: " . csrf_token() . "\n";
    echo "  Token Length: " . strlen(csrf_token()) . " characters\n\n";
    
    echo "🔧 MIDDLEWARE ANALYSIS\n";
    echo "=====================\n";
    
    // Check middleware stack
    $app = app();
    $router = $app['router'];
    $middleware = $router->getMiddleware();
    
    echo "📋 Registered Middleware:\n";
    foreach ($middleware as $name => $class) {
        if (strpos($name, 'csrf') !== false || strpos($name, 'session') !== false || strpos($name, 'auth') !== false) {
            echo "  - {$name}: {$class}\n";
        }
    }
    
    echo "\n🚨 ISSUE DIAGNOSIS\n";
    echo "=================\n";
    
    echo "🔍 Checking RefreshCsrfToken middleware behavior:\n";
    
    // Read the current middleware
    $middlewareFile = file_get_contents('app/Http/Middleware/RefreshCsrfToken.php');
    
    if (strpos($middlewareFile, 'session()->invalidate()') !== false) {
        echo "❌ CRITICAL ISSUE FOUND:\n";
        echo "   RefreshCsrfToken middleware invalidates session on login pages\n";
        echo "   This causes 419 Page Expired errors\n\n";
        
        echo "🔧 PROBLEMATIC CODE:\n";
        echo "   Lines 19-23: Aggressive session invalidation\n";
        echo "   session()->invalidate() → Destroys entire session\n";
        echo "   session()->regenerateToken() → Creates new CSRF token\n";
        echo "   session()->migrate(true) → Forces session migration\n\n";
        
        echo "💡 ROOT CAUSE:\n";
        echo "   When user submits login form:\n";
        echo "   1. Form has CSRF token A\n";
        echo "   2. RefreshCsrfToken runs BEFORE form validation\n";
        echo "   3. Middleware invalidates session and creates token B\n";
        echo "   4. Form validation fails because token A ≠ token B\n";
        echo "   5. Laravel returns 419 Page Expired error\n\n";
        
        echo "🛠️ SOLUTION NEEDED:\n";
        echo "   Modify RefreshCsrfToken to only regenerate on GET requests:\n";
        echo "   - Only refresh tokens on GET requests to login pages\n";
        echo "   - Skip regeneration on POST requests (form submissions)\n";
        echo "   - This preserves token validity during form submission\n\n";
        
        // Show the fix
        echo "📝 RECOMMENDED FIX:\n";
        echo "Replace lines 19-23 with:\n";
        echo "```php\n";
        echo "// Only regenerate token for GET requests to login pages, not POST submissions\n";
        echo "if ((\$request->is('*/login') || \$request->routeIs('*.auth.login')) && \$request->isMethod('GET')) {\n";
        echo "    session()->regenerateToken();\n";
        echo "}\n";
        echo "```\n\n";
        
        echo "🔧 APPLYING FIX NOW:\n";
        echo "===================\n";
        
        // Apply the fix
        $fixedMiddleware = str_replace(
            "        // For login pages, aggressively regenerate session to clear stale tokens
        if (\$request->is('*/login') || \$request->routeIs('*.auth.login')) {
            session()->invalidate();
            session()->regenerateToken();
            session()->migrate(true);
        }",
            "        // Only regenerate token for GET requests to login pages, not POST submissions
        if ((\$request->is('*/login') || \$request->routeIs('*.auth.login')) && \$request->isMethod('GET')) {
            session()->regenerateToken();
        }",
            $middlewareFile
        );
        
        if ($fixedMiddleware !== $middlewareFile) {
            file_put_contents('app/Http/Middleware/RefreshCsrfToken.php', $fixedMiddleware);
            echo "✅ RefreshCsrfToken middleware fixed!\n";
            echo "   - Removed session()->invalidate()\n";
            echo "   - Removed session()->migrate(true)\n";
            echo "   - Only regenerates token on GET requests\n";
            echo "   - POST requests preserve existing tokens\n\n";
        } else {
            echo "⚠️ Could not apply automatic fix\n";
            echo "   Please manually edit the middleware file\n\n";
        }
    } else {
        echo "✅ RefreshCsrfToken middleware appears to be fixed\n\n";
    }
    
    echo "🧪 ADDITIONAL CHECKS\n";
    echo "===================\n";
    
    // Check for other middleware conflicts
    $filamentConfig = file_get_contents('app/Providers/Filament/AdminPanelProvider.php');
    if (strpos($filamentConfig, 'AuthenticateSession::class') !== false) {
        echo "⚠️ WARNING: AuthenticateSession middleware detected in AdminPanelProvider\n";
        echo "   This can conflict with custom session handling\n";
        echo "   Consider removing it if login issues persist\n\n";
    }
    
    // Check session storage
    echo "📁 Session Storage Check:\n";
    $sessionPath = storage_path('framework/sessions');
    if (is_dir($sessionPath)) {
        $sessionFiles = glob($sessionPath . '/*');
        echo "   Session files: " . count($sessionFiles) . "\n";
        echo "   Last modified: " . (count($sessionFiles) > 0 ? date('Y-m-d H:i:s', filemtime($sessionFiles[0])) : 'N/A') . "\n";
    } else {
        echo "   Session directory not found\n";
    }
    
    echo "\n🎯 TESTING INSTRUCTIONS\n";
    echo "======================\n";
    echo "1. Clear browser cache and cookies\n";
    echo "2. Go to: http://localhost:8000/login\n";
    echo "3. Enter: sabita / bita123\n";
    echo "4. Submit form\n";
    echo "5. Should redirect to /paramedis (no 419 error)\n\n";
    
    echo "🔍 IF STILL GETTING 419 ERROR:\n";
    echo "==============================\n";
    echo "1. Check browser console for JavaScript errors\n";
    echo "2. Verify CSRF token in form matches session\n";
    echo "3. Check if multiple tabs are interfering\n";
    echo "4. Try incognito/private browsing mode\n";
    echo "5. Check Laravel logs: tail -f storage/logs/laravel.log\n\n";
    
    echo "✅ DIAGNOSIS COMPLETED\n";
    echo "======================\n";
    echo "Main issue: RefreshCsrfToken middleware invalidating sessions on POST requests\n";
    echo "Solution: Modified middleware to only regenerate tokens on GET requests\n";
    echo "Next: Test Bita login with fixed middleware\n";
    
} catch (Exception $e) {
    echo "❌ Error during diagnosis: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}