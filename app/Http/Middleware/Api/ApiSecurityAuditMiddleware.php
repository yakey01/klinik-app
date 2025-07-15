<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Services\SessionManager;

class ApiSecurityAuditMiddleware
{
    protected SessionManager $sessionManager;

    public function __construct(SessionManager $sessionManager)
    {
        $this->sessionManager = $sessionManager;
    }

    /**
     * Handle an incoming request and perform security auditing
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $requestId = $this->generateRequestId();
        $securityFlags = [];

        // Pre-request security analysis
        $this->auditRequest($request, $requestId, $securityFlags);

        // Add request ID to headers for tracking
        $request->headers->set('X-Request-ID', $requestId);
        $request->headers->set('X-Request-Start', $startTime);

        $response = $next($request);

        // Post-request security analysis
        $this->auditResponse($request, $response, $requestId, $securityFlags, $startTime);

        return $response;
    }

    /**
     * Audit incoming request for security issues
     */
    private function auditRequest(Request $request, string $requestId, array &$securityFlags): void
    {
        try {
            $user = Auth::user();
            $ipAddress = $request->ip();
            $userAgent = $request->userAgent();
            $path = $request->path();
            $method = $request->method();

            // Basic request logging
            Log::info('API Security Audit - Request', [
                'request_id' => $requestId,
                'user_id' => $user?->id,
                'ip_address' => $ipAddress,
                'method' => $method,
                'path' => $path,
                'user_agent' => substr($userAgent, 0, 200), // Truncate user agent
                'timestamp' => now()->toISOString(),
            ]);

            // Check for suspicious patterns
            if ($this->hasRapidRequests($user, $ipAddress)) {
                $securityFlags[] = 'rapid_requests';
                Log::warning('Security Alert: Rapid requests detected', [
                    'request_id' => $requestId,
                    'user_id' => $user?->id,
                    'ip_address' => $ipAddress,
                ]);
            }

            // Check for IP address anomalies
            if ($user && $this->hasIpAnomalies($user, $ipAddress)) {
                $securityFlags[] = 'ip_anomaly';
                Log::warning('Security Alert: IP address anomaly', [
                    'request_id' => $requestId,
                    'user_id' => $user->id,
                    'current_ip' => $ipAddress,
                ]);
            }

            // Check for suspicious user agent patterns
            if ($this->hasSuspiciousUserAgent($userAgent)) {
                $securityFlags[] = 'suspicious_user_agent';
                Log::warning('Security Alert: Suspicious user agent', [
                    'request_id' => $requestId,
                    'user_id' => $user?->id,
                    'user_agent' => $userAgent,
                ]);
            }

            // Check for geographic anomalies (if location headers available)
            if ($user && $this->hasGeographicAnomalies($request, $user)) {
                $securityFlags[] = 'geographic_anomaly';
                Log::warning('Security Alert: Geographic anomaly detected', [
                    'request_id' => $requestId,
                    'user_id' => $user->id,
                    'country' => $request->header('CF-IPCountry'),
                ]);
            }

            // Store security flags for post-processing
            Cache::put("security_flags_{$requestId}", $securityFlags, 300); // 5 minutes

        } catch (\Exception $e) {
            Log::error('Failed to audit request', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Audit response for security issues and performance
     */
    private function auditResponse(
        Request $request, 
        Response $response, 
        string $requestId, 
        array $securityFlags, 
        float $startTime
    ): void {
        try {
            $responseTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
            $statusCode = $response->getStatusCode();
            $user = Auth::user();

            // Retrieve security flags from cache
            $cachedFlags = Cache::get("security_flags_{$requestId}", []);
            $allFlags = array_merge($securityFlags, $cachedFlags);

            // Performance and response logging
            Log::info('API Security Audit - Response', [
                'request_id' => $requestId,
                'user_id' => $user?->id,
                'status_code' => $statusCode,
                'response_time_ms' => round($responseTime, 2),
                'security_flags' => $allFlags,
                'timestamp' => now()->toISOString(),
            ]);

            // Flag slow responses for security investigation
            if ($responseTime > 5000) { // 5 seconds
                Log::warning('Performance Alert: Slow response detected', [
                    'request_id' => $requestId,
                    'response_time_ms' => round($responseTime, 2),
                    'path' => $request->path(),
                ]);
            }

            // Flag failed authentication attempts
            if ($statusCode === 401) {
                $this->trackFailedAuthentication($request);
            }

            // Flag suspicious activity for logged-in users
            if ($user && !empty($allFlags)) {
                $this->sessionManager->flagSuspiciousActivity(
                    $user,
                    'api_security_flags',
                    [
                        'flags' => $allFlags,
                        'request_id' => $requestId,
                        'endpoint' => $request->path(),
                    ]
                );
            }

            // Clean up temporary cache
            Cache::forget("security_flags_{$requestId}");

        } catch (\Exception $e) {
            Log::error('Failed to audit response', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check for rapid requests from same user/IP
     */
    private function hasRapidRequests($user, string $ipAddress): bool
    {
        $key = $user ? "rapid_requests_user_{$user->id}" : "rapid_requests_ip_{$ipAddress}";
        $requests = Cache::get($key, 0);
        
        // Allow up to 10 requests per minute
        if ($requests > 10) {
            return true;
        }

        Cache::put($key, $requests + 1, 60); // 1 minute TTL
        return false;
    }

    /**
     * Check for IP address anomalies
     */
    private function hasIpAnomalies($user, string $currentIp): bool
    {
        if (!$user) {
            return false;
        }

        $lastKnownIp = Cache::get("last_ip_user_{$user->id}");
        
        if (!$lastKnownIp) {
            Cache::put("last_ip_user_{$user->id}", $currentIp, 24 * 60 * 60); // 24 hours
            return false;
        }

        if ($lastKnownIp !== $currentIp) {
            Cache::put("last_ip_user_{$user->id}", $currentIp, 24 * 60 * 60);
            return true;
        }

        return false;
    }

    /**
     * Check for suspicious user agent patterns
     */
    private function hasSuspiciousUserAgent(?string $userAgent): bool
    {
        if (!$userAgent) {
            return true; // Missing user agent is suspicious
        }

        $suspiciousPatterns = [
            'bot', 'crawler', 'spider', 'scraper', 'curl', 'wget', 'python', 'postman'
        ];

        $userAgentLower = strtolower($userAgent);
        
        foreach ($suspiciousPatterns as $pattern) {
            if (str_contains($userAgentLower, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for geographic anomalies
     */
    private function hasGeographicAnomalies(Request $request, $user): bool
    {
        $currentCountry = $request->header('CF-IPCountry');
        
        if (!$currentCountry) {
            return false; // No location data available
        }

        $lastKnownCountry = Cache::get("last_country_user_{$user->id}");
        
        if (!$lastKnownCountry) {
            Cache::put("last_country_user_{$user->id}", $currentCountry, 24 * 60 * 60);
            return false;
        }

        if ($lastKnownCountry !== $currentCountry) {
            // Check if the change happened within a suspicious timeframe
            $lastLocationChange = Cache::get("last_location_change_user_{$user->id}");
            
            if ($lastLocationChange && now()->diffInMinutes($lastLocationChange) < 60) {
                return true; // Country changed within 1 hour - suspicious
            }

            Cache::put("last_country_user_{$user->id}", $currentCountry, 24 * 60 * 60);
            Cache::put("last_location_change_user_{$user->id}", now(), 24 * 60 * 60);
        }

        return false;
    }

    /**
     * Track failed authentication attempts
     */
    private function trackFailedAuthentication(Request $request): void
    {
        $ipAddress = $request->ip();
        $key = "failed_auth_ip_{$ipAddress}";
        $attempts = Cache::get($key, 0) + 1;

        Cache::put($key, $attempts, 60 * 15); // 15 minutes

        if ($attempts >= 5) {
            Log::warning('Security Alert: Multiple failed authentication attempts', [
                'ip_address' => $ipAddress,
                'attempts' => $attempts,
                'path' => $request->path(),
                'user_agent' => $request->userAgent(),
            ]);
        }
    }

    /**
     * Generate unique request ID
     */
    private function generateRequestId(): string
    {
        return substr(str_replace('-', '', \Illuminate\Support\Str::uuid()->toString()), 0, 16);
    }
}