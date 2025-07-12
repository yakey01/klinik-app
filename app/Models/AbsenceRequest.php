<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class AbsenceRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'absence_date',
        'absence_type',
        'reason',
        'evidence_file',
        'evidence_metadata',
        'status',
        'admin_notes',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
        'requires_medical_cert',
        'is_half_day',
        'half_day_start',
        'half_day_end',
        'deduction_amount',
        'replacement_staff',
    ];

    protected $casts = [
        'absence_date' => 'date',
        'evidence_metadata' => 'array',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'requires_medical_cert' => 'boolean',
        'is_half_day' => 'boolean',
        'half_day_start' => 'datetime',
        'half_day_end' => 'datetime',
        'deduction_amount' => 'decimal:2',
        'replacement_staff' => 'array',
    ];

    /**
     * User relationship
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Reviewed by relationship
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get evidence file URL
     */
    public function getEvidenceFileUrlAttribute(): ?string
    {
        return $this->evidence_file ? Storage::url($this->evidence_file) : null;
    }

    /**
     * Get formatted absence type
     */
    public function getFormattedAbsenceTypeAttribute(): string
    {
        return match($this->absence_type) {
            'sick' => 'Sakit',
            'personal' => 'Keperluan Pribadi',
            'vacation' => 'Cuti',
            'emergency' => 'Darurat',
            'medical' => 'Medical',
            'family' => 'Keluarga',
            'other' => 'Lainnya',
            default => ucfirst($this->absence_type)
        };
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'cancelled' => 'gray',
            default => 'secondary'
        };
    }

    /**
     * Approve request
     */
    public function approve(int $reviewedBy, ?string $notes = null): void
    {
        $this->update([
            'status' => 'approved',
            'reviewed_at' => now(),
            'reviewed_by' => $reviewedBy,
            'admin_notes' => $notes,
        ]);
    }

    /**
     * Reject request
     */
    public function reject(int $reviewedBy, string $notes): void
    {
        $this->update([
            'status' => 'rejected',
            'reviewed_at' => now(),
            'reviewed_by' => $reviewedBy,
            'admin_notes' => $notes,
        ]);
    }

    /**
     * Cancel request
     */
    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Check if request can be edited
     */
    public function canBeEdited(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if request can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'approved']);
    }

    /**
     * Scope for pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for approved requests
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for rejected requests
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope for requests in date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('absence_date', [$startDate, $endDate]);
    }
}
