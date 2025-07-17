<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JadwalJaga;
use App\Models\User;
use App\Models\ShiftTemplate;
use Carbon\Carbon;

class DokterJadwalJagaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🏥 Creating jadwal jaga for dokter users...');

        // Get all dokter users
        $dokterUsers = User::whereHas('role', function ($query) {
            $query->whereIn('name', ['dokter', 'dokter_gigi']);
        })->get();

        if ($dokterUsers->isEmpty()) {
            $this->command->warn('⚠️  No dokter users found. Please run DokterUserSeeder first.');
            return;
        }

        // Get shift templates
        $shiftTemplates = ShiftTemplate::all();
        
        if ($shiftTemplates->isEmpty()) {
            $this->command->warn('⚠️  No shift templates found. Creating default templates...');
            $this->createDefaultShiftTemplates();
            $shiftTemplates = ShiftTemplate::all();
        }

        $jadwalCount = 0;
        $startDate = Carbon::now()->subMonths(2)->startOfMonth();
        $endDate = Carbon::now()->addMonths(1)->endOfMonth();

        foreach ($dokterUsers as $dokter) {
            $this->command->info("📅 Creating schedule for: {$dokter->name}");
            
            $currentDate = $startDate->copy();
            
            while ($currentDate <= $endDate) {
                // Skip Sundays for regular schedule
                if ($currentDate->dayOfWeek === 0) {
                    $currentDate->addDay();
                    continue;
                }

                // Assign random shift (skip some days for variety)
                if (rand(1, 10) <= 7) { // 70% chance of having a shift
                    $shiftTemplate = $shiftTemplates->random();
                    
                    // Check if there's already a schedule for this date and shift
                    $existingSchedule = JadwalJaga::where('pegawai_id', $dokter->id)
                        ->where('tanggal_jaga', $currentDate->format('Y-m-d'))
                        ->where('shift_template_id', $shiftTemplate->id)
                        ->first();

                    if (!$existingSchedule) {
                        JadwalJaga::create([
                            'tanggal_jaga' => $currentDate->format('Y-m-d'),
                            'shift_template_id' => $shiftTemplate->id,
                            'pegawai_id' => $dokter->id,
                            'unit_kerja' => 'Dokter Jaga',
                            'peran' => 'Dokter',
                            'status_jaga' => $this->getRandomStatus(),
                            'keterangan' => $this->getRandomKeterangan()
                        ]);
                        
                        $jadwalCount++;
                    }
                }

                $currentDate->addDay();
            }
        }

        $this->command->info("✅ Created {$jadwalCount} jadwal jaga entries for dokter users");
    }

    /**
     * Create default shift templates if none exist
     */
    private function createDefaultShiftTemplates()
    {
        $shifts = [
            [
                'nama_shift' => 'Pagi',
                'jam_masuk' => '07:00',
                'jam_pulang' => '15:00',
                'durasi_jam' => 8,
                'keterangan' => 'Shift pagi untuk dokter'
            ],
            [
                'nama_shift' => 'Siang',
                'jam_masuk' => '15:00',
                'jam_pulang' => '23:00',
                'durasi_jam' => 8,
                'keterangan' => 'Shift siang untuk dokter'
            ],
            [
                'nama_shift' => 'Malam',
                'jam_masuk' => '23:00',
                'jam_pulang' => '07:00',
                'durasi_jam' => 8,
                'keterangan' => 'Shift malam untuk dokter'
            ]
        ];

        foreach ($shifts as $shift) {
            ShiftTemplate::create($shift);
        }
    }

    /**
     * Get random status for jadwal jaga
     */
    private function getRandomStatus()
    {
        $statuses = ['Aktif', 'Aktif', 'Aktif', 'Aktif', 'Cuti', 'Izin']; // Most are active
        return $statuses[array_rand($statuses)];
    }

    /**
     * Get random keterangan
     */
    private function getRandomKeterangan()
    {
        $keterangans = [
            'Jadwal normal',
            'Jadwal rutin',
            'Shift tambahan',
            'Pengganti rekan',
            null, // Some without keterangan
            null,
            null
        ];
        
        return $keterangans[array_rand($keterangans)];
    }
}