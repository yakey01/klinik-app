<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GpsSpoofingSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'is_enabled',
        'name',
        'description',
        'mock_location_score',
        'fake_gps_app_score',
        'developer_mode_score',
        'impossible_travel_score',
        'coordinate_anomaly_score',
        'device_integrity_score',
        'low_risk_threshold',
        'medium_risk_threshold',
        'high_risk_threshold',
        'warning_threshold',
        'flagged_threshold',
        'blocked_threshold',
        'detect_mock_location',
        'detect_fake_gps_apps',
        'detect_developer_mode',
        'detect_impossible_travel',
        'detect_coordinate_anomaly',
        'detect_device_integrity',
        'max_travel_speed_kmh',
        'min_time_between_locations',
        'accuracy_threshold',
        'send_email_alerts',
        'send_realtime_alerts',
        'send_critical_only',
        'notification_recipients',
        'auto_block_enabled',
        'block_duration_hours',
        'require_admin_unblock',
        'whitelisted_ips',
        'whitelisted_devices',
        'trusted_locations',
        'fake_gps_apps_database',
        'log_all_attempts',
        'log_low_risk_only',
        'retention_days',
        'created_by',
        'updated_by',
        'last_updated_at',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'detect_mock_location' => 'boolean',
        'detect_fake_gps_apps' => 'boolean',
        'detect_developer_mode' => 'boolean',
        'detect_impossible_travel' => 'boolean',
        'detect_coordinate_anomaly' => 'boolean',
        'detect_device_integrity' => 'boolean',
        'send_email_alerts' => 'boolean',
        'send_realtime_alerts' => 'boolean',
        'send_critical_only' => 'boolean',
        'auto_block_enabled' => 'boolean',
        'require_admin_unblock' => 'boolean',
        'log_all_attempts' => 'boolean',
        'log_low_risk_only' => 'boolean',
        'max_travel_speed_kmh' => 'decimal:2',
        'accuracy_threshold' => 'decimal:2',
        'notification_recipients' => 'array',
        'whitelisted_ips' => 'array',
        'whitelisted_devices' => 'array',
        'trusted_locations' => 'array',
        'fake_gps_apps_database' => 'array',
        'last_updated_at' => 'datetime',
    ];

    /**
     * Get the user who created this setting
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this setting
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the singleton settings record
     */
    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1], [
            'name' => 'GPS Anti-Spoofing Configuration',
            'description' => 'Konfigurasi sistem deteksi GPS spoofing untuk keamanan presensi',
        ]);
    }

    /**
     * Check if GPS spoofing detection is enabled
     */
    public static function isEnabled(): bool
    {
        return static::current()->is_enabled;
    }

    /**
     * Get detection method scores
     */
    public function getDetectionScores(): array
    {
        return [
            'mock_location' => $this->mock_location_score,
            'fake_gps_app' => $this->fake_gps_app_score,
            'developer_mode' => $this->developer_mode_score,
            'impossible_travel' => $this->impossible_travel_score,
            'coordinate_anomaly' => $this->coordinate_anomaly_score,
            'device_integrity' => $this->device_integrity_score,
        ];
    }

    /**
     * Get enabled detection methods
     */
    public function getEnabledMethods(): array
    {
        $methods = [];
        
        if ($this->detect_mock_location) $methods[] = 'mock_location';
        if ($this->detect_fake_gps_apps) $methods[] = 'fake_gps_app';
        if ($this->detect_developer_mode) $methods[] = 'developer_mode';
        if ($this->detect_impossible_travel) $methods[] = 'impossible_travel';
        if ($this->detect_coordinate_anomaly) $methods[] = 'coordinate_anomaly';
        if ($this->detect_device_integrity) $methods[] = 'device_integrity';
        
        return $methods;
    }

    /**
     * Calculate risk level from score
     */
    public function calculateRiskLevel(int $score): string
    {
        if ($score >= $this->high_risk_threshold) return 'critical';
        if ($score >= $this->medium_risk_threshold) return 'high';
        if ($score >= $this->low_risk_threshold) return 'medium';
        return 'low';
    }

    /**
     * Determine action from score
     */
    public function determineAction(int $score): string
    {
        if ($score >= $this->blocked_threshold) return 'blocked';
        if ($score >= $this->flagged_threshold) return 'flagged';
        if ($score >= $this->warning_threshold) return 'warning';
        return 'none';
    }

    /**
     * Check if IP is whitelisted
     */
    public function isIpWhitelisted(string $ip): bool
    {
        return in_array($ip, $this->whitelisted_ips ?? []);
    }

    /**
     * Check if device is whitelisted
     */
    public function isDeviceWhitelisted(string $deviceId): bool
    {
        return in_array($deviceId, $this->whitelisted_devices ?? []);
    }

    /**
     * Check if location is trusted
     */
    public function isTrustedLocation(float $lat, float $lng, float $radius = 100): bool
    {
        $trustedLocations = $this->trusted_locations ?? [];
        
        foreach ($trustedLocations as $trusted) {
            $distance = $this->calculateDistance(
                $lat, $lng, 
                $trusted['latitude'], $trusted['longitude']
            );
            
            if ($distance <= ($trusted['radius'] ?? $radius)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Calculate distance between coordinates
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // Earth's radius in meters
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);
             
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earthRadius * $c;
    }

    /**
     * Get risk level color
     */
    public function getRiskLevelColor(string $level): string
    {
        return match($level) {
            'low' => 'success',
            'medium' => 'warning',
            'high' => 'danger',
            'critical' => 'danger',
            default => 'gray',
        };
    }

    /**
     * Get formatted settings summary
     */
    public function getSummary(): array
    {
        return [
            'status' => $this->is_enabled ? 'Aktif' : 'Nonaktif',
            'detection_methods' => count($this->getEnabledMethods()),
            'auto_block' => $this->auto_block_enabled ? 'Ya' : 'Tidak',
            'email_alerts' => $this->send_email_alerts ? 'Ya' : 'Tidak',
            'blocked_threshold' => $this->blocked_threshold . '%',
            'max_speed' => $this->max_travel_speed_kmh . ' km/h',
            'fake_apps_count' => count($this->fake_gps_apps_database ?? []),
            'trusted_locations' => count($this->trusted_locations ?? []),
            'whitelisted_devices' => count($this->whitelisted_devices ?? []),
            'last_updated' => $this->updated_at->diffForHumans(),
        ];
    }
}