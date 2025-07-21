<?php
/**
 * CSRF Debug Script for Production
 * This script will help identify CSRF issues in production environment
 */

// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "=== CSRF PRODUCTION DEBUGGING ===\n\n";

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    
    try {
        $app = require_once __DIR__ . '/bootstrap/app.php';
        $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();
        
        echo "✅ Laravel loaded\n";
        
        // 1. Environment Check
        echo "\n1. ENVIRONMENT CHECK:\n";
        echo "   APP_ENV: " . env('APP_ENV', 'not set') . "\n";
        echo "   APP_DEBUG: " . (env('APP_DEBUG', false) ? 'true' : 'false') . "\n";
        echo "   APP_URL: " . env('APP_URL', 'not set') . "\n";
        echo "   APP_KEY: " . (env('APP_KEY') ? 'SET (' . strlen(env('APP_KEY')) . ' chars)' : 'NOT SET') . "\n";
        
        // 2. Session Configuration
        echo "\n2. SESSION CONFIGURATION:\n";
        echo "   Driver: " . config('session.driver') . "\n";
        echo "   Lifetime: " . config('session.lifetime') . " minutes\n";
        echo "   Domain: " . (config('session.domain') ?: 'auto-detect') . "\n";
        echo "   Path: " . config('session.path') . "\n";
        echo "   Secure: " . (config('session.secure') ? 'true' : 'false') . "\n";
        echo "   HttpOnly: " . (config('session.http_only') ? 'true' : 'false') . "\n";
        echo "   SameSite: " . (config('session.same_site') ?: 'null') . "\n";
        
        // 3. Database Session Check
        if (config('session.driver') === 'database') {
            echo "\n3. DATABASE SESSION CHECK:\n";
            try {
                $sessionTableExists = Schema::hasTable('sessions');
                echo "   Sessions table exists: " . ($sessionTableExists ? 'YES' : 'NO') . "\n";
                
                if ($sessionTableExists) {
                    $sessionCount = DB::table('sessions')->count();
                    echo "   Current sessions: $sessionCount\n";
                    
                    // Show recent sessions
                    $recentSessions = DB::table('sessions')
                        ->orderBy('last_activity', 'desc')
                        ->limit(3)
                        ->get(['id', 'last_activity', 'ip_address']);
                    
                    echo "   Recent sessions:\n";
                    foreach ($recentSessions as $session) {
                        $lastActivity = date('Y-m-d H:i:s', $session->last_activity);
                        echo "     - " . substr($session->id, 0, 8) . "... from {$session->ip_address} at {$lastActivity}\n";
                    }
                }
            } catch (Exception $e) {
                echo "   ❌ Database session error: " . $e->getMessage() . "\n";
            }
        }
        
        // 4. CSRF Token Test
        echo "\n4. CSRF TOKEN TEST:\n";
        try {
            // Start session if not started
            if (!session()->isStarted()) {
                session()->start();
                echo "   ✅ Session started\n";
            } else {
                echo "   ✅ Session already active\n";
            }
            
            $token = csrf_token();
            echo "   ✅ CSRF token generated: " . substr($token, 0, 16) . "...\n";
            echo "   Token length: " . strlen($token) . " characters\n";
            
            // Test token regeneration
            session()->regenerateToken();
            $newToken = csrf_token();
            echo "   ✅ Token regenerated: " . substr($newToken, 0, 16) . "...\n";
            echo "   Tokens different: " . ($token !== $newToken ? 'YES' : 'NO') . "\n";
            
        } catch (Exception $e) {
            echo "   ❌ CSRF token error: " . $e->getMessage() . "\n";
        }
        
        // 5. Middleware Check
        echo "\n5. MIDDLEWARE CHECK:\n";
        $middlewareClasses = [
            'RefreshCsrfToken' => \App\Http\Middleware\RefreshCsrfToken::class,
            'SessionCleanupMiddleware' => \App\Http\Middleware\SessionCleanupMiddleware::class,
            'ClearStaleSessionMiddleware' => \App\Http\Middleware\ClearStaleSessionMiddleware::class,
            'VerifyCsrfToken' => \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
        ];
        
        foreach ($middlewareClasses as $name => $class) {
            if (class_exists($class)) {
                echo "   ✅ $name exists\n";
                try {
                    $instance = app($class);
                    echo "     Can instantiate: YES\n";
                } catch (Exception $e) {
                    echo "     Can instantiate: NO - " . $e->getMessage() . "\n";
                }
            } else {
                echo "   ❌ $name missing\n";
            }
        }
        
        // 6. Route Test for Paramedis
        echo "\n6. PARAMEDIS ROUTE TEST:\n";
        $testRoutes = [
            '/paramedis/login' => 'Login page',
            '/paramedis' => 'Main redirect',
            '/paramedis/mobile-app' => 'Mobile app'
        ];
        
        foreach ($testRoutes as $path => $description) {
            try {
                $request = Request::create($path, 'GET');
                $route = Route::getRoutes()->match($request);
                echo "   ✅ $description ($path): " . $route->getName() . "\n";
                
                // Check middleware
                $middleware = $route->gatherMiddleware();
                $csrfMiddleware = array_filter($middleware, function($mw) {
                    return str_contains($mw, 'csrf') || str_contains($mw, 'Csrf');
                });
                
                if (!empty($csrfMiddleware)) {
                    echo "     CSRF middleware: " . implode(', ', $csrfMiddleware) . "\n";
                }
                
            } catch (Exception $e) {
                echo "   ❌ $description ($path): " . $e->getMessage() . "\n";
            }
        }
        
        // 7. Create simple CSRF test form
        echo "\n7. GENERATING CSRF TEST:\n";
        $testToken = csrf_token();
        $testHtml = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>CSRF Test</title>
</head>
<body>
    <h2>CSRF Token Test</h2>
    <p>Current token: <code>{$testToken}</code></p>
    <form method="POST" action="/paramedis/login">
        <input type="hidden" name="_token" value="{$testToken}">
        <input type="email" name="email" value="tina@paramedis.com" required>
        <input type="password" name="password" value="password123" required>
        <button type="submit">Test Login</button>
    </form>
    <p><strong>Token in meta tag:</strong></p>
    <meta name="csrf-token" content="{$testToken}">
    <script>
        console.log('CSRF Token from meta:', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
    </script>
</body>
</html>
HTML;
        
        file_put_contents(__DIR__ . '/public/csrf-test.html', $testHtml);
        echo "   ✅ Created CSRF test file: /public/csrf-test.html\n";
        echo "   Access it at: " . config('app.url') . "/csrf-test.html\n";
        
        echo "\n=== DEBUGGING COMPLETE ===\n";
        echo "Next steps:\n";
        echo "1. Visit the CSRF test page to verify tokens work\n";
        echo "2. Check browser developer tools for CSRF-related errors\n";
        echo "3. If tokens are generated but login fails, the issue is elsewhere\n";
        echo "4. Check server error logs for specific error messages\n";
        
    } catch (Exception $e) {
        echo "❌ Debug script failed: " . $e->getMessage() . "\n";
        echo "Stack trace: " . $e->getTraceAsString() . "\n";
    }
} else {
    echo "❌ Run this script from Laravel root directory\n";
}

echo "\n=== END CSRF DEBUG ===\n";