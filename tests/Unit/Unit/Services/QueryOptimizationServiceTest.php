<?php

namespace Tests\Unit\Unit\Services;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Builder;
use App\Services\QueryOptimizationService;
use App\Services\LoggingService;
use App\Models\Pasien;
use App\Models\Tindakan;
use App\Models\Pendapatan;

class QueryOptimizationServiceTest extends TestCase
{
    use RefreshDatabase;

    private QueryOptimizationService $optimizer;
    private LoggingService $loggingService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->loggingService = $this->createMock(LoggingService::class);
        $this->optimizer = new QueryOptimizationService($this->loggingService);
    }

    /** @test */
    public function it_can_optimize_query_with_eager_loading()
    {
        $query = Pasien::query();
        
        $optimizedQuery = $this->optimizer->optimizeQuery($query, Pasien::class);
        
        $this->assertInstanceOf(Builder::class, $optimizedQuery);
        
        // Check that eager loading is applied
        $eagerLoads = $optimizedQuery->getEagerLoads();
        $this->assertArrayHasKey('tindakan', $eagerLoads);
    }

    /** @test */
    public function it_can_optimize_query_with_additional_relations()
    {
        $query = Tindakan::query();
        $additionalRelations = ['customRelation'];
        
        $optimizedQuery = $this->optimizer->optimizeQuery($query, Tindakan::class, $additionalRelations);
        
        $this->assertInstanceOf(Builder::class, $optimizedQuery);
        
        // Check that both default and additional relations are loaded
        $eagerLoads = $optimizedQuery->getEagerLoads();
        $this->assertArrayHasKey('pasien', $eagerLoads); // Default
        $this->assertArrayHasKey('customRelation', $eagerLoads); // Additional
    }

    /** @test */
    public function it_can_optimize_count_query()
    {
        // Create test data
        Pasien::factory()->count(5)->create();
        
        $query = Pasien::query();
        
        $count = $this->optimizer->optimizeCountQuery($query, Pasien::class);
        
        $this->assertEquals(5, $count);
    }

    /** @test */
    public function it_can_optimize_paginated_query()
    {
        // Create test data
        Pasien::factory()->count(20)->create();
        
        $query = Pasien::query();
        
        $optimizedQuery = $this->optimizer->optimizePaginatedQuery($query, Pasien::class, 1, 10);
        
        $this->assertInstanceOf(Builder::class, $optimizedQuery);
        
        // Check that eager loading is applied
        $eagerLoads = $optimizedQuery->getEagerLoads();
        $this->assertArrayHasKey('tindakan', $eagerLoads);
    }

    /** @test */
    public function it_can_optimize_search_query()
    {
        // Create test data
        Pasien::factory()->create(['nama' => 'John Doe']);
        Pasien::factory()->create(['nama' => 'Jane Smith']);
        
        $query = Pasien::query();
        $searchTerm = 'John';
        
        $optimizedQuery = $this->optimizer->optimizeSearchQuery($query, Pasien::class, $searchTerm);
        
        $this->assertInstanceOf(Builder::class, $optimizedQuery);
        
        // Execute the query to verify it works
        $results = $optimizedQuery->get();
        $this->assertCount(1, $results);
        $this->assertEquals('John Doe', $results->first()->nama);
    }

    /** @test */
    public function it_can_optimize_search_query_with_custom_fields()
    {
        // Create test data
        Pasien::factory()->create(['nama' => 'John Doe', 'no_rekam_medis' => 'RM001']);
        Pasien::factory()->create(['nama' => 'Jane Smith', 'no_rekam_medis' => 'RM002']);
        
        $query = Pasien::query();
        $searchTerm = 'RM001';
        $searchFields = ['no_rekam_medis'];
        
        $optimizedQuery = $this->optimizer->optimizeSearchQuery($query, Pasien::class, $searchTerm, $searchFields);
        
        $results = $optimizedQuery->get();
        $this->assertCount(1, $results);
        $this->assertEquals('RM001', $results->first()->no_rekam_medis);
    }

    /** @test */
    public function it_can_optimize_bulk_insert_operation()
    {
        $data = [
            ['nama' => 'Patient 1', 'no_rekam_medis' => 'RM001', 'jenis_kelamin' => 'L'],
            ['nama' => 'Patient 2', 'no_rekam_medis' => 'RM002', 'jenis_kelamin' => 'P'],
            ['nama' => 'Patient 3', 'no_rekam_medis' => 'RM003', 'jenis_kelamin' => 'L'],
        ];
        
        $result = $this->optimizer->optimizeBulkOperation('insert', Pasien::class, $data);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('inserted', $result);
        $this->assertEquals(3, $result['inserted']);
        
        // Verify data was inserted
        $this->assertDatabaseHas('pasien', ['no_rekam_medis' => 'RM001']);
        $this->assertDatabaseHas('pasien', ['no_rekam_medis' => 'RM002']);
        $this->assertDatabaseHas('pasien', ['no_rekam_medis' => 'RM003']);
    }

    /** @test */
    public function it_can_optimize_bulk_update_operation()
    {
        // Create test data
        $patients = Pasien::factory()->count(3)->create();
        
        $data = [
            ['id' => $patients[0]->id, 'nama' => 'Updated Patient 1'],
            ['id' => $patients[1]->id, 'nama' => 'Updated Patient 2'],
            ['id' => $patients[2]->id, 'nama' => 'Updated Patient 3'],
        ];
        
        $result = $this->optimizer->optimizeBulkOperation('update', Pasien::class, $data);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('updated', $result);
        $this->assertEquals(3, $result['updated']);
        
        // Verify data was updated
        $this->assertDatabaseHas('pasien', ['id' => $patients[0]->id, 'nama' => 'Updated Patient 1']);
        $this->assertDatabaseHas('pasien', ['id' => $patients[1]->id, 'nama' => 'Updated Patient 2']);
        $this->assertDatabaseHas('pasien', ['id' => $patients[2]->id, 'nama' => 'Updated Patient 3']);
    }

    /** @test */
    public function it_can_optimize_bulk_delete_operation()
    {
        // Create test data
        $patients = Pasien::factory()->count(3)->create();
        $ids = $patients->pluck('id')->toArray();
        
        $result = $this->optimizer->optimizeBulkOperation('delete', Pasien::class, $ids);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('deleted', $result);
        $this->assertEquals(3, $result['deleted']);
        
        // Verify data was deleted
        $this->assertDatabaseMissing('pasien', ['id' => $patients[0]->id]);
        $this->assertDatabaseMissing('pasien', ['id' => $patients[1]->id]);
        $this->assertDatabaseMissing('pasien', ['id' => $patients[2]->id]);
    }

    /** @test */
    public function it_can_get_optimization_statistics()
    {
        $stats = $this->optimizer->getOptimizationStats();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('eager_load_relationships', $stats);
        $this->assertArrayHasKey('optimization_patterns', $stats);
        $this->assertArrayHasKey('cache_enabled', $stats);
        $this->assertArrayHasKey('database_driver', $stats);
    }

    /** @test */
    public function it_can_analyze_query_performance()
    {
        // Create test data
        Pasien::factory()->count(10)->create();
        
        $query = Pasien::query()->limit(5);
        
        $analysis = $this->optimizer->analyzeQueryPerformance($query, Pasien::class);
        
        $this->assertIsArray($analysis);
        $this->assertArrayHasKey('execution_time', $analysis);
        $this->assertArrayHasKey('result_count', $analysis);
        $this->assertArrayHasKey('queries_executed', $analysis);
        $this->assertArrayHasKey('model', $analysis);
        $this->assertArrayHasKey('memory_usage', $analysis);
        $this->assertArrayHasKey('queries', $analysis);
        
        $this->assertEquals(5, $analysis['result_count']);
        $this->assertEquals('Pasien', $analysis['model']);
        $this->assertIsFloat($analysis['execution_time']);
        $this->assertIsInt($analysis['memory_usage']);
    }

    /** @test */
    public function it_handles_optimization_errors_gracefully()
    {
        // Test with invalid model class
        $query = Pasien::query();
        
        $result = $this->optimizer->optimizeQuery($query, 'InvalidModel');
        
        // Should return the original query on error
        $this->assertInstanceOf(Builder::class, $result);
    }

    /** @test */
    public function it_can_optimize_large_dataset_count()
    {
        // Mock large dataset by creating many records
        Pasien::factory()->count(50)->create();
        
        $query = Pasien::query();
        
        $count = $this->optimizer->optimizeCountQuery($query, Pasien::class);
        
        $this->assertEquals(50, $count);
    }

    /** @test */
    public function it_can_optimize_cursor_pagination_for_large_datasets()
    {
        // Create test data
        Pasien::factory()->count(200)->create();
        
        $query = Pasien::query();
        
        // Test with high page number (should trigger cursor pagination)
        $optimizedQuery = $this->optimizer->optimizePaginatedQuery($query, Pasien::class, 150, 10);
        
        $this->assertInstanceOf(Builder::class, $optimizedQuery);
    }

    /** @test */
    public function it_can_handle_search_with_relationship_fields()
    {
        // Create test data with relationships
        $patient = Pasien::factory()->create();
        $tindakan = Tindakan::factory()->create([
            'pasien_id' => $patient->id,
        ]);
        
        $query = Tindakan::query();
        $searchTerm = $patient->nama;
        
        $optimizedQuery = $this->optimizer->optimizeSearchQuery($query, Tindakan::class, $searchTerm);
        
        $this->assertInstanceOf(Builder::class, $optimizedQuery);
    }

    /** @test */
    public function it_logs_optimization_operations()
    {
        $this->loggingService->expects($this->once())
            ->method('logPerformance')
            ->with(
                'query_optimization',
                $this->anything(),
                $this->anything(),
                $this->anything()
            );
        
        $query = Pasien::query();
        $this->optimizer->optimizeQuery($query, Pasien::class);
    }

    /** @test */
    public function it_can_optimize_different_model_types()
    {
        // Test with different models
        $models = [
            Pasien::class,
            Tindakan::class,
            Pendapatan::class,
        ];
        
        foreach ($models as $modelClass) {
            $query = $modelClass::query();
            
            $optimizedQuery = $this->optimizer->optimizeQuery($query, $modelClass);
            
            $this->assertInstanceOf(Builder::class, $optimizedQuery);
        }
    }

    /** @test */
    public function it_can_handle_empty_search_terms()
    {
        $query = Pasien::query();
        $searchTerm = '';
        
        $optimizedQuery = $this->optimizer->optimizeSearchQuery($query, Pasien::class, $searchTerm);
        
        $this->assertInstanceOf(Builder::class, $optimizedQuery);
    }

    /** @test */
    public function it_can_optimize_queries_with_where_clauses()
    {
        $query = Pasien::query()->where('jenis_kelamin', 'L');
        
        $optimizedQuery = $this->optimizer->optimizeQuery($query, Pasien::class);
        
        $this->assertInstanceOf(Builder::class, $optimizedQuery);
        
        // Check that the where clause is preserved
        $wheres = $optimizedQuery->getQuery()->wheres;
        $this->assertCount(1, $wheres);
        $this->assertEquals('jenis_kelamin', $wheres[0]['column']);
    }

    /** @test */
    public function it_can_optimize_queries_with_ordering()
    {
        $query = Pasien::query()->orderBy('created_at', 'desc');
        
        $optimizedQuery = $this->optimizer->optimizeQuery($query, Pasien::class);
        
        $this->assertInstanceOf(Builder::class, $optimizedQuery);
        
        // Check that ordering is preserved
        $orders = $optimizedQuery->getQuery()->orders;
        $this->assertCount(1, $orders);
        $this->assertEquals('created_at', $orders[0]['column']);
        $this->assertEquals('desc', $orders[0]['direction']);
    }

    /** @test */
    public function it_can_handle_bulk_operations_with_large_datasets()
    {
        // Create large dataset for bulk operations
        $largeData = [];
        for ($i = 1; $i <= 100; $i++) {
            $largeData[] = [
                'nama' => "Patient $i",
                'no_rekam_medis' => "RM" . str_pad($i, 3, '0', STR_PAD_LEFT),
                'jenis_kelamin' => $i % 2 === 0 ? 'P' : 'L',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        $result = $this->optimizer->optimizeBulkOperation('insert', Pasien::class, $largeData);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('inserted', $result);
        $this->assertEquals(100, $result['inserted']);
    }

    /** @test */
    public function it_can_optimize_queries_with_joins()
    {
        $query = Tindakan::query()
            ->join('pasien', 'tindakan.pasien_id', '=', 'pasien.id')
            ->select('tindakan.*');
        
        $optimizedQuery = $this->optimizer->optimizeQuery($query, Tindakan::class);
        
        $this->assertInstanceOf(Builder::class, $optimizedQuery);
        
        // Check that joins are preserved
        $joins = $optimizedQuery->getQuery()->joins;
        $this->assertCount(1, $joins);
        $this->assertEquals('pasien', $joins[0]->table);
    }

    /** @test */
    public function it_can_handle_optimization_with_grouping()
    {
        $query = Pasien::query()
            ->groupBy('jenis_kelamin')
            ->selectRaw('jenis_kelamin, COUNT(*) as count');
        
        $optimizedQuery = $this->optimizer->optimizeQuery($query, Pasien::class);
        
        $this->assertInstanceOf(Builder::class, $optimizedQuery);
        
        // Check that grouping is preserved
        $groups = $optimizedQuery->getQuery()->groups;
        $this->assertCount(1, $groups);
        $this->assertEquals('jenis_kelamin', $groups[0]);
    }

    /** @test */
    public function it_can_optimize_queries_with_having_clauses()
    {
        $query = Pasien::query()
            ->groupBy('jenis_kelamin')
            ->havingRaw('COUNT(*) > 1');
        
        $optimizedQuery = $this->optimizer->optimizeQuery($query, Pasien::class);
        
        $this->assertInstanceOf(Builder::class, $optimizedQuery);
        
        // Check that having clause is preserved
        $havings = $optimizedQuery->getQuery()->havings;
        $this->assertCount(1, $havings);
    }
}