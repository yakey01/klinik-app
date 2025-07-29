<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Tindakan;
use App\Services\JaspelCalculationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

echo "🎯 Validating Tindakan ID 19 to test the proper workflow...\n";

// Login as bendahara user (assuming user ID 17 is bendahara)
Auth::loginUsingId(17);
echo "✅ Logged in as: " . Auth::user()->name . "\n";

try {
    DB::transaction(function() {
        // Get the pending Tindakan
        $tindakan = Tindakan::find(19);
        if (!$tindakan) {
            throw new Exception("Tindakan ID 19 not found");
        }
        
        echo "📋 Original Tindakan status: {$tindakan->status_validasi}\n";
        echo "💰 Jasa Paramedis: Rp " . number_format($tindakan->jasa_paramedis, 0, ',', '.') . "\n";
        
        // Update Tindakan to approved (disetujui)
        $tindakan->update([
            'status_validasi' => 'disetujui',
            'status' => 'selesai',
            'validated_by' => Auth::id(),
            'validated_at' => now(),
            'komentar_validasi' => 'Approved for testing dashboard workflow',
        ]);
        
        echo "✅ Tindakan updated to 'disetujui'\n";
        
        // Generate Jaspel using the same service as ValidationCenter
        $jaspelService = app(JaspelCalculationService::class);
        $createdJaspel = $jaspelService->calculateJaspelFromTindakan($tindakan);
        
        if (is_array($createdJaspel) && count($createdJaspel) > 0) {
            echo "🎉 Created " . count($createdJaspel) . " Jaspel records:\n";
            
            foreach ($createdJaspel as $jaspel) {
                if ($jaspel instanceof \App\Models\Jaspel) {
                    // Update to approved status like ValidationCenter does
                    $jaspel->update([
                        'status_validasi' => 'disetujui',
                        'validasi_by' => Auth::id(),
                        'validasi_at' => now(),
                        'catatan_validasi' => 'Auto-approved with Tindakan validation'
                    ]);
                    
                    echo "  - Jaspel ID {$jaspel->id}: User {$jaspel->user_id}, Rp " . number_format($jaspel->nominal, 0, ',', '.') . " ({$jaspel->status_validasi})\n";
                }
            }
        } else {
            echo "❌ No Jaspel records created\n";
        }
    });
    
    echo "✅ Transaction completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// Check the results
echo "\n🔍 Checking results for User 13 (Naning Paramedis):\n";
$user13Jaspel = App\Models\Jaspel::where('user_id', 13)->get();
echo "Total Jaspel records: {$user13Jaspel->count()}\n";
foreach ($user13Jaspel as $jaspel) {
    echo "  - ID {$jaspel->id}: Rp " . number_format($jaspel->nominal, 0, ',', '.') . " ({$jaspel->status_validasi}) - {$jaspel->tanggal}\n";
}

echo "\n🎉 Validation workflow test completed!\n";