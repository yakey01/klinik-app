<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'address',
        'latitude',
        'longitude',
        'location',
        'radius_meters',
        'is_active',
        'location_type',
        'allowed_shifts',
        'working_hours',
        'tolerance_settings',
        'contact_person',
        'contact_phone',
        'require_photo',
        'strict_geofence',
        'gps_accuracy_required',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean',
        'require_photo' => 'boolean',
        'strict_geofence' => 'boolean',
        'allowed_shifts' => 'array',
        'working_hours' => 'array',
        'tolerance_settings' => 'array',
    ];

    // Note: Relationship dengan Attendance akan ditambahkan nanti
    // jika diperlukan dengan menambah kolom work_location_id ke tabel attendances

    /**
     * Get location type label
     */
    public function getLocationTypeLabelAttribute(): string
    {
        return match($this->location_type) {
            'main_office' => '🏢 Kantor Pusat',
            'branch_office' => '🏪 Kantor Cabang',
            'project_site' => '🚧 Lokasi Proyek',
            'mobile_location' => '📱 Lokasi Mobile',
            'client_office' => '🤝 Kantor Klien',
            default => '📍 Lokasi Kerja',
        };
    }

    /**
     * Check if coordinates are within geofence
     */
    public function isWithinGeofence(float $latitude, float $longitude, ?float $accuracy = null): bool
    {
        // Calculate distance using Haversine formula
        $distance = $this->calculateDistance($latitude, $longitude);
        
        // If GPS accuracy is provided, add it to radius for tolerance
        $effectiveRadius = $this->radius_meters;
        if ($accuracy && !$this->strict_geofence) {
            $effectiveRadius += min($accuracy, $this->gps_accuracy_required);
        }
        
        return $distance <= $effectiveRadius;
    }

    /**
     * Calculate distance between two coordinates in meters
     */
    public function calculateDistance(float $lat2, float $lon2): float
    {
        $lat1 = (float) $this->latitude;
        $lon1 = (float) $this->longitude;
        
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
     * Get Google Maps URL
     */
    public function getGoogleMapsUrlAttribute(): string
    {
        return "https://maps.google.com/maps?q={$this->latitude},{$this->longitude}";
    }

    /**
     * Get coordinates as array
     */
    public function getCoordinatesAttribute(): array
    {
        return [
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
        ];
    }

    /**
     * Get location for Filament Google Maps
     */
    public function getLocationAttribute(): array
    {
        return [
            'lat' => (float) $this->latitude,
            'lng' => (float) $this->longitude,
        ];
    }

    /**
     * Set location from Filament Google Maps
     */
    public function setLocationAttribute(?array $location): void
    {
        if (is_array($location)) {
            $this->attributes['latitude'] = $location['lat'] ?? null;
            $this->attributes['longitude'] = $location['lng'] ?? null;
            unset($this->attributes['location']);
        }
    }

    /**
     * Scope for active locations
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for location type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('location_type', $type);
    }

    /**
     * Get formatted radius
     */
    public function getFormattedRadiusAttribute(): string
    {
        if ($this->radius_meters >= 1000) {
            return number_format($this->radius_meters / 1000, 1) . ' km';
        }
        return $this->radius_meters . ' m';
    }

    /**
     * Check if shift is allowed at this location
     */
    public function isShiftAllowed(string $shift): bool
    {
        if (empty($this->allowed_shifts)) {
            return true; // All shifts allowed if not specified
        }
        
        return in_array($shift, $this->allowed_shifts);
    }

    /**
     * Get working hours for specific day
     */
    public function getWorkingHours(?string $day = null): ?array
    {
        if (empty($this->working_hours)) {
            return null;
        }
        
        if ($day) {
            return $this->working_hours[$day] ?? null;
        }
        
        return $this->working_hours;
    }

    /**
     * Get tolerance settings
     */
    public function getToleranceSettings(): array
    {
        return $this->tolerance_settings ?? [
            'late_tolerance_minutes' => 15,
            'early_departure_tolerance_minutes' => 15,
            'break_time_minutes' => 60,
            'overtime_threshold_minutes' => 480, // 8 hours
        ];
    }

    /**
     * Check if location requires photo verification
     */
    public function requiresPhoto(): bool
    {
        return $this->require_photo;
    }
}