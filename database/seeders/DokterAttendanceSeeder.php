<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\User;
use App\Models\JadwalJaga;
use Carbon\Carbon;

class DokterAttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('👨‍⚕️ Creating attendance data for dokter users...');

        // Get all dokter users
        $dokterUsers = User::whereHas('role', function ($query) {
            $query->whereIn('name', ['dokter', 'dokter_gigi']);
        })->get();

        if ($dokterUsers->isEmpty()) {
            $this->command->warn('⚠️  No dokter users found. Please run DokterUserSeeder first.');
            return;
        }

        $attendanceCount = 0;
        $startDate = Carbon::now()->subMonths(2);
        $endDate = Carbon::now()->subDays(1); // Until yesterday

        foreach ($dokterUsers as $dokter) {
            $this->command->info("📋 Creating attendance for: {$dokter->name}");
            
            // Get jadwal jaga for this dokter
            $jadwalJagas = JadwalJaga::where('pegawai_id', $dokter->id)
                ->whereBetween('tanggal_jaga', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->where('status_jaga', 'Aktif')
                ->with('shiftTemplate')
                ->get();

            foreach ($jadwalJagas as $jadwal) {
                // 85% chance dokter hadir sesuai jadwal
                if (rand(1, 100) <= 85) {
                    $tanggalJaga = Carbon::parse($jadwal->tanggal_jaga);
                    
                    // Skip if attendance already exists
                    $existingAttendance = Attendance::where('user_id', $dokter->id)
                        ->whereDate('date', $tanggalJaga)
                        ->first();

                    if ($existingAttendance) {
                        continue;
                    }

                    // Calculate check in/out times based on shift
                    $jamMasuk = Carbon::parse($jadwal->tanggal_jaga)->setTimeFromTimeString($jadwal->shiftTemplate->jam_masuk);
                    $jamPulang = Carbon::parse($jadwal->tanggal_jaga)->setTimeFromTimeString($jadwal->shiftTemplate->jam_pulang);
                    
                    // Handle overnight shifts
                    if ($jadwal->shiftTemplate->jam_pulang < $jadwal->shiftTemplate->jam_masuk) {
                        $jamPulang->addDay();
                    }

                    // Add some realistic variation
                    $checkIn = $this->addTimeVariation($jamMasuk, -10, 30); // Can be early or late
                    $checkOut = $this->addTimeVariation($jamPulang, -30, 60); // Usually on time or late

                    // Determine attendance status
                    $status = $this->determineAttendanceStatus($checkIn, $jamMasuk, $checkOut, $jamPulang);

                    // Calculate work duration
                    $workDuration = $checkOut->diffInMinutes($checkIn);

                    Attendance::create([
                        'user_id' => $dokter->id,
                        'date' => $tanggalJaga->format('Y-m-d'),
                        'time_in' => $checkIn->format('H:i:s'),
                        'time_out' => $checkOut->format('H:i:s'),
                        'latlon_in' => '-6.2088,106.8456',
                        'latlon_out' => '-6.2088,106.8456',
                        'location_name_in' => 'Klinik Dokterku',
                        'location_name_out' => 'Klinik Dokterku',
                        'latitude' => -6.2088,
                        'longitude' => 106.8456,
                        'accuracy' => 5.0,
                        'checkout_latitude' => -6.2088,
                        'checkout_longitude' => 106.8456,
                        'checkout_accuracy' => 5.0,
                        'location_validated' => true,
                        'status' => $status == 'on_time' ? 'present' : 'late',
                        'notes' => $this->getRandomNotes(),
                        'created_at' => $tanggalJaga,
                        'updated_at' => $tanggalJaga
                    ]);

                    $attendanceCount++;
                }
            }

            // Also create some attendance for days without jadwal (emergency calls, etc.)
            $this->createEmergencyAttendance($dokter, $startDate, $endDate);
        }

        $this->command->info("✅ Created {$attendanceCount} attendance entries for dokter users");
    }

    /**
     * Create emergency attendance (dokter dipanggil darurat)
     */
    private function createEmergencyAttendance($dokter, $startDate, $endDate)
    {
        // 10% chance of emergency calls per month
        $emergencyDays = rand(2, 6);
        
        for ($i = 0; $i < $emergencyDays; $i++) {
            $randomDate = $this->getRandomDate($startDate, $endDate);
            
            // Skip if it's already a scheduled day or attendance exists
            $existingAttendance = Attendance::where('user_id', $dokter->id)
                ->whereDate('date', $randomDate)
                ->first();
                
            $existingSchedule = JadwalJaga::where('pegawai_id', $dokter->id)
                ->whereDate('tanggal_jaga', $randomDate)
                ->first();

            if ($existingAttendance || $existingSchedule) {
                continue;
            }

            // Emergency call times (usually at night or early morning)
            $checkIn = $randomDate->copy()->setTime(rand(2, 6), rand(0, 59));
            $checkOut = $checkIn->copy()->addHours(rand(2, 6));

            $workDuration = $checkOut->diffInMinutes($checkIn);

            Attendance::create([
                'user_id' => $dokter->id,
                'date' => $randomDate->format('Y-m-d'),
                'time_in' => $checkIn->format('H:i:s'),
                'time_out' => $checkOut->format('H:i:s'),
                'latlon_in' => '-6.2088,106.8456',
                'latlon_out' => '-6.2088,106.8456',
                'location_name_in' => 'Klinik Dokterku',
                'location_name_out' => 'Klinik Dokterku',
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                'accuracy' => 5.0,
                'checkout_latitude' => -6.2088,
                'checkout_longitude' => 106.8456,
                'checkout_accuracy' => 5.0,
                'location_validated' => true,
                'status' => 'present',
                'notes' => 'Panggilan darurat',
                'created_at' => $randomDate,
                'updated_at' => $randomDate
            ]);
        }
    }

    /**
     * Add time variation to simulate real attendance
     */
    private function addTimeVariation($baseTime, $minMinutes, $maxMinutes)
    {
        $variation = rand($minMinutes, $maxMinutes);
        return $baseTime->copy()->addMinutes($variation);
    }

    /**
     * Determine attendance status based on check in/out times
     */
    private function determineAttendanceStatus($checkIn, $scheduledIn, $checkOut, $scheduledOut)
    {
        $lateThreshold = 15; // 15 minutes late threshold
        $earlyLeaveThreshold = 30; // 30 minutes early leave threshold

        $minutesLate = $checkIn->diffInMinutes($scheduledIn, false);
        $minutesEarly = $scheduledOut->diffInMinutes($checkOut, false);

        if ($minutesLate > $lateThreshold && $minutesEarly > $earlyLeaveThreshold) {
            return 'late_and_early_leave';
        } elseif ($minutesLate > $lateThreshold) {
            return 'late';
        } elseif ($minutesEarly > $earlyLeaveThreshold) {
            return 'early_leave';
        } else {
            return 'on_time';
        }
    }

    /**
     * Get random date between start and end
     */
    private function getRandomDate($startDate, $endDate)
    {
        $timestamp = rand($startDate->timestamp, $endDate->timestamp);
        return Carbon::createFromTimestamp($timestamp);
    }

    /**
     * Get random notes for attendance
     */
    private function getRandomNotes()
    {
        $notes = [
            'Shift normal',
            'Shift rutin',
            'Pasien emergency',
            'Konsultasi urgent',
            'Operasi darurat',
            'Shift overtime',
            'Menggantikan rekan',
            null,
            null,
            null
        ];
        
        return $notes[array_rand($notes)];
    }
}