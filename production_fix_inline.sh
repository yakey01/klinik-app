#\!/bin/bash
echo "Connecting to production server and executing fix..."

ssh -p 65002 u454362045@153.92.8.132 << 'ENDSSH'
cd public_html
echo "=== Pulling latest code ==="
git pull origin main

echo ""
echo "=== Creating inline fix script ==="
cat > fix_paramedis_now.php << 'ENDPHP'
<?php
/**
 * Inline Production Fix for Paramedis Login
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== PRODUCTION PARAMEDIS LOGIN FIX ===\n\n";

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    
    try {
        $app = require_once __DIR__ . '/bootstrap/app.php';
        $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();
        
        echo "✅ Laravel loaded - Environment: " . config('app.env') . "\n";
        
        // Test database
        $pdo = DB::connection()->getPdo();
        echo "✅ Database connected\n";
        
        // Get/create paramedis role
        $paramedisRole = DB::table('roles')->where('name', 'paramedis')->first();
        if (\!$paramedisRole) {
            $roleId = DB::table('roles')->insertGetId([
                'name' => 'paramedis',
                'display_name' => 'Paramedic', 
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            echo "✅ Created paramedis role ID: $roleId\n";
            $paramedisRole = (object)['id' => $roleId];
        } else {
            echo "✅ Paramedis role exists ID: {$paramedisRole->id}\n";
        }
        
        // Delete existing user
        DB::table('users')->where('email', 'tina@paramedis.com')->delete();
        echo "✅ Cleaned existing user\n";
        
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
        echo "✅ Created user ID: $userId\n";
        
        // Test authentication
        $authTest = Auth::attempt(['email' => 'tina@paramedis.com', 'password' => 'password123']);
        echo "✅ Auth test: " . ($authTest ? 'SUCCESS' : 'FAILED') . "\n";
        if ($authTest) Auth::logout();
        
        // Clear caches
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        echo "✅ Caches cleared\n";
        
        if ($authTest) {
            echo "\n🎉 SUCCESS\! Paramedis login is ready\!\n";
            echo "Credentials: tina@paramedis.com / password123\n";
            echo "URL: " . config('app.url') . "/paramedis/login\n";
        } else {
            echo "\n❌ Authentication still not working\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ Laravel not found\n";
}
ENDPHP

echo ""
echo "=== Executing fix script ==="
php fix_paramedis_now.php

echo ""
echo "=== Fix completed ==="
ENDSSH
