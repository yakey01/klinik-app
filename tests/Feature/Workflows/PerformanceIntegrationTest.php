<?php

namespace Tests\Feature\Workflows;

use Tests\TestCase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Pasien;
use App\Models\Tindakan;
use App\Models\PendapatanHarian;
use App\Models\PengeluaranHarian;
use App\Models\JenisTindakan;
use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use App\Models\Shift;
use App\Services\PetugasStatsService;
use App\Services\BulkOperationService;
use App\Services\ValidationWorkflowService;
use App\Services\NotificationService;
use App\Services\TelegramService;
use App\Filament\Petugas\Widgets\PetugasStatsWidget;
use App\Filament\Petugas\Resources\PasienResource;
use Livewire\Livewire;
use App\Filament\Petugas\Resources\PasienResource\Pages\ListPasiens;
use Spatie\Permission\Models\Role;

class PerformanceIntegrationTest extends TestCase
{

    protected User $petugas;
    protected PetugasStatsService $statsService;
    protected BulkOperationService $bulkService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Roles are already created by base TestCase via RoleSetupTrait
        $this->petugas = User::factory()->create(['name' => 'Performance Test User']);
        $this->petugas->assignRole('petugas');
        
        // Initialize services
        $this->statsService = new PetugasStatsService();
        $this->bulkService = new BulkOperationService();
        
        // Clear cache
        Cache::flush();
        
        // Set up database for performance testing
        $this->setUpPerformanceData();
    }

    protected function setUpPerformanceData(): void
        // Roles are already created by base TestCase
    {
        // Create base data for performance testing
        $jenisTindakan = JenisTindakan::factory()->create(['tarif' => 150000]);
        $pendapatan = Pendapatan::factory()->create(['nama_pendapatan' => 'Konsultasi']);
        $pengeluaran = Pengeluaran::factory()->create(['nama_pengeluaran' => 'Obat']);
        $shift = Shift::factory()->create(['name' => 'Pagi', 'is_active' => true]);
        
        // Store IDs for use in tests
        $this->jenisTindakanId = $jenisTindakan->id;
        $this->pendapatanId = $pendapatan->id;
        $this->pengeluaranId = $pengeluaran->id;
        $this->shiftId = $shift->id;
    }

    public function test_stats_service_performance_with_large_dataset()
    {
        // Arrange - Create large dataset
        $this->actingAs($this->petugas);
        
        // Create 1000 patients
        Pasien::factory()->count(1000)->create([
            'input_by' => $this->petugas->id,
            'created_at' => now()->subDays(rand(0, 30)),
        ]);

        // Create 500 tindakan
        $patients = Pasien::where('input_by', $this->petugas->id)->take(500)->get();
        foreach ($patients as $patient) {
            Tindakan::factory()->create([
                'input_by' => $this->petugas->id,
                'jenis_tindakan_id' => $this->jenisTindakanId,
                'pasien_id' => $patient->id,
                'shift_id' => $this->shiftId,
                'tarif' => rand(100000, 500000),
                'status_validasi' => 'approved',
                'created_at' => now()->subDays(rand(0, 30)),
            ]);
        }

        // Create 300 pendapatan records
        for ($i = 0; $i < 300; $i++) {
            PendapatanHarian::factory()->create([
                'user_id' => $this->petugas->id,
                'pendapatan_id' => $this->pendapatanId,
                'nominal' => rand(50000, 300000),
                'tanggal_input' => now()->subDays(rand(0, 30))->format('Y-m-d'),
                'status_validasi' => 'approved',
            ]);
        }

        // Act - Measure performance of dashboard stats
        $startTime = microtime(true);
        $stats = $this->statsService->getDashboardStats($this->petugas->id);
        $endTime = microtime(true);
        
        $executionTime = $endTime - $startTime;

        // Assert - Stats should be calculated efficiently
        $this->assertLessThan(2.0, $executionTime); // Should complete in under 2 seconds
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('daily', $stats);
        $this->assertArrayHasKey('weekly', $stats);
        $this->assertArrayHasKey('monthly', $stats);
        
        // Verify data accuracy
        $this->assertGreaterThan(0, $stats['daily']['today']['pasien_count'] + 
                                      $stats['daily']['yesterday']['pasien_count']);
    }

    public function test_widget_performance_with_concurrent_access()
    {
        // Arrange - Create data and multiple users
        $this->actingAs($this->petugas);
        
        // Create moderate dataset
        Pasien::factory()->count(100)->create(['input_by' => $this->petugas->id]);
        
        $otherUsers = User::factory()->count(10)->create();
        foreach ($otherUsers as $user) {
            $user->assignRole('petugas');
            Pasien::factory()->count(50)->create(['input_by' => $user->id]);
        }

        // Act - Simulate concurrent widget loading
        $startTime = microtime(true);
        
        $widget = new PetugasStatsWidget();
        $widgetData = $widget->getViewData();
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Assert - Widget should load quickly even with concurrent data
        $this->assertLessThan(1.5, $executionTime); // Should complete in under 1.5 seconds
        $this->assertIsArray($widgetData);
        $this->assertArrayHasKey('stats', $widgetData);
        $this->assertArrayHasKey('trends', $widgetData);
    }

    public function test_bulk_operation_performance()
    {
        // Arrange
        $this->actingAs($this->petugas);
        
        // Create dataset for bulk operations
        $bulkData = [];
        for ($i = 1; $i <= 500; $i++) {
            $bulkData[] = [
                'nama' => "Performance Patient {$i}",
                'no_rekam_medis' => "RM-PERF-" . str_pad($i, 4, '0', STR_PAD_LEFT),
                'tanggal_lahir' => '1990-01-01',
                'jenis_kelamin' => $i % 2 === 0 ? 'P' : 'L',
                'input_by' => $this->petugas->id,
                'alamat' => "Test Address {$i}",
            ];
        }

        // Act - Measure bulk create performance
        $startTime = microtime(true);
        $result = $this->bulkService->bulkCreate(Pasien::class, $bulkData, [
            'batch_size' => 50
        ]);
        $endTime = microtime(true);
        
        $executionTime = $endTime - $startTime;

        // Assert - Bulk operation should be efficient
        $this->assertLessThan(10.0, $executionTime); // Should complete in under 10 seconds
        $this->assertTrue($result['success']);
        $this->assertEquals(500, $result['created']);
        $this->assertEquals(0, $result['failed']);
        
        // Verify database has correct count
        $this->assertEquals(500, Pasien::where('input_by', $this->petugas->id)->count());
    }

    public function test_database_query_optimization()
    {
        // Arrange
        $this->actingAs($this->petugas);
        
        // Create relationships data
        $patients = Pasien::factory()->count(200)->create(['input_by' => $this->petugas->id]);
        
        foreach ($patients as $patient) {
            Tindakan::factory()->count(rand(1, 5))->create([
                'input_by' => $this->petugas->id,
                'jenis_tindakan_id' => $this->jenisTindakanId,
                'pasien_id' => $patient->id,
                'shift_id' => $this->shiftId,
            ]);
        }

        // Act - Test N+1 query prevention
        $startTime = microtime(true);
        
        // This should use optimized queries with proper eager loading
        $query = PasienResource::getEloquentQuery();
        $patientsWithRelations = $query->with(['tindakan', 'inputBy'])->get();
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Assert - Query should be optimized
        $this->assertLessThan(1.0, $executionTime); // Should complete in under 1 second
        $this->assertCount(200, $patientsWithRelations);
        
        // Verify relationships are loaded (no additional queries)
        foreach ($patientsWithRelations->take(5) as $patient) {
            $this->assertTrue($patient->relationLoaded('tindakan'));
            $this->assertTrue($patient->relationLoaded('inputBy'));
        }
    }

    public function test_cache_effectiveness()
    {
        // Arrange
        $this->actingAs($this->petugas);
        
        // Create data
        Pasien::factory()->count(100)->create(['input_by' => $this->petugas->id]);
        
        // Act 1 - First call (should hit database)
        $startTime1 = microtime(true);
        $stats1 = $this->statsService->getDashboardStats($this->petugas->id);
        $endTime1 = microtime(true);
        $firstCallTime = $endTime1 - $startTime1;
        
        // Act 2 - Second call (should hit cache)
        $startTime2 = microtime(true);
        $stats2 = $this->statsService->getDashboardStats($this->petugas->id);
        $endTime2 = microtime(true);
        $secondCallTime = $endTime2 - $startTime2;

        // Assert - Cache should provide significant speedup
        $this->assertLessThan($firstCallTime * 0.5, $secondCallTime); // Second call should be at least 50% faster
        $this->assertEquals($stats1, $stats2); // Results should be identical
        $this->assertLessThan(0.1, $secondCallTime); // Cached call should be very fast
    }

    public function test_pagination_performance()
    {
        // Arrange
        $this->actingAs($this->petugas);
        
        // Create large dataset
        Pasien::factory()->count(1000)->create([
            'input_by' => $this->petugas->id,
            'created_at' => now()->subDays(rand(0, 365)),
        ]);

        // Act - Test paginated listing performance
        $startTime = microtime(true);
        
        $component = Livewire::test(ListPasiens::class);
        $tableData = $component->get('table')->getRecords();
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Assert - Pagination should handle large datasets efficiently
        $this->assertLessThan(2.0, $executionTime); // Should complete in under 2 seconds
        $this->assertLessThanOrEqual(25, $tableData->count()); // Should respect pagination limit
        $component->assertSuccessful();
    }

    public function test_search_performance()
    {
        // Arrange
        $this->actingAs($this->petugas);
        
        // Create searchable data
        for ($i = 1; $i <= 500; $i++) {
            Pasien::factory()->create([
                'input_by' => $this->petugas->id,
                'nama' => "Patient Name {$i}",
                'no_rekam_medis' => "RM-2024-" . str_pad($i, 4, '0', STR_PAD_LEFT),
            ]);
        }

        // Act - Test search performance
        $startTime = microtime(true);
        
        $component = Livewire::test(ListPasiens::class)
            ->set('tableSearch', 'Patient Name 250');
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Assert - Search should be fast
        $this->assertLessThan(1.0, $executionTime); // Should complete in under 1 second
        $component->assertSuccessful();
        $component->assertSee('Patient Name 250');
    }

    public function test_validation_workflow_performance()
    {
        // Arrange
        $this->actingAs($this->petugas);
        
        $telegramService = new TelegramService();
        $notificationService = new NotificationService($telegramService);
        $validationService = new ValidationWorkflowService($telegramService, $notificationService);

        // Create multiple items for validation
        $tindakans = [];
        $patients = Pasien::factory()->count(50)->create(['input_by' => $this->petugas->id]);
        
        foreach ($patients as $patient) {
            $tindakans[] = Tindakan::factory()->create([
                'input_by' => $this->petugas->id,
                'jenis_tindakan_id' => $this->jenisTindakanId,
                'pasien_id' => $patient->id,
                'shift_id' => $this->shiftId,
                'tarif' => 120000,
                'status_validasi' => 'pending',
            ]);
        }

        // Act - Test batch validation performance
        $startTime = microtime(true);
        
        foreach ($tindakans as $tindakan) {
            $validationService->submitForValidation($tindakan);
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Assert - Validation should handle multiple items efficiently
        $this->assertLessThan(5.0, $executionTime); // Should complete in under 5 seconds
        
        // Verify all items were processed
        foreach ($tindakans as $tindakan) {
            $tindakan->refresh();
            $this->assertNotNull($tindakan->submitted_at);
        }
    }

    public function test_memory_usage_optimization()
    {
        // Arrange
        $this->actingAs($this->petugas);
        
        $initialMemory = memory_get_usage(true);

        // Act - Process large dataset
        $bulkData = [];
        for ($i = 1; $i <= 100; $i++) {
            $bulkData[] = [
                'nama' => "Memory Test Patient {$i}",
                'no_rekam_medis' => "RM-MEM-" . str_pad($i, 3, '0', STR_PAD_LEFT),
                'tanggal_lahir' => '1990-01-01',
                'jenis_kelamin' => 'L',
                'input_by' => $this->petugas->id,
                'alamat' => str_repeat('Test address data ', 10), // Make records larger
            ];
        }

        $result = $this->bulkService->bulkCreate(Pasien::class, $bulkData, [
            'batch_size' => 25 // Process in smaller batches
        ]);

        $peakMemory = memory_get_peak_usage(true);
        $memoryIncrease = $peakMemory - $initialMemory;

        // Assert - Memory usage should be reasonable
        $this->assertTrue($result['success']);
        $this->assertLessThan(50 * 1024 * 1024, $memoryIncrease); // Should use less than 50MB additional memory
    }

    public function test_concurrent_user_performance()
    {
        // Simulate multiple users accessing the system
        
        // Arrange - Create multiple users with data
        $users = [];
        for ($i = 1; $i <= 5; $i++) {
            $user = User::factory()->create(['name' => "Concurrent User {$i}"]);
            $user->assignRole('petugas');
            $users[] = $user;
            
            // Create data for each user
            Pasien::factory()->count(20)->create(['input_by' => $user->id]);
        }

        // Act - Simulate concurrent access
        $startTime = microtime(true);
        
        $results = [];
        foreach ($users as $user) {
            $this->actingAs($user);
            $results[] = $this->statsService->getDashboardStats($user->id);
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Assert - System should handle concurrent users efficiently
        $this->assertLessThan(3.0, $executionTime); // Should complete in under 3 seconds
        $this->assertCount(5, $results);
        
        // Verify each user got their own data
        foreach ($results as $index => $result) {
            $this->assertIsArray($result);
            $this->assertArrayHasKey('daily', $result);
            // Each user should have their own patient count
            $this->assertGreaterThanOrEqual(0, $result['daily']['today']['pasien_count']);
        }
    }

    public function test_database_connection_pooling_efficiency()
    {
        // Test that database connections are managed efficiently
        
        // Arrange
        $this->actingAs($this->petugas);
        
        $startTime = microtime(true);
        $initialConnections = DB::getConnections();

        // Act - Perform multiple database operations
        for ($i = 1; $i <= 20; $i++) {
            // Create patient
            $patient = Pasien::factory()->create(['input_by' => $this->petugas->id]);
            
            // Create tindakan
            Tindakan::factory()->create([
                'input_by' => $this->petugas->id,
                'pasien_id' => $patient->id,
                'jenis_tindakan_id' => $this->jenisTindakanId,
                'shift_id' => $this->shiftId,
            ]);
            
            // Get stats (involves multiple queries)
            $this->statsService->getDashboardStats($this->petugas->id);
        }

        $endTime = microtime(true);
        $finalConnections = DB::getConnections();
        $executionTime = $endTime - $startTime;

        // Assert - Operations should be efficient and not create excessive connections
        $this->assertLessThan(5.0, $executionTime); // Should complete in under 5 seconds
        $this->assertLessThanOrEqual(count($initialConnections) + 2, count($finalConnections)); // Should not create excessive connections
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}