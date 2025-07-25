<?php

namespace App\Console\Commands;

use App\Models\Pengeluaran;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ShowMasterPengeluaran extends Command
{
    protected $signature = 'show:master-pengeluaran';
    protected $description = 'Show master pengeluaran data organized by categories';

    public function handle()
    {
        $this->info('Master Pengeluaran Data Summary');
        $this->line('');
        
        // Get all pengeluaran grouped by category
        $pengeluarans = Pengeluaran::all()->groupBy('kategori');
        
        $categories = [
            'konsumsi' => '✅ 1. Konsumsi / Minuman / Makanan',
            'alat_bahan' => '✅ 2. Belanja Alat & Bahan Habis Pakai',
            'akomodasi' => '✅ 3. Akomodasi & Transportasi',
            'medis' => '✅ 4. Obat & Alkes',
            'honor' => '✅ 5. Honor & Fee',
            'promosi' => '✅ 6. Promosi & Kegiatan',
        ];
        
        foreach ($categories as $categoryKey => $categoryTitle) {
            $this->info($categoryTitle);
            
            if (isset($pengeluarans[$categoryKey])) {
                foreach ($pengeluarans[$categoryKey] as $pengeluaran) {
                    $this->line("  • {$pengeluaran->kode_pengeluaran} - {$pengeluaran->nama_pengeluaran}", 'info');
                }
            } else {
                $this->line("  (No items found)", 'comment');
            }
            
            $this->line('');
        }
        
        // Show other categories if any
        $otherCategories = $pengeluarans->keys()->diff(array_keys($categories));
        if ($otherCategories->isNotEmpty()) {
            $this->info('Other Categories:');
            foreach ($otherCategories as $categoryKey) {
                $this->line("Category: {$categoryKey}");
                foreach ($pengeluarans[$categoryKey] as $pengeluaran) {
                    $this->line("  • {$pengeluaran->kode_pengeluaran} - {$pengeluaran->nama_pengeluaran}");
                }
                $this->line('');
            }
        }
        
        // Summary statistics
        $this->info('Summary Statistics:');
        $this->line("Total pengeluaran records: " . Pengeluaran::count());
        $this->line("Categories available: " . $pengeluarans->keys()->count());
        
        $this->line('');
        $this->info('✅ Master Pengeluaran data loaded successfully!');
        
        return 0;
    }
}