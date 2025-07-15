<?php

namespace Tests\Feature\Performance;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use App\Models\User;
use App\Models\Pasien;
use App\Models\Tindakan;
use App\Models\PendapatanHarian;
use App\Models\PengeluaranHarian;
use App\Models\JenisTindakan;
use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use App\Models\Shift;
use App\Filament\Petugas\Resources\PasienResource\Pages\ListPasiens;
use App\Filament\Petugas\Resources\PasienResource\Pages\CreatePasien;
use App\Filament\Petugas\Resources\PasienResource\Pages\EditPasien;
use App\Filament\Petugas\Resources\TindakanResource\Pages\ListTindakans;
use App\Filament\Petugas\Resources\TindakanResource\Pages\CreateTindakan;
use App\Filament\Petugas\Resources\PendapatanHarianResource\Pages\ListPendapatanHarians;
use App\Filament\Petugas\Resources\PendapatanHarianResource\Pages\CreatePendapatanHarian;
use App\Filament\Petugas\Resources\PengeluaranHarianResource\Pages\ListPengeluaranHarians;
use App\Filament\Petugas\Resources\JumlahPasienHarianResource\Pages\ListJumlahPasienHarians;
use Spatie\Permission\Models\Role;

class ComponentPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $petugas;
    protected array $baseData;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create role and user
        Role::create(['name' => 'petugas']);
        $this->petugas = User::factory()->create(['name' => 'Component Test User']);
        $this->petugas->assignRole('petugas');
        
        // Create base data
        $this->createBaseData();
        
        // Clear cache
        Cache::flush();
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

    public function test_list_component_performance_with_small_dataset()
    {
        // Test list component performance with small dataset
        $this->actingAs($this->petugas);
        
        // Create small dataset
        Pasien::factory()->count(25)->create(['input_by' => $this->petugas->id]);
        
        // Test initial load
        $startTime = microtime(true);
        $component = Livewire::test(ListPasiens::class);
        $loadTime = microtime(true) - $startTime;
        
        $this->assertLessThan(2.0, $loadTime);
        $component->assertSuccessful();
        
        // Test table data retrieval
        $tableData = $component->get('table')->getRecords();
        $this->assertCount(25, $tableData);
    }

    public function test_list_component_performance_with_large_dataset()
    {
        // Test list component performance with large dataset
        $this->actingAs($this->petugas);
        
        // Create large dataset
        Pasien::factory()->count(2000)->create(['input_by' => $this->petugas->id]);
        
        // Test initial load with pagination
        $startTime = microtime(true);
        $component = Livewire::test(ListPasiens::class);
        $loadTime = microtime(true) - $startTime;
        
        $this->assertLessThan(3.0, $loadTime);
        $component->assertSuccessful();
        
        // Should load only paginated data
        $tableData = $component->get('table')->getRecords();
        $this->assertLessThanOrEqual(25, $tableData->count()); // Default pagination
    }

    public function test_create_component_performance()
    {
        // Test create component performance
        $this->actingAs($this->petugas);
        
        // Test initial form load
        $startTime = microtime(true);
        $component = Livewire::test(CreatePasien::class);
        $formLoadTime = microtime(true) - $startTime;
        
        $this->assertLessThan(1.5, $formLoadTime);
        $component->assertSuccessful();
        
        // Test form submission
        $pasienData = [
            'no_rekam_medis' => 'RM-PERF-001',
            'nama' => 'Performance Test Patient',
            'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => 'L',
        ];
        
        $startTime = microtime(true);
        $component->fillForm($pasienData)->call('create');
        $submitTime = microtime(true) - $startTime;
        
        $this->assertLessThan(2.0, $submitTime);
        $component->assertSuccessful();
    }

    public function test_edit_component_performance()
    {
        // Test edit component performance
        $this->actingAs($this->petugas);
        
        $patient = Pasien::factory()->create(['input_by' => $this->petugas->id]);
        
        // Test edit form load
        $startTime = microtime(true);
        $component = Livewire::test(EditPasien::class, ['record' => $patient->getRouteKey()]);
        $editLoadTime = microtime(true) - $startTime;
        
        $this->assertLessThan(1.5, $editLoadTime);
        $component->assertSuccessful();
        
        // Test form update
        $startTime = microtime(true);
        $component->fillForm(['nama' => 'Updated Name'])->call('save');
        $updateTime = microtime(true) - $startTime;
        
        $this->assertLessThan(2.0, $updateTime);
        $component->assertSuccessful();
    }

    public function test_search_component_performance()
    {
        // Test search performance across different scenarios
        $this->actingAs($this->petugas);
        
        // Create searchable data
        for ($i = 1; $i <= 500; $i++) {
            Pasien::factory()->create([
                'input_by' => $this->petugas->id,
                'nama' => "Test Patient {$i}",
                'no_rekam_medis' => "RM-2024-" . str_pad($i, 4, '0', STR_PAD_LEFT),
            ]);
        }
        
        $component = Livewire::test(ListPasiens::class);
        
        // Test exact search
        $startTime = microtime(true);
        $component->set('tableSearch', 'RM-2024-0250');
        $exactSearchTime = microtime(true) - $startTime;
        
        $this->assertLessThan(1.0, $exactSearchTime);
        
        // Test partial name search
        $startTime = microtime(true);
        $component->set('tableSearch', 'Test Patient 2');
        $partialSearchTime = microtime(true) - $startTime;
        
        $this->assertLessThan(1.5, $partialSearchTime);
        
        // Test wildcard search
        $startTime = microtime(true);
        $component->set('tableSearch', 'Patient');
        $wildcardSearchTime = microtime(true) - $startTime;
        
        $this->assertLessThan(2.0, $wildcardSearchTime);
        
        // Test clearing search
        $startTime = microtime(true);
        $component->set('tableSearch', '');
        $clearSearchTime = microtime(true) - $startTime;
        
        $this->assertLessThan(1.0, $clearSearchTime);
    }

    public function test_filter_component_performance()
    {
        // Test filter performance
        $this->actingAs($this->petugas);
        
        // Create filterable data
        for ($i = 1; $i <= 300; $i++) {
            Pasien::factory()->create([
                'input_by' => $this->petugas->id,
                'jenis_kelamin' => $i % 2 === 0 ? 'P' : 'L',
                'created_at' => now()->subDays(rand(0, 365)),
            ]);
        }
        
        $component = Livewire::test(ListPasiens::class);
        
        // Test gender filter
        $startTime = microtime(true);
        $component->set('tableFilters.jenis_kelamin.value', 'P');
        $genderFilterTime = microtime(true) - $startTime;
        
        $this->assertLessThan(1.5, $genderFilterTime);
        
        // Test date range filter
        $startTime = microtime(true);
        $component->set('tableFilters.created_at.from', now()->subDays(30)->format('Y-m-d'));
        $component->set('tableFilters.created_at.until', now()->format('Y-m-d'));
        $dateFilterTime = microtime(true) - $startTime;
        
        $this->assertLessThan(2.0, $dateFilterTime);
        
        // Test clearing filters
        $startTime = microtime(true);
        $component->call('resetTableFilters');
        $clearFiltersTime = microtime(true) - $startTime;
        
        $this->assertLessThan(1.0, $clearFiltersTime);
    }

    public function test_sorting_component_performance()
    {
        // Test sorting performance
        $this->actingAs($this->petugas);
        
        // Create sortable data
        Pasien::factory()->count(400)->create([
            'input_by' => $this->petugas->id,
            'created_at' => now()->subDays(rand(0, 100)),
        ]);
        
        $component = Livewire::test(ListPasiens::class);
        
        $sortColumns = ['nama', 'no_rekam_medis', 'created_at', 'tanggal_lahir'];
        
        foreach ($sortColumns as $column) {
            // Test ascending sort
            $startTime = microtime(true);
            $component->set('tableSortColumn', $column);
            $component->set('tableSortDirection', 'asc');
            $ascSortTime = microtime(true) - $startTime;
            
            $this->assertLessThan(1.5, $ascSortTime);
            
            // Test descending sort
            $startTime = microtime(true);
            $component->set('tableSortDirection', 'desc');
            $descSortTime = microtime(true) - $startTime;
            
            $this->assertLessThan(1.0, $descSortTime);
        }
    }

    public function test_bulk_selection_performance()
    {
        // Test bulk selection performance
        $this->actingAs($this->petugas);
        
        // Create data for bulk operations
        $patients = Pasien::factory()->count(100)->create(['input_by' => $this->petugas->id]);
        
        $component = Livewire::test(ListPasiens::class);
        
        // Test selecting all records on page
        $startTime = microtime(true);
        $component->call('selectAllTableRecords');
        $selectAllTime = microtime(true) - $startTime;
        
        $this->assertLessThan(1.0, $selectAllTime);
        
        // Test individual selection
        $patientIds = $patients->take(10)->pluck('id')->toArray();
        
        $startTime = microtime(true);
        $component->set('selectedTableRecords', $patientIds);
        $individualSelectTime = microtime(true) - $startTime;
        
        $this->assertLessThan(0.5, $individualSelectTime);
        
        // Test deselecting all
        $startTime = microtime(true);
        $component->call('deselectAllTableRecords');
        $deselectAllTime = microtime(true) - $startTime;
        
        $this->assertLessThan(0.5, $deselectAllTime);
    }

    public function test_form_component_performance()
    {
        // Test complex form component performance
        $this->actingAs($this->petugas);
        
        $patient = Pasien::factory()->create(['input_by' => $this->petugas->id]);
        
        // Test tindakan form with relationships
        $startTime = microtime(true);
        $component = Livewire::test(CreateTindakan::class);
        $formLoadTime = microtime(true) - $startTime;
        
        $this->assertLessThan(2.0, $formLoadTime);
        $component->assertSuccessful();
        
        // Test form field updates
        $tindakanData = [
            'jenis_tindakan_id' => $this->baseData['jenis_tindakan']->id,
            'pasien_id' => $patient->id,
            'tanggal_tindakan' => now()->format('Y-m-d H:i'),
            'shift_id' => $this->baseData['shift']->id,
            'tarif' => 150000,
        ];
        
        // Test individual field updates
        foreach ($tindakanData as $field => $value) {
            $startTime = microtime(true);
            $component->set("data.{$field}", $value);
            $fieldUpdateTime = microtime(true) - $startTime;
            
            $this->assertLessThan(0.5, $fieldUpdateTime);
        }
        
        // Test form submission
        $startTime = microtime(true);
        $component->call('create');
        $submitTime = microtime(true) - $startTime;
        
        $this->assertLessThan(3.0, $submitTime);
        $component->assertSuccessful();
    }

    public function test_component_memory_usage()
    {
        // Test component memory usage
        $this->actingAs($this->petugas);
        
        $initialMemory = memory_get_usage(true);
        
        // Create moderate dataset
        Pasien::factory()->count(200)->create(['input_by' => $this->petugas->id]);
        
        // Load multiple components
        $listComponent = Livewire::test(ListPasiens::class);
        $createComponent = Livewire::test(CreatePasien::class);
        
        $peakMemory = memory_get_peak_usage(true);
        $memoryIncrease = $peakMemory - $initialMemory;
        
        // Should not use excessive memory
        $this->assertLessThan(50 * 1024 * 1024, $memoryIncrease); // Less than 50MB
    }

    public function test_component_caching_performance()
    {
        // Test component caching performance
        $this->actingAs($this->petugas);
        
        // Create data
        Pasien::factory()->count(150)->create(['input_by' => $this->petugas->id]);
        
        // First load (cold cache)
        Cache::flush();
        $startTime = microtime(true);
        $component1 = Livewire::test(ListPasiens::class);
        $coldLoadTime = microtime(true) - $startTime;
        
        // Second load (warm cache)
        $startTime = microtime(true);
        $component2 = Livewire::test(ListPasiens::class);
        $warmLoadTime = microtime(true) - $startTime;
        
        // Warm load should be faster
        $this->assertLessThan($coldLoadTime, $warmLoadTime);
        $this->assertLessThan(1.0, $warmLoadTime);
    }

    public function test_multiple_component_interaction_performance()
    {
        // Test performance when multiple components interact
        $this->actingAs($this->petugas);
        
        // Create data for different resources
        Pasien::factory()->count(100)->create(['input_by' => $this->petugas->id]);
        
        PendapatanHarian::factory()->count(80)->create([
            'user_id' => $this->petugas->id,
            'pendapatan_id' => $this->baseData['pendapatan']->id,
        ]);
        
        PengeluaranHarian::factory()->count(60)->create([
            'user_id' => $this->petugas->id,
            'pengeluaran_id' => $this->baseData['pengeluaran']->id,
        ]);
        
        // Load multiple components simultaneously
        $startTime = microtime(true);
        
        $pasienComponent = Livewire::test(ListPasiens::class);
        $pendapatanComponent = Livewire::test(ListPendapatanHarians::class);
        $pengeluaranComponent = Livewire::test(ListPengeluaranHarians::class);
        
        $multiComponentTime = microtime(true) - $startTime;
        
        // Should load multiple components efficiently
        $this->assertLessThan(5.0, $multiComponentTime);
        
        // All components should be successful
        $pasienComponent->assertSuccessful();
        $pendapatanComponent->assertSuccessful();
        $pengeluaranComponent->assertSuccessful();
    }

    public function test_component_update_performance()
    {
        // Test component update performance
        $this->actingAs($this->petugas);
        
        // Create data
        Pasien::factory()->count(200)->create(['input_by' => $this->petugas->id]);
        
        $component = Livewire::test(ListPasiens::class);
        
        // Test rapid consecutive updates
        $updateTimes = [];
        
        for ($i = 0; $i < 5; $i++) {
            $startTime = microtime(true);
            $component->set('tableSearch', "search_term_{$i}");
            $endTime = microtime(true);
            
            $updateTimes[] = $endTime - $startTime;
        }
        
        // All updates should be fast
        foreach ($updateTimes as $time) {
            $this->assertLessThan(1.0, $time);
        }
        
        // Average update time should be very fast
        $avgTime = array_sum($updateTimes) / count($updateTimes);
        $this->assertLessThan(0.5, $avgTime);
    }

    public function test_component_validation_performance()
    {
        // Test form validation performance
        $this->actingAs($this->petugas);
        
        $component = Livewire::test(CreatePasien::class);
        
        // Test validation with invalid data
        $invalidData = [
            'nama' => '', // Required field
            'tanggal_lahir' => 'invalid-date',
            'jenis_kelamin' => 'invalid',
            'email' => 'invalid-email',
        ];
        
        $startTime = microtime(true);
        $component->fillForm($invalidData)->call('create');
        $validationTime = microtime(true) - $startTime;
        
        // Validation should be fast even with multiple errors
        $this->assertLessThan(1.0, $validationTime);
        
        // Should have validation errors
        $this->assertTrue($component->instance->getErrorBag()->isNotEmpty());
    }

    public function test_component_database_query_optimization()
    {
        // Test database query optimization in components
        $this->actingAs($this->petugas);
        
        // Create data with relationships
        $patients = Pasien::factory()->count(50)->create(['input_by' => $this->petugas->id]);
        
        foreach ($patients as $patient) {
            Tindakan::factory()->count(2)->create([
                'input_by' => $this->petugas->id,
                'pasien_id' => $patient->id,
                'jenis_tindakan_id' => $this->baseData['jenis_tindakan']->id,
                'shift_id' => $this->baseData['shift']->id,
            ]);
        }
        
        // Enable query logging
        DB::enableQueryLog();
        
        $startTime = microtime(true);
        $component = Livewire::test(ListTindakans::class);
        $loadTime = microtime(true) - $startTime;
        
        $queryLog = DB::getQueryLog();
        $queryCount = count($queryLog);
        
        // Should load efficiently
        $this->assertLessThan(3.0, $loadTime);
        
        // Should not have excessive queries (N+1 prevention)
        $this->assertLessThan(15, $queryCount);
        
        DB::disableQueryLog();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}