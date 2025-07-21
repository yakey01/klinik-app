<?php
/**
 * Production Paramedis User Creator & CSRF Debugger
 * Run this script on production server to create test user and debug CSRF
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== PRODUCTION PARAMEDIS USER CREATOR & CSRF DEBUGGER ===\n\n";

// Check if we're in Laravel context
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    
    try {
        // Bootstrap Laravel
        $app = require_once __DIR__ . '/bootstrap/app.php';
        $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();
        
        echo "✅ Laravel bootstrapped successfully\n";
        
        // Check database connection
        try {
            $pdo = DB::connection()->getPdo();
            echo "✅ Database connection successful\n";
        } catch (Exception $e) {
            echo "❌ Database connection failed: " . $e->getMessage() . "\n";
            exit(1);
        }
        
        // Check if roles exist
        $roles = DB::table('roles')->get(['id', 'name']);
        echo "✅ Available roles:\n";
        foreach ($roles as $role) {
            echo "   - {$role->name} (ID: {$role->id})\n";
        }
        
        $paramedisRole = DB::table('roles')->where('name', 'paramedis')->first();
        if (!$paramedisRole) {
            echo "❌ Paramedis role not found!\n";
            exit(1);
        }
        
        // Check if test user exists
        $existingUser = DB::table('users')->where('email', 'tina@paramedis.com')->first();
        if ($existingUser) {
            echo "✅ Test user already exists: tina@paramedis.com\n";
            echo "   Current role_id: " . ($existingUser->role_id ?? 'NULL') . "\n";
            
            // Update user to ensure correct role
            DB::table('users')->where('id', $existingUser->id)->update([
                'role_id' => $paramedisRole->id,
                'password' => Hash::make('password123'),
                'updated_at' => now()
            ]);
            echo "✅ Updated user role and password\n";
        } else {
            // Create new user
            $userId = DB::table('users')->insertGetId([
                'name' => 'Tina Paramedis',
                'email' => 'tina@paramedis.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password123'),
                'role_id' => $paramedisRole->id,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            echo "✅ Created new user: tina@paramedis.com (ID: $userId)\n";
        }
        
        // Verify user with role
        $user = DB::table('users')
            ->join('roles', 'users.role_id', '=', 'roles.id')
            ->where('users.email', 'tina@paramedis.com')
            ->select('users.*', 'roles.name as role_name')
            ->first();
            
        if ($user) {
            echo "✅ User verification successful:\n";
            echo "   Email: {$user->email}\n";
            echo "   Name: {$user->name}\n";
            echo "   Role: {$user->role_name}\n";
            echo "   Role ID: {$user->role_id}\n";
        }
        
        // Check session configuration
        echo "\n--- SESSION CONFIGURATION ---\n";
        echo "SESSION_DRIVER: " . config('session.driver') . "\n";
        echo "SESSION_LIFETIME: " . config('session.lifetime') . " minutes\n";
        echo "SESSION_DOMAIN: " . (config('session.domain') ?? 'null') . "\n";
        echo "SESSION_SECURE_COOKIE: " . (config('session.secure') ? 'true' : 'false') . "\n";
        echo "SESSION_HTTP_ONLY: " . (config('session.http_only') ? 'true' : 'false') . "\n";
        echo "SESSION_SAME_SITE: " . (config('session.same_site') ?? 'null') . "\n";
        
        // Check CSRF configuration
        echo "\n--- CSRF CONFIGURATION ---\n";
        echo "APP_KEY: " . (config('app.key') ? 'SET' : 'NOT SET') . "\n";
        echo "APP_URL: " . config('app.url') . "\n";
        echo "APP_ENV: " . config('app.env') . "\n";
        echo "APP_DEBUG: " . (config('app.debug') ? 'true' : 'false') . "\n";
        
        // Test CSRF token generation
        try {
            $token = csrf_token();
            echo "✅ CSRF token generation successful: " . substr($token, 0, 10) . "...\n";
        } catch (Exception $e) {
            echo "❌ CSRF token generation failed: " . $e->getMessage() . "\n";
        }
        
        // Check if sessions table exists (for database driver)
        if (config('session.driver') === 'database') {
            try {
                $sessionCount = DB::table('sessions')->count();
                echo "✅ Sessions table accessible, current sessions: $sessionCount\n";
                
                // Clean up old sessions
                $cleaned = DB::table('sessions')
                    ->where('last_activity', '<', now()->subHours(24)->timestamp)
                    ->delete();
                echo "✅ Cleaned up $cleaned old sessions\n";
                
            } catch (Exception $e) {
                echo "❌ Sessions table error: " . $e->getMessage() . "\n";
            }
        }
        
        // Check routes
        echo "\n--- ROUTES CHECK ---\n";
        $routes = ['paramedis', 'paramedis/mobile-app', 'paramedis/login'];
        foreach ($routes as $path) {
            try {
                $route = Route::getRoutes()->getByAction($path);
                echo "✅ Route exists: /$path\n";
            } catch (Exception $e) {
                echo "❌ Route missing: /$path\n";
            }
        }
        
        echo "\n--- PRODUCTION SETUP COMPLETE ---\n";
        echo "Test credentials:\n";
        echo "Email: tina@paramedis.com\n";
        echo "Password: password123\n";
        echo "Role: paramedis\n\n";
        
        echo "Next steps:\n";
        echo "1. Try logging in with these credentials\n";
        echo "2. If still getting page expired, check browser console for errors\n";
        echo "3. Check server error logs for detailed error messages\n";
        
    } catch (Exception $e) {
        echo "❌ Script failed: " . $e->getMessage() . "\n";
        echo "Stack trace: " . $e->getTraceAsString() . "\n";
    }
    
} else {
    echo "❌ Laravel not found - run this from Laravel root directory\n";
}

echo "\n=== SCRIPT COMPLETE ===\n";