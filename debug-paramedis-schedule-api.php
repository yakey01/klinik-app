<?php
// DEBUG PARAMEDIS SCHEDULE API DEEP ANALYSIS
echo "🔍 DEEP ANALYSIS: PARAMEDIS SCHEDULE API\n";
echo "=======================================\n\n";

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Pegawai;
use App\Models\JadwalJaga;
use Illuminate\Support\Facades\DB;

try {
    echo "🎯 ANALYZING MOBILE DASHBOARD SCHEDULE CARD ISSUE\n";
    echo "================================================\n\n";
    
    echo "📊 DATABASE STRUCTURE ANALYSIS:\n";
    echo "==============================\n";
    
    // 1. Check Bita user and relationships
    $bita = User::where('username', 'sabita')->first();
    
    if (!$bita) {
        echo "❌ Bita user not found\n";
        exit(1);
    }
    
    echo "✅ User: {$bita->name} (ID: {$bita->id})\n";
    echo "🎭 Role: " . ($bita->role ? $bita->role->name : 'No role') . "\n\n";
    
    // 2. Check Pegawai relationship (Critical for schedule lookup)
    echo "👥 PEGAWAI RELATIONSHIP ANALYSIS:\n";
    echo "================================\n";
    
    $pegawai = Pegawai::where('user_id', $bita->id)->first();
    
    if ($pegawai) {
        echo "✅ Pegawai record found:\n";
        echo "  - ID: {$pegawai->id}\n";
        echo "  - User ID: {$pegawai->user_id}\n";
        echo "  - Name: {$pegawai->nama}\n";
        echo "  - Jenis: {$pegawai->jenis_pegawai}\n";
        echo "  - Unit Kerja: {$pegawai->unit_kerja}\n";
        echo "  - Status: " . ($pegawai->is_active ? 'Active' : 'Inactive') . "\n\n";
        
        // Check if paramedis type
        $isParamedis = $pegawai->jenis_pegawai === 'Paramedis';
        echo "🔍 Is Paramedis: " . ($isParamedis ? 'YES ✅' : 'NO ❌') . "\n\n";
        
        if (!$isParamedis) {
            echo "❌ CRITICAL ISSUE: Pegawai jenis_pegawai is not 'Paramedis'\n";
            echo "   Current value: '{$pegawai->jenis_pegawai}'\n";
            echo "   API will fail because it filters by jenis_pegawai = 'Paramedis'\n\n";
        }
    } else {
        echo "❌ NO PEGAWAI RECORD FOUND FOR BITA\n";
        echo "   This is CRITICAL - API requires pegawai record\n\n";
        
        // Check if any pegawai records exist
        $allPegawai = Pegawai::all(['id', 'user_id', 'nama', 'jenis_pegawai', 'unit_kerja']);
        echo "📋 All Pegawai records:\n";
        foreach ($allPegawai as $p) {
            echo "  - ID: {$p->id}, User ID: {$p->user_id}, Name: {$p->nama}, Type: {$p->jenis_pegawai}\n";
        }
        echo "\n";
    }
    
    // 3. Check JadwalJaga records for this user
    echo "📅 JADWAL JAGA ANALYSIS:\n";
    echo "=======================\n";
    
    $userId = $bita->id;
    
    // Direct lookup by user ID (pegawai_id in jadwal_jagas table)
    $schedules = JadwalJaga::where('pegawai_id', $userId)->get();
    
    echo "🔍 Schedules for User ID {$userId}: " . $schedules->count() . " records\n";
    
    if ($schedules->count() > 0) {
        echo "✅ Found schedules:\n";
        foreach ($schedules as $schedule) {
            echo "  - ID: {$schedule->id}\n";
            echo "    Pegawai ID: {$schedule->pegawai_id}\n";
            echo "    Tanggal: {$schedule->tanggal_jaga}\n";
            echo "    Unit Kerja: {$schedule->unit_kerja}\n";
            echo "    Status: {$schedule->status_jaga}\n";
            echo "    Shift Template ID: {$schedule->shift_template_id}\n";
            
            // Check if this matches paramedis criteria
            $isParamedisUnit = in_array($schedule->unit_kerja, ['Pelayanan', 'Pendaftaran']);
            echo "    Matches Paramedis Units: " . ($isParamedisUnit ? 'YES ✅' : 'NO ❌') . "\n";
            echo "    ---\n";
        }
    } else {
        echo "❌ NO SCHEDULES FOUND for User ID {$userId}\n\n";
        
        // Check all schedules to see what exists
        $allSchedules = JadwalJaga::take(10)->get(['id', 'pegawai_id', 'tanggal_jaga', 'unit_kerja', 'status_jaga']);
        echo "📋 Sample existing schedules:\n";
        foreach ($allSchedules as $s) {
            echo "  - ID: {$s->id}, Pegawai ID: {$s->pegawai_id}, Unit: {$s->unit_kerja}, Date: {$s->tanggal_jaga}\n";
        }
        echo "\n";
    }
    
    // 4. Check specific paramedis schedules (using API logic)
    echo "🎯 API LOGIC SIMULATION:\n";
    echo "=======================\n";
    
    echo "Step 1: Check if pegawai record exists...\n";
    $pegawaiCheck = Pegawai::where('user_id', $userId)
        ->where('jenis_pegawai', 'Paramedis')
        ->first();
    
    if (!$pegawaiCheck) {
        echo "❌ FAIL: No pegawai record with jenis_pegawai='Paramedis' for user {$userId}\n";
        echo "   API will return empty array here\n\n";
    } else {
        echo "✅ PASS: Pegawai record found\n\n";
        
        echo "Step 2: Query schedules with paramedis unit filter...\n";
        $paramedisSchedules = JadwalJaga::where('pegawai_id', $userId)
            ->whereIn('unit_kerja', ['Pelayanan', 'Pendaftaran'])
            ->where('tanggal_jaga', '>=', now()->subDays(1))
            ->with(['shiftTemplate'])
            ->orderBy('tanggal_jaga')
            ->take(10)
            ->get();
        
        echo "✅ Found {$paramedisSchedules->count()} paramedis schedules\n";
        
        if ($paramedisSchedules->count() > 0) {
            echo "📋 Paramedis schedules:\n";
            foreach ($paramedisSchedules as $schedule) {
                echo "  - Date: {$schedule->tanggal_jaga}\n";
                echo "    Unit: {$schedule->unit_kerja}\n";
                echo "    Status: {$schedule->status_jaga}\n";
                if ($schedule->shiftTemplate) {
                    echo "    Shift: {$schedule->shiftTemplate->nama_shift} ({$schedule->shiftTemplate->jam_masuk} - {$schedule->shiftTemplate->jam_keluar})\n";
                }
                echo "    ---\n";
            }
        } else {
            echo "❌ NO PARAMEDIS SCHEDULES FOUND\n";
            echo "   API will return empty array\n\n";
        }
    }
    
    // 5. Check foreign key relationships
    echo "🔗 FOREIGN KEY ANALYSIS:\n";
    echo "=======================\n";
    
    echo "Checking foreign key relationships:\n";
    
    // Check users table
    $userExists = DB::table('users')->where('id', $userId)->exists();
    echo "✅ users.id = {$userId}: " . ($userExists ? 'EXISTS' : 'NOT FOUND') . "\n";
    
    // Check if any jadwal_jagas reference this user
    $jadwalCount = DB::table('jadwal_jagas')->where('pegawai_id', $userId)->count();
    echo "📊 jadwal_jagas.pegawai_id = {$userId}: {$jadwalCount} records\n";
    
    // Check if any pegawai records reference this user
    $pegawaiCount = DB::table('pegawai')->where('user_id', $userId)->count();
    echo "📊 pegawai.user_id = {$userId}: {$pegawaiCount} records\n\n";
    
    // 6. Diagnosis and recommendations
    echo "🩺 DIAGNOSIS:\n";
    echo "============\n";
    
    $issues = [];
    
    if (!$pegawai) {
        $issues[] = "❌ CRITICAL: No pegawai record for Bita";
    } elseif ($pegawai->jenis_pegawai !== 'Paramedis') {
        $issues[] = "❌ CRITICAL: Pegawai jenis_pegawai is '{$pegawai->jenis_pegawai}', not 'Paramedis'";
    }
    
    if ($schedules->count() === 0) {
        $issues[] = "❌ CRITICAL: No schedule records for User ID {$userId}";
    }
    
    $paramedisScheduleCount = JadwalJaga::where('pegawai_id', $userId)
        ->whereIn('unit_kerja', ['Pelayanan', 'Pendaftaran'])
        ->count();
    
    if ($paramedisScheduleCount === 0) {
        $issues[] = "❌ CRITICAL: No schedules with unit_kerja in ['Pelayanan', 'Pendaftaran']";
    }
    
    if (empty($issues)) {
        echo "✅ NO CRITICAL ISSUES FOUND\n";
        echo "   API should work correctly\n\n";
    } else {
        echo "🚨 CRITICAL ISSUES FOUND:\n";
        foreach ($issues as $issue) {
            echo "   {$issue}\n";
        }
        echo "\n";
    }
    
    // 7. Solutions
    echo "💡 SOLUTIONS:\n";
    echo "=============\n";
    
    if (!$pegawai) {
        echo "1. CREATE PEGAWAI RECORD:\n";
        echo "   INSERT INTO pegawai (user_id, nama, jenis_pegawai, unit_kerja, is_active) \n";
        echo "   VALUES ({$userId}, '{$bita->name}', 'Paramedis', 'Pelayanan', 1);\n\n";
    } elseif ($pegawai->jenis_pegawai !== 'Paramedis') {
        echo "1. UPDATE PEGAWAI TYPE:\n";
        echo "   UPDATE pegawai SET jenis_pegawai = 'Paramedis' WHERE user_id = {$userId};\n\n";
    }
    
    if ($paramedisScheduleCount === 0) {
        echo "2. CREATE SCHEDULE RECORDS:\n";
        echo "   Admin should create schedules via /admin/jadwal-jagas with:\n";
        echo "   - pegawai_id = {$userId}\n";
        echo "   - unit_kerja = 'Pelayanan' OR 'Pendaftaran'\n";
        echo "   - tanggal_jaga = future dates\n";
        echo "   - status_jaga = 'Aktif'\n\n";
    }
    
    echo "3. TEST API ENDPOINT:\n";
    echo "   URL: GET /paramedis/api/schedules\n";
    echo "   Headers: X-CSRF-TOKEN, Authorization: Bearer <token>\n";
    echo "   Expected: Array of schedule objects\n\n";
    
    echo "4. FRONTEND FIX:\n";
    echo "   Dashboard.tsx should show 'Jadwal Jaga Berikutnya' based on API data\n";
    echo "   Empty response → 'Tidak ada jadwal jaga'\n";
    echo "   Valid response → Show next upcoming schedule\n\n";
    
    echo "✅ ANALYSIS COMPLETED\n";
    echo "===================\n";
    echo "Issue: Mobile dashboard shows 'Tidak ada jadwal jaga' because:\n";
    if (!empty($issues)) {
        foreach ($issues as $issue) {
            echo "- " . str_replace('❌ CRITICAL: ', '', $issue) . "\n";
        }
    } else {
        echo "- API should be working correctly\n";
        echo "- Check browser console for frontend errors\n";
        echo "- Verify authentication tokens\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error during analysis: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}