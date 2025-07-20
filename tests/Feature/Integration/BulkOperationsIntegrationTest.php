<?php

namespace Tests\Feature\Integration;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Pasien;
use App\Models\Dokter;
use App\Models\Tindakan;
use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use App\Models\JenisTindakan;
use App\Models\Jaspel;
use App\Models\Role;
use App\Services\CacheService;
use App\Models\BulkOperation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class BulkOperationsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $petugasUser;
    private User $bendaharaUser;
    private User $adminUser;
    private Dokter $dokter;
    private JenisTindakan $jenisTindakan;

    protected function setUp(): void
        // Roles are already created by base TestCase
    {
        parent::setUp();
        
        // Create roles first
        
        // Create test users
        $this->petugasUser = User::factory()->create([
            'role_id' => $petugasRole->id,
            'is_active' => true,
        ]);
        
        $this->bendaharaUser = User::factory()->create([
            'role_id' => $bendaharaRole->id,
            'is_active' => true,
        ]);
        
        $this->adminUser = User::factory()->create([
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);
        
        // Create dokter
        $this->dokter = Dokter::factory()->create([
            'user_id' => User::factory()->create(['role_id' => $dokterRole->id])->id,
            'aktif' => true,
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

    public function test_bulk_patient_import_csv()
    {
        $this->actingAs($this->petugasUser);
        
        // Create CSV content for bulk patient import
        $csvContent = "no_rekam_medis,nama,tanggal_lahir,jenis_kelamin,alamat,no_telepon,email\n";
        $csvContent .= "RM001,John Doe,1990-01-01,L,Jakarta,08123456789,john@example.com\n";
        $csvContent .= "RM002,Jane Smith,1985-05-15,P,Bandung,08234567890,jane@example.com\n";
        $csvContent .= "RM003,Bob Johnson,1992-08-20,L,Surabaya,08345678901,bob@example.com\n";
        $csvContent .= "RM004,Alice Brown,1988-12-10,P,Medan,08456789012,alice@example.com\n";
        $csvContent .= "RM005,Charlie Wilson,1995-03-25,L,Yogyakarta,08567890123,charlie@example.com\n";
        
        // Save CSV to storage
        Storage::fake('local');
        $csvFile = UploadedFile::fake()->createWithContent('patients.csv', $csvContent);
        
        $bulkOperation = BulkOperation::create([
            'type' => 'import',
            'model' => 'Pasien',
            'file_path' => $csvFile->store('bulk_imports'),
            'status' => 'pending',
            'total_records' => 5,
            'processed_records' => 0,
            'failed_records' => 0,
            'user_id' => $this->petugasUser->id,
            'metadata' => json_encode(['format' => 'csv', 'delimiter' => ',']),
        ]);
        
        // Process bulk import
        $this->processBulkPatientImport($bulkOperation);
        
        // Verify patients were created
        $this->assertEquals(5, Pasien::count());
        
        // Verify specific records
        $this->assertDatabaseHas('pasien', [
            'no_rekam_medis' => 'RM001',
            'nama' => 'John Doe',
            'jenis_kelamin' => 'L',
        ]);
        
        $this->assertDatabaseHas('pasien', [
            'no_rekam_medis' => 'RM005',
            'nama' => 'Charlie Wilson',
            'jenis_kelamin' => 'L',
        ]);
        
        // Verify bulk operation was updated
        $bulkOperation->refresh();
        $this->assertEquals('completed', $bulkOperation->status);
        $this->assertEquals(5, $bulkOperation->processed_records);
        $this->assertEquals(0, $bulkOperation->failed_records);
        
        return $bulkOperation;
    }

    public function test_bulk_tindakan_creation_with_validation()
    {
        $this->actingAs($this->petugasUser);
        
        // Create patients first
        $patients = Pasien::factory()->count(10)->create();
        
        // Prepare bulk tindakan data
        $tindakanData = [];
        foreach ($patients as $index => $patient) {
            $tindakanData[] = [
                'pasien_id' => $patient->id,
                'jenis_tindakan_id' => $this->jenisTindakan->id,
                'dokter_id' => $this->dokter->id,
                'tanggal_tindakan' => Carbon::now()->subDays($index),
                'tarif' => $this->jenisTindakan->tarif,
                'jasa_dokter' => $this->jenisTindakan->jasa_dokter,
                'jasa_paramedis' => $this->jenisTindakan->jasa_paramedis,
                'jasa_non_paramedis' => $this->jenisTindakan->jasa_non_paramedis,
                'catatan' => "Bulk tindakan #{$index}",
                'status' => 'selesai',
                'status_validasi' => 'pending',
                'input_by' => $this->petugasUser->id,
            ];
        }
        
        // Record bulk operation
        $bulkOperation = BulkOperation::create([
            'type' => 'create',
            'model' => 'Tindakan',
            'status' => 'processing',
            'total_records' => count($tindakanData),
            'processed_records' => 0,
            'user_id' => $this->petugasUser->id,
            'metadata' => json_encode(['batch_size' => 5]),
        ]);
        
        // Process in batches for performance
        $startTime = microtime(true);
        
        DB::beginTransaction();
        try {
            foreach (array_chunk($tindakanData, 5) as $batch) {
                Tindakan::insert($batch);
                $bulkOperation->increment('processed_records', count($batch));
            }
            DB::commit();
            
            $bulkOperation->update(['status' => 'completed']);
            
        } catch (\Exception $e) {
            DB::rollback();
            $bulkOperation->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
            throw $e;
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Verify all tindakan were created
        $this->assertEquals(10, Tindakan::count());
        
        // Verify bulk operation tracking
        $bulkOperation->refresh();
        $this->assertEquals('completed', $bulkOperation->status);
        $this->assertEquals(10, $bulkOperation->processed_records);
        
        // Performance assertion (should complete within 1 second)
        $this->assertLessThan(1.0, $executionTime, 'Bulk tindakan creation took too long');
        
        // Verify data integrity
        $this->assertEquals(10, Tindakan::where('status_validasi', 'pending')->count());
        $this->assertEquals(10, Tindakan::where('input_by', $this->petugasUser->id)->count());
        
        return $bulkOperation;
    }

    public function test_bulk_validation_approval_workflow()
    {
        // Create test data using previous method
        $bulkOperation = $this->test_bulk_tindakan_creation_with_validation();
        
        $this->actingAs($this->bendaharaUser);
        
        // Get all pending tindakan
        $pendingTindakan = Tindakan::where('status_validasi', 'pending')->get();
        $this->assertCount(10, $pendingTindakan);
        
        // Bulk approve all tindakan
        $bulkValidationOperation = BulkOperation::create([
            'type' => 'update',
            'model' => 'Tindakan',
            'status' => 'processing',
            'total_records' => $pendingTindakan->count(),
            'processed_records' => 0,
            'user_id' => $this->bendaharaUser->id,
            'metadata' => json_encode([
                'operation' => 'bulk_validation',
                'validation_status' => 'approved',
                'comment' => 'Bulk approval for end-of-month processing'
            ]),
        ]);
        
        $startTime = microtime(true);
        
        // Perform bulk validation
        DB::beginTransaction();
        try {
            $updatedCount = DB::table('tindakan')
                ->whereIn('id', $pendingTindakan->pluck('id'))
                ->update([
                    'status_validasi' => 'approved',
                    'validated_by' => $this->bendaharaUser->id,
                    'validated_at' => Carbon::now(),
                    'komentar_validasi' => 'Bulk approval for end-of-month processing',
                    'updated_at' => Carbon::now(),
                ]);
            
            $bulkValidationOperation->update([
                'status' => 'completed',
                'processed_records' => $updatedCount
            ]);
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            $bulkValidationOperation->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
            throw $e;
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Verify all tindakan were approved
        $this->assertEquals(10, Tindakan::where('status_validasi', 'approved')->count());
        $this->assertEquals(0, Tindakan::where('status_validasi', 'pending')->count());
        
        // Verify validation details
        $approvedTindakan = Tindakan::where('status_validasi', 'approved')->get();
        foreach ($approvedTindakan as $tindakan) {
            $this->assertEquals($this->bendaharaUser->id, $tindakan->validated_by);
            $this->assertNotNull($tindakan->validated_at);
            $this->assertEquals('Bulk approval for end-of-month processing', $tindakan->komentar_validasi);
        }
        
        // Performance assertion
        $this->assertLessThan(0.5, $executionTime, 'Bulk validation took too long');
        
        return $bulkValidationOperation;
    }

    public function test_bulk_pendapatan_generation_from_approved_tindakan()
    {
        // First create and approve tindakan
        $this->test_bulk_validation_approval_workflow();
        
        $this->actingAs($this->petugasUser);
        
        // Get all approved tindakan
        $approvedTindakan = Tindakan::where('status_validasi', 'approved')->get();
        $this->assertCount(10, $approvedTindakan);
        
        // Generate bulk pendapatan
        $pendapatanData = [];
        foreach ($approvedTindakan as $tindakan) {
            $pendapatanData[] = [
                'tindakan_id' => $tindakan->id,
                'kategori' => 'tindakan_medis',
                'keterangan' => 'Pendapatan dari ' . $tindakan->jenisTindakan->nama,
                'jumlah' => $tindakan->tarif,
                'status' => 'pending',
                'input_by' => $this->petugasUser->id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
        }
        
        $bulkOperation = BulkOperation::create([
            'type' => 'create',
            'model' => 'Pendapatan',
            'status' => 'processing',
            'total_records' => count($pendapatanData),
            'processed_records' => 0,
            'user_id' => $this->petugasUser->id,
            'metadata' => json_encode([
                'source' => 'approved_tindakan',
                'batch_size' => 10
            ]),
        ]);
        
        $startTime = microtime(true);
        
        // Bulk insert pendapatan
        DB::beginTransaction();
        try {
            Pendapatan::insert($pendapatanData);
            
            $bulkOperation->update([
                'status' => 'completed',
                'processed_records' => count($pendapatanData)
            ]);
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            $bulkOperation->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
            throw $e;
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Verify pendapatan were created
        $this->assertEquals(10, Pendapatan::count());
        
        // Verify financial calculations
        $totalPendapatan = Pendapatan::sum('jumlah');
        $expectedTotal = $approvedTindakan->sum('tarif');
        $this->assertEquals($expectedTotal, $totalPendapatan);
        
        // Verify each pendapatan links to correct tindakan
        foreach ($approvedTindakan as $tindakan) {
            $this->assertDatabaseHas('pendapatan', [
                'tindakan_id' => $tindakan->id,
                'jumlah' => $tindakan->tarif,
                'status' => 'pending',
            ]);
        }
        
        // Performance assertion
        $this->assertLessThan(0.3, $executionTime, 'Bulk pendapatan generation took too long');
        
        return $bulkOperation;
    }

    public function test_bulk_jaspel_calculation_and_distribution()
    {
        // First generate pendapatan
        $this->test_bulk_pendapatan_generation_from_approved_tindakan();
        
        $this->actingAs($this->bendaharaUser);
        
        // Get all approved tindakan for jaspel calculation
        $approvedTindakan = Tindakan::where('status_validasi', 'approved')->get();
        $this->assertCount(10, $approvedTindakan);
        
        // Prepare jaspel data for all staff types
        $jaspelData = [];
        $periode = Carbon::now()->format('Y-m');
        
        foreach ($approvedTindakan as $tindakan) {
            // Jaspel for dokter
            $jaspelData[] = [
                'tindakan_id' => $tindakan->id,
                'user_id' => $tindakan->dokter->user_id,
                'jenis_jaspel' => 'dokter',
                'jumlah' => $tindakan->jasa_dokter,
                'periode' => $periode,
                'status' => 'pending',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
            
            // Jaspel for paramedis (use petugas as example)
            $jaspelData[] = [
                'tindakan_id' => $tindakan->id,
                'user_id' => $this->petugasUser->id,
                'jenis_jaspel' => 'paramedis',
                'jumlah' => $tindakan->jasa_paramedis,
                'periode' => $periode,
                'status' => 'pending',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
            
            // Jaspel for non-paramedis (use admin as example)
            $jaspelData[] = [
                'tindakan_id' => $tindakan->id,
                'user_id' => $this->adminUser->id,
                'jenis_jaspel' => 'non_paramedis',
                'jumlah' => $tindakan->jasa_non_paramedis,
                'periode' => $periode,
                'status' => 'pending',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
        }
        
        $bulkOperation = BulkOperation::create([
            'type' => 'create',
            'model' => 'Jaspel',
            'status' => 'processing',
            'total_records' => count($jaspelData),
            'processed_records' => 0,
            'user_id' => $this->bendaharaUser->id,
            'metadata' => json_encode([
                'periode' => $periode,
                'jaspel_types' => ['dokter', 'paramedis', 'non_paramedis'],
                'batch_size' => 15
            ]),
        ]);
        
        $startTime = microtime(true);
        
        // Process jaspel in batches
        DB::beginTransaction();
        try {
            foreach (array_chunk($jaspelData, 15) as $batch) {
                Jaspel::insert($batch);
                $bulkOperation->increment('processed_records', count($batch));
            }
            
            $bulkOperation->update(['status' => 'completed']);
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollback();
            $bulkOperation->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
            throw $e;
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Verify jaspel records were created (10 tindakan × 3 jaspel types = 30 records)
        $this->assertEquals(30, Jaspel::count());
        
        // Verify jaspel distribution by type
        $this->assertEquals(10, Jaspel::where('jenis_jaspel', 'dokter')->count());
        $this->assertEquals(10, Jaspel::where('jenis_jaspel', 'paramedis')->count());
        $this->assertEquals(10, Jaspel::where('jenis_jaspel', 'non_paramedis')->count());
        
        // Verify financial calculations
        $totalJaspelDokter = Jaspel::where('jenis_jaspel', 'dokter')->sum('jumlah');
        $totalJaspelParamedis = Jaspel::where('jenis_jaspel', 'paramedis')->sum('jumlah');
        $totalJaspelNonParamedis = Jaspel::where('jenis_jaspel', 'non_paramedis')->sum('jumlah');
        
        $expectedDokter = $approvedTindakan->sum('jasa_dokter');
        $expectedParamedis = $approvedTindakan->sum('jasa_paramedis');
        $expectedNonParamedis = $approvedTindakan->sum('jasa_non_paramedis');
        
        $this->assertEquals($expectedDokter, $totalJaspelDokter);
        $this->assertEquals($expectedParamedis, $totalJaspelParamedis);
        $this->assertEquals($expectedNonParamedis, $totalJaspelNonParamedis);
        
        // Performance assertion
        $this->assertLessThan(0.5, $executionTime, 'Bulk jaspel calculation took too long');
        
        return $bulkOperation;
    }

    public function test_bulk_export_patient_data_csv()
    {
        // Create test patients first
        $this->test_bulk_patient_import_csv();
        
        $this->actingAs($this->adminUser);
        
        // Get all patients for export
        $patients = Pasien::all();
        $this->assertCount(5, $patients);
        
        $bulkOperation = BulkOperation::create([
            'type' => 'export',
            'model' => 'Pasien',
            'status' => 'processing',
            'total_records' => $patients->count(),
            'processed_records' => 0,
            'user_id' => $this->adminUser->id,
            'metadata' => json_encode([
                'format' => 'csv',
                'fields' => ['no_rekam_medis', 'nama', 'tanggal_lahir', 'jenis_kelamin', 'alamat', 'no_telepon', 'email']
            ]),
        ]);
        
        Storage::fake('local');
        
        $startTime = microtime(true);
        
        // Generate CSV export
        $csvContent = "no_rekam_medis,nama,tanggal_lahir,jenis_kelamin,alamat,no_telepon,email\n";
        
        foreach ($patients as $patient) {
            $csvContent .= implode(',', [
                $patient->no_rekam_medis,
                '"' . $patient->nama . '"',
                $patient->tanggal_lahir->format('Y-m-d'),
                $patient->jenis_kelamin,
                '"' . $patient->alamat . '"',
                $patient->no_telepon,
                $patient->email,
            ]) . "\n";
            
            $bulkOperation->increment('processed_records');
        }
        
        // Save export file
        $filename = 'exports/patients_' . Carbon::now()->format('Y-m-d_H-i-s') . '.csv';
        Storage::put($filename, $csvContent);
        
        $bulkOperation->update([
            'status' => 'completed',
            'file_path' => $filename
        ]);
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Verify export file exists
        Storage::assertExists($filename);
        
        // Verify export content
        $exportedContent = Storage::get($filename);
        $this->assertStringContains('John Doe', $exportedContent);
        $this->assertStringContains('RM001', $exportedContent);
        $this->assertStringContains('Charlie Wilson', $exportedContent);
        
        // Verify bulk operation was completed
        $bulkOperation->refresh();
        $this->assertEquals('completed', $bulkOperation->status);
        $this->assertEquals(5, $bulkOperation->processed_records);
        $this->assertNotNull($bulkOperation->file_path);
        
        // Performance assertion
        $this->assertLessThan(0.2, $executionTime, 'Bulk export took too long');
        
        return $bulkOperation;
    }

    public function test_bulk_delete_with_soft_deletes()
    {
        // Create test data first
        $this->test_bulk_patient_import_csv();
        
        $this->actingAs($this->adminUser);
        
        // Get patients to delete (delete first 3)
        $patientsToDelete = Pasien::take(3)->get();
        $this->assertCount(3, $patientsToDelete);
        
        $bulkOperation = BulkOperation::create([
            'type' => 'delete',
            'model' => 'Pasien',
            'status' => 'processing',
            'total_records' => $patientsToDelete->count(),
            'processed_records' => 0,
            'user_id' => $this->adminUser->id,
            'metadata' => json_encode([
                'soft_delete' => true,
                'reason' => 'Data cleanup - test records'
            ]),
        ]);
        
        $startTime = microtime(true);
        
        // Perform bulk soft delete
        DB::beginTransaction();
        try {
            $deletedCount = 0;
            foreach ($patientsToDelete as $patient) {
                $patient->delete(); // Soft delete
                $deletedCount++;
                $bulkOperation->increment('processed_records');
            }
            
            $bulkOperation->update(['status' => 'completed']);
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollback();
            $bulkOperation->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
            throw $e;
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Verify soft deletes
        $this->assertEquals(2, Pasien::count()); // 5 - 3 = 2 remaining
        $this->assertEquals(3, Pasien::onlyTrashed()->count()); // 3 soft deleted
        $this->assertEquals(5, Pasien::withTrashed()->count()); // 5 total
        
        // Verify specific records were soft deleted
        $this->assertSoftDeleted('pasien', ['no_rekam_medis' => 'RM001']);
        $this->assertSoftDeleted('pasien', ['no_rekam_medis' => 'RM002']);
        $this->assertSoftDeleted('pasien', ['no_rekam_medis' => 'RM003']);
        
        // Verify remaining records are still active
        $this->assertDatabaseHas('pasien', ['no_rekam_medis' => 'RM004', 'deleted_at' => null]);
        $this->assertDatabaseHas('pasien', ['no_rekam_medis' => 'RM005', 'deleted_at' => null]);
        
        // Verify bulk operation
        $bulkOperation->refresh();
        $this->assertEquals('completed', $bulkOperation->status);
        $this->assertEquals(3, $bulkOperation->processed_records);
        
        // Performance assertion
        $this->assertLessThan(0.1, $executionTime, 'Bulk delete took too long');
        
        return $bulkOperation;
    }

    public function test_bulk_operation_error_handling_and_rollback()
    {
        $this->actingAs($this->petugasUser);
        
        // Create invalid tindakan data (missing required fields)
        $invalidTindakanData = [
            [
                'pasien_id' => 999999, // Non-existent patient
                'jenis_tindakan_id' => $this->jenisTindakan->id,
                'dokter_id' => $this->dokter->id,
                'tanggal_tindakan' => Carbon::now(),
                'status' => 'selesai',
                'input_by' => $this->petugasUser->id,
            ],
            [
                'pasien_id' => null, // Invalid null
                'jenis_tindakan_id' => $this->jenisTindakan->id,
                'dokter_id' => $this->dokter->id,
                'tanggal_tindakan' => Carbon::now(),
                'status' => 'selesai',
                'input_by' => $this->petugasUser->id,
            ]
        ];
        
        $bulkOperation = BulkOperation::create([
            'type' => 'create',
            'model' => 'Tindakan',
            'status' => 'processing',
            'total_records' => count($invalidTindakanData),
            'processed_records' => 0,
            'user_id' => $this->petugasUser->id,
            'metadata' => json_encode(['test_error_handling' => true]),
        ]);
        
        // Attempt bulk insert with invalid data
        DB::beginTransaction();
        try {
            Tindakan::insert($invalidTindakanData);
            
            // This should not be reached
            $this->fail('Expected exception was not thrown');
            
        } catch (\Exception $e) {
            DB::rollback();
            
            $bulkOperation->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'failed_records' => count($invalidTindakanData)
            ]);
        }
        
        // Verify no tindakan were created due to rollback
        $this->assertEquals(0, Tindakan::count());
        
        // Verify bulk operation recorded the failure
        $bulkOperation->refresh();
        $this->assertEquals('failed', $bulkOperation->status);
        $this->assertNotNull($bulkOperation->error_message);
        $this->assertEquals(2, $bulkOperation->failed_records);
        $this->assertEquals(0, $bulkOperation->processed_records);
        
        return $bulkOperation;
    }

    public function test_bulk_operation_progress_tracking()
    {
        $this->actingAs($this->petugasUser);
        
        // Create large dataset for progress tracking
        $patients = Pasien::factory()->count(20)->create();
        
        $bulkOperation = BulkOperation::create([
            'type' => 'update',
            'model' => 'Pasien',
            'status' => 'processing',
            'total_records' => $patients->count(),
            'processed_records' => 0,
            'user_id' => $this->petugasUser->id,
            'metadata' => json_encode([
                'operation' => 'update_contact_info',
                'batch_size' => 5
            ]),
        ]);
        
        $startTime = microtime(true);
        
        // Process in batches with progress tracking
        $batchSize = 5;
        foreach ($patients->chunk($batchSize) as $batch) {
            DB::beginTransaction();
            try {
                foreach ($batch as $patient) {
                    $patient->update([
                        'alamat' => 'Updated Address - ' . $patient->id,
                        'updated_at' => Carbon::now(),
                    ]);
                }
                
                // Update progress
                $bulkOperation->increment('processed_records', $batch->count());
                
                DB::commit();
                
                // Simulate realistic processing time
                usleep(10000); // 10ms delay per batch
                
            } catch (\Exception $e) {
                DB::rollback();
                $bulkOperation->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage()
                ]);
                throw $e;
            }
        }
        
        $bulkOperation->update(['status' => 'completed']);
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Verify all records were processed
        $bulkOperation->refresh();
        $this->assertEquals('completed', $bulkOperation->status);
        $this->assertEquals(20, $bulkOperation->processed_records);
        $this->assertEquals(20, $bulkOperation->total_records);
        
        // Verify data was actually updated
        $updatedCount = Pasien::where('alamat', 'LIKE', 'Updated Address -%')->count();
        $this->assertEquals(20, $updatedCount);
        
        // Calculate progress percentage
        $progressPercentage = ($bulkOperation->processed_records / $bulkOperation->total_records) * 100;
        $this->assertEquals(100, $progressPercentage);
        
        // Performance assertion (with processing delays, should still be reasonable)
        $this->assertLessThan(2.0, $executionTime, 'Bulk operation with progress tracking took too long');
        
        return $bulkOperation;
    }

    public function test_bulk_operations_cache_integration()
    {
        // Test cache invalidation during bulk operations
        $cacheService = app(CacheService::class);
        
        // Warm up cache with patient statistics
        $initialStats = $cacheService->cacheStatistics('patient_stats', function() {
            return [
                'total_count' => Pasien::count(),
                'male_count' => Pasien::where('jenis_kelamin', 'L')->count(),
                'female_count' => Pasien::where('jenis_kelamin', 'P')->count(),
            ];
        });
        
        $this->assertEquals(0, $initialStats['total_count']);
        
        // Perform bulk import
        $this->test_bulk_patient_import_csv();
        
        // Cache should be invalidated after bulk operation
        $cacheService->invalidateByTag('model');
        
        // Get fresh statistics
        $updatedStats = $cacheService->cacheStatistics('patient_stats', function() {
            return [
                'total_count' => Pasien::count(),
                'male_count' => Pasien::where('jenis_kelamin', 'L')->count(),
                'female_count' => Pasien::where('jenis_kelamin', 'P')->count(),
            ];
        });
        
        $this->assertEquals(5, $updatedStats['total_count']);
        $this->assertEquals(3, $updatedStats['male_count']); // John, Bob, Charlie
        $this->assertEquals(2, $updatedStats['female_count']); // Jane, Alice
        
        // Test bulk operation performance with cache
        $startTime = microtime(true);
        
        // Multiple cache hits should be very fast
        for ($i = 0; $i < 10; $i++) {
            $cachedStats = $cacheService->cacheStatistics('patient_stats', function() {
                return [
                    'total_count' => Pasien::count(),
                    'male_count' => Pasien::where('jenis_kelamin', 'L')->count(),
                    'female_count' => Pasien::where('jenis_kelamin', 'P')->count(),
                ];
            });
        }
        
        $endTime = microtime(true);
        $cacheExecutionTime = $endTime - $startTime;
        
        // Cache hits should be very fast
        $this->assertLessThan(0.1, $cacheExecutionTime, 'Cache performance is poor');
        
        return true;
    }

    private function processBulkPatientImport($bulkOperation)
    {
        $filePath = $bulkOperation->file_path;
        $csvContent = Storage::get($filePath);
        $lines = explode("\n", trim($csvContent));
        
        // Skip header
        $header = array_shift($lines);
        
        $patientData = [];
        foreach ($lines as $line) {
            if (empty($line)) continue;
            
            $data = str_getcsv($line);
            $patientData[] = [
                'no_rekam_medis' => $data[0],
                'nama' => $data[1],
                'tanggal_lahir' => $data[2],
                'jenis_kelamin' => $data[3],
                'alamat' => $data[4],
                'no_telepon' => $data[5],
                'email' => $data[6],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
        }
        
        // Bulk insert
        DB::beginTransaction();
        try {
            Pasien::insert($patientData);
            
            $bulkOperation->update([
                'status' => 'completed',
                'processed_records' => count($patientData)
            ]);
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            $bulkOperation->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}