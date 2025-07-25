<?php

namespace App\Services;

use App\Models\User;
use App\Models\JadwalJaga;
use App\Models\WorkLocation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class AttendanceValidationService
{
    /**
     * Validate user's schedule for today
     */
    public function validateSchedule(User $user, Carbon $date = null): array
    {
        $date = $date ?? Carbon::today();
        $currentTime = Carbon::now();
        
        // Get user's schedule for today
        $jadwalJaga = JadwalJaga::where('pegawai_id', $user->id)
            ->whereDate('tanggal_jaga', $date)
            ->with('shiftTemplate')
            ->first();
        
        if (!$jadwalJaga) {
            return [
                'valid' => false,
                'message' => 'Anda tidak memiliki jadwal jaga hari ini. Hubungi admin untuk informasi lebih lanjut.',
                'code' => 'NO_SCHEDULE'
            ];
        }
        
        // Check status with case insensitive comparison
        if (strtolower($jadwalJaga->status_jaga) !== 'aktif') {
            return [
                'valid' => false,
                'message' => "Jadwal jaga Anda hari ini berstatus '{$jadwalJaga->status_jaga}'. Hubungi admin untuk informasi lebih lanjut.",
                'code' => 'SCHEDULE_INACTIVE',
                'schedule_status' => $jadwalJaga->status_jaga
            ];
        }
        
        return [
            'valid' => true,
            'message' => 'Jadwal jaga valid',
            'code' => 'VALID_SCHEDULE',
            'jadwal_jaga' => $jadwalJaga
        ];
    }
    
    /**
     * Validate user's work location and geofencing
     */
    public function validateWorkLocation(User $user, float $latitude, float $longitude, ?float $accuracy = null): array
    {
        // Always refresh user relationship to get latest work location data
        $user->load(['workLocation', 'location']);
        
        // Clear any cached work location data
        Cache::forget("user_work_location_{$user->id}");
        
        // Get fresh work location data (force refresh from database)
        $workLocation = WorkLocation::where('id', $user->work_location_id)
            ->where('is_active', true)
            ->first();
        
        if (!$workLocation) {
            // Double-check by loading fresh user data
            $freshUser = User::find($user->id);
            if ($freshUser && $freshUser->work_location_id) {
                $workLocation = WorkLocation::find($freshUser->work_location_id);
            }
            
            // Fallback to legacy location if work location not set
            if (!$workLocation) {
                $location = $user->location;
                if (!$location) {
                    // Log for debugging
                    \Log::warning('User has no work location assigned', [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'work_location_id' => $user->work_location_id,
                        'location_id' => $user->location_id
                    ]);
                    
                    return [
                        'valid' => false,
                        'message' => 'Anda belum memiliki lokasi kerja yang ditetapkan. Hubungi admin untuk pengaturan.',
                        'code' => 'NO_WORK_LOCATION'
                    ];
                }
                
                // Use legacy location validation
                if (!$location->isWithinGeofence($latitude, $longitude)) {
                    $distance = $location->getDistanceFrom($latitude, $longitude);
                    return [
                        'valid' => false,
                        'message' => "Anda berada di luar area kerja yang diizinkan. Jarak Anda dari lokasi kerja adalah " . round($distance) . " meter, sedangkan radius yang diizinkan adalah " . $location->radius . " meter.",
                        'code' => 'OUTSIDE_GEOFENCE',
                        'data' => [
                            'distance' => round($distance),
                            'allowed_radius' => $location->radius,
                            'location_name' => $location->name
                        ]
                    ];
                }
                
                return [
                    'valid' => true,
                    'message' => 'Lokasi valid (menggunakan lokasi lama)',
                    'code' => 'VALID_LEGACY_LOCATION',
                    'location' => $location
                ];
            }
        }
        
        // Validate work location is active
        if (!$workLocation->is_active) {
            return [
                'valid' => false,
                'message' => 'Lokasi kerja Anda sedang tidak aktif. Hubungi admin untuk informasi lebih lanjut.',
                'code' => 'WORK_LOCATION_INACTIVE'
            ];
        }
        
        // Validate geofencing with WorkLocation
        if (!$workLocation->isWithinGeofence($latitude, $longitude, $accuracy)) {
            $distance = $workLocation->calculateDistance($latitude, $longitude);
            
            return [
                'valid' => false,
                'message' => "Anda berada di luar area kerja yang diizinkan. Jarak Anda dari lokasi kerja adalah " . round($distance) . " meter, sedangkan radius yang diizinkan adalah " . $workLocation->radius_meters . " meter.",
                'code' => 'OUTSIDE_GEOFENCE',
                'data' => [
                    'distance' => round($distance),
                    'allowed_radius' => $workLocation->radius_meters,
                    'location_name' => $workLocation->name,
                    'strict_geofence' => $workLocation->strict_geofence,
                    'gps_accuracy_required' => $workLocation->gps_accuracy_required
                ]
            ];
        }
        
        return [
            'valid' => true,
            'message' => 'Lokasi kerja valid',
            'code' => 'VALID_WORK_LOCATION',
            'work_location' => $workLocation
        ];
    }
    
    /**
     * Validate shift time for check-in with enhanced tolerance settings
     */
    public function validateShiftTime(JadwalJaga $jadwalJaga, Carbon $currentTime = null): array
    {
        $currentTime = $currentTime ?? Carbon::now();
        $shiftTemplate = $jadwalJaga->shiftTemplate;
        
        if (!$shiftTemplate) {
            return [
                'valid' => false,
                'message' => 'Template shift tidak ditemukan. Hubungi admin untuk informasi lebih lanjut.',
                'code' => 'NO_SHIFT_TEMPLATE'
            ];
        }
        
        // Get shift start and end times with flexible parsing
        // Handle both H:i format and full datetime format
        try {
            if (strlen($shiftTemplate->jam_masuk) > 5) {
                // Full datetime format
                $shiftStart = Carbon::parse($shiftTemplate->jam_masuk);
            } else {
                // H:i format
                $shiftStart = Carbon::createFromFormat('H:i', $shiftTemplate->jam_masuk);
            }
        } catch (Exception $e) {
            $shiftStart = Carbon::parse($shiftTemplate->jam_masuk);
        }
        
        try {
            $jamKeluar = $shiftTemplate->jam_pulang ?? $shiftTemplate->jam_keluar ?? null;
            if (!$jamKeluar) {
                return [
                    'valid' => false,
                    'message' => 'Jam keluar shift tidak ditemukan. Hubungi admin untuk informasi lebih lanjut.',
                    'code' => 'NO_SHIFT_END_TIME'
                ];
            }
            
            if (strlen($jamKeluar) > 5) {
                // Full datetime format
                $shiftEnd = Carbon::parse($jamKeluar);
            } else {
                // H:i format
                $shiftEnd = Carbon::createFromFormat('H:i', $jamKeluar);
            }
        } catch (Exception $e) {
            $shiftEnd = Carbon::parse($jamKeluar);
        }
        
        // Get work location tolerance settings with enhanced configuration
        $user = $jadwalJaga->pegawai;
        $workLocation = $user->workLocation;
        
        // Use new tolerance fields or fallback to defaults
        $lateToleranceMinutes = $workLocation ? $workLocation->late_tolerance_minutes ?? 15 : 15;
        $checkInBeforeShiftMinutes = $workLocation ? $workLocation->checkin_before_shift_minutes ?? 30 : 30;
        
        // Calculate enhanced check-in window
        $checkInEarliestTime = $shiftStart->copy()->subMinutes($checkInBeforeShiftMinutes);
        $checkInLatestTime = $shiftStart->copy()->addMinutes($lateToleranceMinutes);
        
        // Check if current time is within allowed check-in window
        $currentTimeOnly = Carbon::createFromFormat('H:i:s', $currentTime->format('H:i:s'));
        
        // Too early check
        if ($currentTimeOnly->lt($checkInEarliestTime)) {
            return [
                'valid' => false,
                'message' => "Terlalu awal untuk check-in. Anda dapat check-in mulai pukul {$checkInEarliestTime->format('H:i')} ({$checkInBeforeShiftMinutes} menit sebelum shift dimulai).",
                'code' => 'TOO_EARLY',
                'data' => [
                    'shift_start' => $shiftStart->format('H:i'),
                    'check_in_earliest' => $checkInEarliestTime->format('H:i'),
                    'check_in_latest' => $checkInLatestTime->format('H:i'),
                    'current_time' => $currentTimeOnly->format('H:i'),
                    'tolerance_settings' => [
                        'late_tolerance_minutes' => $lateToleranceMinutes,
                        'checkin_before_shift_minutes' => $checkInBeforeShiftMinutes
                    ]
                ]
            ];
        }
        
        // Late check-in but still within tolerance
        if ($currentTimeOnly->gt($checkInLatestTime)) {
            $lateMinutes = $currentTimeOnly->diffInMinutes($shiftStart);
            
            // Check if exceeds maximum late tolerance 
            if ($lateMinutes > ($lateToleranceMinutes + 30)) { // Allow extra 30 minutes buffer
                return [
                    'valid' => false,
                    'message' => "Check-in terlalu terlambat ({$lateMinutes} menit). Batas maksimal toleransi adalah {$lateToleranceMinutes} menit. Hubungi supervisor untuk approval manual.",
                    'code' => 'TOO_LATE',
                    'data' => [
                        'shift_start' => $shiftStart->format('H:i'),
                        'late_minutes' => $lateMinutes,
                        'max_tolerance_minutes' => $lateToleranceMinutes,
                        'current_time' => $currentTimeOnly->format('H:i')
                    ]
                ];
            }
            
            return [
                'valid' => true, // Still valid but late
                'message' => "Check-in terlambat {$lateMinutes} menit dari jadwal shift ({$shiftStart->format('H:i')}). Status: Terlambat dengan toleransi.",
                'code' => 'VALID_BUT_LATE',
                'data' => [
                    'shift_start' => $shiftStart->format('H:i'),
                    'late_minutes' => $lateMinutes,
                    'tolerance_minutes' => $lateToleranceMinutes,
                    'within_tolerance' => $lateMinutes <= $lateToleranceMinutes,
                    'status' => $lateMinutes <= $lateToleranceMinutes ? 'late_within_tolerance' : 'late_beyond_tolerance'
                ]
            ];
        }
        
        // On-time or early (within allowed window)
        $earlyMinutes = $currentTimeOnly->lt($shiftStart) ? $shiftStart->diffInMinutes($currentTimeOnly) : 0;
        
        return [
            'valid' => true,
            'message' => $earlyMinutes > 0 
                ? "Check-in {$earlyMinutes} menit sebelum shift dimulai. Status: Tepat waktu." 
                : 'Check-in tepat waktu.',
            'code' => 'ON_TIME',
            'data' => [
                'shift_start' => $shiftStart->format('H:i'),
                'shift_end' => $shiftEnd->format('H:i'),
                'early_minutes' => $earlyMinutes,
                'check_in_window' => [
                    'earliest' => $checkInEarliestTime->format('H:i'),
                    'latest' => $checkInLatestTime->format('H:i')
                ],
                'tolerance_settings' => [
                    'late_tolerance_minutes' => $lateToleranceMinutes,
                    'checkin_before_shift_minutes' => $checkInBeforeShiftMinutes
                ]
            ]
        ];
    }
    
    /**
     * Validate shift and location compatibility
     */
    public function validateShiftLocationCompatibility(JadwalJaga $jadwalJaga, WorkLocation $workLocation): array
    {
        $shiftTemplate = $jadwalJaga->shiftTemplate;
        
        if (!$shiftTemplate) {
            return [
                'valid' => true, // Allow check-in even without shift template
                'message' => 'Template shift tidak ditemukan, namun check-in diizinkan',
                'code' => 'NO_SHIFT_TEMPLATE_ALLOW'
            ];
        }
        
        // Check if shift is allowed at this work location
        if (!$workLocation->isShiftAllowed($shiftTemplate->nama_shift)) {
            return [
                'valid' => false,
                'message' => "Shift '{$shiftTemplate->nama_shift}' tidak diizinkan di lokasi '{$workLocation->name}'. Hubungi admin untuk informasi lebih lanjut.",
                'code' => 'SHIFT_NOT_ALLOWED',
                'data' => [
                    'shift_name' => $shiftTemplate->nama_shift,
                    'location_name' => $workLocation->name,
                    'allowed_shifts' => $workLocation->allowed_shifts
                ]
            ];
        }
        
        return [
            'valid' => true,
            'message' => 'Shift dan lokasi kompatibel',
            'code' => 'COMPATIBLE'
        ];
    }
    
    /**
     * Validate check-out request
     */
    public function validateCheckout(User $user, float $latitude, float $longitude, ?float $accuracy = null, Carbon $date = null): array
    {
        $date = $date ?? Carbon::today();
        
        // 1. Check if user has checked in today
        $attendance = \App\Models\Attendance::getTodayAttendance($user->id);
        
        if (!$attendance) {
            return [
                'valid' => false,
                'message' => 'Anda belum melakukan check-in hari ini. Silakan check-in terlebih dahulu.',
                'code' => 'NOT_CHECKED_IN'
            ];
        }
        
        // 2. Check if already checked out
        if ($attendance->hasCheckedOut()) {
            return [
                'valid' => false,
                'message' => 'Anda sudah melakukan check-out hari ini.',
                'code' => 'ALREADY_CHECKED_OUT',
                'data' => [
                    'time_in' => $attendance->time_in->format('H:i:s'),
                    'time_out' => $attendance->time_out->format('H:i:s'),
                    'duration' => $attendance->formatted_work_duration
                ]
            ];
        }
        
        // 3. Validate work location for checkout
        $locationValidation = $this->validateWorkLocation($user, $latitude, $longitude, $accuracy);
        
        if (!$locationValidation['valid']) {
            return [
                'valid' => false,
                'message' => 'Check-out gagal: ' . $locationValidation['message'],
                'code' => $locationValidation['code'],
                'data' => $locationValidation['data'] ?? null
            ];
        }
        
        // 4. Validate check-out time with tolerance settings
        $currentTime = Carbon::now();
        $currentWorkMinutes = $currentTime->diffInMinutes($attendance->time_in);
        
        // Get user's schedule and work location for tolerance settings
        $scheduleValidation = $this->validateSchedule($user, $date);
        if ($scheduleValidation['valid']) {
            $jadwalJaga = $scheduleValidation['jadwal_jaga'];
            $shiftTemplate = $jadwalJaga->shiftTemplate;
            
            if ($shiftTemplate) {
                // Handle flexible time format parsing for checkout validation
                try {
                    $jamKeluar = $shiftTemplate->jam_pulang ?? $shiftTemplate->jam_keluar ?? null;
                    if (!$jamKeluar) {
                        // Skip shift time validation if no end time
                        return [
                            'valid' => true,
                            'message' => 'Check-out diizinkan (tidak ada jam keluar yang ditetapkan)',
                            'code' => 'VALID_CHECKOUT_NO_END_TIME',
                            'attendance' => $attendance,
                            'work_location' => $locationValidation['work_location'] ?? $locationValidation['location'] ?? null,
                            'work_duration_minutes' => $currentWorkMinutes
                        ];
                    }
                    
                    if (strlen($jamKeluar) > 5) {
                        // Full datetime format
                        $shiftEnd = Carbon::parse($jamKeluar);
                    } else {
                        // H:i format
                        $shiftEnd = Carbon::createFromFormat('H:i', $jamKeluar);
                    }
                } catch (Exception $e) {
                    $shiftEnd = Carbon::parse($jamKeluar);
                }
                
                $currentTimeOnly = Carbon::createFromFormat('H:i:s', $currentTime->format('H:i:s'));
                
                // Get work location tolerance settings
                $workLocation = $user->workLocation;
                $earlyDepartureToleranceMinutes = $workLocation ? $workLocation->early_departure_tolerance_minutes ?? 15 : 15;
                $checkoutAfterShiftMinutes = $workLocation ? $workLocation->checkout_after_shift_minutes ?? 60 : 60;
                
                // Calculate checkout window
                $checkoutEarliestTime = $shiftEnd->copy()->subMinutes($earlyDepartureToleranceMinutes);
                $checkoutLatestTime = $shiftEnd->copy()->addMinutes($checkoutAfterShiftMinutes);
                
                // Check if checkout is too early
                if ($currentTimeOnly->lt($checkoutEarliestTime)) {
                    $earlyMinutes = $checkoutEarliestTime->diffInMinutes($currentTimeOnly);
                    return [
                        'valid' => false,
                        'message' => "Check-out terlalu awal. Anda dapat check-out mulai pukul {$checkoutEarliestTime->format('H:i')} ({$earlyMinutes} menit lagi).",
                        'code' => 'CHECKOUT_TOO_EARLY',
                        'data' => [
                            'shift_end' => $shiftEnd->format('H:i'),
                            'checkout_earliest' => $checkoutEarliestTime->format('H:i'),
                            'checkout_latest' => $checkoutLatestTime->format('H:i'),
                            'current_time' => $currentTimeOnly->format('H:i'),
                            'early_minutes' => $earlyMinutes,
                            'tolerance_settings' => [
                                'early_departure_tolerance_minutes' => $earlyDepartureToleranceMinutes,
                                'checkout_after_shift_minutes' => $checkoutAfterShiftMinutes
                            ]
                        ]
                    ];
                }
                
                // Check if checkout is too late (optional warning)
                if ($currentTimeOnly->gt($checkoutLatestTime)) {
                    $lateMinutes = $currentTimeOnly->diffInMinutes($shiftEnd);
                    // Still allow but with warning message
                    return [
                        'valid' => true,
                        'message' => "Check-out sangat terlambat ({$lateMinutes} menit setelah shift berakhir). Durasi kerja mungkin termasuk lembur.",
                        'code' => 'CHECKOUT_VERY_LATE',
                        'attendance' => $attendance,
                        'work_location' => $locationValidation['work_location'] ?? $locationValidation['location'] ?? null,
                        'work_duration_minutes' => $currentWorkMinutes,
                        'data' => [
                            'shift_end' => $shiftEnd->format('H:i'),
                            'late_minutes' => $lateMinutes,
                            'overtime_likely' => true
                        ]
                    ];
                }
                
                // Early departure within tolerance
                if ($currentTimeOnly->lt($shiftEnd)) {
                    $earlyMinutes = $shiftEnd->diffInMinutes($currentTimeOnly);
                    return [
                        'valid' => true,
                        'message' => "Check-out {$earlyMinutes} menit sebelum shift berakhir. Status: Dalam toleransi.",
                        'code' => 'CHECKOUT_EARLY_TOLERANCE',
                        'attendance' => $attendance,
                        'work_location' => $locationValidation['work_location'] ?? $locationValidation['location'] ?? null,
                        'work_duration_minutes' => $currentWorkMinutes,
                        'data' => [
                            'shift_end' => $shiftEnd->format('H:i'),
                            'early_minutes' => $earlyMinutes,
                            'within_tolerance' => true
                        ]
                    ];
                }
            }
        }
        
        // 5. Check minimum work duration to prevent accidental check-outs
        $minimumWorkMinutes = 30; // 30 minutes minimum
        if ($currentWorkMinutes < $minimumWorkMinutes) {
            return [
                'valid' => false,
                'message' => "Check-out terlalu cepat. Minimal bekerja {$minimumWorkMinutes} menit. Anda baru bekerja {$currentWorkMinutes} menit.",
                'code' => 'MINIMUM_WORK_TIME_NOT_MET',
                'data' => [
                    'current_work_minutes' => $currentWorkMinutes,
                    'minimum_required_minutes' => $minimumWorkMinutes,
                    'time_in' => $attendance->time_in->format('H:i:s')
                ]
            ];
        }
        
        return [
            'valid' => true,
            'message' => 'Check-out diizinkan',
            'code' => 'VALID_CHECKOUT',
            'attendance' => $attendance,
            'work_location' => $locationValidation['work_location'] ?? $locationValidation['location'] ?? null,
            'work_duration_minutes' => $currentWorkMinutes
        ];
    }

    /**
     * Comprehensive validation for check-in
     */
    public function validateCheckin(User $user, float $latitude, float $longitude, ?float $accuracy = null, Carbon $date = null): array
    {
        $validationResults = [];
        
        // 1. Validate schedule
        $scheduleValidation = $this->validateSchedule($user, $date);
        $validationResults['schedule'] = $scheduleValidation;
        
        if (!$scheduleValidation['valid']) {
            return [
                'valid' => false,
                'message' => $scheduleValidation['message'],
                'code' => $scheduleValidation['code'],
                'validations' => $validationResults
            ];
        }
        
        $jadwalJaga = $scheduleValidation['jadwal_jaga'];
        
        // 2. Validate work location
        $locationValidation = $this->validateWorkLocation($user, $latitude, $longitude, $accuracy);
        $validationResults['location'] = $locationValidation;
        
        if (!$locationValidation['valid']) {
            return [
                'valid' => false,
                'message' => $locationValidation['message'],
                'code' => $locationValidation['code'],
                'data' => $locationValidation['data'] ?? null,
                'validations' => $validationResults
            ];
        }
        
        // 3. Validate shift time
        $timeValidation = $this->validateShiftTime($jadwalJaga);
        $validationResults['time'] = $timeValidation;
        
        // 4. Validate shift-location compatibility if work location is available
        if (isset($locationValidation['work_location'])) {
            $compatibilityValidation = $this->validateShiftLocationCompatibility($jadwalJaga, $locationValidation['work_location']);
            $validationResults['compatibility'] = $compatibilityValidation;
            
            if (!$compatibilityValidation['valid']) {
                return [
                    'valid' => false,
                    'message' => $compatibilityValidation['message'],
                    'code' => $compatibilityValidation['code'],
                    'data' => $compatibilityValidation['data'] ?? null,
                    'validations' => $validationResults
                ];
            }
        }
        
        // Determine overall validity and message
        $isLate = $timeValidation['code'] === 'LATE_CHECKIN';
        $message = $isLate ? $timeValidation['message'] : 'Semua validasi berhasil - check-in diizinkan';
        
        return [
            'valid' => true,
            'message' => $message,
            'code' => $isLate ? 'VALID_BUT_LATE' : 'VALID',
            'jadwal_jaga' => $jadwalJaga,
            'work_location' => $locationValidation['work_location'] ?? $locationValidation['location'] ?? null,
            'validations' => $validationResults
        ];
    }
}