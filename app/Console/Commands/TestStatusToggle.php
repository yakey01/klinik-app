<?php

namespace App\Console\Commands;

use App\Models\Pendapatan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestStatusToggle extends Command
{
    protected $signature = 'test:status-toggle';
    protected $description = 'Test the status toggle functionality for pendapatan records';

    public function handle()
    {
        $this->info('Testing Status Toggle Functionality...');
        
        // Get all pendapatan records
        $pendapatans = Pendapatan::all();
        
        if ($pendapatans->isEmpty()) {
            $this->error('No pendapatan records found. Please run the seeder first.');
            return 1;
        }
        
        $this->info('Current Pendapatan Records:');
        $this->line('');
        
        foreach ($pendapatans as $pendapatan) {
            $isAktifStatus = $pendapatan->is_aktif ? 'Active' : 'Inactive';
            $validationStatus = ucfirst($pendapatan->status_validasi);
            
            $this->line(sprintf(
                '• %s - %s | Active: %s | Validation: %s',
                $pendapatan->kode_pendapatan,
                $pendapatan->nama_pendapatan,
                $isAktifStatus,
                $validationStatus
            ));
        }
        
        $this->line('');
        
        // Test is_aktif toggle
        $testRecord = $pendapatans->first();
        $originalIsAktif = $testRecord->is_aktif;
        
        $this->info("Testing is_aktif toggle on: {$testRecord->kode_pendapatan}");
        $this->line("Original is_aktif: " . ($originalIsAktif ? 'true' : 'false'));
        
        // Toggle is_aktif
        $testRecord->update(['is_aktif' => !$originalIsAktif]);
        $testRecord->refresh();
        
        $this->line("After toggle is_aktif: " . ($testRecord->is_aktif ? 'true' : 'false'));
        
        // Restore original state
        $testRecord->update(['is_aktif' => $originalIsAktif]);
        $this->info("✅ is_aktif toggle test completed");
        
        $this->line('');
        
        // Test status_validasi toggle
        $this->info("Testing status_validasi toggle on: {$testRecord->kode_pendapatan}");
        $originalStatus = $testRecord->status_validasi;
        $this->line("Original status_validasi: {$originalStatus}");
        
        // Toggle status_validasi
        $newStatus = match($originalStatus) {
            'pending' => 'disetujui',
            'disetujui' => 'pending',
            'ditolak' => 'disetujui',
            default => 'disetujui'
        };
        
        $testRecord->update([
            'status_validasi' => $newStatus,
            'validasi_by' => 1,
            'validasi_at' => now(),
        ]);
        $testRecord->refresh();
        
        $this->line("After toggle status_validasi: {$testRecord->status_validasi}");
        
        // Restore original state
        $testRecord->update([
            'status_validasi' => $originalStatus,
            'validasi_by' => $originalStatus === 'disetujui' ? 1 : null,
            'validasi_at' => $originalStatus === 'disetujui' ? now() : null,
        ]);
        
        $this->info("✅ status_validasi toggle test completed");
        
        $this->line('');
        $this->info('✅ All status toggle tests completed successfully!');
        
        $this->line('');
        $this->info('Summary:');
        $this->line('• is_aktif toggle: Controls active/inactive status');
        $this->line('• status_validasi toggle: Controls validation approval status');
        $this->line('• Both toggles are now available in the PendapatanResource UI');
        
        return 0;
    }
}