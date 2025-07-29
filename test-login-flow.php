<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔍 COMPREHENSIVE AUTHENTICATION TEST\n";
echo "=====================================\n\n";

// Test 1: Verify user exists and credentials
echo "1. 👤 USER VERIFICATION:\n";
$user = \App\Models\User::where('email', 'naning@dokterku.com')->first();
if ($user) {
    echo "   ✅ User found: {$user->name} (ID: {$user->id})\n";
    echo "   📧 Email: {$user->email}\n";
    echo "   🔑 Role: {$user->role['name']}\n";
    echo "   📱 Active: " . ($user->deleted_at ? 'NO' : 'YES') . "\n";
    
    // Test password
    if (Hash::check('password123', $user->password)) {
        echo "   🔐 Password 'password123': ✅ VALID\n";
    } else {
        echo "   🔐 Password 'password123': ❌ INVALID\n";
        // Reset password to be sure
        $user->password = Hash::make('password123');
        $user->save();
        echo "   🔄 Password reset to 'password123'\n";
    }
} else {
    echo "   ❌ User not found!\n";
    exit(1);
}

echo "\n2. 🏥 PEGAWAI VERIFICATION:\n";
$pegawai = \App\Models\Pegawai::where('user_id', $user->id)->whereNull('deleted_at')->first();
if ($pegawai) {
    echo "   ✅ Active pegawai found: {$pegawai->nama} (ID: {$pegawai->id})\n";
} else {
    echo "   ❌ No active pegawai record!\n";
    // Check if there's a soft-deleted one
    $deletedPegawai = \App\Models\Pegawai::where('user_id', $user->id)->withTrashed()->get();
    echo "   📋 All pegawai records for user: " . $deletedPegawai->count() . "\n";
    foreach ($deletedPegawai as $p) {
        echo "     - ID: {$p->id}, Name: {$p->nama}, Deleted: " . ($p->deleted_at ? 'YES' : 'NO') . "\n";
    }
}

echo "\n3. 📊 DATA VERIFICATION:\n";
// Test API directly with user authentication
Auth::login($user);
$controller = new \App\Http\Controllers\Api\V2\Dashboards\ParamedisDashboardController();
$request = new \Illuminate\Http\Request(['month' => 7, 'year' => 2025]);
$request->setUserResolver(function () use ($user) { return $user; });

try {
    $response = $controller->getAttendanceRecap($request);
    $data = json_decode($response->content(), true);
    
    if ($data['success']) {
        $recap = $data['data']['recap'];
        echo "   ✅ API Response: SUCCESS\n";
        echo "   📈 Days Present: {$recap['days_present']}\n";
        echo "   📅 Scheduled Days: {$recap['scheduled_days']}\n";
        echo "   📊 Attendance %: {$recap['attendance_percentage']}%\n";
        echo "   📋 Daily Breakdown: " . count($data['data']['daily_breakdown']) . " days\n";
        
        // Check for days with actual attendance
        $attendedDays = array_filter($data['data']['daily_breakdown'], fn($day) => !is_null($day['attendance_info']));
        echo "   🎯 Days with Attendance: " . count($attendedDays) . "\n";
        
        if (count($attendedDays) > 0) {
            $sample = array_values($attendedDays)[0];
            echo "   📝 Sample Day: {$sample['date']} - {$sample['attendance_info']['time_in']} to {$sample['attendance_info']['time_out']}\n";
        }
    } else {
        echo "   ❌ API Response: FAILED - {$data['message']}\n";
    }
} catch (\Exception $e) {
    echo "   ❌ API Error: {$e->getMessage()}\n";
}

echo "\n4. 🌐 WEB ROUTE VERIFICATION:\n";
// Test web route middleware
try {
    $webRequest = \Illuminate\Http\Request::create('/paramedis/mobile-app', 'GET');
    $webRequest->setUserResolver(function () use ($user) { return $user; });
    
    // Check if route exists
    $router = app('router');
    $route = $router->getRoutes()->match($webRequest);
    echo "   ✅ Route found: " . $route->getName() . "\n";
    echo "   🔐 Middleware: " . implode(', ', $route->middleware()) . "\n";
    
} catch (\Exception $e) {
    echo "   ❌ Route Error: {$e->getMessage()}\n";
}

echo "\n5. 🗂️ COMPILED ASSETS VERIFICATION:\n";
// Check if our debug logging made it into the compiled JS
$manifestPath = public_path('build/manifest.json');
if (file_exists($manifestPath)) {
    $manifest = json_decode(file_get_contents($manifestPath), true);
    echo "   ✅ Build manifest exists\n";
    echo "   📦 Assets: " . count($manifest) . " files\n";
    
    // Find the paramedis mobile app JS file
    $paramedisMobileApp = null;
    foreach ($manifest as $key => $value) {
        if (strpos($key, 'paramedis-mobile-app') !== false) {
            $paramedisMobileApp = $value;
            break;
        }
    }
    
    if ($paramedisMobileApp) {
        echo "   ✅ Paramedis mobile app asset found: {$paramedisMobileApp['file']}\n";
        
        // Check if our debug logging is in the compiled file
        $jsFile = public_path('build/' . $paramedisMobileApp['file']);
        if (file_exists($jsFile)) {
            $jsContent = file_get_contents($jsFile);
            if (strpos($jsContent, 'Fetching attendance recap from') !== false) {
                echo "   ✅ Debug logging present in compiled JS\n";
            } else {
                echo "   ❌ Debug logging NOT found in compiled JS\n";
            }
        }
    } else {
        echo "   ❌ Paramedis mobile app asset not found in manifest\n";
    }
} else {
    echo "   ❌ Build manifest not found\n";
}

echo "\n=====================================\n";
echo "🎯 ANALYSIS COMPLETE\n";