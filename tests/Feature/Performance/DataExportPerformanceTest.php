<?php

namespace Tests\Feature\Performance;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Pasien;
use App\Models\Tindakan;
use App\Models\PendapatanHarian;
use App\Models\PengeluaranHarian;
use App\Models\JumlahPasienHarian;
use App\Models\JenisTindakan;
use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use App\Models\Shift;
use App\Services\ExportImportService;
use App\Services\BulkOperationService;
use Spatie\Permission\Models\Role;

class DataExportPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $petugas;
    protected ExportImportService $exportService;
    protected BulkOperationService $bulkService;
    protected array $baseData;

    protected function setUp(): void
        // Roles are already created by base TestCase
    {
        parent::setUp();
        
        // Create role and user
        $this->petugas = User::factory()->create(['name' => 'Export Test User']);
        $this->petugas->assignRole('petugas');
        
        $this->exportService = new ExportImportService();
        $this->bulkService = new BulkOperationService();
        
        // Setup storage for testing
        Storage::fake('local');
        
        // Create base data
        $this->createBaseData();
    }

    protected function createBaseData(): void
    {
        $this->baseData = [
            'jenis_tindakan' => JenisTindakan::factory()->create(['tarif' => 150000]),
            'pendapatan' => Pendapatan::factory()->create(['nama_pendapatan' => 'Konsultasi']),
            'pengeluaran' => Pengeluaran::factory()->create(['nama_pengeluaran' => 'Obat']),
            'shift' => Shift::factory()->create(['name' => 'Pagi', 'is_active' => true]),
        ];
    }

    public function test_small_dataset_export_performance()
    {
        // Test export performance with small dataset (< 100 records)
        $this->actingAs($this->petugas);
        
        // Create small dataset
        $patients = Pasien::factory()->count(50)->create(['input_by' => $this->petugas->id]);
        
        // Test CSV export
        $startTime = microtime(true);
        $csvResult = $this->exportService->exportData(
            Pasien::class, 
            ['input_by' => $this->petugas->id], 
            'csv'
        );
        $csvTime = microtime(true) - $startTime;
        
        $this->assertTrue($csvResult['success']);
        $this->assertLessThan(2.0, $csvTime);
        $this->assertFileExists($csvResult['file_path']);
        
        // Test Excel export
        $startTime = microtime(true);
        $excelResult = $this->exportService->exportData(
            Pasien::class, 
            ['input_by' => $this->petugas->id], 
            'xlsx'
        );
        $excelTime = microtime(true) - $startTime;
        
        $this->assertTrue($excelResult['success']);
        $this->assertLessThan(3.0, $excelTime);
        $this->assertFileExists($excelResult['file_path']);
        
        // Test PDF export
        $startTime = microtime(true);
        $pdfResult = $this->exportService->exportData(
            Pasien::class, 
            ['input_by' => $this->petugas->id], 
            'pdf'
        );
        $pdfTime = microtime(true) - $startTime;
        
        $this->assertTrue($pdfResult['success']);
        $this->assertLessThan(5.0, $pdfTime);
        $this->assertFileExists($pdfResult['file_path']);
    }

    public function test_medium_dataset_export_performance()
    {
        // Test export performance with medium dataset (100-1000 records)
        $this->actingAs($this->petugas);
        
        // Create medium dataset
        $patients = Pasien::factory()->count(500)->create(['input_by' => $this->petugas->id]);
        
        // Test CSV export (should be fastest)
        $startTime = microtime(true);
        $csvResult = $this->exportService->exportData(
            Pasien::class, 
            ['input_by' => $this->petugas->id], 
            'csv'
        );
        $csvTime = microtime(true) - $startTime;
        
        $this->assertTrue($csvResult['success']);
        $this->assertLessThan(5.0, $csvTime);
        
        // Verify file size is reasonable
        $fileSize = Storage::size($csvResult['file_path']);
        $this->assertGreaterThan(1024, $fileSize); // At least 1KB
        $this->assertLessThan(5 * 1024 * 1024, $fileSize); // Less than 5MB
        
        // Test Excel export
        $startTime = microtime(true);
        $excelResult = $this->exportService->exportData(
            Pasien::class, 
            ['input_by' => $this->petugas->id], 
            'xlsx'
        );
        $excelTime = microtime(true) - $startTime;
        
        $this->assertTrue($excelResult['success']);
        $this->assertLessThan(10.0, $excelTime);
        
        // Excel should be slower than CSV but still reasonable
        $this->assertGreaterThan($csvTime, $excelTime);
    }

    public function test_large_dataset_export_performance()
    {
        // Test export performance with large dataset (1000+ records)
        $this->actingAs($this->petugas);
        
        // Create large dataset in batches to avoid memory issues
        $batchSize = 200;
        $totalRecords = 2000;
        
        for ($i = 0; $i < $totalRecords; $i += $batchSize) {
            $remainingRecords = min($batchSize, $totalRecords - $i);
            Pasien::factory()->count($remainingRecords)->create(['input_by' => $this->petugas->id]);
        }
        
        // Test CSV export with chunking
        $startTime = microtime(true);
        $csvResult = $this->exportService->exportData(
            Pasien::class, 
            ['input_by' => $this->petugas->id], 
            'csv',
            ['chunk_size' => 500]
        );
        $csvTime = microtime(true) - $startTime;
        
        $this->assertTrue($csvResult['success']);
        $this->assertLessThan(15.0, $csvTime); // Should complete within 15 seconds
        
        // Verify file contains expected number of records
        $fileContent = Storage::get($csvResult['file_path']);
        $lineCount = substr_count($fileContent, "\n");
        $this->assertGreaterThan(1900, $lineCount); // Should have most records (accounting for headers)
    }

    public function test_memory_usage_during_export()
    {
        // Test memory usage during export operations
        $this->actingAs($this->petugas);
        
        $initialMemory = memory_get_usage(true);
        
        // Create dataset
        Pasien::factory()->count(1000)->create(['input_by' => $this->petugas->id]);
        
        $beforeExportMemory = memory_get_usage(true);
        
        // Perform export
        $result = $this->exportService->exportData(
            Pasien::class, 
            ['input_by' => $this->petugas->id], 
            'csv',
            ['chunk_size' => 100] // Use chunking to manage memory
        );
        
        $peakMemory = memory_get_peak_usage(true);
        $memoryIncrease = $peakMemory - $beforeExportMemory;
        
        $this->assertTrue($result['success']);
        
        // Memory usage should be reasonable (less than 50MB increase)
        $this->assertLessThan(50 * 1024 * 1024, $memoryIncrease);
    }

    public function test_concurrent_export_performance()
    {
        // Test performance with multiple concurrent export operations
        $users = [];
        for ($i = 1; $i <= 3; $i++) {
            $user = User::factory()->create(['name' => "Export User {$i}"]);
            $user->assignRole('petugas');
            $users[] = $user;
            
            // Create data for each user
            Pasien::factory()->count(200)->create(['input_by' => $user->id]);
        }
        
        $startTime = microtime(true);
        
        // Simulate concurrent exports
        $results = [];
        foreach ($users as $user) {
            $this->actingAs($user);
            $results[] = $this->exportService->exportData(
                Pasien::class, 
                ['input_by' => $user->id], 
                'csv'
            );
        }
        
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        
        // Should handle concurrent exports efficiently
        $this->assertLessThan(10.0, $totalTime);
        
        // All exports should succeed
        foreach ($results as $result) {
            $this->assertTrue($result['success']);
            $this->assertFileExists($result['file_path']);
        }
    }

    public function test_complex_data_export_performance()
    {
        // Test export performance with complex data relationships
        $this->actingAs($this->petugas);
        
        // Create patients
        $patients = Pasien::factory()->count(300)->create(['input_by' => $this->petugas->id]);
        
        // Create related tindakan
        foreach ($patients as $patient) {
            Tindakan::factory()->count(rand(1, 3))->create([
                'input_by' => $this->petugas->id,
                'pasien_id' => $patient->id,
                'jenis_tindakan_id' => $this->baseData['jenis_tindakan']->id,
                'shift_id' => $this->baseData['shift']->id,
            ]);
        }
        
        // Test export with relationships
        $startTime = microtime(true);
        $result = $this->exportService->exportDataWithRelations(
            Pasien::class,
            ['input_by' => $this->petugas->id],
            ['tindakan', 'tindakan.jenisTindakan'],
            'csv'
        );
        $exportTime = microtime(true) - $startTime;
        
        $this->assertTrue($result['success']);
        $this->assertLessThan(8.0, $exportTime);
        
        // Verify exported data includes relationships
        $fileContent = Storage::get($result['file_path']);
        $this->assertStringContains('tindakan_count', $fileContent); // Should include relation data
    }

    public function test_filtered_export_performance()
    {
        // Test export performance with complex filters
        $this->actingAs($this->petugas);
        
        // Create diverse dataset
        $patients = [];
        for ($i = 1; $i <= 500; $i++) {
            $patients[] = Pasien::factory()->create([
                'input_by' => $this->petugas->id,
                'jenis_kelamin' => $i % 2 === 0 ? 'P' : 'L',
                'created_at' => now()->subDays(rand(0, 365)),
            ]);
        }
        
        // Test export with date range filter
        $startTime = microtime(true);
        $dateFilterResult = $this->exportService->exportData(
            Pasien::class,
            [
                'input_by' => $this->petugas->id,
                'created_at' => ['>=', now()->subDays(30)],
            ],
            'csv'
        );
        $dateFilterTime = microtime(true) - $startTime;
        
        $this->assertTrue($dateFilterResult['success']);
        $this->assertLessThan(3.0, $dateFilterTime);
        
        // Test export with multiple filters
        $startTime = microtime(true);
        $multiFilterResult = $this->exportService->exportData(
            Pasien::class,
            [
                'input_by' => $this->petugas->id,
                'jenis_kelamin' => 'P',
                'created_at' => ['>=', now()->subDays(90)],
            ],
            'csv'
        );
        $multiFilterTime = microtime(true) - $startTime;
        
        $this->assertTrue($multiFilterResult['success']);
        $this->assertLessThan(3.0, $multiFilterTime);
    }

    public function test_different_format_export_performance()
    {
        // Test performance comparison between different export formats
        $this->actingAs($this->petugas);
        
        // Create test dataset
        Pasien::factory()->count(400)->create(['input_by' => $this->petugas->id]);
        
        $formats = ['csv', 'xlsx', 'pdf', 'json'];
        $formatTimes = [];
        
        foreach ($formats as $format) {
            $startTime = microtime(true);
            
            $result = $this->exportService->exportData(
                Pasien::class,
                ['input_by' => $this->petugas->id],
                $format
            );
            
            $endTime = microtime(true);
            $formatTimes[$format] = $endTime - $startTime;
            
            $this->assertTrue($result['success'], "Export failed for format: {$format}");
            $this->assertFileExists($result['file_path'], "File not created for format: {$format}");
        }
        
        // CSV should be fastest
        $this->assertLessThan(3.0, $formatTimes['csv']);
        
        // JSON should be fast
        $this->assertLessThan(4.0, $formatTimes['json']);
        
        // Excel should be moderate
        $this->assertLessThan(8.0, $formatTimes['xlsx']);
        
        // PDF should be slowest but still reasonable
        $this->assertLessThan(15.0, $formatTimes['pdf']);
        
        // Verify order: CSV <= JSON < Excel < PDF
        $this->assertLessThanOrEqual($formatTimes['json'], $formatTimes['csv']);
        $this->assertLessThan($formatTimes['xlsx'], $formatTimes['json']);
        $this->assertLessThan($formatTimes['pdf'], $formatTimes['xlsx']);
    }

    public function test_bulk_import_performance()
    {
        // Test bulk import performance
        $this->actingAs($this->petugas);
        
        // Create import data
        $importData = [];
        for ($i = 1; $i <= 1000; $i++) {
            $importData[] = [
                'nama' => "Import Patient {$i}",
                'no_rekam_medis' => "RM-IMP-" . str_pad($i, 4, '0', STR_PAD_LEFT),
                'tanggal_lahir' => '1990-01-01',
                'jenis_kelamin' => $i % 2 === 0 ? 'P' : 'L',
                'input_by' => $this->petugas->id,
            ];
        }
        
        // Test bulk import performance
        $startTime = microtime(true);
        $result = $this->bulkService->bulkCreate(
            Pasien::class, 
            $importData, 
            ['batch_size' => 100]
        );
        $importTime = microtime(true) - $startTime;
        
        $this->assertTrue($result['success']);
        $this->assertEquals(1000, $result['created']);
        $this->assertEquals(0, $result['failed']);
        $this->assertLessThan(20.0, $importTime); // Should complete within 20 seconds
        
        // Verify data was imported correctly
        $importedCount = Pasien::where('input_by', $this->petugas->id)->count();
        $this->assertEquals(1000, $importedCount);
    }

    public function test_export_file_cleanup_performance()
    {
        // Test cleanup of old export files
        $this->actingAs($this->petugas);
        
        // Create test data
        Pasien::factory()->count(100)->create(['input_by' => $this->petugas->id]);
        
        // Create multiple export files
        $exportFiles = [];
        for ($i = 0; $i < 5; $i++) {
            $result = $this->exportService->exportData(
                Pasien::class,
                ['input_by' => $this->petugas->id],
                'csv'
            );
            $exportFiles[] = $result['file_path'];
        }
        
        // Verify files exist
        foreach ($exportFiles as $file) {
            $this->assertFileExists($file);
        }
        
        // Test cleanup performance
        $startTime = microtime(true);
        $cleanupResult = $this->exportService->cleanupOldExports(24); // Cleanup files older than 24 hours
        $cleanupTime = microtime(true) - $startTime;
        
        $this->assertTrue($cleanupResult['success']);
        $this->assertLessThan(2.0, $cleanupTime);
    }

    public function test_streaming_export_performance()
    {
        // Test streaming export for large datasets
        $this->actingAs($this->petugas);
        
        // Create large dataset
        Pasien::factory()->count(5000)->create(['input_by' => $this->petugas->id]);
        
        // Test streaming CSV export
        $startTime = microtime(true);
        $result = $this->exportService->streamingExport(
            Pasien::class,
            ['input_by' => $this->petugas->id],
            'csv',
            ['chunk_size' => 200]
        );
        $streamingTime = microtime(true) - $startTime;
        
        $this->assertTrue($result['success']);
        $this->assertLessThan(30.0, $streamingTime); // Should complete within 30 seconds
        
        // Verify memory usage was controlled
        $peakMemory = memory_get_peak_usage(true);
        $this->assertLessThan(100 * 1024 * 1024, $peakMemory); // Less than 100MB peak memory
    }

    public function test_export_with_validation_performance()
    {
        // Test export performance with data validation
        $this->actingAs($this->petugas);
        
        // Create mixed quality data
        $validPatients = Pasien::factory()->count(200)->create(['input_by' => $this->petugas->id]);
        
        // Create some patients with potential issues
        $problematicPatients = Pasien::factory()->count(50)->create([
            'input_by' => $this->petugas->id,
            'email' => null, // Missing email
        ]);
        
        // Test export with validation
        $startTime = microtime(true);
        $result = $this->exportService->exportDataWithValidation(
            Pasien::class,
            ['input_by' => $this->petugas->id],
            'csv',
            [
                'validate_email' => true,
                'validate_phone' => true,
                'skip_invalid' => true,
            ]
        );
        $validationTime = microtime(true) - $startTime;
        
        $this->assertTrue($result['success']);
        $this->assertLessThan(8.0, $validationTime);
        
        // Should report validation issues
        $this->assertArrayHasKey('validation_errors', $result);
        $this->assertGreaterThan(0, count($result['validation_errors']));
    }

    public function test_scheduled_export_performance()
    {
        // Test performance of scheduled/automated exports
        $this->actingAs($this->petugas);
        
        // Create data spanning multiple months
        for ($month = 1; $month <= 6; $month++) {
            Pasien::factory()->count(100)->create([
                'input_by' => $this->petugas->id,
                'created_at' => now()->subMonths($month),
            ]);
        }
        
        // Test monthly export
        $startTime = microtime(true);
        $monthlyResult = $this->exportService->exportMonthlyData(
            $this->petugas->id,
            now()->subMonth(),
            'csv'
        );
        $monthlyTime = microtime(true) - $startTime;
        
        $this->assertTrue($monthlyResult['success']);
        $this->assertLessThan(5.0, $monthlyTime);
        
        // Test quarterly export
        $startTime = microtime(true);
        $quarterlyResult = $this->exportService->exportQuarterlyData(
            $this->petugas->id,
            now()->startOfQuarter(),
            'xlsx'
        );
        $quarterlyTime = microtime(true) - $startTime;
        
        $this->assertTrue($quarterlyResult['success']);
        $this->assertLessThan(10.0, $quarterlyTime);
    }

    protected function tearDown(): void
    {
        // Cleanup test files
        Storage::deleteDirectory('exports');
        parent::tearDown();
    }
}