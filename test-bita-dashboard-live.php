<?php
// TEST BITA DASHBOARD LIVE ACCESS
echo "🧪 TESTING BITA DASHBOARD LIVE ACCESS\n";
echo "====================================\n\n";

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\JadwalJaga;
use Illuminate\Support\Facades\Auth;

try {
    echo "🔍 CURRENT COMMIT STATE ANALYSIS\n";
    echo "===============================\n";
    
    // Get current commit info
    $currentCommit = exec('git rev-parse HEAD');
    echo "📍 Current Commit: " . substr($currentCommit, 0, 9) . "\n";
    echo "📍 Expected: dde65eff0 (paramedis schedule fixes)\n\n";
    
    echo "👤 BITA USER STATUS CHECK\n";
    echo "========================\n";
    
    // Find Bita user
    $bita = User::where('username', 'sabita')->first();
    
    if (!$bita) {
        echo "❌ Bita user not found\n";
        exit(1);
    }
    
    echo "✅ User: {$bita->name} (ID: {$bita->id})\n";
    echo "🎭 Role: " . ($bita->role ? $bita->role->name : 'No role') . "\n";
    echo "🔑 Password: Last updated " . $bita->updated_at->diffForHumans() . "\n";
    echo "📧 Email: {$bita->email}\n";
    echo "✅ Active: " . ($bita->is_active ? 'YES' : 'NO') . "\n\n";
    
    echo "📅 SCHEDULE DATA VERIFICATION\n";
    echo "============================\n";
    
    // Check current schedules for Bita
    $schedules = JadwalJaga::where('pegawai_id', $bita->id)
        ->whereIn('unit_kerja', ['Pelayanan', 'Pendaftaran'])
        ->where('tanggal_jaga', '>=', now()->subDays(1))
        ->with(['shiftTemplate'])
        ->orderBy('tanggal_jaga')
        ->take(10)
        ->get();
    
    echo "📊 Total schedules found: {$schedules->count()}\n\n";
    
    if ($schedules->count() > 0) {
        echo "✅ SCHEDULES FOUND:\n";
        foreach ($schedules as $schedule) {
            echo "🆔 ID: {$schedule->id}\n";
            echo "📅 Date: {$schedule->tanggal_jaga->format('Y-m-d H:i:s')}\n";
            echo "🏥 Unit: {$schedule->unit_kerja}\n";
            echo "📋 Status: {$schedule->status_jaga}\n";
            echo "🕐 Shift Template: " . ($schedule->shiftTemplate ? $schedule->shiftTemplate->nama_shift : 'None') . "\n";
            if ($schedule->shiftTemplate) {
                echo "⏰ Time: {$schedule->shiftTemplate->jam_masuk} - " . ($schedule->shiftTemplate->jam_keluar ?? 'N/A') . "\n";
            }
            echo "---\n";
        }
        
        // Test API endpoint simulation
        echo "\n🧪 API ENDPOINT SIMULATION\n";
        echo "=========================\n";
        
        Auth::login($bita);
        
        // Simulate the exact API logic
        $apiSchedules = JadwalJaga::where('pegawai_id', $bita->id)
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
        
        echo "📡 API Response (simulated):\n";
        echo json_encode($apiSchedules->toArray(), JSON_PRETTY_PRINT) . "\n\n";
        
        if ($apiSchedules->count() > 0) {
            $nextSchedule = $apiSchedules->first();
            echo "📱 DASHBOARD SHOULD SHOW:\n";
            echo "========================\n";
            echo "🎯 Card: 'Jadwal Jaga Berikutnya'\n";
            echo "🏷️ Badge: 'Segera'\n";
            echo "📅 Date: " . \Carbon\Carbon::parse($nextSchedule['tanggal'])->format('l, d F Y') . "\n";
            echo "🕐 Time: {$nextSchedule['waktu']}\n";
            echo "📍 Location: {$nextSchedule['lokasi']}\n";
            echo "🌅 Shift: {$nextSchedule['jenis']}\n\n";
            
            echo "✅ EXPECTED BEHAVIOR: Dashboard should display next schedule\n";
        } else {
            echo "❌ API would return empty array\n";
            echo "📱 Dashboard would show: 'Tidak ada jadwal jaga'\n";
        }
        
    } else {
        echo "❌ NO SCHEDULES FOUND\n";
        echo "📱 Dashboard will show: 'Tidak ada jadwal jaga'\n\n";
        
        // Check if any schedules exist at all
        $allSchedules = JadwalJaga::where('pegawai_id', $bita->id)->get();
        echo "🔍 Total schedules for Bita (all units): {$allSchedules->count()}\n";
        
        if ($allSchedules->count() > 0) {
            echo "⚠️ Found schedules but not in paramedis units:\n";
            foreach ($allSchedules as $s) {
                echo "  - Date: {$s->tanggal_jaga}, Unit: {$s->unit_kerja}\n";
            }
        }
    }
    
    echo "\n🌐 LIVE DASHBOARD TEST\n";
    echo "====================\n";
    
    echo "1. Login URL: http://localhost:8000/login\n";
    echo "2. Credentials: sabita / bita123\n";
    echo "3. Dashboard URL: http://localhost:8000/paramedis\n";
    echo "4. Expected to see: 'Jadwal Jaga Berikutnya' card with data\n\n";
    
    echo "🔧 TROUBLESHOOTING STEPS\n";
    echo "=======================\n";
    
    if ($schedules->count() > 0) {
        echo "✅ Backend data is correct\n";
        echo "💡 If dashboard still shows 'Tidak ada jadwal':\n";
        echo "   1. Clear browser cache and cookies\n";
        echo "   2. Check browser console for JavaScript errors\n";
        echo "   3. Verify network tab for failed API calls\n";
        echo "   4. Check CSRF token in request headers\n";
        echo "   5. Use test-bita-api-call.html for live HTTP testing\n";
    } else {
        echo "❌ Backend data is missing\n";
        echo "💡 Admin needs to create schedules:\n";
        echo "   1. Go to /admin/jadwal-jagas\n";
        echo "   2. Create schedule for Bita (pegawai_id = {$bita->id})\n";
        echo "   3. Set unit_kerja = 'Pelayanan' or 'Pendaftaran'\n";
        echo "   4. Set future tanggal_jaga\n";
        echo "   5. Set status_jaga = 'Aktif'\n";
    }
    
    echo "\n🎯 COMMIT COMPARISON\n";
    echo "===================\n";
    echo "Current commit dde65eff0 should have:\n";
    echo "✅ Fixed paramedis schedule API errors\n";
    echo "✅ Enhanced error handling in Dashboard.tsx\n";
    echo "✅ Defensive programming in JadwalJaga.tsx\n";
    echo "✅ Working /paramedis/api/schedules endpoint\n\n";
    
    echo "🚨 ISSUE DIAGNOSIS\n";
    echo "=================\n";
    
    if ($schedules->count() === 0) {
        echo "❌ ROOT CAUSE: No schedule data in database\n";
        echo "   Schedule data may have been lost during git operations\n";
        echo "   Need to recreate schedules via admin panel\n";
    } else {
        echo "✅ Database has schedule data\n";
        echo "❌ Issue likely in frontend or authentication\n";
        echo "   Use browser testing tools to identify the problem\n";
    }
    
    echo "\n✅ ANALYSIS COMPLETED\n";
    
} catch (Exception $e) {
    echo "❌ Error during test: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}