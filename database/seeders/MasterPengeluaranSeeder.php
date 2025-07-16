<?php

namespace Database\Seeders;

use App\Services\AutoCodeGeneratorService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterPengeluaranSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating Master Pengeluaran data...');
        
        // ✅ 1. Konsumsi / Minuman / Makanan
        $konsumsiData = [
            'Konsumsi dokter',
            'Konsumsi',
            'Beli bebek goreng belakang',
            'Beli aqua',
            'Coca cola',
        ];
        
        $this->createPengeluaranCategory('Konsumsi / Minuman / Makanan', $konsumsiData, 'konsumsi');
        
        // ✅ 2. Belanja Alat & Bahan Habis Pakai
        $belanjaData = [
            'Kresek kinik',
            'Belanja plastik',
            'Belanja tisu, A4, bayclean, spidol',
            'Beli tinta',
            'Belanja gigi',
            'FC Lembar KB',
        ];
        
        $this->createPengeluaranCategory('Belanja Alat & Bahan Habis Pakai', $belanjaData, 'alat_bahan');
        
        // ✅ 3. Akomodasi & Transportasi
        $akomodasiData = [
            'Akomodasi Home Visite',
            'Transport suji',
            'Akomodasi rapat',
            'Akomodasi pelatihan',
            'Akomodasi BPJS',
            'Akomodasi Home Visite tgl 15',
            'Bensin sabita',
        ];
        
        $this->createPengeluaranCategory('Akomodasi & Transportasi', $akomodasiData, 'akomodasi');
        
        // ✅ 4. Obat & Alkes
        $obatData = [
            'Order obat pelita',
        ];
        
        $this->createPengeluaranCategory('Obat & Alkes', $obatData, 'medis');
        
        // ✅ 5. Honor & Fee
        $honorData = [
            'Fee drg Zulfa bulan Mei',
        ];
        
        $this->createPengeluaranCategory('Honor & Fee', $honorData, 'honor');
        
        // ✅ 6. Promosi & Kegiatan
        $promosiData = [
            'Order banner prolanis',
        ];
        
        $this->createPengeluaranCategory('Promosi & Kegiatan', $promosiData, 'promosi');
        
        $this->command->info('✅ Master Pengeluaran data created successfully!');
    }
    
    /**
     * Create pengeluaran records for a specific category
     */
    private function createPengeluaranCategory(string $categoryName, array $items, string $kategori): void
    {
        $this->command->info("Creating {$categoryName} items...");
        
        foreach ($items as $item) {
            $data = [
                'kode_pengeluaran' => AutoCodeGeneratorService::generatePengeluaranCode(),
                'nama_pengeluaran' => $item,
                'kategori' => $kategori,
                'keterangan' => "Master data - {$categoryName}: {$item}",
                'input_by' => 1, // Admin user
                'tanggal' => now(),
                'nominal' => 0, // Will be filled when actually used
                'created_at' => now(),
                'updated_at' => now(),
            ];
            
            DB::table('pengeluaran')->insert($data);
            $this->command->info("  ✓ {$data['kode_pengeluaran']} - {$item}");
        }
    }
}