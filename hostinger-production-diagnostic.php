<?php

/**
 * HOSTINGER PRODUCTION DIAGNOSTIC TOOL
 * Deep dive investigation for Dr. Yaya Mulyana login and dashboard issues
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Carbon\Carbon;

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔍 HOSTINGER PRODUCTION DIAGNOSTIC - Dr. Yaya Mulyana Deep Dive" . PHP_EOL;
echo "=" . str_repeat("=", 70) . PHP_EOL;
echo "Timestamp: " . Carbon::now()->format('Y-m-d H:i:s') . PHP_EOL;
echo "Environment: " . config('app.env') . PHP_EOL;
echo "Database: " . config('database.default') . PHP_EOL . PHP_EOL;

// 1. DATABASE INVESTIGATION
echo "📊 1. DATABASE INVESTIGATION" . PHP_EOL;
echo "-" . str_repeat("-", 30) . PHP_EOL;

// Find Dr. Yaya by username
$dokterByUsername = \App\Models\Dokter::where('username', 'yaya')->first();

if (!$dokterByUsername) {
    echo "❌ CRITICAL: Dokter with username 'yaya' not found!" . PHP_EOL;
    
    // Search by name pattern
    $dokterByName = \App\Models\Dokter::where('nama_lengkap', 'LIKE', '%Yaya%')->first();
    
    if ($dokterByName) {
        echo "✅ Found dokter by name pattern:" . PHP_EOL;
        echo "   ID: " . $dokterByName->id . PHP_EOL;
        echo "   Username: " . ($dokterByName->username ?? 'NULL') . PHP_EOL;
        echo "   Nama Lengkap: " . $dokterByName->nama_lengkap . PHP_EOL;
        $dokterByUsername = $dokterByName;
    } else {
        echo "❌ No dokter found with 'Yaya' in nama_lengkap" . PHP_EOL;
        
        // List all dokters to debug
        echo PHP_EOL . "📋 ALL DOKTERS IN DATABASE:" . PHP_EOL;
        $allDokters = \App\Models\Dokter::select('id', 'username', 'nama_lengkap', 'user_id')->get();
        foreach ($allDokters as $dok) {
            echo "   ID: {$dok->id} | Username: " . ($dok->username ?? 'NULL') . " | Name: {$dok->nama_lengkap}" . PHP_EOL;
        }
        exit(1);
    }
}

$dokter = $dokterByUsername;
echo "✅ Found Dr. Yaya Dokter Record:" . PHP_EOL;
echo "   Dokter ID: " . $dokter->id . PHP_EOL;
echo "   Username: " . $dokter->username . PHP_EOL;
echo "   Nama Lengkap: " . $dokter->nama_lengkap . PHP_EOL;
echo "   User ID: " . $dokter->user_id . PHP_EOL;
echo "   Status Aktif: " . ($dokter->aktif ? 'YES' : 'NO') . PHP_EOL;
echo "   Status Akun: " . ($dokter->status_akun ?? 'NULL') . PHP_EOL;

// Check associated user
$user = $dokter->user;
if ($user) {
    echo "✅ Associated User Record:" . PHP_EOL;
    echo "   User ID: " . $user->id . PHP_EOL;
    echo "   User Name: " . $user->name . PHP_EOL;
    echo "   User Email: " . $user->email . PHP_EOL;
    echo "   User Created: " . $user->created_at . PHP_EOL;
} else {
    echo "❌ CRITICAL: No associated User record found!" . PHP_EOL;
    exit(1);
}

echo PHP_EOL;

// 2. AUTHENTICATION TESTING
echo "🔐 2. AUTHENTICATION TESTING" . PHP_EOL;
echo "-" . str_repeat("-", 30) . PHP_EOL;

try {
    // Test password verification
    $testPasswords = ['yaya', 'password', '123456', 'dokter123', $dokter->username];
    
    foreach ($testPasswords as $testPassword) {
        if ($dokter->password) {
            $isValid = \Hash::check($testPassword, $dokter->password);
            echo ($isValid ? "✅" : "❌") . " Password '{$testPassword}': " . ($isValid ? "VALID" : "INVALID") . PHP_EOL;
            
            if ($isValid) {
                echo "   🎯 LOGIN PASSWORD FOUND: '{$testPassword}'" . PHP_EOL;
                break;
            }
        }
    }
    
    if (!$dokter->password) {
        echo "❌ CRITICAL: Dokter has no password set!" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "❌ Error testing passwords: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL;

// 3. MOBILE APP ROUTE SIMULATION
echo "📱 3. MOBILE APP ROUTE SIMULATION" . PHP_EOL;
echo "-" . str_repeat("-", 30) . PHP_EOL;

try {
    // Simulate the mobile app route logic
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Selamat Pagi' : ($hour < 17 ? 'Selamat Siang' : 'Selamat Malam');
    
    $displayName = $dokter ? $dokter->nama_lengkap : $user->name;
    
    $userData = [
        'name' => $displayName,
        'email' => $user->email,
        'greeting' => $greeting,
        'initials' => strtoupper(substr($displayName ?? 'DA', 0, 2))
    ];
    
    echo "✅ Mobile App userData (would be in meta tag):" . PHP_EOL;
    echo json_encode($userData, JSON_PRETTY_PRINT) . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Error simulating mobile app route: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL;

// 4. DASHBOARD API TESTING
echo "🔄 4. DASHBOARD API TESTING" . PHP_EOL;
echo "-" . str_repeat("-", 30) . PHP_EOL;

try {
    // Simulate authentication for API testing
    auth()->login($user);
    
    echo "✅ Authenticated as: " . auth()->user()->name . PHP_EOL . PHP_EOL;
    
    // Test Dashboard API
    $controller = new \App\Http\Controllers\Api\V2\Dashboards\DokterDashboardController();
    $request = new Request();
    
    $response = $controller->index($request);
    $responseData = $response->getData(true);
    
    if ($responseData['success']) {
        echo "✅ DASHBOARD API SUCCESSFUL" . PHP_EOL;
        echo "Response Structure:" . PHP_EOL;
        echo "   Success: " . ($responseData['success'] ? 'true' : 'false') . PHP_EOL;
        echo "   Message: " . $responseData['message'] . PHP_EOL;
        
        $data = $responseData['data'];
        
        // User data analysis
        echo PHP_EOL . "👤 USER SECTION:" . PHP_EOL;
        if (isset($data['user'])) {
            echo "   ✅ User ID: " . $data['user']['id'] . PHP_EOL;
            echo "   ✅ User Name: " . $data['user']['name'] . PHP_EOL;
            echo "   ✅ User Email: " . $data['user']['email'] . PHP_EOL;
            echo "   ✅ Jabatan: " . $data['user']['jabatan'] . PHP_EOL;
            echo "   ✅ Initials: " . $data['user']['initials'] . PHP_EOL;
        } else {
            echo "   ❌ User data missing from API response!" . PHP_EOL;
        }
        
        // Dokter data analysis
        echo PHP_EOL . "👨‍⚕️ DOKTER SECTION:" . PHP_EOL;
        if (isset($data['dokter'])) {
            echo "   ✅ Dokter ID: " . $data['dokter']['id'] . PHP_EOL;
            echo "   ✅ Nama Lengkap: " . $data['dokter']['nama_lengkap'] . PHP_EOL;
            echo "   ✅ NIK: " . $data['dokter']['nik'] . PHP_EOL;
            echo "   ✅ Jabatan: " . $data['dokter']['jabatan'] . PHP_EOL;
            echo "   ✅ Status: " . $data['dokter']['status'] . PHP_EOL;
        } else {
            echo "   ❌ Dokter data missing from API response!" . PHP_EOL;
        }
        
        // Stats analysis
        echo PHP_EOL . "📊 STATS SECTION:" . PHP_EOL;
        if (isset($data['stats'])) {
            foreach ($data['stats'] as $key => $value) {
                echo "   ✅ {$key}: " . (is_array($value) ? json_encode($value) : $value) . PHP_EOL;
            }
        } else {
            echo "   ❌ Stats data missing from API response!" . PHP_EOL;
        }
        
        // Performance analysis  
        echo PHP_EOL . "🎯 PERFORMANCE SECTION:" . PHP_EOL;
        if (isset($data['performance'])) {
            echo "   ✅ Attendance Rank: " . ($data['performance']['attendance_rank'] ?? 'NULL') . PHP_EOL;
            echo "   ✅ Total Staff: " . ($data['performance']['total_staff'] ?? 'NULL') . PHP_EOL;
            echo "   ✅ Attendance %: " . ($data['performance']['attendance_percentage'] ?? 'NULL') . PHP_EOL;
            echo "   ✅ Attendance Rate: " . ($data['performance']['attendance_rate'] ?? 'NULL') . PHP_EOL;
        } else {
            echo "   ❌ Performance data missing from API response!" . PHP_EOL;
        }
        
        // Next schedule analysis
        echo PHP_EOL . "📅 NEXT SCHEDULE SECTION:" . PHP_EOL;
        if (isset($data['next_schedule']) && $data['next_schedule']) {
            $sched = $data['next_schedule'];
            echo "   ✅ Schedule ID: " . $sched['id'] . PHP_EOL;
            echo "   ✅ Date: " . $sched['date'] . PHP_EOL;
            echo "   ✅ Formatted Date: " . $sched['formatted_date'] . PHP_EOL;
            echo "   ✅ Shift Name: " . $sched['shift_name'] . PHP_EOL;
            echo "   ✅ Start Time: " . $sched['start_time'] . PHP_EOL;
            echo "   ✅ End Time: " . $sched['end_time'] . PHP_EOL;
            echo "   ✅ Days Until: " . $sched['days_until'] . PHP_EOL;
        } else {
            echo "   ⚠️ No next schedule found" . PHP_EOL;
        }
        
        // Frontend logic simulation
        echo PHP_EOL . "🎭 FRONTEND WELCOME MESSAGE LOGIC:" . PHP_EOL;
        $welcomeName = $data['dokter']['nama_lengkap'] ?? 
                      $data['user']['name'] ?? 
                      $userData['name'] ?? 
                      'Dokter';
        
        echo "   🎯 Final welcome name would be: '{$welcomeName}'" . PHP_EOL;
        echo "   🎯 Greeting: " . ($data['greeting'] ?? 'Default greeting') . PHP_EOL;
        
    } else {
        echo "❌ DASHBOARD API FAILED" . PHP_EOL;
        echo "Error Message: " . $responseData['message'] . PHP_EOL;
        if (isset($responseData['data'])) {
            echo "Error Data: " . json_encode($responseData['data']) . PHP_EOL;
        }
    }
    
} catch (Exception $e) {
    echo "❌ CRITICAL ERROR in Dashboard API: " . $e->getMessage() . PHP_EOL;
    echo "Stack Trace: " . $e->getTraceAsString() . PHP_EOL;
}

echo PHP_EOL;

// 5. ATTENDANCE DATA INVESTIGATION
echo "📈 5. ATTENDANCE DATA INVESTIGATION" . PHP_EOL;
echo "-" . str_repeat("-", 30) . PHP_EOL;

try {
    // Check dokter_presensis table
    $attendanceCount = \App\Models\DokterPresensi::where('dokter_id', $dokter->id)->count();
    echo "✅ DokterPresensi records for this dokter: {$attendanceCount}" . PHP_EOL;
    
    if ($attendanceCount > 0) {
        $recentAttendance = \App\Models\DokterPresensi::where('dokter_id', $dokter->id)
            ->orderBy('tanggal', 'desc')
            ->take(5)
            ->get(['tanggal', 'jam_masuk', 'jam_pulang']);
        
        echo "Recent attendance records:" . PHP_EOL;
        foreach ($recentAttendance as $att) {
            echo "   - {$att->tanggal}: {$att->jam_masuk} - " . ($att->jam_pulang ?? 'Not out yet') . PHP_EOL;
        }
    }
    
    // Check AttendanceRecap calculation
    $month = Carbon::now()->month;
    $year = Carbon::now()->year;
    
    echo PHP_EOL . "AttendanceRecap for current month ({$year}-{$month}):" . PHP_EOL;
    $attendanceData = \App\Models\AttendanceRecap::getRecapData($month, $year, 'Dokter');
    
    $userFound = false;
    foreach ($attendanceData as $staff) {
        if ($staff['staff_id'] == $user->id) {
            echo "   ✅ Found in AttendanceRecap:" . PHP_EOL;
            echo "      Staff ID: " . $staff['staff_id'] . PHP_EOL;
            echo "      Staff Name: " . $staff['staff_name'] . PHP_EOL;
            echo "      Staff Type: " . $staff['staff_type'] . PHP_EOL;
            echo "      Attendance %: " . $staff['attendance_percentage'] . PHP_EOL;
            echo "      Rank: " . $staff['rank'] . PHP_EOL;
            $userFound = true;
            break;
        }
    }
    
    if (!$userFound) {
        echo "   ❌ Dr. Yaya not found in AttendanceRecap!" . PHP_EOL;
        echo "   Total records in recap: " . $attendanceData->count() . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "❌ Error investigating attendance data: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL;

// 6. JADWAL JAGA INVESTIGATION
echo "📅 6. JADWAL JAGA INVESTIGATION" . PHP_EOL;
echo "-" . str_repeat("-", 30) . PHP_EOL;

try {
    $jadwalCount = \App\Models\JadwalJaga::where('pegawai_id', $user->id)->count();
    echo "✅ JadwalJaga records for this user: {$jadwalCount}" . PHP_EOL;
    
    if ($jadwalCount > 0) {
        // Next schedule
        $nextSchedule = \App\Models\JadwalJaga::where('pegawai_id', $user->id)
            ->where('tanggal_jaga', '>=', Carbon::today())
            ->orderBy('tanggal_jaga')
            ->first();
        
        if ($nextSchedule) {
            echo "Next schedule:" . PHP_EOL;
            echo "   - Date: " . $nextSchedule->tanggal_jaga . PHP_EOL;
            echo "   - Unit Kerja: " . $nextSchedule->unit_kerja . PHP_EOL;
            echo "   - Status: " . $nextSchedule->status_jaga . PHP_EOL;
        } else {
            echo "   ❌ No upcoming schedules found" . PHP_EOL;
        }
        
        // Recent schedules
        $recentSchedules = \App\Models\JadwalJaga::where('pegawai_id', $user->id)
            ->orderBy('tanggal_jaga', 'desc')
            ->take(5)
            ->get(['tanggal_jaga', 'unit_kerja', 'status_jaga']);
        
        echo "Recent schedules:" . PHP_EOL;
        foreach ($recentSchedules as $sched) {
            echo "   - {$sched->tanggal_jaga}: {$sched->unit_kerja} ({$sched->status_jaga})" . PHP_EOL;
        }
    } else {
        echo "   ❌ No JadwalJaga records found!" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "❌ Error investigating jadwal jaga: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL;

// 7. CACHE INVESTIGATION
echo "💾 7. CACHE INVESTIGATION" . PHP_EOL;
echo "-" . str_repeat("-", 30) . PHP_EOL;

try {
    $cacheKey = "dokter_dashboard_stats_{$user->id}";
    
    if (\Cache::has($cacheKey)) {
        echo "⚠️ Dashboard cache exists for user {$user->id}" . PHP_EOL;
        $cachedData = \Cache::get($cacheKey);
        echo "Cached data keys: " . implode(', ', array_keys($cachedData)) . PHP_EOL;
        
        // Clear cache for testing
        \Cache::forget($cacheKey);
        echo "✅ Cache cleared" . PHP_EOL;
    } else {
        echo "✅ No dashboard cache found for user {$user->id}" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "❌ Error investigating cache: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL;

// 8. SUMMARY AND RECOMMENDATIONS
echo "📋 8. SUMMARY AND RECOMMENDATIONS" . PHP_EOL;
echo "-" . str_repeat("-", 30) . PHP_EOL;

echo "🎯 KEY FINDINGS:" . PHP_EOL;
echo "   - Dokter Username: " . $dokter->username . PHP_EOL;
echo "   - Dokter Nama Lengkap: " . $dokter->nama_lengkap . PHP_EOL;
echo "   - User Name: " . $user->name . PHP_EOL;
echo "   - Expected Welcome: " . ($data['dokter']['nama_lengkap'] ?? $userData['name'] ?? 'UNKNOWN') . PHP_EOL;

echo PHP_EOL . "🔧 RECOMMENDED ACTIONS:" . PHP_EOL;
echo "   1. Clear all application caches" . PHP_EOL;
echo "   2. Check browser cache and localStorage" . PHP_EOL;
echo "   3. Verify API authentication in production" . PHP_EOL;
echo "   4. Test API endpoints directly in production" . PHP_EOL;
echo "   5. Compare database data between localhost and production" . PHP_EOL;

echo PHP_EOL . "✅ DIAGNOSTIC COMPLETE" . PHP_EOL;
echo "=" . str_repeat("=", 70) . PHP_EOL;