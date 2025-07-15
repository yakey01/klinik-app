<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\JumlahPasienHarian;
use App\Models\Dokter;
use App\Models\User;
use Carbon\Carbon;

class JumlahPasienHarianSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Only run in development environment
        if (!app()->environment(['local', 'development'])) {
            $this->command->info('JumlahPasienHarian seeder skipped in production environment');
            return;
        }
        // Get existing dokters
        $dokters = Dokter::all();
        $petugasUser = User::where('role_id', 4)->first(); // Role petugas

        if ($dokters->isEmpty() || !$petugasUser) {
            $this->command->info('Pastikan ada data dokter dan user petugas untuk seeder ini.');
            return;
        }

        // Create sample data for the last 7 days
        for ($i = 6; $i >= 0; $i--) {
            $tanggal = Carbon::now()->subDays($i);
            
            foreach (['umum', 'gigi'] as $poli) {
                $dokter = $dokters->random();
                
                JumlahPasienHarian::create([
                    'tanggal' => $tanggal,
                    'poli' => $poli,
                    'jumlah_pasien_umum' => rand(5, 25),
                    'jumlah_pasien_bpjs' => rand(10, 30),
                    'dokter_id' => $dokter->id,
                    'input_by' => $petugasUser->id,
                    'created_at' => $tanggal->copy()->addHours(rand(8, 16)),
                    'updated_at' => $tanggal->copy()->addHours(rand(8, 16)),
                ]);
            }
        }

        $this->command->info('Sample data jumlah pasien harian berhasil dibuat.');
    }
}