<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignmentHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'work_location_id',
        'previous_work_location_id',
        'assigned_by',
        'assignment_method',
        'assignment_reasons',
        'assignment_score',
        'metadata',
        'notes'
    ];

    protected $casts = [
        'assignment_reasons' => 'array',
        'metadata' => 'array',
        'assignment_score' => 'integer'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workLocation(): BelongsTo
    {
        return $this->belongsTo(WorkLocation::class);
    }

    public function previousWorkLocation(): BelongsTo
    {
        return $this->belongsTo(WorkLocation::class, 'previous_work_location_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Get formatted assignment reasons
     */
    public function getFormattedReasonsAttribute(): string
    {
        if (!$this->assignment_reasons) {
            return 'No specific reasons recorded';
        }

        return implode('; ', $this->assignment_reasons);
    }

    /**
     * Get confidence level badge
     */
    public function getConfidenceBadgeAttribute(): string
    {
        $confidence = $this->metadata['confidence'] ?? 'unknown';

        $badges = [
            'very_high' => '🟢 Very High',
            'high' => '🔵 High',
            'medium' => '🟡 Medium',
            'low' => '🟠 Low',
            'unknown' => '⚪ Unknown'
        ];

        return $badges[$confidence] ?? $badges['unknown'];
    }

    /**
     * Get assignment method label
     */
    public function getMethodLabelAttribute(): string
    {
        $methods = [
            'smart_algorithm' => '🧠 Smart Assignment',
            'manual' => '👤 Manual Assignment',
            'bulk' => '📋 Bulk Assignment',
            'auto' => '🤖 Auto Assignment'
        ];

        return $methods[$this->assignment_method] ?? '❓ Unknown Method';
    }
}