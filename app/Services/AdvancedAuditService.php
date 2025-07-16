<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\RealTimeNotificationService;
use App\Services\LoggingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Exception;

class AdvancedAuditService
{
    protected RealTimeNotificationService $notificationService;
    protected LoggingService $loggingService;
    
    protected array $sensitiveActions = [
        'financial.approve',
        'financial.reject',
        'financial.bulk_approve',
        'user.create',
        'user.delete',
        'role.assign',
        'permission.grant',
        'system.backup',
        'system.restore',
        'data.export',
        'settings.change',
    ];

    protected array $complianceRequirements = [
        'data_retention_days' => 2555, // 7 years
        'log_integrity_check' => true,
        'encryption_required' => true,
        'access_monitoring' => true,
        'audit_trail_immutable' => true,
    ];

    public function __construct(
        RealTimeNotificationService $notificationService,
        LoggingService $loggingService
    ) {
        $this->notificationService = $notificationService;
        $this->loggingService = $loggingService;
    }

    /**
     * Record comprehensive audit trail
     */
    public function recordAuditTrail(array $data): array
    {
        try {
            $auditData = $this->prepareAuditData($data);
            
            // Store in database
            $auditLog = AuditLog::create($auditData);
            
            // Store in separate secure storage for compliance
            $this->storeSecureAuditLog($auditData);
            
            // Check for suspicious activity
            $this->checkForSuspiciousActivity($auditData);
            
            // Generate compliance alerts if needed
            $this->checkComplianceRequirements($auditData);

            return [
                'success' => true,
                'audit_id' => $auditLog->id,
                'recorded_at' => $auditLog->created_at,
            ];

        } catch (Exception $e) {
            Log::error('AdvancedAuditService: Failed to record audit trail', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Prepare audit data with comprehensive information
     */
    protected function prepareAuditData(array $data): array
    {
        $request = request();
        $user = auth()->user();

        return array_merge([
            'user_id' => $user?->id,
            'session_id' => session()->getId(),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'url' => $request?->fullUrl(),
            'method' => $request?->method(),
            'action' => 'unknown',
            'resource_type' => null,
            'resource_id' => null,
            'old_values' => null,
            'new_values' => null,
            'metadata' => [],
            'risk_level' => 'low',
            'compliance_flags' => [],
            'created_at' => now(),
        ], $data);
    }

    /**
     * Store audit log in secure, immutable storage
     */
    protected function storeSecureAuditLog(array $auditData): void
    {
        // Hash the audit data for integrity verification
        $auditData['integrity_hash'] = $this->generateIntegrityHash($auditData);
        
        // Encrypt sensitive data
        if ($this->complianceRequirements['encryption_required']) {
            $auditData = $this->encryptSensitiveFields($auditData);
        }

        // Store in secure log file with rotation
        $this->writeToSecureLogFile($auditData);
        
        // Store in blockchain-like structure for immutability
        $this->storeInImmutableLog($auditData);
    }

    /**
     * Generate integrity hash for audit data
     */
    protected function generateIntegrityHash(array $data): string
    {
        // Remove dynamic fields that shouldn't affect integrity
        unset($data['created_at'], $data['integrity_hash']);
        
        return hash('sha256', json_encode($data, JSON_SORT_KEYS));
    }

    /**
     * Encrypt sensitive fields in audit data
     */
    protected function encryptSensitiveFields(array $data): array
    {
        $sensitiveFields = ['old_values', 'new_values', 'metadata'];
        
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field]) && !is_null($data[$field])) {
                $data[$field . '_encrypted'] = encrypt(json_encode($data[$field]));
                unset($data[$field]);
            }
        }
        
        return $data;
    }

    /**
     * Write to secure log file
     */
    protected function writeToSecureLogFile(array $auditData): void
    {
        $logFile = storage_path('logs/audit/secure-audit-' . date('Y-m') . '.log');
        
        // Ensure directory exists
        if (!file_exists(dirname($logFile))) {
            mkdir(dirname($logFile), 0750, true);
        }
        
        $logEntry = json_encode($auditData) . "\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Store in immutable log structure
     */
    protected function storeInImmutableLog(array $auditData): void
    {
        $previousHash = $this->getLastImmutableHash();
        $currentHash = hash('sha256', $previousHash . json_encode($auditData));
        
        $immutableEntry = [
            'previous_hash' => $previousHash,
            'current_hash' => $currentHash,
            'audit_data' => $auditData,
            'timestamp' => now()->timestamp,
        ];
        
        Cache::put(
            "immutable_audit:{$currentHash}",
            $immutableEntry,
            now()->addYears(10)
        );
        
        // Update the chain pointer
        Cache::put('last_immutable_hash', $currentHash, now()->addYears(10));
    }

    /**
     * Get last hash in immutable chain
     */
    protected function getLastImmutableHash(): string
    {
        return Cache::get('last_immutable_hash', 'genesis_block');
    }

    /**
     * Check for suspicious activity patterns
     */
    protected function checkForSuspiciousActivity(array $auditData): void
    {
        $suspiciousPatterns = [
            'rapid_fire_actions' => $this->checkRapidFireActions($auditData),
            'unusual_access_time' => $this->checkUnusualAccessTime($auditData),
            'privilege_escalation' => $this->checkPrivilegeEscalation($auditData),
            'data_exfiltration' => $this->checkDataExfiltration($auditData),
            'unauthorized_location' => $this->checkUnauthorizedLocation($auditData),
        ];

        $detectedPatterns = array_filter($suspiciousPatterns);
        
        if (!empty($detectedPatterns)) {
            $this->handleSuspiciousActivity($auditData, $detectedPatterns);
        }
    }

    /**
     * Check for rapid fire actions (potential automation/attack)
     */
    protected function checkRapidFireActions(array $auditData): bool
    {
        if (!$auditData['user_id']) {
            return false;
        }

        $recentActions = AuditLog::where('user_id', $auditData['user_id'])
            ->where('created_at', '>=', now()->subMinutes(5))
            ->count();

        return $recentActions > 50; // More than 50 actions in 5 minutes
    }

    /**
     * Check for unusual access time
     */
    protected function checkUnusualAccessTime(array $auditData): bool
    {
        $hour = now()->hour;
        
        // Flag access between 11 PM and 5 AM as unusual
        return $hour >= 23 || $hour <= 5;
    }

    /**
     * Check for privilege escalation attempts
     */
    protected function checkPrivilegeEscalation(array $auditData): bool
    {
        $escalationActions = [
            'role.assign',
            'permission.grant',
            'user.promote',
            'admin.access',
        ];

        return in_array($auditData['action'], $escalationActions);
    }

    /**
     * Check for potential data exfiltration
     */
    protected function checkDataExfiltration(array $auditData): bool
    {
        $exfiltrationActions = [
            'data.export',
            'report.generate',
            'bulk.download',
        ];

        if (!in_array($auditData['action'], $exfiltrationActions)) {
            return false;
        }

        // Check volume of recent exports
        $recentExports = AuditLog::where('user_id', $auditData['user_id'])
            ->whereIn('action', $exfiltrationActions)
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        return $recentExports > 10; // More than 10 exports in 24 hours
    }

    /**
     * Check for unauthorized location access
     */
    protected function checkUnauthorizedLocation(array $auditData): bool
    {
        if (!$auditData['ip_address']) {
            return false;
        }

        // Check if IP is from unauthorized location
        $authorizedIPs = config('security.authorized_ip_ranges', []);
        
        foreach ($authorizedIPs as $range) {
            if ($this->ipInRange($auditData['ip_address'], $range)) {
                return false;
            }
        }

        return true; // IP not in authorized ranges
    }

    /**
     * Check if IP is in range
     */
    protected function ipInRange(string $ip, string $range): bool
    {
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }

        [$subnet, $mask] = explode('/', $range);
        
        return (ip2long($ip) & ~((1 << (32 - $mask)) - 1)) === ip2long($subnet);
    }

    /**
     * Handle detected suspicious activity
     */
    protected function handleSuspiciousActivity(array $auditData, array $detectedPatterns): void
    {
        $alertData = [
            'title' => 'Aktivitas Mencurigakan Terdeteksi',
            'message' => 'Sistem mendeteksi pola aktivitas yang mencurigakan',
            'urgency' => 'critical',
            'category' => 'security',
            'metadata' => [
                'user_id' => $auditData['user_id'],
                'ip_address' => $auditData['ip_address'],
                'action' => $auditData['action'],
                'detected_patterns' => array_keys($detectedPatterns),
                'timestamp' => now()->toISOString(),
            ],
            'recipient' => 'security_team',
        ];

        $this->notificationService->sendNotification($alertData);

        // Log to security log
        Log::channel('security')->alert('Suspicious activity detected', $alertData);

        // Increment risk score for user
        $this->incrementUserRiskScore($auditData['user_id'], count($detectedPatterns));
    }

    /**
     * Increment user risk score
     */
    protected function incrementUserRiskScore(int $userId, int $increment): void
    {
        $cacheKey = "user_risk_score:{$userId}";
        $currentScore = Cache::get($cacheKey, 0);
        $newScore = $currentScore + $increment;
        
        Cache::put($cacheKey, $newScore, now()->addDays(30));

        // Alert if risk score is too high
        if ($newScore >= 10) {
            $this->handleHighRiskUser($userId, $newScore);
        }
    }

    /**
     * Handle high-risk user
     */
    protected function handleHighRiskUser(int $userId, int $riskScore): void
    {
        $user = User::find($userId);
        
        if (!$user) {
            return;
        }

        $alertData = [
            'title' => 'User Berisiko Tinggi',
            'message' => "User {$user->name} memiliki risk score {$riskScore}",
            'urgency' => 'high',
            'category' => 'security',
            'metadata' => [
                'user_id' => $userId,
                'user_name' => $user->name,
                'risk_score' => $riskScore,
                'recommended_action' => 'Review user activity and consider temporary restrictions',
            ],
            'recipient' => 'admin',
        ];

        $this->notificationService->sendNotification($alertData);
    }

    /**
     * Check compliance requirements
     */
    protected function checkComplianceRequirements(array $auditData): void
    {
        $violations = [];

        // Check data retention compliance
        if ($this->isDataRetentionViolation()) {
            $violations[] = 'data_retention';
        }

        // Check access control compliance
        if ($this->isAccessControlViolation($auditData)) {
            $violations[] = 'access_control';
        }

        // Check audit trail completeness
        if ($this->isAuditTrailIncomplete($auditData)) {
            $violations[] = 'audit_completeness';
        }

        if (!empty($violations)) {
            $this->handleComplianceViolations($violations, $auditData);
        }
    }

    /**
     * Check for data retention violations
     */
    protected function isDataRetentionViolation(): bool
    {
        $retentionDays = $this->complianceRequirements['data_retention_days'];
        $cutoffDate = now()->subDays($retentionDays);
        
        $oldRecords = AuditLog::where('created_at', '<', $cutoffDate)->count();
        
        return $oldRecords > 0; // Should have been deleted
    }

    /**
     * Check for access control violations
     */
    protected function isAccessControlViolation(array $auditData): bool
    {
        // Check if sensitive action was performed without proper authorization
        if (!in_array($auditData['action'], $this->sensitiveActions)) {
            return false;
        }

        $user = User::find($auditData['user_id']);
        
        if (!$user) {
            return true; // No user found is a violation
        }

        // Check if user has appropriate permissions
        return !$this->userHasPermissionForAction($user, $auditData['action']);
    }

    /**
     * Check if user has permission for action
     */
    protected function userHasPermissionForAction(User $user, string $action): bool
    {
        $permissionMap = [
            'financial.approve' => 'approve-financial-transactions',
            'financial.reject' => 'reject-financial-transactions',
            'financial.bulk_approve' => 'bulk-approve-transactions',
            'user.create' => 'create-users',
            'user.delete' => 'delete-users',
            'role.assign' => 'assign-roles',
            'permission.grant' => 'grant-permissions',
            'system.backup' => 'system-backup',
            'system.restore' => 'system-restore',
            'data.export' => 'export-data',
            'settings.change' => 'change-settings',
        ];

        $requiredPermission = $permissionMap[$action] ?? null;
        
        if (!$requiredPermission) {
            return true; // No specific permission required
        }

        return $user->hasPermissionTo($requiredPermission);
    }

    /**
     * Check if audit trail is incomplete
     */
    protected function isAuditTrailIncomplete(array $auditData): bool
    {
        $requiredFields = ['user_id', 'action', 'ip_address', 'user_agent'];
        
        foreach ($requiredFields as $field) {
            if (empty($auditData[$field])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Handle compliance violations
     */
    protected function handleComplianceViolations(array $violations, array $auditData): void
    {
        $alertData = [
            'title' => 'Pelanggaran Compliance Terdeteksi',
            'message' => 'Sistem mendeteksi pelanggaran terhadap requirement compliance',
            'urgency' => 'high',
            'category' => 'compliance',
            'metadata' => [
                'violations' => $violations,
                'audit_data' => $auditData,
                'detected_at' => now()->toISOString(),
            ],
            'recipient' => 'compliance_officer',
        ];

        $this->notificationService->sendNotification($alertData);

        // Log to compliance log
        Log::channel('compliance')->warning('Compliance violations detected', $alertData);
    }

    /**
     * Generate compliance report
     */
    public function generateComplianceReport(array $filters = []): array
    {
        $startDate = $filters['start_date'] ?? now()->subMonth();
        $endDate = $filters['end_date'] ?? now();

        $query = AuditLog::whereBetween('created_at', [$startDate, $endDate]);

        $report = [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'summary' => [
                'total_actions' => $query->count(),
                'unique_users' => $query->distinct('user_id')->count('user_id'),
                'sensitive_actions' => $query->whereIn('action', $this->sensitiveActions)->count(),
                'failed_actions' => $query->where('metadata->status', 'failed')->count(),
            ],
            'by_action' => $query->selectRaw('action, COUNT(*) as count')
                ->groupBy('action')
                ->orderByDesc('count')
                ->get(),
            'by_user' => $query->selectRaw('user_id, COUNT(*) as count')
                ->groupBy('user_id')
                ->with('user:id,name,email')
                ->orderByDesc('count')
                ->limit(20)
                ->get(),
            'risk_analysis' => $this->getRiskAnalysis($startDate, $endDate),
            'compliance_status' => $this->getComplianceStatus(),
            'recommendations' => $this->generateRecommendations($query),
        ];

        return $report;
    }

    /**
     * Get risk analysis for the period
     */
    protected function getRiskAnalysis(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'high_risk_users' => $this->getHighRiskUsers(),
            'suspicious_patterns' => $this->getSuspiciousPatterns($startDate, $endDate),
            'access_violations' => $this->getAccessViolations($startDate, $endDate),
            'unusual_activities' => $this->getUnusualActivities($startDate, $endDate),
        ];
    }

    /**
     * Get high-risk users
     */
    protected function getHighRiskUsers(): array
    {
        $keys = Cache::store('redis')->getStore()->getRedis()->keys('user_risk_score:*');
        $highRiskUsers = [];

        foreach ($keys as $key) {
            $score = Cache::get($key);
            if ($score >= 5) {
                $userId = str_replace('user_risk_score:', '', $key);
                $user = User::find($userId);
                if ($user) {
                    $highRiskUsers[] = [
                        'user_id' => $userId,
                        'name' => $user->name,
                        'risk_score' => $score,
                    ];
                }
            }
        }

        return $highRiskUsers;
    }

    /**
     * Get suspicious patterns
     */
    protected function getSuspiciousPatterns(Carbon $startDate, Carbon $endDate): array
    {
        // This would analyze patterns in the audit log
        return [
            'rapid_fire_actions' => AuditLog::whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('user_id, COUNT(*) as count')
                ->groupBy('user_id')
                ->havingRaw('COUNT(*) > 100')
                ->get(),
            'off_hours_access' => AuditLog::whereBetween('created_at', [$startDate, $endDate])
                ->whereRaw('HOUR(created_at) BETWEEN 23 AND 5')
                ->count(),
        ];
    }

    /**
     * Get access violations
     */
    protected function getAccessViolations(Carbon $startDate, Carbon $endDate): array
    {
        return AuditLog::whereBetween('created_at', [$startDate, $endDate])
            ->where('metadata->compliance_violation', true)
            ->get()
            ->toArray();
    }

    /**
     * Get unusual activities
     */
    protected function getUnusualActivities(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'new_locations' => AuditLog::whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('ip_address, COUNT(*) as count')
                ->groupBy('ip_address')
                ->havingRaw('COUNT(*) = 1')
                ->get(),
            'bulk_operations' => AuditLog::whereBetween('created_at', [$startDate, $endDate])
                ->where('action', 'like', '%bulk%')
                ->count(),
        ];
    }

    /**
     * Get overall compliance status
     */
    protected function getComplianceStatus(): array
    {
        return [
            'data_retention' => $this->checkDataRetentionCompliance(),
            'audit_completeness' => $this->checkAuditCompleteness(),
            'access_control' => $this->checkAccessControlCompliance(),
            'encryption' => $this->checkEncryptionCompliance(),
            'log_integrity' => $this->checkLogIntegrity(),
        ];
    }

    /**
     * Check data retention compliance
     */
    protected function checkDataRetentionCompliance(): array
    {
        $retentionDays = $this->complianceRequirements['data_retention_days'];
        $cutoffDate = now()->subDays($retentionDays);
        
        $oldRecords = AuditLog::where('created_at', '<', $cutoffDate)->count();
        
        return [
            'status' => $oldRecords === 0 ? 'compliant' : 'non_compliant',
            'old_records_count' => $oldRecords,
            'retention_period_days' => $retentionDays,
        ];
    }

    /**
     * Check audit completeness
     */
    protected function checkAuditCompleteness(): array
    {
        $totalRecords = AuditLog::count();
        $incompleteRecords = AuditLog::whereNull('user_id')
            ->orWhereNull('action')
            ->orWhereNull('ip_address')
            ->count();

        $completenessRate = $totalRecords > 0 ? 
            round((($totalRecords - $incompleteRecords) / $totalRecords) * 100, 2) : 100;

        return [
            'status' => $completenessRate >= 95 ? 'compliant' : 'non_compliant',
            'completeness_rate' => $completenessRate,
            'incomplete_records' => $incompleteRecords,
        ];
    }

    /**
     * Check access control compliance
     */
    protected function checkAccessControlCompliance(): array
    {
        $violations = AuditLog::where('metadata->access_violation', true)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        return [
            'status' => $violations === 0 ? 'compliant' : 'non_compliant',
            'violations_last_30_days' => $violations,
        ];
    }

    /**
     * Check encryption compliance
     */
    protected function checkEncryptionCompliance(): array
    {
        return [
            'status' => $this->complianceRequirements['encryption_required'] ? 'compliant' : 'non_compliant',
            'encryption_enabled' => $this->complianceRequirements['encryption_required'],
        ];
    }

    /**
     * Check log integrity
     */
    protected function checkLogIntegrity(): array
    {
        // Sample integrity check on recent records
        $recentRecords = AuditLog::orderByDesc('created_at')
            ->limit(100)
            ->get();

        $integrityViolations = 0;
        
        foreach ($recentRecords as $record) {
            if (!$this->verifyRecordIntegrity($record)) {
                $integrityViolations++;
            }
        }

        return [
            'status' => $integrityViolations === 0 ? 'compliant' : 'non_compliant',
            'integrity_violations' => $integrityViolations,
            'records_checked' => $recentRecords->count(),
        ];
    }

    /**
     * Verify record integrity
     */
    protected function verifyRecordIntegrity(AuditLog $record): bool
    {
        if (!$record->integrity_hash) {
            return false; // No hash to verify
        }

        $expectedHash = $this->generateIntegrityHash($record->toArray());
        
        return $record->integrity_hash === $expectedHash;
    }

    /**
     * Generate recommendations based on analysis
     */
    protected function generateRecommendations($query): array
    {
        $recommendations = [];

        // Check for high activity users
        $highActivityUsers = $query->selectRaw('user_id, COUNT(*) as count')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1000')
            ->count();

        if ($highActivityUsers > 0) {
            $recommendations[] = [
                'type' => 'security',
                'priority' => 'medium',
                'title' => 'Review High Activity Users',
                'description' => "Found {$highActivityUsers} users with unusually high activity",
                'action' => 'Review user activity patterns and verify legitimacy',
            ];
        }

        // Check for failed actions
        $failedActions = $query->where('metadata->status', 'failed')->count();
        
        if ($failedActions > 100) {
            $recommendations[] = [
                'type' => 'operational',
                'priority' => 'low',
                'title' => 'High Failed Action Rate',
                'description' => "Found {$failedActions} failed actions",
                'action' => 'Investigate causes of action failures and improve user training',
            ];
        }

        return $recommendations;
    }

    /**
     * Clean up old audit logs per retention policy
     */
    public function cleanupOldLogs(): int
    {
        $retentionDays = $this->complianceRequirements['data_retention_days'];
        $cutoffDate = now()->subDays($retentionDays);
        
        $deletedCount = AuditLog::where('created_at', '<', $cutoffDate)->delete();

        Log::info('Old audit logs cleaned up', [
            'deleted_count' => $deletedCount,
            'cutoff_date' => $cutoffDate,
        ]);

        return $deletedCount;
    }

    /**
     * Export audit logs for external compliance systems
     */
    public function exportAuditLogs(array $filters = []): string
    {
        $startDate = $filters['start_date'] ?? now()->subMonth();
        $endDate = $filters['end_date'] ?? now();
        
        $logs = AuditLog::whereBetween('created_at', [$startDate, $endDate])
            ->with('user:id,name,email')
            ->get();

        $exportData = $logs->map(function ($log) {
            return [
                'id' => $log->id,
                'timestamp' => $log->created_at->toISOString(),
                'user' => $log->user?->name ?? 'System',
                'action' => $log->action,
                'resource' => $log->resource_type,
                'ip_address' => $log->ip_address,
                'success' => $log->metadata['status'] ?? 'unknown',
                'integrity_hash' => $log->integrity_hash,
            ];
        });

        $filename = 'audit_export_' . now()->format('Y-m-d_H-i-s') . '.json';
        $filepath = storage_path('app/exports/' . $filename);

        file_put_contents($filepath, json_encode($exportData, JSON_PRETTY_PRINT));

        return $filepath;
    }
}