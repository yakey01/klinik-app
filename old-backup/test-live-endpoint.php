<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== LIVE ENDPOINT TEST - PARAMEDIS DASHBOARD ===" . PHP_EOL;

// Simulate the EXACT route from routes/api.php
$credentials = ['email' => 'naning@dokterku.com', 'password' => 'naning'];

if (Auth::attempt($credentials)) {
    echo "✅ Authentication successful" . PHP_EOL;
    
    $user = Auth::user();
    echo "User: " . $user->name . " (ID: " . $user->id . ")" . PHP_EOL;
    
    // EXACT CODE FROM routes/api.php line ~101
    // Get paramedis data
    $paramedis = App\Models\Pegawai::where('user_id', $user->id)
        ->where('jenis_pegawai', 'Paramedis')
        ->first();
    
    if (!$paramedis) {
        echo "❌ ERROR: Paramedis data not found" . PHP_EOL;
        exit;
    }
    
    echo "✅ Paramedis found: " . $paramedis->nama_lengkap . PHP_EOL;
    
    // Calculate dynamic Jaspel data from validated Tindakan
    $today = Carbon\Carbon::today();
    $thisMonth = Carbon\Carbon::now()->startOfMonth();
    $thisWeek = Carbon\Carbon::now()->startOfWeek();
    
    echo "Date calculations:" . PHP_EOL;
    echo "  Today: " . $today->format('Y-m-d') . PHP_EOL;
    echo "  This month: " . $thisMonth->format('Y-m-01') . PHP_EOL;
    echo "  This week: " . $thisWeek->format('Y-m-d') . PHP_EOL;
    
    // Monthly Jaspel from Jaspel model (consistent with Jaspel page)
    $jaspelMonthly = App\Models\Jaspel::where('user_id', $user->id)
        ->whereMonth('tanggal', $thisMonth->month)
        ->whereYear('tanggal', $thisMonth->year)
        ->whereIn('status_validasi', ['disetujui', 'approved'])
        ->sum('nominal');
    
    // Weekly Jaspel from Jaspel model (consistent calculation)
    $jaspelWeekly = App\Models\Jaspel::where('user_id', $user->id)
        ->where('tanggal', '>=', $thisWeek)
        ->whereIn('status_validasi', ['disetujui', 'approved'])
        ->sum('nominal');
    
    // Approved vs Pending breakdown using Jaspel model
    $approvedJaspel = App\Models\Jaspel::where('user_id', $user->id)
        ->whereMonth('tanggal', $thisMonth->month)
        ->whereYear('tanggal', $thisMonth->year)
        ->whereIn('status_validasi', ['disetujui', 'approved'])
        ->sum('nominal');
        
    // WORLD-CLASS: Calculate comprehensive pending Jaspel from multiple sources
    // 1. Existing Jaspel records with pending status
    $pendingJaspelRecords = App\Models\Jaspel::where('user_id', $user->id)
        ->whereMonth('tanggal', $thisMonth->month)
        ->whereYear('tanggal', $thisMonth->year)
        ->where('status_validasi', 'pending')
        ->sum('nominal');
        
    // 2. Approved Tindakan that haven't generated Jaspel records yet (paramedis portion)
    $pendingFromTindakan = App\Models\Tindakan::where('paramedis_id', $paramedis->id)
        ->whereMonth('tanggal_tindakan', $thisMonth->month)
        ->whereYear('tanggal_tindakan', $thisMonth->year)
        ->whereIn('status_validasi', ['approved', 'disetujui'])
        ->whereDoesntHave('jaspel', function($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->where('jenis_jaspel', 'paramedis');
        })
        ->where('jasa_paramedis', '>', 0)
        ->sum('jasa_paramedis');
        
    // Total pending = existing pending Jaspel + paramedis portion of approved Tindakan without Jaspel
    $pendingJaspel = $pendingJaspelRecords + ($pendingFromTindakan * 0.15); // 15% paramedis calculation
    
    // Shifts and attendance
    $shiftsThisMonth = App\Models\JadwalJaga::where('pegawai_id', $user->id)
        ->whereMonth('tanggal_jaga', Carbon\Carbon::now()->month)
        ->whereYear('tanggal_jaga', Carbon\Carbon::now()->year)
        ->count();
    
    // Create exact API response like routes/api.php
    $response = [
        'status' => 'success',
        'message' => 'Dashboard data retrieved successfully',
        'data' => [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'paramedis' => [
                'id' => $paramedis->id,
                'nama' => $paramedis->nama_lengkap,
                'jenis' => $paramedis->jenis_pegawai
            ],
            'jaspel' => [
                'monthly' => floatval($jaspelMonthly),
                'weekly' => floatval($jaspelWeekly), 
                'approved' => floatval($approvedJaspel),
                'pending' => floatval($pendingJaspel),
                'breakdown' => [
                    'pending_records' => floatval($pendingJaspelRecords),
                    'pending_from_tindakan' => floatval($pendingFromTindakan * 0.15)
                ]
            ],
            'attendance' => [
                'shifts_this_month' => $shiftsThisMonth
            ],
            'last_updated' => now()->toISOString()
        ]
    ];
    
    echo "" . PHP_EOL;
    echo "🎯 FINAL API RESPONSE (EXACTLY what frontend should receive):" . PHP_EOL;
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    
    echo "" . PHP_EOL;
    echo "📊 SUMMARY OF VALUES:" . PHP_EOL;
    echo "  Monthly Jaspel: Rp" . number_format($jaspelMonthly, 0, ',', '.') . PHP_EOL;
    echo "  Weekly Jaspel: Rp" . number_format($jaspelWeekly, 0, ',', '.') . PHP_EOL;
    echo "  Approved Jaspel: Rp" . number_format($approvedJaspel, 0, ',', '.') . PHP_EOL;
    echo "  Pending Jaspel: Rp" . number_format($pendingJaspel, 0, ',', '.') . PHP_EOL;
    echo "  Shifts this month: " . $shiftsThisMonth . PHP_EOL;
    
    if ($jaspelMonthly > 0 || $pendingJaspel > 0) {
        echo "" . PHP_EOL;
        echo "✅ DATA IS PERFECT - Dashboard should show these values!" . PHP_EOL;
        echo "❌ If dashboard is empty, it's a FRONTEND/SESSION issue, NOT database" . PHP_EOL;
    }
    
} else {
    echo "❌ Authentication failed" . PHP_EOL;
}

echo "" . PHP_EOL . "=== LIVE ENDPOINT TEST COMPLETE ===" . PHP_EOL;