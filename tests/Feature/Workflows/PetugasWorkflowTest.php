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
use App\Filament\Petugas\Resources\TindakanResource\Pages\CreateTindakan;
use App\Filament\Petugas\Resources\PendapatanHarianResource\Pages\CreatePendapatanHarian;
use App\Filament\Petugas\Resources\PasienResource\Pages\CreatePasien;
use Spatie\Permission\Models\Role;

class PetugasWorkflowTest extends TestCase
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
        
        // Create roles
        Role::create(['name' => 'petugas']);
        Role::create(['name' => 'supervisor']);
        Role::create(['name' => 'manager']);
        Role::create(['name' => 'admin']);
        
        // Create users with roles
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

    public function test_complete_patient_registration_workflow()
    {
        // Arrange
        $this->actingAs($this->petugas);

        // Act - Complete patient registration workflow
        $patientData = [
            'no_rekam_medis' => 'RM-2024-001',
            'nama' => 'John Doe',
            'tanggal_lahir' => '1990-01-15',
            'jenis_kelamin' => 'L',
            'alamat' => 'Jl. Test No. 123',
            'no_telepon' => '08123456789',
            'email' => 'john@test.com',
            'pekerjaan' => 'Engineer',
            'status_pernikahan' => 'menikah',
        ];

        $component = Livewire::test(CreatePasien::class)
            ->fillForm($patientData)
            ->call('create');

        // Assert - Patient created successfully
        $component->assertSuccessful();
        
        $patient = Pasien::where('no_rekam_medis', 'RM-2024-001')->first();
        $this->assertNotNull($patient);
        $this->assertEquals($this->petugas->id, $patient->input_by);
        $this->assertEquals('John Doe', $patient->nama);

        // Verify audit trail
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->petugas->id,
            'action' => 'created',
            'model_type' => Pasien::class,
            'model_id' => $patient->id,
        ]);

        // Verify stats service reflects new patient
        $statsService = new PetugasStatsService();
        $stats = $statsService->getDashboardStats($this->petugas->id);
        $this->assertEquals(1, $stats['daily']['today']['pasien_count']);
    }

    public function test_complete_tindakan_validation_workflow()
    {
        // Arrange
        $this->actingAs($this->petugas);
        
        // Create required related data
        $jenisTindakan = JenisTindakan::factory()->create([
            'nama' => 'Konsultasi Umum',
            'tarif' => 150000,
            'is_active' => true,
        ]);
        
        $patient = Pasien::factory()->create([
            'input_by' => $this->petugas->id,
            'nama' => 'Test Patient',
        ]);
        
        $shift = Shift::factory()->create([
            'name' => 'Pagi',
            'is_active' => true,
        ]);
        
        $dokter = Dokter::factory()->create([
            'nama_lengkap' => 'Dr. Test',
            'aktif' => true,
        ]);

        // Act 1 - Create Tindakan
        $tindakanData = [
            'jenis_tindakan_id' => $jenisTindakan->id,
            'pasien_id' => $patient->id,
            'tanggal_tindakan' => now()->format('Y-m-d H:i'),
            'shift_id' => $shift->id,
            'dokter_id' => $dokter->id,
            'tarif' => 150000,
            'jasa_dokter' => 60000,
            'jasa_paramedis' => 0,
            'jasa_non_paramedis' => 0,
        ];

        $component = Livewire::test(CreateTindakan::class)
            ->fillForm($tindakanData)
            ->call('create');

        // Assert - Tindakan created successfully
        $component->assertSuccessful();
        
        $tindakan = Tindakan::latest()->first();
        $this->assertNotNull($tindakan);
        $this->assertEquals($this->petugas->id, $tindakan->input_by);
        $this->assertEquals('pending', $tindakan->status_validasi);

        // Act 2 - Submit for validation
        $submissionResult = $this->validationService->submitForValidation($tindakan);

        // Assert - Validation submitted (not auto-approved due to amount > 100k)
        $this->assertTrue($submissionResult['success']);
        $this->assertEquals('pending', $submissionResult['status']);
        $this->assertFalse($submissionResult['auto_approved']);

        $tindakan->refresh();
        $this->assertEquals('pending', $tindakan->status_validasi);
        $this->assertNotNull($tindakan->submitted_at);

        // Verify audit log for submission
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->petugas->id,
            'action' => 'submitted',
            'model_type' => Tindakan::class,
            'model_id' => $tindakan->id,
        ]);

        // Act 3 - Supervisor approves
        $this->actingAs($this->supervisor);
        $approvalResult = $this->validationService->approve($tindakan, [
            'reason' => 'Approved by supervisor',
            'approved_by' => $this->supervisor->id,
        ]);

        // Assert - Approval successful
        $this->assertTrue($approvalResult['success']);
        $this->assertEquals('approved', $approvalResult['status']);

        $tindakan->refresh();
        $this->assertEquals('approved', $tindakan->status_validasi);
        $this->assertEquals('selesai', $tindakan->status);
        $this->assertEquals($this->supervisor->id, $tindakan->approved_by);

        // Verify audit log for approval
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->supervisor->id,
            'action' => 'approved',
            'model_type' => Tindakan::class,
            'model_id' => $tindakan->id,
        ]);

        // Verify stats service reflects approved tindakan
        $statsService = new PetugasStatsService();
        $stats = $statsService->getDashboardStats($this->petugas->id);
        $this->assertEquals(1, $stats['daily']['today']['tindakan_count']);
        $this->assertEquals(150000, $stats['daily']['today']['tindakan_sum']);
    }

    public function test_auto_approval_workflow_for_small_amounts()
    {
        // Arrange
        $this->actingAs($this->petugas);
        
        $jenisTindakan = JenisTindakan::factory()->create([
            'nama' => 'Konsultasi Sederhana',
            'tarif' => 50000, // Below auto-approval threshold
            'is_active' => true,
        ]);
        
        $patient = Pasien::factory()->create(['input_by' => $this->petugas->id]);
        $shift = Shift::factory()->create(['name' => 'Pagi', 'is_active' => true]);

        $tindakan = Tindakan::factory()->create([
            'input_by' => $this->petugas->id,
            'jenis_tindakan_id' => $jenisTindakan->id,
            'pasien_id' => $patient->id,
            'shift_id' => $shift->id,
            'tarif' => 50000,
            'status_validasi' => 'pending',
        ]);

        // Act - Submit for validation
        $result = $this->validationService->submitForValidation($tindakan);

        // Assert - Auto-approved due to low amount
        $this->assertTrue($result['success']);
        $this->assertEquals('approved', $result['status']);
        $this->assertTrue($result['auto_approved']);

        $tindakan->refresh();
        $this->assertEquals('approved', $tindakan->status_validasi);
        $this->assertEquals('selesai', $tindakan->status);
        $this->assertEquals('system', $tindakan->approved_by);

        // Verify audit log shows auto-approval
        $auditLog = AuditLog::where('model_id', $tindakan->id)
            ->where('action', 'approved')
            ->first();
        
        $this->assertNotNull($auditLog);
        $changes = json_decode($auditLog->changes, true);
        $this->assertTrue($changes['auto_approved']);
    }

    public function test_pendapatan_validation_workflow()
    {
        // Arrange
        $this->actingAs($this->petugas);
        
        $pendapatan = Pendapatan::factory()->create([
            'nama_pendapatan' => 'Konsultasi',
            'is_aktif' => true,
        ]);

        // Act 1 - Create Pendapatan Harian
        $pendapatanData = [
            'tanggal_input' => now()->format('Y-m-d'),
            'shift' => 'Pagi',
            'pendapatan_id' => $pendapatan->id,
            'nominal' => 300000,
            'deskripsi' => 'Pendapatan konsultasi hari ini',
        ];

        $component = Livewire::test(CreatePendapatanHarian::class)
            ->fillForm($pendapatanData)
            ->call('create');

        // Assert - Pendapatan created
        $component->assertSuccessful();
        
        $pendapatanHarian = PendapatanHarian::latest()->first();
        $this->assertNotNull($pendapatanHarian);
        $this->assertEquals($this->petugas->id, $pendapatanHarian->user_id);

        // Act 2 - Submit for validation
        $submissionResult = $this->validationService->submitForValidation($pendapatanHarian);

        // Assert - Should be auto-approved (300k < 500k threshold)
        $this->assertTrue($submissionResult['success']);
        $this->assertEquals('approved', $submissionResult['status']);
        $this->assertTrue($submissionResult['auto_approved']);

        $pendapatanHarian->refresh();
        $this->assertEquals('approved', $pendapatanHarian->status_validasi);

        // Verify stats service includes the pendapatan
        $statsService = new PetugasStatsService();
        $stats = $statsService->getDashboardStats($this->petugas->id);
        $this->assertEquals(300000, $stats['daily']['today']['pendapatan_sum']);
    }

    public function test_rejection_workflow_with_notification()
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
            'submitted_at' => now(),
        ]);

        // Act - Supervisor rejects
        $this->actingAs($this->supervisor);
        $rejectionResult = $this->validationService->reject($tindakan, 'Incomplete documentation', [
            'rejected_by' => $this->supervisor->id,
        ]);

        // Assert - Rejection successful
        $this->assertTrue($rejectionResult['success']);
        $this->assertEquals('rejected', $rejectionResult['status']);

        $tindakan->refresh();
        $this->assertEquals('rejected', $tindakan->status_validasi);
        $this->assertEquals('batal', $tindakan->status);
        $this->assertEquals($this->supervisor->id, $tindakan->rejected_by);
        $this->assertEquals('Incomplete documentation', $tindakan->rejection_reason);

        // Verify audit trail
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->supervisor->id,
            'action' => 'rejected',
            'model_type' => Tindakan::class,
            'model_id' => $tindakan->id,
        ]);

        // Verify stats don't include rejected tindakan in successful counts
        $statsService = new PetugasStatsService();
        $stats = $statsService->getDashboardStats($this->petugas->id);
        $this->assertEquals(0, $stats['daily']['today']['tindakan_sum']); // Rejected, so not counted
    }

    public function test_revision_request_workflow()
    {
        // Arrange
        $this->actingAs($this->petugas);
        
        $jenisTindakan = JenisTindakan::factory()->create(['tarif' => 180000]);
        $patient = Pasien::factory()->create(['input_by' => $this->petugas->id]);
        $shift = Shift::factory()->create(['name' => 'Sore', 'is_active' => true]);

        $tindakan = Tindakan::factory()->create([
            'input_by' => $this->petugas->id,
            'jenis_tindakan_id' => $jenisTindakan->id,
            'pasien_id' => $patient->id,
            'shift_id' => $shift->id,
            'tarif' => 180000,
            'status_validasi' => 'pending',
            'submitted_at' => now(),
        ]);

        // Act - Supervisor requests revision
        $this->actingAs($this->supervisor);
        $revisionResult = $this->validationService->requestRevision($tindakan, 'Please add more details about the procedure', [
            'requested_by' => $this->supervisor->id,
        ]);

        // Assert - Revision request successful
        $this->assertTrue($revisionResult['success']);
        $this->assertEquals('revision', $revisionResult['status']);

        $tindakan->refresh();
        $this->assertEquals('revision', $tindakan->status_validasi);
        $this->assertEquals($this->supervisor->id, $tindakan->revision_requested_by);
        $this->assertEquals('Please add more details about the procedure', $tindakan->revision_reason);

        // Verify audit trail
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->supervisor->id,
            'action' => 'revision_requested',
            'model_type' => Tindakan::class,
            'model_id' => $tindakan->id,
        ]);
    }

    public function test_bulk_operation_workflow()
    {
        // Arrange
        $this->actingAs($this->petugas);
        
        // Create multiple patients for bulk operations
        $patients = Pasien::factory()->count(5)->create([
            'input_by' => $this->petugas->id,
        ]);

        // Act - Bulk update patients
        $updateData = $patients->map(function ($patient, $index) {
            return [
                'id' => $patient->id,
                'alamat' => "Updated Address {$index}",
            ];
        })->toArray();

        $bulkService = new \App\Services\BulkOperationService();
        $result = $bulkService->bulkUpdate(Pasien::class, $updateData, 'id');

        // Assert - Bulk operation successful
        $this->assertTrue($result['success']);
        $this->assertEquals(5, $result['updated']);
        $this->assertEquals(0, $result['failed']);

        // Verify all patients were updated
        foreach ($patients as $index => $patient) {
            $this->assertDatabaseHas('pasien', [
                'id' => $patient->id,
                'alamat' => "Updated Address {$index}",
            ]);
        }

        // Verify stats service reflects all patients
        $statsService = new PetugasStatsService();
        $stats = $statsService->getDashboardStats($this->petugas->id);
        $this->assertEquals(5, $stats['daily']['today']['pasien_count']);
    }

    public function test_notification_flow_integration()
    {
        // Arrange
        $this->actingAs($this->petugas);
        
        $jenisTindakan = JenisTindakan::factory()->create(['tarif' => 250000]);
        $patient = Pasien::factory()->create(['input_by' => $this->petugas->id]);
        $shift = Shift::factory()->create(['name' => 'Pagi', 'is_active' => true]);

        $tindakan = Tindakan::factory()->create([
            'input_by' => $this->petugas->id,
            'jenis_tindakan_id' => $jenisTindakan->id,
            'pasien_id' => $patient->id,
            'shift_id' => $shift->id,
            'tarif' => 250000,
            'status_validasi' => 'pending',
        ]);

        // Act - Submit for validation (should trigger notifications)
        $result = $this->validationService->submitForValidation($tindakan);

        // Assert - Submission successful
        $this->assertTrue($result['success']);
        
        // Verify notification service can get pending validations
        $pendingValidations = $this->validationService->getPendingValidations($this->supervisor->id);
        $this->assertTrue($pendingValidations['success']);
        $this->assertGreaterThan(0, $pendingValidations['total']);

        // Find our tindakan in pending validations
        $ourValidation = collect($pendingValidations['data'])
            ->first(fn($item) => $item['model'] === 'Tindakan' && str_contains($item['id'], (string)$tindakan->id));
        
        $this->assertNotNull($ourValidation);
        $this->assertEquals(250000, $ourValidation['amount']);
        $this->assertEquals('medium', $ourValidation['priority']);
    }

    public function test_validation_statistics_workflow()
    {
        // Arrange
        $this->actingAs($this->petugas);
        
        // Create and process multiple validations
        $jenisTindakan = JenisTindakan::factory()->create(['tarif' => 100000]);
        $patient = Pasien::factory()->create(['input_by' => $this->petugas->id]);
        $shift = Shift::factory()->create(['name' => 'Pagi', 'is_active' => true]);

        // Create 3 tindakan
        $tindakan1 = Tindakan::factory()->create([
            'input_by' => $this->petugas->id,
            'jenis_tindakan_id' => $jenisTindakan->id,
            'pasien_id' => $patient->id,
            'shift_id' => $shift->id,
            'tarif' => 120000,
            'status_validasi' => 'pending',
        ]);

        $tindakan2 = Tindakan::factory()->create([
            'input_by' => $this->petugas->id,
            'jenis_tindakan_id' => $jenisTindakan->id,
            'pasien_id' => $patient->id,
            'shift_id' => $shift->id,
            'tarif' => 130000,
            'status_validasi' => 'pending',
        ]);

        $tindakan3 = Tindakan::factory()->create([
            'input_by' => $this->petugas->id,
            'jenis_tindakan_id' => $jenisTindakan->id,
            'pasien_id' => $patient->id,
            'shift_id' => $shift->id,
            'tarif' => 140000,
            'status_validasi' => 'pending',
        ]);

        // Act - Submit all for validation
        $this->validationService->submitForValidation($tindakan1);
        $this->validationService->submitForValidation($tindakan2);
        $this->validationService->submitForValidation($tindakan3);

        // Supervisor processes them differently
        $this->actingAs($this->supervisor);
        $this->validationService->approve($tindakan1);
        $this->validationService->approve($tindakan2);
        $this->validationService->reject($tindakan3, 'Test rejection');

        // Act - Get validation statistics
        $stats = $this->validationService->getValidationStats(Tindakan::class, 30);

        // Assert - Statistics are correct
        $this->assertTrue($stats['success']);
        $data = $stats['data'];
        
        $this->assertEquals(3, $data['total_submissions']);
        $this->assertEquals(2, $data['approved']);
        $this->assertEquals(1, $data['rejected']);
        $this->assertEquals(66.67, $data['approval_rate']); // 2/3 = 66.67%
    }

    public function test_complete_financial_workflow()
    {
        // Arrange
        $this->actingAs($this->petugas);
        
        $pendapatan = Pendapatan::factory()->create(['nama_pendapatan' => 'Konsultasi']);
        $pengeluaran = Pengeluaran::factory()->create(['nama_pengeluaran' => 'Obat']);

        // Act - Create pendapatan and pengeluaran
        $pendapatanHarian = PendapatanHarian::factory()->create([
            'user_id' => $this->petugas->id,
            'pendapatan_id' => $pendapatan->id,
            'tanggal_input' => now()->format('Y-m-d'),
            'nominal' => 400000,
            'status_validasi' => 'pending',
        ]);

        $pengeluaranHarian = PengeluaranHarian::factory()->create([
            'user_id' => $this->petugas->id,
            'pengeluaran_id' => $pengeluaran->id,
            'tanggal_input' => now()->format('Y-m-d'),
            'nominal' => 150000,
            'status_validasi' => 'pending',
        ]);

        // Submit both for validation
        $this->validationService->submitForValidation($pendapatanHarian);
        $this->validationService->submitForValidation($pengeluaranHarian);

        // Both should be auto-approved (below thresholds)
        $pendapatanHarian->refresh();
        $pengeluaranHarian->refresh();

        $this->assertEquals('approved', $pendapatanHarian->status_validasi);
        $this->assertEquals('approved', $pengeluaranHarian->status_validasi);

        // Assert - Stats reflect both transactions
        $statsService = new PetugasStatsService();
        $stats = $statsService->getDashboardStats($this->petugas->id);
        
        $this->assertEquals(400000, $stats['daily']['today']['pendapatan_sum']);
        $this->assertEquals(150000, $stats['daily']['today']['pengeluaran_sum']);
        $this->assertEquals(250000, $stats['daily']['today']['net_income']); // 400k - 150k
    }

    public function test_cache_invalidation_workflow()
    {
        // Arrange
        $this->actingAs($this->petugas);
        
        $statsService = new PetugasStatsService();
        
        // Get initial stats (should cache them)
        $initialStats = $statsService->getDashboardStats($this->petugas->id);
        $this->assertEquals(0, $initialStats['daily']['today']['pasien_count']);

        // Act - Create a patient
        $patient = Pasien::factory()->create(['input_by' => $this->petugas->id]);

        // Clear cache to simulate real-world cache invalidation
        $statsService->clearStatsCache($this->petugas->id);

        // Get updated stats
        $updatedStats = $statsService->getDashboardStats($this->petugas->id);

        // Assert - Stats reflect new patient
        $this->assertEquals(1, $updatedStats['daily']['today']['pasien_count']);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}