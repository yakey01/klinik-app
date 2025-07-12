<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\Auditable;

class GpsSpoofingConfig extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'config_name',
        'description',
        'is_active',
        
        // Travel Detection Thresholds
        'max_travel_speed_kmh',
        'min_time_diff_seconds',
        'max_distance_km',
        
        // GPS Accuracy Thresholds
        'min_gps_accuracy_meters',
        'max_gps_accuracy_meters',
        
        // Risk Scoring Weights
        'mock_location_weight',
        'fake_gps_app_weight',
        'developer_mode_weight',
        'impossible_travel_weight',
        'coordinate_anomaly_weight',
        'device_integrity_weight',
        
        // Risk Level Thresholds
        'low_risk_threshold',
        'medium_risk_threshold',
        'high_risk_threshold',
        'critical_risk_threshold',
        
        // Auto-Action Settings
        'auto_block_critical',
        'auto_block_high_risk',
        'auto_flag_medium_risk',
        'auto_warning_low_risk',
        
        // Detection Feature Toggles
        'enable_mock_location_detection',
        'enable_fake_gps_detection',
        'enable_developer_mode_detection',
        'enable_impossible_travel_detection',
        'enable_coordinate_anomaly_detection',
        'enable_device_integrity_check',
        
        // Monitoring Settings
        'data_retention_days',
        'polling_interval_seconds',
        'enable_real_time_alerts',
        'enable_email_notifications',
        'notification_email',
        
        // Whitelist Settings
        'whitelisted_ips',
        'whitelisted_devices',
        'trusted_locations',
        
        // Advanced Settings
        'max_failed_attempts_per_hour',
        'temporary_block_duration_minutes',
        'require_admin_review_for_unblock',
        
        // Device Management Settings
        'auto_register_devices',
        'max_devices_per_user',
        'require_admin_approval_for_new_devices',
        'device_limit_policy',
        'device_auto_cleanup_days',
        'auto_revoke_excess_devices',
        
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'max_travel_speed_kmh' => 'decimal:2',
        'max_distance_km' => 'decimal:2',
        'min_gps_accuracy_meters' => 'decimal:2',
        'max_gps_accuracy_meters' => 'decimal:2',
        'auto_block_critical' => 'boolean',
        'auto_block_high_risk' => 'boolean',
        'auto_flag_medium_risk' => 'boolean',
        'auto_warning_low_risk' => 'boolean',
        'enable_mock_location_detection' => 'boolean',
        'enable_fake_gps_detection' => 'boolean',
        'enable_developer_mode_detection' => 'boolean',
        'enable_impossible_travel_detection' => 'boolean',
        'enable_coordinate_anomaly_detection' => 'boolean',
        'enable_device_integrity_check' => 'boolean',
        'enable_real_time_alerts' => 'boolean',
        'enable_email_notifications' => 'boolean',
        'require_admin_review_for_unblock' => 'boolean',
        'auto_register_devices' => 'boolean',
        'require_admin_approval_for_new_devices' => 'boolean',
        'auto_revoke_excess_devices' => 'boolean',
        'whitelisted_ips' => 'array',
        'whitelisted_devices' => 'array',
        'trusted_locations' => 'array',
    ];

    /**
     * Get the user who created this config
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this config
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the active configuration
     */
    public static function getActiveConfig(): ?self
    {
        return static::where('is_active', true)->first();
    }

    /**
     * Calculate risk score based on detection flags
     */
    public function calculateRiskScore(array $detectionFlags): int
    {
        $score = 0;

        if ($detectionFlags['mock_location_detected'] ?? false) {
            $score += $this->mock_location_weight;
        }
        if ($detectionFlags['fake_gps_app_detected'] ?? false) {
            $score += $this->fake_gps_app_weight;
        }
        if ($detectionFlags['developer_mode_detected'] ?? false) {
            $score += $this->developer_mode_weight;
        }
        if ($detectionFlags['impossible_travel_detected'] ?? false) {
            $score += $this->impossible_travel_weight;
        }
        if ($detectionFlags['coordinate_anomaly_detected'] ?? false) {
            $score += $this->coordinate_anomaly_weight;
        }
        if ($detectionFlags['device_integrity_failed'] ?? false) {
            $score += $this->device_integrity_weight;
        }

        return min($score, 100); // Cap at 100
    }

    /**
     * Determine risk level based on score
     */
    public function getRiskLevel(int $score): string
    {
        if ($score >= $this->critical_risk_threshold) {
            return 'critical';
        } elseif ($score >= $this->high_risk_threshold) {
            return 'high';
        } elseif ($score >= $this->medium_risk_threshold) {
            return 'medium';
        } elseif ($score >= $this->low_risk_threshold) {
            return 'low';
        }

        return 'none';
    }

    /**
     * Get recommended action based on risk level
     */
    public function getRecommendedAction(string $riskLevel): string
    {
        return match ($riskLevel) {
            'critical' => $this->auto_block_critical ? 'blocked' : 'flagged',
            'high' => $this->auto_block_high_risk ? 'blocked' : 'flagged',
            'medium' => $this->auto_flag_medium_risk ? 'flagged' : 'warning',
            'low' => $this->auto_warning_low_risk ? 'warning' : 'none',
            default => 'none',
        };
    }

    /**
     * Check if a detection method is enabled
     */
    public function isDetectionEnabled(string $method): bool
    {
        return match ($method) {
            'mock_location' => $this->enable_mock_location_detection,
            'fake_gps' => $this->enable_fake_gps_detection,
            'developer_mode' => $this->enable_developer_mode_detection,
            'impossible_travel' => $this->enable_impossible_travel_detection,
            'coordinate_anomaly' => $this->enable_coordinate_anomaly_detection,
            'device_integrity' => $this->enable_device_integrity_check,
            default => false,
        };
    }

    /**
     * Get detection summary
     */
    public function getDetectionSummary(): array
    {
        $enabledMethods = [];
        $weights = [];

        if ($this->enable_mock_location_detection) {
            $enabledMethods[] = 'Mock Location';
            $weights['mock_location'] = $this->mock_location_weight;
        }
        if ($this->enable_fake_gps_detection) {
            $enabledMethods[] = 'Fake GPS App';
            $weights['fake_gps'] = $this->fake_gps_app_weight;
        }
        if ($this->enable_developer_mode_detection) {
            $enabledMethods[] = 'Developer Mode';
            $weights['developer_mode'] = $this->developer_mode_weight;
        }
        if ($this->enable_impossible_travel_detection) {
            $enabledMethods[] = 'Impossible Travel';
            $weights['impossible_travel'] = $this->impossible_travel_weight;
        }
        if ($this->enable_coordinate_anomaly_detection) {
            $enabledMethods[] = 'Coordinate Anomaly';
            $weights['coordinate_anomaly'] = $this->coordinate_anomaly_weight;
        }
        if ($this->enable_device_integrity_check) {
            $enabledMethods[] = 'Device Integrity';
            $weights['device_integrity'] = $this->device_integrity_weight;
        }

        return [
            'enabled_methods' => $enabledMethods,
            'total_methods' => count($enabledMethods),
            'weights' => $weights,
            'max_possible_score' => array_sum($weights),
        ];
    }

    /**
     * Scope for active configs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if automatic device registration is enabled
     */
    public function isAutoDeviceRegistrationEnabled(): bool
    {
        return $this->auto_register_devices;
    }

    /**
     * Get maximum devices allowed per user
     */
    public function getMaxDevicesPerUser(): int
    {
        return $this->max_devices_per_user ?? 1;
    }

    /**
     * Check if admin approval is required for new devices
     */
    public function requiresAdminApprovalForNewDevices(): bool
    {
        return $this->require_admin_approval_for_new_devices;
    }

    /**
     * Get device limit policy
     */
    public function getDeviceLimitPolicy(): string
    {
        return $this->device_limit_policy ?? 'strict';
    }

    /**
     * Check if excess devices should be auto-revoked
     */
    public function shouldAutoRevokeExcessDevices(): bool
    {
        return $this->auto_revoke_excess_devices;
    }

    /**
     * Get device auto cleanup days
     */
    public function getDeviceAutoCleanupDays(): int
    {
        return $this->device_auto_cleanup_days ?? 30;
    }
}
