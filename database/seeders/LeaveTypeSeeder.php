<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $leaveTypes = [
            [
                'nama' => 'Cuti Tahunan',
                'alokasi_hari' => 12,
                'description' => 'Cuti tahunan untuk pegawai dengan alokasi 12 hari per tahun',
                'active' => true,
            ],
            [
                'nama' => 'Sakit',
                'alokasi_hari' => null, // Tidak terbatas
                'description' => 'Cuti sakit untuk pegawai yang sedang sakit (perlu surat dokter untuk > 2 hari)',
                'active' => true,
            ],
            [
                'nama' => 'Izin',
                'alokasi_hari' => null, // Tidak terbatas
                'description' => 'Izin keperluan mendadak atau urusan pribadi',
                'active' => true,
            ],
            [
                'nama' => 'Dinas Luar',
                'alokasi_hari' => null, // Tidak terbatas
                'description' => 'Tugas dinas di luar klinik atau perjalanan dinas',
                'active' => true,
            ],
            [
                'nama' => 'Ibadah',
                'alokasi_hari' => 5,
                'description' => 'Cuti untuk keperluan ibadah khusus (haji, umroh, dll)',
                'active' => true,
            ],
            [
                'nama' => 'Melahirkan',
                'alokasi_hari' => 90,
                'description' => 'Cuti melahirkan untuk pegawai wanita',
                'active' => true,
            ],
            [
                'nama' => 'Besar',
                'alokasi_hari' => 2,
                'description' => 'Cuti besar (setelah 6 tahun masa kerja)',
                'active' => false, // Default non-aktif karena jarang digunakan
            ],
        ];

        foreach ($leaveTypes as $leaveType) {
            LeaveType::updateOrCreate(
                ['nama' => $leaveType['nama']],
                $leaveType
            );
        }
    }
}