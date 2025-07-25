<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;

class BiometricTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_device_id',
        'template_id',
        'biometric_type',
        'template_data',
        'template_hash',
        'template_metadata',
        'algorithm_version',
        'is_primary',
        'is_active',
        'enrolled_at',
        'last_verified_at',
        'verification_count',
        'failed_attempts',
        'last_failed_at',
        'is_compromised',
        'compromised_at',
        'compromised_reason',
        'security_metadata',
    ];

    protected $casts = [
        'template_metadata' => 'array',
        'security_metadata' => 'array',
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'is_compromised' => 'boolean',
        'enrolled_at' => 'datetime',
        'last_verified_at' => 'datetime',
        'last_failed_at' => 'datetime',
        'compromised_at' => 'datetime',
    ];

    // Available biometric types
    public const TYPE_FINGERPRINT = 'fingerprint';
    public const TYPE_FACE = 'face';
    public const TYPE_VOICE = 'voice';
    public const TYPE_IRIS = 'iris';

    public const TYPES = [
        self::TYPE_FINGERPRINT,
        self::TYPE_FACE,
        self::TYPE_VOICE,
        self::TYPE_IRIS,
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
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($template) {
            if (!$template->template_id) {
                $template->template_id = self::generateTemplateId();
            }
            if (!$template->enrolled_at) {
                $template->enrolled_at = now();
            }
        });
    }

    /**
     * Generate a unique template ID
     */
    public static function generateTemplateId(): string
    {
        return 'bt_' . Str::random(32);
    }

    /**
     * Create a new biometric template
     */
    public static function createTemplate(
        User $user,
        string $biometricType,
        string $templateData,
        ?UserDevice $device = null,
        array $metadata = [],
        string $algorithmVersion = '1.0'
    ): self {
        // Validate biometric type
        if (!in_array($biometricType, self::TYPES)) {
            throw new \InvalidArgumentException("Invalid biometric type: {$biometricType}");
        }

        // Encrypt template data
        $encryptedData = Crypt::encrypt($templateData);
        $templateHash = hash('sha256', $templateData);

        // Check if this is the first template of this type for the user
        $isPrimary = !self::where('user_id', $user->id)
            ->where('biometric_type', $biometricType)
            ->where('is_active', true)
            ->exists();

        return self::create([
            'user_id' => $user->id,
            'user_device_id' => $device?->id,
            'biometric_type' => $biometricType,
            'template_data' => $encryptedData,
            'template_hash' => $templateHash,
            'template_metadata' => $metadata,
            'algorithm_version' => $algorithmVersion,
            'is_primary' => $isPrimary,
            'is_active' => true,
            'enrolled_at' => now(),
        ]);
    }

    /**
     * Decrypt template data
     */
    public function getDecryptedTemplateData(): string
    {
        return Crypt::decrypt($this->template_data);
    }

    /**
     * Verify biometric data against this template
     */
    public function verify(string $biometricData, float $threshold = 0.85): array
    {
        try {
            $decryptedTemplate = $this->getDecryptedTemplateData();
            
            // This is a simplified verification - in real implementation,
            // you would use a proper biometric SDK
            $similarity = $this->calculateSimilarity($decryptedTemplate, $biometricData);
            $isMatch = $similarity >= $threshold;

            if ($isMatch) {
                $this->recordSuccessfulVerification();
            } else {
                $this->recordFailedVerification();
            }

            return [
                'verified' => $isMatch,
                'similarity' => $similarity,
                'threshold' => $threshold,
                'template_id' => $this->template_id,
                'biometric_type' => $this->biometric_type,
            ];
        } catch (\Exception $e) {
            $this->recordFailedVerification();
            
            return [
                'verified' => false,
                'error' => 'Verification failed',
                'template_id' => $this->template_id,
                'biometric_type' => $this->biometric_type,
            ];
        }
    }

    /**
     * Calculate similarity between templates (simplified)
     */
    private function calculateSimilarity(string $template1, string $template2): float
    {
        // This is a very simplified similarity calculation
        // In real implementation, you would use proper biometric algorithms
        $hash1 = hash('sha256', $template1);
        $hash2 = hash('sha256', $template2);
        
        if ($hash1 === $hash2) {
            return 1.0;
        }

        // Calculate character-level similarity (very basic)
        $len1 = strlen($template1);
        $len2 = strlen($template2);
        $maxLen = max($len1, $len2);
        
        if ($maxLen === 0) {
            return 0.0;
        }

        $matches = 0;
        $minLen = min($len1, $len2);
        
        for ($i = 0; $i < $minLen; $i++) {
            if ($template1[$i] === $template2[$i]) {
                $matches++;
            }
        }

        return $matches / $maxLen;
    }

    /**
     * Record successful verification
     */
    public function recordSuccessfulVerification(): void
    {
        $this->increment('verification_count');
        $this->update([
            'last_verified_at' => now(),
            'failed_attempts' => 0, // Reset failed attempts on success
        ]);
    }

    /**
     * Record failed verification
     */
    public function recordFailedVerification(): void
    {
        $this->increment('failed_attempts');
        $this->update(['last_failed_at' => now()]);

        // Check if template should be marked as compromised
        if ($this->failed_attempts >= 10) {
            $this->markAsCompromised('too_many_failures');
        }
    }

    /**
     * Mark template as compromised
     */
    public function markAsCompromised(string $reason): void
    {
        $this->update([
            'is_compromised' => true,
            'is_active' => false,
            'compromised_at' => now(),
            'compromised_reason' => $reason,
        ]);
    }

    /**
     * Set as primary template
     */
    public function setAsPrimary(): void
    {
        // Remove primary status from other templates of the same type
        self::where('user_id', $this->user_id)
            ->where('biometric_type', $this->biometric_type)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);

        $this->update(['is_primary' => true]);
    }

    /**
     * Deactivate template
     */
    public function deactivate(string $reason = 'manual'): void
    {
        $this->update([
            'is_active' => false,
            'security_metadata' => array_merge($this->security_metadata ?? [], [
                'deactivated_at' => now()->toISOString(),
                'deactivated_reason' => $reason,
            ]),
        ]);
    }

    /**
     * Update template metadata
     */
    public function updateMetadata(array $metadata): void
    {
        $currentMetadata = $this->template_metadata ?? [];
        $this->update([
            'template_metadata' => array_merge($currentMetadata, $metadata)
        ]);
    }

    /**
     * Check if template is usable
     */
    public function isUsable(): bool
    {
        return $this->is_active && !$this->is_compromised;
    }

    /**
     * Get quality score from metadata
     */
    public function getQualityScore(): ?float
    {
        return data_get($this->template_metadata, 'quality_score');
    }

    /**
     * Get enrollment info
     */
    public function getEnrollmentInfo(): array
    {
        return [
            'enrolled_at' => $this->enrolled_at?->toISOString(),
            'algorithm_version' => $this->algorithm_version,
            'quality_score' => $this->getQualityScore(),
            'verification_count' => $this->verification_count,
            'failed_attempts' => $this->failed_attempts,
            'is_primary' => $this->is_primary,
        ];
    }

    /**
     * Scope for active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('is_compromised', false);
    }

    /**
     * Scope for specific biometric type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('biometric_type', $type);
    }

    /**
     * Scope for primary templates
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope for user templates
     */
    public function scopeForUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * Scope for device templates
     */
    public function scopeForDevice($query, UserDevice $device)
    {
        return $query->where('user_device_id', $device->id);
    }

    /**
     * Get formatted biometric type
     */
    public function getFormattedTypeAttribute(): string
    {
        return match($this->biometric_type) {
            self::TYPE_FINGERPRINT => 'Fingerprint',
            self::TYPE_FACE => 'Face Recognition',
            self::TYPE_VOICE => 'Voice Recognition',
            self::TYPE_IRIS => 'Iris Recognition',
            default => ucfirst($this->biometric_type),
        };
    }

    /**
     * Get template status
     */
    public function getStatusAttribute(): string
    {
        if ($this->is_compromised) {
            return 'compromised';
        }

        if (!$this->is_active) {
            return 'inactive';
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
            'inactive' => 'warning',
            'compromised' => 'danger',
            default => 'gray',
        };
    }

    /**
     * Get success rate
     */
    public function getSuccessRateAttribute(): float
    {
        $totalAttempts = $this->verification_count + $this->failed_attempts;
        
        if ($totalAttempts === 0) {
            return 0.0;
        }

        return ($this->verification_count / $totalAttempts) * 100;
    }

    /**
     * Check if template needs attention
     */
    public function needsAttention(): bool
    {
        return $this->failed_attempts > 5 || 
               $this->success_rate < 80 ||
               $this->is_compromised;
    }

    /**
     * Find active template for user and type
     */
    public static function findActiveForUser(User $user, string $biometricType): ?self
    {
        return self::forUser($user)
            ->ofType($biometricType)
            ->active()
            ->primary()
            ->first();
    }

    /**
     * Get all active templates for user
     */
    public static function getActiveForUser(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return self::forUser($user)
            ->active()
            ->orderBy('biometric_type')
            ->orderByDesc('is_primary')
            ->get();
    }

    /**
     * Clean up old templates
     */
    public static function cleanupOldTemplates(int $daysOld = 365): int
    {
        return self::where('enrolled_at', '<', now()->subDays($daysOld))
            ->where('is_active', false)
            ->delete();
    }
}