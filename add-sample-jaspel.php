<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Jaspel;
use App\Models\User;
use Carbon\Carbon;

echo "🔄 Adding sample Jaspel records for current month...\n";

// Get paramedis users (those with jaspel records)
$paramedisUsers = [6, 17, 18, 23]; // From the existing data

// Generate more sample jaspel for current month
$currentMonth = Carbon::now();
$sampleData = [];

// Generate daily data for the month
for ($day = 1; $day <= $currentMonth->day; $day++) {
    $date = $currentMonth->copy()->startOfMonth()->addDays($day - 1);
    
    // Skip future dates
    if ($date->isFuture()) {
        continue;
    }
    
    // Random chance to have jaspel on each day
    if (rand(1, 100) <= 70) { // 70% chance
        foreach ($paramedisUsers as $userId) {
            // Random chance for each user
            if (rand(1, 100) <= 60) { // 60% chance per user
                $amount = [10000, 12000, 15000, 18000, 20000, 25000, 30000][rand(0, 6)];
                
                $sampleData[] = [
                    'tindakan_id' => null, // Sample data without tindakan
                    'user_id' => $userId,
                    'jenis_jaspel' => 'paramedis',
                    'nominal' => $amount,
                    'tanggal' => $date->format('Y-m-d'),
                    'shift_id' => null,
                    'input_by' => 1,
                    'status_validasi' => 'disetujui',
                    'created_at' => $date->format('Y-m-d H:i:s'),
                    'updated_at' => $date->format('Y-m-d H:i:s'),
                ];
            }
        }
    }
}

echo "Generated " . count($sampleData) . " sample jaspel records\n";

// Insert in batches
$createdCount = 0;
foreach (array_chunk($sampleData, 50) as $batch) {
    Jaspel::insert($batch);
    $createdCount += count($batch);
    echo "✅ Inserted batch of " . count($batch) . " records\n";
}

echo "\n📊 Summary:\n";
echo "✅ Created: {$createdCount} new sample Jaspel records\n";
echo "📈 Total Jaspel records now: " . Jaspel::count() . "\n";

// Show current month statistics
$monthlyTotal = Jaspel::where('jenis_jaspel', 'paramedis')
    ->where('status_validasi', 'disetujui')
    ->whereYear('tanggal', $currentMonth->year)
    ->whereMonth('tanggal', $currentMonth->month)
    ->sum('nominal');

echo "💰 Current month paramedis jaspel: Rp " . number_format($monthlyTotal, 0, ',', '.') . "\n";

// Show by user for current month
$monthlyByUser = Jaspel::where('jenis_jaspel', 'paramedis')
    ->where('status_validasi', 'disetujui')
    ->whereYear('tanggal', $currentMonth->year)
    ->whereMonth('tanggal', $currentMonth->month)
    ->selectRaw('user_id, SUM(nominal) as total, COUNT(*) as count')
    ->groupBy('user_id')
    ->with('user')
    ->get();

echo "\n👥 Current Month Jaspel by Paramedis:\n";
foreach($monthlyByUser as $userJaspel) {
    $userName = $userJaspel->user ? $userJaspel->user->name : "User ID {$userJaspel->user_id}";
    echo "   {$userName}: Rp " . number_format($userJaspel->total, 0, ',', '.') . " ({$userJaspel->count} records)\n";
}

echo "\n🎉 Sample data generation completed!\n";