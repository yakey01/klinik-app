<?php

namespace Database\Seeders;

use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use App\Services\AutoCodeGeneratorService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FinancialTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating test data for financial records...');
        
        // Create some test pendapatan records with varied status
        $pendapatanData = [
            [
                'nama_pendapatan' => 'Konsultasi Umum',
                'sumber_pendapatan' => 'Umum',
                'is_aktif' => true,
                'status_validasi' => 'disetujui',
                'nominal' => 150000,
                'kategori' => 'konsultasi',
            ],
            [
                'nama_pendapatan' => 'Pemeriksaan Gigi',
                'sumber_pendapatan' => 'Gigi',
                'is_aktif' => true,
                'status_validasi' => 'pending',
                'nominal' => 200000,
                'kategori' => 'tindakan_medis',
            ],
            [
                'nama_pendapatan' => 'Tindakan Medis',
                'sumber_pendapatan' => 'Umum',
                'is_aktif' => false,
                'status_validasi' => 'disetujui',
                'nominal' => 300000,
                'kategori' => 'tindakan_medis',
            ],
            [
                'nama_pendapatan' => 'Laboratorium',
                'sumber_pendapatan' => 'Umum',
                'is_aktif' => true,
                'status_validasi' => 'pending',
                'nominal' => 100000,
                'kategori' => 'laboratorium',
            ],
            [
                'nama_pendapatan' => 'Radiologi',
                'sumber_pendapatan' => 'Umum',
                'is_aktif' => true,
                'status_validasi' => 'ditolak',
                'nominal' => 250000,
                'kategori' => 'radiologi',
            ],
        ];
        
        foreach ($pendapatanData as $data) {
            $data['kode_pendapatan'] = AutoCodeGeneratorService::generatePendapatanCode();
            $data['input_by'] = 1; // Admin user
            $data['tanggal'] = now();
            $data['keterangan'] = 'Test data - ' . $data['nama_pendapatan'];
            $data['created_at'] = now();
            $data['updated_at'] = now();
            
            // Add validation fields if approved
            if ($data['status_validasi'] === 'disetujui') {
                $data['validasi_by'] = 1;
                $data['validasi_at'] = now();
            }
            
            DB::table('pendapatan')->insert($data);
            $this->command->info("Created pendapatan: {$data['kode_pendapatan']} - {$data['nama_pendapatan']} ({$data['status_validasi']})");
        }
        
        // Create some test pengeluaran records
        $pengeluaranData = [
            [
                'nama_pengeluaran' => 'Alat Tulis Kantor',
                'kategori' => 'operasional',
                'keterangan' => 'Pembelian alat tulis untuk operasional kantor',
            ],
            [
                'nama_pengeluaran' => 'Obat-obatan',
                'kategori' => 'medis',
                'keterangan' => 'Pembelian obat-obatan untuk klinik',
            ],
            [
                'nama_pengeluaran' => 'Maintenance AC',
                'kategori' => 'maintenance',
                'keterangan' => 'Biaya maintenance AC ruangan',
            ],
        ];
        
        foreach ($pengeluaranData as $data) {
            $data['kode_pengeluaran'] = AutoCodeGeneratorService::generatePengeluaranCode();
            $data['input_by'] = 1; // Admin user
            $data['tanggal'] = now();
            $data['nominal'] = 0;
            $data['created_at'] = now();
            $data['updated_at'] = now();
            
            DB::table('pengeluaran')->insert($data);
            $this->command->info("Created pengeluaran: {$data['kode_pengeluaran']} - {$data['nama_pengeluaran']}");
        }
        
        $this->command->info('✅ Financial test data created successfully!');
    }
}