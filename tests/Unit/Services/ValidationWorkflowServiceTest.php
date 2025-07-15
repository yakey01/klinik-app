<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\ValidationWorkflowService;
use App\Services\TelegramService;
use App\Services\NotificationService;
use App\Models\User;
use App\Models\Tindakan;
use App\Models\PendapatanHarian;
use App\Models\PengeluaranHarian;
use App\Models\JenisTindakan;
use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use App\Models\Pasien;
use App\Models\AuditLog;
use Mockery;

class ValidationWorkflowServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ValidationWorkflowService $service;
    protected User $user;
    protected User $supervisor;
    protected TelegramService $telegramService;
    protected NotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->supervisor = User::factory()->create();
        
        $this->telegramService = Mockery::mock(TelegramService::class);
        $this->notificationService = Mockery::mock(NotificationService::class);
        
        $this->service = new ValidationWorkflowService(
            $this->telegramService,
            $this->notificationService
        );
        
        Auth::login($this->user);
    }

    public function test_it_submits_record_for_validation()
    {
        // Arrange
        $jenisTindakan = JenisTindakan::factory()->create(['nama' => 'Test Tindakan']);
        $pasien = Pasien::factory()->create(['input_by' => $this->user->id]);
        
        $tindakan = Tindakan::factory()->create([
            'input_by' => $this->user->id,
            'jenis_tindakan_id' => $jenisTindakan->id,
            'pasien_id' => $pasien->id,
            'tanggal_tindakan' => now(),
            'tarif' => 150000,
            'status_validasi' => 'pending',
        ]);
        
        $this->notificationService->shouldReceive('sendRealTimeNotification')
            ->once()
            ->andReturn(['success' => true]);
        
        // Act
        $result = $this->service->submitForValidation($tindakan);
        
        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('pending', $result['status']);
        $this->assertFalse($result['auto_approved']);
        
        $tindakan->refresh();
        $this->assertEquals('pending', $tindakan->status_validasi);
        $this->assertNotNull($tindakan->submitted_at);
        $this->assertEquals($this->user->id, $tindakan->submitted_by);
    }

    public function test_it_auto_approves_below_threshold()
    {
        // Arrange
        $jenisTindakan = JenisTindakan::factory()->create(['nama' => 'Test Tindakan']);
        $pasien = Pasien::factory()->create(['input_by' => $this->user->id]);
        
        $tindakan = Tindakan::factory()->create([
            'input_by' => $this->user->id,
            'jenis_tindakan_id' => $jenisTindakan->id,
            'pasien_id' => $pasien->id,
            'tanggal_tindakan' => now(),
            'tarif' => 50000, // Below auto-approval threshold of 100000
            'status_validasi' => 'pending',
        ]);
        
        $this->notificationService->shouldReceive('sendRealTimeNotification')
            ->once()
            ->andReturn(['success' => true]);
        
        // Act
        $result = $this->service->submitForValidation($tindakan);
        
        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('approved', $result['status']);
        $this->assertTrue($result['auto_approved']);
        
        $tindakan->refresh();
        $this->assertEquals('approved', $tindakan->status_validasi);
        $this->assertNotNull($tindakan->approved_at);
        $this->assertEquals('system', $tindakan->approved_by);
        $this->assertEquals('Auto-approved based on threshold', $tindakan->approval_reason);
    }

    public function test_it_validates_required_fields()
    {
        // Arrange
        $tindakan = Tindakan::factory()->create([
            'input_by' => $this->user->id,
            'jenis_tindakan_id' => null, // Missing required field
            'pasien_id' => null, // Missing required field
            'tanggal_tindakan' => now(),
            'tarif' => 50000,
            'status_validasi' => 'pending',
        ]);
        
        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Missing required fields: jenis_tindakan_id, pasien_id');
        
        $this->service->submitForValidation($tindakan);
    }

    public function test_it_approves_record_successfully()
    {
        // Arrange
        $jenisTindakan = JenisTindakan::factory()->create(['nama' => 'Test Tindakan']);
        $pasien = Pasien::factory()->create(['input_by' => $this->user->id]);
        
        $tindakan = Tindakan::factory()->create([
            'input_by' => $this->user->id,
            'jenis_tindakan_id' => $jenisTindakan->id,
            'pasien_id' => $pasien->id,
            'tanggal_tindakan' => now(),
            'tarif' => 150000,
            'status_validasi' => 'pending',
            'status' => 'pending',
        ]);
        
        $this->notificationService->shouldReceive('sendRealTimeNotification')
            ->once()
            ->andReturn(['success' => true]);
        
        // Act
        $result = $this->service->approve($tindakan, [
            'reason' => 'Looks good',
            'approved_by' => $this->supervisor->id,
        ]);
        
        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('approved', $result['status']);
        
        $tindakan->refresh();
        $this->assertEquals('approved', $tindakan->status_validasi);
        $this->assertEquals('selesai', $tindakan->status);
        $this->assertNotNull($tindakan->approved_at);
        $this->assertEquals($this->supervisor->id, $tindakan->approved_by);
        $this->assertEquals('Looks good', $tindakan->approval_reason);
    }

    public function test_it_rejects_record_with_reason()
    {
        // Arrange
        $jenisTindakan = JenisTindakan::factory()->create(['nama' => 'Test Tindakan']);
        $pasien = Pasien::factory()->create(['input_by' => $this->user->id]);
        
        $tindakan = Tindakan::factory()->create([
            'input_by' => $this->user->id,
            'jenis_tindakan_id' => $jenisTindakan->id,
            'pasien_id' => $pasien->id,
            'tanggal_tindakan' => now(),
            'tarif' => 150000,
            'status_validasi' => 'pending',
            'status' => 'pending',
        ]);
        
        $this->notificationService->shouldReceive('sendRealTimeNotification')
            ->once()
            ->andReturn(['success' => true]);
        
        // Act
        $result = $this->service->reject($tindakan, 'Incomplete information', [
            'rejected_by' => $this->supervisor->id,
        ]);
        
        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('rejected', $result['status']);
        
        $tindakan->refresh();
        $this->assertEquals('rejected', $tindakan->status_validasi);
        $this->assertEquals('batal', $tindakan->status);
        $this->assertNotNull($tindakan->rejected_at);
        $this->assertEquals($this->supervisor->id, $tindakan->rejected_by);
        $this->assertEquals('Incomplete information', $tindakan->rejection_reason);
    }

    public function test_it_requests_revision_with_reason()
    {
        // Arrange
        $jenisTindakan = JenisTindakan::factory()->create(['nama' => 'Test Tindakan']);
        $pasien = Pasien::factory()->create(['input_by' => $this->user->id]);
        
        $tindakan = Tindakan::factory()->create([
            'input_by' => $this->user->id,
            'jenis_tindakan_id' => $jenisTindakan->id,
            'pasien_id' => $pasien->id,
            'tanggal_tindakan' => now(),
            'tarif' => 150000,
            'status_validasi' => 'pending',
        ]);
        
        $this->notificationService->shouldReceive('sendRealTimeNotification')
            ->once()
            ->andReturn(['success' => true]);
        
        // Act
        $result = $this->service->requestRevision($tindakan, 'Please add more details', [
            'requested_by' => $this->supervisor->id,
        ]);
        
        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('revision', $result['status']);
        
        $tindakan->refresh();
        $this->assertEquals('revision', $tindakan->status_validasi);
        $this->assertNotNull($tindakan->revision_requested_at);
        $this->assertEquals($this->supervisor->id, $tindakan->revision_requested_by);
        $this->assertEquals('Please add more details', $tindakan->revision_reason);
    }

    public function test_it_gets_validation_statistics()
    {
        // Arrange
        $jenisTindakan = JenisTindakan::factory()->create(['nama' => 'Test Tindakan']);
        $pasien = Pasien::factory()->create(['input_by' => $this->user->id]);
        
        // Create audit logs for testing
        AuditLog::create([
            'user_id' => $this->user->id,
            'action' => 'submitted',
            'model_type' => Tindakan::class,
            'model_id' => 1,
            'changes' => json_encode(['status' => 'pending']),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'url' => '/test',
            'method' => 'POST',
            'risk_level' => 'medium',
        ]);
        
        AuditLog::create([
            'user_id' => $this->supervisor->id,
            'action' => 'approved',
            'model_type' => Tindakan::class,
            'model_id' => 1,
            'changes' => json_encode(['status' => 'approved', 'auto_approved' => false]),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'url' => '/test',
            'method' => 'POST',
            'risk_level' => 'medium',
        ]);
        
        AuditLog::create([
            'user_id' => $this->supervisor->id,
            'action' => 'rejected',
            'model_type' => Tindakan::class,
            'model_id' => 2,
            'changes' => json_encode(['status' => 'rejected']),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'url' => '/test',
            'method' => 'POST',
            'risk_level' => 'medium',
        ]);
        
        // Act
        $result = $this->service->getValidationStats(Tindakan::class, 30);
        
        // Assert
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        
        $stats = $result['data'];
        $this->assertArrayHasKey('total_submissions', $stats);
        $this->assertArrayHasKey('approved', $stats);
        $this->assertArrayHasKey('rejected', $stats);
        $this->assertArrayHasKey('pending', $stats);
        $this->assertArrayHasKey('approval_rate', $stats);
        $this->assertArrayHasKey('daily_breakdown', $stats);
        
        $this->assertEquals(1, $stats['total_submissions']);
        $this->assertEquals(1, $stats['approved']);
        $this->assertEquals(1, $stats['rejected']);
        $this->assertEquals(50, $stats['approval_rate']); // 1/2 = 50%
    }

    public function test_it_gets_pending_validations_for_authorized_user()
    {
        // Arrange
        $this->supervisor->assignRole('supervisor');
        Auth::login($this->supervisor);
        
        $jenisTindakan = JenisTindakan::factory()->create(['nama' => 'Test Tindakan']);
        $pasien = Pasien::factory()->create(['input_by' => $this->user->id]);
        
        $tindakan = Tindakan::factory()->create([
            'input_by' => $this->user->id,
            'jenis_tindakan_id' => $jenisTindakan->id,
            'pasien_id' => $pasien->id,
            'tanggal_tindakan' => now(),
            'tarif' => 150000,
            'status_validasi' => 'pending',
            'submitted_at' => now(),
        ]);
        
        // Act
        $result = $this->service->getPendingValidations($this->supervisor->id);
        
        // Assert
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('total', $result);
        
        $pendingItems = $result['data'];
        $this->assertGreaterThan(0, count($pendingItems));
        
        $firstItem = $pendingItems[0];
        $this->assertArrayHasKey('id', $firstItem);
        $this->assertArrayHasKey('model', $firstItem);
        $this->assertArrayHasKey('title', $firstItem);
        $this->assertArrayHasKey('submitted_by', $firstItem);
        $this->assertArrayHasKey('amount', $firstItem);
        $this->assertArrayHasKey('priority', $firstItem);
        $this->assertArrayHasKey('days_pending', $firstItem);
        
        $this->assertEquals('Tindakan', $firstItem['model']);
        $this->assertEquals(150000, $firstItem['amount']);
    }

    public function test_it_restricts_pending_validations_for_unauthorized_user()
    {
        // Arrange - user without supervisor role
        Auth::login($this->user);
        
        // Act
        $result = $this->service->getPendingValidations($this->user->id);
        
        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['total']);
        $this->assertEmpty($result['data']);
    }

    public function test_it_calculates_priority_correctly()
    {
        // Test through a mock class to access protected method
        $service = new class($this->telegramService, $this->notificationService) extends ValidationWorkflowService {
            public function testCalculatePriority($record)
            {
                return $this->calculatePriority($record);
            }
        };
        
        // Test high amount, high days
        $highPriorityRecord = Mockery::mock();
        $highPriorityRecord->shouldReceive('getAttribute')->with('nominal')->andReturn(1500000);
        $highPriorityRecord->shouldReceive('getAttribute')->with('tarif')->andReturn(null);
        $highPriorityRecord->submitted_at = now()->subDays(10);
        
        $highPriority = $service->testCalculatePriority($highPriorityRecord);
        $this->assertGreaterThan(5, $highPriority);
        
        // Test low amount, low days
        $lowPriorityRecord = Mockery::mock();
        $lowPriorityRecord->shouldReceive('getAttribute')->with('nominal')->andReturn(50000);
        $lowPriorityRecord->shouldReceive('getAttribute')->with('tarif')->andReturn(null);
        $lowPriorityRecord->submitted_at = now();
        
        $lowPriority = $service->testCalculatePriority($lowPriorityRecord);
        $this->assertLessThan(3, $lowPriority);
    }

    public function test_it_logs_validation_actions()
    {
        // Arrange
        $jenisTindakan = JenisTindakan::factory()->create(['nama' => 'Test Tindakan']);
        $pasien = Pasien::factory()->create(['input_by' => $this->user->id]);
        
        $tindakan = Tindakan::factory()->create([
            'input_by' => $this->user->id,
            'jenis_tindakan_id' => $jenisTindakan->id,
            'pasien_id' => $pasien->id,
            'tanggal_tindakan' => now(),
            'tarif' => 50000,
            'status_validasi' => 'pending',
        ]);
        
        $this->notificationService->shouldReceive('sendRealTimeNotification')
            ->once()
            ->andReturn(['success' => true]);
        
        // Act
        $result = $this->service->submitForValidation($tindakan);
        
        // Assert
        $this->assertTrue($result['success']);
        
        // Check that audit log was created
        $auditLog = AuditLog::where('model_type', Tindakan::class)
            ->where('model_id', $tindakan->id)
            ->where('action', 'submitted')
            ->first();
        
        $this->assertNotNull($auditLog);
        $this->assertEquals($this->user->id, $auditLog->user_id);
        $this->assertEquals('submitted', $auditLog->action);
        $this->assertEquals(Tindakan::class, $auditLog->model_type);
        $this->assertEquals($tindakan->id, $auditLog->model_id);
    }

    public function test_it_handles_database_transactions()
    {
        // Arrange
        $jenisTindakan = JenisTindakan::factory()->create(['nama' => 'Test Tindakan']);
        $pasien = Pasien::factory()->create(['input_by' => $this->user->id]);
        
        $tindakan = Tindakan::factory()->create([
            'input_by' => $this->user->id,
            'jenis_tindakan_id' => $jenisTindakan->id,
            'pasien_id' => $pasien->id,
            'tanggal_tindakan' => now(),
            'tarif' => 150000,
            'status_validasi' => 'pending',
        ]);
        
        // Mock notification service to throw exception
        $this->notificationService->shouldReceive('sendRealTimeNotification')
            ->once()
            ->andThrow(new \Exception('Notification failed'));
        
        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Notification failed');
        
        $this->service->submitForValidation($tindakan);
        
        // Check that transaction was rolled back
        $tindakan->refresh();
        $this->assertEquals('pending', $tindakan->status_validasi);
        $this->assertNull($tindakan->submitted_at);
    }

    public function test_it_works_with_different_model_types()
    {
        // Test with PendapatanHarian
        $pendapatan = Pendapatan::factory()->create(['nama_pendapatan' => 'Test Pendapatan']);
        $pendapatanHarian = PendapatanHarian::factory()->create([
            'user_id' => $this->user->id,
            'pendapatan_id' => $pendapatan->id,
            'tanggal_input' => now()->format('Y-m-d'),
            'nominal' => 300000,
            'status_validasi' => 'pending',
        ]);
        
        $this->notificationService->shouldReceive('sendRealTimeNotification')
            ->once()
            ->andReturn(['success' => true]);
        
        $result = $this->service->submitForValidation($pendapatanHarian);
        $this->assertTrue($result['success']);
        
        // Test with PengeluaranHarian
        $pengeluaran = Pengeluaran::factory()->create(['nama_pengeluaran' => 'Test Pengeluaran']);
        $pengeluaranHarian = PengeluaranHarian::factory()->create([
            'user_id' => $this->user->id,
            'pengeluaran_id' => $pengeluaran->id,
            'tanggal_input' => now()->format('Y-m-d'),
            'nominal' => 150000,
            'status_validasi' => 'pending',
        ]);
        
        $this->notificationService->shouldReceive('sendRealTimeNotification')
            ->once()
            ->andReturn(['success' => true]);
        
        $result2 = $this->service->submitForValidation($pengeluaranHarian);
        $this->assertTrue($result2['success']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}