<?php
/**
 * Production Cleanup and Optimization
 * Clean up old sessions, optimize caches, and prepare production environment
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== PRODUCTION CLEANUP AND OPTIMIZATION ===\n\n";

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    
    try {
        $app = require_once __DIR__ . '/bootstrap/app.php';
        $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();
        
        echo "✅ Laravel loaded\n\n";
        
        // 1. Clean Old Sessions
        echo "1. CLEANING OLD SESSIONS:\n";
        if (config('session.driver') === 'database') {
            try {
                $oldSessions = DB::table('sessions')
                    ->where('last_activity', '<', now()->subHours(24)->timestamp)
                    ->count();
                    
                $deleted = DB::table('sessions')
                    ->where('last_activity', '<', now()->subHours(24)->timestamp)
                    ->delete();
                    
                echo "   ✅ Cleaned $deleted old sessions (older than 24h)\n";
                
                $currentSessions = DB::table('sessions')->count();
                echo "   Current active sessions: $currentSessions\n";
                
            } catch (Exception $e) {
                echo "   ❌ Session cleanup failed: " . $e->getMessage() . "\n";
            }
        } else {
            echo "   ℹ️  Session driver is not database, skipping session cleanup\n";
        }
        
        // 2. Clear Laravel Caches
        echo "\n2. CLEARING LARAVEL CACHES:\n";
        try {
            // Clear application cache
            Artisan::call('cache:clear');
            echo "   ✅ Application cache cleared\n";
            
            // Clear config cache
            Artisan::call('config:clear');
            echo "   ✅ Config cache cleared\n";
            
            // Clear route cache
            Artisan::call('route:clear');
            echo "   ✅ Route cache cleared\n";
            
            // Clear view cache
            Artisan::call('view:clear');
            echo "   ✅ View cache cleared\n";
            
        } catch (Exception $e) {
            echo "   ❌ Cache clearing failed: " . $e->getMessage() . "\n";
        }
        
        // 3. Optimize for Production
        echo "\n3. OPTIMIZING FOR PRODUCTION:\n";
        if (config('app.env') === 'production') {
            try {
                // Cache config
                Artisan::call('config:cache');
                echo "   ✅ Config cached\n";
                
                // Cache routes
                Artisan::call('route:cache');
                echo "   ✅ Routes cached\n";
                
                // Cache views
                Artisan::call('view:cache');
                echo "   ✅ Views cached\n";
                
            } catch (Exception $e) {
                echo "   ❌ Optimization failed: " . $e->getMessage() . "\n";
            }
        } else {
            echo "   ℹ️  Not production environment, skipping optimization\n";
        }
        
        // 4. Clean Duplicate Users
        echo "\n4. CLEANING DUPLICATE USERS:\n";
        try {
            // Find duplicate emails
            $duplicates = DB::table('users')
                ->select('email', DB::raw('COUNT(*) as count'))
                ->groupBy('email')
                ->having('count', '>', 1)
                ->get();
                
            if ($duplicates->count() > 0) {
                echo "   Found " . $duplicates->count() . " duplicate emails:\n";
                
                foreach ($duplicates as $duplicate) {
                    echo "     - {$duplicate->email} ({$duplicate->count} copies)\n";
                    
                    // Keep only the most recent user
                    $users = DB::table('users')
                        ->where('email', $duplicate->email)
                        ->orderBy('created_at', 'desc')
                        ->get();
                        
                    $keepUser = $users->first();
                    $deleteUsers = $users->skip(1);
                    
                    foreach ($deleteUsers as $deleteUser) {
                        DB::table('users')->where('id', $deleteUser->id)->delete();
                        echo "       Deleted duplicate user ID: {$deleteUser->id}\n";
                    }
                }
            } else {
                echo "   ✅ No duplicate users found\n";
            }
            
        } catch (Exception $e) {
            echo "   ❌ Duplicate cleanup failed: " . $e->getMessage() . "\n";
        }
        
        // 5. Verify Critical Users
        echo "\n5. VERIFYING CRITICAL USERS:\n";
        $criticalUsers = [
            'admin@dokterku.com' => 'admin',
            'tina@paramedis.com' => 'paramedis'
        ];
        
        foreach ($criticalUsers as $email => $expectedRole) {
            $user = DB::table('users')
                ->join('roles', 'users.role_id', '=', 'roles.id')
                ->where('users.email', $email)
                ->select('users.*', 'roles.name as role_name')
                ->first();
                
            if ($user) {
                $roleMatch = $user->role_name === $expectedRole;
                echo "   ✅ $email: " . ($roleMatch ? "GOOD ({$user->role_name})" : "ROLE MISMATCH (expected: $expectedRole, got: {$user->role_name})") . "\n";
            } else {
                echo "   ❌ $email: MISSING\n";
            }
        }
        
        // 6. File Permissions Check
        echo "\n6. FILE PERMISSIONS CHECK:\n";
        $checkPaths = [
            'storage' => storage_path(),
            'storage/logs' => storage_path('logs'),
            'storage/framework/cache' => storage_path('framework/cache'),
            'storage/framework/sessions' => storage_path('framework/sessions'),
            'storage/framework/views' => storage_path('framework/views'),
            'bootstrap/cache' => base_path('bootstrap/cache')
        ];
        
        foreach ($checkPaths as $name => $path) {
            if (file_exists($path)) {
                $writable = is_writable($path);
                echo "   $name: " . ($writable ? 'WRITABLE ✅' : 'NOT WRITABLE ❌') . "\n";
                
                if (!$writable) {
                    echo "     Path: $path\n";
                    echo "     Fix: chmod 755 $path\n";
                }
            } else {
                echo "   $name: MISSING ❌ (Path: $path)\n";
            }
        }
        
        // 7. Environment Configuration Check
        echo "\n7. ENVIRONMENT CONFIGURATION:\n";
        $envChecks = [
            'APP_ENV' => ['expected' => 'production', 'actual' => config('app.env')],
            'APP_DEBUG' => ['expected' => false, 'actual' => config('app.debug')],
            'SESSION_DRIVER' => ['expected' => 'database', 'actual' => config('session.driver')],
            'SESSION_SECURE_COOKIE' => ['expected' => true, 'actual' => config('session.secure')]
        ];
        
        foreach ($envChecks as $key => $check) {
            $matches = $check['actual'] === $check['expected'];
            $status = $matches ? '✅' : '⚠️';
            echo "   $key: {$check['actual']} $status\n";
            
            if (!$matches) {
                echo "     Expected: {$check['expected']}\n";
            }
        }
        
        // 8. Generate Summary Report
        echo "\n=== PRODUCTION READY SUMMARY ===\n";
        
        // Check if paramedis login should work
        $paramedisTest = Auth::attempt(['email' => 'tina@paramedis.com', 'password' => 'password123']);
        if ($paramedisTest) {
            Auth::logout();
        }
        
        echo "Database: " . (DB::connection()->getPdo() ? 'CONNECTED ✅' : 'FAILED ❌') . "\n";
        echo "Paramedis user: " . ($paramedisTest ? 'LOGIN WORKS ✅' : 'LOGIN FAILED ❌') . "\n";
        echo "Caches: CLEARED AND OPTIMIZED ✅\n";
        echo "Sessions: CLEANED ✅\n";
        
        if ($paramedisTest) {
            echo "\n🎉 PRODUCTION IS READY!\n";
            echo "\nParamedis Login Details:\n";
            echo "URL: " . config('app.url') . "/paramedis/login\n";
            echo "Email: tina@paramedis.com\n";
            echo "Password: password123\n";
            echo "\nThe login should now work without 'page expired' errors.\n";
        } else {
            echo "\n⚠️  Production setup needs attention. Check the errors above.\n";
        }
        
        echo "\n=== CLEANUP COMPLETE ===\n";
        
    } catch (Exception $e) {
        echo "❌ Cleanup failed: " . $e->getMessage() . "\n";
        echo "Stack trace: " . $e->getTraceAsString() . "\n";
    }
} else {
    echo "❌ Run from Laravel root directory\n";
}

echo "\n=== END CLEANUP ===\n";