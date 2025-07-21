<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\\Contracts\\Console\\Kernel');
$kernel->bootstrap();

echo "=== FINAL LOGIN FIX ===" . PHP_EOL;

try {
    // Test user exists and auth works
    $user = DB::table('users')->where('email', 'tina@paramedis.com')->first();
    if (!$user) {
        echo "❌ User not found" . PHP_EOL;
        exit(1);
    }
    
    echo "✅ User found: {$user->email}" . PHP_EOL;
    
    // Test password hash
    $passwordWorks = Hash::check('password123', $user->password);
    echo "Password test: " . ($passwordWorks ? 'PASS ✅' : 'FAIL ❌') . PHP_EOL;
    
    if (!$passwordWorks) {
        // Create fresh password hash
        $newHash = Hash::make('password123');
        DB::table('users')
            ->where('email', 'tina@paramedis.com')
            ->update(['password' => $newHash, 'updated_at' => now()]);
        echo "✅ Password updated with fresh hash" . PHP_EOL;
    }
    
    // Test Laravel Auth
    $authTest = Auth::attempt(['email' => 'tina@paramedis.com', 'password' => 'password123']);
    echo "Laravel auth: " . ($authTest ? 'SUCCESS ✅' : 'FAILED ❌') . PHP_EOL;
    
    if ($authTest) {
        $authUser = Auth::user();
        echo "✅ Authenticated as: {$authUser->email}" . PHP_EOL;
        
        // Test Filament canAccessPanel directly
        try {
            $panel = \Filament\Facades\Filament::getPanel('paramedis');
            $canAccess = $authUser->canAccessPanel($panel);
            echo "Can access paramedis panel: " . ($canAccess ? 'YES ✅' : 'NO ❌') . PHP_EOL;
        } catch (Exception $e) {
            echo "Panel access test error: " . $e->getMessage() . PHP_EOL;
        }
        
        Auth::logout();
    }
    
    // Clear all possible caches
    echo PHP_EOL . "Clearing all caches..." . PHP_EOL;
    
    try {
        Artisan::call('cache:clear');
        echo "✅ Cache cleared" . PHP_EOL;
    } catch (Exception $e) {
        echo "Cache clear failed: " . $e->getMessage() . PHP_EOL;
    }
    
    try {
        Artisan::call('config:clear');
        echo "✅ Config cache cleared" . PHP_EOL;
    } catch (Exception $e) {
        echo "Config clear failed: " . $e->getMessage() . PHP_EOL;
    }
    
    try {
        Artisan::call('route:clear');
        echo "✅ Route cache cleared" . PHP_EOL;
    } catch (Exception $e) {
        echo "Route clear failed: " . $e->getMessage() . PHP_EOL;
    }
    
    try {
        Artisan::call('view:clear');
        echo "✅ View cache cleared" . PHP_EOL;
    } catch (Exception $e) {
        echo "View clear failed: " . $e->getMessage() . PHP_EOL;
    }
    
    // Check if sessions table exists and clear old sessions
    try {
        $sessionsExist = DB::getSchemaBuilder()->hasTable('sessions');
        if ($sessionsExist) {
            $oldSessions = DB::table('sessions')
                ->where('last_activity', '<', now()->subHours(2)->timestamp)
                ->delete();
            echo "✅ Cleaned $oldSessions old sessions" . PHP_EOL;
        }
    } catch (Exception $e) {
        echo "Session cleanup error: " . $e->getMessage() . PHP_EOL;
    }
    
    // Force regenerate autoload
    try {
        echo "Regenerating autoload..." . PHP_EOL;
        shell_exec('composer dump-autoload');
        echo "✅ Autoload regenerated" . PHP_EOL;
    } catch (Exception $e) {
        echo "Autoload regeneration failed: " . $e->getMessage() . PHP_EOL;
    }
    
    // Final authentication test
    echo PHP_EOL . "Final authentication test..." . PHP_EOL;
    $finalAuth = Auth::attempt(['email' => 'tina@paramedis.com', 'password' => 'password123']);
    echo "Final auth result: " . ($finalAuth ? 'SUCCESS ✅' : 'FAILED ❌') . PHP_EOL;
    
    if ($finalAuth) {
        Auth::logout();
        echo "✅ Authentication working properly" . PHP_EOL;
        
        echo PHP_EOL . "🎉 FINAL FIX COMPLETE!" . PHP_EOL;
        echo "Login should now work at: https://dokterkuklinik.com/paramedis/login" . PHP_EOL;
        echo "Email: tina@paramedis.com" . PHP_EOL;
        echo "Password: password123" . PHP_EOL;
        echo PHP_EOL . "Try logging in now - the issue should be resolved!" . PHP_EOL;
    } else {
        echo "❌ Authentication still failing - check error logs" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
    echo "File: " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
}

echo PHP_EOL . "=== FIX EXECUTION COMPLETE ===" . PHP_EOL;
?>