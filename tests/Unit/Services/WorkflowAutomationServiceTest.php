<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\WorkflowAutomationService;
use App\Services\ValidationWorkflowService;
use App\Services\IntelligentFraudDetectionService;
use App\Services\PredictiveAnalyticsService;
use App\Services\NotificationService;
use App\Models\Tindakan;
use App\Models\PendapatanHarian;
use App\Models\PengeluaranHarian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Mockery;

/**
 * Comprehensive test suite for WorkflowAutomationService
 * Tests smart validation, ML-powered decisions, and automation features
 */
class WorkflowAutomationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected WorkflowAutomationService $service;
    protected ValidationWorkflowService $mockValidationService;
    protected IntelligentFraudDetectionService $mockFraudDetection;
    protected PredictiveAnalyticsService $mockPredictiveAnalytics;
    protected NotificationService $mockNotificationService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock services
        $this->mockValidationService = Mockery::mock(ValidationWorkflowService::class);
        $this->mockFraudDetection = Mockery::mock(IntelligentFraudDetectionService::class);
        $this->mockPredictiveAnalytics = Mockery::mock(PredictiveAnalyticsService::class);
        $this->mockNotificationService = Mockery::mock(NotificationService::class);

        // Initialize service with mocks
        $this->service = new WorkflowAutomationService(
            $this->mockValidationService,
            $this->mockFraudDetection,
            $this->mockPredictiveAnalytics,
            $this->mockNotificationService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_process_smart_validation_with_auto_approval()
    {
        // Arrange
        $user = User::factory()->create();
        Auth::login($user);

        $tindakan = Tindakan::factory()->create([
            'tarif' => 50000, // Below auto-approval threshold
            'input_by' => $user->id,
            'status' => 'pending'
        ]);

        // Mock fraud detection to return low risk
        $this->mockFraudDetection
            ->shouldReceive('analyzeTransaction')
            ->once()
            ->with($tindakan)
            ->andReturn([
                'risk_score' => 0.05,
                'indicators' => []
            ]);

        // Mock validation service for auto-approval
        $this->mockValidationService
            ->shouldReceive('approve')
            ->once()
            ->with($tindakan, Mockery::type('array'))
            ->andReturn([
                'success' => true,
                'status' => 'approved',
                'message' => 'Auto-approved successfully'
            ]);

        // Act
        $result = $this->service->processSmartValidation($tindakan);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('approved', $result['status']);
        $this->assertTrue($result['auto_approved']);
    }

    /** @test */
    public function it_can_detect_and_reject_fraudulent_transactions()
    {
        // Arrange
        $user = User::factory()->create();
        Auth::login($user);

        $tindakan = Tindakan::factory()->create([
            'tarif' => 5000000, // High amount
            'input_by' => $user->id,
            'status' => 'pending'
        ]);

        // Mock fraud detection to return high risk
        $this->mockFraudDetection
            ->shouldReceive('analyzeTransaction')
            ->once()
            ->with($tindakan)
            ->andReturn([
                'risk_score' => 0.9, // 90% fraud probability
                'indicators' => ['unusual_amount', 'off_hours_transaction']
            ]);

        // Mock validation service for auto-rejection
        $this->mockValidationService
            ->shouldReceive('reject')
            ->once()
            ->with($tindakan, Mockery::type('string'), Mockery::type('array'))
            ->andReturn([
                'success' => true,
                'status' => 'rejected',
                'message' => 'Auto-rejected due to fraud risk'
            ]);

        // Act
        $result = $this->service->processSmartValidation($tindakan);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('rejected', $result['status']);
        $this->assertArrayHasKey('fraud_indicators', $result);
    }

    /** @test */
    public function it_can_calculate_priority_scores_correctly()
    {
        // Arrange
        $user = User::factory()->create();
        Auth::login($user);

        // High priority transaction (high amount, old)
        $highPriorityTransaction = Tindakan::factory()->create([
            'tarif' => 2000000,
            'created_at' => now()->subHours(25), // Over 24 hours old
            'input_by' => $user->id
        ]);

        // Low priority transaction (small amount, recent)
        $lowPriorityTransaction = Tindakan::factory()->create([
            'tarif' => 50000,
            'created_at' => now()->subMinutes(30),
            'input_by' => $user->id
        ]);

        // Mock fraud detection for both
        $this->mockFraudDetection
            ->shouldReceive('analyzeTransaction')
            ->twice()
            ->andReturn(['risk_score' => 0.1, 'indicators' => []]);

        // Act
        $highPriorityResult = $this->service->processSmartValidation($highPriorityTransaction);
        $lowPriorityResult = $this->service->processSmartValidation($lowPriorityTransaction);

        // Assert
        $this->assertGreaterThan(
            $lowPriorityResult['priority_score'] ?? 0,
            $highPriorityResult['priority_score'] ?? 0
        );
    }

    /** @test */
    public function it_can_handle_bulk_operations_efficiently()
    {
        // Arrange
        $user = User::factory()->create();
        Auth::login($user);

        $recordIds = [1, 2, 3, 4, 5];
        $action = 'approve';
        $options = [
            'model_class' => Tindakan::class,
            'reason' => 'Bulk approval test'
        ];

        // Mock individual approvals
        $this->mockValidationService
            ->shouldReceive('approve')
            ->times(5)
            ->andReturn([
                'success' => true,
                'status' => 'approved'
            ]);

        // Create test records
        foreach ($recordIds as $id) {
            Tindakan::factory()->create([
                'id' => $id,
                'input_by' => $user->id,
                'status' => 'pending'
            ]);
        }

        // Act
        $result = $this->service->optimizedBulkProcess($recordIds, $action, $options);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(5, $result['processed_count']);
        $this->assertTrue($result['optimization_applied']);
    }

    /** @test */
    public function it_can_process_notification_bundling()
    {
        // Arrange
        $user = User::factory()->create();
        Auth::login($user);

        // Mock notification service
        $this->mockNotificationService
            ->shouldReceive('getBundleableNotifications')
            ->once()
            ->andReturn([
                ['id' => 1, 'type' => 'validation_submitted', 'user_id' => $user->id],
                ['id' => 2, 'type' => 'validation_submitted', 'user_id' => $user->id],
                ['id' => 3, 'type' => 'validation_approved', 'user_id' => $user->id],
            ]);

        $this->mockNotificationService
            ->shouldReceive('sendBundledNotification')
            ->once()
            ->andReturn(true);

        // Act
        $result = $this->service->processNotificationBundle();

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('bundled', $result);
        $this->assertArrayHasKey('sent', $result);
    }

    /** @test */
    public function it_can_apply_workflow_templates()
    {
        // Arrange
        $user = User::factory()->create();
        Auth::login($user);

        $tindakan = Tindakan::factory()->create([
            'tarif' => 150000, // Medium amount for routine template
            'input_by' => $user->id,
            'status' => 'pending'
        ]);

        // Act
        $result = $this->service->applyWorkflowTemplate($tindakan, 'routine_medical');

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('applied', $result);
    }

    /** @test */
    public function it_can_get_automation_statistics()
    {
        // Arrange
        $user = User::factory()->create();
        Auth::login($user);

        // Act
        $stats = $this->service->getAutomationStats();

        // Assert
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('auto_approval_rate', $stats);
        $this->assertArrayHasKey('processing_time_improvement', $stats);
        $this->assertArrayHasKey('notification_efficiency', $stats);
        $this->assertArrayHasKey('fraud_detection_accuracy', $stats);
        $this->assertArrayHasKey('overall_automation_score', $stats);

        // Verify all values are numeric and within expected ranges
        $this->assertIsFloat($stats['auto_approval_rate']);
        $this->assertGreaterThanOrEqual(0, $stats['auto_approval_rate']);
        $this->assertLessThanOrEqual(1, $stats['auto_approval_rate']);
    }

    /** @test */
    public function it_handles_errors_gracefully_in_smart_validation()
    {
        // Arrange
        $user = User::factory()->create();
        Auth::login($user);

        $tindakan = Tindakan::factory()->create([
            'input_by' => $user->id,
            'status' => 'pending'
        ]);

        // Mock fraud detection to throw exception
        $this->mockFraudDetection
            ->shouldReceive('analyzeTransaction')
            ->once()
            ->with($tindakan)
            ->andThrow(new \Exception('Fraud detection service unavailable'));

        // Mock fallback to standard validation
        $this->mockValidationService
            ->shouldReceive('submitForValidation')
            ->once()
            ->with($tindakan, Mockery::type('array'))
            ->andReturn([
                'success' => true,
                'status' => 'pending',
                'message' => 'Fallback validation successful'
            ]);

        // Act
        $result = $this->service->processSmartValidation($tindakan);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('pending', $result['status']);
    }

    /** @test */
    public function it_can_learn_from_validation_decisions()
    {
        // Arrange
        $user = User::factory()->create();
        Auth::login($user);

        $tindakan = Tindakan::factory()->create([
            'tarif' => 100000,
            'input_by' => $user->id,
            'status' => 'pending'
        ]);

        // Mock fraud detection
        $this->mockFraudDetection
            ->shouldReceive('analyzeTransaction')
            ->once()
            ->andReturn(['risk_score' => 0.3, 'indicators' => []]);

        // Mock successful validation
        $this->mockValidationService
            ->shouldReceive('submitForValidation')
            ->once()
            ->andReturn([
                'success' => true,
                'status' => 'pending',
                'message' => 'Submitted for manual review'
            ]);

        // Act
        $result = $this->service->processSmartValidation($tindakan);

        // Assert
        $this->assertTrue($result['success']);
        
        // Verify learning data is cached for ML training
        $this->assertTrue(\Cache::has('learning_data_' . $tindakan->id . '_*'));
    }

    /** @test */
    public function it_respects_automation_rules_configuration()
    {
        // Test that automation rules can be enabled/disabled
        $service = new WorkflowAutomationService(
            $this->mockValidationService,
            $this->mockFraudDetection,
            $this->mockPredictiveAnalytics,
            $this->mockNotificationService
        );

        // Reflection to access protected property
        $reflection = new \ReflectionClass($service);
        $rulesProperty = $reflection->getProperty('automationRules');
        $rulesProperty->setAccessible(true);
        $rules = $rulesProperty->getValue($service);

        // Assert default configuration
        $this->assertTrue($rules['smart_auto_approval']['enabled']);
        $this->assertEquals(0.85, $rules['smart_auto_approval']['ml_threshold']);
        $this->assertTrue($rules['notification_bundling']['enabled']);
        $this->assertEquals(300, $rules['notification_bundling']['bundle_interval']);
    }

    /** @test */
    public function it_validates_required_fields_before_processing()
    {
        // Arrange
        $user = User::factory()->create();
        Auth::login($user);

        // Create record with missing required fields
        $tindakan = new Tindakan([
            'input_by' => $user->id,
            // Missing required fields: jenis_tindakan_id, pasien_id, tanggal_tindakan, tarif
        ]);

        // Expect validation to fail
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Missing required fields');

        // Act
        $this->service->processSmartValidation($tindakan);
    }

    /** @test */
    public function it_calculates_risk_scores_accurately()
    {
        // Test risk score calculation for various scenarios
        $testCases = [
            [
                'amount' => 100000,
                'hour' => 10, // Business hours
                'expected_risk_range' => [0.0, 0.4]
            ],
            [
                'amount' => 5000000,
                'hour' => 2, // Off hours
                'expected_risk_range' => [0.5, 1.0]
            ],
            [
                'amount' => 500000,
                'hour' => 14, // Business hours
                'expected_risk_range' => [0.1, 0.6]
            ]
        ];

        foreach ($testCases as $case) {
            $user = User::factory()->create();
            $tindakan = Tindakan::factory()->create([
                'tarif' => $case['amount'],
                'created_at' => now()->setHour($case['hour']),
                'input_by' => $user->id
            ]);

            // Use reflection to test protected method
            $reflection = new \ReflectionClass($this->service);
            $method = $reflection->getMethod('calculateRiskScore');
            $method->setAccessible(true);

            $riskScore = $method->invoke($this->service, $tindakan);

            $this->assertGreaterThanOrEqual(
                $case['expected_risk_range'][0],
                $riskScore,
                "Risk score {$riskScore} is below expected minimum {$case['expected_risk_range'][0]} for amount {$case['amount']} at hour {$case['hour']}"
            );

            $this->assertLessThanOrEqual(
                $case['expected_risk_range'][1],
                $riskScore,
                "Risk score {$riskScore} is above expected maximum {$case['expected_risk_range'][1]} for amount {$case['amount']} at hour {$case['hour']}"
            );
        }
    }
}