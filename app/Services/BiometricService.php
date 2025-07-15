<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserDevice;
use App\Models\BiometricTemplate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BiometricService
{
    /**
     * Enroll biometric template for user
     */
    public function enrollBiometric(
        User $user,
        string $biometricType,
        string $biometricData,
        ?UserDevice $device = null,
        array $metadata = []
    ): array {
        try {
            // Validate biometric type
            if (!in_array($biometricType, BiometricTemplate::TYPES)) {
                throw new \InvalidArgumentException("Invalid biometric type: {$biometricType}");
            }

            // Validate biometric data quality
            $qualityScore = $this->assessBiometricQuality($biometricData, $biometricType);
            if ($qualityScore < 0.7) {
                throw new \InvalidArgumentException('Biometric data quality is too low. Please try again.');
            }

            // Check if user already has this biometric type enrolled
            $existingTemplate = BiometricTemplate::findActiveForUser($user, $biometricType);
            if ($existingTemplate) {
                // Deactivate existing template
                $existingTemplate->deactivate('replaced_by_new_enrollment');
            }

            // Create metadata with quality score
            $templateMetadata = array_merge($metadata, [
                'quality_score' => $qualityScore,
                'enrollment_device_id' => $device?->device_id,
                'enrollment_ip' => request()?->ip(),
                'enrollment_user_agent' => request()?->userAgent(),
                'algorithm_version' => '1.0',
            ]);

            // Create biometric template
            $template = BiometricTemplate::createTemplate(
                $user,
                $biometricType,
                $biometricData,
                $device,
                $templateMetadata
            );

            // Update device biometric capabilities if device provided
            if ($device) {
                $this->updateDeviceBiometricCapabilities($device, $biometricType);
            }

            Log::info('Biometric template enrolled', [
                'user_id' => $user->id,
                'device_id' => $device?->id,
                'biometric_type' => $biometricType,
                'template_id' => $template->template_id,
                'quality_score' => $qualityScore,
            ]);

            return [
                'success' => true,
                'template_id' => $template->template_id,
                'biometric_type' => $biometricType,
                'quality_score' => $qualityScore,
                'is_primary' => $template->is_primary,
                'enrolled_at' => $template->enrolled_at->toISOString(),
                'message' => 'Biometric template enrolled successfully',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to enroll biometric template', [
                'user_id' => $user->id,
                'biometric_type' => $biometricType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Verify biometric data against user's templates
     */
    public function verifyBiometric(
        User $user,
        string $biometricType,
        string $biometricData,
        ?UserDevice $device = null,
        float $threshold = 0.85
    ): array {
        try {
            // Find active template for user and type
            $template = BiometricTemplate::findActiveForUser($user, $biometricType);
            
            if (!$template) {
                Log::warning('No biometric template found for verification', [
                    'user_id' => $user->id,
                    'biometric_type' => $biometricType,
                    'device_id' => $device?->id,
                ]);

                return [
                    'verified' => false,
                    'error' => 'No biometric template enrolled for this type',
                    'biometric_type' => $biometricType,
                    'requires_enrollment' => true,
                ];
            }

            // Check if template is usable
            if (!$template->isUsable()) {
                Log::warning('Biometric template not usable for verification', [
                    'user_id' => $user->id,
                    'biometric_type' => $biometricType,
                    'template_id' => $template->template_id,
                    'is_active' => $template->is_active,
                    'is_compromised' => $template->is_compromised,
                ]);

                return [
                    'verified' => false,
                    'error' => 'Biometric template is not available for verification',
                    'biometric_type' => $biometricType,
                    'template_status' => $template->status,
                    'requires_enrollment' => true,
                ];
            }

            // Assess biometric data quality
            $qualityScore = $this->assessBiometricQuality($biometricData, $biometricType);
            if ($qualityScore < 0.5) {
                Log::warning('Biometric verification failed due to low quality', [
                    'user_id' => $user->id,
                    'biometric_type' => $biometricType,
                    'quality_score' => $qualityScore,
                ]);

                return [
                    'verified' => false,
                    'error' => 'Biometric data quality is too low',
                    'quality_score' => $qualityScore,
                    'biometric_type' => $biometricType,
                    'requires_retry' => true,
                ];
            }

            // Perform verification
            $verificationResult = $template->verify($biometricData, $threshold);

            // Update device biometric verification count if successful
            if ($verificationResult['verified'] && $device) {
                $device->increment('biometric_verification_count');
                $device->update(['last_biometric_verification_at' => now()]);
            }

            Log::info('Biometric verification attempted', [
                'user_id' => $user->id,
                'device_id' => $device?->id,
                'biometric_type' => $biometricType,
                'template_id' => $template->template_id,
                'verified' => $verificationResult['verified'],
                'similarity' => $verificationResult['similarity'],
                'quality_score' => $qualityScore,
            ]);

            return array_merge($verificationResult, [
                'quality_score' => $qualityScore,
                'template_info' => $template->getEnrollmentInfo(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to verify biometric', [
                'user_id' => $user->id,
                'biometric_type' => $biometricType,
                'error' => $e->getMessage(),
            ]);

            return [
                'verified' => false,
                'error' => 'Biometric verification failed',
                'biometric_type' => $biometricType,
            ];
        }
    }

    /**
     * Get user's biometric templates
     */
    public function getUserBiometrics(User $user): array
    {
        $templates = BiometricTemplate::getActiveForUser($user);

        return $templates->map(function ($template) {
            return [
                'template_id' => $template->template_id,
                'biometric_type' => $template->biometric_type,
                'formatted_type' => $template->formatted_type,
                'is_primary' => $template->is_primary,
                'status' => $template->status,
                'enrolled_at' => $template->enrolled_at?->toISOString(),
                'last_verified_at' => $template->last_verified_at?->toISOString(),
                'verification_count' => $template->verification_count,
                'success_rate' => round($template->success_rate, 2),
                'quality_score' => $template->getQualityScore(),
                'needs_attention' => $template->needsAttention(),
                'device_info' => $template->userDevice ? [
                    'device_id' => $template->userDevice->device_id,
                    'device_name' => $template->userDevice->device_name,
                    'platform' => $template->userDevice->platform,
                ] : null,
            ];
        })->toArray();
    }

    /**
     * Remove biometric template
     */
    public function removeBiometric(
        User $user,
        string $biometricType,
        string $reason = 'user_request'
    ): array {
        try {
            $template = BiometricTemplate::findActiveForUser($user, $biometricType);
            
            if (!$template) {
                throw new \InvalidArgumentException('No active biometric template found for this type');
            }

            $template->deactivate($reason);

            Log::info('Biometric template removed', [
                'user_id' => $user->id,
                'biometric_type' => $biometricType,
                'template_id' => $template->template_id,
                'reason' => $reason,
            ]);

            return [
                'success' => true,
                'template_id' => $template->template_id,
                'biometric_type' => $biometricType,
                'message' => 'Biometric template removed successfully',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to remove biometric template', [
                'user_id' => $user->id,
                'biometric_type' => $biometricType,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Update biometric template metadata
     */
    public function updateBiometricMetadata(
        User $user,
        string $biometricType,
        array $metadata
    ): array {
        try {
            $template = BiometricTemplate::findActiveForUser($user, $biometricType);
            
            if (!$template) {
                throw new \InvalidArgumentException('No active biometric template found for this type');
            }

            $template->updateMetadata($metadata);

            Log::info('Biometric template metadata updated', [
                'user_id' => $user->id,
                'biometric_type' => $biometricType,
                'template_id' => $template->template_id,
                'metadata_keys' => array_keys($metadata),
            ]);

            return [
                'success' => true,
                'template_id' => $template->template_id,
                'biometric_type' => $biometricType,
                'message' => 'Biometric template metadata updated successfully',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to update biometric template metadata', [
                'user_id' => $user->id,
                'biometric_type' => $biometricType,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Check biometric capabilities for device
     */
    public function checkDeviceBiometricCapabilities(?UserDevice $device): array
    {
        if (!$device) {
            return [
                'has_biometric_support' => false,
                'supported_types' => [],
                'enrolled_types' => [],
            ];
        }

        $capabilities = $device->biometric_capabilities ?? [];
        $enrolledTypes = $device->biometric_types ?? [];

        return [
            'has_biometric_support' => $device->biometric_enabled,
            'supported_types' => $capabilities,
            'enrolled_types' => $enrolledTypes,
            'enrollment_date' => $device->biometric_enrolled_at?->toISOString(),
            'verification_count' => $device->biometric_verification_count,
            'last_verification' => $device->last_biometric_verification_at?->toISOString(),
        ];
    }

    /**
     * Assess biometric data quality (simplified implementation)
     */
    private function assessBiometricQuality(string $biometricData, string $biometricType): float
    {
        // This is a simplified quality assessment
        // In real implementation, you would use proper biometric SDK
        
        $dataLength = strlen($biometricData);
        $baseScore = 0.5;

        // Basic quality checks based on data length and type
        switch ($biometricType) {
            case BiometricTemplate::TYPE_FINGERPRINT:
                $baseScore = $dataLength > 1000 ? 0.8 : 0.6;
                break;
            case BiometricTemplate::TYPE_FACE:
                $baseScore = $dataLength > 5000 ? 0.85 : 0.65;
                break;
            case BiometricTemplate::TYPE_VOICE:
                $baseScore = $dataLength > 2000 ? 0.75 : 0.55;
                break;
            case BiometricTemplate::TYPE_IRIS:
                $baseScore = $dataLength > 3000 ? 0.9 : 0.7;
                break;
        }

        // Add some randomization to simulate real quality assessment
        $variance = (mt_rand(-10, 10) / 100);
        $finalScore = max(0.0, min(1.0, $baseScore + $variance));

        return round($finalScore, 3);
    }

    /**
     * Update device biometric capabilities
     */
    private function updateDeviceBiometricCapabilities(UserDevice $device, string $biometricType): void
    {
        $capabilities = $device->biometric_capabilities ?? [];
        $enrolledTypes = $device->biometric_types ?? [];

        // Add to capabilities if not already present
        if (!in_array($biometricType, $capabilities)) {
            $capabilities[] = $biometricType;
        }

        // Add to enrolled types if not already present
        if (!in_array($biometricType, $enrolledTypes)) {
            $enrolledTypes[] = $biometricType;
        }

        $device->update([
            'biometric_capabilities' => array_unique($capabilities),
            'biometric_types' => array_unique($enrolledTypes),
            'biometric_enabled' => true,
            'biometric_enrolled_at' => $device->biometric_enrolled_at ?? now(),
        ]);
    }

    /**
     * Get biometric security recommendations
     */
    public function getBiometricSecurityRecommendations(User $user): array
    {
        $templates = BiometricTemplate::getActiveForUser($user);
        $recommendations = [];

        // Check for missing biometric types
        $enrolledTypes = $templates->pluck('biometric_type')->toArray();
        $availableTypes = BiometricTemplate::TYPES;
        $missingTypes = array_diff($availableTypes, $enrolledTypes);

        if (!empty($missingTypes)) {
            $recommendations[] = [
                'type' => 'enrollment',
                'priority' => 'medium',
                'title' => 'Enroll additional biometric types',
                'description' => 'Consider enrolling ' . implode(', ', $missingTypes) . ' for enhanced security',
                'action' => 'enroll_biometric',
                'data' => ['missing_types' => $missingTypes],
            ];
        }

        // Check for templates that need attention
        $problematicTemplates = $templates->filter(function ($template) {
            return $template->needsAttention();
        });

        foreach ($problematicTemplates as $template) {
            $recommendations[] = [
                'type' => 'security',
                'priority' => 'high',
                'title' => "Update {$template->formatted_type} template",
                'description' => "Your {$template->formatted_type} template has security issues and should be updated",
                'action' => 're_enroll_biometric',
                'data' => [
                    'biometric_type' => $template->biometric_type,
                    'template_id' => $template->template_id,
                    'issues' => [
                        'failed_attempts' => $template->failed_attempts,
                        'success_rate' => $template->success_rate,
                        'is_compromised' => $template->is_compromised,
                    ],
                ],
            ];
        }

        // Check for old templates
        $oldTemplates = $templates->filter(function ($template) {
            return $template->enrolled_at->diffInDays(now()) > 365;
        });

        if ($oldTemplates->isNotEmpty()) {
            $recommendations[] = [
                'type' => 'maintenance',
                'priority' => 'low',
                'title' => 'Update old biometric templates',
                'description' => 'Some biometric templates are over a year old and should be updated',
                'action' => 'bulk_re_enroll',
                'data' => ['old_templates' => $oldTemplates->pluck('biometric_type')->toArray()],
            ];
        }

        return $recommendations;
    }
}