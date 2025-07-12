<?php

namespace App\Services;

use App\Models\GpsSpoofingDetection;
use App\Models\GpsSpoofingSetting;
use App\Models\User;
use App\Notifications\GpsSpoofingAlert;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

class GpsSpoofingDetectionService
{
    private GpsSpoofingSetting $settings;
    
    public function __construct()
    {
        $this->settings = GpsSpoofingSetting::current();
    }
    
    /**
     * Main analysis method for GPS data
     */
    public function analyzeGpsData(User $user, array $locationData, string $attendanceType = 'check_in'): array
    {
        // Check if GPS spoofing detection is enabled
        if (!$this->settings->is_enabled) {
            return [
                'is_spoofed' => false,
                'risk_level' => 'low',
                'risk_score' => 0,
                'detection_methods' => [],
                'spoofing_indicators' => [],
                'action_taken' => 'none',
                'message' => 'GPS spoofing detection is disabled',
            ];
        }
        
        // Check whitelist first
        if ($this->isWhitelisted($user, $locationData)) {
            return [
                'is_spoofed' => false,
                'risk_level' => 'low',
                'risk_score' => 0,
                'detection_methods' => [],
                'spoofing_indicators' => [],
                'action_taken' => 'none',
                'message' => 'User/device/location is whitelisted',
            ];
        }
        
        return $this->detectSpoofing($locationData, $user, $attendanceType);
    }
    
    /**
     * Check if user/device/location is whitelisted
     */
    private function isWhitelisted(User $user, array $locationData): bool
    {
        // Check IP whitelist
        $ip = request()->ip();
        if ($this->settings->isIpWhitelisted($ip)) {
            return true;
        }
        
        // Check device whitelist
        if (isset($locationData['device_id']) && $this->settings->isDeviceWhitelisted($locationData['device_id'])) {
            return true;
        }
        
        // Check trusted locations
        if (isset($locationData['latitude'], $locationData['longitude'])) {
            if ($this->settings->isTrustedLocation($locationData['latitude'], $locationData['longitude'])) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Detect GPS spoofing for attendance
     */
    public function detectSpoofing(array $locationData, User $user, string $attendanceType = 'check_in'): array
    {
        $results = [
            'is_spoofed' => false,
            'risk_level' => 'low',
            'risk_score' => 0,
            'detection_methods' => [],
            'spoofing_indicators' => [],
            'action_taken' => 'none',
        ];

        $enabledMethods = $this->settings->getEnabledMethods();
        $detectionScores = $this->settings->getDetectionScores();
        
        // 1. Mock Location Detection
        if (in_array('mock_location', $enabledMethods)) {
            $mockLocationResult = $this->detectMockLocation($locationData);
            if ($mockLocationResult['detected']) {
                $results['detection_methods'][] = 'mock_location';
                $results['spoofing_indicators'][] = $mockLocationResult['indicator'];
                $results['risk_score'] += $detectionScores['mock_location'];
            }
        }

        // 2. Fake GPS App Detection
        if (in_array('fake_gps_app', $enabledMethods)) {
            $fakeAppResult = $this->detectFakeGpsApps($locationData);
            if ($fakeAppResult['detected']) {
                $results['detection_methods'][] = 'fake_gps_app';
                $results['spoofing_indicators'][] = $fakeAppResult['indicator'];
                $results['risk_score'] += $detectionScores['fake_gps_app'];
            }
        }

        // 3. Developer Mode Detection
        if (in_array('developer_mode', $enabledMethods)) {
            $devModeResult = $this->detectDeveloperMode($locationData);
            if ($devModeResult['detected']) {
                $results['detection_methods'][] = 'developer_mode';
                $results['spoofing_indicators'][] = $devModeResult['indicator'];
                $results['risk_score'] += $detectionScores['developer_mode'];
            }
        }

        // 4. Impossible Travel Detection
        if (in_array('impossible_travel', $enabledMethods)) {
            $impossibleTravelResult = $this->detectImpossibleTravel($user, $locationData);
            if ($impossibleTravelResult['detected']) {
                $results['detection_methods'][] = 'impossible_travel';
                $results['spoofing_indicators'][] = $impossibleTravelResult['indicator'];
                $results['risk_score'] += $detectionScores['impossible_travel'];
            }
        }

        // 5. Coordinate Anomaly Detection
        if (in_array('coordinate_anomaly', $enabledMethods)) {
            $anomalyResult = $this->detectCoordinateAnomaly($locationData);
            if ($anomalyResult['detected']) {
                $results['detection_methods'][] = 'coordinate_anomaly';
                $results['spoofing_indicators'][] = $anomalyResult['indicator'];
                $results['risk_score'] += $detectionScores['coordinate_anomaly'];
            }
        }

        // 6. Device Integrity Check
        if (in_array('device_integrity', $enabledMethods)) {
            $integrityResult = $this->checkDeviceIntegrity($locationData);
            if (!$integrityResult['passed']) {
                $results['detection_methods'][] = 'device_integrity_failed';
                $results['spoofing_indicators'][] = $integrityResult['indicator'];
                $results['risk_score'] += $detectionScores['device_integrity'];
            }
        }

        // Determine risk level and spoofing status using settings
        $results['risk_level'] = $this->settings->calculateRiskLevel($results['risk_score']);
        $results['is_spoofed'] = $results['risk_score'] >= $this->settings->flagged_threshold;
        $results['action_taken'] = $this->settings->determineAction($results['risk_score']);

        // Store detection record
        $detection = $this->storeDetection($user, $locationData, $results, $attendanceType);

        // Send alert notifications based on settings
        if ($this->shouldSendAlert($results)) {
            $this->sendSpoofingAlert($detection);
        }

        return $results;
    }

    /**
     * Detect mock location usage
     */
    private function detectMockLocation(array $locationData): array
    {
        $detected = false;
        $indicators = [];

        // Check for mock location provider indicators
        if (isset($locationData['provider']) && $locationData['provider'] === 'mock') {
            $detected = true;
            $indicators[] = 'Mock provider detected in location data';
        }

        // Check for suspiciously perfect accuracy using settings
        if (isset($locationData['accuracy']) && $locationData['accuracy'] <= $this->settings->accuracy_threshold) {
            $detected = true;
            $indicators[] = 'Suspiciously perfect accuracy (' . $locationData['accuracy'] . 'm)';
        }

        // Check for mock location flags in device fingerprint
        if (isset($locationData['device_fingerprint']['mock_location_enabled'])) {
            $detected = true;
            $indicators[] = 'Mock location enabled in device settings';
        }

        return [
            'detected' => $detected,
            'indicator' => implode('; ', $indicators),
        ];
    }

    /**
     * Detect fake GPS applications
     */
    private function detectFakeGpsApps(array $locationData): array
    {
        $detected = false;
        $fakeApps = [];

        // Get fake GPS apps from settings database
        $knownFakeApps = $this->settings->fake_gps_apps_database ?? [];

        // Check for fake GPS apps in device fingerprint
        if (isset($locationData['device_fingerprint']['installed_apps'])) {
            $installedApps = $locationData['device_fingerprint']['installed_apps'];
            foreach ($knownFakeApps as $fakeApp) {
                if (in_array($fakeApp, $installedApps)) {
                    $detected = true;
                    $fakeApps[] = $fakeApp;
                }
            }
        }

        // Check for suspicious app signatures
        if (isset($locationData['device_fingerprint']['suspicious_apps'])) {
            $suspiciousApps = $locationData['device_fingerprint']['suspicious_apps'];
            if (count($suspiciousApps) > 0) {
                $detected = true;
                $fakeApps = array_merge($fakeApps, $suspiciousApps);
            }
        }

        return [
            'detected' => $detected,
            'indicator' => $detected ? 'Fake GPS apps detected: ' . implode(', ', $fakeApps) : '',
        ];
    }

    /**
     * Detect developer mode activation
     */
    private function detectDeveloperMode(array $locationData): array
    {
        $detected = false;
        $indicators = [];

        // Check for developer mode indicators
        if (isset($locationData['device_fingerprint']['developer_mode_enabled']) && 
            $locationData['device_fingerprint']['developer_mode_enabled'] === true) {
            $detected = true;
            $indicators[] = 'Developer mode enabled on device';
        }

        // Check for USB debugging
        if (isset($locationData['device_fingerprint']['usb_debugging_enabled']) && 
            $locationData['device_fingerprint']['usb_debugging_enabled'] === true) {
            $detected = true;
            $indicators[] = 'USB debugging enabled';
        }

        // Check for unknown sources
        if (isset($locationData['device_fingerprint']['unknown_sources_enabled']) && 
            $locationData['device_fingerprint']['unknown_sources_enabled'] === true) {
            $detected = true;
            $indicators[] = 'Unknown sources installation enabled';
        }

        return [
            'detected' => $detected,
            'indicator' => implode('; ', $indicators),
        ];
    }

    /**
     * Detect impossible travel (teleportation)
     */
    private function detectImpossibleTravel(User $user, array $locationData): array
    {
        $detected = false;
        $indicator = '';

        // Get last location record for this user
        $lastDetection = GpsSpoofingDetection::where('user_id', $user->id)
            ->orderBy('attempted_at', 'desc')
            ->first();

        if ($lastDetection) {
            $timeDiff = Carbon::parse($locationData['timestamp'] ?? now())
                ->diffInSeconds($lastDetection->attempted_at);
            
            // Calculate distance using Haversine formula
            $distance = $this->calculateDistance(
                $lastDetection->latitude,
                $lastDetection->longitude,
                $locationData['latitude'],
                $locationData['longitude']
            );

            // Calculate speed in km/h
            $speedKmh = $timeDiff > 0 ? ($distance / 1000) / ($timeDiff / 3600) : 0;

            // Flag as impossible using settings threshold
            if ($speedKmh > $this->settings->max_travel_speed_kmh) {
                $detected = true;
                $indicator = "Impossible travel: {$speedKmh} km/h over {$distance}m in {$timeDiff}s (max allowed: {$this->settings->max_travel_speed_kmh} km/h)";
            }
            
            // Also check minimum time between locations
            if ($timeDiff < $this->settings->min_time_between_locations) {
                $detected = true;
                $indicator = "Too frequent location updates: {$timeDiff}s (min required: {$this->settings->min_time_between_locations}s)";
            }

            // Store travel analysis
            $locationData['travel_analysis'] = [
                'speed_kmh' => $speedKmh,
                'distance_km' => $distance / 1000,
                'time_diff_seconds' => $timeDiff,
            ];
        }

        return [
            'detected' => $detected,
            'indicator' => $indicator,
        ];
    }

    /**
     * Detect coordinate anomalies
     */
    private function detectCoordinateAnomaly(array $locationData): array
    {
        $detected = false;
        $indicators = [];

        // Check for suspicious coordinate patterns
        $lat = $locationData['latitude'];
        $lng = $locationData['longitude'];

        // Check for obviously fake coordinates (0,0)
        if ($lat == 0 && $lng == 0) {
            $detected = true;
            $indicators[] = 'Null Island coordinates (0,0)';
        }

        // Check for repeating decimal patterns (common in fake GPS)
        if (preg_match('/(\d)\1{4,}/', str_replace('.', '', (string)$lat))) {
            $detected = true;
            $indicators[] = 'Repeating decimal pattern in latitude';
        }

        if (preg_match('/(\d)\1{4,}/', str_replace('.', '', (string)$lng))) {
            $detected = true;
            $indicators[] = 'Repeating decimal pattern in longitude';
        }

        // Check for impossibly precise coordinates (too many decimal places)
        $latDecimals = strlen(substr(strrchr((string)$lat, "."), 1));
        $lngDecimals = strlen(substr(strrchr((string)$lng, "."), 1));
        
        if ($latDecimals > 10 || $lngDecimals > 10) {
            $detected = true;
            $indicators[] = 'Impossibly precise coordinates';
        }

        // Check for coordinates outside valid ranges
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            $detected = true;
            $indicators[] = 'Coordinates outside valid range';
        }

        return [
            'detected' => $detected,
            'indicator' => implode('; ', $indicators),
        ];
    }

    /**
     * Check device integrity
     */
    private function checkDeviceIntegrity(array $locationData): array
    {
        $passed = true;
        $indicators = [];

        // Check for rooted/jailbroken device
        if (isset($locationData['device_fingerprint']['is_rooted']) && 
            $locationData['device_fingerprint']['is_rooted'] === true) {
            $passed = false;
            $indicators[] = 'Device is rooted/jailbroken';
        }

        // Check for emulator detection
        if (isset($locationData['device_fingerprint']['is_emulator']) && 
            $locationData['device_fingerprint']['is_emulator'] === true) {
            $passed = false;
            $indicators[] = 'Device appears to be an emulator';
        }

        // Check for tampered system files
        if (isset($locationData['device_fingerprint']['system_integrity']) && 
            $locationData['device_fingerprint']['system_integrity'] === false) {
            $passed = false;
            $indicators[] = 'System integrity check failed';
        }

        return [
            'passed' => $passed,
            'indicator' => implode('; ', $indicators),
        ];
    }

    /**
     * Check if alert should be sent based on settings
     */
    private function shouldSendAlert(array $results): bool
    {
        // Check if notifications are enabled
        if (!$this->settings->send_email_alerts && !$this->settings->send_realtime_alerts) {
            return false;
        }
        
        // If only critical alerts are enabled
        if ($this->settings->send_critical_only) {
            return $results['risk_level'] === 'critical';
        }
        
        // Send alerts for flagged and above
        return $results['risk_score'] >= $this->settings->warning_threshold;
    }

    /**
     * Store detection record
     */
    private function storeDetection(User $user, array $locationData, array $results, string $attendanceType): ?GpsSpoofingDetection
    {
        try {
            return GpsSpoofingDetection::create([
                'user_id' => $user->id,
                'device_id' => $locationData['device_id'] ?? null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'latitude' => $locationData['latitude'],
                'longitude' => $locationData['longitude'],
                'accuracy' => $locationData['accuracy'] ?? null,
                'altitude' => $locationData['altitude'] ?? null,
                'speed' => $locationData['speed'] ?? null,
                'heading' => $locationData['heading'] ?? null,
                'detection_results' => $results,
                'risk_level' => $results['risk_level'],
                'risk_score' => $results['risk_score'],
                'is_spoofed' => $results['is_spoofed'],
                'is_blocked' => $results['action_taken'] === 'blocked',
                'mock_location_detected' => in_array('mock_location', $results['detection_methods']),
                'fake_gps_app_detected' => in_array('fake_gps_app', $results['detection_methods']),
                'developer_mode_detected' => in_array('developer_mode', $results['detection_methods']),
                'impossible_travel_detected' => in_array('impossible_travel', $results['detection_methods']),
                'coordinate_anomaly_detected' => in_array('coordinate_anomaly', $results['detection_methods']),
                'device_integrity_failed' => in_array('device_integrity_failed', $results['detection_methods']),
                'spoofing_indicators' => $results['spoofing_indicators'],
                'travel_speed_kmh' => $locationData['travel_analysis']['speed_kmh'] ?? null,
                'time_diff_seconds' => $locationData['travel_analysis']['time_diff_seconds'] ?? null,
                'distance_from_last_km' => $locationData['travel_analysis']['distance_km'] ?? null,
                'action_taken' => $results['action_taken'],
                'attendance_type' => $attendanceType,
                'attempted_at' => $locationData['timestamp'] ?? now(),
                'location_source' => $locationData['source'] ?? 'gps',
                'device_fingerprint' => $locationData['device_fingerprint'] ?? [],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to store GPS spoofing detection', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'location_data' => $locationData,
            ]);
            return null;
        }
    }

    /**
     * Send spoofing alert to administrators
     */
    private function sendSpoofingAlert(?GpsSpoofingDetection $detection): void
    {
        if (!$detection) {
            return;
        }

        try {
            // Check if email alerts are enabled
            if ($this->settings->send_email_alerts) {
                // Get recipients from settings or fallback to admin users
                $recipients = $this->settings->notification_recipients ?? [];
                
                if (empty($recipients)) {
                    // Fallback to admin users
                    $admins = User::whereHas('roles', function($query) {
                        $query->where('name', 'admin');
                    })->get();
                    
                    // Send email and database notifications
                    Notification::send($admins, new GpsSpoofingAlert($detection));
                } else {
                    // Send to specific recipients
                    foreach ($recipients as $email) {
                        \Illuminate\Support\Facades\Mail::to($email)
                            ->send(new \App\Mail\GpsSpoofingAlertMail($detection));
                    }
                }
            }

            // Send Filament notification for real-time alerts
            if ($this->settings->send_realtime_alerts) {
                GpsSpoofingAlert::sendFilamentNotification($detection);
            }

            Log::info('GPS spoofing alert sent', [
                'detection_id' => $detection->id,
                'user_id' => $detection->user_id,
                'risk_level' => $detection->risk_level,
                'email_enabled' => $this->settings->send_email_alerts,
                'realtime_enabled' => $this->settings->send_realtime_alerts,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send GPS spoofing alert', [
                'detection_id' => $detection->id,
                'error' => $e->getMessage(),
                'settings_enabled' => [
                    'email' => $this->settings->send_email_alerts,
                    'realtime' => $this->settings->send_realtime_alerts,
                ],
            ]);
        }
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
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
     * Get detection summary for dashboard
     */
    public function getDetectionSummary(): array
    {
        return GpsSpoofingDetection::getDetectionSummary();
    }

    /**
     * Check if user is currently blocked
     */
    public function isUserBlocked(User $user): bool
    {
        if (!$this->settings->auto_block_enabled) {
            return false;
        }
        
        $blockDuration = $this->settings->block_duration_hours;
        
        return GpsSpoofingDetection::where('user_id', $user->id)
            ->where('is_blocked', true)
            ->where('attempted_at', '>=', now()->subHours($blockDuration))
            ->exists();
    }
    
    /**
     * Get current settings
     */
    public function getSettings(): GpsSpoofingSetting
    {
        return $this->settings;
    }
    
    /**
     * Refresh settings from database
     */
    public function refreshSettings(): void
    {
        $this->settings = GpsSpoofingSetting::current();
    }
}