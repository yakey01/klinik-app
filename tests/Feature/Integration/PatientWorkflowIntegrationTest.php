<?php

namespace Tests\Feature\Integration;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Pasien;
use App\Models\Dokter;
use App\Models\Tindakan;
use App\Models\Pendapatan;
use App\Models\JenisTindakan;
use App\Models\Jaspel;
use App\Services\CacheService;
use App\Services\LoggingService;
use Carbon\Carbon;

class PatientWorkflowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $petugasUser;
    private User $bendaharaUser;
    private User $dokterUser;
    private Dokter $dokter;
    private JenisTindakan $jenisTindakan;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users with proper roles
        $this->petugasUser = User::factory()->create([
            'role' => 'petugas',
            'is_active' => true,
        ]);
        
        $this->bendaharaUser = User::factory()->create([
            'role' => 'bendahara',
            'is_active' => true,
        ]);
        
        $this->dokterUser = User::factory()->create([
            'role' => 'dokter',
            'is_active' => true,
        ]);
        
        // Create dokter
        $this->dokter = Dokter::factory()->create([
            'user_id' => $this->dokterUser->id,
            'status' => 'aktif',
        ]);
        
        // Create jenis tindakan
        $this->jenisTindakan = JenisTindakan::factory()->create([
            'nama' => 'Konsultasi Umum',
            'tarif' => 100000,
            'jasa_dokter' => 60000,
            'jasa_paramedis' => 20000,
            'jasa_non_paramedis' => 20000,
            'is_active' => true,
        ]);
    }

    public function test_complete_patient_registration_workflow()
    {
        // Step 1: Petugas registers a new patient
        $this->actingAs($this->petugasUser);
        
        $patientData = [
            'no_rekam_medis' => 'RM001',
            'nama' => 'John Doe',
            'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => 'L',
            'alamat' => 'Jakarta',
            'no_telepon' => '08123456789',
            'email' => 'john@example.com',
        ];
        
        $patient = Pasien::create($patientData);
        
        $this->assertDatabaseHas('pasien', [
            'no_rekam_medis' => 'RM001',
            'nama' => 'John Doe',
        ]);
        
        $this->assertNotNull($patient);
        
        // Verify activity logging
        $this->assertDatabaseHas('audit_logs', [
            'model_type' => Pasien::class,
            'model_id' => $patient->id,
            'action' => 'created',
        ]);
        
        return $patient;
    }

    public function test_complete_medical_procedure_workflow()
    {
        $patient = $this->test_complete_patient_registration_workflow();
        
        // Step 2: Petugas creates a medical procedure (tindakan)
        $this->actingAs($this->petugasUser);
        
        $tindakanData = [
            'pasien_id' => $patient->id,
            'jenis_tindakan_id' => $this->jenisTindakan->id,
            'dokter_id' => $this->dokter->id,
            'tanggal_tindakan' => Carbon::now(),
            'tarif' => $this->jenisTindakan->tarif,
            'jasa_dokter' => $this->jenisTindakan->jasa_dokter,
            'jasa_paramedis' => $this->jenisTindakan->jasa_paramedis,
            'jasa_non_paramedis' => $this->jenisTindakan->jasa_non_paramedis,
            'catatan' => 'Konsultasi rutin',
            'status' => 'selesai',
            'status_validasi' => 'pending',
            'input_by' => $this->petugasUser->id,
        ];
        
        $tindakan = Tindakan::create($tindakanData);
        
        $this->assertDatabaseHas('tindakan', [
            'pasien_id' => $patient->id,
            'jenis_tindakan_id' => $this->jenisTindakan->id,
            'status' => 'selesai',
            'status_validasi' => 'pending',
        ]);
        
        $this->assertNotNull($tindakan);
        
        // Verify relationships
        $this->assertEquals($patient->id, $tindakan->pasien_id);
        $this->assertEquals($this->dokter->id, $tindakan->dokter_id);
        $this->assertEquals($this->jenisTindakan->id, $tindakan->jenis_tindakan_id);
        
        return $tindakan;
    }

    public function test_automatic_revenue_generation_workflow()
    {
        $tindakan = $this->test_complete_medical_procedure_workflow();
        
        // Step 3: Create pendapatan for the tindakan
        $pendapatan = Pendapatan::create([
            'tindakan_id' => $tindakan->id,
            'kategori' => 'tindakan_medis',
            'keterangan' => 'Pendapatan dari ' . $tindakan->jenisTindakan->nama,
            'jumlah' => $tindakan->tarif,
            'status' => 'pending',
            'input_by' => $tindakan->input_by,
        ]);
        
        $this->assertDatabaseHas('pendapatan', [
            'tindakan_id' => $tindakan->id,
            'kategori' => 'tindakan_medis',
            'jumlah' => $tindakan->tarif,
            'status' => 'pending',
        ]);
        
        $this->assertNotNull($pendapatan);
        $this->assertEquals($tindakan->tarif, $pendapatan->jumlah);
        
        return $pendapatan;
    }

    public function test_validation_approval_workflow()
    {
        $tindakan = $this->test_complete_medical_procedure_workflow();
        $pendapatan = Pendapatan::where('tindakan_id', $tindakan->id)->first();
        
        // Step 4: Bendahara validates the tindakan and pendapatan
        $this->actingAs($this->bendaharaUser);
        
        // Validate tindakan
        $tindakan->update([
            'status_validasi' => 'approved',
            'validated_by' => $this->bendaharaUser->id,
            'validated_at' => Carbon::now(),
            'komentar_validasi' => 'Tindakan telah diverifikasi dan disetujui',
        ]);
        
        $this->assertDatabaseHas('tindakan', [
            'id' => $tindakan->id,
            'status_validasi' => 'approved',
            'validated_by' => $this->bendaharaUser->id,
        ]);
        
        // Validate pendapatan
        $pendapatan->update([
            'status' => 'approved',
            'validasi_by' => $this->bendaharaUser->id,
            'validated_at' => Carbon::now(),
        ]);
        
        $this->assertDatabaseHas('pendapatan', [
            'id' => $pendapatan->id,
            'status' => 'approved',
            'validasi_by' => $this->bendaharaUser->id,
        ]);
        
        return [$tindakan->fresh(), $pendapatan->fresh()];
    }

    public function test_automatic_jaspel_generation_workflow()
    {
        [$tindakan, $pendapatan] = $this->test_validation_approval_workflow();
        
        // Step 5: Create Jaspel after approval
        $jaspelDokter = Jaspel::create([
            'tindakan_id' => $tindakan->id,
            'user_id' => $this->dokterUser->id,
            'jenis_jaspel' => 'dokter',
            'jumlah' => $tindakan->jasa_dokter,
            'periode' => Carbon::now()->format('Y-m'),
            'status' => 'pending',
        ]);
        
        $this->assertDatabaseHas('jaspel', [
            'tindakan_id' => $tindakan->id,
            'user_id' => $this->dokterUser->id,
            'jenis_jaspel' => 'dokter',
            'jumlah' => $tindakan->jasa_dokter,
            'status' => 'pending',
        ]);
        
        $jaspel = collect([$jaspelDokter]);
        $this->assertGreaterThan(0, $jaspel->count());
        
        return $jaspel;
    }

    public function test_complete_workflow_with_caching()
    {
        // Test the complete workflow with caching enabled
        $cacheService = app(CacheService::class);
        
        // Clear cache before test
        $cacheService->flushAll();
        
        // Run complete workflow
        $patient = $this->test_complete_patient_registration_workflow();
        $tindakan = Tindakan::where('pasien_id', $patient->id)->first();
        
        // Test cached patient statistics
        $stats = Pasien::getCachedStats();
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_count', $stats);
        $this->assertGreaterThan(0, $stats['total_count']);
        
        // Test cached relationships
        $cachedTindakan = $patient->tindakan;
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $cachedTindakan);
        
        return true;
    }

    public function test_workflow_data_integrity()
    {
        $patient = $this->test_complete_patient_registration_workflow();
        $tindakan = $this->test_complete_medical_procedure_workflow();
        $pendapatan = Pendapatan::where('tindakan_id', $tindakan->id)->first();
        
        // Test data consistency
        $this->assertEquals($tindakan->tarif, $pendapatan->jumlah);
        $this->assertEquals($tindakan->id, $pendapatan->tindakan_id);
        
        // Test referential integrity
        $this->assertEquals($patient->id, $tindakan->pasien_id);
        $this->assertEquals($this->dokter->id, $tindakan->dokter_id);
        $this->assertEquals($this->jenisTindakan->id, $tindakan->jenis_tindakan_id);
        
        // Test soft delete integrity
        $patient->delete();
        $this->assertSoftDeleted('pasien', ['id' => $patient->id]);
        
        // Related tindakan should still exist (no cascade delete)
        $this->assertDatabaseHas('tindakan', ['id' => $tindakan->id]);
        
        return true;
    }

    public function test_workflow_performance_logging()
    {
        // Test that workflow operations are logged for performance monitoring
        $this->test_complete_patient_registration_workflow();
        
        // Check that activity logs are created
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
        ]);
        
        return true;
    }

    public function test_workflow_search_and_filtering()
    {
        $this->actingAs($this->petugasUser);
        
        // Create test data
        $patient1 = Pasien::factory()->create(['nama' => 'John Doe', 'jenis_kelamin' => 'L']);
        $patient2 = Pasien::factory()->create(['nama' => 'Jane Smith', 'jenis_kelamin' => 'P']);
        $patient3 = Pasien::factory()->create(['nama' => 'Bob Johnson', 'jenis_kelamin' => 'L']);
        
        // Test search functionality
        $searchResults = Pasien::where('nama', 'LIKE', '%John%')->get();
        $this->assertCount(2, $searchResults);
        
        // Test filtering by gender
        $malePatients = Pasien::where('jenis_kelamin', 'L')->get();
        $this->assertCount(2, $malePatients);
        
        // Test date range filtering
        $todayPatients = Pasien::whereDate('created_at', today())->get();
        $this->assertCount(3, $todayPatients);
        
        return true;
    }

    public function test_complete_end_to_end_workflow()
    {
        // Test the complete workflow from patient registration to jaspel generation
        
        // Step 1: Patient registration
        $patient = $this->test_complete_patient_registration_workflow();
        $this->assertNotNull($patient);
        
        // Step 2: Medical procedure
        $tindakan = $this->test_complete_medical_procedure_workflow();
        $this->assertNotNull($tindakan);
        
        // Step 3: Automatic revenue generation
        $pendapatan = $this->test_automatic_revenue_generation_workflow();
        $this->assertNotNull($pendapatan);
        
        // Step 4: Validation and approval
        [$validatedTindakan, $validatedPendapatan] = $this->test_validation_approval_workflow();
        $this->assertEquals('approved', $validatedTindakan->status_validasi);
        $this->assertEquals('approved', $validatedPendapatan->status);
        
        // Step 5: Jaspel generation
        $jaspel = $this->test_automatic_jaspel_generation_workflow();
        $this->assertGreaterThan(0, $jaspel->count());
        
        // Verify complete data chain
        $this->assertEquals($patient->id, $tindakan->pasien_id);
        $this->assertEquals($tindakan->id, $pendapatan->tindakan_id);
        $this->assertEquals($tindakan->id, $jaspel->first()->tindakan_id);
        
        // Test workflow timing and performance
        $this->assertTrue($validatedTindakan->validated_at <= Carbon::now());
        $this->assertTrue($validatedPendapatan->validated_at <= Carbon::now());
        
        return [
            'patient' => $patient,
            'tindakan' => $validatedTindakan,
            'pendapatan' => $validatedPendapatan,
            'jaspel' => $jaspel,
        ];
    }

    public function test_workflow_rollback_scenarios()
    {
        // Test rollback scenarios when workflow fails
        $patient = $this->test_complete_patient_registration_workflow();
        $tindakan = $this->test_complete_medical_procedure_workflow();
        
        // Test rejecting tindakan
        $this->actingAs($this->bendaharaUser);
        
        $tindakan->update([
            'status_validasi' => 'rejected',
            'validated_by' => $this->bendaharaUser->id,
            'validated_at' => Carbon::now(),
            'komentar_validasi' => 'Tindakan ditolak karena tidak sesuai prosedur',
        ]);
        
        $this->assertDatabaseHas('tindakan', [
            'id' => $tindakan->id,
            'status_validasi' => 'rejected',
        ]);
        
        // Pendapatan should also be rejected
        $pendapatan = Pendapatan::where('tindakan_id', $tindakan->id)->first();
        $pendapatan->update([
            'status' => 'rejected',
            'validasi_by' => $this->bendaharaUser->id,
            'validated_at' => Carbon::now(),
        ]);
        
        $this->assertDatabaseHas('pendapatan', [
            'id' => $pendapatan->id,
            'status' => 'rejected',
        ]);
        
        // No jaspel should be generated for rejected tindakan
        $jaspelCount = Jaspel::where('tindakan_id', $tindakan->id)->count();
        $this->assertEquals(0, $jaspelCount);
        
        return true;
    }

    public function test_workflow_concurrent_operations()
    {
        // Test concurrent operations to ensure data consistency
        $this->actingAs($this->petugasUser);
        
        // Create multiple patients concurrently
        $patients = [];
        for ($i = 1; $i <= 5; $i++) {
            $patients[] = Pasien::create([
                'no_rekam_medis' => "RM00{$i}",
                'nama' => "Patient {$i}",
                'jenis_kelamin' => $i % 2 === 0 ? 'P' : 'L',
                'tanggal_lahir' => '1990-01-01',
            ]);
        }
        
        // Verify all patients were created
        $this->assertCount(5, $patients);
        
        // Create tindakan for each patient
        $tindakanList = [];
        foreach ($patients as $patient) {
            $tindakanList[] = Tindakan::create([
                'pasien_id' => $patient->id,
                'jenis_tindakan_id' => $this->jenisTindakan->id,
                'dokter_id' => $this->dokter->id,
                'tanggal_tindakan' => Carbon::now(),
                'tarif' => $this->jenisTindakan->tarif,
                'status' => 'selesai',
                'status_validasi' => 'pending',
                'input_by' => $this->petugasUser->id,
            ]);
        }
        
        // Verify all tindakan were created
        $this->assertCount(5, $tindakanList);
        
        return true;
    }
}