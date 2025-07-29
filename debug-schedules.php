<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\JadwalJaga;
use Carbon\Carbon;

echo "🔍 Checking actual schedule count for Naning:\n";
$naning = User::where('name', 'like', '%Naning%')->first();

if ($naning) {
    echo "✅ Found user: {$naning->name} (ID: {$naning->id})\n";
    
    // Check future schedules matching the API logic
    $schedules = JadwalJaga::where('pegawai_id', $naning->id)
        ->whereIn('unit_kerja', ['Pendaftaran', 'Pelayanan'])
        ->where('tanggal_jaga', '>=', now()->subDays(1))
        ->with(['shiftTemplate'])
        ->get()
        ->filter(function ($jadwal) {
            if (!$jadwal->shiftTemplate) return false;
            $shiftStart = Carbon::parse(
                $jadwal->tanggal_jaga->format('Y-m-d') . ' ' . 
                Carbon::parse($jadwal->shiftTemplate->jam_masuk)->format('H:i:s')
            );
            return $shiftStart->gt(now());
        });
    
    echo "📋 Future schedules for API: {$schedules->count()}\n";
    echo "📊 Total pages would be: " . ceil($schedules->count() / 3) . "\n";
    
    if ($schedules->count() > 0) {
        echo "✅ First few schedules:\n";
        foreach ($schedules->take(5) as $i => $s) {
            echo "  {$i}. {$s->tanggal_jaga->format('Y-m-d')} - {$s->shiftTemplate->nama_shift}\n";
        }
    } else {
        echo "❌ No future schedules found - that's why pagination doesn't show!\n";
        echo "📅 Current time: " . now()->format('Y-m-d H:i:s') . "\n";
        
        // Check all schedules without future filter
        $allSchedules = JadwalJaga::where('pegawai_id', $naning->id)
            ->whereIn('unit_kerja', ['Pendaftaran', 'Pelayanan'])
            ->with(['shiftTemplate'])
            ->get();
        
        echo "📝 Total schedules without future filter: {$allSchedules->count()}\n";
    }
} else {
    echo "❌ Naning user not found\n";
}