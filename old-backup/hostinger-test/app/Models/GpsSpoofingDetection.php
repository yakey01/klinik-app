<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GpsSpoofingDetection extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_id',
        'ip_address',
        'user_agent',
        'latitude',
        'longitude',
        'accuracy',
        'altitude',
        'speed',
        'heading',
        'detection_results',
        'risk_level',
        'risk_score',
        'is_spoofed',
        'is_blocked',
        'mock_location_detected',
        'fake_gps_app_detected',
        'developer_mode_detected',
        'impossible_travel_detected',
        'coordinate_anomaly_detected',
        'device_integrity_failed',
        'spoofing_indicators',
        'detected_fake_apps',
        'travel_speed_kmh',
        'time_diff_seconds',
        'distance_from_last_km',
        'action_taken',
        'admin_notes',
        'reviewed_at',
        'reviewed_by',
        'attendance_type',
        'attempted_at',
        'location_source',
        'device_fingerprint',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'accuracy' => 'decimal:2',
        'altitude' => 'decimal:2',
        'speed' => 'decimal:2',
        'heading' => 'decimal:2',
        'travel_speed_kmh' => 'decimal:2',
        'distance_from_last_km' => 'decimal:2',
        'detection_results' => 'array',
        'spoofing_indicators' => 'array',
        'device_fingerprint' => 'array',
        'is_spoofed' => 'boolean',
        'is_blocked' => 'boolean',
        'mock_location_detected' => 'boolean',
        'fake_gps_app_detected' => 'boolean',
        'developer_mode_detected' => 'boolean',
        'impossible_travel_detected' => 'boolean',
        'coordinate_anomaly_detected' => 'boolean',
        'device_integrity_failed' => 'boolean',
        'attempted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Set default values for required fields if not provided
            if (empty($model->detection_results)) {
                $model->detection_results = ['auto_generated' => true];
            }
            if (empty($model->attempted_at)) {
                $model->attempted_at = now();
            }
            if (empty($model->ip_address)) {
                $model->ip_address = request()->ip() ?: '127.0.0.1';
            }
        });
    }

    /**
     * Get the user that attempted the location
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who reviewed this detection
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

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
            default => 'gray',
        };
    }

    /**
     * Get risk level label
     */
    public function getRiskLevelLabelAttribute(): string
    {
        return match ($this->risk_level) {
            'low' => '🟢 Rendah',
            'medium' => '🟡 Sedang',
            'high' => '🔴 Tinggi',
            'critical' => '🚨 Kritis',
            default => '⚪ Unknown',
        };
    }

    /**
     * Get action taken label
     */
    public function getActionTakenLabelAttribute(): string
    {
        return match ($this->action_taken) {
            'none' => '➖ Tidak Ada',
            'warning' => '⚠️ Peringatan',
            'blocked' => '🚫 Diblokir',
            'flagged' => '🏴 Ditandai',
            default => '❓ Unknown',
        };
    }

    /**
     * Get detected spoofing methods as array
     */
    public function getDetectedMethodsAttribute(): array
    {
        $methods = [];

        if ($this->mock_location_detected) {
            $methods[] = 'Mock Location';
        }
        if ($this->fake_gps_app_detected) {
            $methods[] = 'Fake GPS App';
        }
        if ($this->developer_mode_detected) {
            $methods[] = 'Developer Mode';
        }
        if ($this->impossible_travel_detected) {
            $methods[] = 'Impossible Travel';
        }
        if ($this->coordinate_anomaly_detected) {
            $methods[] = 'Coordinate Anomaly';
        }
        if ($this->device_integrity_failed) {
            $methods[] = 'Device Integrity Failed';
        }

        return $methods;
    }

    /**
     * Get coordinates as Google Maps URL
     */
    public function getGoogleMapsUrlAttribute(): string
    {
        return "https://maps.google.com/maps?q={$this->latitude},{$this->longitude}";
    }

    /**
     * Scope for spoofed detections
     */
    public function scopeSpoofed($query)
    {
        return $query->where('is_spoofed', true);
    }

    /**
     * Scope for blocked detections
     */
    public function scopeBlocked($query)
    {
        return $query->where('is_blocked', true);
    }

    /**
     * Scope for high risk detections
     */
    public function scopeHighRisk($query)
    {
        return $query->whereIn('risk_level', ['high', 'critical']);
    }

    /**
     * Scope for unreviewed detections
     */
    public function scopeUnreviewed($query)
    {
        return $query->whereNull('reviewed_at');
    }

    /**
     * Scope for today's detections
     */
    public function scopeToday($query)
    {
        return $query->whereDate('attempted_at', today());
    }

    /**
     * Calculate detection summary
     */
    public static function getDetectionSummary(): array
    {
        $total = static::count();
        $spoofed = static::spoofed()->count();
        $blocked = static::blocked()->count();
        $highRisk = static::highRisk()->count();
        $unreviewed = static::unreviewed()->count();
        $today = static::today()->count();

        return [
            'total' => $total,
            'spoofed' => $spoofed,
            'blocked' => $blocked,
            'high_risk' => $highRisk,
            'unreviewed' => $unreviewed,
            'today' => $today,
            'spoofing_rate' => $total > 0 ? round(($spoofed / $total) * 100, 2) : 0,
        ];
    }
}
