<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserDevice;
use App\Models\RefreshToken;
use App\Models\UserSession;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TokenService
{
    /**
     * Create authentication tokens for user
     */
    public function createAuthTokens(
        User $user, 
        ?UserDevice $device = null, 
        string $clientType = 'mobile_app',
        array $scopes = [],
        array $sessionData = [],
        array $locationData = []
    ): array {
        try {
            // Get token configuration
            $config = config("api.token_types.{$clientType}", config('api.token_types.mobile_app'));
            
            // Create access token using Sanctum
            $tokenScopes = empty($scopes) ? $config['scopes'] : $scopes;
            $accessToken = $user->createToken(
                "auth-token-{$clientType}",
                $tokenScopes,
                Carbon::now()->addMinutes($config['expires_in'])
            );

            // Create refresh token if allowed
            $refreshTokenData = null;
            if ($config['can_refresh']) {
                $refreshTokenData = RefreshToken::createForUser(
                    $user, 
                    $device, 
                    $clientType, 
                    $tokenScopes,
                    ['request_id' => request()?->header('X-Request-ID')]
                );
            }

            // Create user session
            $userSession = UserSession::createForUser(
                $user,
                $device,
                $clientType,
                $accessToken->accessToken->id,
                $sessionData,
                $locationData
            );

            // Update device with refresh token hash if applicable
            if ($device && $refreshTokenData) {
                $device->update([
                    'refresh_token_hash' => RefreshToken::hashToken($refreshTokenData['refresh_token']),
                    'refresh_token_expires_at' => $refreshTokenData['expires_at'],
                    'last_login_at' => now(),
                    'last_activity_at' => now(),
                ]);
            }

            Log::info('Authentication tokens created', [
                'user_id' => $user->id,
                'device_id' => $device?->id,
                'client_type' => $clientType,
                'session_id' => $userSession->session_id,
                'has_refresh_token' => !is_null($refreshTokenData),
            ]);

            return [
                'access_token' => $accessToken->plainTextToken,
                'refresh_token' => $refreshTokenData['refresh_token'] ?? null,
                'token_type' => 'Bearer',
                'expires_in' => $config['expires_in'] * 60, // Convert to seconds
                'expires_at' => Carbon::now()->addMinutes($config['expires_in'])->toISOString(),
                'refresh_expires_at' => $refreshTokenData['expires_at']?->toISOString(),
                'scopes' => $tokenScopes,
                'session_id' => $userSession->session_id,
                'device_id' => $device?->device_id,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create authentication tokens', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Refresh access token using refresh token
     */
    public function refreshAccessToken(string $refreshToken, ?UserDevice $device = null): array
    {
        try {
            // Find and validate refresh token
            $refreshTokenModel = RefreshToken::findByToken($refreshToken);
            
            if (!$refreshTokenModel) {
                throw new \Exception('Invalid or expired refresh token');
            }

            if (!$refreshTokenModel->canRefresh()) {
                throw new \Exception('Refresh token cannot be refreshed');
            }

            $user = $refreshTokenModel->user;

            // Validate device if provided
            if ($device && $refreshTokenModel->user_device_id !== $device->id) {
                throw new \Exception('Device mismatch for refresh token');
            }

            // Mark refresh token as used
            $refreshTokenModel->use();

            // Create new access token
            $config = config("api.token_types.{$refreshTokenModel->client_type}");
            $newAccessToken = $user->createToken(
                "refreshed-token-{$refreshTokenModel->client_type}",
                $refreshTokenModel->getScopes(),
                Carbon::now()->addMinutes($config['expires_in'])
            );

            // Update session with new access token
            $userSession = UserSession::where('user_id', $user->id)
                ->where('user_device_id', $refreshTokenModel->user_device_id)
                ->where('is_active', true)
                ->latest()
                ->first();

            if ($userSession) {
                $userSession->update([
                    'access_token_id' => $newAccessToken->accessToken->id,
                    'last_activity_at' => now(),
                ]);
            }

            Log::info('Access token refreshed', [
                'user_id' => $user->id,
                'refresh_token_id' => $refreshTokenModel->id,
                'device_id' => $device?->id,
                'session_id' => $userSession?->session_id,
            ]);

            return [
                'access_token' => $newAccessToken->plainTextToken,
                'token_type' => 'Bearer',
                'expires_in' => $config['expires_in'] * 60,
                'expires_at' => Carbon::now()->addMinutes($config['expires_in'])->toISOString(),
                'scopes' => $refreshTokenModel->getScopes(),
                'session_id' => $userSession?->session_id,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to refresh access token', [
                'error' => $e->getMessage(),
                'refresh_token_hash' => RefreshToken::hashToken($refreshToken),
            ]);

            throw $e;
        }
    }

    /**
     * Revoke user tokens
     */
    public function revokeTokens(
        User $user, 
        ?UserDevice $device = null, 
        bool $allDevices = false,
        string $reason = 'logout'
    ): array {
        try {
            $revokedTokens = 0;
            $revokedSessions = 0;

            if ($allDevices) {
                // Revoke all tokens for user
                $user->tokens()->delete();
                $revokedTokens = RefreshToken::revokeAllForUser($user, $reason);
                $revokedSessions = UserSession::endAllForUser($user, $reason);
            } else if ($device) {
                // Revoke tokens for specific device
                $user->tokens()
                    ->whereHas('tokenable', function ($query) use ($device) {
                        // This is a simplified approach - in production you might
                        // want to store device association with access tokens
                    })
                    ->delete();
                
                $revokedTokens = RefreshToken::revokeAllForDevice($device, $reason);
                $revokedSessions = UserSession::endAllForDevice($device, $reason);
                
                // Clear device refresh token
                $device->update([
                    'refresh_token_hash' => null,
                    'refresh_token_expires_at' => null,
                ]);
            } else {
                // Revoke current user tokens
                $user->currentAccessToken()?->delete();
                
                // Find and revoke associated refresh tokens (simplified)
                $revokedTokens = RefreshToken::where('user_id', $user->id)
                    ->where('is_revoked', false)
                    ->limit(1)
                    ->update([
                        'is_revoked' => true,
                        'revoked_at' => now(),
                        'revoked_reason' => $reason,
                    ]);
            }

            Log::info('User tokens revoked', [
                'user_id' => $user->id,
                'device_id' => $device?->id,
                'all_devices' => $allDevices,
                'reason' => $reason,
                'revoked_tokens' => $revokedTokens,
                'revoked_sessions' => $revokedSessions,
            ]);

            return [
                'revoked_tokens' => $revokedTokens,
                'revoked_sessions' => $revokedSessions,
                'message' => 'Tokens revoked successfully',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to revoke tokens', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Validate access token
     */
    public function validateAccessToken(string $token): ?PersonalAccessToken
    {
        try {
            $accessToken = PersonalAccessToken::findToken($token);
            
            if (!$accessToken) {
                return null;
            }

            // Check if token is expired
            if ($accessToken->expires_at && $accessToken->expires_at->isPast()) {
                return null;
            }

            // Update last used timestamp
            $accessToken->forceFill(['last_used_at' => now()])->save();

            return $accessToken;
        } catch (\Exception $e) {
            Log::error('Failed to validate access token', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get active sessions for user
     */
    public function getActiveSessions(User $user): array
    {
        $sessions = UserSession::forUser($user)
            ->active()
            ->with('userDevice')
            ->orderByDesc('last_activity_at')
            ->get();

        return $sessions->map(function ($session) {
            return [
                'session_id' => $session->session_id,
                'device' => [
                    'id' => $session->userDevice?->device_id,
                    'name' => $session->userDevice?->formatted_device_info,
                    'type' => $session->userDevice?->device_type,
                    'platform' => $session->userDevice?->platform,
                ],
                'client_type' => $session->client_type,
                'started_at' => $session->started_at?->toISOString(),
                'last_activity_at' => $session->last_activity_at?->toISOString(),
                'location' => $session->formatted_location,
                'ip_address' => $session->ip_address,
                'is_current' => $session->access_token_id === auth()->user()?->currentAccessToken()?->id,
                'security_score' => $session->security_score,
                'has_suspicious_activity' => $session->hasSuspiciousActivity(),
            ];
        })->toArray();
    }

    /**
     * Get refresh tokens for user
     */
    public function getRefreshTokens(User $user): array
    {
        $tokens = RefreshToken::forUser($user)
            ->active()
            ->with('userDevice')
            ->orderByDesc('last_used_at')
            ->get();

        return $tokens->map(function ($token) {
            return [
                'id' => $token->id,
                'client_type' => $token->client_type,
                'device' => [
                    'id' => $token->userDevice?->device_id,
                    'name' => $token->userDevice?->formatted_device_info,
                ],
                'scopes' => $token->getScopes(),
                'created_at' => $token->created_at?->toISOString(),
                'expires_at' => $token->expires_at?->toISOString(),
                'last_used_at' => $token->last_used_at?->toISOString(),
                'time_until_expiration' => $token->time_until_expiration,
                'status' => $token->status,
            ];
        })->toArray();
    }

    /**
     * Clean up expired tokens
     */
    public function cleanupExpiredTokens(): array
    {
        try {
            // Clean up expired access tokens
            $expiredAccessTokens = PersonalAccessToken::where('expires_at', '<', now())->count();
            PersonalAccessToken::where('expires_at', '<', now())->delete();

            // Clean up expired refresh tokens
            $expiredRefreshTokens = RefreshToken::cleanupExpired();

            // Clean up expired sessions
            $expiredSessions = UserSession::cleanupExpired();

            Log::info('Token cleanup completed', [
                'expired_access_tokens' => $expiredAccessTokens,
                'expired_refresh_tokens' => $expiredRefreshTokens,
                'expired_sessions' => $expiredSessions,
            ]);

            return [
                'expired_access_tokens' => $expiredAccessTokens,
                'expired_refresh_tokens' => $expiredRefreshTokens,
                'expired_sessions' => $expiredSessions,
                'message' => 'Token cleanup completed successfully',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to cleanup expired tokens', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Force logout all sessions for security reasons
     */
    public function forceLogoutAllSessions(User $user, string $reason = 'security'): int
    {
        try {
            // Revoke all access tokens
            $user->tokens()->delete();

            // Revoke all refresh tokens
            RefreshToken::revokeAllForUser($user, $reason);

            // Force logout all sessions
            $loggedOutSessions = UserSession::forceLogoutAllForUser($user, $reason);

            // Clear device refresh tokens
            UserDevice::where('user_id', $user->id)->update([
                'refresh_token_hash' => null,
                'refresh_token_expires_at' => null,
            ]);

            Log::warning('Force logout all sessions', [
                'user_id' => $user->id,
                'reason' => $reason,
                'logged_out_sessions' => $loggedOutSessions,
            ]);

            return $loggedOutSessions;
        } catch (\Exception $e) {
            Log::error('Failed to force logout all sessions', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Check if token needs refresh
     */
    public function needsRefresh(PersonalAccessToken $accessToken, int $refreshThresholdMinutes = 30): bool
    {
        if (!$accessToken->expires_at) {
            return false;
        }

        return $accessToken->expires_at->diffInMinutes(now()) <= $refreshThresholdMinutes;
    }

    /**
     * Get token security metadata
     */
    public function getTokenSecurityMetadata(PersonalAccessToken $accessToken): array
    {
        $user = $accessToken->tokenable;
        $currentSession = UserSession::where('access_token_id', $accessToken->id)
            ->where('is_active', true)
            ->first();

        return [
            'token_id' => $accessToken->id,
            'created_at' => $accessToken->created_at?->toISOString(),
            'expires_at' => $accessToken->expires_at?->toISOString(),
            'last_used_at' => $accessToken->last_used_at?->toISOString(),
            'abilities' => $accessToken->abilities,
            'session' => $currentSession ? [
                'session_id' => $currentSession->session_id,
                'security_score' => $currentSession->security_score,
                'suspicious_activity' => $currentSession->hasSuspiciousActivity(),
                'security_flags' => $currentSession->security_flags,
            ] : null,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'role' => $user->role?->name,
            ],
        ];
    }
}