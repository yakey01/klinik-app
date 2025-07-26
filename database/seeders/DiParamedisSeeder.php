<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\DiParamedis;
use App\Models\Pegawai;
use App\Models\User;
use Carbon\Carbon;

class DiParamedisSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all paramedis
        $paramedisUsers = User::whereHas('role', fn($q) => $q->where('name', 'paramedis'))
            ->whereHas('pegawai', fn($q) => $q->where('jenis_pegawai', 'Paramedis'))
            ->with('pegawai')
            ->get();

        if ($paramedisUsers->isEmpty()) {
            $this->command->warn('No paramedis users found. Creating sample data...');
            
            // Create sample paramedis
            $pegawai = Pegawai::factory()->create([
                'jenis_pegawai' => 'Paramedis',
                'nama_lengkap' => 'Sample Paramedis',
                'aktif' => true,
            ]);
            
            $user = User::factory()->create([
                'pegawai_id' => $pegawai->id,
                'name' => $pegawai->nama_lengkap,
            ]);
            
            $paramedisUsers = collect([$user]);
        }

        $this->command->info("Creating DI Paramedis for {$paramedisUsers->count()} paramedis users...");

        foreach ($paramedisUsers as $user) {
            // Create DI for the last 30 days
            $startDate = Carbon::now()->subDays(30);
            $endDate = Carbon::now();

            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                // Skip weekends randomly (30% chance to work on weekend)
                if ($date->isWeekend() && fake()->boolean(70)) {
                    continue;
                }

                // Create DI for this date
                $shift = $this->determineShift($date);
                
                $di = DiParamedis::factory()->create([
                    'pegawai_id' => $user->pegawai_id,
                    'user_id' => $user->id,
                    'tanggal' => $date->format('Y-m-d'),
                    'shift' => $shift,
                    'jam_mulai' => $this->getShiftStartTime($shift),
                    'jam_selesai' => $this->getShiftEndTime($shift),
                    'status' => $this->determineStatus($date),
                ]);

                // Add approval info for approved/rejected status
                if (in_array($di->status, ['approved', 'rejected'])) {
                    $approver = User::whereHas('role', fn($q) => $q->whereIn('name', ['admin', 'manajer']))
                        ->inRandomOrder()
                        ->first();
                    
                    $di->update([
                        'approved_by' => $approver?->id,
                        'approved_at' => $date->copy()->addDay()->setTime(10, 0),
                        'rejection_reason' => $di->status === 'rejected' ? 
                            fake()->randomElement([
                                'Data tidak lengkap',
                                'Laporan kegiatan tidak sesuai',
                                'Perlu verifikasi lebih lanjut',
                            ]) : null,
                    ]);
                }
            }
        }

        $totalCreated = DiParamedis::count();
        $this->command->info("Successfully created {$totalCreated} DI Paramedis records.");
        
        // Show summary
        $summary = DiParamedis::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');
            
        $this->command->table(
            ['Status', 'Count'],
            $summary->map(fn($count, $status) => [$status, $count])->toArray()
        );
    }

    /**
     * Determine shift based on date patterns
     */
    private function determineShift($date): string
    {
        // Simple rotation pattern
        $dayOfMonth = $date->day;
        $shifts = ['Pagi', 'Siang', 'Malam'];
        
        return $shifts[($dayOfMonth - 1) % 3];
    }

    /**
     * Get shift start time
     */
    private function getShiftStartTime(string $shift): string
    {
        return match($shift) {
            'Pagi' => '07:00:00',
            'Siang' => '14:00:00',
            'Malam' => '21:00:00',
            default => '08:00:00',
        };
    }

    /**
     * Get shift end time
     */
    private function getShiftEndTime(string $shift): string
    {
        return match($shift) {
            'Pagi' => '14:00:00',
            'Siang' => '21:00:00',
            'Malam' => '07:00:00', // Next day
            default => '16:00:00',
        };
    }

    /**
     * Determine status based on date
     */
    private function determineStatus($date): string
    {
        $daysAgo = Carbon::now()->diffInDays($date);
        
        if ($daysAgo === 0) {
            // Today - could be draft or submitted
            return fake()->randomElement(['draft', 'submitted']);
        } elseif ($daysAgo === 1) {
            // Yesterday - likely submitted or approved
            return fake()->randomElement(['submitted', 'approved']);
        } elseif ($daysAgo <= 7) {
            // Last week - mostly approved, some rejected
            $rand = fake()->numberBetween(1, 100);
            if ($rand <= 80) {
                return 'approved';
            } elseif ($rand <= 95) {
                return 'rejected';
            } else {
                return 'submitted';
            }
        } else {
            // Older - all should be processed
            return fake()->numberBetween(1, 100) <= 90 ? 'approved' : 'rejected';
        }
    }
}