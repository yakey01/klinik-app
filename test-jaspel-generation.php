<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\JaspelCalculationService;

echo "🧪 Testing Jaspel generation for Tindakan ID 19...\n";

$tindakan = App\Models\Tindakan::with(['paramedis', 'jenisTindakan'])->find(19);
if (!$tindakan) {
    echo "❌ Tindakan not found\n";
    exit;
}

echo "Tindakan details:\n";
echo "  Status: {$tindakan->status_validasi}\n";
echo "  Paramedis ID: {$tindakan->paramedis_id}\n";
echo "  Jasa Paramedis: Rp " . number_format($tindakan->jasa_paramedis, 0, ',', '.') . "\n";

if ($tindakan->paramedis) {
    echo "  Paramedis User ID: {$tindakan->paramedis->user_id}\n";
}

if ($tindakan->jenisTindakan) {
    echo "  Jenis Tindakan: {$tindakan->jenisTindakan->nama}\n";
    echo "  Tarif: Rp " . number_format($tindakan->jenisTindakan->tarif, 0, ',', '.') . "\n";
}

$jaspelService = app(JaspelCalculationService::class);
$createdJaspel = $jaspelService->calculateJaspelFromTindakan($tindakan);

if (is_array($createdJaspel) && count($createdJaspel) > 0) {
    echo "\n✅ Successfully created " . count($createdJaspel) . " Jaspel records:\n";
    foreach ($createdJaspel as $jaspel) {
        echo "  - ID {$jaspel->id}: User {$jaspel->user_id}, Rp " . number_format($jaspel->nominal, 0, ',', '.') . " ({$jaspel->status_validasi})\n";
    }
} else {
    echo "\n❌ No Jaspel records created\n";
    if ($createdJaspel === null) {
        echo "  Reason: Status validation failed\n";
    }
}

echo "\n🔍 Checking final results for User 13:\n";
$user13Jaspel = App\Models\Jaspel::where('user_id', 13)->get();
echo "Total Jaspel records: {$user13Jaspel->count()}\n";
foreach ($user13Jaspel as $jaspel) {
    echo "  - ID {$jaspel->id}: Rp " . number_format($jaspel->nominal, 0, ',', '.') . " ({$jaspel->status_validasi}) - {$jaspel->tanggal}\n";
}

echo "\n🎉 Test completed!\n";