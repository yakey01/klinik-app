<?php
/**
 * Hostinger Production Database Diagnostic Script
 * Run this on the production server to analyze database state
 */

require_once __DIR__ . '/vendor/autoload.php';

// Load Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== HOSTINGER PRODUCTION DATABASE DIAGNOSTIC ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // 1. Check database connection
    echo "1. DATABASE CONNECTION CHECK\n";
    echo "----------------------------\n";
    $dbName = DB::connection()->getDatabaseName();
    echo "Database Name: " . $dbName . "\n";
    echo "Connection Status: Connected successfully\n\n";

    // 2. Check environment configuration
    echo "2. ENVIRONMENT CONFIGURATION\n";
    echo "-----------------------------\n";
    echo "APP_ENV: " . env('APP_ENV') . "\n";
    echo "DB_CONNECTION: " . env('DB_CONNECTION') . "\n";
    echo "DB_HOST: " . env('DB_HOST') . "\n";
    echo "DB_DATABASE: " . env('DB_DATABASE') . "\n\n";

    // 3. Check JadwalJaga table
    echo "3. JADWAL JAGA TABLE ANALYSIS\n";
    echo "------------------------------\n";
    $jadwalJagaCount = App\Models\JadwalJaga::count();
    echo "Total JadwalJaga records: " . $jadwalJagaCount . "\n";

    if ($jadwalJagaCount > 0) {
        echo "Sample JadwalJaga records (first 5):\n";
        $sampleJadwal = App\Models\JadwalJaga::with('pegawai')->take(5)->get();
        foreach ($sampleJadwal as $jadwal) {
            echo "  ID: {$jadwal->id}, Pegawai: {$jadwal->pegawai->nama_lengkap ?? 'N/A'}, Tanggal: {$jadwal->tanggal_jaga}, Shift: {$jadwal->shift}\n";
        }
    }
    echo "\n";

    // 4. Check Users table
    echo "4. USERS TABLE ANALYSIS\n";
    echo "-----------------------\n";
    $totalUsers = App\Models\User::count();
    echo "Total Users: " . $totalUsers . "\n";

    $dokterUsers = App\Models\User::where('role', 'dokter')->count();
    echo "Dokter Users: " . $dokterUsers . "\n\n";

    // 5. Check Dr. Yaya specifically
    echo "5. DR. YAYA ANALYSIS\n";
    echo "--------------------\n";
    $yayaUser = App\Models\User::where('email', 'yaya@dokterkuklinik.com')->first();
    if ($yayaUser) {
        echo "Dr. Yaya found:\n";
        echo "  ID: " . $yayaUser->id . "\n";
        echo "  Name: " . $yayaUser->nama_lengkap . "\n";
        echo "  Role: " . $yayaUser->role . "\n";
        echo "  Email: " . $yayaUser->email . "\n";

        $yayaSchedules = App\Models\JadwalJaga::where('pegawai_id', $yayaUser->id)->count();
        echo "  Schedule count: " . $yayaSchedules . "\n";

        if ($yayaSchedules > 0) {
            echo "  Recent schedules:\n";
            $recentSchedules = App\Models\JadwalJaga::where('pegawai_id', $yayaUser->id)
                ->orderBy('tanggal_jaga', 'desc')
                ->take(3)
                ->get();
            foreach ($recentSchedules as $schedule) {
                echo "    {$schedule->tanggal_jaga} - {$schedule->shift}\n";
            }
        }
    } else {
        echo "Dr. Yaya not found in users table\n";
    }
    echo "\n";

    // 6. Check API endpoint response
    echo "6. API ENDPOINT TEST\n";
    echo "--------------------\n";
    
    // Simulate the dashboard API call
    $controller = new App\Http\Controllers\Api\V2\Dashboards\DokterDashboardController();
    
    // Create a mock request for Dr. Yaya
    if ($yayaUser) {
        try {
            // Mock authentication
            auth()->login($yayaUser);
            
            $request = new Illuminate\Http\Request();
            $response = $controller->index($request);
            
            if ($response instanceof Illuminate\Http\JsonResponse) {
                $data = $response->getData(true);
                echo "API Response Status: Success\n";
                echo "Response structure:\n";
                echo "  - attendanceRanking: " . (isset($data['attendanceRanking']) ? count($data['attendanceRanking']) . " items" : "Not present") . "\n";
                echo "  - totalDokter: " . ($data['totalDokter'] ?? 'Not present') . "\n";
                echo "  - totalParamedis: " . ($data['totalParamedis'] ?? 'Not present') . "\n";
                echo "  - jadwalHariIni: " . (isset($data['jadwalHariIni']) ? count($data['jadwalHariIni']) . " items" : "Not present") . "\n";
                
                if (isset($data['attendanceRanking']) && !empty($data['attendanceRanking'])) {
                    echo "  AttendanceRanking sample:\n";
                    foreach (array_slice($data['attendanceRanking'], 0, 3) as $ranking) {
                        echo "    " . ($ranking['nama_lengkap'] ?? 'N/A') . " - " . ($ranking['total_kehadiran'] ?? 0) . " kehadiran\n";
                    }
                }
            } else {
                echo "API Response: Unexpected response type\n";
            }
        } catch (Exception $e) {
            echo "API Test Error: " . $e->getMessage() . "\n";
        }
    } else {
        echo "Cannot test API - Dr. Yaya user not found\n";
    }
    echo "\n";

    // 7. Check AttendanceRecap table
    echo "7. ATTENDANCE RECAP ANALYSIS\n";
    echo "----------------------------\n";
    $attendanceRecapCount = App\Models\AttendanceRecap::count();
    echo "Total AttendanceRecap records: " . $attendanceRecapCount . "\n";

    if ($attendanceRecapCount > 0) {
        echo "Sample AttendanceRecap records (first 3):\n";
        $sampleAttendance = App\Models\AttendanceRecap::with('user')->take(3)->get();
        foreach ($sampleAttendance as $attendance) {
            echo "  User: {$attendance->user->nama_lengkap ?? 'N/A'}, Kehadiran: {$attendance->total_kehadiran}, Bulan: {$attendance->bulan}\n";
        }
    }
    echo "\n";

    // 8. Check if there's any seeded data
    echo "8. SEEDED DATA CHECK\n";
    echo "-------------------\n";
    
    // Check for potential seeded users
    $commonSeededEmails = [
        'admin@dokterkuklinik.com',
        'dokter@dokterkuklinik.com',
        'paramedis@dokterkuklinik.com',
        'test@example.com'
    ];
    
    foreach ($commonSeededEmails as $email) {
        $user = App\Models\User::where('email', $email)->first();
        if ($user) {
            echo "  Found seeded user: {$email} (ID: {$user->id})\n";
        }
    }

    echo "\n=== DIAGNOSTIC COMPLETE ===\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}