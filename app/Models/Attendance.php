<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Attendance extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'location_id',
        'work_location_id',
        'jadwal_jaga_id',
        'date',
        'time_in',
        'time_out',
        'latlon_in',
        'latlon_out',
        'location_name_in',
        'location_name_out',
        'device_info',
        'device_id', // Reference to user_devices.device_id
        'device_fingerprint', // For security validation
        'photo_in',
        'photo_out',
        'notes',
        'status',
        // Enhanced GPS fields
        'latitude',
        'longitude',
        'accuracy',
        'checkout_latitude',
        'checkout_longitude', 
        'checkout_accuracy',
        'location_validated',
    ];

    protected $casts = [
        'date' => 'date',
        'time_in' => 'datetime:H:i:s',
        'time_out' => 'datetime:H:i:s',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'checkout_latitude' => 'decimal:8',
        'checkout_longitude' => 'decimal:8',
        'accuracy' => 'float',
        'checkout_accuracy' => 'float',
        'location_validated' => 'boolean',
    ];

    /**
     * Relationship dengan User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship dengan UserDevice
     */
    public function userDevice(): BelongsTo
    {
        return $this->belongsTo(UserDevice::class, 'device_id', 'device_id');
    }

    /**
     * Relationship dengan Location (legacy)
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Relationship dengan WorkLocation untuk enhanced geofencing
     */
    public function workLocation(): BelongsTo
    {
        return $this->belongsTo(WorkLocation::class, 'work_location_id');
    }

    /**
     * Relationship dengan JadwalJaga untuk validasi schedule
     */
    public function jadwalJaga(): BelongsTo
    {
        return $this->belongsTo(JadwalJaga::class, 'jadwal_jaga_id');
    }

    /**
     * Get latitude from latlon_in
     */
    public function getLatitudeInAttribute(): ?float
    {
        if (!$this->latlon_in) return null;
        $coords = explode(',', $this->latlon_in);
        return isset($coords[0]) ? (float) $coords[0] : null;
    }

    /**
     * Get longitude from latlon_in
     */
    public function getLongitudeInAttribute(): ?float
    {
        if (!$this->latlon_in) return null;
        $coords = explode(',', $this->latlon_in);
        return isset($coords[1]) ? (float) $coords[1] : null;
    }

    /**
     * Get latitude from latlon_out
     */
    public function getLatitudeOutAttribute(): ?float
    {
        if (!$this->latlon_out) return null;
        $coords = explode(',', $this->latlon_out);
        return isset($coords[0]) ? (float) $coords[0] : null;
    }

    /**
     * Get longitude from latlon_out
     */
    public function getLongitudeOutAttribute(): ?float
    {
        if (!$this->latlon_out) return null;
        $coords = explode(',', $this->latlon_out);
        return isset($coords[1]) ? (float) $coords[1] : null;
    }

    /**
     * Check if user already checked in today
     */
    public static function hasCheckedInToday(int $userId): bool
    {
        return self::where('user_id', $userId)
            ->where('date', Carbon::today())
            ->exists();
    }

    /**
     * Get today's attendance for user
     */
    public static function getTodayAttendance(int $userId): ?self
    {
        return self::where('user_id', $userId)
            ->where('date', Carbon::today())
            ->first();
    }

    /**
     * Get all today's attendance records for user (for multiple shifts)
     */
    public static function getTodayAttendances(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('user_id', $userId)
            ->where('date', Carbon::today())
            ->with(['jadwalJaga'])
            ->orderBy('time_in', 'asc')
            ->get();
    }

    /**
     * Get current active attendance (checked in but not out)
     */
    public static function getCurrentActiveAttendance(int $userId): ?self
    {
        return self::where('user_id', $userId)
            ->where('date', Carbon::today())
            ->whereNotNull('time_in')
            ->whereNull('time_out')
            ->latest('time_in')
            ->first();
    }

    /**
     * Check if user has today's scheduled shifts
     */
    public static function getTodayScheduledShifts(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return \App\Models\JadwalJaga::where('pegawai_id', $userId)
            ->where('tanggal_jaga', Carbon::today())
            ->orderBy('jam_mulai', 'asc')
            ->get();
    }

    /**
     * Check if user can check out (has checked in but not out)
     */
    public function canCheckOut(): bool
    {
        return $this->time_in && !$this->time_out;
    }

    /**
     * Check if user has completed check-out
     */
    public function hasCheckedOut(): bool
    {
        return $this->time_in && $this->time_out;
    }

    /**
     * Check if user can check in for a new day
     */
    public static function canCheckInNewDay(int $userId): bool
    {
        $todayAttendance = self::getTodayAttendance($userId);
        
        // Can check in if no attendance today, or if completed previous day's check-out
        return !$todayAttendance || $todayAttendance->hasCheckedOut();
    }

    /**
     * Get attendance status for today - Enhanced for multiple shifts
     */
    public static function getTodayStatus(int $userId): array
    {
        // Get current active attendance (checked in but not out)
        $activeAttendance = self::getCurrentActiveAttendance($userId);
        
        // If user has an active attendance, they can check out
        if ($activeAttendance) {
            return [
                'status' => 'checked_in',
                'message' => 'Sudah check-in, belum check-out',
                'can_check_in' => false,
                'can_check_out' => true,
                'attendance' => $activeAttendance,
                'active_shift' => $activeAttendance->jadwalJaga,
                'shift_info' => $activeAttendance->jadwalJaga ? [
                    'start_time' => $activeAttendance->jadwalJaga->jam_mulai,
                    'end_time' => $activeAttendance->jadwalJaga->jam_selesai,
                    'location' => $activeAttendance->jadwalJaga->lokasi ?? 'Lokasi kerja'
                ] : null
            ];
        }
        
        // Get today's scheduled shifts
        $todayShifts = self::getTodayScheduledShifts($userId);
        $todayAttendances = self::getTodayAttendances($userId);
        
        // If no scheduled shifts today, still allow check-in for unscheduled work
        if ($todayShifts->isEmpty()) {
            return [
                'status' => 'no_schedule',
                'message' => 'Tidak ada jadwal hari ini - Check-in manual',
                'can_check_in' => true,
                'can_check_out' => false,
                'attendance' => null,
                'scheduled_shifts' => [],
                'completed_shifts' => $todayAttendances->count()
            ];
        }
        
        // Check which shifts are completed vs pending
        $completedShifts = $todayAttendances->where('time_in', '!=', null)
                                          ->where('time_out', '!=', null);
        $pendingShifts = $todayShifts->count() - $completedShifts->count();
        
        // If all shifts are completed, but user might have additional unscheduled shifts
        if ($pendingShifts <= 0) {
            return [
                'status' => 'all_shifts_completed',
                'message' => "Semua shift selesai ({$completedShifts->count()}/{$todayShifts->count()}) - Check-in tambahan tersedia",
                'can_check_in' => true, // Allow additional check-ins
                'can_check_out' => false,
                'attendance' => $todayAttendances->last(),
                'scheduled_shifts' => $todayShifts,
                'completed_shifts' => $completedShifts->count(),
                'total_shifts' => $todayShifts->count()
            ];
        }
        
        // User has pending shifts - can check in for next shift
        return [
            'status' => 'has_pending_shifts',
            'message' => "Ada {$pendingShifts} shift tersisa hari ini",
            'can_check_in' => true,
            'can_check_out' => false,
            'attendance' => $todayAttendances->last(),
            'scheduled_shifts' => $todayShifts,
            'completed_shifts' => $completedShifts->count(),
            'pending_shifts' => $pendingShifts,
            'total_shifts' => $todayShifts->count()
        ];
    }

    /**
     * Calculate work duration in minutes
     */
    public function getWorkDurationAttribute(): ?int
    {
        if (!$this->time_in || !$this->time_out) return null;
        
        $timeIn = Carbon::parse($this->time_in);
        $timeOut = Carbon::parse($this->time_out);
        
        return $timeOut->diffInMinutes($timeIn);
    }

    /**
     * Get formatted work duration
     */
    public function getFormattedWorkDurationAttribute(): ?string
    {
        $duration = $this->work_duration;
        if (!$duration) return null;
        
        $hours = intval($duration / 60);
        $minutes = $duration % 60;
        
        return sprintf('%dj %dm', $hours, $minutes);
    }

    /**
     * Scope untuk filter berdasarkan tanggal
     */
    public function scopeForDate($query, $date)
    {
        return $query->where('date', $date);
    }

    /**
     * Scope untuk filter berdasarkan user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope untuk attendance hari ini
     */
    public function scopeToday($query)
    {
        return $query->where('date', Carbon::today());
    }

    /**
     * Scope untuk attendance bulan ini
     */
    public function scopeThisMonth($query)
    {
        return $query->whereYear('date', Carbon::now()->year)
            ->whereMonth('date', Carbon::now()->month);
    }
}