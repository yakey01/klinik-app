<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Carbon\Carbon;

class RefreshToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_device_id',
        'token_hash',
        'client_type',
        'scopes',
        'expires_at',
        'last_used_at',
        'last_used_ip',
        'user_agent',
        'is_revoked',
        'revoked_at',
        'revoked_reason',
        'metadata',
    ];

    protected $casts = [
        'scopes' => 'array',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'revoked_at' => 'datetime',
        'is_revoked' => 'boolean',
        'metadata' => 'array',
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
     * Generate a new refresh token
     */
    public static function generateToken(): string
    {
        return Str::random(60);
    }

    /**
     * Hash the refresh token
     */
    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Create a new refresh token for user
     */
    public static function createForUser(
        User $user, 
        ?UserDevice $device = null, 
        string $clientType = 'mobile_app',
        array $scopes = [],
        array $metadata = []
    ): array {
        $token = self::generateToken();
        $tokenHash = self::hashToken($token);

        // Get token configuration
        $config = config("api.token_types.{$clientType}", config('api.token_types.mobile_app'));
        $expiresAt = Carbon::now()->addMinutes($config['expires_in']);

        $refreshToken = self::create([
            'user_id' => $user->id,
            'user_device_id' => $device?->id,
            'token_hash' => $tokenHash,
            'client_type' => $clientType,
            'scopes' => empty($scopes) ? $config['scopes'] : $scopes,
            'expires_at' => $expiresAt,
            'last_used_at' => now(),
            'last_used_ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'metadata' => $metadata,
        ]);

        return [
            'refresh_token' => $token,
            'model' => $refreshToken,
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * Find refresh token by token string
     */
    public static function findByToken(string $token): ?self
    {
        $tokenHash = self::hashToken($token);
        
        return self::where('token_hash', $tokenHash)
            ->where('is_revoked', false)
            ->where('expires_at', '>', now())
            ->first();
    }

    /**
     * Use the refresh token (update last_used_at)
     */
    public function use(?string $ipAddress = null, ?string $userAgent = null): void
    {
        $this->update([
            'last_used_at' => now(),
            'last_used_ip' => $ipAddress ?? request()?->ip(),
            'user_agent' => $userAgent ?? request()?->userAgent(),
        ]);
    }

    /**
     * Revoke the refresh token
     */
    public function revoke(string $reason = 'manual'): void
    {
        $this->update([
            'is_revoked' => true,
            'revoked_at' => now(),
            'revoked_reason' => $reason,
        ]);
    }

    /**
     * Check if token is valid
     */
    public function isValid(): bool
    {
        return !$this->is_revoked && 
               $this->expires_at && 
               $this->expires_at->isFuture();
    }

    /**
     * Check if token is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if token can be refreshed
     */
    public function canRefresh(): bool
    {
        $config = config("api.token_types.{$this->client_type}", config('api.token_types.mobile_app'));
        return $config['can_refresh'] ?? false;
    }

    /**
     * Get token scopes
     */
    public function getScopes(): array
    {
        return $this->scopes ?? [];
    }

    /**
     * Check if token has specific scope
     */
    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->getScopes());
    }

    /**
     * Revoke all refresh tokens for user
     */
    public static function revokeAllForUser(User $user, string $reason = 'logout_all'): int
    {
        return self::where('user_id', $user->id)
            ->where('is_revoked', false)
            ->update([
                'is_revoked' => true,
                'revoked_at' => now(),
                'revoked_reason' => $reason,
            ]);
    }

    /**
     * Revoke all refresh tokens for device
     */
    public static function revokeAllForDevice(UserDevice $device, string $reason = 'device_revoked'): int
    {
        return self::where('user_device_id', $device->id)
            ->where('is_revoked', false)
            ->update([
                'is_revoked' => true,
                'revoked_at' => now(),
                'revoked_reason' => $reason,
            ]);
    }

    /**
     * Clean up expired tokens
     */
    public static function cleanupExpired(): int
    {
        return self::where('expires_at', '<', now())
            ->where('is_revoked', false)
            ->update([
                'is_revoked' => true,
                'revoked_at' => now(),
                'revoked_reason' => 'expired',
            ]);
    }

    /**
     * Scope for active tokens
     */
    public function scopeActive($query)
    {
        return $query->where('is_revoked', false)
            ->where('expires_at', '>', now());
    }

    /**
     * Scope for specific client type
     */
    public function scopeClientType($query, string $clientType)
    {
        return $query->where('client_type', $clientType);
    }

    /**
     * Scope for user tokens
     */
    public function scopeForUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * Get formatted expiration time
     */
    public function getFormattedExpiresAtAttribute(): string
    {
        return $this->expires_at ? $this->expires_at->format('Y-m-d H:i:s') : 'Never';
    }

    /**
     * Get time until expiration
     */
    public function getTimeUntilExpirationAttribute(): ?string
    {
        if (!$this->expires_at) {
            return null;
        }

        return $this->expires_at->diffForHumans();
    }

    /**
     * Get token status
     */
    public function getStatusAttribute(): string
    {
        if ($this->is_revoked) {
            return 'revoked';
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
            'revoked' => 'danger',
            default => 'gray',
        };
    }
}