<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use App\Models\Dokter;
use App\Models\Tindakan;
use App\Models\Jaspel;
use App\Models\Pasien;
use App\Models\JenisTindakan;
use Carbon\Carbon;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🎯 Creating test data for Dr. Yaya (User ID: 1)..." . PHP_EOL;

// Find Dr. Yaya
$dokter = Dokter::where('user_id', 1)->first();
$user = User::find(1);

if (!$dokter || !$user) {
    echo "❌ Dr. Yaya not found!" . PHP_EOL;
    exit;
}

echo "✅ Found Dr. Yaya: " . $dokter->nama_lengkap . " (Email: " . $user->email . ")" . PHP_EOL;

// Find or create test patient
$pasien = Pasien::firstOrCreate([
    'no_rekam_medis' => 'RM-TEST-DOK001'
], [
    'nama' => 'Pasien Test Dokter',
    'no_rekam_medis' => 'RM-TEST-DOK001',
    'tanggal_lahir' => '1990-01-01',
    'jenis_kelamin' => 'L',
    'alamat' => 'Test Address',
    'no_telepon' => '08123456789'
]);

echo "✅ Test patient ready: " . $pasien->nama . " (ID: " . $pasien->id . ")" . PHP_EOL;

// Find or create jenis tindakan
$jenisTindakan = JenisTindakan::firstOrCreate([
    'nama' => 'Konsultasi Dokter Test'
], [
    'kode' => 'KDT001',
    'nama' => 'Konsultasi Dokter Test',
    'kategori' => 'konsultasi',
    'tarif' => 100000,
    'jasa_dokter' => 50000,
    'jasa_paramedis' => 25000,
    'jasa_non_paramedis' => 10000,
    'is_active' => true
]);

echo "✅ Test tindakan type ready: " . $jenisTindakan->nama . " (ID: " . $jenisTindakan->id . ")" . PHP_EOL;

// Create test Tindakan records
$today = Carbon::today();
$yesterday = Carbon::yesterday();

// Tindakan 1 - Approved
$tindakan1 = Tindakan::create([
    'pasien_id' => $pasien->id,
    'jenis_tindakan_id' => $jenisTindakan->id,
    'dokter_id' => $dokter->id,
    'shift_id' => 1, // Add required shift_id
    'tanggal_tindakan' => $today,
    'tarif' => 100000,
    'jasa_dokter' => 50000,
    'jasa_paramedis' => 25000,
    'jasa_non_paramedis' => 10000,
    'catatan' => 'Test konsultasi dokter 1',
    'status' => 'selesai',
    'status_validasi' => 'approved', // This should trigger Jaspel creation
    'validated_by' => 1,
    'validated_at' => now(),
    'input_by' => 1
]);

// Tindakan 2 - Approved  
$tindakan2 = Tindakan::create([
    'pasien_id' => $pasien->id,
    'jenis_tindakan_id' => $jenisTindakan->id,
    'dokter_id' => $dokter->id,
    'shift_id' => 1, // Add required shift_id
    'tanggal_tindakan' => $yesterday,
    'tarif' => 100000,
    'jasa_dokter' => 50000,
    'jasa_paramedis' => 25000,
    'jasa_non_paramedis' => 10000,
    'catatan' => 'Test konsultasi dokter 2',
    'status' => 'selesai',
    'status_validasi' => 'approved',
    'validated_by' => 1,
    'validated_at' => now(),
    'input_by' => 1
]);

echo "✅ Created 2 test Tindakan records" . PHP_EOL;
echo "Tindakan 1 ID: " . $tindakan1->id . " (Jasa Dokter: Rp " . number_format($tindakan1->jasa_dokter) . ")" . PHP_EOL;
echo "Tindakan 2 ID: " . $tindakan2->id . " (Jasa Dokter: Rp " . number_format($tindakan2->jasa_dokter) . ")" . PHP_EOL;

// Now create corresponding Jaspel records
$jaspel1 = Jaspel::create([
    'user_id' => $user->id,
    'tindakan_id' => $tindakan1->id,
    'tanggal' => $today,
    'nominal' => $tindakan1->jasa_dokter,
    'jenis_jaspel' => 'dokter',
    'keterangan' => 'Jaspel dari ' . $jenisTindakan->nama,
    'status' => 'paid',
    'shift_id' => null,
    'input_by' => 1
]);

$jaspel2 = Jaspel::create([
    'user_id' => $user->id,
    'tindakan_id' => $tindakan2->id,
    'tanggal' => $yesterday,
    'nominal' => $tindakan2->jasa_dokter,
    'jenis_jaspel' => 'dokter',
    'keterangan' => 'Jaspel dari ' . $jenisTindakan->nama,
    'status' => 'paid',
    'shift_id' => null,
    'input_by' => 1
]);

echo "✅ Created 2 Jaspel records" . PHP_EOL;
echo "Jaspel 1 ID: " . $jaspel1->id . " (Nominal: Rp " . number_format($jaspel1->nominal) . ")" . PHP_EOL;
echo "Jaspel 2 ID: " . $jaspel2->id . " (Nominal: Rp " . number_format($jaspel2->nominal) . ")" . PHP_EOL;

echo PHP_EOL . "🎯 SUMMARY:" . PHP_EOL;
echo "Dr. Yaya has 2 Jaspel records worth Rp " . number_format($jaspel1->nominal + $jaspel2->nominal) . " total" . PHP_EOL;