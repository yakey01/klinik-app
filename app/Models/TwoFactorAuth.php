<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TwoFactorAuth extends Model
{
    use HasFactory;

    protected $table = 'two_factor_auth';

    protected $fillable = [
        'user_id',
        'secret_key',
        'recovery_codes',
        'enabled',
        'enabled_at',
        'last_used_at',
        'backup_codes_used',
    ];

    protected $casts = [
        'recovery_codes' => 'array',
        'backup_codes_used' => 'array',
        'enabled' => 'boolean',
        'enabled_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    protected $hidden = [
        'secret_key',
        'recovery_codes',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Generate recovery codes
    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6));
        }
        
        $this->recovery_codes = $codes;
        $this->backup_codes_used = [];
        $this->save();
        
        return $codes;
    }

    // Use recovery code
    public function useRecoveryCode(string $code): bool
    {
        if (!$this->recovery_codes || !in_array($code, $this->recovery_codes)) {
            return false;
        }

        $usedCodes = $this->backup_codes_used ?? [];
        if (in_array($code, $usedCodes)) {
            return false;
        }

        $usedCodes[] = $code;
        $this->backup_codes_used = $usedCodes;
        $this->last_used_at = now();
        $this->save();

        return true;
    }

    // Check if recovery code is valid
    public function isValidRecoveryCode(string $code): bool
    {
        if (!$this->recovery_codes || !in_array($code, $this->recovery_codes)) {
            return false;
        }

        $usedCodes = $this->backup_codes_used ?? [];
        return !in_array($code, $usedCodes);
    }

    // Get unused recovery codes
    public function getUnusedRecoveryCodes(): array
    {
        if (!$this->recovery_codes) {
            return [];
        }

        $usedCodes = $this->backup_codes_used ?? [];
        return array_diff($this->recovery_codes, $usedCodes);
    }

    // Check if user has unused recovery codes
    public function hasUnusedRecoveryCodes(): bool
    {
        return count($this->getUnusedRecoveryCodes()) > 0;
    }

    // Enable 2FA
    public function enable(): void
    {
        $this->enabled = true;
        $this->enabled_at = now();
        $this->save();
        
        // Log the event
        if (class_exists('\App\Models\AuditLog')) {
            \App\Models\AuditLog::logSecurity(
                'two_factor_enabled',
                $this->user,
                'User enabled two-factor authentication',
                ['user_id' => $this->user_id]
            );
        }
    }

    // Disable 2FA
    public function disable(): void
    {
        $this->enabled = false;
        $this->enabled_at = null;
        $this->last_used_at = null;
        $this->save();
        
        // Log the event
        if (class_exists('\App\Models\AuditLog')) {
            \App\Models\AuditLog::logSecurity(
                'two_factor_disabled',
                $this->user,
                'User disabled two-factor authentication',
                ['user_id' => $this->user_id]
            );
        }
    }

    // Update last used timestamp
    public function updateLastUsed(): void
    {
        $this->last_used_at = now();
        $this->save();
    }
}