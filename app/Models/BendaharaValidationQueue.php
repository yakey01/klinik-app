<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BendaharaValidationQueue extends Model
{
    use HasFactory;

    protected $table = 'bendahara_validation_queue';

    protected $fillable = [
        'validation_type',
        'validatable_type',
        'validatable_id',
        'submitted_by',
        'assigned_to',
        'priority',
        'status',
        'submitted_at',
        'assigned_at',
        'completed_at',
        'amount',
        'notes',
        'validation_rules',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'assigned_at' => 'datetime',
        'completed_at' => 'datetime',
        'amount' => 'decimal:2',
        'validation_rules' => 'array',
    ];

    public function validatable(): MorphTo
    {
        return $this->morphTo();
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAssigned($query)
    {
        return $query->where('status', 'in_review');
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('validation_type', $type);
    }

    // Attributes
    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'low' => 'gray',
            'normal' => 'blue',
            'high' => 'orange',
            'urgent' => 'red',
            default => 'gray'
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'warning',
            'in_review' => 'info',
            'completed' => 'success',
            'rejected' => 'danger',
            default => 'gray'
        };
    }

    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    public function getDurationAttribute(): ?string
    {
        if (!$this->completed_at) {
            return null;
        }

        return $this->submitted_at->diffForHumans($this->completed_at, true);
    }
}