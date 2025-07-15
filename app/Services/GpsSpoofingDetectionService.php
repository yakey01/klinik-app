<?php

namespace App\Services;

use App\Models\GpsSpoofingDetection;
use App\Models\GpsSpoofingConfig;
use App\Models\User;
use App\Notifications\GpsSpoofingAlert;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

class GpsSpoofingDetectionService
{
    private ?GpsSpoofingConfig $config;
    
    public function __construct()
    {
        $this->config = GpsSpoofingConfig::getActiveConfig();
    }
    
    /**
     * Main analysis method for GPS data
     */
    public function analyzeGpsData(User $user, array $locationData, string $attendanceType = 'check_in'): array
    {
        // Check if GPS spoofing detection is enabled
        if (!$this->config || !$this->config->is_active) {
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
        
        // Perform actual GPS spoofing detection
        $detectionResult = $this->performDetection($user, $locationData, $attendanceType);
        
        // Log the detection for monitoring
        Log::info('GPS spoofing detection performed', [
            'user_id' => $user->id,
            'attendance_type' => $attendanceType,
            'is_spoofed' => $detectionResult['is_spoofed'],
            'risk_level' => $detectionResult['risk_level'],
            'risk_score' => $detectionResult['risk_score'],
            'detection_methods' => $detectionResult['detection_methods'],
        ]);
        
        return $detectionResult;
    }
    
    /**
     * Perform GPS spoofing detection
     */
    private function performDetection(User $user, array $locationData, string $attendanceType): array
    {
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
        if ($this->isIpWhitelisted($ip)) {
            return true;
        }
        
        // Check device whitelist
        if (isset($locationData['device_id']) && $this->isDeviceWhitelisted($locationData['device_id'])) {
            return true;
        }
        
        // Check trusted locations
        if (isset($locationData['latitude'], $locationData['longitude'])) {
            if ($this->isTrustedLocation($locationData['latitude'], $locationData['longitude'])) {
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

        $enabledMethods = $this->config->enabled_methods ?? [
            'mock_location', 'fake_gps_app', 'developer_mode', 
            'impossible_travel', 'coordinate_anomaly', 'device_integrity'
        ];
        $detectionScores = $this->config->detection_scores ?? [
            'mock_location' => 30,
            'fake_gps_app' => 25,
            'developer_mode' => 15,
            'impossible_travel' => 40,
            'coordinate_anomaly' => 20,
            'device_integrity' => 35,
        ];
        
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

        // Determine risk level and spoofing status using config
        $results['risk_level'] = $this->calculateRiskLevel($results['risk_score']);
        $results['is_spoofed'] = $results['risk_score'] >= ($this->config->flagged_threshold ?? 70);
        $results['action_taken'] = $this->determineAction($results['risk_score']);

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

        // Check for suspiciously perfect accuracy using config
        $accuracyThreshold = $this->config->accuracy_threshold ?? 5;
        if (isset($locationData['accuracy']) && $locationData['accuracy'] <= $accuracyThreshold) {
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

        // Get fake GPS apps from config database
        $knownFakeApps = $this->config->fake_gps_apps_database ?? [
            'com.lexa.fakegps',
            'com.incorporateapps.fakegps.freeversion',
            'com.blogspot.newapphorizons.fakegps',
            'com.teamsrsoft.fakegps',
            'com.drakulaapps.fakegps',
        ];

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

            // Flag as impossible using config threshold
            $maxTravelSpeed = $this->config->max_travel_speed_kmh ?? 200;
            if ($speedKmh > $maxTravelSpeed) {
                $detected = true;
                $indicator = "Impossible travel: {$speedKmh} km/h over {$distance}m in {$timeDiff}s (max allowed: {$maxTravelSpeed} km/h)";
            }
            
            // Also check minimum time between locations
            $minTimeBetween = $this->config->min_time_between_locations ?? 60;
            if ($timeDiff < $minTimeBetween) {
                $detected = true;
                $indicator = "Too frequent location updates: {$timeDiff}s (min required: {$minTimeBetween}s)";
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
     * Check if alert should be sent based on config
     */
    private function shouldSendAlert(array $results): bool
    {
        // Check if notifications are enabled
        $sendEmailAlerts = $this->config->send_email_alerts ?? true;
        $sendRealtimeAlerts = $this->config->send_realtime_alerts ?? true;
        
        if (!$sendEmailAlerts && !$sendRealtimeAlerts) {
            return false;
        }
        
        // If only critical alerts are enabled
        $sendCriticalOnly = $this->config->send_critical_only ?? false;
        if ($sendCriticalOnly) {
            return $results['risk_level'] === 'critical';
        }
        
        // Send alerts for flagged and above
        $warningThreshold = $this->config->warning_threshold ?? 50;
        return $results['risk_score'] >= $warningThreshold;
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
            $sendEmailAlerts = $this->config->send_email_alerts ?? true;
            if ($sendEmailAlerts) {
                // Get recipients from config or fallback to admin users
                $recipients = $this->config->notification_recipients ?? [];
                
                if (empty($recipients)) {
                    // Fallback to admin users
                    $admins = User::whereHas('roles', function($query) {
                        $query->where('name', 'admin');
                    })->get();
                    
                    // Send email and database notifications
                    if ($admins->count() > 0) {
                        Notification::send($admins, new GpsSpoofingAlert($detection));
                    }
                } else {
                    // Send to specific recipients
                    foreach ($recipients as $email) {
                        \Illuminate\Support\Facades\Mail::to($email)
                            ->send(new \App\Mail\GpsSpoofingAlertMail($detection));
                    }
                }
            }

            // Send Filament notification for real-time alerts
            $sendRealtimeAlerts = $this->config->send_realtime_alerts ?? true;
            if ($sendRealtimeAlerts) {
                // This would send to Filament admin panel
                Log::info('Real-time GPS spoofing alert', [
                    'detection_id' => $detection->id,
                    'user_id' => $detection->user_id,
                    'risk_level' => $detection->risk_level,
                ]);
            }

            Log::info('GPS spoofing alert sent', [
                'detection_id' => $detection->id,
                'user_id' => $detection->user_id,
                'risk_level' => $detection->risk_level,
                'email_enabled' => $sendEmailAlerts,
                'realtime_enabled' => $sendRealtimeAlerts,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send GPS spoofing alert', [
                'detection_id' => $detection->id,
                'error' => $e->getMessage(),
                'config_enabled' => [
                    'email' => $this->config->send_email_alerts ?? true,
                    'realtime' => $this->config->send_realtime_alerts ?? true,
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
        $autoBlockEnabled = $this->config->auto_block_enabled ?? false;
        if (!$autoBlockEnabled) {
            return false;
        }
        
        $blockDuration = $this->config->block_duration_hours ?? 24;
        
        return GpsSpoofingDetection::where('user_id', $user->id)
            ->where('is_blocked', true)
            ->where('attempted_at', '>=', now()->subHours($blockDuration))
            ->exists();
    }
    
    /**
     * Get current config
     */
    public function getConfig(): ?GpsSpoofingConfig
    {
        return $this->config;
    }
    
    /**
     * Refresh config from database
     */
    public function refreshConfig(): void
    {
        $this->config = GpsSpoofingConfig::getActiveConfig();
    }
    
    /**
     * Calculate risk level based on score
     */
    private function calculateRiskLevel(int $score): string
    {
        $criticalThreshold = $this->config->critical_threshold ?? 80;
        $highThreshold = $this->config->high_threshold ?? 60;
        $mediumThreshold = $this->config->medium_threshold ?? 40;
        
        if ($score >= $criticalThreshold) {
            return 'critical';
        } elseif ($score >= $highThreshold) {
            return 'high';
        } elseif ($score >= $mediumThreshold) {
            return 'medium';
        } else {
            return 'low';
        }
    }
    
    /**
     * Determine action based on risk score
     */
    private function determineAction(int $score): string
    {
        $blockThreshold = $this->config->block_threshold ?? 90;
        $flagThreshold = $this->config->flagged_threshold ?? 70;
        $warnThreshold = $this->config->warning_threshold ?? 50;
        
        if ($score >= $blockThreshold) {
            return 'blocked';
        } elseif ($score >= $flagThreshold) {
            return 'flagged';
        } elseif ($score >= $warnThreshold) {
            return 'warning';
        } else {
            return 'none';
        }
    }
    
    // Helper methods for backward compatibility
    private function isIpWhitelisted(string $ip): bool
    {
        if (!$this->config || !$this->config->whitelisted_ips) {
            return false;
        }
        
        foreach ($this->config->whitelisted_ips as $whitelistedIp) {
            if (isset($whitelistedIp['ip']) && $whitelistedIp['ip'] === $ip) {
                return true;
            }
        }
        
        return false;
    }
    
    private function isDeviceWhitelisted(string $deviceId): bool
    {
        if (!$this->config || !$this->config->whitelisted_devices) {
            return false;
        }
        
        foreach ($this->config->whitelisted_devices as $whitelistedDevice) {
            if (isset($whitelistedDevice['device_id']) && $whitelistedDevice['device_id'] === $deviceId) {
                return true;
            }
        }
        
        return false;
    }
    
    private function isTrustedLocation(float $lat, float $lon): bool
    {
        if (!$this->config || !$this->config->trusted_locations) {
            return false;
        }
        
        foreach ($this->config->trusted_locations as $location) {
            if (isset($location['latitude'], $location['longitude'], $location['radius'])) {
                $distance = $this->calculateDistance(
                    $lat, $lon,
                    $location['latitude'], $location['longitude']
                );
                
                if ($distance <= $location['radius']) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
}