<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Jaspel;

class TestNaningApi extends Command
{
    protected $signature = 'test:naning-api';
    protected $description = 'Test API endpoint for Naning Jaspel data';

    public function handle()
    {
        $this->info('🔌 TESTING NANING API ENDPOINT');
        $this->newLine();

        // Get Naning user
        $naning = User::where('name', 'LIKE', '%Naning%')
                     ->whereHas('roles', function($q) { $q->where('name', 'paramedis'); })
                     ->first();

        if (!$naning) {
            $this->error('❌ Naning not found!');
            return;
        }

        $this->info("Testing for user: {$naning->name} (ID: {$naning->id})");

        // Test 1: Direct database query
        $this->info('Step 1: Direct database query...');
        $jaspelRecords = Jaspel::where('user_id', $naning->id)
            ->with(['tindakan.jenisTindakan', 'tindakan.pasien', 'validasiBy'])
            ->get();

        $this->info("✓ Found {$jaspelRecords->count()} Jaspel records");

        foreach ($jaspelRecords as $jaspel) {
            $tindakan = $jaspel->tindakan;
            $jenisTindakan = $tindakan ? $tindakan->jenisTindakan : null;
            
            $this->line("  - Jaspel ID: {$jaspel->id}");
            $this->line("    Nominal: Rp " . number_format($jaspel->nominal, 0, ',', '.'));
            $this->line("    Status: {$jaspel->status_validasi}");
            $this->line("    Jenis: " . ($jenisTindakan ? $jenisTindakan->nama : 'N/A'));
            $this->line("    Tanggal: {$jaspel->tanggal->format('Y-m-d')}");
        }

        // Test 2: Test with API-like query (approved tindakan only)
        $this->newLine();
        $this->info('Step 2: API-like query (approved tindakan only)...');
        
        $apiQuery = Jaspel::where('user_id', $naning->id)
            ->with(['tindakan.jenisTindakan', 'tindakan.pasien', 'validasiBy'])
            ->whereHas('tindakan', function($query) {
                $query->where('status_validasi', 'approved');
            });

        $apiResults = $apiQuery->get();
        $this->info("✓ Found {$apiResults->count()} Jaspel from approved tindakan");

        // Test 3: Format data like mobile API
        $this->newLine();
        $this->info('Step 3: Format data like mobile API...');

        $formattedData = $apiResults->map(function($jaspel) {
            $tindakan = $jaspel->tindakan;
            $jenisTindakan = $tindakan ? $tindakan->jenisTindakan : null;
            $pasien = $tindakan ? $tindakan->pasien : null;

            return [
                'id' => (string) $jaspel->id,
                'tanggal' => $jaspel->tanggal->format('Y-m-d'),
                'jenis' => $jenisTindakan ? $jenisTindakan->nama : 'Jaspel ' . ucwords(str_replace('_', ' ', $jaspel->jenis_jaspel)),
                'jumlah' => (int) $jaspel->nominal,
                'status' => $jaspel->status_validasi === 'disetujui' ? 'paid' : 
                           ($jaspel->status_validasi === 'pending' ? 'pending' : 'rejected'),
                'keterangan' => $jaspel->keterangan ?: (
                    $pasien ? "Pasien: {$pasien->nama}" : 
                    ($jenisTindakan ? $jenisTindakan->nama : 'Jaspel medis')
                ),
            ];
        });

        $this->info("✓ Formatted {$formattedData->count()} records for mobile app");

        foreach ($formattedData as $item) {
            $this->line("  - ID: {$item['id']}, Jenis: {$item['jenis']}");
            $this->line("    Jumlah: Rp " . number_format($item['jumlah'], 0, ',', '.'));
            $this->line("    Status: {$item['status']}, Tanggal: {$item['tanggal']}");
        }

        // Test 4: Summary calculation
        $this->newLine();
        $this->info('Step 4: Summary calculation...');

        $totalPaid = $apiResults->where('status_validasi', 'disetujui')->sum('nominal');
        $totalPending = $apiResults->where('status_validasi', 'pending')->sum('nominal');
        $totalRejected = $apiResults->where('status_validasi', 'ditolak')->sum('nominal');

        $this->info("Summary for mobile app:");
        $this->line("  - Total Paid: Rp " . number_format($totalPaid, 0, ',', '.'));
        $this->line("  - Total Pending: Rp " . number_format($totalPending, 0, ',', '.'));
        $this->line("  - Total Rejected: Rp " . number_format($totalRejected, 0, ',', '.'));

        $this->newLine();
        $this->info('✅ API test completed successfully!');
        $this->info('📱 Naning should see this data in mobile app');
        $this->info('🔗 URL: http://127.0.0.1:8000/paramedis/mobile-app');
        $this->info('👤 Login: naning@dokterku.com');
    }
}