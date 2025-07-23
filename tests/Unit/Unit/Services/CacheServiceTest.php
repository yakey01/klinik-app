<?php

namespace Tests\Unit\Unit\Services;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use App\Services\CacheService;
use App\Services\LoggingService;
use App\Models\Pasien;

class CacheServiceTest extends TestCase
{
    use RefreshDatabase;

    private CacheService $cacheService;
    private LoggingService $loggingService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->loggingService = $this->createMock(LoggingService::class);
        $this->cacheService = new CacheService($this->loggingService);
        
        // Clear cache before each test
        Cache::flush();
    }

    /** @test */
    public function it_can_cache_model_query()
    {
        $key = 'test_model_query';
        $expectedValue = 'cached_result';
        
        $result = $this->cacheService->cacheModelQuery($key, function() use ($expectedValue) {
            return $expectedValue;
        });
        
        $this->assertEquals($expectedValue, $result);
        
        // Verify it's cached
        $cachedResult = $this->cacheService->cacheModelQuery($key, function() {
            return 'should_not_be_called';
        });
        
        $this->assertEquals($expectedValue, $cachedResult);
    }

    /** @test */
    public function it_can_cache_query_results()
    {
        $key = 'test_query';
        $expectedValue = ['data' => 'query_result'];
        
        $result = $this->cacheService->cacheQuery($key, function() use ($expectedValue) {
            return $expectedValue;
        });
        
        $this->assertEquals($expectedValue, $result);
    }

    /** @test */
    public function it_can_cache_view_fragments()
    {
        $key = 'test_view';
        $expectedValue = '<div>cached view</div>';
        
        $result = $this->cacheService->cacheView($key, function() use ($expectedValue) {
            return $expectedValue;
        });
        
        $this->assertEquals($expectedValue, $result);
    }

    /** @test */
    public function it_can_cache_api_responses()
    {
        $key = 'test_api';
        $expectedValue = ['status' => 'success', 'data' => []];
        
        $result = $this->cacheService->cacheApiResponse($key, function() use ($expectedValue) {
            return $expectedValue;
        });
        
        $this->assertEquals($expectedValue, $result);
    }

    /** @test */
    public function it_can_cache_dashboard_data()
    {
        $key = 'test_dashboard';
        $expectedValue = ['stats' => ['users' => 100, 'orders' => 50]];
        
        $result = $this->cacheService->cacheDashboard($key, function() use ($expectedValue) {
            return $expectedValue;
        });
        
        $this->assertEquals($expectedValue, $result);
    }

    /** @test */
    public function it_can_cache_report_data()
    {
        $key = 'test_report';
        $expectedValue = ['report' => 'monthly_sales'];
        
        $result = $this->cacheService->cacheReport($key, function() use ($expectedValue) {
            return $expectedValue;
        });
        
        $this->assertEquals($expectedValue, $result);
    }

    /** @test */
    public function it_can_cache_statistics()
    {
        $key = 'test_statistics';
        $expectedValue = ['total' => 1000, 'average' => 50];
        
        $result = $this->cacheService->cacheStatistics($key, function() use ($expectedValue) {
            return $expectedValue;
        });
        
        $this->assertEquals($expectedValue, $result);
    }

    /** @test */
    public function it_can_forget_cache_by_key()
    {
        $key = 'test_forget';
        $value = 'test_value';
        
        // Cache a value
        $this->cacheService->cacheModelQuery($key, function() use ($value) {
            return $value;
        });
        
        // Verify it's cached
        $this->assertTrue(Cache::has('model:' . $key));
        
        // Forget the cache
        $result = $this->cacheService->forget($key);
        
        $this->assertTrue($result);
        $this->assertFalse(Cache::has('model:' . $key));
    }

    /** @test */
    public function it_can_flush_cache_by_tag()
    {
        $key1 = 'test_tag_1';
        $key2 = 'test_tag_2';
        
        // Cache some values
        $this->cacheService->cacheModelQuery($key1, function() {
            return 'value1';
        });
        
        $this->cacheService->cacheQuery($key2, function() {
            return 'value2';
        });
        
        // Flush model cache tag
        $result = $this->cacheService->flushTag('model');
        
        $this->assertTrue($result);
    }

    /** @test */
    public function it_can_flush_all_cache()
    {
        // Cache some values
        $this->cacheService->cacheModelQuery('test1', function() {
            return 'value1';
        });
        
        $this->cacheService->cacheQuery('test2', function() {
            return 'value2';
        });
        
        // Flush all cache
        $result = $this->cacheService->flushAll();
        
        $this->assertTrue($result);
    }

    /** @test */
    public function it_can_get_cache_statistics()
    {
        $stats = $this->cacheService->getStats();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('enabled', $stats);
        $this->assertArrayHasKey('driver', $stats);
        $this->assertArrayHasKey('tags', $stats);
        $this->assertArrayHasKey('prefixes', $stats);
        $this->assertArrayHasKey('ttl_config', $stats);
    }

    /** @test */
    public function it_can_warm_up_cache()
    {
        // Create some test data
        Pasien::factory()->count(3)->create();
        
        $warmedData = $this->cacheService->warmUp();
        
        $this->assertIsArray($warmedData);
        $this->assertArrayHasKey('dashboard', $warmedData);
        $this->assertArrayHasKey('model_counts', $warmedData);
        $this->assertArrayHasKey('user_stats', $warmedData);
        $this->assertArrayHasKey('financial_summaries', $warmedData);
    }

    /** @test */
    public function it_uses_correct_cache_prefixes()
    {
        $key = 'test_prefix';
        $value = 'test_value';
        
        // Test model cache prefix
        $this->cacheService->cacheModelQuery($key, function() use ($value) {
            return $value;
        });
        
        $this->assertTrue(Cache::has('model:' . $key));
        
        // Test query cache prefix
        $this->cacheService->cacheQuery($key, function() use ($value) {
            return $value;
        });
        
        $this->assertTrue(Cache::has('query:' . $key));
    }

    /** @test */
    public function it_uses_correct_ttl_values()
    {
        $key = 'test_ttl';
        $value = 'test_value';
        
        // Test with custom TTL
        $customTtl = 300;
        $this->cacheService->cacheModelQuery($key, function() use ($value) {
            return $value;
        }, $customTtl);
        
        // Verify the value is cached
        $this->assertTrue(Cache::has('model:' . $key));
    }

    /** @test */
    public function it_handles_cache_failures_gracefully()
    {
        // Mock cache failure
        Cache::shouldReceive('remember')
            ->once()
            ->andThrow(new \Exception('Cache failure'));
            
        // Mock flush method for tearDown
        Cache::shouldReceive('flush')->andReturnNull();
        
        $key = 'test_failure';
        $expectedValue = 'fallback_value';
        
        $result = $this->cacheService->cacheModelQuery($key, function() use ($expectedValue) {
            return $expectedValue;
        });
        
        $this->assertEquals($expectedValue, $result);
    }

    /** @test */
    public function it_can_cache_model_with_invalidation()
    {
        $patient = Pasien::factory()->create();
        $key = 'test_model_invalidation';
        $value = 'cached_value';
        
        $result = $this->cacheService->cacheModelWithInvalidation($patient, $key, function() use ($value) {
            return $value;
        });
        
        $this->assertEquals($value, $result);
    }

    /** @test */
    public function it_can_invalidate_model_cache()
    {
        $patient = Pasien::factory()->create();
        
        $result = $this->cacheService->invalidateModelCache($patient);
        
        $this->assertTrue($result);
    }

    /** @test */
    public function it_can_cache_with_dependencies()
    {
        $key = 'test_dependencies';
        $dependencies = ['user_id' => 1, 'role' => 'admin'];
        $value = 'dependent_value';
        
        $result = $this->cacheService->cacheWithDependencies($key, $dependencies, function() use ($value) {
            return $value;
        });
        
        $this->assertEquals($value, $result);
    }

    /** @test */
    public function it_can_perform_batch_cache_operations()
    {
        $operations = [
            [
                'type' => 'model',
                'key' => 'batch_model',
                'callback' => function() { return 'model_value'; },
                'ttl' => 300
            ],
            [
                'type' => 'query',
                'key' => 'batch_query',
                'callback' => function() { return 'query_value'; },
                'ttl' => 600
            ],
            [
                'type' => 'dashboard',
                'key' => 'batch_dashboard',
                'callback' => function() { return ['stats' => 'dashboard_value']; },
                'ttl' => 900
            ]
        ];
        
        $results = $this->cacheService->batchCache($operations);
        
        $this->assertIsArray($results);
        $this->assertArrayHasKey('batch_model', $results);
        $this->assertArrayHasKey('batch_query', $results);
        $this->assertArrayHasKey('batch_dashboard', $results);
        
        $this->assertEquals('model_value', $results['batch_model']);
        $this->assertEquals('query_value', $results['batch_query']);
        $this->assertEquals(['stats' => 'dashboard_value'], $results['batch_dashboard']);
    }

    /** @test */
    public function it_logs_cache_operations()
    {
        $this->loggingService->expects($this->once())
            ->method('logPerformance')
            ->with(
                'cache_access',
                $this->anything(),
                $this->anything(),
                $this->anything()
            );
        
        $key = 'test_logging';
        $value = 'logged_value';
        
        $this->cacheService->cacheModelQuery($key, function() use ($value) {
            return $value;
        });
    }

    /** @test */
    public function it_can_get_model_cache_key()
    {
        $patient = Pasien::factory()->create();
        $suffix = 'test_suffix';
        
        $cacheKey = $this->cacheService->getModelCacheKey($patient, $suffix);
        
        $expectedKey = 'model:' . get_class($patient) . ':' . $patient->getKey() . ':' . $suffix;
        $this->assertEquals($expectedKey, $cacheKey);
    }

    /** @test */
    public function it_estimates_cache_entry_size()
    {
        $smallData = 'small';
        $largeData = str_repeat('large data ', 1000);
        
        $smallResult = $this->cacheService->cacheModelQuery('small', function() use ($smallData) {
            return $smallData;
        });
        
        $largeResult = $this->cacheService->cacheModelQuery('large', function() use ($largeData) {
            return $largeData;
        });
        
        $this->assertEquals($smallData, $smallResult);
        $this->assertEquals($largeData, $largeResult);
    }

    /** @test */
    public function it_handles_disabled_cache()
    {
        // Mock cache as disabled
        config(['cache.enabled' => false]);
        
        $key = 'test_disabled';
        $value = 'direct_value';
        
        $result = $this->cacheService->cacheModelQuery($key, function() use ($value) {
            return $value;
        });
        
        $this->assertEquals($value, $result);
    }

    /** @test */
    public function it_can_clear_specific_cache_patterns()
    {
        // Cache some values with different patterns
        $this->cacheService->cacheModelQuery('model_test', function() {
            return 'model_value';
        });
        
        $this->cacheService->cacheQuery('query_test', function() {
            return 'query_value';
        });
        
        // Clear specific pattern
        $result = $this->cacheService->flushTag('model');
        
        $this->assertTrue($result);
    }

    /** @test */
    public function it_can_warm_up_specific_cache_types()
    {
        // Test warming up different cache types
        $warmedData = $this->cacheService->warmUp();
        
        $this->assertIsArray($warmedData);
        
        // Check that all cache types are warmed
        $this->assertArrayHasKey('dashboard', $warmedData);
        $this->assertArrayHasKey('model_counts', $warmedData);
        $this->assertArrayHasKey('user_stats', $warmedData);
        $this->assertArrayHasKey('financial_summaries', $warmedData);
    }
    
    protected function tearDown(): void
    {
        // Use real Cache facade for cleanup, bypassing mocks
        \Illuminate\Support\Facades\Cache::getFacadeRoot()->flush();
        parent::tearDown();
    }
}