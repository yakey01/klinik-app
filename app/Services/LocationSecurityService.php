<?php

namespace App\Services;

use App\Models\User;
use App\Models\WorkLocation;
use App\Models\LocationValidation;
use App\Models\GpsSpoofingConfig;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Unified Location Security Service
 * 
 * Consolidates GPS validation, geofencing, and spoofing detection
 * into a single comprehensive security analysis system.
 */
class LocationSecurityService
{
    /**
     * Comprehensive location security analysis
     * Combines geofencing validation with advanced spoofing detection
     */
    public function analyzeLocationSecurity(User $user, array $locationData, string $attendanceType): array
    {
        Log::info('Starting comprehensive location security analysis', [
            'user_id' => $user->id,
            'attendance_type' => $attendanceType,
            'coordinates' => "{$locationData['latitude']},{$locationData['longitude']}"
        ]);

        // Step 1: Basic GPS validation
        $basicValidation = $this->validateGpsCoordinates($locationData);
        if (!$basicValidation['valid']) {
            return $this->buildSecurityResult($user, $locationData, $attendanceType, $basicValidation, [], 'critical');
        }

        // Step 2: Geofencing analysis
        $geofencingResult = $this->performGeofencingAnalysis($user, $locationData);
        
        // Step 3: Advanced spoofing detection
        $spoofingAnalysis = $this->performSpoofingDetection($user, $locationData, $attendanceType);
        
        // Step 4: Risk assessment and decision
        $riskAssessment = $this->calculateComprehensiveRisk($geofencingResult, $spoofingAnalysis);
        
        // Step 5: Create unified record
        $validationRecord = $this->createLocationValidationRecord(
            $user, 
            $locationData, 
            $attendanceType, 
            $geofencingResult, 
            $spoofingAnalysis, 
            $riskAssessment
        );

        return $this->buildSecurityResult(
            $user, 
            $locationData, 
            $attendanceType, 
            $geofencingResult, 
            $spoofingAnalysis, 
            $riskAssessment['risk_level'],
            $validationRecord
        );
    }

    /**
     * Basic GPS coordinate validation
     */
    private function validateGpsCoordinates(array $locationData): array
    {
        $latitude = (float) $locationData['latitude'];
        $longitude = (float) $locationData['longitude'];
        
        // Validate coordinate ranges
        if ($latitude < -90 || $latitude > 90) {
            return ['valid' => false, 'error' => 'Invalid latitude range'];
        }
        
        if ($longitude < -180 || $longitude > 180) {
            return ['valid' => false, 'error' => 'Invalid longitude range'];
        }
        
        // Check for obviously fake coordinates
        if ($latitude == 0 && $longitude == 0) {
            return ['valid' => false, 'error' => 'Null Island coordinates detected'];
        }
        
        return [
            'valid' => true,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy' => $locationData['accuracy'] ?? null
        ];
    }

    /**
     * Geofencing analysis against work locations
     */
    private function performGeofencingAnalysis(User $user, array $locationData): array
    {
        $workLocations = WorkLocation::where('is_active', true)->get();
        $userLatitude = (float) $locationData['latitude'];
        $userLongitude = (float) $locationData['longitude'];
        
        $closestLocation = null;
        $shortestDistance = PHP_FLOAT_MAX;
        
        foreach ($workLocations as $location) {
            $distance = $this->calculateHaversineDistance(
                $userLatitude, 
                $userLongitude,
                $location->latitude, 
                $location->longitude
            );
            
            if ($distance < $shortestDistance) {
                $shortestDistance = $distance;
                $closestLocation = $location;
            }
        }
        
        $isWithinZone = $closestLocation && $shortestDistance <= $closestLocation->radius_meters;
        
        return [
            'is_within_zone' => $isWithinZone,
            'closest_location' => $closestLocation,
            'distance_from_zone' => $shortestDistance,
            'work_zone_radius' => $closestLocation ? $closestLocation->radius_meters : 0,
            'geofencing_passed' => $isWithinZone
        ];
    }

    /**
     * Advanced GPS spoofing detection
     */
    private function performSpoofingDetection(User $user, array $locationData, string $attendanceType): array
    {
        $config = $this->getSpoofingConfig();
        $detectionResults = [];
        $riskScore = 0;
        
        // Detection 1: Mock Location
        $mockDetection = $this->detectMockLocation($locationData, $config);
        $detectionResults['mock_location'] = $mockDetection;
        if ($mockDetection['detected']) $riskScore += $config['mock_location_weight'];
        
        // Detection 2: Coordinate Anomalies
        $anomalyDetection = $this->detectCoordinateAnomalies($locationData, $config);
        $detectionResults['coordinate_anomaly'] = $anomalyDetection;
        if ($anomalyDetection['detected']) $riskScore += $config['coordinate_anomaly_weight'];
        
        // Detection 3: Impossible Travel
        $travelDetection = $this->detectImpossibleTravel($user, $locationData, $config);
        $detectionResults['impossible_travel'] = $travelDetection;
        if ($travelDetection['detected']) $riskScore += $config['impossible_travel_weight'];
        
        // Detection 4: Device Integrity
        $deviceDetection = $this->checkDeviceIntegrity($locationData, $config);
        $detectionResults['device_integrity'] = $deviceDetection;
        if ($deviceDetection['failed']) $riskScore += $config['device_integrity_weight'];
        
        // Detection 5: GPS Accuracy Analysis
        $accuracyDetection = $this->analyzeGpsAccuracy($locationData, $config);
        $detectionResults['gps_accuracy'] = $accuracyDetection;
        if ($accuracyDetection['suspicious']) $riskScore += $config['gps_accuracy_weight'];
        
        return [
            'detection_results' => $detectionResults,
            'raw_risk_score' => $riskScore,
            'spoofing_detected' => $riskScore > $config['spoofing_threshold'],
            'detection_methods_triggered' => $this->getTriggeredMethods($detectionResults)
        ];
    }

    /**
     * Mock location detection
     */
    private function detectMockLocation(array $locationData, array $config): array
    {
        $indicators = [];
        $detected = false;
        
        // Check for suspiciously perfect accuracy
        $accuracy = $locationData['accuracy'] ?? 0;
        if ($accuracy > 0 && $accuracy < 5) {
            $indicators[] = 'Perfect GPS accuracy';
            $detected = true;
        }
        
        // Check for mock provider indicators
        if (isset($locationData['is_mock']) && $locationData['is_mock']) {
            $indicators[] = 'Mock provider detected';
            $detected = true;
        }
        
        return [
            'detected' => $detected,
            'indicators' => $indicators,
            'confidence' => $detected ? 0.8 : 0.1
        ];
    }

    /**
     * Coordinate anomaly detection
     */
    private function detectCoordinateAnomalies(array $locationData, array $config): array
    {
        $latitude = (float) $locationData['latitude'];
        $longitude = (float) $locationData['longitude'];
        $indicators = [];
        $detected = false;
        
        // Check for too many decimal places (> 8 is suspicious)
        $latDecimals = strlen(substr(strrchr($latitude, "."), 1));
        $lngDecimals = strlen(substr(strrchr($longitude, "."), 1));
        
        if ($latDecimals > 8 || $lngDecimals > 8) {
            $indicators[] = 'Unrealistic coordinate precision';
            $detected = true;
        }
        
        // Check for repeating patterns
        $latStr = (string) $latitude;
        $lngStr = (string) $longitude;
        
        if (preg_match('/(\d)\1{4,}/', $latStr) || preg_match('/(\d)\1{4,}/', $lngStr)) {
            $indicators[] = 'Repeating decimal patterns';
            $detected = true;
        }
        
        return [
            'detected' => $detected,
            'indicators' => $indicators,
            'confidence' => $detected ? 0.7 : 0.2
        ];
    }

    /**
     * Impossible travel detection
     */
    private function detectImpossibleTravel(User $user, array $locationData, array $config): array
    {
        $lastValidation = LocationValidation::where('user_id', $user->id)
            ->orderBy('validation_time', 'desc')
            ->first();
            
        if (!$lastValidation) {
            return ['detected' => false, 'reason' => 'No previous location data'];
        }
        
        $currentLat = (float) $locationData['latitude'];
        $currentLng = (float) $locationData['longitude'];
        
        $distance = $this->calculateHaversineDistance(
            $lastValidation->latitude,
            $lastValidation->longitude,
            $currentLat,
            $currentLng
        );
        
        $timeDiff = now()->diffInSeconds($lastValidation->validation_time);
        $speedKmh = $timeDiff > 0 ? ($distance / 1000) / ($timeDiff / 3600) : 0;
        
        $maxSpeed = $config['max_travel_speed_kmh'] ?? 200;
        $detected = $speedKmh > $maxSpeed && $timeDiff > 60; // Ignore if < 1 minute
        
        return [
            'detected' => $detected,
            'speed_kmh' => round($speedKmh, 2),
            'distance_km' => round($distance / 1000, 2),
            'time_diff_minutes' => round($timeDiff / 60, 1),
            'max_allowed_speed' => $maxSpeed,
            'confidence' => $detected ? 0.9 : 0.1
        ];
    }

    /**
     * Device integrity check
     */
    private function checkDeviceIntegrity(array $locationData, array $config): array
    {
        $indicators = [];
        $failed = false;
        
        // Check for developer mode indicators
        if (isset($locationData['developer_mode']) && $locationData['developer_mode']) {
            $indicators[] = 'Developer mode enabled';
            $failed = true;
        }
        
        // Check for root/jailbreak indicators
        if (isset($locationData['rooted']) && $locationData['rooted']) {
            $indicators[] = 'Device is rooted/jailbroken';
            $failed = true;
        }
        
        return [
            'failed' => $failed,
            'indicators' => $indicators,
            'confidence' => $failed ? 0.8 : 0.3
        ];
    }

    /**
     * GPS accuracy analysis
     */
    private function analyzeGpsAccuracy(array $locationData, array $config): array
    {
        $accuracy = $locationData['accuracy'] ?? 0;
        $minAccuracy = $config['min_gps_accuracy_meters'] ?? 5;
        $maxAccuracy = $config['max_gps_accuracy_meters'] ?? 100;
        
        $suspicious = $accuracy < $minAccuracy || $accuracy > $maxAccuracy;
        
        return [
            'suspicious' => $suspicious,
            'accuracy' => $accuracy,
            'min_threshold' => $minAccuracy,
            'max_threshold' => $maxAccuracy,
            'confidence' => $suspicious ? 0.6 : 0.2
        ];
    }

    /**
     * Calculate comprehensive risk assessment
     */
    private function calculateComprehensiveRisk(array $geofencingResult, array $spoofingAnalysis): array
    {
        $baseRiskScore = $spoofingAnalysis['raw_risk_score'];
        
        // Adjust risk based on geofencing
        if (!$geofencingResult['is_within_zone']) {
            $baseRiskScore += 20; // Add risk for being outside work zone
        }
        
        // Determine risk level
        $riskLevel = 'low';
        if ($baseRiskScore >= 80) $riskLevel = 'critical';
        elseif ($baseRiskScore >= 60) $riskLevel = 'high';
        elseif ($baseRiskScore >= 40) $riskLevel = 'medium';
        
        // Determine action
        $actionTaken = 'none';
        if ($riskLevel === 'critical') $actionTaken = 'blocked';
        elseif ($riskLevel === 'high') $actionTaken = 'flagged';
        elseif ($riskLevel === 'medium') $actionTaken = 'warning';
        
        return [
            'risk_score' => $baseRiskScore,
            'risk_level' => $riskLevel,
            'action_taken' => $actionTaken,
            'is_blocked' => $actionTaken === 'blocked',
            'requires_review' => in_array($actionTaken, ['blocked', 'flagged'])
        ];
    }

    /**
     * Create unified location validation record
     */
    private function createLocationValidationRecord(
        User $user,
        array $locationData,
        string $attendanceType,
        array $geofencingResult,
        array $spoofingAnalysis,
        array $riskAssessment
    ): LocationValidation {
        return LocationValidation::create([
            'user_id' => $user->id,
            'latitude' => $locationData['latitude'],
            'longitude' => $locationData['longitude'],
            'accuracy' => $locationData['accuracy'] ?? null,
            'work_zone_radius' => $geofencingResult['work_zone_radius'],
            'is_within_zone' => $geofencingResult['is_within_zone'],
            'distance_from_zone' => $geofencingResult['distance_from_zone'],
            'validation_time' => now(),
            'attendance_type' => $attendanceType,
            
            // Enhanced security fields
            'risk_level' => $riskAssessment['risk_level'],
            'risk_score' => $riskAssessment['risk_score'],
            'is_spoofed' => $spoofingAnalysis['spoofing_detected'],
            'action_taken' => $riskAssessment['action_taken'],
            'detection_results' => json_encode($spoofingAnalysis['detection_results']),
            'spoofing_indicators' => json_encode($spoofingAnalysis['detection_methods_triggered']),
            
            'notes' => $this->generateValidationNotes($geofencingResult, $spoofingAnalysis, $riskAssessment)
        ]);
    }

    /**
     * Build comprehensive security result
     */
    private function buildSecurityResult(
        User $user,
        array $locationData,
        string $attendanceType,
        array $geofencingResult,
        array $spoofingAnalysis = [],
        string $riskLevel = 'low',
        LocationValidation $record = null
    ): array {
        return [
            'success' => $riskLevel !== 'critical',
            'location_valid' => $geofencingResult['geofencing_passed'] ?? false,
            'security_passed' => !($spoofingAnalysis['spoofing_detected'] ?? false),
            'risk_level' => $riskLevel,
            'action_taken' => $riskLevel === 'critical' ? 'blocked' : 'allowed',
            
            'geofencing' => $geofencingResult,
            'spoofing_analysis' => $spoofingAnalysis,
            'validation_record_id' => $record ? $record->id : null,
            
            'message' => $this->getSecurityMessage($geofencingResult, $spoofingAnalysis, $riskLevel)
        ];
    }

    /**
     * Generate validation notes
     */
    private function generateValidationNotes(array $geofencingResult, array $spoofingAnalysis, array $riskAssessment): string
    {
        $notes = [];
        
        if (!$geofencingResult['is_within_zone']) {
            $distance = round($geofencingResult['distance_from_zone'], 2);
            $notes[] = "Outside work zone by {$distance}m";
        }
        
        if ($spoofingAnalysis['spoofing_detected']) {
            $methods = implode(', ', $spoofingAnalysis['detection_methods_triggered']);
            $notes[] = "Spoofing detected: {$methods}";
        }
        
        if ($riskAssessment['risk_level'] !== 'low') {
            $notes[] = "Risk level: {$riskAssessment['risk_level']} (score: {$riskAssessment['risk_score']})";
        }
        
        return implode('; ', $notes);
    }

    /**
     * Get security message for user feedback
     */
    private function getSecurityMessage(array $geofencingResult, array $spoofingAnalysis, string $riskLevel): string
    {
        if ($riskLevel === 'critical') {
            return 'Akses ditolak karena terdeteksi aktivitas mencurigakan pada lokasi GPS Anda.';
        }
        
        if (!$geofencingResult['geofencing_passed']) {
            $distance = round($geofencingResult['distance_from_zone'], 2);
            return "Anda berada di luar zona kerja sejauh {$distance} meter.";
        }
        
        if ($spoofingAnalysis['spoofing_detected'] ?? false) {
            return 'Lokasi GPS Anda memerlukan verifikasi tambahan.';
        }
        
        return 'Validasi lokasi berhasil.';
    }

    /**
     * Get triggered detection methods
     */
    private function getTriggeredMethods(array $detectionResults): array
    {
        $triggered = [];
        
        foreach ($detectionResults as $method => $result) {
            if (($result['detected'] ?? false) || ($result['failed'] ?? false) || ($result['suspicious'] ?? false)) {
                $triggered[] = str_replace('_', ' ', ucfirst($method));
            }
        }
        
        return $triggered;
    }

    /**
     * Get spoofing configuration
     */
    private function getSpoofingConfig(): array
    {
        return Cache::remember('gps_spoofing_config', 3600, function () {
            $config = GpsSpoofingConfig::first();
            
            return $config ? [
                'max_travel_speed_kmh' => $config->max_travel_speed_kmh,
                'min_gps_accuracy_meters' => $config->min_gps_accuracy_meters,
                'max_gps_accuracy_meters' => $config->max_gps_accuracy_meters,
                'spoofing_threshold' => 50, // Default threshold
                'mock_location_weight' => 30,
                'coordinate_anomaly_weight' => 20,
                'impossible_travel_weight' => 40,
                'device_integrity_weight' => 35,
                'gps_accuracy_weight' => 15,
            ] : $this->getDefaultConfig();
        });
    }

    /**
     * Default configuration values
     */
    private function getDefaultConfig(): array
    {
        return [
            'max_travel_speed_kmh' => 200,
            'min_gps_accuracy_meters' => 5,
            'max_gps_accuracy_meters' => 100,
            'spoofing_threshold' => 50,
            'mock_location_weight' => 30,
            'coordinate_anomaly_weight' => 20,
            'impossible_travel_weight' => 40,
            'device_integrity_weight' => 35,
            'gps_accuracy_weight' => 15,
        ];
    }

    /**
     * Calculate distance using Haversine formula
     */
    private function calculateHaversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // Earth's radius in meters
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng/2) * sin($dLng/2);
             
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earthRadius * $c;
    }
}