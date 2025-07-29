<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔍 Finding the correct Naning user for Tindakan ID 19...\n";

// Get Tindakan ID 19
$tindakan = App\Models\Tindakan::with(['paramedis.user'])->find(19);
if (!$tindakan) {
    echo "❌ Tindakan ID 19 not found\n";
    exit;
}

echo "Tindakan ID 19 details:\n";
echo "  Paramedis ID: {$tindakan->paramedis_id}\n";
echo "  Jasa Paramedis: Rp " . number_format($tindakan->jasa_paramedis, 0, ',', '.') . "\n";
echo "  Status: {$tindakan->status_validasi}\n";
echo "  Tanggal: {$tindakan->tanggal_tindakan}\n";

if ($tindakan->paramedis && $tindakan->paramedis->user) {
    $user = $tindakan->paramedis->user;
    echo "  -> User ID: {$user->id}\n";
    echo "  -> User Name: {$user->name}\n";
    echo "  -> User Email: {$user->email}\n";
    
    // Check if this user has dashboard access
    echo "\n🎯 Dashboard check for user {$user->id}:\n";
    
    // Check Jaspel records for this user
    $jaspelRecords = App\Models\Jaspel::where('user_id', $user->id)->get();
    echo "  Existing Jaspel records: {$jaspelRecords->count()}\n";
    foreach ($jaspelRecords as $jaspel) {
        echo "    - ID {$jaspel->id}: Rp " . number_format($jaspel->nominal, 0, ',', '.') . " ({$jaspel->status_validasi})\n";
    }
    
} else {
    echo "  ❌ No user relationship found\n";
    
    // Check paramedis record
    if ($tindakan->paramedis) {
        echo "  Paramedis exists but no user relationship:\n";
        echo "    Paramedis ID: {$tindakan->paramedis->id}\n";
        echo "    Nama: {$tindakan->paramedis->nama_lengkap}\n";
        echo "    User ID: {$tindakan->paramedis->user_id}\n";
    }
}

echo "\n🎉 Analysis completed!\n";