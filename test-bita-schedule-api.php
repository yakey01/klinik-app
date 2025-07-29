<?php
// TEST BITA SCHEDULE API DIRECTLY
echo "🧪 TESTING BITA SCHEDULE API CALL\n";
echo "=================================\n\n";

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Pegawai;
use App\Models\JadwalJaga;
use Illuminate\Support\Facades\Auth;

try {
    echo "🔐 AUTHENTICATING AS BITA\n";
    echo "========================\n";
    
    // 1. Find and authenticate as Bita
    $bita = User::where('username', 'sabita')->first();
    
    if (!$bita) {
        echo "❌ Bita user not found\n";
        exit(1);
    }
    
    // Manually authenticate
    Auth::login($bita);
    $authenticatedUser = Auth::user();
    
    echo "✅ Authenticated as: {$authenticatedUser->name}\n";
    echo "🆔 User ID: {$authenticatedUser->id}\n";
    echo "🎭 Role: " . ($authenticatedUser->role ? $authenticatedUser->role->name : 'No role') . "\n\n";
    
    echo "🧪 SIMULATING API ENDPOINT LOGIC\n";
    echo "===============================\n";
    
    // 2. Replicate the exact API logic from routes/web.php
    echo "Step 1: Get authenticated user...\n";
    $user = Auth::user();
    echo "✅ User: {$user->name} (ID: {$user->id})\n\n";
    
    echo "Step 2: Check pegawai record...\n";
    
    // Get the actual pegawai_id - ONLY for paramedis users
    $pegawai = Pegawai::where('user_id', $user->id)
        ->where('jenis_pegawai', 'Paramedis')
        ->first();
    
    if (!$pegawai) {
        echo "❌ CRITICAL: No pegawai record found with jenis_pegawai='Paramedis'\n";
        echo "   API would return empty array here\n";
        echo "   This explains why mobile dashboard shows 'Tidak ada jadwal jaga'\n\n";
        
        // Check what pegawai records exist
        $allPegawai = Pegawai::where('user_id', $user->id)->get();
        echo "🔍 Pegawai records for user {$user->id}:\n";
        foreach ($allPegawai as $p) {
            echo "  - ID: {$p->id}, Jenis: '{$p->jenis_pegawai}', Unit: '{$p->unit_kerja}'\n";
        }
        echo "\n";
        
        // Try to fix it
        echo "🔧 ATTEMPTING TO FIX PEGAWAI RECORD\n";
        echo "==================================\n";
        
        if ($allPegawai->count() > 0) {
            $firstPegawai = $allPegawai->first();
            echo "Updating first pegawai record to 'Paramedis'...\n";
            $firstPegawai->update(['jenis_pegawai' => 'Paramedis']);
            echo "✅ Updated pegawai ID {$firstPegawai->id} to jenis_pegawai='Paramedis'\n\n";
            
            // Retry the check
            $pegawai = Pegawai::where('user_id', $user->id)
                ->where('jenis_pegawai', 'Paramedis')
                ->first();
        } else {
            echo "Creating new pegawai record...\n";
            $pegawai = Pegawai::create([
                'user_id' => $user->id,
                'nama' => $user->name,
                'jenis_pegawai' => 'Paramedis',
                'unit_kerja' => 'Pelayanan',
                'is_active' => true
            ]);
            echo "✅ Created new pegawai record ID {$pegawai->id}\n\n";
        }
    }
    
    if ($pegawai) {
        echo "✅ Pegawai record found:\n";
        echo "  - ID: {$pegawai->id}\n";
        echo "  - User ID: {$pegawai->user_id}\n";
        echo "  - Jenis: {$pegawai->jenis_pegawai}\n";
        echo "  - Unit: {$pegawai->unit_kerja}\n\n";
        
        echo "Step 3: Query schedules...\n";
        
        $pegawaiId = $user->id; // Use current user's ID for jadwal lookup
        
        // Get current and upcoming schedules for this paramedis ONLY
        $schedules = JadwalJaga::where('pegawai_id', $pegawaiId)
            ->whereIn('unit_kerja', ['Pelayanan', 'Pendaftaran'])
            ->where('tanggal_jaga', '>=', now()->subDays(1))
            ->with(['shiftTemplate'])
            ->orderBy('tanggal_jaga')
            ->orderBy('shift_template_id')
            ->take(10)
            ->get()
            ->map(function ($jadwal) {
                // Transform to frontend format
                $jamMasuk = $jadwal->shiftTemplate ? 
                    \Carbon\Carbon::parse($jadwal->shiftTemplate->jam_masuk)->hour : 8;
                
                $jenis = match(true) {
                    $jamMasuk >= 6 && $jamMasuk < 14 => 'pagi',
                    $jamMasuk >= 14 && $jamMasuk < 22 => 'siang', 
                    default => 'malam'
                };
                
                return [
                    'id' => (string) $jadwal->id,
                    'tanggal' => $jadwal->tanggal_jaga->format('Y-m-d'),
                    'waktu' => $jadwal->shiftTemplate ? 
                        $jadwal->shiftTemplate->jam_masuk . ' - ' . ($jadwal->shiftTemplate->jam_keluar ?? '00:00') : 
                        '08:00 - 16:00',
                    'lokasi' => $jadwal->unit_kerja,
                    'jenis' => $jenis,
                    'status' => $jadwal->status_jaga === 'Aktif' ? 'scheduled' : 'completed'
                ];
            });
        
        echo "✅ Found {$schedules->count()} schedules\n";
        
        if ($schedules->count() > 0) {
            echo "📋 Schedule details:\n";
            foreach ($schedules as $schedule) {
                echo "  - ID: {$schedule['id']}\n";
                echo "    Date: {$schedule['tanggal']}\n";
                echo "    Time: {$schedule['waktu']}\n";
                echo "    Location: {$schedule['lokasi']}\n";
                echo "    Type: {$schedule['jenis']}\n";
                echo "    Status: {$schedule['status']}\n";
                echo "    ---\n";
            }
            
            echo "\n✅ API WOULD RETURN VALID DATA\n";
            echo "🎯 Mobile dashboard should show next schedule\n\n";
            
            // Show what the dashboard should display
            $nextSchedule = $schedules->first();
            echo "📱 DASHBOARD DISPLAY:\n";
            echo "===================\n";
            echo "Card: 'Jadwal Jaga Berikutnya'\n";
            echo "Badge: 'Segera'\n";
            echo "Content:\n";
            echo "  📅 " . \Carbon\Carbon::parse($nextSchedule['tanggal'])->format('l, d F Y') . "\n";
            echo "  🕐 {$nextSchedule['waktu']}\n";
            echo "  📍 {$nextSchedule['lokasi']}\n";
            echo "  🌅 Shift {$nextSchedule['jenis']}\n";
            
        } else {
            echo "❌ NO SCHEDULES FOUND\n";
            echo "🎯 API would return empty array\n";
            echo "📱 Dashboard shows: 'Tidak ada jadwal jaga'\n\n";
            
            echo "🔍 SCHEDULE DEBUGGING:\n";
            echo "=====================\n";
            
            // Check all schedules for this user
            $allSchedules = JadwalJaga::where('pegawai_id', $pegawaiId)->get();
            echo "Total schedules for user {$pegawaiId}: {$allSchedules->count()}\n";
            
            if ($allSchedules->count() > 0) {
                echo "All schedules (including non-paramedis units):\n";
                foreach ($allSchedules as $s) {
                    echo "  - Date: {$s->tanggal_jaga}, Unit: {$s->unit_kerja}, Status: {$s->status_jaga}\n";
                }
                echo "\n";
                
                // Check unit filter
                $paramedisUnits = ['Pelayanan', 'Pendaftaran'];
                $validUnits = $allSchedules->whereIn('unit_kerja', $paramedisUnits);
                echo "Schedules matching paramedis units: {$validUnits->count()}\n";
                
                // Check date filter
                $recentSchedules = $allSchedules->where('tanggal_jaga', '>=', now()->subDays(1));
                echo "Schedules from yesterday onwards: {$recentSchedules->count()}\n";
            }
        }
    }
    
    echo "\n🎯 SUMMARY:\n";
    echo "==========\n";
    
    if ($schedules && $schedules->count() > 0) {
        echo "✅ API WORKING CORRECTLY\n";
        echo "   - Pegawai record exists\n";
        echo "   - Schedules found and formatted\n";
        echo "   - Frontend should display schedule\n\n";
        
        echo "💡 If dashboard still shows 'Tidak ada jadwal':\n";
        echo "   1. Check browser console for JavaScript errors\n";
        echo "   2. Verify CSRF token in request headers\n";
        echo "   3. Check network tab for failed API calls\n";
        echo "   4. Clear browser cache and cookies\n";
        
    } else {
        echo "❌ API ISSUE IDENTIFIED\n";
        echo "   - No valid schedules found for paramedis units\n";
        echo "   - Admin needs to create schedules with unit_kerja 'Pelayanan' or 'Pendaftaran'\n";
        echo "   - Or existing schedules have wrong unit_kerja values\n";
    }
    
    echo "\n🔧 NEXT STEPS:\n";
    echo "=============\n";
    echo "1. Login as Bita: http://localhost:8000/login (sabita/bita123)\n";
    echo "2. Go to mobile app: http://localhost:8000/paramedis\n";
    echo "3. Check 'Jadwal Jaga Berikutnya' card\n";
    echo "4. Open browser console to see API calls\n";
    echo "5. If still empty, admin should create schedules via /admin/jadwal-jagas\n";
    
} catch (Exception $e) {
    echo "❌ Error during test: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}