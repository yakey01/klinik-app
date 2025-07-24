<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Tindakan;
use App\Models\Jaspel;
use App\Models\Pegawai;

echo "=== NANING DATA ANALYSIS FOR VALIDATION CENTER ===" . PHP_EOL;

// Find Naning user
$naning = User::where('name', 'like', '%Naning%')->first();
if (!$naning) {
    echo "Naning user not found" . PHP_EOL;
    exit;
}

echo "Naning User: " . $naning->name . " (ID: " . $naning->id . ")" . PHP_EOL;

// Find Naning's paramedis record
$paramedicRecord = Pegawai::where('user_id', $naning->id)
    ->where('jenis_pegawai', 'Paramedis')
    ->first();

if (!$paramedicRecord) {
    echo "No paramedis record found for Naning" . PHP_EOL;
    exit;
}

echo "Paramedis Record ID: " . $paramedicRecord->id . PHP_EOL . PHP_EOL;

// Check Tindakan records where Naning is the paramedis
echo "=== TINDAKAN RECORDS (Naning as Paramedis) ===" . PHP_EOL;
$tindakanRecords = Tindakan::where('paramedis_id', $paramedicRecord->id)
    ->orderBy('tanggal_tindakan', 'desc')
    ->get(['id', 'tanggal_tindakan', 'jasa_paramedis', 'status_validasi', 'validated_by']);

if ($tindakanRecords->count() === 0) {
    echo "No Tindakan records found for Naning as paramedis" . PHP_EOL;
} else {
    foreach ($tindakanRecords as $t) {
        echo "- ID: " . $t->id . ", Date: " . $t->tanggal_tindakan . ", Amount: Rp" . number_format($t->jasa_paramedis) . ", Status: " . $t->status_validasi . PHP_EOL;
    }
}

echo PHP_EOL . "=== JASPEL RECORDS (Naning as User) ===" . PHP_EOL;
$jaspelRecords = Jaspel::where('user_id', $naning->id)
    ->orderBy('tanggal', 'desc')
    ->get(['id', 'tindakan_id', 'tanggal', 'nominal', 'status_validasi', 'jenis_jaspel']);

if ($jaspelRecords->count() === 0) {
    echo "No Jaspel records found for Naning" . PHP_EOL;
} else {
    foreach ($jaspelRecords as $j) {
        echo "- ID: " . $j->id . ", Tindakan ID: " . ($j->tindakan_id ?? 'null') . ", Date: " . $j->tanggal . ", Amount: Rp" . number_format($j->nominal) . ", Status: " . $j->status_validasi . ", Type: " . $j->jenis_jaspel . PHP_EOL;
    }
}

// Check for pending Tindakan (should appear in validation-center)
echo PHP_EOL . "=== PENDING TINDAKAN (Should appear in ValidationCenter) ===" . PHP_EOL;
$pendingTindakan = Tindakan::where('status_validasi', 'pending')
    ->with(['pasien:id,nama_pasien', 'paramedis.user:id,name', 'dokter.user:id,name'])
    ->get();

if ($pendingTindakan->count() === 0) {
    echo "No pending Tindakan records found" . PHP_EOL;
} else {
    foreach ($pendingTindakan as $pt) {
        $paramedicName = $pt->paramedis && $pt->paramedis->user ? $pt->paramedis->user->name : 'Unknown';
        $dokterName = $pt->dokter && $pt->dokter->user ? $pt->dokter->user->name : 'Unknown';
        echo "- Tindakan ID: " . $pt->id . ", Patient: " . ($pt->pasien->nama_pasien ?? 'Unknown') . ", Paramedis: " . $paramedicName . ", Dokter: " . $dokterName . ", Status: " . $pt->status_validasi . PHP_EOL;
    }
}

// Show what should appear in Naning's dashboard pending tab
echo PHP_EOL . "=== WHAT SHOULD APPEAR IN NANING'S DASHBOARD PENDING TAB ===" . PHP_EOL;

// 1. Jaspel records with pending status
$naningPendingJaspel = Jaspel::where('user_id', $naning->id)
    ->where('status_validasi', 'pending')
    ->get();

echo "1. Pending Jaspel records: " . $naningPendingJaspel->count() . PHP_EOL;
if ($naningPendingJaspel->count() > 0) {
    foreach ($naningPendingJaspel as $pj) {
        echo "   - Jaspel ID " . $pj->id . ": Rp" . number_format($pj->nominal) . " (" . $pj->jenis_jaspel . ")" . PHP_EOL;
    }
}

// 2. Approved Tindakan without Jaspel (these should generate pending Jaspel)
$approvedTindakanWithoutJaspel = Tindakan::where('paramedis_id', $paramedicRecord->id)
    ->whereIn('status_validasi', ['approved', 'disetujui'])
    ->whereDoesntHave('jaspel', function($query) use ($naning) {
        $query->where('user_id', $naning->id)
              ->where('jenis_jaspel', 'paramedis');
    })
    ->where('jasa_paramedis', '>', 0)
    ->get();

echo "2. Approved Tindakan awaiting Jaspel generation: " . $approvedTindakanWithoutJaspel->count() . PHP_EOL;
if ($approvedTindakanWithoutJaspel->count() > 0) {
    foreach ($approvedTindakanWithoutJaspel as $at) {
        echo "   - Tindakan ID " . $at->id . ": Rp" . number_format($at->jasa_paramedis) . " (15% = Rp" . number_format($at->jasa_paramedis * 0.15) . ")" . PHP_EOL;
    }
}

$totalPending = $naningPendingJaspel->sum('nominal') + ($approvedTindakanWithoutJaspel->sum('jasa_paramedis') * 0.15);
echo PHP_EOL . "TOTAL PENDING for Naning's Dashboard: Rp" . number_format($totalPending) . PHP_EOL;