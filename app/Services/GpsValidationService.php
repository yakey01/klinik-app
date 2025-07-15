<?php

namespace App\Services;

use App\Models\WorkLocation;
use Illuminate\Support\Facades\Log;

class GpsValidationService
{
    /**
     * Calculate Haversine distance between two coordinates
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
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
     * Validate GPS coordinates against work locations
     */
    public function validateLocation(float $latitude, float $longitude, ?float $accuracy = null): array
    {
        try {
            // Validate coordinate ranges
            if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
                return [
                    'is_valid' => false,
                    'distance' => null,
                    'location' => null,
                    'error' => 'Invalid coordinate ranges'
                ];
            }

            $workLocations = WorkLocation::active()->get();
            
            if ($workLocations->isEmpty()) {
                return [
                    'is_valid' => false,
                    'distance' => null,
                    'location' => null,
                    'error' => 'No work locations configured'
                ];
            }

            $validLocation = null;
            $minDistance = PHP_FLOAT_MAX;

            // Find closest work location
            foreach ($workLocations as $location) {
                $distance = $this->calculateDistance(
                    $latitude, 
                    $longitude, 
                    $location->latitude, 
                    $location->longitude
                );

                if ($distance < $minDistance) {
                    $minDistance = $distance;
                    $validLocation = $location;
                }
            }

            // Check if within geofence using the closest location
            $isValid = $validLocation && $validLocation->isWithinGeofence($latitude, $longitude, $accuracy);

            return [
                'is_valid' => $isValid,
                'distance' => round($minDistance, 2),
                'location' => $validLocation ? [
                    'id' => $validLocation->id,
                    'name' => $validLocation->name,
                    'address' => $validLocation->address,
                    'radius' => $validLocation->radius_meters,
                    'latitude' => $validLocation->latitude,
                    'longitude' => $validLocation->longitude
                ] : null,
                'accuracy' => $accuracy,
                'is_suspicious' => $this->detectSuspiciousActivity($latitude, $longitude, $accuracy),
                'validation_details' => [
                    'coordinates_provided' => ['lat' => $latitude, 'lng' => $longitude],
                    'accuracy_provided' => $accuracy,
                    'closest_location_distance' => round($minDistance, 2),
                    'geofence_radius' => $validLocation?->radius_meters,
                    'within_geofence' => $isValid
                ]
            ];

        } catch (\Exception $e) {
            Log::error('GPS validation error: ' . $e->getMessage(), [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'accuracy' => $accuracy,
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'is_valid' => false,
                'distance' => null,
                'location' => null,
                'error' => 'GPS validation system error',
                'is_suspicious' => true
            ];
        }
    }

    /**
     * Detect potentially suspicious GPS activity
     */
    private function detectSuspiciousActivity(float $latitude, float $longitude, ?float $accuracy): bool
    {
        $suspiciousIndicators = [];

        // Check for suspiciously perfect coordinates (potential spoofing)
        if ($this->isPerfectCoordinate($latitude, $longitude)) {
            $suspiciousIndicators[] = 'perfect_coordinates';
        }

        // Check for extremely high accuracy (potential spoofing)
        if ($accuracy !== null && $accuracy < 1.0) {
            $suspiciousIndicators[] = 'unrealistic_accuracy';
        }

        // Check for extremely low accuracy (potential interference)
        if ($accuracy !== null && $accuracy > 1000.0) {
            $suspiciousIndicators[] = 'poor_accuracy';
        }

        // Check for common test/mock coordinates
        if ($this->isTestCoordinate($latitude, $longitude)) {
            $suspiciousIndicators[] = 'test_coordinates';
        }

        // Log suspicious activity for monitoring
        if (!empty($suspiciousIndicators)) {
            Log::warning('Suspicious GPS activity detected', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'accuracy' => $accuracy,
                'indicators' => $suspiciousIndicators
            ]);
        }

        return !empty($suspiciousIndicators);
    }

    /**
     * Check if coordinates are suspiciously perfect (potential spoofing)
     */
    private function isPerfectCoordinate(float $latitude, float $longitude): bool
    {
        // Check for coordinates with too many trailing zeros
        $latStr = (string) $latitude;
        $lonStr = (string) $longitude;
        
        $latZeros = substr_count(rtrim($latStr, '0'), '0');
        $lonZeros = substr_count(rtrim($lonStr, '0'), '0');
        
        // More than 4 zeros in either coordinate is suspicious
        return $latZeros >= 4 || $lonZeros >= 4;
    }

    /**
     * Check if coordinates are common test/mock locations
     */
    private function isTestCoordinate(float $latitude, float $longitude): bool
    {
        $testLocations = [
            [0.0, 0.0], // Null Island
            [37.4220936, -122.084], // Google HQ (common mock location)
            [37.7749, -122.4194], // San Francisco (common test location)
            [-7.797068, 110.370529], // Yogyakarta (common Indonesia test)
        ];

        foreach ($testLocations as [$testLat, $testLon]) {
            $distance = $this->calculateDistance($latitude, $longitude, $testLat, $testLon);
            if ($distance < 100) { // Within 100 meters of test location
                return true;
            }
        }

        return false;
    }

    /**
     * Get user-friendly validation message
     */
    public function getValidationMessage(array $validationResult): string
    {
        if ($validationResult['is_valid']) {
            return 'Lokasi valid - Anda berada di area kerja yang ditentukan.';
        }

        if (isset($validationResult['error'])) {
            return match($validationResult['error']) {
                'No work locations configured' => 'Belum ada lokasi kerja yang dikonfigurasi. Hubungi administrator.',
                'Invalid coordinate ranges' => 'Koordinat GPS tidak valid. Pastikan GPS perangkat berfungsi dengan baik.',
                'GPS validation system error' => 'Terjadi kesalahan sistem validasi GPS. Coba lagi dalam beberapa saat.',
                default => 'Validasi lokasi gagal: ' . $validationResult['error']
            };
        }

        $distance = $validationResult['distance'] ?? 0;
        $locationName = $validationResult['location']['name'] ?? 'lokasi kerja';
        
        if ($distance > 1000) {
            return "Anda berada {$distance}m dari {$locationName}. Silakan mendekat ke area kerja untuk melakukan presensi.";
        } else {
            return "Lokasi tidak valid. Pastikan Anda berada di area {$locationName} untuk melakukan presensi.";
        }
    }

    /**
     * Validate GPS accuracy thresholds
     */
    public function isAccuracyAcceptable(?float $accuracy, int $maxAccuracy = 50): bool
    {
        if ($accuracy === null) {
            return true; // Allow null accuracy
        }

        return $accuracy <= $maxAccuracy;
    }

    /**
     * Get GPS quality rating based on accuracy
     */
    public function getGpsQuality(?float $accuracy): string
    {
        if ($accuracy === null) {
            return 'unknown';
        }

        if ($accuracy <= 5) {
            return 'excellent';
        } elseif ($accuracy <= 15) {
            return 'good';
        } elseif ($accuracy <= 50) {
            return 'fair';
        } else {
            return 'poor';
        }
    }
}