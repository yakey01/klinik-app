<?php

namespace App\Console\Commands;

use App\Models\Pengeluaran;
use App\Services\AutoCodeGeneratorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestPengeluaranCreate extends Command
{
    protected $signature = 'test:pengeluaran-create';
    protected $description = 'Test creating new pengeluaran without validation';

    public function handle()
    {
        $this->info('Testing Pengeluaran Creation Without Validation...');
        
        $testData = [
            'kode_pengeluaran' => AutoCodeGeneratorService::generatePengeluaranCode(),
            'nama_pengeluaran' => 'Test Pengeluaran - No Validation',
            'kategori' => 'konsumsi',
            'keterangan' => 'Test data created without validation requirement',
            'input_by' => 1,
            'tanggal' => now(),
            'nominal' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        
        try {
            DB::transaction(function() use ($testData) {
                DB::table('pengeluaran')->insert($testData);
            });
            
            $this->info("✅ Successfully created pengeluaran: {$testData['kode_pengeluaran']}");
            
            // Verify the record was created
            $created = Pengeluaran::where('kode_pengeluaran', $testData['kode_pengeluaran'])->first();
            
            if ($created) {
                $this->info("✅ Record verified in database:");
                $this->line("  • Code: {$created->kode_pengeluaran}");
                $this->line("  • Name: {$created->nama_pengeluaran}");
                $this->line("  • Category: {$created->kategori}");
                $this->line("  • No status_validasi field required");
                
                // Clean up - delete the test record
                $created->delete();
                $this->info("✅ Test record cleaned up");
            } else {
                $this->error("❌ Record not found in database");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Error creating pengeluaran: " . $e->getMessage());
            return 1;
        }
        
        $this->info('✅ Pengeluaran creation test completed successfully!');
        return 0;
    }
}