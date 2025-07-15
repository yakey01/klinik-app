<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Carbon\Carbon;

class UserSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_device_id',
        'session_id',
        'access_token_id',
        'client_type',
        'session_data',
        'started_at',
        'last_activity_at',
        'expires_at',
        'ip_address',
        'user_agent',
        'location_country',
        'location_city',
        'location_latitude',
        'location_longitude',
        'is_active',
        'force_logout',
        'ended_at',
        'ended_reason',
        'security_flags',
    ];

    protected $casts = [
        'session_data' => 'array',
        'started_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'expires_at' => 'datetime',
        'ended_at' => 'datetime',
        'is_active' => 'boolean',
        'force_logout' => 'boolean',
        'security_flags' => 'array',
        'location_latitude' => 'decimal:8',
        'location_longitude' => 'decimal:8',
    ];

    /**
     * Relationship with User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship with UserDevice
     */
    public function userDevice(): BelongsTo
    {
        return $this->belongsTo(UserDevice::class);
    }

    /**
     * Generate a new session ID
     */
    public static function generateSessionId(): string
    {
        return Str::uuid()->toString();
    }

    /**
     * Create a new session for user
     */
    public static function createForUser(
        User $user,
        ?UserDevice $device = null,
        string $clientType = 'mobile_app',
        ?string $accessTokenId = null,
        array $sessionData = [],
        array $locationData = []
    ): self {
        $sessionId = self::generateSessionId();
        
        // Set default expiration based on client type
        $config = config("api.token_types.{$clientType}", config('api.token_types.mobile_app'));
        $expiresAt = $config['expires_in'] ? Carbon::now()->addMinutes($config['expires_in']) : null;

        return self::create([
            'user_id' => $user->id,
            'user_device_id' => $device?->id,
            'session_id' => $sessionId,
            'access_token_id' => $accessTokenId,
            'client_type' => $clientType,
            'session_data' => $sessionData,
            'started_at' => now(),
            'last_activity_at' => now(),
            'expires_at' => $expiresAt,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'location_country' => $locationData['country'] ?? null,
            'location_city' => $locationData['city'] ?? null,
            'location_latitude' => $locationData['latitude'] ?? null,
            'location_longitude' => $locationData['longitude'] ?? null,
            'is_active' => true,
        ]);
    }

    /**
     * Find session by session ID
     */
    public static function findBySessionId(string $sessionId): ?self
    {
        return self::where('session_id', $sessionId)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Update session activity
     */
    public function updateActivity(?array $locationData = null): void
    {
        $updateData = [
            'last_activity_at' => now(),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ];

        if ($locationData) {
            $updateData = array_merge($updateData, [
                'location_country' => $locationData['country'] ?? $this->location_country,
                'location_city' => $locationData['city'] ?? $this->location_city,
                'location_latitude' => $locationData['latitude'] ?? $this->location_latitude,
                'location_longitude' => $locationData['longitude'] ?? $this->location_longitude,
            ]);
        }

        $this->update($updateData);
    }

    /**
     * End the session
     */
    public function end(string $reason = 'manual'): void
    {
        $this->update([
            'is_active' => false,
            'ended_at' => now(),
            'ended_reason' => $reason,
        ]);
    }

    /**
     * Force logout for security reasons
     */
    public function forceLogout(string $reason = 'security'): void
    {
        $this->update([
            'force_logout' => true,
            'is_active' => false,
            'ended_at' => now(),
            'ended_reason' => $reason,
        ]);
    }

    /**
     * Check if session is valid
     */
    public function isValid(): bool
    {
        if (!$this->is_active || $this->force_logout) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Extend session expiration
     */
    public function extendExpiration(int $minutes = null): void
    {
        if (!$minutes) {
            $config = config("api.token_types.{$this->client_type}", config('api.token_types.web_app'));
            $minutes = $config['expires_in'];
        }

        $this->update([
            'expires_at' => Carbon::now()->addMinutes($minutes),
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Check if session is about to expire
     */
    public function isAboutToExpire(int $thresholdMinutes = 60): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return $this->expires_at->diffInMinutes(now()) <= $thresholdMinutes;
    }

    /**
     * Check if session is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Add security flag
     */
    public function addSecurityFlag(string $flag, mixed $data = null): void
    {
        $securityFlags = $this->security_flags ?? [];
        $securityFlags[$flag] = [
            'flagged_at' => now()->toISOString(),
            'data' => $data,
        ];

        $this->update(['security_flags' => $securityFlags]);
    }

    /**
     * Check if session has security flag
     */
    public function hasSecurityFlag(string $flag): bool
    {
        return isset($this->security_flags[$flag]);
    }

    /**
     * Update session data
     */
    public function updateSessionData(array $data): void
    {
        $sessionData = $this->session_data ?? [];
        $sessionData = array_merge($sessionData, $data);
        
        $this->update(['session_data' => $sessionData]);
    }

    /**
     * Get session data value
     */
    public function getSessionData(string $key, mixed $default = null): mixed
    {
        return data_get($this->session_data, $key, $default);
    }

    /**
     * End all sessions for user
     */
    public static function endAllForUser(User $user, string $reason = 'logout_all'): int
    {
        return self::where('user_id', $user->id)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'ended_at' => now(),
                'ended_reason' => $reason,
            ]);
    }

    /**
     * End all sessions for device
     */
    public static function endAllForDevice(UserDevice $device, string $reason = 'device_revoked'): int
    {
        return self::where('user_device_id', $device->id)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'ended_at' => now(),
                'ended_reason' => $reason,
            ]);
    }

    /**
     * Force logout all sessions for user
     */
    public static function forceLogoutAllForUser(User $user, string $reason = 'security'): int
    {
        return self::where('user_id', $user->id)
            ->where('is_active', true)
            ->update([
                'force_logout' => true,
                'is_active' => false,
                'ended_at' => now(),
                'ended_reason' => $reason,
            ]);
    }

    /**
     * Clean up expired sessions
     */
    public static function cleanupExpired(): int
    {
        return self::where('expires_at', '<', now())
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'ended_at' => now(),
                'ended_reason' => 'expired',
            ]);
    }

    /**
     * Scope for active sessions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('force_logout', false)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope for specific client type
     */
    public function scopeClientType($query, string $clientType)
    {
        return $query->where('client_type', $clientType);
    }

    /**
     * Scope for user sessions
     */
    public function scopeForUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * Scope for recent activity
     */
    public function scopeRecentActivity($query, int $minutes = 30)
    {
        return $query->where('last_activity_at', '>', now()->subMinutes($minutes));
    }

    /**
     * Get session duration
     */
    public function getDurationAttribute(): ?string
    {
        if (!$this->started_at) {
            return null;
        }

        $endTime = $this->ended_at ?? now();
        return $this->started_at->diffForHumans($endTime, true);
    }

    /**
     * Get formatted location
     */
    public function getFormattedLocationAttribute(): string
    {
        $parts = array_filter([$this->location_city, $this->location_country]);
        return implode(', ', $parts) ?: 'Unknown';
    }

    /**
     * Get session status
     */
    public function getStatusAttribute(): string
    {
        if ($this->force_logout) {
            return 'force_logout';
        }

        if (!$this->is_active) {
            return 'ended';
        }

        if ($this->isExpired()) {
            return 'expired';
        }

        return 'active';
    }

    /**
     * Get status color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'active' => 'success',
            'expired' => 'warning',
            'ended' => 'gray',
            'force_logout' => 'danger',
            default => 'gray',
        };
    }

    /**
     * Check if session has suspicious activity
     */
    public function hasSuspiciousActivity(): bool
    {
        return !empty($this->security_flags);
    }

    /**
     * Get security score (0-100)
     */
    public function getSecurityScoreAttribute(): int
    {
        $score = 100;
        $flags = $this->security_flags ?? [];

        // Deduct points for security issues
        foreach ($flags as $flag => $data) {
            $score -= match($flag) {
                'location_anomaly' => 20,
                'device_change' => 15,
                'ip_change' => 10,
                'suspicious_activity' => 25,
                'failed_biometric' => 30,
                default => 5,
            };
        }

        return max(0, $score);
    }
}