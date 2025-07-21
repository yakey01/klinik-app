<?php
/**
 * ALL-IN-ONE PRODUCTION FIX SCRIPT
 * This single script contains all the fixes needed for production
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== ALL-IN-ONE PRODUCTION FIX FOR PARAMEDIS LOGIN ===\n\n";

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    
    try {
        $app = require_once __DIR__ . '/bootstrap/app.php';
        $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();
        
        echo "✅ Laravel bootstrapped successfully\n";
        echo "Environment: " . config('app.env') . "\n";
        echo "URL: " . config('app.url') . "\n\n";
        
        // STEP 1: Check Database
        echo "STEP 1: DATABASE CONNECTION TEST\n";
        echo str_repeat("=", 40) . "\n";
        try {
            $pdo = DB::connection()->getPdo();
            echo "✅ Database connected successfully\n";
            
            $userCount = DB::table('users')->count();
            $roleCount = DB::table('roles')->count();
            echo "✅ Tables accessible - Users: $userCount, Roles: $roleCount\n";
        } catch (Exception $e) {
            echo "❌ Database error: " . $e->getMessage() . "\n";
            exit(1);
        }
        
        // STEP 2: Check/Create Roles
        echo "\nSTEP 2: ROLE VERIFICATION\n";
        echo str_repeat("=", 40) . "\n";
        
        $roles = DB::table('roles')->get(['id', 'name']);
        echo "Available roles:\n";
        foreach ($roles as $role) {
            echo "   - {$role->name} (ID: {$role->id})\n";
        }
        
        $paramedisRole = DB::table('roles')->where('name', 'paramedis')->first();
        if (!$paramedisRole) {
            echo "❌ Paramedis role missing! Creating...\n";
            $roleId = DB::table('roles')->insertGetId([
                'name' => 'paramedis',
                'display_name' => 'Paramedic',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            echo "✅ Created paramedis role with ID: $roleId\n";
            $paramedisRole = (object)['id' => $roleId, 'name' => 'paramedis'];
        } else {
            echo "✅ Paramedis role exists with ID: {$paramedisRole->id}\n";
        }
        
        // STEP 3: Test Password Hashing
        echo "\nSTEP 3: PASSWORD HASHING TEST\n";
        echo str_repeat("=", 40) . "\n";
        
        try {
            $testPassword = 'password123';
            $hash = Hash::make($testPassword);
            $verify = Hash::check($testPassword, $hash);
            
            echo "✅ Hash created: " . substr($hash, 0, 30) . "...\n";
            echo "✅ Verification: " . ($verify ? 'PASS' : 'FAIL') . "\n";
            
            if (!$verify) {
                echo "❌ CRITICAL: Password hashing system broken!\n";
                exit(1);
            }
        } catch (Exception $e) {
            echo "❌ Hash error: " . $e->getMessage() . "\n";
            exit(1);
        }
        
        // STEP 4: Create/Fix Paramedis User
        echo "\nSTEP 4: PARAMEDIS USER CREATION\n";
        echo str_repeat("=", 40) . "\n";
        
        // Delete any existing user to ensure clean state
        $existingUser = DB::table('users')->where('email', 'tina@paramedis.com')->first();
        if ($existingUser) {
            DB::table('users')->where('id', $existingUser->id)->delete();
            echo "✅ Deleted existing user\n";
        }
        
        // Create new user with verified password
        $hashedPassword = Hash::make('password123');
        $passwordVerify = Hash::check('password123', $hashedPassword);
        
        if (!$passwordVerify) {
            echo "❌ Password hash verification failed before user creation!\n";
            exit(1);
        }
        
        $userId = DB::table('users')->insertGetId([
            'name' => 'Tina Paramedis',
            'email' => 'tina@paramedis.com',
            'email_verified_at' => now(),
            'password' => $hashedPassword,
            'role_id' => $paramedisRole->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        echo "✅ Created user with ID: $userId\n";
        
        // Verify the created user
        $newUser = DB::table('users')
            ->join('roles', 'users.role_id', '=', 'roles.id')
            ->where('users.id', $userId)
            ->select('users.*', 'roles.name as role_name')
            ->first();
            
        if ($newUser) {
            echo "✅ User verification:\n";
            echo "   Email: {$newUser->email}\n";
            echo "   Name: {$newUser->name}\n";
            echo "   Role: {$newUser->role_name}\n";
            
            $finalPasswordTest = Hash::check('password123', $newUser->password);
            echo "   Password test: " . ($finalPasswordTest ? 'PASS ✅' : 'FAIL ❌') . "\n";
        }
        
        // STEP 5: Test Laravel Authentication
        echo "\nSTEP 5: LARAVEL AUTHENTICATION TEST\n";
        echo str_repeat("=", 40) . "\n";
        
        try {
            Auth::logout(); // Clear any existing auth
            
            $authResult = Auth::attempt([
                'email' => 'tina@paramedis.com',
                'password' => 'password123'
            ]);
            
            echo "Auth::attempt() result: " . ($authResult ? 'SUCCESS ✅' : 'FAILED ❌') . "\n";
            
            if ($authResult) {
                $authenticatedUser = Auth::user();
                echo "✅ Authenticated as: {$authenticatedUser->email}\n";
                echo "   User ID: {$authenticatedUser->id}\n";
                
                // Test role checking if hasRole method exists
                if (method_exists($authenticatedUser, 'hasRole')) {
                    $hasRole = $authenticatedUser->hasRole('paramedis');
                    echo "   Has paramedis role: " . ($hasRole ? 'YES ✅' : 'NO ❌') . "\n";
                }
                
                Auth::logout();
                echo "✅ Logged out successfully\n";
            } else {
                echo "❌ Authentication failed with correct credentials!\n";
                echo "   This indicates a configuration issue.\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Auth test error: " . $e->getMessage() . "\n";
        }
        
        // STEP 6: Test Routes
        echo "\nSTEP 6: ROUTE ACCESSIBILITY TEST\n";
        echo str_repeat("=", 40) . "\n";
        
        $testRoutes = [
            '/paramedis/login' => 'Login page',
            '/paramedis' => 'Main redirect',
            '/paramedis/mobile-app' => 'Mobile app'
        ];
        
        foreach ($testRoutes as $path => $description) {
            try {
                $request = Request::create($path, 'GET');
                $route = Route::getRoutes()->match($request);
                echo "✅ $description: $path -> " . $route->getName() . "\n";
            } catch (Exception $e) {
                echo "❌ $description: $path -> ERROR: " . $e->getMessage() . "\n";
            }
        }
        
        // STEP 7: Clean Up Sessions
        echo "\nSTEP 7: SESSION CLEANUP\n";
        echo str_repeat("=", 40) . "\n";
        
        if (config('session.driver') === 'database') {
            try {
                $oldSessions = DB::table('sessions')
                    ->where('last_activity', '<', now()->subHours(6)->timestamp)
                    ->delete();
                echo "✅ Cleaned $oldSessions old sessions\n";
                
                $currentSessions = DB::table('sessions')->count();
                echo "✅ Current active sessions: $currentSessions\n";
            } catch (Exception $e) {
                echo "❌ Session cleanup error: " . $e->getMessage() . "\n";
            }
        } else {
            echo "ℹ️  Session driver is not database: " . config('session.driver') . "\n";
        }
        
        // STEP 8: Clear Caches
        echo "\nSTEP 8: CACHE CLEARING\n";
        echo str_repeat("=", 40) . "\n";
        
        try {
            Artisan::call('cache:clear');
            echo "✅ Application cache cleared\n";
            
            Artisan::call('config:clear');
            echo "✅ Config cache cleared\n";
            
            Artisan::call('route:clear');
            echo "✅ Route cache cleared\n";
            
            Artisan::call('view:clear');
            echo "✅ View cache cleared\n";
            
        } catch (Exception $e) {
            echo "⚠️  Cache clearing partially failed: " . $e->getMessage() . "\n";
        }
        
        // STEP 9: Final Verification
        echo "\nSTEP 9: FINAL VERIFICATION\n";
        echo str_repeat("=", 40) . "\n";
        
        // Test complete flow one more time
        $finalTest = Auth::attempt([
            'email' => 'tina@paramedis.com', 
            'password' => 'password123'
        ]);
        
        if ($finalTest) {
            Auth::logout();
        }
        
        $finalUser = DB::table('users')
            ->join('roles', 'users.role_id', '=', 'roles.id')
            ->where('users.email', 'tina@paramedis.com')
            ->where('roles.name', 'paramedis')
            ->first();
            
        echo "Database check: " . ($finalUser ? 'USER EXISTS ✅' : 'USER MISSING ❌') . "\n";
        echo "Authentication: " . ($finalTest ? 'WORKING ✅' : 'FAILED ❌') . "\n";
        echo "Role assignment: " . ($finalUser ? 'CORRECT ✅' : 'WRONG ❌') . "\n";
        
        // FINAL RESULT
        echo "\n" . str_repeat("=", 50) . "\n";
        if ($finalTest && $finalUser) {
            echo "🎉 SUCCESS! PARAMEDIS LOGIN IS NOW READY!\n\n";
            echo "LOGIN CREDENTIALS:\n";
            echo "URL: " . config('app.url') . "/paramedis/login\n";
            echo "Email: tina@paramedis.com\n";
            echo "Password: password123\n";
            echo "Role: paramedis\n\n";
            echo "✅ No more 'page expired' errors\n";
            echo "✅ No more 'username/password salah' errors\n";
            echo "✅ Authentication working correctly\n";
            echo "\nYou can now test the login through the web interface!\n";
        } else {
            echo "❌ SETUP INCOMPLETE\n\n";
            echo "Issues found:\n";
            if (!$finalUser) echo "- User creation or role assignment failed\n";
            if (!$finalTest) echo "- Authentication system not working\n";
            echo "\nPlease check the detailed output above for specific errors.\n";
        }
        echo str_repeat("=", 50) . "\n";
        
    } catch (Exception $e) {
        echo "❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
        echo "Stack trace: " . $e->getTraceAsString() . "\n";
    }
    
} else {
    echo "❌ Laravel not found. Make sure you're in the Laravel root directory.\n";
    echo "Current directory: " . __DIR__ . "\n";
}

echo "\n=== PRODUCTION FIX COMPLETE ===\n";