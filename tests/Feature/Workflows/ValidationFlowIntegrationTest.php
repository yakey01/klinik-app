<?php

namespace Tests\Feature\Workflows;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use App\Models\User;
use App\Models\Pasien;
use App\Models\Tindakan;
use App\Models\PendapatanHarian;
use App\Models\PengeluaranHarian;
use App\Models\JenisTindakan;
use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use App\Models\Dokter;
use App\Models\Pegawai;
use App\Models\Shift;
use App\Models\AuditLog;
use App\Services\ValidationWorkflowService;
use App\Services\NotificationService;
use App\Services\TelegramService;
use App\Services\PetugasStatsService;
use Spatie\Permission\Models\Role;

class ValidationFlowIntegrationTest extends TestCase
{
    use DatabaseMigrations;

    protected User $petugas;
    protected User $supervisor;
    protected User $manager;
    protected ValidationWorkflowService $validationService;
    protected NotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Roles are already created by base TestCase via RoleSetupTrait
        $this->petugas = User::factory()->create(['name' => 'Test Petugas']);
        $this->petugas->assignRole('petugas');
        
        $this->supervisor = User::factory()->create(['name' => 'Test Supervisor']);
        $this->supervisor->assignRole('supervisor');
        
        $this->manager = User::factory()->create(['name' => 'Test Manager']);
        $this->manager->assignRole('manager');
        
        // Initialize services
        $telegramService = new TelegramService();
        $this->notificationService = new NotificationService($telegramService);
        $this->validationService = new ValidationWorkflowService($telegramService, $this->notificationService);
        
        // Clear cache and notifications
        Cache::flush();
        Notification::fake();
    }

    public function test_escalation_workflow_for_high_amount_tindakan()
    {
        // Arrange
        $this->actingAs($this->petugas);
        
        $jenisTindakan = JenisTindakan::factory()->create(['tarif' => 2000000]); // Very high amount
        $patient = Pasien::factory()->create(['input_by' => $this->petugas->id]);
        $shift = Shift::factory()->create(['name' => 'Pagi', 'is_active' => true]);

        $tindakan = Tindakan::factory()->create([
            'input_by' => $this->petugas->id,
            'jenis_tindakan_id' => $jenisTindakan->id,
            'pasien_id' => $patient->id,
            'shift_id' => $shift->id,
            'tarif' => 2000000,
            'status_validasi' => 'pending',
        ]);

        // Act 1 - Submit for validation (should require manager approval due to high amount)
        $submissionResult = $this->validationService->submitForValidation($tindakan);

        // Assert - Should require manager approval
        $this->assertTrue($submissionResult['success']);
        $this->assertEquals('pending', $submissionResult['status']);
        $this->assertFalse($submissionResult['auto_approved']);
        $this->assertEquals('manager', $submissionResult['requires_approval_from']);

        // Act 2 - Supervisor tries to approve (should be rejected)
        $this->actingAs($this->supervisor);
        $supervisorApprovalResult = $this->validationService->approve($tindakan);

        // Assert - Supervisor approval should fail
        $this->assertFalse($supervisorApprovalResult['success']);
        $this->assertStringContains('insufficient permissions', strtolower($supervisorApprovalResult['error']));

        // Act 3 - Manager approves
        $this->actingAs($this->manager);
        $managerApprovalResult = $this->validationService->approve($tindakan, [
            'reason' => 'Approved high-value procedure',
            'approved_by' => $this->manager->id,
        ]);

        // Assert - Manager approval should succeed
        $this->assertTrue($managerApprovalResult['success']);
        $this->assertEquals('approved', $managerApprovalResult['status']);

        $tindakan->refresh();
        $this->assertEquals('approved', $tindakan->status_validasi);
        $this->assertEquals($this->manager->id, $tindakan->approved_by);
    }

    public function test_batch_validation_workflow()
    {
        // Arrange
        $this->actingAs($this->petugas);
        
        $jenisTindakan = JenisTindakan::factory()->create(['tarif' => 150000]);
        $patient = Pasien::factory()->create(['input_by' => $this->petugas->id]);
        $shift = Shift::factory()->create(['name' => 'Pagi', 'is_active' => true]);

        // Create multiple tindakan
        $tindakans = [];
        for ($i = 1; $i <= 5; $i++) {
            $tindakans[] = Tindakan::factory()->create([
                'input_by' => $this->petugas->id,
                'jenis_tindakan_id' => $jenisTindakan->id,
                'pasien_id' => $patient->id,
                'shift_id' => $shift->id,
                'tarif' => 150000,
                'status_validasi' => 'pending',
            ]);
        }

        // Act 1 - Submit all for validation
        $submissionResults = [];
        foreach ($tindakans as $tindakan) {
            $submissionResults[] = $this->validationService->submitForValidation($tindakan);
        }

        // Assert - All submitted successfully
        foreach ($submissionResults as $result) {
            $this->assertTrue($result['success']);
            $this->assertEquals('pending', $result['status']);
        }

        // Act 2 - Supervisor performs batch approval
        $this->actingAs($this->supervisor);
        $batchApprovalResult = $this->validationService->batchApproval(
            array_map(fn($t) => $t->id, $tindakans),
            'Tindakan',
            'approve',
            'Batch approval of routine procedures'
        );

        // Assert - Batch approval successful
        $this->assertTrue($batchApprovalResult['success']);
        $this->assertEquals(5, $batchApprovalResult['processed']);
        $this->assertEquals(5, $batchApprovalResult['approved']);
        $this->assertEquals(0, $batchApprovalResult['failed']);

        // Verify all tindakan are approved
        foreach ($tindakans as $tindakan) {
            $tindakan->refresh();
            $this->assertEquals('approved', $tindakan->status_validasi);
            $this->assertEquals($this->supervisor->id, $tindakan->approved_by);
        }
    }

    public function test_cross_model_validation_consistency()
    {
        // Test that validation workflow is consistent across different models
        
        // Arrange
        $this->actingAs($this->petugas);
        
        // Create Tindakan
        $jenisTindakan = JenisTindakan::factory()->create(['tarif' => 300000]);
        $patient = Pasien::factory()->create(['input_by' => $this->petugas->id]);
        $shift = Shift::factory()->create(['name' => 'Pagi', 'is_active' => true]);

        $tindakan = Tindakan::factory()->create([
            'input_by' => $this->petugas->id,
            'jenis_tindakan_id' => $jenisTindakan->id,
            'pasien_id' => $patient->id,
            'shift_id' => $shift->id,
            'tarif' => 300000,
            'status_validasi' => 'pending',
        ]);

        // Create PendapatanHarian
        $pendapatan = Pendapatan::factory()->create(['nama_pendapatan' => 'Konsultasi']);
        $pendapatanHarian = PendapatanHarian::factory()->create([
            'user_id' => $this->petugas->id,
            'pendapatan_id' => $pendapatan->id,
            'tanggal_input' => now()->format('Y-m-d'),
            'nominal' => 600000, // Above auto-approval threshold
            'status_validasi' => 'pending',
        ]);

        // Create PengeluaranHarian
        $pengeluaran = Pengeluaran::factory()->create(['nama_pengeluaran' => 'Obat']);
        $pengeluaranHarian = PengeluaranHarian::factory()->create([
            'user_id' => $this->petugas->id,
            'pengeluaran_id' => $pengeluaran->id,
            'tanggal_input' => now()->format('Y-m-d'),
            'nominal' => 400000,
            'status_validasi' => 'pending',
        ]);

        // Act - Submit all for validation
        $tindakanResult = $this->validationService->submitForValidation($tindakan);
        $pendapatanResult = $this->validationService->submitForValidation($pendapatanHarian);
        $pengeluaranResult = $this->validationService->submitForValidation($pengeluaranHarian);

        // Assert - All should have consistent validation behavior
        $this->assertTrue($tindakanResult['success']);
        $this->assertTrue($pendapatanResult['success']);
        $this->assertTrue($pengeluaranResult['success']);

        // Check approval requirements based on amounts
        $this->assertEquals('pending', $tindakanResult['status']); // 300k > auto-approval threshold
        $this->assertEquals('pending', $pendapatanResult['status']); // 600k > auto-approval threshold
        $this->assertEquals('approved', $pengeluaranResult['status']); // 400k < auto-approval threshold

        // Act 2 - Supervisor approves pending items
        $this->actingAs($this->supervisor);
        $tindakanApproval = $this->validationService->approve($tindakan);
        $pendapatanApproval = $this->validationService->approve($pendapatanHarian);

        // Assert - Both should be approved successfully
        $this->assertTrue($tindakanApproval['success']);
        $this->assertTrue($pendapatanApproval['success']);

        // Verify final states
        $tindakan->refresh();
        $pendapatanHarian->refresh();
        $pengeluaranHarian->refresh();

        $this->assertEquals('approved', $tindakan->status_validasi);
        $this->assertEquals('approved', $pendapatanHarian->status_validasi);
        $this->assertEquals('approved', $pengeluaranHarian->status_validasi);
    }

    public function test_notification_delivery_integration()
    {
        // Arrange
        $this->actingAs($this->petugas);
        
        $jenisTindakan = JenisTindakan::factory()->create(['tarif' => 200000]);
        $patient = Pasien::factory()->create(['input_by' => $this->petugas->id]);
        $shift = Shift::factory()->create(['name' => 'Pagi', 'is_active' => true]);

        $tindakan = Tindakan::factory()->create([
            'input_by' => $this->petugas->id,
            'jenis_tindakan_id' => $jenisTindakan->id,
            'pasien_id' => $patient->id,
            'shift_id' => $shift->id,
            'tarif' => 200000,
            'status_validasi' => 'pending',
        ]);

        // Act 1 - Submit for validation (should trigger notification)
        $submissionResult = $this->validationService->submitForValidation($tindakan);

        // Assert - Submission successful and notifications generated
        $this->assertTrue($submissionResult['success']);

        // Act 2 - Check notification service has pending notifications
        $pendingNotifications = $this->notificationService->getUserNotifications($this->supervisor->id, 10);

        // Assert - Supervisor should have notification
        $this->assertTrue($pendingNotifications['success']);
        $this->assertGreaterThan(0, $pendingNotifications['total']);
        $this->assertGreaterThan(0, $pendingNotifications['unread']);

        // Find our specific notification
        $notifications = $pendingNotifications['notifications'];
        $tindakanNotification = collect($notifications)->first(function ($notification) use ($tindakan) {
            return isset($notification['data']['model_type']) && 
                   $notification['data']['model_type'] === 'Tindakan' &&
                   $notification['data']['model_id'] === $tindakan->id;
        });

        $this->assertNotNull($tindakanNotification);
        $this->assertEquals('validation_pending', $tindakanNotification['type']);
        $this->assertEquals('medium', $tindakanNotification['priority']);

        // Act 3 - Supervisor approves (should trigger completion notification)
        $this->actingAs($this->supervisor);
        $approvalResult = $this->validationService->approve($tindakan);

        // Assert - Approval successful
        $this->assertTrue($approvalResult['success']);

        // Act 4 - Check petugas notifications for approval confirmation
        $petugasNotifications = $this->notificationService->getUserNotifications($this->petugas->id, 10);

        // Assert - Petugas should have approval notification
        $this->assertTrue($petugasNotifications['success']);
        $this->assertGreaterThan(0, $petugasNotifications['total']);

        $approvalNotification = collect($petugasNotifications['notifications'])->first(function ($notification) use ($tindakan) {
            return isset($notification['data']['model_type']) && 
                   $notification['data']['model_type'] === 'Tindakan' &&
                   $notification['data']['model_id'] === $tindakan->id &&
                   $notification['type'] === 'validation_approved';
        });

        $this->assertNotNull($approvalNotification);
    }

    public function test_concurrent_validation_handling()
    {
        // Test what happens when multiple users try to validate the same item
        
        // Arrange
        $this->actingAs($this->petugas);
        
        $jenisTindakan = JenisTindakan::factory()->create(['tarif' => 180000]);
        $patient = Pasien::factory()->create(['input_by' => $this->petugas->id]);
        $shift = Shift::factory()->create(['name' => 'Pagi', 'is_active' => true]);

        $tindakan = Tindakan::factory()->create([
            'input_by' => $this->petugas->id,
            'jenis_tindakan_id' => $jenisTindakan->id,
            'pasien_id' => $patient->id,
            'shift_id' => $shift->id,
            'tarif' => 180000,
            'status_validasi' => 'pending',
            'submitted_at' => now(),
        ]);

        // Act 1 - Supervisor starts approval process
        $this->actingAs($this->supervisor);
        $approval1 = $this->validationService->approve($tindakan);

        // Act 2 - Manager tries to approve the same item (should fail)
        $this->actingAs($this->manager);
        $approval2 = $this->validationService->approve($tindakan);

        // Assert - First approval should succeed, second should fail
        $this->assertTrue($approval1['success']);
        $this->assertFalse($approval2['success']);
        $this->assertStringContains('already processed', strtolower($approval2['error']));

        // Verify final state
        $tindakan->refresh();
        $this->assertEquals('approved', $tindakan->status_validasi);
        $this->assertEquals($this->supervisor->id, $tindakan->approved_by);
    }

    public function test_validation_with_incomplete_data_workflow()
    {
        // Test validation workflow when required data is missing
        
        // Arrange
        $this->actingAs($this->petugas);
        
        $jenisTindakan = JenisTindakan::factory()->create(['tarif' => 160000]);
        $patient = Pasien::factory()->create(['input_by' => $this->petugas->id]);

        // Create tindakan with missing shift (incomplete data)
        $tindakan = Tindakan::factory()->create([
            'input_by' => $this->petugas->id,
            'jenis_tindakan_id' => $jenisTindakan->id,
            'pasien_id' => $patient->id,
            'shift_id' => null, // Missing required data
            'tarif' => 160000,
            'status_validasi' => 'pending',
        ]);

        // Act - Submit for validation
        $submissionResult = $this->validationService->submitForValidation($tindakan);

        // Assert - Should fail validation due to incomplete data
        $this->assertFalse($submissionResult['success']);
        $this->assertStringContains('incomplete', strtolower($submissionResult['error']));
        
        $tindakan->refresh();
        $this->assertEquals('pending', $tindakan->status_validasi);
        $this->assertNull($tindakan->submitted_at);
    }

    public function test_validation_workflow_performance()
    {
        // Test performance of validation workflow with multiple items
        
        // Arrange
        $this->actingAs($this->petugas);
        
        $jenisTindakan = JenisTindakan::factory()->create(['tarif' => 100000]);
        $patient = Pasien::factory()->create(['input_by' => $this->petugas->id]);
        $shift = Shift::factory()->create(['name' => 'Pagi', 'is_active' => true]);

        // Create 20 tindakan
        $tindakans = [];
        for ($i = 1; $i <= 20; $i++) {
            $tindakans[] = Tindakan::factory()->create([
                'input_by' => $this->petugas->id,
                'jenis_tindakan_id' => $jenisTindakan->id,
                'pasien_id' => $patient->id,
                'shift_id' => $shift->id,
                'tarif' => 100000,
                'status_validasi' => 'pending',
            ]);
        }

        // Act - Measure time for batch submission
        $startTime = microtime(true);
        
        $submissionResults = [];
        foreach ($tindakans as $tindakan) {
            $submissionResults[] = $this->validationService->submitForValidation($tindakan);
        }
        
        $submissionTime = microtime(true) - $startTime;

        // Assert - All submissions should succeed in reasonable time
        $this->assertLessThan(5.0, $submissionTime); // Should complete in under 5 seconds
        
        foreach ($submissionResults as $result) {
            $this->assertTrue($result['success']);
        }

        // Act 2 - Measure time for batch approval
        $this->actingAs($this->supervisor);
        $startTime = microtime(true);
        
        $batchResult = $this->validationService->batchApproval(
            array_map(fn($t) => $t->id, $tindakans),
            'Tindakan',
            'approve',
            'Performance test batch approval'
        );
        
        $approvalTime = microtime(true) - $startTime;

        // Assert - Batch approval should be efficient
        $this->assertLessThan(3.0, $approvalTime); // Should complete in under 3 seconds
        $this->assertTrue($batchResult['success']);
        $this->assertEquals(20, $batchResult['approved']);
    }

    public function test_validation_state_transitions()
    {
        // Test all possible state transitions in validation workflow
        
        // Arrange
        $this->actingAs($this->petugas);
        
        $jenisTindakan = JenisTindakan::factory()->create(['tarif' => 170000]);
        $patient = Pasien::factory()->create(['input_by' => $this->petugas->id]);
        $shift = Shift::factory()->create(['name' => 'Pagi', 'is_active' => true]);

        $tindakan = Tindakan::factory()->create([
            'input_by' => $this->petugas->id,
            'jenis_tindakan_id' => $jenisTindakan->id,
            'pasien_id' => $patient->id,
            'shift_id' => $shift->id,
            'tarif' => 170000,
            'status_validasi' => 'pending',
        ]);

        // Test 1: pending → submitted
        $submissionResult = $this->validationService->submitForValidation($tindakan);
        $this->assertTrue($submissionResult['success']);
        $this->assertEquals('pending', $submissionResult['status']);

        $tindakan->refresh();
        $this->assertEquals('pending', $tindakan->status_validasi);
        $this->assertNotNull($tindakan->submitted_at);

        // Test 2: pending → revision
        $this->actingAs($this->supervisor);
        $revisionResult = $this->validationService->requestRevision($tindakan, 'Need more details');
        $this->assertTrue($revisionResult['success']);
        $this->assertEquals('revision', $revisionResult['status']);

        $tindakan->refresh();
        $this->assertEquals('revision', $tindakan->status_validasi);

        // Test 3: revision → pending (resubmission)
        $this->actingAs($this->petugas);
        $resubmissionResult = $this->validationService->resubmitAfterRevision($tindakan, 'Added more details as requested');
        $this->assertTrue($resubmissionResult['success']);

        $tindakan->refresh();
        $this->assertEquals('pending', $tindakan->status_validasi);

        // Test 4: pending → approved
        $this->actingAs($this->supervisor);
        $approvalResult = $this->validationService->approve($tindakan);
        $this->assertTrue($approvalResult['success']);

        $tindakan->refresh();
        $this->assertEquals('approved', $tindakan->status_validasi);

        // Test 5: Try to change approved item (should fail)
        $changeResult = $this->validationService->reject($tindakan, 'Cannot reject approved item');
        $this->assertFalse($changeResult['success']);
        $this->assertStringContains('already approved', strtolower($changeResult['error']));
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}