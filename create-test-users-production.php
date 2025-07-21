<?php
/**
 * Create Multiple Test Users for Production
 * Creates users for all roles to test authentication
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== CREATE MULTIPLE TEST USERS FOR PRODUCTION ===\n\n";

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    
    try {
        $app = require_once __DIR__ . '/bootstrap/app.php';
        $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();
        
        echo "✅ Laravel bootstrapped\n";
        
        // Get all roles
        $roles = DB::table('roles')->get(['id', 'name', 'display_name']);
        echo "✅ Available roles: " . count($roles) . "\n";
        
        foreach ($roles as $role) {
            echo "   - {$role->name} (ID: {$role->id})\n";
        }
        
        // Define test users for each role
        $testUsers = [
            [
                'name' => 'Test Admin',
                'email' => 'admin@test.com',
                'password' => 'admin123',
                'role' => 'admin'
            ],
            [
                'name' => 'Tina Paramedis',
                'email' => 'tina@paramedis.com',
                'password' => 'password123',
                'role' => 'paramedis'
            ],
            [
                'name' => 'Test Bendahara',
                'email' => 'bendahara@test.com', 
                'password' => 'bendahara123',
                'role' => 'bendahara'
            ],
            [
                'name' => 'Test Manajer',
                'email' => 'manajer@test.com',
                'password' => 'manajer123', 
                'role' => 'manajer'
            ],
            [
                'name' => 'Test Petugas',
                'email' => 'petugas@test.com',
                'password' => 'petugas123',
                'role' => 'petugas'
            ],
            [
                'name' => 'Test Dokter',
                'email' => 'dokter@test.com',
                'password' => 'dokter123',
                'role' => 'dokter'
            ]
        ];
        
        echo "\n=== CREATING TEST USERS ===\n";
        
        foreach ($testUsers as $userData) {
            echo "\nProcessing: {$userData['email']} ({$userData['role']})...\n";
            
            // Find role
            $role = collect($roles)->firstWhere('name', $userData['role']);
            if (!$role) {
                echo "   ❌ Role '{$userData['role']}' not found\n";
                continue;
            }
            
            // Check if user already exists
            $existingUser = DB::table('users')->where('email', $userData['email'])->first();
            if ($existingUser) {
                echo "   ⚠️  User exists, updating...\n";
                
                // Update existing user
                DB::table('users')->where('id', $existingUser->id)->update([
                    'name' => $userData['name'],
                    'password' => Hash::make($userData['password']),
                    'role_id' => $role->id,
                    'email_verified_at' => now(),
                    'updated_at' => now()
                ]);
                
                $userId = $existingUser->id;
                echo "   ✅ Updated user ID: $userId\n";
            } else {
                echo "   📝 Creating new user...\n";
                
                // Create new user
                $userId = DB::table('users')->insertGetId([
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'email_verified_at' => now(),
                    'password' => Hash::make($userData['password']),
                    'role_id' => $role->id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                echo "   ✅ Created user ID: $userId\n";
            }
            
            // Verify password
            $user = DB::table('users')->where('id', $userId)->first();
            $passwordCheck = Hash::check($userData['password'], $user->password);
            echo "   Password verification: " . ($passwordCheck ? 'PASS ✅' : 'FAIL ❌') . "\n";
            
            // Test Laravel Auth
            try {
                $authAttempt = Auth::attempt([
                    'email' => $userData['email'],
                    'password' => $userData['password']
                ]);
                echo "   Laravel Auth test: " . ($authAttempt ? 'PASS ✅' : 'FAIL ❌') . "\n";
                
                if ($authAttempt) {
                    Auth::logout();
                }
            } catch (Exception $e) {
                echo "   Auth test error: " . $e->getMessage() . "\n";
            }
        }
        
        // Summary
        echo "\n=== FINAL USER SUMMARY ===\n";
        $allUsers = DB::table('users')
            ->leftJoin('roles', 'users.role_id', '=', 'roles.id')
            ->select('users.email', 'users.name', 'roles.name as role_name')
            ->orderBy('users.email')
            ->get();
            
        echo "Total users in database: " . count($allUsers) . "\n\n";
        
        echo "TEST CREDENTIALS:\n";
        echo str_repeat("=", 50) . "\n";
        
        foreach ($testUsers as $userData) {
            $userInDb = $allUsers->firstWhere('email', $userData['email']);
            if ($userInDb) {
                echo sprintf("%-25s | %-15s | %s\n", 
                    $userData['email'], 
                    $userData['password'],
                    $userInDb->role_name ?? 'NO ROLE'
                );
            }
        }
        
        echo "\n=== SPECIAL FOCUS: PARAMEDIS USER ===\n";
        $paramedisUser = DB::table('users')
            ->join('roles', 'users.role_id', '=', 'roles.id')
            ->where('users.email', 'tina@paramedis.com')
            ->select('users.*', 'roles.name as role_name')
            ->first();
            
        if ($paramedisUser) {
            echo "✅ Paramedis user ready:\n";
            echo "   Email: {$paramedisUser->email}\n";
            echo "   Password: password123\n";
            echo "   Role: {$paramedisUser->role_name}\n";
            echo "   User ID: {$paramedisUser->id}\n";
            echo "   Role ID: {$paramedisUser->role_id}\n";
            
            // Final auth test
            $finalAuthTest = Auth::attempt([
                'email' => 'tina@paramedis.com',
                'password' => 'password123'
            ]);
            echo "   Final auth test: " . ($finalAuthTest ? 'SUCCESS ✅' : 'FAILED ❌') . "\n";
            
            if ($finalAuthTest) {
                Auth::logout();
                echo "\n🎉 PARAMEDIS LOGIN SHOULD NOW WORK!\n";
                echo "   Try logging in at: /paramedis/login\n";
            }
        } else {
            echo "❌ Paramedis user creation failed\n";
        }
        
        echo "\n=== USER CREATION COMPLETE ===\n";
        
    } catch (Exception $e) {
        echo "❌ Script failed: " . $e->getMessage() . "\n";
        echo "Stack trace: " . $e->getTraceAsString() . "\n";
    }
} else {
    echo "❌ Run from Laravel root directory\n";
}

echo "\n=== END USER CREATION ===\n";