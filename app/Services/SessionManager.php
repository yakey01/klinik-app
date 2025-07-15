<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserSession;
use App\Models\UserDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class SessionManager
{
    /**
     * Get active sessions for user
     */
    public function getActiveSessions(User $user): Collection
    {
        return UserSession::forUser($user)
            ->active()
            ->with('userDevice')
            ->orderByDesc('last_activity_at')
            ->get()
            ->map(function ($session) {
                return [
                    'session_id' => $session->session_id,
                    'device' => [
                        'id' => $session->userDevice?->device_id,
                        'name' => $session->userDevice?->formatted_device_info ?? 'Unknown Device',
                        'type' => $session->userDevice?->device_type ?? 'unknown',
                        'platform' => $session->userDevice?->platform ?? 'unknown',
                        'is_primary' => $session->userDevice?->is_primary ?? false,
                        'is_trusted' => $session->userDevice?->isTrusted() ?? false,
                    ],
                    'client_type' => $session->client_type,
                    'started_at' => $session->started_at?->toISOString(),
                    'last_activity_at' => $session->last_activity_at?->toISOString(),
                    'duration' => $session->duration,
                    'location' => [
                        'formatted' => $session->formatted_location,
                        'country' => $session->location_country,
                        'city' => $session->location_city,
                        'latitude' => $session->location_latitude,
                        'longitude' => $session->location_longitude,
                    ],
                    'network' => [
                        'ip_address' => $session->ip_address,
                        'user_agent' => $session->user_agent,
                    ],
                    'security' => [
                        'score' => $session->security_score,
                        'flags' => $session->security_flags ?? [],
                        'has_suspicious_activity' => $session->hasSuspiciousActivity(),
                        'is_current' => $this->isCurrentSession($session),
                    ],
                    'status' => $session->status,
                ];
            });
    }

    /**
     * Get session statistics for user
     */
    public function getSessionStatistics(User $user): array
    {
        $activeSessions = UserSession::forUser($user)->active()->count();
        $totalSessions = UserSession::forUser($user)->count();
        $suspiciousSessions = UserSession::forUser($user)
            ->whereNotNull('security_flags')
            ->count();
        
        $recentActivity = UserSession::forUser($user)
            ->recentActivity(24 * 60) // Last 24 hours
            ->count();

        $deviceCount = UserDevice::where('user_id', $user->id)
            ->active()
            ->count();

        return [
            'active_sessions' => $activeSessions,
            'total_sessions_all_time' => $totalSessions,
            'suspicious_sessions' => $suspiciousSessions,
            'recent_activity_24h' => $recentActivity,
            'registered_devices' => $deviceCount,
            'session_limit_reached' => $activeSessions >= $this->getMaxSessionsPerUser($user),
            'security_score' => $this->calculateUserSecurityScore($user),
        ];
    }

    /**
     * End specific session
     */
    public function endSession(string $sessionId, string $reason = 'user_request'): bool
    {
        try {
            $session = UserSession::findBySessionId($sessionId);
            
            if (!$session) {
                return false;
            }

            $session->end($reason);

            // Revoke associated access tokens if needed
            if ($session->access_token_id) {
                \Laravel\Sanctum\PersonalAccessToken::where('id', $session->access_token_id)
                    ->delete();
            }

            Log::info('Session ended', [
                'session_id' => $sessionId,
                'user_id' => $session->user_id,
                'reason' => $reason,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to end session', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Force logout all sessions for security
     */
    public function forceLogoutAllSessions(User $user, string $reason = 'security'): int
    {
        try {
            // Force logout all active sessions
            $loggedOutCount = UserSession::forceLogoutAllForUser($user, $reason);

            // Revoke all access tokens
            $user->tokens()->delete();

            // Log security action
            Log::warning('Force logout all sessions', [
                'user_id' => $user->id,
                'reason' => $reason,
                'sessions_logged_out' => $loggedOutCount,
                'triggered_by' => auth()->id() ?? 'system',
            ]);

            return $loggedOutCount;
        } catch (\Exception $e) {
            Log::error('Failed to force logout all sessions', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Check if current session and flag suspicious activity
     */
    public function flagSuspiciousActivity(
        User $user, 
        string $activityType, 
        array $details = [],
        ?string $sessionId = null
    ): void {
        try {
            $session = $sessionId 
                ? UserSession::findBySessionId($sessionId)
                : $this->getCurrentSession($user);

            if ($session) {
                $session->addSecurityFlag($activityType, $details);

                Log::warning('Suspicious activity flagged', [
                    'user_id' => $user->id,
                    'session_id' => $session->session_id,
                    'activity_type' => $activityType,
                    'details' => $details,
                ]);

                // Auto-logout if security score is too low
                if ($session->security_score < 30) {
                    $session->forceLogout('low_security_score');
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to flag suspicious activity', [
                'user_id' => $user->id,
                'activity_type' => $activityType,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Validate session security and detect anomalies
     */
    public function validateSessionSecurity(UserSession $session, Request $request): array
    {
        $issues = [];

        try {
            // Check IP address changes
            if ($session->ip_address && $session->ip_address !== $request->ip()) {
                $issues[] = [
                    'type' => 'ip_change',
                    'severity' => 'medium',
                    'details' => [
                        'previous_ip' => $session->ip_address,
                        'current_ip' => $request->ip(),
                    ],
                ];
            }

            // Check user agent changes
            if ($session->user_agent && $session->user_agent !== $request->userAgent()) {
                $issues[] = [
                    'type' => 'user_agent_change',
                    'severity' => 'high',
                    'details' => [
                        'previous_agent' => $session->user_agent,
                        'current_agent' => $request->userAgent(),
                    ],
                ];
            }

            // Check session duration
            if ($session->started_at && $session->started_at->diffInHours(now()) > 24) {
                $issues[] = [
                    'type' => 'long_session',
                    'severity' => 'low',
                    'details' => [
                        'session_duration_hours' => $session->started_at->diffInHours(now()),
                    ],
                ];
            }

            // Check for rapid location changes (if location data available)
            if ($this->hasLocationAnomalies($session, $request)) {
                $issues[] = [
                    'type' => 'location_anomaly',
                    'severity' => 'high',
                    'details' => [
                        'message' => 'Rapid geographic location change detected',
                    ],
                ];
            }

            // Flag issues in session
            foreach ($issues as $issue) {
                $session->addSecurityFlag($issue['type'], $issue['details']);
            }

            return $issues;
        } catch (\Exception $e) {
            Log::error('Session security validation failed', [
                'session_id' => $session->session_id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Create session for device binding
     */
    public function createDeviceBoundSession(
        User $user, 
        UserDevice $device, 
        string $clientType = 'mobile_app',
        array $sessionData = []
    ): UserSession {
        // End other sessions for this device if in strict mode
        if ($this->isStrictDeviceMode()) {
            UserSession::endAllForDevice($device, 'new_session');
        }

        return UserSession::createForUser(
            $user,
            $device,
            $clientType,
            null, // Access token ID will be set later
            array_merge($sessionData, [
                'device_bound' => true,
                'security_level' => 'high',
            ])
        );
    }

    /**
     * Track session activity and update last activity
     */
    public function trackActivity(UserSession $session, Request $request): void
    {
        try {
            $locationData = $this->extractLocationData($request);
            $session->updateActivity($locationData);

            // Validate security
            $securityIssues = $this->validateSessionSecurity($session, $request);
            
            if (!empty($securityIssues)) {
                Log::info('Session security issues detected during activity tracking', [
                    'session_id' => $session->session_id,
                    'issues_count' => count($securityIssues),
                    'high_severity_issues' => array_filter($securityIssues, fn($issue) => $issue['severity'] === 'high'),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to track session activity', [
                'session_id' => $session->session_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get current session for authenticated user
     */
    private function getCurrentSession(User $user): ?UserSession
    {
        $accessToken = $user->currentAccessToken();
        
        if (!$accessToken) {
            return null;
        }

        return UserSession::where('access_token_id', $accessToken->id)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Check if session is current session
     */
    private function isCurrentSession(UserSession $session): bool
    {
        $currentUser = auth()->user();
        
        if (!$currentUser) {
            return false;
        }

        $currentToken = $currentUser->currentAccessToken();
        
        return $currentToken && $session->access_token_id === $currentToken->id;
    }

    /**
     * Calculate user security score based on sessions
     */
    private function calculateUserSecurityScore(User $user): int
    {
        $sessions = UserSession::forUser($user)->active()->get();
        
        if ($sessions->isEmpty()) {
            return 100;
        }

        $totalScore = $sessions->sum('security_score');
        return (int) round($totalScore / $sessions->count());
    }

    /**
     * Get maximum sessions per user
     */
    private function getMaxSessionsPerUser(User $user): int
    {
        return config('api.device_binding.max_devices_per_user', 3);
    }

    /**
     * Check if device binding is in strict mode
     */
    private function isStrictDeviceMode(): bool
    {
        return config('api.device_binding.enabled', true);
    }

    /**
     * Extract location data from request
     */
    private function extractLocationData(Request $request): array
    {
        return [
            'country' => $request->header('CF-IPCountry') ?? $request->input('country'),
            'city' => $request->input('city'),
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
        ];
    }

    /**
     * Check for location anomalies
     */
    private function hasLocationAnomalies(UserSession $session, Request $request): bool
    {
        // Simplified check - in production, implement proper geolocation logic
        $currentCountry = $request->header('CF-IPCountry');
        
        if (!$currentCountry || !$session->location_country) {
            return false;
        }

        // Flag if country changes within short time period
        if ($session->location_country !== $currentCountry) {
            $timeDiff = $session->last_activity_at->diffInMinutes(now());
            return $timeDiff < 60; // Less than 1 hour for country change
        }

        return false;
    }
}