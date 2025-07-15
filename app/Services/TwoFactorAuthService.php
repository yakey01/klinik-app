<?php

namespace App\Services;

use App\Models\User;
use App\Models\TwoFactorAuth;
use App\Models\AuditLog;
use PragmaRX\Google2FALaravel\Google2FA;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TwoFactorAuthService
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = app(Google2FA::class);
    }

    /**
     * Generate 2FA secret for user
     */
    public function generateSecret(User $user): string
    {
        $secret = $this->google2fa->generateSecretKey();
        
        // Store or update 2FA record
        $twoFactor = TwoFactorAuth::updateOrCreate(
            ['user_id' => $user->id],
            [
                'secret_key' => encrypt($secret),
                'enabled' => false,
            ]
        );

        // Generate initial recovery codes
        $twoFactor->generateRecoveryCodes();

        return $secret;
    }

    /**
     * Generate QR code for 2FA setup
     */
    public function generateQrCode(User $user, string $secret): string
    {
        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        return QrCode::size(200)
            ->format('svg')
            ->generate($qrCodeUrl);
    }

    /**
     * Verify 2FA code
     */
    public function verifyCode(User $user, string $code): bool
    {
        $twoFactor = TwoFactorAuth::where('user_id', $user->id)->first();
        
        if (!$twoFactor || !$twoFactor->enabled) {
            return false;
        }

        $secret = decrypt($twoFactor->secret_key);
        $valid = $this->google2fa->verifyKey($secret, $code);

        if ($valid) {
            $twoFactor->updateLastUsed();
            
            // Log successful 2FA verification
            AuditLog::logSecurity(
                'two_factor_verified',
                $user,
                'User successfully verified 2FA code',
                ['user_id' => $user->id]
            );
        } else {
            // Log failed 2FA verification
            AuditLog::logSecurity(
                'two_factor_failed',
                $user,
                'User failed 2FA verification',
                ['user_id' => $user->id, 'code_attempted' => substr($code, 0, 2) . '****']
            );
        }

        return $valid;
    }

    /**
     * Enable 2FA for user
     */
    public function enable(User $user, string $verificationCode): bool
    {
        $twoFactor = TwoFactorAuth::where('user_id', $user->id)->first();
        
        if (!$twoFactor) {
            return false;
        }

        $secret = decrypt($twoFactor->secret_key);
        $valid = $this->google2fa->verifyKey($secret, $verificationCode);

        if ($valid) {
            $twoFactor->enable();
            return true;
        }

        return false;
    }

    /**
     * Disable 2FA for user
     */
    public function disable(User $user, string $password): bool
    {
        if (!Hash::check($password, $user->password)) {
            return false;
        }

        $twoFactor = TwoFactorAuth::where('user_id', $user->id)->first();
        
        if ($twoFactor) {
            $twoFactor->disable();
            return true;
        }

        return false;
    }

    /**
     * Verify recovery code
     */
    public function verifyRecoveryCode(User $user, string $code): bool
    {
        $twoFactor = TwoFactorAuth::where('user_id', $user->id)->first();
        
        if (!$twoFactor || !$twoFactor->enabled) {
            return false;
        }

        $valid = $twoFactor->useRecoveryCode(strtoupper($code));

        if ($valid) {
            // Log successful recovery code use
            AuditLog::logSecurity(
                'two_factor_recovery_used',
                $user,
                'User successfully used 2FA recovery code',
                ['user_id' => $user->id, 'code' => substr($code, 0, 2) . '****']
            );
        } else {
            // Log failed recovery code attempt
            AuditLog::logSecurity(
                'two_factor_recovery_failed',
                $user,
                'User failed 2FA recovery code verification',
                ['user_id' => $user->id, 'code_attempted' => substr($code, 0, 2) . '****']
            );
        }

        return $valid;
    }

    /**
     * Generate new recovery codes
     */
    public function generateNewRecoveryCodes(User $user, string $password): ?array
    {
        if (!Hash::check($password, $user->password)) {
            return null;
        }

        $twoFactor = TwoFactorAuth::where('user_id', $user->id)->first();
        
        if (!$twoFactor || !$twoFactor->enabled) {
            return null;
        }

        $codes = $twoFactor->generateRecoveryCodes();

        // Log recovery codes regeneration
        AuditLog::logSecurity(
            'two_factor_recovery_regenerated',
            $user,
            'User regenerated 2FA recovery codes',
            ['user_id' => $user->id, 'codes_count' => count($codes)]
        );

        return $codes;
    }

    /**
     * Check if user has 2FA enabled
     */
    public function isEnabled(User $user): bool
    {
        $twoFactor = TwoFactorAuth::where('user_id', $user->id)->first();
        return $twoFactor && $twoFactor->enabled;
    }

    /**
     * Get 2FA status for user
     */
    public function getStatus(User $user): array
    {
        $twoFactor = TwoFactorAuth::where('user_id', $user->id)->first();
        
        if (!$twoFactor) {
            return [
                'enabled' => false,
                'setup_required' => true,
                'recovery_codes_available' => 0,
                'last_used' => null,
                'enabled_at' => null,
            ];
        }

        return [
            'enabled' => $twoFactor->enabled,
            'setup_required' => false,
            'recovery_codes_available' => count($twoFactor->getUnusedRecoveryCodes()),
            'last_used' => $twoFactor->last_used_at,
            'enabled_at' => $twoFactor->enabled_at,
        ];
    }

    /**
     * Get unused recovery codes for user
     */
    public function getUnusedRecoveryCodes(User $user): array
    {
        $twoFactor = TwoFactorAuth::where('user_id', $user->id)->first();
        
        if (!$twoFactor) {
            return [];
        }

        return $twoFactor->getUnusedRecoveryCodes();
    }

    /**
     * Force disable 2FA for user (admin action)
     */
    public function forceDisable(User $user, User $admin): bool
    {
        $twoFactor = TwoFactorAuth::where('user_id', $user->id)->first();
        
        if ($twoFactor) {
            $twoFactor->disable();
            
            // Log admin action
            AuditLog::logSecurity(
                'two_factor_force_disabled',
                $admin,
                'Admin force disabled 2FA for user',
                [
                    'target_user_id' => $user->id,
                    'target_user_email' => $user->email,
                    'admin_id' => $admin->id,
                ]
            );
            
            return true;
        }

        return false;
    }

    /**
     * Check if 2FA is required for user role
     */
    public function isRequiredForUser(User $user): bool
    {
        $requiredRoles = config('auth.2fa_required_roles', ['super-admin', 'admin']);
        
        foreach ($requiredRoles as $role) {
            if ($user->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get 2FA compliance report
     */
    public function getComplianceReport(): array
    {
        $totalUsers = User::count();
        $usersWithTwoFactor = TwoFactorAuth::where('enabled', true)->count();
        $requiredUsers = User::role(['super-admin', 'admin'])->count();
        $requiredUsersWithTwoFactor = TwoFactorAuth::where('enabled', true)
            ->whereHas('user', function ($query) {
                $query->role(['super-admin', 'admin']);
            })->count();

        return [
            'total_users' => $totalUsers,
            'users_with_2fa' => $usersWithTwoFactor,
            'coverage_percentage' => $totalUsers > 0 ? round(($usersWithTwoFactor / $totalUsers) * 100, 2) : 0,
            'required_users' => $requiredUsers,
            'required_users_with_2fa' => $requiredUsersWithTwoFactor,
            'compliance_percentage' => $requiredUsers > 0 ? round(($requiredUsersWithTwoFactor / $requiredUsers) * 100, 2) : 100,
        ];
    }
}