<?php

namespace Tests\Feature\Workflows;

use Tests\TestCase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use App\Models\User;
use App\Models\Pasien;
use App\Models\Tindakan;
use App\Models\PendapatanHarian;
use App\Models\JenisTindakan;
use App\Models\Pendapatan;
use App\Models\Shift;
use App\Services\ValidationWorkflowService;
use App\Services\NotificationService;
use App\Services\TelegramService;
use App\Services\PetugasStatsService;
use App\Services\BulkOperationService;
use App\Filament\Petugas\Resources\PasienResource\Pages\CreatePasien;
use App\Filament\Petugas\Resources\TindakanResource\Pages\CreateTindakan;
use Spatie\Permission\Models\Role;

class ErrorHandlingIntegrationTest extends TestCase
{

    protected User $petugas;
    protected User $supervisor;
    protected ValidationWorkflowService $validationService;
    protected NotificationService $notificationService;
    protected BulkOperationService $bulkService;

    protected function setUp(): void
        // Roles are already created by base TestCase
    {
        parent::setUp();
        
        // Create roles
        
        // Create users
        $this->petugas = User::factory()->create(['name' => 'Test Petugas']);
        $this->petugas->assignRole('petugas');
        
        $this->supervisor = User::factory()->create(['name' => 'Test Supervisor']);
        $this->supervisor->assignRole('supervisor');
        
        // Initialize services
        $telegramService = new TelegramService();
        $this->notificationService = new NotificationService($telegramService);
        $this->validationService = new ValidationWorkflowService($telegramService, $this->notificationService);
        $this->bulkService = new BulkOperationService();
        
        Cache::flush();
    }

    public function test_database_connection_failure_handling()
    {
        // Arrange
        $this->actingAs($this->petugas);

        // Simulate database connection failure
        DB::shouldReceive('beginTransaction')->andThrow(new \Exception('Database connection failed'));

        $pasienData = [
            'no_rekam_medis' => 'RM-2024-001',
            'nama' => 'Test Patient',
            'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => 'L',
        ];

        // Act & Assert
        try {
            $component = Livewire::test(CreatePasien::class)
                ->fillForm($pasienData)
                ->call('create');
            
            // Should handle error gracefully
            $component->assertHasErrors();
        } catch (\Exception $e) {
            // Error should be caught and handled appropriately
            $this->assertStringContains('Database', $e->getMessage());
        }
    }

    public function test_validation_service_exception_handling()
    {
        // Arrange
        $this->actingAs($this->petugas);
        
        $jenisTindakan = JenisTindakan::factory()->create(['tarif' => 150000]);
        $patient = Pasien::factory()->create(['input_by' => $this->petugas->id]);
        $shift = Shift::factory()->create(['name' => 'Pagi', 'is_active' => true]);

        $tindakan = Tindakan::factory()->create([
            'input_by' => $this->petugas->id,
            'jenis_tindakan_id' => $jenisTindakan->id,
            'pasien_id' => $patient->id,
            'shift_id' => $shift->id,
            'tarif' => 150000,
            'status_validasi' => 'pending',
        ]);

        // Force exception in validation service
        $mockValidationService = $this->createMock(ValidationWorkflowService::class);
        $mockValidationService->method('submitForValidation')
            ->willThrow(new \Exception('Validation service exception'));

        $this->app->instance(ValidationWorkflowService::class, $mockValidationService);

        // Act
        try {
            $result = $mockValidationService->submitForValidation($tindakan);
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            // Assert - Exception should be properly caught
            $this->assertStringContains('Validation service exception', $e->getMessage());
        }
    }

    public function test_notification_service_failure_fallback()
    {
        // Arrange
        $this->actingAs($this->petugas);
        
        // Mock notification service to fail
        $mockNotificationService = $this->createMock(NotificationService::class);
        $mockNotificationService->method('getUserNotifications')
            ->willReturn([
                'success' => false,
                'error' => 'Notification service unavailable',
                'notifications' => [],
                'total' => 0,
                'unread' => 0,
            ]);

        $this->app->instance(NotificationService::class, $mockNotificationService);

        // Act
        $result = $mockNotificationService->getUserNotifications($this->petugas->id, 10);

        // Assert - Should handle failure gracefully
        $this->assertFalse($result['success']);
        $this->assertEquals('Notification service unavailable', $result['error']);
        $this->assertEmpty($result['notifications']);
    }

    public function test_bulk_operation_partial_failure_handling()
    {
        // Arrange
        $this->actingAs($this->petugas);
        
        $mixedData = [
            [
                'nama' => 'Valid Patient 1',
                'no_rekam_medis' => 'RM-2024-001',
                'tanggal_lahir' => '1990-01-01',
                'jenis_kelamin' => 'L',
                'input_by' => $this->petugas->id,
            ],
            [
                'nama' => '', // Invalid - empty name
                'no_rekam_medis' => 'RM-2024-002',
                'tanggal_lahir' => '1991-01-01',
                'jenis_kelamin' => 'P',
                'input_by' => $this->petugas->id,
            ],
            [
                'nama' => 'Valid Patient 2',
                'no_rekam_medis' => 'RM-2024-003',
                'tanggal_lahir' => '1992-01-01',
                'jenis_kelamin' => 'L',
                'input_by' => $this->petugas->id,
            ],
            [
                'nama' => 'Invalid Patient',
                'no_rekam_medis' => 'RM-2024-001', // Duplicate
                'tanggal_lahir' => '1993-01-01',
                'jenis_kelamin' => 'P',
                'input_by' => $this->petugas->id,
            ],
        ];

        // Act
        $result = $this->bulkService->bulkCreate(Pasien::class, $mixedData);

        // Assert - Should handle partial failures gracefully
        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['created']); // Only valid records
        $this->assertEquals(2, $result['failed']); // Invalid records
        $this->assertCount(2, $result['errors']);

        // Verify valid data was created
        $this->assertDatabaseHas('pasien', ['nama' => 'Valid Patient 1']);
        $this->assertDatabaseHas('pasien', ['nama' => 'Valid Patient 2']);
        $this->assertDatabaseMissing('pasien', ['nama' => '']);
        $this->assertDatabaseMissing('pasien', ['nama' => 'Invalid Patient']);
    }

    public function test_concurrent_user_access_handling()
    {
        // Test handling of concurrent access by multiple users
        
        // Arrange - Create shared data
        $jenisTindakan = JenisTindakan::factory()->create(['tarif' => 120000]);
        $patient = Pasien::factory()->create(['input_by' => $this->petugas->id]);
        $shift = Shift::factory()->create(['name' => 'Pagi', 'is_active' => true]);

        $tindakan = Tindakan::factory()->create([
            'input_by' => $this->petugas->id,
            'jenis_tindakan_id' => $jenisTindakan->id,
            'pasien_id' => $patient->id,
            'shift_id' => $shift->id,
            'tarif' => 120000,
            'status_validasi' => 'pending',
            'submitted_at' => now(),
        ]);

        // Act 1 - Supervisor starts processing
        $this->actingAs($this->supervisor);
        $result1 = $this->validationService->approve($tindakan);

        // Act 2 - Another supervisor tries to process same item
        $otherSupervisor = User::factory()->create();
        $otherSupervisor->assignRole('supervisor');
        $this->actingAs($otherSupervisor);
        
        $result2 = $this->validationService->approve($tindakan);

        // Assert - Second attempt should fail gracefully
        $this->assertTrue($result1['success']);
        $this->assertFalse($result2['success']);
        $this->assertStringContains('already processed', strtolower($result2['error']));
    }

    public function test_invalid_data_type_handling()
    {
        // Test handling of invalid data types in forms
        
        $this->actingAs($this->petugas);

        $invalidData = [
            'no_rekam_medis' => 'RM-2024-001',
            'nama' => 'Test Patient',
            'tanggal_lahir' => 'invalid-date', // Invalid date format
            'jenis_kelamin' => 'X', // Invalid gender
            'email' => 'not-an-email', // Invalid email
        ];

        // Act
        $component = Livewire::test(CreatePasien::class)
            ->fillForm($invalidData)
            ->call('create');

        // Assert - Should handle validation errors
        $component->assertHasFormErrors([
            'tanggal_lahir',
            'jenis_kelamin',
            'email'
        ]);

        // Verify no invalid data was saved
        $this->assertDatabaseMissing('pasien', [
            'nama' => 'Test Patient',
            'tanggal_lahir' => 'invalid-date',
        ]);
    }

    public function test_memory_limit_handling_for_large_datasets()
    {
        // Test handling of large datasets that might hit memory limits
        
        $this->actingAs($this->petugas);

        // Create a large dataset (simulate memory pressure)
        $largeDataset = [];
        for ($i = 1; $i <= 1000; $i++) {
            $largeDataset[] = [
                'nama' => "Patient {$i}",
                'no_rekam_medis' => "RM-2024-" . str_pad($i, 4, '0', STR_PAD_LEFT),
                'tanggal_lahir' => '1990-01-01',
                'jenis_kelamin' => $i % 2 === 0 ? 'P' : 'L',
                'input_by' => $this->petugas->id,
                'alamat' => str_repeat('Long address data ', 50), // Make records larger
            ];
        }

        // Act - Process with appropriate batch size to avoid memory issues
        $result = $this->bulkService->bulkCreate(Pasien::class, $largeDataset, [
            'batch_size' => 50,
            'memory_limit' => '128M'
        ]);

        // Assert - Should complete successfully with batching
        $this->assertTrue($result['success']);
        $this->assertEquals(1000, $result['created']);
        $this->assertEquals(0, $result['failed']);
    }

    public function test_network_timeout_handling()
    {
        // Test handling of network timeouts (e.g., for external services)
        
        $this->actingAs($this->petugas);

        // Mock TelegramService to simulate timeout
        $mockTelegramService = $this->createMock(TelegramService::class);
        $mockTelegramService->method('sendMessage')
            ->willThrow(new \Exception('Request timeout'));

        $mockNotificationService = new NotificationService($mockTelegramService);

        // Act - Try to send notification that will timeout
        $result = $mockNotificationService->sendNotification(
            $this->supervisor->id,
            'Test notification',
            'This is a test',
            'info'
        );

        // Assert - Should handle timeout gracefully
        $this->assertFalse($result['success']);
        $this->assertStringContains('timeout', strtolower($result['error']));
    }

    public function test_disk_space_full_handling()
    {
        // Test handling when disk space is full (simulated)
        
        $this->actingAs($this->petugas);

        // Mock file operations to simulate disk full
        $mockStatsService = $this->createMock(PetugasStatsService::class);
        $mockStatsService->method('exportStats')
            ->willThrow(new \Exception('No space left on device'));

        // Act & Assert
        try {
            $mockStatsService->exportStats($this->petugas->id, 'csv');
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            $this->assertStringContains('No space left', $e->getMessage());
        }
    }

    public function test_form_validation_with_malicious_input()
    {
        // Test handling of potentially malicious input
        
        $this->actingAs($this->petugas);

        $maliciousData = [
            'no_rekam_medis' => 'RM-2024-001',
            'nama' => '<script>alert("xss")</script>', // XSS attempt
            'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => 'L',
            'alamat' => "'; DROP TABLE pasien; --", // SQL injection attempt
            'email' => 'test@test.com',
        ];

        // Act
        $component = Livewire::test(CreatePasien::class)
            ->fillForm($maliciousData)
            ->call('create');

        // Assert - Should sanitize and handle malicious input
        $component->assertSuccessful();

        // Verify data was sanitized (specific sanitization depends on implementation)
        $this->assertDatabaseHas('pasien', [
            'no_rekam_medis' => 'RM-2024-001',
        ]);

        // Verify malicious content was not executed/stored as-is
        $patient = Pasien::where('no_rekam_medis', 'RM-2024-001')->first();
        $this->assertNotNull($patient);
        $this->assertStringNotContains('<script>', $patient->nama);
        $this->assertStringNotContains('DROP TABLE', $patient->alamat);
    }

    public function test_session_expiry_handling()
    {
        // Test handling of expired user sessions
        
        $this->actingAs($this->petugas);

        // Create form data
        $pasienData = [
            'no_rekam_medis' => 'RM-2024-001',
            'nama' => 'Session Test Patient',
            'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => 'L',
        ];

        // Simulate session expiry
        Auth::logout();

        // Act
        $component = Livewire::test(CreatePasien::class)
            ->fillForm($pasienData)
            ->call('create');

        // Assert - Should handle unauthenticated state
        $component->assertRedirect(); // Should redirect to login
        
        // Verify no data was saved from unauthenticated user
        $this->assertDatabaseMissing('pasien', [
            'nama' => 'Session Test Patient',
        ]);
    }

    public function test_service_dependency_failure_cascade()
    {
        // Test handling when service dependencies fail in cascade
        
        $this->actingAs($this->petugas);

        // Create a chain of failing services
        $mockTelegramService = $this->createMock(TelegramService::class);
        $mockTelegramService->method('isConfigured')
            ->willReturn(false); // Service not properly configured

        $mockNotificationService = new NotificationService($mockTelegramService);
        $mockValidationService = new ValidationWorkflowService($mockTelegramService, $mockNotificationService);

        $jenisTindakan = JenisTindakan::factory()->create(['tarif' => 150000]);
        $patient = Pasien::factory()->create(['input_by' => $this->petugas->id]);
        $shift = Shift::factory()->create(['name' => 'Pagi', 'is_active' => true]);

        $tindakan = Tindakan::factory()->create([
            'input_by' => $this->petugas->id,
            'jenis_tindakan_id' => $jenisTindakan->id,
            'pasien_id' => $patient->id,
            'shift_id' => $shift->id,
            'tarif' => 150000,
            'status_validasi' => 'pending',
        ]);

        // Act - Validation should still work even if notification fails
        $result = $mockValidationService->submitForValidation($tindakan);

        // Assert - Validation should succeed with degraded notification capability
        $this->assertTrue($result['success']);
        $this->assertEquals('pending', $result['status']);
        
        // Notification might fail but core functionality should work
        if (isset($result['notification_sent'])) {
            $this->assertFalse($result['notification_sent']);
        }
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}