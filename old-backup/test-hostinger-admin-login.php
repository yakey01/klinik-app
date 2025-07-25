<?php
/**
 * Test script untuk menguji login admin di Hostinger
 * Jalankan script ini di server Hostinger untuk test login functionality
 */

// Simulasi test login admin
echo "=== TEST ADMIN LOGIN HOSTINGER ===\n";
echo "Tanggal: " . date('Y-m-d H:i:s') . "\n";
echo "===================================\n\n";

// Test 1: Cek koneksi database
echo "1. Testing database connection...\n";
try {
    require_once __DIR__ . '/vendor/autoload.php';
    
    $app = require_once __DIR__ . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    // Test database connection
    $pdo = DB::connection()->getPdo();
    echo "✅ Database connection: SUCCESS\n";
    
    // Test users table
    $userCount = DB::table('users')->count();
    echo "✅ Users table accessible: {$userCount} users found\n";
    
} catch (Exception $e) {
    echo "❌ Database connection: FAILED - " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Cek admin user exists
echo "\n2. Testing admin user existence...\n";
try {
    $admin = DB::table('users')->where('email', 'admin@dokterku.com')->first();
    if ($admin) {
        echo "✅ Admin user found: ID {$admin->id}, Email: {$admin->email}\n";
        echo "   Role: {$admin->role}\n";
        echo "   Created: {$admin->created_at}\n";
    } else {
        echo "❌ Admin user NOT found with email admin@dokterku.com\n";
        
        // Check for other admin users
        $admins = DB::table('users')->where('role', 'admin')->get();
        if ($admins->count() > 0) {
            echo "   Found other admin users:\n";
            foreach ($admins as $user) {
                echo "   - ID: {$user->id}, Email: {$user->email}\n";
            }
        }
    }
} catch (Exception $e) {
    echo "❌ Admin user check: FAILED - " . $e->getMessage() . "\n";
}

// Test 3: Test password verification
echo "\n3. Testing password verification...\n";
try {
    $admin = DB::table('users')->where('email', 'admin@dokterku.com')->first();
    if ($admin) {
        $testPassword = 'admin123'; // Default password yang sering digunakan
        
        if (Hash::check($testPassword, $admin->password)) {
            echo "✅ Password verification: SUCCESS with password 'admin123'\n";
        } else {
            echo "❌ Password verification: FAILED with password 'admin123'\n";
            echo "   Current hash: " . substr($admin->password, 0, 20) . "...\n";
            
            // Try to create a new hash for testing
            $newHash = Hash::make('admin123');
            echo "   New hash would be: " . substr($newHash, 0, 20) . "...\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Password verification: FAILED - " . $e->getMessage() . "\n";
}

// Test 4: Test authentication config
echo "\n4. Testing authentication configuration...\n";
try {
    $authGuard = config('auth.defaults.guard');
    $authProvider = config('auth.defaults.provider');
    
    echo "✅ Auth guard: {$authGuard}\n";
    echo "✅ Auth provider: {$authProvider}\n";
    
    $providerConfig = config("auth.providers.{$authProvider}");
    echo "✅ Provider config: " . json_encode($providerConfig) . "\n";
    
} catch (Exception $e) {
    echo "❌ Auth config check: FAILED - " . $e->getMessage() . "\n";
}

// Test 5: Test session configuration
echo "\n5. Testing session configuration...\n";
try {
    $sessionDriver = config('session.driver');
    $sessionLifetime = config('session.lifetime');
    
    echo "✅ Session driver: {$sessionDriver}\n";
    echo "✅ Session lifetime: {$sessionLifetime} minutes\n";
    
} catch (Exception $e) {
    echo "❌ Session config check: FAILED - " . $e->getMessage() . "\n";
}

// Test 6: Test hasRole method if User model exists
echo "\n6. Testing User model hasRole method...\n";
try {
    $admin = App\Models\User::where('email', 'admin@dokterku.com')->first();
    if ($admin) {
        $hasAdminRole = $admin->hasRole('admin');
        echo "✅ hasRole('admin'): " . ($hasAdminRole ? 'TRUE' : 'FALSE') . "\n";
        
        // Test available roles
        if (method_exists($admin, 'getRoles')) {
            $roles = $admin->getRoles();
            echo "✅ User roles: " . implode(', ', $roles) . "\n";
        }
    }
} catch (Exception $e) {
    echo "❌ hasRole method test: FAILED - " . $e->getMessage() . "\n";
}

echo "\n=== TEST COMPLETED ===\n";
echo "Silakan jalankan script ini di server Hostinger dengan:\n";
echo "php test-hostinger-admin-login.php\n";