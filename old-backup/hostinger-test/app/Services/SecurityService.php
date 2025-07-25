<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserSession;
use App\Models\AuditLog;
use App\Models\TwoFactorAuth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;

class SecurityService
{
    private TwoFactorAuthService $twoFactorService;

    public function __construct(TwoFactorAuthService $twoFactorService)
    {
        $this->twoFactorService = $twoFactorService;
    }

    /**
     * Authenticate user with security checks
     */
    public function authenticate(Request $request, string $email, string $password): array
    {
        $rateLimitKey = 'login_attempts:' . $request->ip();
        $maxAttempts = 5;
        $decayMinutes = 15;

        // Check rate limiting
        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            
            AuditLog::logSecurity(
                'login_rate_limited',
                null,
                'Login rate limit exceeded',
                [
                    'ip_address' => $request->ip(),
                    'email' => $email,
                    'retry_after' => $seconds,
                ]
            );
            
            return [
                'success' => false,
                'message' => 'Too many login attempts. Please try again in ' . ceil($seconds / 60) . ' minutes.',
                'retry_after' => $seconds,
            ];
        }

        // Find user
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            RateLimiter::hit($rateLimitKey, $decayMinutes * 60);
            
            AuditLog::logSecurity(
                'login_failed',
                $user,
                'Failed login attempt',
                [
                    'ip_address' => $request->ip(),
                    'email' => $email,
                    'user_agent' => $request->userAgent(),
                ]
            );
            
            return [
                'success' => false,
                'message' => 'Invalid credentials.',
            ];
        }

        // Check if user is active
        if (!$user->is_active) {
            AuditLog::logSecurity(
                'login_inactive_user',
                $user,
                'Login attempt by inactive user',
                [
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]
            );
            
            return [
                'success' => false,
                'message' => 'Account is inactive.',
            ];
        }

        // Check for suspicious activity
        $suspiciousActivity = $this->checkSuspiciousActivity($request, $user);
        
        // Check if 2FA is required
        $requires2FA = $this->twoFactorService->isEnabled($user) || 
                      $this->twoFactorService->isRequiredForUser($user);

        // Clear rate limiting on successful authentication
        RateLimiter::clear($rateLimitKey);

        // Log successful authentication
        AuditLog::logAuth(
            'login_success',
            $user,
            'User logged in successfully',
            [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'requires_2fa' => $requires2FA,
                'suspicious_activity' => $suspiciousActivity,
            ]
        );

        return [
            'success' => true,
            'user' => $user,
            'requires_2fa' => $requires2FA,
            'suspicious_activity' => $suspiciousActivity,
        ];
    }

    /**
     * Create secure session for user
     */
    public function createSession(Request $request, User $user): UserSession
    {
        $agent = new Agent();
        $agent->setUserAgent($request->userAgent());
        
        // Get location data (placeholder - implement with actual geolocation service)
        $locationData = $this->getLocationFromIP($request->ip());
        
        // Create session
        $session = UserSession::create([
            'user_id' => $user->id,
            'session_id' => UserSession::generateSessionId(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_type' => $agent->isMobile() ? 'mobile' : ($agent->isTablet() ? 'tablet' : 'desktop'),
            'device_name' => $agent->device() ?: 'Unknown',
            'browser' => $agent->browser() . ' ' . $agent->version($agent->browser()),
            'platform' => $agent->platform() . ' ' . $agent->version($agent->platform()),
            'location' => $locationData,
            'is_current' => true,
            'last_activity' => now(),
            'expires_at' => now()->addMinutes(config('session.lifetime', 120)),
        ]);

        // Update user's last login
        $user->update(['last_login_at' => now()]);

        return $session;
    }

    /**
     * Check for suspicious activity
     */
    private function checkSuspiciousActivity(Request $request, User $user): array
    {
        $suspiciousActivity = [];

        // Check for unusual location
        if ($this->isUnusualLocation($request, $user)) {
            $suspiciousActivity[] = 'unusual_location';
        }

        // Check for unusual device
        if ($this->isUnusualDevice($request, $user)) {
            $suspiciousActivity[] = 'unusual_device';
        }

        // Check for unusual time
        if ($this->isUnusualTime($request, $user)) {
            $suspiciousActivity[] = 'unusual_time';
        }

        return $suspiciousActivity;
    }

    /**
     * Check if login is from unusual location
     */
    private function isUnusualLocation(Request $request, User $user): bool
    {
        $currentIP = $request->ip();
        
        // Skip check for local IPs
        if (in_array($currentIP, ['127.0.0.1', '::1']) || 
            preg_match('/^192\.168\./', $currentIP) ||
            preg_match('/^10\./', $currentIP)) {
            return false;
        }

        // Get recent sessions from last 30 days
        $recentSessions = UserSession::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->pluck('ip_address')
            ->unique()
            ->toArray();

        // If no recent sessions, consider it suspicious
        if (empty($recentSessions)) {
            return true;
        }

        // Check if current IP is in recent sessions
        return !in_array($currentIP, $recentSessions);
    }

    /**
     * Check if login is from unusual device
     */
    private function isUnusualDevice(Request $request, User $user): bool
    {
        $currentUserAgent = $request->userAgent();
        
        // Get recent user agents from last 30 days
        $recentUserAgents = UserSession::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->pluck('user_agent')
            ->unique()
            ->toArray();

        // If no recent sessions, consider it suspicious
        if (empty($recentUserAgents)) {
            return true;
        }

        // Check if current user agent is in recent sessions
        return !in_array($currentUserAgent, $recentUserAgents);
    }

    /**
     * Check if login is at unusual time
     */
    private function isUnusualTime(Request $request, User $user): bool
    {
        $currentHour = now()->hour;
        
        // Get user's typical login hours from last 30 days
        $recentLoginHours = UserSession::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->get()
            ->pluck('created_at')
            ->map(fn($date) => $date->hour)
            ->countBy()
            ->toArray();

        // If no recent logins, not suspicious
        if (empty($recentLoginHours)) {
            return false;
        }

        // Check if current hour is in user's typical login hours
        // Consider it suspicious if user never logged in at this hour
        return !isset($recentLoginHours[$currentHour]);
    }

    /**
     * Get location data from IP address
     */
    private function getLocationFromIP(string $ip): ?array
    {
        // Skip for local IPs
        if (in_array($ip, ['127.0.0.1', '::1']) || 
            preg_match('/^192\.168\./', $ip) ||
            preg_match('/^10\./', $ip)) {
            return ['country' => 'Local', 'city' => 'Local'];
        }

        // Implement actual geolocation service here
        // For now, return null
        return null;
    }

    /**
     * Terminate user session
     */
    public function terminateSession(string $sessionId, string $reason = 'manual'): bool
    {
        $session = UserSession::where('session_id', $sessionId)->first();
        
        if (!$session) {
            return false;
        }

        $session->terminate();
        
        AuditLog::logSecurity(
            'session_terminated',
            $session->user,
            'User session terminated',
            [
                'session_id' => $sessionId,
                'reason' => $reason,
                'ip_address' => $session->ip_address,
            ]
        );

        return true;
    }

    /**
     * Terminate all user sessions except current
     */
    public function terminateAllSessions(User $user, string $currentSessionId = null): int
    {
        $query = UserSession::where('user_id', $user->id)
            ->where('is_active', true);

        if ($currentSessionId) {
            $query->where('session_id', '!=', $currentSessionId);
        }

        $sessions = $query->get();
        
        foreach ($sessions as $session) {
            $session->terminate();
        }

        $count = $sessions->count();

        AuditLog::logSecurity(
            'all_sessions_terminated',
            $user,
            'All user sessions terminated',
            [
                'terminated_count' => $count,
                'current_session_id' => $currentSessionId,
            ]
        );

        return $count;
    }

    /**
     * Get user's active sessions
     */
    public function getActiveSessions(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return UserSession::where('user_id', $user->id)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->orderBy('last_activity', 'desc')
            ->get();
    }

    /**
     * Get security dashboard stats
     */
    public function getSecurityStats(): array
    {
        $now = now();
        $last24Hours = $now->subHours(24);
        $lastWeek = $now->subWeek();

        return [
            'failed_logins_24h' => AuditLog::where('action', 'login_failed')
                ->where('created_at', '>=', $last24Hours)
                ->count(),
            'successful_logins_24h' => AuditLog::where('action', 'login_success')
                ->where('created_at', '>=', $last24Hours)
                ->count(),
            'active_sessions' => UserSession::where('is_active', true)
                ->where('expires_at', '>', $now)
                ->count(),
            'suspicious_activities_week' => AuditLog::where('action', 'LIKE', '%suspicious%')
                ->where('created_at', '>=', $lastWeek)
                ->count(),
            'two_factor_enabled_users' => TwoFactorAuth::where('enabled', true)->count(),
            'total_users' => User::count(),
        ];
    }

    /**
     * Change user password with security checks
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): array
    {
        // Verify current password
        if (!Hash::check($currentPassword, $user->password)) {
            AuditLog::logSecurity(
                'password_change_failed',
                $user,
                'Failed password change attempt - incorrect current password',
                ['user_id' => $user->id]
            );
            
            return [
                'success' => false,
                'message' => 'Current password is incorrect.',
            ];
        }

        // Update password
        $user->update(['password' => Hash::make($newPassword)]);

        // Terminate all other sessions
        $this->terminateAllSessions($user);

        AuditLog::logSecurity(
            'password_changed',
            $user,
            'User password changed successfully',
            ['user_id' => $user->id]
        );

        return [
            'success' => true,
            'message' => 'Password changed successfully. All other sessions have been terminated.',
        ];
    }

    /**
     * Lock user account
     */
    public function lockAccount(User $user, string $reason = 'security'): void
    {
        $user->update(['is_active' => false]);
        
        // Terminate all sessions
        $this->terminateAllSessions($user);

        AuditLog::logSecurity(
            'account_locked',
            $user,
            'User account locked',
            [
                'user_id' => $user->id,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Unlock user account
     */
    public function unlockAccount(User $user, User $admin): void
    {
        $user->update(['is_active' => true]);

        AuditLog::logSecurity(
            'account_unlocked',
            $admin,
            'User account unlocked by admin',
            [
                'target_user_id' => $user->id,
                'admin_id' => $admin->id,
            ]
        );
    }
}