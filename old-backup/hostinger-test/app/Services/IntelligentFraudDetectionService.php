<?php

namespace App\Services;

use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use App\Models\Tindakan;
use App\Models\User;
use App\Models\AuditLog;
use App\Services\RealTimeNotificationService;
use App\Services\AdvancedAuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Exception;

class IntelligentFraudDetectionService
{
    protected RealTimeNotificationService $notificationService;
    protected AdvancedAuditService $auditService;
    
    protected array $fraudPatterns = [
        'amount_anomaly' => [
            'threshold_multiplier' => 5, // 5x normal amount
            'confidence_threshold' => 0.8,
        ],
        'frequency_anomaly' => [
            'normal_frequency_days' => 7,
            'threshold_multiplier' => 10,
        ],
        'time_pattern_anomaly' => [
            'unusual_hours' => [22, 23, 0, 1, 2, 3, 4, 5],
            'threshold_count' => 5,
        ],
        'location_anomaly' => [
            'max_locations_per_day' => 3,
            'suspicious_country_codes' => ['CN', 'RU', 'IR', 'KP'],
        ],
        'behavioral_anomaly' => [
            'pattern_window_days' => 30,
            'deviation_threshold' => 2.5, // Standard deviations
        ],
    ];

    protected array $riskScoreWeights = [
        'amount_anomaly' => 30,
        'frequency_anomaly' => 25,
        'time_pattern_anomaly' => 15,
        'location_anomaly' => 20,
        'behavioral_anomaly' => 10,
    ];

    public function __construct(
        RealTimeNotificationService $notificationService,
        AdvancedAuditService $auditService
    ) {
        $this->notificationService = $notificationService;
        $this->auditService = $auditService;
    }

    /**
     * Perform comprehensive fraud detection analysis
     */
    public function performFraudAnalysis(array $data): array
    {
        $startTime = microtime(true);
        
        try {
            $analysisResults = [
                'transaction_id' => $data['transaction_id'] ?? null,
                'user_id' => $data['user_id'] ?? null,
                'analysis_timestamp' => now(),
                'risk_score' => 0,
                'fraud_probability' => 0,
                'detected_patterns' => [],
                'recommendations' => [],
                'requires_manual_review' => false,
            ];

            // Run multiple fraud detection algorithms
            $patterns = [
                'amount_anomaly' => $this->detectAmountAnomalies($data),
                'frequency_anomaly' => $this->detectFrequencyAnomalies($data),
                'time_pattern_anomaly' => $this->detectTimePatternAnomalies($data),
                'location_anomaly' => $this->detectLocationAnomalies($data),
                'behavioral_anomaly' => $this->detectBehavioralAnomalies($data),
                'duplicate_detection' => $this->detectDuplicateTransactions($data),
                'velocity_checks' => $this->performVelocityChecks($data),
                'network_analysis' => $this->performNetworkAnalysis($data),
            ];

            // Calculate composite risk score
            $analysisResults['risk_score'] = $this->calculateRiskScore($patterns);
            $analysisResults['fraud_probability'] = $this->calculateFraudProbability($patterns);
            $analysisResults['detected_patterns'] = array_filter($patterns, fn($p) => $p['detected']);
            
            // Generate recommendations
            $analysisResults['recommendations'] = $this->generateRecommendations($analysisResults);
            
            // Determine if manual review is required
            $analysisResults['requires_manual_review'] = $this->requiresManualReview($analysisResults);

            // Store analysis results
            $this->storeAnalysisResults($analysisResults);

            // Send alerts if necessary
            $this->sendFraudAlerts($analysisResults);

            // Log performance
            Log::info('Fraud analysis completed', [
                'analysis_time' => round(microtime(true) - $startTime, 3),
                'risk_score' => $analysisResults['risk_score'],
                'patterns_detected' => count($analysisResults['detected_patterns']),
            ]);

            return $analysisResults;

        } catch (Exception $e) {
            Log::error('Fraud analysis failed', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            return [
                'error' => true,
                'message' => 'Fraud analysis failed',
                'risk_score' => 100, // High risk on failure
            ];
        }
    }

    /**
     * Detect amount anomalies using statistical analysis
     */
    protected function detectAmountAnomalies(array $data): array
    {
        $result = ['detected' => false, 'confidence' => 0, 'details' => []];
        
        if (!isset($data['amount']) || !isset($data['user_id'])) {
            return $result;
        }

        // Get user's historical transaction amounts
        $historicalAmounts = $this->getUserHistoricalAmounts($data['user_id'], $data['type'] ?? 'pendapatan');
        
        if (count($historicalAmounts) < 5) {
            return $result; // Not enough data
        }

        // Calculate statistical measures
        $mean = array_sum($historicalAmounts) / count($historicalAmounts);
        $variance = array_sum(array_map(fn($x) => pow($x - $mean, 2), $historicalAmounts)) / count($historicalAmounts);
        $stdDev = sqrt($variance);

        // Z-score calculation
        $zScore = $stdDev > 0 ? abs($data['amount'] - $mean) / $stdDev : 0;
        
        // Detect anomaly
        $threshold = $this->fraudPatterns['amount_anomaly']['threshold_multiplier'];
        $confidenceThreshold = $this->fraudPatterns['amount_anomaly']['confidence_threshold'];

        if ($zScore > $threshold) {
            $result['detected'] = true;
            $result['confidence'] = min(1.0, $zScore / 10); // Normalize confidence
            $result['details'] = [
                'z_score' => round($zScore, 2),
                'mean_amount' => round($mean, 2),
                'std_deviation' => round($stdDev, 2),
                'current_amount' => $data['amount'],
                'deviation_factor' => round($data['amount'] / $mean, 2),
            ];
        }

        return $result;
    }

    /**
     * Detect frequency anomalies in transaction patterns
     */
    protected function detectFrequencyAnomalies(array $data): array
    {
        $result = ['detected' => false, 'confidence' => 0, 'details' => []];
        
        if (!isset($data['user_id'])) {
            return $result;
        }

        $userId = $data['user_id'];
        $windowDays = $this->fraudPatterns['frequency_anomaly']['normal_frequency_days'];
        
        // Get recent transaction frequency
        $recentTransactions = $this->getUserRecentTransactions($userId, $windowDays);
        $currentFrequency = count($recentTransactions);
        
        // Get historical average frequency
        $historicalFrequency = $this->getUserAverageFrequency($userId, 30);
        
        if ($historicalFrequency == 0) {
            return $result;
        }

        $frequencyRatio = $currentFrequency / $historicalFrequency;
        $threshold = $this->fraudPatterns['frequency_anomaly']['threshold_multiplier'];

        if ($frequencyRatio > $threshold) {
            $result['detected'] = true;
            $result['confidence'] = min(1.0, $frequencyRatio / ($threshold * 2));
            $result['details'] = [
                'current_frequency' => $currentFrequency,
                'historical_average' => round($historicalFrequency, 2),
                'frequency_ratio' => round($frequencyRatio, 2),
                'window_days' => $windowDays,
            ];
        }

        return $result;
    }

    /**
     * Detect time pattern anomalies
     */
    protected function detectTimePatternAnomalies(array $data): array
    {
        $result = ['detected' => false, 'confidence' => 0, 'details' => []];
        
        if (!isset($data['timestamp']) || !isset($data['user_id'])) {
            return $result;
        }

        $hour = Carbon::parse($data['timestamp'])->hour;
        $unusualHours = $this->fraudPatterns['time_pattern_anomaly']['unusual_hours'];
        
        if (!in_array($hour, $unusualHours)) {
            return $result;
        }

        // Check user's historical pattern
        $userPattern = $this->getUserTimePattern($data['user_id']);
        $normalHourTransactions = $userPattern['normal_hours'] ?? 0;
        $unusualHourTransactions = $userPattern['unusual_hours'] ?? 0;
        
        if ($normalHourTransactions > 0) {
            $unusualRatio = $unusualHourTransactions / $normalHourTransactions;
            
            if ($unusualRatio < 0.1) { // Less than 10% of transactions usually at this time
                $result['detected'] = true;
                $result['confidence'] = 1.0 - $unusualRatio;
                $result['details'] = [
                    'transaction_hour' => $hour,
                    'unusual_hour_ratio' => round($unusualRatio, 3),
                    'normal_hours_count' => $normalHourTransactions,
                    'unusual_hours_count' => $unusualHourTransactions,
                ];
            }
        }

        return $result;
    }

    /**
     * Detect location anomalies
     */
    protected function detectLocationAnomalies(array $data): array
    {
        $result = ['detected' => false, 'confidence' => 0, 'details' => []];
        
        if (!isset($data['ip_address']) || !isset($data['user_id'])) {
            return $result;
        }

        $ipInfo = $this->getIPGeolocation($data['ip_address']);
        
        if (!$ipInfo) {
            return $result;
        }

        // Check for suspicious countries
        $suspiciousCountries = $this->fraudPatterns['location_anomaly']['suspicious_country_codes'];
        
        if (in_array($ipInfo['country_code'], $suspiciousCountries)) {
            $result['detected'] = true;
            $result['confidence'] = 0.9;
            $result['details'] = [
                'country' => $ipInfo['country'],
                'country_code' => $ipInfo['country_code'],
                'city' => $ipInfo['city'],
                'reason' => 'suspicious_country',
            ];
            return $result;
        }

        // Check for unusual location for user
        $userLocations = $this->getUserHistoricalLocations($data['user_id']);
        $isNewLocation = !in_array($ipInfo['country_code'], array_column($userLocations, 'country_code'));
        
        if ($isNewLocation && count($userLocations) > 0) {
            $result['detected'] = true;
            $result['confidence'] = 0.7;
            $result['details'] = [
                'country' => $ipInfo['country'],
                'country_code' => $ipInfo['country_code'],
                'historical_countries' => array_unique(array_column($userLocations, 'country')),
                'reason' => 'new_location',
            ];
        }

        return $result;
    }

    /**
     * Detect behavioral anomalies using machine learning approaches
     */
    protected function detectBehavioralAnomalies(array $data): array
    {
        $result = ['detected' => false, 'confidence' => 0, 'details' => []];
        
        if (!isset($data['user_id'])) {
            return $result;
        }

        // Get user behavioral profile
        $profile = $this->getUserBehavioralProfile($data['user_id']);
        
        if (!$profile || count($profile['features']) < 5) {
            return $result; // Not enough behavioral data
        }

        // Calculate behavioral deviation
        $currentBehavior = $this->extractBehavioralFeatures($data);
        $deviationScore = $this->calculateBehavioralDeviation($profile['features'], $currentBehavior);
        
        $threshold = $this->fraudPatterns['behavioral_anomaly']['deviation_threshold'];
        
        if ($deviationScore > $threshold) {
            $result['detected'] = true;
            $result['confidence'] = min(1.0, $deviationScore / ($threshold * 2));
            $result['details'] = [
                'deviation_score' => round($deviationScore, 2),
                'threshold' => $threshold,
                'anomalous_features' => $this->identifyAnomalousFeatures($profile['features'], $currentBehavior),
            ];
        }

        return $result;
    }

    /**
     * Detect duplicate transactions
     */
    protected function detectDuplicateTransactions(array $data): array
    {
        $result = ['detected' => false, 'confidence' => 0, 'details' => []];
        
        if (!isset($data['amount']) || !isset($data['user_id'])) {
            return $result;
        }

        // Look for similar transactions in recent history
        $threshold = now()->subHours(24);
        $similarTransactions = $this->findSimilarTransactions($data, $threshold);
        
        if (count($similarTransactions) > 0) {
            $result['detected'] = true;
            $result['confidence'] = min(1.0, count($similarTransactions) / 5);
            $result['details'] = [
                'similar_count' => count($similarTransactions),
                'time_window' => '24 hours',
                'similar_transactions' => array_map(function($t) {
                    return [
                        'id' => $t['id'],
                        'amount' => $t['amount'],
                        'created_at' => $t['created_at'],
                    ];
                }, $similarTransactions),
            ];
        }

        return $result;
    }

    /**
     * Perform velocity checks
     */
    protected function performVelocityChecks(array $data): array
    {
        $result = ['detected' => false, 'confidence' => 0, 'details' => []];
        
        if (!isset($data['user_id']) || !isset($data['amount'])) {
            return $result;
        }

        $userId = $data['user_id'];
        $velocityChecks = [
            'hourly' => $this->checkVelocity($userId, 1, 5), // 5 transactions per hour
            'daily' => $this->checkVelocity($userId, 24, 50), // 50 transactions per day
            'amount_hourly' => $this->checkAmountVelocity($userId, 1, 10000000), // 10M per hour
            'amount_daily' => $this->checkAmountVelocity($userId, 24, 50000000), // 50M per day
        ];

        $violations = array_filter($velocityChecks, fn($check) => $check['violated']);
        
        if (!empty($violations)) {
            $result['detected'] = true;
            $result['confidence'] = count($violations) / 4; // Normalize by number of checks
            $result['details'] = [
                'violations' => $violations,
                'velocity_checks' => $velocityChecks,
            ];
        }

        return $result;
    }

    /**
     * Perform network analysis for connected fraud
     */
    protected function performNetworkAnalysis(array $data): array
    {
        $result = ['detected' => false, 'confidence' => 0, 'details' => []];
        
        if (!isset($data['user_id'])) {
            return $result;
        }

        // Check for connected suspicious users
        $suspiciousConnections = $this->findSuspiciousConnections($data['user_id']);
        
        if (!empty($suspiciousConnections)) {
            $result['detected'] = true;
            $result['confidence'] = min(1.0, count($suspiciousConnections) / 3);
            $result['details'] = [
                'suspicious_connections' => $suspiciousConnections,
                'connection_types' => ['shared_ip', 'similar_patterns', 'temporal_correlation'],
            ];
        }

        return $result;
    }

    /**
     * Calculate composite risk score
     */
    protected function calculateRiskScore(array $patterns): int
    {
        $totalScore = 0;
        
        foreach ($patterns as $patternType => $pattern) {
            if ($pattern['detected']) {
                $weight = $this->riskScoreWeights[$patternType] ?? 10;
                $confidence = $pattern['confidence'] ?? 0.5;
                $totalScore += $weight * $confidence;
            }
        }

        return min(100, round($totalScore));
    }

    /**
     * Calculate fraud probability using ensemble method
     */
    protected function calculateFraudProbability(array $patterns): float
    {
        $detectedPatterns = array_filter($patterns, fn($p) => $p['detected']);
        
        if (empty($detectedPatterns)) {
            return 0.0;
        }

        // Weighted average of confidences
        $totalWeight = 0;
        $weightedSum = 0;
        
        foreach ($detectedPatterns as $patternType => $pattern) {
            $weight = $this->riskScoreWeights[$patternType] ?? 10;
            $confidence = $pattern['confidence'] ?? 0.5;
            
            $totalWeight += $weight;
            $weightedSum += $weight * $confidence;
        }

        $probability = $totalWeight > 0 ? $weightedSum / $totalWeight : 0;
        
        // Apply ensemble correction
        $patternCount = count($detectedPatterns);
        $ensembleBoost = min(0.3, $patternCount * 0.1); // Boost for multiple patterns
        
        return min(1.0, $probability + $ensembleBoost);
    }

    /**
     * Generate recommendations based on analysis
     */
    protected function generateRecommendations(array $analysisResults): array
    {
        $recommendations = [];
        $riskScore = $analysisResults['risk_score'];
        $detectedPatterns = $analysisResults['detected_patterns'];

        if ($riskScore >= 80) {
            $recommendations[] = [
                'priority' => 'critical',
                'action' => 'immediate_block',
                'description' => 'Block transaction immediately and require manual review',
            ];
        } elseif ($riskScore >= 60) {
            $recommendations[] = [
                'priority' => 'high',
                'action' => 'manual_review',
                'description' => 'Route to manual review queue for investigation',
            ];
        } elseif ($riskScore >= 40) {
            $recommendations[] = [
                'priority' => 'medium',
                'action' => 'additional_verification',
                'description' => 'Request additional verification from user',
            ];
        }

        // Pattern-specific recommendations
        foreach ($detectedPatterns as $patternType => $pattern) {
            switch ($patternType) {
                case 'amount_anomaly':
                    $recommendations[] = [
                        'priority' => 'medium',
                        'action' => 'verify_amount',
                        'description' => 'Verify transaction amount with supporting documentation',
                    ];
                    break;
                
                case 'location_anomaly':
                    $recommendations[] = [
                        'priority' => 'medium',
                        'action' => 'location_verification',
                        'description' => 'Verify user location and identity',
                    ];
                    break;
                
                case 'frequency_anomaly':
                    $recommendations[] = [
                        'priority' => 'low',
                        'action' => 'pattern_monitoring',
                        'description' => 'Monitor user transaction patterns for 7 days',
                    ];
                    break;
            }
        }

        return array_unique($recommendations, SORT_REGULAR);
    }

    /**
     * Determine if manual review is required
     */
    protected function requiresManualReview(array $analysisResults): bool
    {
        $riskScore = $analysisResults['risk_score'];
        $fraudProbability = $analysisResults['fraud_probability'];
        $criticalPatterns = ['duplicate_detection', 'location_anomaly', 'behavioral_anomaly'];
        
        // High risk score requires manual review
        if ($riskScore >= 70) {
            return true;
        }
        
        // High fraud probability requires manual review
        if ($fraudProbability >= 0.8) {
            return true;
        }
        
        // Critical patterns detected
        $detectedPatterns = array_keys($analysisResults['detected_patterns']);
        if (array_intersect($detectedPatterns, $criticalPatterns)) {
            return true;
        }
        
        return false;
    }

    /**
     * Store analysis results for learning and auditing
     */
    protected function storeAnalysisResults(array $analysisResults): void
    {
        // Store in cache for quick access
        $cacheKey = "fraud_analysis:{$analysisResults['transaction_id']}";
        Cache::put($cacheKey, $analysisResults, now()->addDays(30));
        
        // Store in audit log
        $this->auditService->recordAuditTrail([
            'action' => 'fraud_analysis_completed',
            'resource_type' => 'transaction',
            'resource_id' => $analysisResults['transaction_id'],
            'metadata' => [
                'risk_score' => $analysisResults['risk_score'],
                'fraud_probability' => $analysisResults['fraud_probability'],
                'patterns_detected' => count($analysisResults['detected_patterns']),
                'requires_manual_review' => $analysisResults['requires_manual_review'],
            ],
        ]);
    }

    /**
     * Send fraud alerts based on analysis results
     */
    protected function sendFraudAlerts(array $analysisResults): void
    {
        $riskScore = $analysisResults['risk_score'];
        
        if ($riskScore >= 80) {
            $this->notificationService->sendNotification([
                'title' => 'Fraud Alert - Critical Risk',
                'message' => "Transaction with risk score {$riskScore} detected",
                'urgency' => 'critical',
                'category' => 'security',
                'metadata' => $analysisResults,
                'recipient' => 'fraud_team',
            ]);
        } elseif ($riskScore >= 60) {
            $this->notificationService->sendNotification([
                'title' => 'Fraud Alert - High Risk',
                'message' => "Suspicious transaction detected with risk score {$riskScore}",
                'urgency' => 'high',
                'category' => 'security',
                'metadata' => $analysisResults,
                'recipient' => 'security_team',
            ]);
        }
    }

    // Helper methods for data retrieval and analysis

    protected function getUserHistoricalAmounts(int $userId, string $type): array
    {
        $model = $type === 'pendapatan' ? Pendapatan::class : Pengeluaran::class;
        
        return $model::where('input_by', $userId)
            ->where('created_at', '>=', now()->subDays(90))
            ->pluck('nominal')
            ->toArray();
    }

    protected function getUserRecentTransactions(int $userId, int $days): array
    {
        return DB::table('pendapatan')
            ->select('id', 'nominal', 'created_at', DB::raw("'pendapatan' as type"))
            ->where('input_by', $userId)
            ->where('created_at', '>=', now()->subDays($days))
            ->union(
                DB::table('pengeluaran')
                    ->select('id', 'nominal', 'created_at', DB::raw("'pengeluaran' as type"))
                    ->where('input_by', $userId)
                    ->where('created_at', '>=', now()->subDays($days))
            )
            ->get()
            ->toArray();
    }

    protected function getUserAverageFrequency(int $userId, int $days): float
    {
        $transactions = $this->getUserRecentTransactions($userId, $days);
        
        if (empty($transactions)) {
            return 0;
        }
        
        return count($transactions) / $days;
    }

    protected function getUserTimePattern(int $userId): array
    {
        $transactions = AuditLog::where('user_id', $userId)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->get();

        $normalHours = [8, 9, 10, 11, 12, 13, 14, 15, 16, 17]; // Business hours
        $normalCount = 0;
        $unusualCount = 0;

        foreach ($transactions as $transaction) {
            if (in_array($transaction->hour, $normalHours)) {
                $normalCount += $transaction->count;
            } else {
                $unusualCount += $transaction->count;
            }
        }

        return [
            'normal_hours' => $normalCount,
            'unusual_hours' => $unusualCount,
        ];
    }

    protected function getIPGeolocation(string $ipAddress): ?array
    {
        // Mock implementation - in real world, use a geolocation service
        $mockData = [
            '127.0.0.1' => ['country' => 'Indonesia', 'country_code' => 'ID', 'city' => 'Jakarta'],
            '192.168.1.1' => ['country' => 'Indonesia', 'country_code' => 'ID', 'city' => 'Surabaya'],
        ];

        return $mockData[$ipAddress] ?? ['country' => 'Unknown', 'country_code' => 'XX', 'city' => 'Unknown'];
    }

    protected function getUserHistoricalLocations(int $userId): array
    {
        return AuditLog::where('user_id', $userId)
            ->where('created_at', '>=', now()->subDays(90))
            ->whereNotNull('ip_address')
            ->select('ip_address')
            ->distinct()
            ->get()
            ->map(function ($log) {
                return $this->getIPGeolocation($log->ip_address);
            })
            ->filter()
            ->unique('country_code')
            ->toArray();
    }

    protected function getUserBehavioralProfile(int $userId): ?array
    {
        // Mock implementation - in real world, use ML models
        return [
            'features' => [
                'avg_amount' => 1000000,
                'avg_frequency' => 2.5,
                'preferred_hours' => [9, 10, 11, 14, 15, 16],
                'session_duration' => 25.5,
            ],
        ];
    }

    protected function extractBehavioralFeatures(array $data): array
    {
        return [
            'amount' => $data['amount'] ?? 0,
            'hour' => Carbon::parse($data['timestamp'] ?? now())->hour,
            'session_duration' => $data['session_duration'] ?? 0,
        ];
    }

    protected function calculateBehavioralDeviation(array $profile, array $current): float
    {
        // Simplified deviation calculation
        $deviations = [];
        
        if (isset($profile['avg_amount']) && isset($current['amount'])) {
            $deviations[] = abs($current['amount'] - $profile['avg_amount']) / $profile['avg_amount'];
        }

        return empty($deviations) ? 0 : array_sum($deviations) / count($deviations);
    }

    protected function identifyAnomalousFeatures(array $profile, array $current): array
    {
        $anomalous = [];
        
        if (isset($profile['preferred_hours']) && isset($current['hour'])) {
            if (!in_array($current['hour'], $profile['preferred_hours'])) {
                $anomalous[] = 'unusual_hour';
            }
        }

        return $anomalous;
    }

    protected function findSimilarTransactions(array $data, Carbon $threshold): array
    {
        // Mock implementation
        return [];
    }

    protected function checkVelocity(int $userId, int $hours, int $limit): array
    {
        $count = AuditLog::where('user_id', $userId)
            ->where('created_at', '>=', now()->subHours($hours))
            ->count();

        return [
            'count' => $count,
            'limit' => $limit,
            'violated' => $count > $limit,
        ];
    }

    protected function checkAmountVelocity(int $userId, int $hours, float $limit): array
    {
        $total = DB::table('pendapatan')
            ->where('input_by', $userId)
            ->where('created_at', '>=', now()->subHours($hours))
            ->sum('nominal');

        return [
            'total' => $total,
            'limit' => $limit,
            'violated' => $total > $limit,
        ];
    }

    protected function findSuspiciousConnections(int $userId): array
    {
        // Mock implementation - in real world, use graph analysis
        return [];
    }
}