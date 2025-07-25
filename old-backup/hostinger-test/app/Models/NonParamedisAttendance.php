<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class NonParamedisAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'work_location_id',
        'check_in_time',
        'check_in_latitude',
        'check_in_longitude',
        'check_in_accuracy',
        'check_in_address',
        'check_in_distance',
        'check_in_valid_location',
        'check_out_time',
        'check_out_latitude',
        'check_out_longitude',
        'check_out_accuracy',
        'check_out_address',
        'check_out_distance',
        'check_out_valid_location',
        'total_work_minutes',
        'attendance_date',
        'status',
        'notes',
        'device_info',
        'browser_info',
        'ip_address',
        'gps_metadata',
        'suspected_spoofing',
        'approval_status',
        'approved_by',
        'approved_at',
        'approval_notes',
        'admin_override',
    ];

    protected $casts = [
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'attendance_date' => 'date',
        'approved_at' => 'datetime',
        'check_in_latitude' => 'decimal:8',
        'check_in_longitude' => 'decimal:8',
        'check_in_accuracy' => 'decimal:2',
        'check_in_distance' => 'decimal:2',
        'check_out_latitude' => 'decimal:8',
        'check_out_longitude' => 'decimal:8',
        'check_out_accuracy' => 'decimal:2',
        'check_out_distance' => 'decimal:2',
        'check_in_valid_location' => 'boolean',
        'check_out_valid_location' => 'boolean',
        'suspected_spoofing' => 'boolean',
        'admin_override' => 'boolean',
        'gps_metadata' => 'array',
        'device_info' => 'array',
    ];

    /**
     * Relationship with User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship with WorkLocation
     */
    public function workLocation(): BelongsTo
    {
        return $this->belongsTo(WorkLocation::class);
    }

    /**
     * Relationship with approver
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Check if user is checked in
     */
    public function isCheckedIn(): bool
    {
        return $this->status === 'checked_in';
    }

    /**
     * Check if user is checked out
     */
    public function isCheckedOut(): bool
    {
        return $this->status === 'checked_out';
    }

    /**
     * Get formatted work duration
     */
    public function getFormattedWorkDurationAttribute(): string
    {
        if (!$this->total_work_minutes) {
            return '0 menit';
        }

        $hours = floor($this->total_work_minutes / 60);
        $minutes = $this->total_work_minutes % 60;

        if ($hours > 0) {
            return $hours . ' jam ' . ($minutes > 0 ? $minutes . ' menit' : '');
        }

        return $minutes . ' menit';
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'checked_in' => '✅ Check In',
            'checked_out' => '🏠 Check Out',
            'incomplete' => '⏳ Belum Lengkap',
            default => '❓ Unknown',
        };
    }

    /**
     * Get approval status label
     */
    public function getApprovalStatusLabelAttribute(): string
    {
        return match($this->approval_status) {
            'pending' => '⏳ Menunggu Persetujuan',
            'approved' => '✅ Disetujui',
            'rejected' => '❌ Ditolak',
            default => '❓ Unknown',
        };
    }

    /**
     * Check if attendance is complete (both check-in and check-out)
     */
    public function isComplete(): bool
    {
        return $this->check_in_time && $this->check_out_time;
    }

    /**
     * Calculate work duration in minutes
     */
    public function calculateWorkDuration(): int
    {
        if (!$this->check_in_time || !$this->check_out_time) {
            return 0;
        }

        return $this->check_in_time->diffInMinutes($this->check_out_time);
    }

    /**
     * Update work duration
     */
    public function updateWorkDuration(): void
    {
        $this->total_work_minutes = $this->calculateWorkDuration();
        $this->save();
    }

    /**
     * Check if location is valid (either check-in or check-out)
     */
    public function hasValidLocation(): bool
    {
        return $this->check_in_valid_location || $this->check_out_valid_location;
    }

    /**
     * Get today's attendance for user
     */
    public static function getTodayAttendance(User $user): ?self
    {
        return self::where('user_id', $user->id)
            ->where('attendance_date', Carbon::today())
            ->first();
    }

    /**
     * Create or get today's attendance
     */
    public static function getOrCreateTodayAttendance(User $user): self
    {
        return self::firstOrCreate([
            'user_id' => $user->id,
            'attendance_date' => Carbon::today(),
        ]);
    }

    /**
     * Scope for specific date
     */
    public function scopeForDate($query, Carbon $date)
    {
        return $query->where('attendance_date', $date->toDateString());
    }

    /**
     * Scope for specific user
     */
    public function scopeForUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * Scope for specific status
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for pending approval
     */
    public function scopePendingApproval($query)
    {
        return $query->where('approval_status', 'pending');
    }

    /**
     * Approve attendance
     */
    public function approve(User $approver, ?string $notes = null): void
    {
        $this->update([
            'approval_status' => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'approval_notes' => $notes,
        ]);
    }

    /**
     * Reject attendance
     */
    public function reject(User $approver, ?string $notes = null): void
    {
        $this->update([
            'approval_status' => 'rejected',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'approval_notes' => $notes,
        ]);
    }
}
