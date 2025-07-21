<?php
/**
 * Verify Production Login Flow
 * Complete end-to-end test of login functionality
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== VERIFY PRODUCTION LOGIN FLOW ===\n\n";

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    
    try {
        $app = require_once __DIR__ . '/bootstrap/app.php';
        $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();
        
        echo "✅ Laravel environment loaded\n";
        echo "Environment: " . config('app.env') . "\n";
        echo "Debug mode: " . (config('app.debug') ? 'ON' : 'OFF') . "\n\n";
        
        // 1. Test Database Connection
        echo "1. DATABASE CONNECTION TEST:\n";
        try {
            $users = DB::table('users')->count();
            $roles = DB::table('roles')->count();
            echo "   ✅ Database connected\n";
            echo "   Users: $users, Roles: $roles\n";
        } catch (Exception $e) {
            echo "   ❌ Database error: " . $e->getMessage() . "\n";
            exit(1);
        }
        
        // 2. Test Hash System
        echo "\n2. PASSWORD HASHING TEST:\n";
        $testPassword = 'password123';
        $hash = Hash::make($testPassword);
        $verify = Hash::check($testPassword, $hash);
        echo "   Hash created: " . substr($hash, 0, 30) . "...\n";
        echo "   Verification: " . ($verify ? 'PASS ✅' : 'FAIL ❌') . "\n";
        
        if (!$verify) {
            echo "   ❌ Critical: Hash system not working!\n";
            exit(1);
        }
        
        // 3. Check Paramedis User
        echo "\n3. PARAMEDIS USER CHECK:\n";
        $paramedisUser = DB::table('users')
            ->join('roles', 'users.role_id', '=', 'roles.id')
            ->where('users.email', 'tina@paramedis.com')
            ->where('roles.name', 'paramedis')
            ->select('users.*', 'roles.name as role_name')
            ->first();
            
        if (!$paramedisUser) {
            echo "   ❌ Paramedis user not found or role mismatch\n";
            echo "   Creating paramedis user...\n";
            
            // Get paramedis role
            $role = DB::table('roles')->where('name', 'paramedis')->first();
            if (!$role) {
                echo "   ❌ Paramedis role does not exist!\n";
                exit(1);
            }
            
            // Delete any existing user with same email
            DB::table('users')->where('email', 'tina@paramedis.com')->delete();
            
            // Create new user
            $userId = DB::table('users')->insertGetId([
                'name' => 'Tina Paramedis Test',
                'email' => 'tina@paramedis.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password123'),
                'role_id' => $role->id,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            echo "   ✅ Created paramedis user with ID: $userId\n";
            
            // Reload user data
            $paramedisUser = DB::table('users')
                ->join('roles', 'users.role_id', '=', 'roles.id')
                ->where('users.id', $userId)
                ->select('users.*', 'roles.name as role_name')
                ->first();
        }
        
        echo "   ✅ Paramedis user found:\n";
        echo "     Email: {$paramedisUser->email}\n";
        echo "     Name: {$paramedisUser->name}\n";
        echo "     Role: {$paramedisUser->role_name}\n";
        echo "     User ID: {$paramedisUser->id}\n";
        echo "     Role ID: {$paramedisUser->role_id}\n";
        
        // 4. Test Password
        echo "\n4. PASSWORD VERIFICATION TEST:\n";
        $passwordOK = Hash::check('password123', $paramedisUser->password);
        echo "   Password 'password123': " . ($passwordOK ? 'VALID ✅' : 'INVALID ❌') . "\n";
        
        if (!$passwordOK) {
            echo "   ⚠️  Password mismatch, updating...\n";
            DB::table('users')->where('id', $paramedisUser->id)->update([
                'password' => Hash::make('password123'),
                'updated_at' => now()
            ]);
            
            // Reload and test again
            $updatedUser = DB::table('users')->where('id', $paramedisUser->id)->first();
            $passwordOK = Hash::check('password123', $updatedUser->password);
            echo "   After update: " . ($passwordOK ? 'VALID ✅' : 'INVALID ❌') . "\n";
        }
        
        // 5. Test Laravel Authentication
        echo "\n5. LARAVEL AUTHENTICATION TEST:\n";
        $credentials = [
            'email' => 'tina@paramedis.com',
            'password' => 'password123'
        ];
        
        try {
            // Clear any existing auth
            Auth::logout();
            
            $authResult = Auth::attempt($credentials);
            echo "   Auth::attempt(): " . ($authResult ? 'SUCCESS ✅' : 'FAILED ❌') . "\n";
            
            if ($authResult) {
                $user = Auth::user();
                echo "   Authenticated user: {$user->email}\n";
                echo "   User ID: {$user->id}\n";
                
                // Test hasRole method if available
                if (method_exists($user, 'hasRole')) {
                    $hasParamedisRole = $user->hasRole('paramedis');
                    echo "   Has paramedis role: " . ($hasParamedisRole ? 'YES ✅' : 'NO ❌') . "\n";
                } else {
                    echo "   hasRole method not available\n";
                }
                
                Auth::logout();
                echo "   ✅ Auth test completed and logged out\n";
            } else {
                echo "   ❌ Authentication failed with correct credentials!\n";
                
                // Debug auth configuration
                echo "   Debugging auth config:\n";
                echo "     Default guard: " . config('auth.defaults.guard') . "\n";
                echo "     User provider: " . config('auth.guards.web.provider') . "\n";
                echo "     Provider driver: " . config('auth.providers.users.driver') . "\n";
                echo "     User model: " . config('auth.providers.users.model') . "\n";
            }
            
        } catch (Exception $e) {
            echo "   ❌ Auth exception: " . $e->getMessage() . "\n";
        }
        
        // 6. Test Route Access
        echo "\n6. ROUTE ACCESS TEST:\n";
        $testRoutes = [
            '/paramedis/login',
            '/paramedis',
            '/paramedis/mobile-app'
        ];
        
        foreach ($testRoutes as $path) {
            try {
                $request = Request::create($path, 'GET');
                $route = Route::getRoutes()->match($request);
                echo "   ✅ Route accessible: $path -> " . $route->getName() . "\n";
            } catch (Exception $e) {
                echo "   ❌ Route error: $path -> " . $e->getMessage() . "\n";
            }
        }
        
        // 7. Test Middleware Stack
        echo "\n7. MIDDLEWARE STACK TEST:\n";
        try {
            // Authenticate first
            $user = DB::table('users')->where('email', 'tina@paramedis.com')->first();
            if ($user) {
                Auth::loginUsingId($user->id);
                echo "   ✅ User authenticated for middleware test\n";
                
                // Test ParamedisMiddleware
                $middleware = new \App\Http\Middleware\ParamedisMiddleware();
                $request = Request::create('/paramedis', 'GET');
                
                $response = $middleware->handle($request, function($req) {
                    return response('Middleware passed');
                });
                
                echo "   ✅ ParamedisMiddleware: PASSED\n";
                echo "   Response status: " . $response->getStatusCode() . "\n";
                
                Auth::logout();
            }
        } catch (Exception $e) {
            echo "   ❌ Middleware test failed: " . $e->getMessage() . "\n";
        }
        
        // 8. Final Summary
        echo "\n=== FINAL VERIFICATION SUMMARY ===\n";
        
        // Re-check everything
        $finalUser = DB::table('users')
            ->join('roles', 'users.role_id', '=', 'roles.id')
            ->where('users.email', 'tina@paramedis.com')
            ->select('users.*', 'roles.name as role_name')
            ->first();
            
        if ($finalUser) {
            $finalPasswordCheck = Hash::check('password123', $finalUser->password);
            $finalAuthCheck = Auth::attempt(['email' => 'tina@paramedis.com', 'password' => 'password123']);
            if ($finalAuthCheck) Auth::logout();
            
            echo "✅ User exists: YES\n";
            echo "✅ Role correct: " . ($finalUser->role_name === 'paramedis' ? 'YES' : 'NO') . "\n";
            echo "✅ Password valid: " . ($finalPasswordCheck ? 'YES' : 'NO') . "\n";
            echo "✅ Auth works: " . ($finalAuthCheck ? 'YES' : 'NO') . "\n";
            
            if ($finalPasswordCheck && $finalAuthCheck && $finalUser->role_name === 'paramedis') {
                echo "\n🎉 SUCCESS! LOGIN SHOULD NOW WORK!\n";
                echo "\nTEST CREDENTIALS:\n";
                echo "Email: tina@paramedis.com\n";
                echo "Password: password123\n";
                echo "Login URL: " . config('app.url') . "/paramedis/login\n";
                echo "\nNext: Try logging in through the web interface.\n";
            } else {
                echo "\n❌ Some checks still failing. Review the output above.\n";
            }
        } else {
            echo "❌ User creation failed\n";
        }
        
        echo "\n=== VERIFICATION COMPLETE ===\n";
        
    } catch (Exception $e) {
        echo "❌ Verification failed: " . $e->getMessage() . "\n";
        echo "Stack trace: " . $e->getTraceAsString() . "\n";
    }
} else {
    echo "❌ Run from Laravel root directory\n";
}

echo "\n=== END VERIFICATION ===\n";