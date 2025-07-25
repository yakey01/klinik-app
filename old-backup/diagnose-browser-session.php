<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== BROWSER SESSION DIAGNOSTIC ===" . PHP_EOL;

// 1. Check current sessions
echo "1. SESSION ANALYSIS:" . PHP_EOL;
echo "   Driver: " . config('session.driver') . PHP_EOL;
echo "   Lifetime: " . config('session.lifetime') . " minutes" . PHP_EOL;
echo "   Path: " . config('session.files') . PHP_EOL;

// Check active sessions
$sessionPath = storage_path('framework/sessions');
if (is_dir($sessionPath)) {
    $sessions = glob($sessionPath . '/*');
    echo "   Active sessions: " . count($sessions) . PHP_EOL;
    
    // Show recent sessions
    foreach (array_slice($sessions, -3) as $sessionFile) {
        $modified = date('Y-m-d H:i:s', filemtime($sessionFile));
        echo "      - " . basename($sessionFile) . " (modified: " . $modified . ")" . PHP_EOL;
    }
} else {
    echo "   ⚠️  Session directory not found" . PHP_EOL;
}

echo "" . PHP_EOL;

// 2. Test browser-like request simulation
echo "2. BROWSER REQUEST SIMULATION:" . PHP_EOL;

// Start a session like browser would
session_start();

// Simulate login form submission
$credentials = ['email' => 'naning@dokterku.com', 'password' => 'naning'];

if (Auth::attempt($credentials, true)) { // true = remember me
    echo "   ✅ Login successful with remember token" . PHP_EOL;
    
    $user = Auth::user();
    echo "   User: " . $user->name . PHP_EOL;
    echo "   Session ID: " . session_id() . PHP_EOL;
    
    // Store user in session (like web middleware does)
    session(['user_id' => $user->id]);
    session(['authenticated' => true]);
    
    echo "   Session stored: user_id=" . session('user_id') . PHP_EOL;
    
} else {
    echo "   ❌ Login failed" . PHP_EOL;
}

echo "" . PHP_EOL;

// 3. Test API call with session
echo "3. API CALL WITH SESSION:" . PHP_EOL;

if (Auth::check()) {
    $user = Auth::user();
    echo "   ✅ User authenticated via session: " . $user->name . PHP_EOL;
    
    // Make API call like React would
    try {
        // This simulates the fetch('/api/paramedis/dashboard') call
        $apiUser = Auth::user();
        
        if (!$apiUser) {
            echo "   ❌ API: No authenticated user" . PHP_EOL;
        } else {
            echo "   ✅ API: User found: " . $apiUser->name . PHP_EOL;
            
            // Get paramedis data
            $paramedis = App\Models\Pegawai::where('user_id', $apiUser->id)
                ->where('jenis_pegawai', 'Paramedis')
                ->first();
            
            if ($paramedis) {
                echo "   ✅ API: Paramedis data found" . PHP_EOL;
                
                // Calculate Jaspel
                $thisMonth = Carbon\Carbon::now()->startOfMonth();
                $jaspelMonthly = App\Models\Jaspel::where('user_id', $apiUser->id)
                    ->whereMonth('tanggal', $thisMonth->month)
                    ->whereYear('tanggal', $thisMonth->year)
                    ->whereIn('status_validasi', ['disetujui', 'approved'])
                    ->sum('nominal');
                
                echo "   ✅ API: Monthly Jaspel calculated: Rp" . number_format($jaspelMonthly, 0, ',', '.') . PHP_EOL;
                
                // Create API response
                $apiResponse = [
                    'success' => true,
                    'data' => [
                        'jaspel_monthly' => $jaspelMonthly,
                        'user_name' => $apiUser->name
                    ]
                ];
                
                echo "   ✅ API Response: " . json_encode($apiResponse) . PHP_EOL;
                
            } else {
                echo "   ❌ API: Paramedis data not found" . PHP_EOL;
            }
        }
        
    } catch (Exception $e) {
        echo "   ❌ API Error: " . $e->getMessage() . PHP_EOL;
    }
    
} else {
    echo "   ❌ No session authentication" . PHP_EOL;
}

echo "" . PHP_EOL;

// 4. Check for cache issues
echo "4. CACHE DIAGNOSTIC:" . PHP_EOL;

try {
    // Clear various caches that might interfere
    Artisan::call('config:clear');
    echo "   ✅ Config cache cleared" . PHP_EOL;
    
    Artisan::call('route:clear');
    echo "   ✅ Route cache cleared" . PHP_EOL;
    
    Artisan::call('view:clear');
    echo "   ✅ View cache cleared" . PHP_EOL;
    
    // Test if cache clearing affects data
    $testUser = App\Models\User::find(26);
    if ($testUser) {
        echo "   ✅ User data still accessible after cache clear" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "   ⚠️  Cache clear error: " . $e->getMessage() . PHP_EOL;
}

echo "" . PHP_EOL;

// 5. Generate browser debugging commands
echo "5. BROWSER DEBUGGING COMMANDS:" . PHP_EOL;
echo "   To debug in browser, run these commands in console:" . PHP_EOL;
echo "" . PHP_EOL;
echo "   // Clear browser cache" . PHP_EOL;
echo "   localStorage.clear();" . PHP_EOL;
echo "   sessionStorage.clear();" . PHP_EOL;
echo "" . PHP_EOL;
echo "   // Test API endpoint directly" . PHP_EOL;
echo "   fetch('/api/paramedis/dashboard', {" . PHP_EOL;
echo "     method: 'GET'," . PHP_EOL;
echo "     credentials: 'include'," . PHP_EOL;
echo "     headers: {" . PHP_EOL;
echo "       'Accept': 'application/json'," . PHP_EOL;
echo "       'X-Requested-With': 'XMLHttpRequest'" . PHP_EOL;
echo "     }" . PHP_EOL;
echo "   })" . PHP_EOL;
echo "   .then(response => response.json())" . PHP_EOL;
echo "   .then(data => console.log('API Response:', data))" . PHP_EOL;
echo "   .catch(error => console.error('API Error:', error));" . PHP_EOL;

echo "" . PHP_EOL;
echo "=== BROWSER SESSION DIAGNOSTIC COMPLETE ===" . PHP_EOL;