<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Tindakan;
use App\Models\Jaspel;
use Carbon\Carbon;

echo "🔄 Generating Jaspel records from approved Tindakan...\n";

$tindakanList = Tindakan::where('status_validasi', 'disetujui')
    ->whereNotNull('paramedis_id')
    ->where('jasa_paramedis', '>', 0)
    ->get();

echo "Found {$tindakanList->count()} approved tindakan with paramedis jasa\n";

$createdCount = 0;
$skippedCount = 0;

foreach($tindakanList as $tindakan) {
    // Check if jaspel already exists
    $existingJaspel = Jaspel::where('tindakan_id', $tindakan->id)
        ->where('user_id', $tindakan->paramedis_id)
        ->where('jenis_jaspel', 'paramedis')
        ->first();
    
    if (!$existingJaspel) {
        // Ensure shift_id exists or set to null
        $shiftId = null;
        if ($tindakan->shift_id) {
            $shiftExists = \App\Models\Shift::find($tindakan->shift_id);
            if ($shiftExists) {
                $shiftId = $tindakan->shift_id;
            }
        }

        $jaspel = Jaspel::create([
            'tindakan_id' => $tindakan->id,
            'user_id' => $tindakan->paramedis_id,
            'jenis_jaspel' => 'paramedis',
            'nominal' => $tindakan->jasa_paramedis,
            'tanggal' => $tindakan->tanggal_tindakan ?? Carbon::now()->format('Y-m-d'),
            'shift_id' => $shiftId,
            'input_by' => 1, // Admin user
            'status_validasi' => 'disetujui', // Auto-approve since tindakan is approved
        ]);
        
        echo "✅ Created Jaspel ID: {$jaspel->id} for Tindakan {$tindakan->id}, User {$tindakan->paramedis_id}, Amount: Rp " . number_format($tindakan->jasa_paramedis, 0, ',', '.') . "\n";
        $createdCount++;
    } else {
        echo "⚠️  Jaspel already exists for Tindakan {$tindakan->id}\n";
        $skippedCount++;
    }
}

echo "\n📊 Summary:\n";
echo "✅ Created: {$createdCount} new Jaspel records\n";
echo "⚠️  Skipped: {$skippedCount} existing records\n";
echo "📈 Total Jaspel records now: " . Jaspel::count() . "\n";

// Show some statistics
$totalJaspel = Jaspel::where('jenis_jaspel', 'paramedis')
    ->where('status_validasi', 'disetujui')
    ->sum('nominal');

echo "💰 Total approved paramedis jaspel: Rp " . number_format($totalJaspel, 0, ',', '.') . "\n";

// Show by user
$jaspelByUser = Jaspel::where('jenis_jaspel', 'paramedis')
    ->where('status_validasi', 'disetujui')
    ->selectRaw('user_id, SUM(nominal) as total')
    ->groupBy('user_id')
    ->with('user')
    ->get();

echo "\n👥 Jaspel by Paramedis:\n";
foreach($jaspelByUser as $userJaspel) {
    $userName = $userJaspel->user ? $userJaspel->user->name : "User ID {$userJaspel->user_id}";
    echo "   {$userName}: Rp " . number_format($userJaspel->total, 0, ',', '.') . "\n";
}

echo "\n🎉 Jaspel generation completed!\n";