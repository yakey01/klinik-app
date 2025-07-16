<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerformanceAlert extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'staff_id',
        'alert_type',
        'severity',
        'performance_data',
        'custom_message',
        'sent_by',
        'sent_at',
        'acknowledged_at',
        'acknowledged_by',
        'response_message',
        'status',
    ];

    protected $casts = [
        'performance_data' => 'array',
        'sent_at' => 'datetime',
        'acknowledged_at' => 'datetime',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'staff_id');
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeAcknowledged($query)
    {
        return $query->where('status', 'acknowledged');
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    public function scopeHigh($query)
    {
        return $query->where('severity', 'high');
    }

    public function getSeverityColorAttribute(): string
    {
        return match($this->severity) {
            'critical' => 'danger',
            'high' => 'warning',
            'medium' => 'info',
            'low' => 'success',
            default => 'gray',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'sent' => 'warning',
            'acknowledged' => 'info',
            'resolved' => 'success',
            default => 'gray',
        };
    }

    public function acknowledge(User $user, ?string $message = null): bool
    {
        return $this->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
            'acknowledged_by' => $user->id,
            'response_message' => $message,
        ]);
    }

    public function resolve(User $user, ?string $message = null): bool
    {
        return $this->update([
            'status' => 'resolved',
            'response_message' => $message,
        ]);
    }
}