<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Pegawai;
use App\Models\JadwalJaga;
use Carbon\Carbon;

echo "🔧 CREATING MULTIPLE DAILY SCHEDULES FOR BITA\n";
echo "============================================\n\n";

try {
    // Get Bita user and pegawai
    $bita = User::where('username', 'bita01')->first();
    if (!$bita) {
        throw new Exception('Bita user not found');
    }

    $pegawai = Pegawai::where('user_id', $bita->id)->first();
    if (!$pegawai) {
        throw new Exception('Pegawai record not found for Bita');
    }

    echo "✅ Found Bita user:\n";
    echo "   ID: {$bita->id}\n";
    echo "   Name: {$pegawai->nama}\n";
    echo "   Unit Kerja: {$pegawai->unit_kerja}\n";
    echo "   Jenis Pegawai: {$pegawai->jenis_pegawai}\n\n";

    // Clear existing schedules for today and tomorrow
    $today = Carbon::today();
    $tomorrow = Carbon::tomorrow();
    
    echo "🗑️ Clearing existing schedules for today and tomorrow...\n";
    JadwalJaga::where('pegawai_id', $pegawai->id)
        ->whereBetween('tanggal_jaga', [$today, $tomorrow])
        ->delete();

    // Create multiple schedules for today
    echo "📅 Creating multiple schedules for TODAY ({$today->format('Y-m-d')}):\n";
    
    $todaySchedules = [
        [
            'shift_template_id' => 1, // Pagi: 07:00-14:00
            'shift_name' => 'Pagi',
            'unit_kerja' => 'Pelayanan',
            'peran' => 'Paramedis',
            'status_jaga' => 'Aktif'
        ],
        [
            'shift_template_id' => 2, // Siang: 14:00-21:00
            'shift_name' => 'Siang',
            'unit_kerja' => 'Pendaftaran',
            'peran' => 'Paramedis',
            'status_jaga' => 'Aktif'
        ]
    ];

    foreach ($todaySchedules as $schedule) {
        $jadwal = JadwalJaga::create([
            'pegawai_id' => $pegawai->id,
            'tanggal_jaga' => $today,
            'shift_template_id' => $schedule['shift_template_id'],
            'unit_kerja' => $schedule['unit_kerja'],
            'peran' => $schedule['peran'],
            'status_jaga' => $schedule['status_jaga'],
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        echo "   ✅ {$schedule['shift_name']}: {$schedule['unit_kerja']}\n";
    }

    // Create multiple schedules for tomorrow
    echo "\n📅 Creating multiple schedules for TOMORROW ({$tomorrow->format('Y-m-d')}):\n";
    
    $tomorrowSchedules = [
        [
            'shift_template_id' => 3, // Malam: 21:00-07:00
            'shift_name' => 'Malam',
            'unit_kerja' => 'Pelayanan',
            'peran' => 'Paramedis',
            'status_jaga' => 'Aktif'
        ],
        [
            'shift_template_id' => 1, // Pagi: 07:00-14:00
            'shift_name' => 'Pagi',
            'unit_kerja' => 'Pendaftaran',
            'peran' => 'Paramedis',
            'status_jaga' => 'Aktif'
        ],
        [
            'shift_template_id' => 2, // Siang: 14:00-21:00
            'shift_name' => 'Siang',
            'unit_kerja' => 'Pelayanan',
            'peran' => 'Paramedis',
            'status_jaga' => 'Aktif'
        ]
    ];

    foreach ($tomorrowSchedules as $schedule) {
        $jadwal = JadwalJaga::create([
            'pegawai_id' => $pegawai->id,
            'tanggal_jaga' => $tomorrow,
            'shift_template_id' => $schedule['shift_template_id'],
            'unit_kerja' => $schedule['unit_kerja'],
            'peran' => $schedule['peran'],
            'status_jaga' => $schedule['status_jaga'],
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        echo "   ✅ {$schedule['shift_name']}: {$schedule['unit_kerja']}\n";
    }

    // Verify schedules were created
    echo "\n🔍 Verifying created schedules:\n";
    $allSchedules = JadwalJaga::where('pegawai_id', $pegawai->id)
        ->whereBetween('tanggal_jaga', [$today, $tomorrow])
        ->with('shiftTemplate')
        ->orderBy('tanggal_jaga')
        ->orderBy('shift_template_id')
        ->get();

    foreach ($allSchedules as $schedule) {
        $shiftName = $schedule->shiftTemplate->nama_shift ?? 'Unknown';
        $jamMasuk = $schedule->shiftTemplate->jam_masuk ?? '00:00:00';
        $jamPulang = $schedule->shiftTemplate->jam_pulang ?? '00:00:00';
        echo "   📋 {$schedule->tanggal_jaga->format('Y-m-d')} | {$shiftName} | {$jamMasuk} - {$jamPulang} | {$schedule->unit_kerja}\n";
    }

    echo "\n🎉 SUCCESS: Created " . count($allSchedules) . " schedules for Bita!\n";
    echo "   Today: " . $allSchedules->where('tanggal_jaga', $today->format('Y-m-d'))->count() . " schedules\n";
    echo "   Tomorrow: " . $allSchedules->where('tanggal_jaga', $tomorrow->format('Y-m-d'))->count() . " schedules\n";
    
    echo "\n📱 Dashboard should now show multiple schedules for each day\n";
    echo "🔗 Test at: http://localhost:8000/paramedis\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}