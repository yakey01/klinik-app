<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== WORLD-CLASS DATABASE FORENSIC ANALYSIS ===" . PHP_EOL;
echo "Timestamp: " . now()->toDateTimeString() . PHP_EOL;
echo "Analyst: Database Expert" . PHP_EOL;
echo "" . PHP_EOL;

// 1. CRITICAL: Check current database connection in runtime
echo "1. DATABASE CONNECTION FORENSICS:" . PHP_EOL;
echo "   Default connection: " . config('database.default') . PHP_EOL;

try {
    $pdo = DB::connection()->getPdo();
    echo "   ✅ Connection successful: " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . PHP_EOL;
    
    // Get actual database file being used
    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
        $stmt = $pdo->query("PRAGMA database_list");
        $databases = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($databases as $db) {
            echo "   📁 Database file: " . $db['file'] . PHP_EOL;
        }
    }
} catch (Exception $e) {
    echo "   ❌ Connection failed: " . $e->getMessage() . PHP_EOL;
}

echo "" . PHP_EOL;

// 2. RAW DATABASE QUERY - Bypass Eloquent completely
echo "2. RAW SQL FORENSICS (Bypassing Eloquent):" . PHP_EOL;

try {
    // Direct PDO query for users table
    $userQuery = "SELECT id, name, email, username FROM users WHERE id = 26";
    $userResult = DB::select($userQuery);
    
    if (!empty($userResult)) {
        $user = $userResult[0];
        echo "   ✅ User 26 found via RAW SQL:" . PHP_EOL;
        echo "      - ID: " . $user->id . PHP_EOL;
        echo "      - Name: " . $user->name . PHP_EOL;
        echo "      - Email: " . $user->email . PHP_EOL;
        echo "      - Username: " . ($user->username ?? 'NULL') . PHP_EOL;
    } else {
        echo "   ❌ User 26 NOT FOUND via RAW SQL" . PHP_EOL;
    }
    
    // Direct PDO query for jaspel table
    $jaspelQuery = "SELECT id, user_id, nominal, status_validasi, tanggal FROM jaspel WHERE user_id = 26";
    $jaspelResults = DB::select($jaspelQuery);
    
    echo "   📊 Jaspel records via RAW SQL: " . count($jaspelResults) . PHP_EOL;
    foreach ($jaspelResults as $jaspel) {
        echo "      - ID: " . $jaspel->id . ", Nominal: " . $jaspel->nominal . ", Status: " . $jaspel->status_validasi . ", Date: " . $jaspel->tanggal . PHP_EOL;
    }
    
    // Check pegawai table
    $pegawaiQuery = "SELECT id, user_id, nama_lengkap, jenis_pegawai FROM pegawais WHERE user_id = 26";
    $pegawaiResults = DB::select($pegawaiQuery);
    
    if (!empty($pegawaiResults)) {
        $pegawai = $pegawaiResults[0];
        echo "   ✅ Pegawai found via RAW SQL:" . PHP_EOL;
        echo "      - ID: " . $pegawai->id . PHP_EOL;
        echo "      - User ID: " . $pegawai->user_id . PHP_EOL;
        echo "      - Nama: " . $pegawai->nama_lengkap . PHP_EOL;
        echo "      - Jenis: " . $pegawai->jenis_pegawai . PHP_EOL;
    } else {
        echo "   ❌ Pegawai NOT FOUND via RAW SQL" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "   ❌ RAW SQL Error: " . $e->getMessage() . PHP_EOL;
}

echo "" . PHP_EOL;

// 3. ELOQUENT MODEL ANALYSIS
echo "3. ELOQUENT MODEL FORENSICS:" . PHP_EOL;

try {
    // Test User model
    $userModel = App\Models\User::find(26);
    if ($userModel) {
        echo "   ✅ User Model found: " . $userModel->name . PHP_EOL;
        echo "      Connection: " . $userModel->getConnectionName() . PHP_EOL;
        echo "      Table: " . $userModel->getTable() . PHP_EOL;
    } else {
        echo "   ❌ User Model NOT found" . PHP_EOL;
    }
    
    // Test Jaspel model with debugging
    echo "   🔍 Jaspel Model Analysis:" . PHP_EOL;
    $jaspelQuery = App\Models\Jaspel::where('user_id', 26);
    echo "      SQL Query: " . $jaspelQuery->toSql() . PHP_EOL;
    echo "      Bindings: " . json_encode($jaspelQuery->getBindings()) . PHP_EOL;
    
    $jaspelModels = $jaspelQuery->get();
    echo "      Results: " . $jaspelModels->count() . " records" . PHP_EOL;
    
    foreach ($jaspelModels as $jaspel) {
        echo "         - ID: " . $jaspel->id . ", Nominal: " . $jaspel->nominal . ", Status: " . $jaspel->status_validasi . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "   ❌ Eloquent Error: " . $e->getMessage() . PHP_EOL;
}

echo "" . PHP_EOL;

// 4. SESSION AND AUTHENTICATION ANALYSIS
echo "4. SESSION FORENSICS:" . PHP_EOL;

try {
    // Check if session is working
    echo "   Session driver: " . config('session.driver') . PHP_EOL;
    echo "   Session lifetime: " . config('session.lifetime') . " minutes" . PHP_EOL;
    
    // Try to authenticate Naning
    $credentials = ['email' => 'naning@dokterku.com', 'password' => 'naning'];
    if (Auth::attempt($credentials)) {
        $authUser = Auth::user();
        echo "   ✅ Authentication successful: " . $authUser->name . PHP_EOL;
        echo "      Auth ID: " . $authUser->id . PHP_EOL;
        echo "      Auth Guard: " . Auth::getDefaultDriver() . PHP_EOL;
        
        // Check if this user can access Jaspel data
        $authJaspel = App\Models\Jaspel::where('user_id', $authUser->id)->count();
        echo "      Jaspel accessible: " . $authJaspel . " records" . PHP_EOL;
        
    } else {
        echo "   ❌ Authentication failed" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "   ❌ Session Error: " . $e->getMessage() . PHP_EOL;
}

echo "" . PHP_EOL;

// 5. API ENDPOINT SIMULATION
echo "5. API ENDPOINT SIMULATION:" . PHP_EOL;

try {
    // Simulate exact API call path
    if (Auth::check()) {
        $user = Auth::user();
        
        // Simulate ParamedisDashboardController logic
        $paramedis = App\Models\Pegawai::where('user_id', $user->id)
            ->where('jenis_pegawai', 'Paramedis')
            ->first();
        
        if (!$paramedis) {
            echo "   ❌ CRITICAL: Paramedis record not found for user " . $user->id . PHP_EOL;
            echo "      This is likely why dashboard is empty!" . PHP_EOL;
        } else {
            echo "   ✅ Paramedis found: " . $paramedis->nama_lengkap . PHP_EOL;
            
            // Test exact dashboard calculations
            $thisMonth = Carbon\Carbon::now()->startOfMonth();
            
            // Monthly Jaspel calculation
            $jaspelMonthly = App\Models\Jaspel::where('user_id', $user->id)
                ->whereMonth('tanggal', $thisMonth->month)
                ->whereYear('tanggal', $thisMonth->year)
                ->whereIn('status_validasi', ['disetujui', 'approved'])
                ->sum('nominal');
            
            echo "   📊 Dashboard calculations:" . PHP_EOL;
            echo "      Monthly Jaspel: Rp" . number_format($jaspelMonthly, 0, ',', '.') . PHP_EOL;
            
            if ($jaspelMonthly == 0) {
                echo "   ⚠️  WARNING: Monthly Jaspel is 0 - this could cause empty dashboard" . PHP_EOL;
                
                // Debug why it's 0
                $allUserJaspel = App\Models\Jaspel::where('user_id', $user->id)->get();
                echo "      Debug - All Jaspel for user:" . PHP_EOL;
                foreach ($allUserJaspel as $j) {
                    $inCurrentMonth = ($j->tanggal->month == $thisMonth->month && $j->tanggal->year == $thisMonth->year);
                    $isApproved = in_array($j->status_validasi, ['disetujui', 'approved']);
                    echo "         ID: " . $j->id . ", Date: " . $j->tanggal . ", Status: " . $j->status_validasi . PHP_EOL;
                    echo "         In current month: " . ($inCurrentMonth ? 'YES' : 'NO') . PHP_EOL;
                    echo "         Is approved: " . ($isApproved ? 'YES' : 'NO') . PHP_EOL;
                }
            }
        }
    } else {
        echo "   ❌ User not authenticated for API simulation" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "   ❌ API Simulation Error: " . $e->getMessage() . PHP_EOL;
}

echo "" . PHP_EOL;

// 6. FRONTEND CONNECTION TEST
echo "6. FRONTEND CONNECTION FORENSICS:" . PHP_EOL;

try {
    // Check if the React dashboard component can access the API
    echo "   API endpoint: /api/paramedis/dashboard" . PHP_EOL;
    echo "   Expected response format: JSON" . PHP_EOL;
    
    // Simulate the exact request flow
    if (Auth::check()) {
        $user = Auth::user();
        $response = [
            'status' => 'success',
            'user_authenticated' => true,
            'user_id' => $user->id,
            'user_name' => $user->name
        ];
        
        echo "   ✅ API Response simulation: " . json_encode($response) . PHP_EOL;
    } else {
        echo "   ❌ API would return 401 Unauthorized" . PHP_EOL;
    }
} catch (Exception $e) {
    echo "   ❌ Frontend Connection Error: " . $e->getMessage() . PHP_EOL;
}

echo "" . PHP_EOL;
echo "=== FORENSIC ANALYSIS COMPLETE ===" . PHP_EOL;
echo "🔍 Analyzing results to identify root cause..." . PHP_EOL;