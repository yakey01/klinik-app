<?php
/**
 * Authentication Fix Script for Production
 * Fixes user creation and password issues
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== AUTHENTICATION FIX FOR PRODUCTION ===\n\n";

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    
    try {
        $app = require_once __DIR__ . '/bootstrap/app.php';
        $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();
        
        echo "✅ Laravel bootstrapped\n";
        
        // Check if Hash facade works
        try {
            $testHash = Hash::make('test123');
            $testVerify = Hash::check('test123', $testHash);
            echo "✅ Hash facade working: " . ($testVerify ? 'YES' : 'NO') . "\n";
        } catch (Exception $e) {
            echo "❌ Hash facade error: " . $e->getMessage() . "\n";
        }
        
        // 1. Check existing users
        echo "\n1. CHECKING EXISTING USERS:\n";
        $users = DB::table('users')
            ->leftJoin('roles', 'users.role_id', '=', 'roles.id')
            ->select('users.*', 'roles.name as role_name')
            ->get();
            
        echo "   Total users in database: " . count($users) . "\n";
        foreach ($users as $user) {
            echo "   - {$user->email} ({$user->name}) -> Role: " . ($user->role_name ?? 'NO ROLE') . "\n";
        }
        
        // 2. Check if tina user exists
        echo "\n2. CHECKING TINA USER:\n";
        $tinaUser = DB::table('users')->where('email', 'tina@paramedis.com')->first();
        if ($tinaUser) {
            echo "   ✅ User exists with ID: {$tinaUser->id}\n";
            echo "   Name: {$tinaUser->name}\n";
            echo "   Email: {$tinaUser->email}\n";
            echo "   Role ID: {$tinaUser->role_id}\n";
            echo "   Created: {$tinaUser->created_at}\n";
            echo "   Updated: {$tinaUser->updated_at}\n";
            echo "   Password hash: " . substr($tinaUser->password, 0, 20) . "...\n";
            echo "   Hash length: " . strlen($tinaUser->password) . " chars\n";
            
            // Test password verification
            echo "\n   PASSWORD VERIFICATION TEST:\n";
            $passwords = ['password123', 'Password123', 'PASSWORD123', 'password', '123456'];
            foreach ($passwords as $testPassword) {
                $isValid = Hash::check($testPassword, $tinaUser->password);
                echo "     '$testPassword': " . ($isValid ? 'VALID ✅' : 'INVALID ❌') . "\n";
            }
        } else {
            echo "   ❌ User does not exist\n";
        }
        
        // 3. Get paramedis role
        echo "\n3. CHECKING PARAMEDIS ROLE:\n";
        $paramedisRole = DB::table('roles')->where('name', 'paramedis')->first();
        if ($paramedisRole) {
            echo "   ✅ Paramedis role exists with ID: {$paramedisRole->id}\n";
            echo "   Name: {$paramedisRole->name}\n";
            echo "   Display name: " . ($paramedisRole->display_name ?? 'N/A') . "\n";
        } else {
            echo "   ❌ Paramedis role does not exist!\n";
            
            // Create paramedis role if missing
            echo "   Creating paramedis role...\n";
            $roleId = DB::table('roles')->insertGetId([
                'name' => 'paramedis',
                'display_name' => 'Paramedic',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            echo "   ✅ Created paramedis role with ID: $roleId\n";
            $paramedisRole = (object)['id' => $roleId, 'name' => 'paramedis'];
        }
        
        // 4. Create/Update user with correct password
        echo "\n4. CREATING/UPDATING USER:\n";
        
        // Delete existing user first to ensure clean state
        if ($tinaUser) {
            DB::table('users')->where('id', $tinaUser->id)->delete();
            echo "   ✅ Deleted existing user\n";
        }
        
        // Create new user with verified password
        $hashedPassword = Hash::make('password123');
        echo "   Generated password hash: " . substr($hashedPassword, 0, 30) . "...\n";
        
        // Verify the hash immediately
        $hashVerification = Hash::check('password123', $hashedPassword);
        echo "   Hash verification: " . ($hashVerification ? 'PASS ✅' : 'FAIL ❌') . "\n";
        
        if (!$hashVerification) {
            echo "   ❌ Hash verification failed! There's an issue with password hashing.\n";
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
        
        echo "   ✅ Created new user with ID: $userId\n";
        
        // 5. Verify the new user
        echo "\n5. VERIFYING NEW USER:\n";
        $newUser = DB::table('users')
            ->join('roles', 'users.role_id', '=', 'roles.id')
            ->where('users.id', $userId)
            ->select('users.*', 'roles.name as role_name')
            ->first();
            
        if ($newUser) {
            echo "   ✅ User verification successful:\n";
            echo "     ID: {$newUser->id}\n";
            echo "     Email: {$newUser->email}\n";
            echo "     Name: {$newUser->name}\n";
            echo "     Role: {$newUser->role_name}\n";
            
            // Test password again
            $finalPasswordTest = Hash::check('password123', $newUser->password);
            echo "     Password test: " . ($finalPasswordTest ? 'PASS ✅' : 'FAIL ❌') . "\n";
            
            if ($finalPasswordTest) {
                echo "\n   🎉 USER CREATION SUCCESSFUL!\n";
                echo "   Credentials:\n";
                echo "     Email: tina@paramedis.com\n";
                echo "     Password: password123\n";
                echo "     Role: paramedis\n";
            } else {
                echo "\n   ❌ Password verification still failing!\n";
            }
        }
        
        // 6. Test Laravel Auth
        echo "\n6. TESTING LARAVEL AUTH:\n";
        try {
            $credentials = [
                'email' => 'tina@paramedis.com',
                'password' => 'password123'
            ];
            
            $authAttempt = Auth::attempt($credentials);
            echo "   Laravel Auth::attempt(): " . ($authAttempt ? 'SUCCESS ✅' : 'FAILED ❌') . "\n";
            
            if ($authAttempt) {
                $authenticatedUser = Auth::user();
                echo "   Authenticated user: {$authenticatedUser->email}\n";
                echo "   User ID: {$authenticatedUser->id}\n";
                Auth::logout();
            } else {
                echo "   ❌ Authentication failed even with correct credentials\n";
                
                // Debug Auth provider
                $authConfig = config('auth');
                echo "   Auth guard: " . $authConfig['defaults']['guard'] . "\n";
                echo "   Auth provider: " . $authConfig['defaults']['provider'] . "\n";
                echo "   User provider: " . $authConfig['providers']['users']['driver'] . "\n";
                echo "   User model: " . $authConfig['providers']['users']['model'] . "\n";
            }
            
        } catch (Exception $e) {
            echo "   ❌ Auth test error: " . $e->getMessage() . "\n";
        }
        
        // 7. Additional debugging info
        echo "\n7. ADDITIONAL INFO:\n";
        echo "   PHP Version: " . PHP_VERSION . "\n";
        echo "   Laravel Version: " . app()->version() . "\n";
        echo "   Hash driver: " . config('hashing.driver') . "\n";
        echo "   Bcrypt rounds: " . config('hashing.bcrypt.rounds') . "\n";
        
        echo "\n=== AUTHENTICATION FIX COMPLETE ===\n";
        
        if ($finalPasswordTest && $authAttempt) {
            echo "🎉 SUCCESS! User should now be able to login.\n";
        } else {
            echo "❌ There are still issues. Check the output above for specific problems.\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Script failed: " . $e->getMessage() . "\n";
        echo "Stack trace: " . $e->getTraceAsString() . "\n";
    }
} else {
    echo "❌ Run from Laravel root directory\n";
}

echo "\n=== END AUTHENTICATION FIX ===\n";