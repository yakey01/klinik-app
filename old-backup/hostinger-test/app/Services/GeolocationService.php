<?php

namespace App\Services;

class GeolocationService
{
    /**
     * Calculate distance between two coordinates using Haversine formula
     *
     * @param float $lat1 Latitude of first point
     * @param float $lon1 Longitude of first point
     * @param float $lat2 Latitude of second point
     * @param float $lon2 Longitude of second point
     * @return float Distance in meters
     */
    public static function calculateDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371000; // Earth's radius in meters
        
        $latDiff = deg2rad($lat2 - $lat1);
        $lonDiff = deg2rad($lon2 - $lon1);
        
        $a = sin($latDiff / 2) * sin($latDiff / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDiff / 2) * sin($lonDiff / 2);
             
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }
    
    /**
     * Check if coordinates are within allowed radius
     *
     * @param float $lat User latitude
     * @param float $lon User longitude
     * @param float $clinicLat Clinic latitude
     * @param float $clinicLon Clinic longitude
     * @param float $allowedRadius Allowed radius in meters
     * @return bool
     */
    public static function isWithinRadius($lat, $lon, $clinicLat, $clinicLon, $allowedRadius): bool
    {
        $distance = self::calculateDistance($lat, $lon, $clinicLat, $clinicLon);
        return $distance <= $allowedRadius;
    }
    
    /**
     * Validate GPS accuracy
     *
     * @param float $accuracy GPS accuracy in meters
     * @param float $maxAccuracy Maximum allowed accuracy in meters
     * @return bool
     */
    public static function isAccuracyValid($accuracy, $maxAccuracy = 50): bool
    {
        return $accuracy <= $maxAccuracy;
    }
    
    /**
     * Format distance for display
     *
     * @param float $distance Distance in meters
     * @return string
     */
    public static function formatDistance($distance): string
    {
        if ($distance < 1000) {
            return round($distance) . ' meter';
        }
        
        return round($distance / 1000, 1) . ' km';
    }
    
    /**
     * Get clinic coordinates from config
     *
     * @return array
     */
    public static function getClinicCoordinates(): array
    {
        return [
            'latitude' => config('app.clinic_latitude', -6.2088),
            'longitude' => config('app.clinic_longitude', 106.8456),
            'radius' => config('app.clinic_radius', 100),
        ];
    }
    
    /**
     * Validate geolocation data
     *
     * @param array $data
     * @return array
     */
    public static function validateGeolocationData($data): array
    {
        $errors = [];
        
        if (!isset($data['latitude']) || !is_numeric($data['latitude'])) {
            $errors[] = 'Latitude tidak valid';
        }
        
        if (!isset($data['longitude']) || !is_numeric($data['longitude'])) {
            $errors[] = 'Longitude tidak valid';
        }
        
        if (isset($data['latitude']) && ($data['latitude'] < -90 || $data['latitude'] > 90)) {
            $errors[] = 'Latitude harus antara -90 dan 90';
        }
        
        if (isset($data['longitude']) && ($data['longitude'] < -180 || $data['longitude'] > 180)) {
            $errors[] = 'Longitude harus antara -180 dan 180';
        }
        
        return $errors;
    }
}