<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocationValidation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'latitude',
        'longitude',
        'accuracy',
        'work_zone_radius',
        'is_within_zone',
        'distance_from_zone',
        'validation_time',
        'attendance_type',
        'notes',
        
        // Enhanced security fields
        'risk_level',
        'risk_score',
        'is_spoofed',
        'action_taken',
        'detection_results',
        'spoofing_indicators',
        'device_fingerprint',
        'is_blocked',
        'reviewed_at',
        'reviewed_by',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'accuracy' => 'decimal:2',
        'distance_from_zone' => 'decimal:2',
        'is_within_zone' => 'boolean',
        'validation_time' => 'datetime',
        
        // Enhanced security casts
        'risk_score' => 'integer',
        'is_spoofed' => 'boolean',
        'is_blocked' => 'boolean',
        'detection_results' => 'array',
        'spoofing_indicators' => 'array',
        'device_fingerprint' => 'array',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the location validation
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get validation status badge color
     */
    public function getValidationStatusColorAttribute(): string
    {
        return $this->is_within_zone ? 'success' : 'danger';
    }

    /**
     * Get validation status label
     */
    public function getValidationStatusLabelAttribute(): string
    {
        return $this->is_within_zone ? '✅ Dalam Area' : '❌ Luar Area';
    }

    /**
     * Get attendance type label
     */
    public function getAttendanceTypeLabelAttribute(): string
    {
        return match ($this->attendance_type) {
            'check_in' => '📥 Check In',
            'check_out' => '📤 Check Out',
            default => '❓ Unknown',
        };
    }

    /**
     * Get coordinates as Google Maps URL
     */
    public function getGoogleMapsUrlAttribute(): string
    {
        return "https://maps.google.com/maps?q={$this->latitude},{$this->longitude}";
    }

    /**
     * Get distance from zone formatted
     */
    public function getDistanceFromZoneFormattedAttribute(): string
    {
        if ($this->distance_from_zone === null) {
            return 'N/A';
        }

        if ($this->distance_from_zone < 1000) {
            return number_format($this->distance_from_zone, 1) . ' m';
        }

        return number_format($this->distance_from_zone / 1000, 2) . ' km';
    }

    /**
     * Scope for valid validations (within zone)
     */
    public function scopeValid($query)
    {
        return $query->where('is_within_zone', true);
    }

    /**
     * Scope for invalid validations (outside zone)
     */
    public function scopeInvalid($query)
    {
        return $query->where('is_within_zone', false);
    }

    /**
     * Scope for check-in validations
     */
    public function scopeCheckIn($query)
    {
        return $query->where('attendance_type', 'check_in');
    }

    /**
     * Scope for check-out validations
     */
    public function scopeCheckOut($query)
    {
        return $query->where('attendance_type', 'check_out');
    }

    /**
     * Scope for today's validations
     */
    public function scopeToday($query)
    {
        return $query->whereDate('validation_time', today());
    }

    /**
     * Scope for this week's validations
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('validation_time', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    /**
     * Scope for this month's validations
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('validation_time', now()->month)
                    ->whereYear('validation_time', now()->year);
    }

    /**
     * Calculate validation summary
     */
    public static function getValidationSummary(): array
    {
        $total = static::count();
        $valid = static::valid()->count();
        $invalid = static::invalid()->count();
        $checkIns = static::checkIn()->count();
        $checkOuts = static::checkOut()->count();
        $today = static::today()->count();

        return [
            'total' => $total,
            'valid' => $valid,
            'invalid' => $invalid,
            'check_ins' => $checkIns,
            'check_outs' => $checkOuts,
            'today' => $today,
            'success_rate' => $total > 0 ? round(($valid / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     */
    public static function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // Earth radius in meters

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Validate if coordinates are within work zone
     */
    public static function validateLocation(
        float $userLat,
        float $userLon,
        float $workLat,
        float $workLon,
        int $radius
    ): array {
        $distance = static::calculateDistance($userLat, $userLon, $workLat, $workLon);
        $isWithinZone = $distance <= $radius;

        return [
            'is_within_zone' => $isWithinZone,
            'distance_from_zone' => $isWithinZone ? 0 : ($distance - $radius),
            'actual_distance' => $distance,
        ];
    }

    // ===== ENHANCED SECURITY METHODS =====

    /**
     * Get risk level badge color
     */
    public function getRiskLevelColorAttribute(): string
    {
        return match ($this->risk_level) {
            'low' => 'success',
            'medium' => 'warning', 
            'high' => 'danger',
            'critical' => 'danger',
            default => 'gray'
        };
    }

    /**
     * Get risk level label with emoji
     */
    public function getRiskLevelLabelAttribute(): string
    {
        return match ($this->risk_level) {
            'low' => '🟢 Low Risk',
            'medium' => '🟡 Medium Risk',
            'high' => '🔴 High Risk', 
            'critical' => '🚨 Critical Risk',
            default => '⚪ Unknown'
        };
    }

    /**
     * Get detected spoofing methods as badges
     */
    public function getDetectedMethodsBadgesAttribute(): array
    {
        if (!$this->spoofing_indicators) {
            return [];
        }

        $badges = [];
        foreach ($this->spoofing_indicators as $method) {
            $badges[] = [
                'label' => $method,
                'color' => 'danger'
            ];
        }

        return $badges;
    }

    /**
     * Check if validation requires admin review
     */
    public function getRequiresReviewAttribute(): bool
    {
        return in_array($this->action_taken, ['blocked', 'flagged']) && !$this->reviewed_at;
    }

    /**
     * Get reviewer relationship
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scope for spoofed validations
     */
    public function scopeSpoofed($query)
    {
        return $query->where('is_spoofed', true);
    }

    /**
     * Scope for blocked validations
     */
    public function scopeBlocked($query)
    {
        return $query->where('is_blocked', true);
    }

    /**
     * Scope for high risk validations
     */
    public function scopeHighRisk($query)
    {
        return $query->whereIn('risk_level', ['high', 'critical']);
    }

    /**
     * Scope for unreviewed validations
     */
    public function scopeUnreviewed($query)
    {
        return $query->whereIn('action_taken', ['blocked', 'flagged'])
                    ->whereNull('reviewed_at');
    }

    /**
     * Scope for validations by risk level
     */
    public function scopeByRiskLevel($query, string $riskLevel)
    {
        return $query->where('risk_level', $riskLevel);
    }

    /**
     * Mark validation as reviewed
     */
    public function markAsReviewed(User $reviewer, string $notes = null): void
    {
        $this->update([
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'notes' => $notes ? ($this->notes ? $this->notes . '; ' . $notes : $notes) : $this->notes
        ]);
    }

    /**
     * Get comprehensive security summary
     */
    public function getSecuritySummaryAttribute(): array
    {
        return [
            'geofencing_passed' => $this->is_within_zone,
            'spoofing_detected' => $this->is_spoofed,
            'risk_assessment' => [
                'level' => $this->risk_level,
                'score' => $this->risk_score,
                'label' => $this->risk_level_label
            ],
            'action_taken' => $this->action_taken,
            'requires_review' => $this->requires_review,
            'detection_methods' => $this->spoofing_indicators ?? [],
            'overall_status' => $this->getOverallSecurityStatus()
        ];
    }

    /**
     * Get overall security status
     */
    private function getOverallSecurityStatus(): string
    {
        if ($this->is_blocked) return 'BLOCKED';
        if ($this->is_spoofed) return 'SUSPICIOUS';
        if (!$this->is_within_zone) return 'OUTSIDE_ZONE';
        if ($this->risk_level === 'high' || $this->risk_level === 'critical') return 'HIGH_RISK';
        
        return 'SECURE';
    }

    /**
     * Enhanced validation summary with security metrics
     */
    public static function getEnhancedValidationSummary(): array
    {
        $basic = self::getValidationSummary();
        
        return array_merge($basic, [
            'security_metrics' => [
                'total_spoofed' => self::where('is_spoofed', true)->count(),
                'total_blocked' => self::where('is_blocked', true)->count(),
                'high_risk_count' => self::whereIn('risk_level', ['high', 'critical'])->count(),
                'pending_review' => self::unreviewed()->count(),
                'risk_distribution' => [
                    'low' => self::where('risk_level', 'low')->count(),
                    'medium' => self::where('risk_level', 'medium')->count(),
                    'high' => self::where('risk_level', 'high')->count(),
                    'critical' => self::where('risk_level', 'critical')->count(),
                ]
            ]
        ]);
    }
}
