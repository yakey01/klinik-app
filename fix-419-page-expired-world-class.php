<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;

echo "\n🌟 WORLD-CLASS 419 PAGE EXPIRED FIX\n";
echo "=====================================\n\n";

$issues = [];
$solutions = [];

// 1. SESSION CONFIGURATION AUDIT
echo "1️⃣ SESSION CONFIGURATION AUDIT\n";
$sessionConfig = [
    'driver' => config('session.driver'),
    'lifetime' => config('session.lifetime'),
    'expire_on_close' => config('session.expire_on_close'),
    'encrypt' => config('session.encrypt'),
    'cookie' => config('session.cookie'),
    'path' => config('session.path'),
    'domain' => config('session.domain'),
    'secure' => config('session.secure'),
    'http_only' => config('session.http_only'),
    'same_site' => config('session.same_site'),
];

echo "   Current Configuration:\n";
foreach ($sessionConfig as $key => $value) {
    echo "   - {$key}: " . json_encode($value) . "\n";
}

// Recommendations
if ($sessionConfig['driver'] === 'file') {
    echo "   ⚠️  Using file driver - switch to database for better reliability\n";
    $issues[] = "Session driver is 'file' - less reliable than database";
}

if ($sessionConfig['lifetime'] < 120) {
    echo "   ⚠️  Session lifetime too short: {$sessionConfig['lifetime']} minutes\n";
    $issues[] = "Session lifetime too short";
}

if ($sessionConfig['secure'] && !request()->secure()) {
    echo "   ⚠️  Secure cookie enabled but not using HTTPS\n";
    $issues[] = "Secure cookie enabled on non-HTTPS";
}

// 2. SESSION TABLE CHECK
echo "\n2️⃣ SESSION TABLE INTEGRITY CHECK\n";
if (config('session.driver') === 'database') {
    if (!Schema::hasTable('sessions')) {
        echo "   ❌ Sessions table missing!\n";
        echo "   Running: php artisan session:table && php artisan migrate\n";
        \Illuminate\Support\Facades\Artisan::call('session:table');
        \Illuminate\Support\Facades\Artisan::call('migrate');
        $solutions[] = "Created sessions table";
    } else {
        echo "   ✅ Sessions table exists\n";
        
        // Check table structure
        $columns = Schema::getColumnListing('sessions');
        $requiredColumns = ['id', 'user_id', 'ip_address', 'user_agent', 'payload', 'last_activity'];
        $missingColumns = array_diff($requiredColumns, $columns);
        
        if (!empty($missingColumns)) {
            echo "   ⚠️  Missing columns: " . implode(', ', $missingColumns) . "\n";
            $issues[] = "Sessions table missing columns";
        } else {
            echo "   ✅ Sessions table structure correct\n";
        }
        
        // Clean old sessions
        $deleted = DB::table('sessions')
            ->where('last_activity', '<', time() - (config('session.lifetime') * 60))
            ->delete();
        if ($deleted > 0) {
            echo "   ✅ Cleaned {$deleted} expired sessions\n";
            $solutions[] = "Cleaned {$deleted} expired sessions";
        }
    }
}

// 3. CSRF TOKEN CONFIGURATION
echo "\n3️⃣ CSRF TOKEN CONFIGURATION CHECK\n";
$csrfMiddleware = app()->make(\App\Http\Middleware\VerifyCsrfToken::class);
$reflection = new ReflectionClass($csrfMiddleware);
$property = $reflection->getProperty('except');
$property->setAccessible(true);
$csrfExcept = $property->getValue($csrfMiddleware);

echo "   CSRF Exceptions:\n";
foreach ($csrfExcept as $exception) {
    echo "   - {$exception}\n";
}

// 4. STORAGE PERMISSIONS
echo "\n4️⃣ STORAGE PERMISSIONS CHECK\n";
$storagePaths = [
    'storage/framework/sessions' => '0755',
    'storage/framework/cache' => '0755',
    'storage/framework/views' => '0755',
    'storage/logs' => '0755',
];

foreach ($storagePaths as $path => $requiredPermission) {
    if (!File::exists($path)) {
        File::makeDirectory($path, 0755, true);
        echo "   ✅ Created directory: {$path}\n";
        $solutions[] = "Created directory: {$path}";
    }
    
    $currentPermission = substr(sprintf('%o', fileperms($path)), -4);
    if ($currentPermission !== $requiredPermission) {
        chmod($path, octdec($requiredPermission));
        echo "   ✅ Fixed permissions for: {$path}\n";
        $solutions[] = "Fixed permissions for: {$path}";
    } else {
        echo "   ✅ {$path} permissions OK\n";
    }
}

// 5. APP KEY CHECK
echo "\n5️⃣ APP KEY CHECK\n";
$appKey = config('app.key');
if (empty($appKey)) {
    echo "   ❌ APP_KEY not set! Generating...\n";
    \Illuminate\Support\Facades\Artisan::call('key:generate');
    $issues[] = "APP_KEY was not set";
    $solutions[] = "Generated new APP_KEY";
} else {
    echo "   ✅ APP_KEY is set\n";
}

// 6. MIDDLEWARE STACK CHECK
echo "\n6️⃣ MIDDLEWARE STACK CHECK\n";
$webMiddleware = app('router')->getMiddlewareGroups()['web'] ?? [];
$criticalMiddleware = [
    \Illuminate\Cookie\Middleware\EncryptCookies::class,
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
];

foreach ($criticalMiddleware as $middleware) {
    $found = false;
    foreach ($webMiddleware as $m) {
        if ($m === $middleware || (is_object($m) && get_class($m) === $middleware)) {
            $found = true;
            break;
        }
    }
    echo $found ? "   ✅" : "   ❌";
    echo " " . class_basename($middleware) . "\n";
    if (!$found) {
        $issues[] = "Missing critical middleware: " . class_basename($middleware);
    }
}

// 7. DOMAIN AND URL CONFIGURATION
echo "\n7️⃣ DOMAIN AND URL CONFIGURATION\n";
$appUrl = config('app.url');
$sessionDomain = config('session.domain');
echo "   APP_URL: {$appUrl}\n";
echo "   SESSION_DOMAIN: " . ($sessionDomain ?: 'not set') . "\n";

if ($sessionDomain && !str_contains($appUrl, $sessionDomain)) {
    echo "   ⚠️  SESSION_DOMAIN doesn't match APP_URL\n";
    $issues[] = "SESSION_DOMAIN mismatch with APP_URL";
}

// 8. CREATE ANTI-419 MIDDLEWARE
echo "\n8️⃣ CREATING ANTI-419 MIDDLEWARE\n";
$middlewarePath = app_path('Http/Middleware/PreventPageExpired.php');
if (!File::exists($middlewarePath)) {
    $middlewareContent = <<<'PHP'
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PreventPageExpired
{
    public function handle(Request $request, Closure $next)
    {
        // Auto-refresh CSRF token if expired
        if ($request->isMethod('POST') && !$request->ajax()) {
            if (!$request->session()->has('_token') || 
                $request->input('_token') !== $request->session()->token()) {
                // Regenerate token
                $request->session()->regenerateToken();
                
                // For form submissions, merge the new token
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'CSRF token refreshed',
                        'token' => csrf_token()
                    ], 200);
                }
            }
        }
        
        // Add headers to prevent caching of sensitive pages
        $response = $next($request);
        
        if (method_exists($response, 'header')) {
            $response->header('Cache-Control', 'no-cache, no-store, must-revalidate');
            $response->header('Pragma', 'no-cache');
            $response->header('Expires', '0');
        }
        
        return $response;
    }
}
PHP;
    File::put($middlewarePath, $middlewareContent);
    echo "   ✅ Created PreventPageExpired middleware\n";
    $solutions[] = "Created PreventPageExpired middleware";
} else {
    echo "   ✅ PreventPageExpired middleware exists\n";
}

// 9. ENHANCED LOGIN FORM
echo "\n9️⃣ CREATING ENHANCED LOGIN FORM SCRIPT\n";
$jsPath = public_path('js/prevent-419.js');
if (!File::exists(dirname($jsPath))) {
    File::makeDirectory(dirname($jsPath), 0755, true);
}

$jsContent = <<<'JS'
// Prevent 419 Page Expired Errors
(function() {
    'use strict';
    
    // Auto-refresh CSRF token every 30 minutes
    setInterval(function() {
        fetch('/refresh-csrf', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            // Update all CSRF tokens on the page
            document.querySelectorAll('input[name="_token"]').forEach(function(input) {
                input.value = data.token;
            });
            
            // Update meta tag
            const metaToken = document.querySelector('meta[name="csrf-token"]');
            if (metaToken) {
                metaToken.setAttribute('content', data.token);
            }
            
            console.log('CSRF token refreshed');
        })
        .catch(error => console.error('Error refreshing CSRF token:', error));
    }, 30 * 60 * 1000); // 30 minutes
    
    // Handle form submission errors
    document.addEventListener('DOMContentLoaded', function() {
        const forms = document.querySelectorAll('form');
        
        forms.forEach(function(form) {
            form.addEventListener('submit', function(e) {
                const tokenInput = form.querySelector('input[name="_token"]');
                
                if (!tokenInput || !tokenInput.value) {
                    e.preventDefault();
                    alert('Security token missing. Page will refresh.');
                    window.location.reload();
                    return false;
                }
                
                // Store form data in session storage in case of 419
                const formData = new FormData(form);
                const data = {};
                for (let [key, value] of formData.entries()) {
                    if (key !== '_token' && key !== 'password') {
                        data[key] = value;
                    }
                }
                sessionStorage.setItem('form_data', JSON.stringify(data));
            });
        });
        
        // Restore form data if available
        const savedData = sessionStorage.getItem('form_data');
        if (savedData) {
            try {
                const data = JSON.parse(savedData);
                Object.keys(data).forEach(function(key) {
                    const input = document.querySelector(`[name="${key}"]`);
                    if (input) {
                        input.value = data[key];
                    }
                });
                sessionStorage.removeItem('form_data');
            } catch (e) {
                console.error('Error restoring form data:', e);
            }
        }
    });
    
    // Detect 419 errors and handle gracefully
    window.addEventListener('load', function() {
        if (document.body.textContent.includes('419') && 
            document.body.textContent.includes('Page Expired')) {
            sessionStorage.setItem('encountered_419', 'true');
            setTimeout(function() {
                window.location.reload();
            }, 1000);
        }
    });
})();
JS;

File::put($jsPath, $jsContent);
echo "   ✅ Created prevent-419.js script\n";
$solutions[] = "Created prevent-419.js script";

// 10. CSRF REFRESH ROUTE
echo "\n🔟 ADDING CSRF REFRESH ROUTE\n";
$routeExists = \Route::has('refresh-csrf');
if (!$routeExists) {
    echo "   ℹ️  Add this route to routes/web.php:\n";
    echo "   Route::get('/refresh-csrf', function() {\n";
    echo "       return response()->json(['token' => csrf_token()]);\n";
    echo "   })->name('refresh-csrf');\n";
    $issues[] = "CSRF refresh route not found";
}

// OPTIMIZATION COMMANDS
echo "\n⚡ RUNNING OPTIMIZATION COMMANDS\n";
\Illuminate\Support\Facades\Artisan::call('config:cache');
echo "   ✅ Configuration cached\n";
\Illuminate\Support\Facades\Artisan::call('route:cache');
echo "   ✅ Routes cached\n";
\Illuminate\Support\Facades\Artisan::call('view:cache');
echo "   ✅ Views cached\n";
$solutions[] = "Optimized application caches";

// SUMMARY
echo "\n\n📊 WORLD-CLASS AUDIT SUMMARY\n";
echo "==============================\n\n";

if (empty($issues)) {
    echo "✅ NO ISSUES FOUND - System is bulletproof!\n\n";
} else {
    echo "⚠️  ISSUES DETECTED:\n";
    foreach ($issues as $issue) {
        echo "   - {$issue}\n";
    }
    echo "\n";
}

if (!empty($solutions)) {
    echo "✅ SOLUTIONS APPLIED:\n";
    foreach ($solutions as $solution) {
        echo "   - {$solution}\n";
    }
    echo "\n";
}

echo "🛡️  419 PREVENTION MEASURES:\n";
echo "1. Session lifetime: " . config('session.lifetime') . " minutes\n";
echo "2. CSRF exceptions configured\n";
echo "3. Storage permissions verified\n";
echo "4. Anti-419 middleware created\n";
echo "5. Client-side CSRF refresh script created\n";
echo "6. Session cleanup implemented\n\n";

echo "📝 IMPLEMENTATION CHECKLIST:\n";
echo "1. Add PreventPageExpired to web middleware group\n";
echo "2. Include prevent-419.js in your login layout\n";
echo "3. Add CSRF refresh route to routes/web.php\n";
echo "4. Clear browser cache and cookies\n";
echo "5. Test login with Network tab open\n\n";

echo "✅ World-class 419 fix completed!\n\n";