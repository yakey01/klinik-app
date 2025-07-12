<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ShiftTemplate;
use App\Models\JadwalJaga;
use App\Models\PermohonanCuti;
use App\Models\User;
use Carbon\Carbon;

class CalendarSeeder extends Seeder
{
    public function run(): void
    {
        // Create Shift Templates
        $shifts = [
            [
                'nama_shift' => 'Pagi',
                'jam_masuk' => '07:00:00',
                'jam_pulang' => '15:00:00',
            ],
            [
                'nama_shift' => 'Siang',
                'jam_masuk' => '15:00:00',
                'jam_pulang' => '23:00:00',
            ],
            [
                'nama_shift' => 'Malam',
                'jam_masuk' => '23:00:00',
                'jam_pulang' => '07:00:00',
            ],
            [
                'nama_shift' => 'Full Day',
                'jam_masuk' => '08:00:00',
                'jam_pulang' => '17:00:00',
            ]
        ];

        foreach ($shifts as $shift) {
            ShiftTemplate::create($shift);
        }

        $this->command->info('Shift templates created successfully!');

        // Get users for scheduling
        $users = User::whereIn('email', [
            'dokter@dokterku.com',
            'perawat@dokterku.com',
            'asisten@dokterku.com'
        ])->get();

        if ($users->isEmpty()) {
            $this->command->warn('No users found for scheduling. Please run UserSeeder first.');
            return;
        }

        $shiftTemplates = ShiftTemplate::all();

        // Create sample schedules for next 30 days
        $startDate = Carbon::now();
        for ($i = 0; $i < 30; $i++) {
            $date = $startDate->copy()->addDays($i);
            
            foreach ($users as $user) {
                // Random chance to have a schedule
                if (rand(0, 100) < 70) { // 70% chance
                    $peran = match($user->email) {
                        'dokter@dokterku.com' => 'Dokter',
                        'perawat@dokterku.com' => 'Paramedis',
                        'asisten@dokterku.com' => 'NonParamedis',
                        default => 'Paramedis'
                    };

                    JadwalJaga::create([
                        'tanggal_jaga' => $date,
                        'shift_template_id' => $shiftTemplates->random()->id,
                        'pegawai_id' => $user->id,
                        'unit_instalasi' => collect(['IGD', 'Rawat Inap', 'Poli Umum', 'Laboratorium'])->random(),
                        'peran' => $peran,
                        'status_jaga' => collect(['Aktif', 'Aktif', 'Aktif', 'OnCall'])->random(), // 75% aktif
                        'keterangan' => collect([null, 'Backup coverage', 'Regular schedule', 'Special assignment'])->random(),
                    ]);
                }
            }
        }

        $this->command->info('Jadwal jaga created successfully!');

        // Create sample leave requests
        foreach ($users as $user) {
            for ($i = 0; $i < 3; $i++) {
                $startDate = Carbon::now()->addDays(rand(5, 60));
                $duration = rand(1, 5);
                
                PermohonanCuti::create([
                    'pegawai_id' => $user->id,
                    'tanggal_mulai' => $startDate,
                    'tanggal_selesai' => $startDate->copy()->addDays($duration - 1),
                    'jenis_cuti' => collect(['Cuti Tahunan', 'Sakit', 'Izin', 'Dinas Luar'])->random(),
                    'keterangan' => collect([
                        'Liburan keluarga',
                        'Keperluan pribadi',
                        'Perawatan kesehatan',
                        'Acara keluarga',
                        'Istirahat'
                    ])->random(),
                    'status' => collect(['Menunggu', 'Disetujui', 'Ditolak'])->random(),
                    'disetujui_oleh' => rand(0, 100) < 70 ? User::where('email', 'admin@dokterku.com')->first()?->id : null,
                    'tanggal_keputusan' => rand(0, 100) < 50 ? Carbon::now()->subDays(rand(1, 10)) : null,
                ]);
            }
        }

        $this->command->info('Permohonan cuti created successfully!');
        $this->command->info('Calendar system seeded successfully!');
    }
}