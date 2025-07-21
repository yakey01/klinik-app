<?php

namespace App\Http\Middleware;

use App\Services\GpsSpoofingDetectionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AntiGpsSpoofingMiddleware
{
    protected GpsSpoofingDetectionService $spoofingService;

    public function __construct(GpsSpoofingDetectionService $spoofingService)
    {
        $this->spoofingService = $spoofingService;
    }

    /**
     * Handle an incoming request for attendance-related actions.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only check for authenticated users
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Check if this is an attendance-related request
        if ($this->isAttendanceRequest($request)) {
            // Extract location data from request
            $locationData = $this->extractLocationData($request);
            
            if ($locationData) {
                // Perform GPS spoofing detection using new method
                $detectionResult = $this->spoofingService->analyzeGpsData(
                    $user,
                    $locationData, 
                    $this->getAttendanceType($request)
                );

                // Block request if spoofing detected or user is blocked
                if ($detectionResult['action_taken'] === 'blocked' || 
                    $this->spoofingService->isUserBlocked($user)) {
                    
                    return $this->createBlockedResponse($request, $detectionResult);
                }

                // Add detection results to request for logging
                $request->merge(['gps_detection_result' => $detectionResult]);
            }
        }

        return $next($request);
    }

    /**
     * Check if the request is attendance-related
     */
    private function isAttendanceRequest(Request $request): bool
    {
        $attendanceRoutes = [
            'attendance.check-in',
            'attendance.check-out',
            'api.attendance.check-in',
            'api.attendance.check-out',
        ];

        $currentRoute = $request->route()?->getName();
        
        return in_array($currentRoute, $attendanceRoutes) ||
               str_contains($request->path(), 'attendance') ||
               str_contains($request->path(), 'presensi') ||
               $request->has(['latitude', 'longitude']) && 
               ($request->isMethod('POST') || $request->isMethod('PUT'));
    }

    /**
     * Extract location data from request
     */
    private function extractLocationData(Request $request): ?array
    {
        // Check if location data exists in request
        if (!$request->has(['latitude', 'longitude'])) {
            return null;
        }

        return [
            'latitude' => (float) $request->input('latitude'),
            'longitude' => (float) $request->input('longitude'),
            'accuracy' => $request->input('accuracy'),
            'altitude' => $request->input('altitude'),
            'speed' => $request->input('speed'),
            'heading' => $request->input('heading'),
            'timestamp' => $request->input('timestamp', now()),
            'source' => $request->input('location_source', 'gps'),
            'device_id' => $request->input('device_id') ?? $this->generateDeviceId($request),
            'device_fingerprint' => $this->extractDeviceFingerprint($request),
        ];
    }

    /**
     * Generate device ID from request
     */
    private function generateDeviceId(Request $request): string
    {
        $userAgent = $request->userAgent();
        $ip = $request->ip();
        
        return hash('sha256', $userAgent . $ip . Auth::id());
    }

    /**
     * Extract device fingerprint data
     */
    private function extractDeviceFingerprint(Request $request): array
    {
        return [
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
            'accept_language' => $request->header('Accept-Language'),
            'screen_resolution' => $request->input('screen_resolution'),
            'timezone' => $request->input('timezone'),
            'platform' => $request->input('platform'),
            'mock_location_enabled' => $request->boolean('mock_location_enabled'),
            'developer_mode_enabled' => $request->boolean('developer_mode_enabled'),
            'usb_debugging_enabled' => $request->boolean('usb_debugging_enabled'),
            'unknown_sources_enabled' => $request->boolean('unknown_sources_enabled'),
            'is_rooted' => $request->boolean('is_rooted'),
            'is_emulator' => $request->boolean('is_emulator'),
            'system_integrity' => $request->boolean('system_integrity', true),
            'installed_apps' => $request->input('installed_apps', []),
            'suspicious_apps' => $request->input('suspicious_apps', []),
        ];
    }

    /**
     * Get attendance type from request
     */
    private function getAttendanceType(Request $request): string
    {
        $route = $request->route()?->getName();
        $path = $request->path();
        
        if (str_contains($route, 'check-out') || str_contains($path, 'check-out')) {
            return 'check_out';
        }
        
        return 'check_in';
    }

    /**
     * Create blocked response for spoofed GPS
     */
    private function createBlockedResponse(Request $request, array $detectionResult): Response
    {
        $message = $this->getBlockedMessage($detectionResult);
        
        // Return JSON response for API requests
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'error' => 'GPS_SPOOFING_DETECTED',
                'message' => $message,
                'details' => [
                    'risk_level' => $detectionResult['risk_level'],
                    'risk_score' => $detectionResult['risk_score'],
                    'detected_methods' => $detectionResult['detection_methods'],
                    'action_taken' => $detectionResult['action_taken'],
                ],
                'blocked_at' => now()->toISOString(),
            ], 403);
        }

        // Return redirect with error for web requests
        return back()->withErrors([
            'gps_spoofing' => $message
        ])->withInput();
    }

    /**
     * Get blocked message based on detection result
     */
    private function getBlockedMessage(array $detectionResult): string
    {
        $baseMessage = '🚫 Presensi diblokir karena terdeteksi GPS spoofing!';
        
        $methods = $detectionResult['detection_methods'];
        $methodMessages = [];
        
        if (in_array('mock_location', $methods)) {
            $methodMessages[] = '📍 Mock location terdeteksi';
        }
        
        if (in_array('fake_gps_app', $methods)) {
            $methodMessages[] = '📱 Aplikasi GPS palsu terdeteksi';
        }
        
        if (in_array('developer_mode', $methods)) {
            $methodMessages[] = '⚙️ Developer mode aktif';
        }
        
        if (in_array('impossible_travel', $methods)) {
            $methodMessages[] = '🚀 Pergerakan tidak wajar terdeteksi';
        }

        if (!empty($methodMessages)) {
            $baseMessage .= "\n\nAlasan: " . implode(', ', $methodMessages);
        }
        
        $baseMessage .= "\n\n⚠️ Risk Level: " . ucfirst($detectionResult['risk_level']);
        $baseMessage .= "\n📊 Risk Score: " . $detectionResult['risk_score'] . "%";
        $baseMessage .= "\n\n💡 Nonaktifkan aplikasi GPS palsu dan developer mode, kemudian coba lagi.";
        
        return $baseMessage;
    }
}
