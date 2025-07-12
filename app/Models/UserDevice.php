<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class UserDevice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'device_id',
        'device_name',
        'device_type',
        'platform',
        'os_version',
        'browser_name',
        'browser_version',
        'user_agent',
        'ip_address',
        'mac_address',
        'device_specs',
        'device_fingerprint',
        'push_token',
        'is_active',
        'is_primary',
        'status',
        'first_login_at',
        'last_login_at',
        'last_activity_at',
        'verified_at',
    ];

    protected $casts = [
        'device_specs' => 'array',
        'is_active' => 'boolean',
        'is_primary' => 'boolean',
        'first_login_at' => 'datetime',
        'last_login_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($device) {
            if (!$device->first_login_at) {
                $device->first_login_at = Carbon::now();
            }
        });
    }

    /**
     * Relationship dengan User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate device fingerprint dari device info
     */
    public static function generateFingerprint(array $deviceInfo): string
    {
        $data = [
            $deviceInfo['device_id'] ?? '',
            $deviceInfo['platform'] ?? '',
            $deviceInfo['os_version'] ?? '',
            $deviceInfo['browser_name'] ?? '',
            $deviceInfo['user_agent'] ?? '',
        ];

        return hash('sha256', implode('|', $data));
    }

    /**
     * Check if device is bound to user
     */
    public static function isDeviceBound(int $userId, string $deviceFingerprint): bool
    {
        return self::where('user_id', $userId)
            ->where('device_fingerprint', $deviceFingerprint)
            ->where('status', 'active')
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Bind new device to user (STRICT mode - only one device per user)
     */
    public static function bindDevice(int $userId, array $deviceInfo): self
    {
        // In STRICT mode, deactivate all existing devices for this user
        self::where('user_id', $userId)->update([
            'is_active' => false,
            'status' => 'suspended'
        ]);

        // Create new device binding
        return self::create([
            'user_id' => $userId,
            'device_id' => $deviceInfo['device_id'],
            'device_name' => $deviceInfo['device_name'] ?? null,
            'device_type' => $deviceInfo['device_type'] ?? 'mobile',
            'platform' => $deviceInfo['platform'] ?? 'unknown',
            'os_version' => $deviceInfo['os_version'] ?? null,
            'browser_name' => $deviceInfo['browser_name'] ?? null,
            'browser_version' => $deviceInfo['browser_version'] ?? null,
            'user_agent' => $deviceInfo['user_agent'] ?? null,
            'ip_address' => $deviceInfo['ip_address'] ?? null,
            'mac_address' => $deviceInfo['mac_address'] ?? null,
            'device_specs' => $deviceInfo['device_specs'] ?? null,
            'device_fingerprint' => self::generateFingerprint($deviceInfo),
            'push_token' => $deviceInfo['push_token'] ?? null,
            'is_active' => true,
            'is_primary' => true,
            'status' => 'active',
            'first_login_at' => Carbon::now(),
            'last_login_at' => Carbon::now(),
            'last_activity_at' => Carbon::now(),
        ]);
    }

    /**
     * Update device activity
     */
    public function updateActivity(): void
    {
        $this->update([
            'last_login_at' => Carbon::now(),
            'last_activity_at' => Carbon::now(),
        ]);
    }

    /**
     * Verify device (admin approval)
     */
    public function verify(): void
    {
        $this->update([
            'verified_at' => Carbon::now(),
            'status' => 'active',
        ]);
    }

    /**
     * Revoke device access
     */
    public function revoke(): void
    {
        $this->update([
            'is_active' => false,
            'status' => 'revoked',
        ]);
    }

    /**
     * Check if device is trusted (verified by admin)
     */
    public function isTrusted(): bool
    {
        return !is_null($this->verified_at) && $this->status === 'active';
    }

    /**
     * Get formatted device info
     */
    public function getFormattedDeviceInfoAttribute(): string
    {
        $parts = array_filter([
            $this->device_name,
            $this->platform,
            $this->os_version,
        ]);

        return implode(' - ', $parts) ?: 'Unknown Device';
    }

    /**
     * Get device status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'active' => 'success',
            'suspended' => 'warning',
            'revoked' => 'danger',
            default => 'gray',
        };
    }

    /**
     * Scope untuk device aktif
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('status', 'active');
    }

    /**
     * Scope untuk device terverifikasi
     */
    public function scopeVerified($query)
    {
        return $query->whereNotNull('verified_at');
    }

    /**
     * Scope untuk device primary
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Get user's active device
     */
    public static function getUserActiveDevice(int $userId): ?self
    {
        return self::where('user_id', $userId)
            ->active()
            ->primary()
            ->first();
    }

    /**
     * Extract device info from request
     */
    public static function extractDeviceInfo(\Illuminate\Http\Request $request): array
    {
        $userAgent = $request->userAgent();
        
        return [
            'device_id' => $request->header('X-Device-ID') ?? $request->input('device_id'),
            'device_name' => $request->header('X-Device-Name') ?? $request->input('device_name'),
            'device_type' => $request->header('X-Device-Type') ?? $request->input('device_type', 'mobile'),
            'platform' => $request->header('X-Platform') ?? $request->input('platform', 'unknown'),
            'os_version' => $request->header('X-OS-Version') ?? $request->input('os_version'),
            'browser_name' => $request->header('X-Browser-Name') ?? $request->input('browser_name'),
            'browser_version' => $request->header('X-Browser-Version') ?? $request->input('browser_version'),
            'user_agent' => $userAgent,
            'ip_address' => $request->ip(),
            'mac_address' => $request->header('X-MAC-Address') ?? $request->input('mac_address'),
            'device_specs' => $request->input('device_specs'),
            'push_token' => $request->header('X-Push-Token') ?? $request->input('push_token'),
        ];
    }
}