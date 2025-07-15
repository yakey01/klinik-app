<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use App\Services\CacheService;
use App\Services\LoggingService;

class CacheServiceBasicTest extends TestCase
{
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

    public function test_it_can_cache_model_query()
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

    public function test_it_can_cache_query_results()
    {
        $key = 'test_query';
        $expectedValue = ['data' => 'query_result'];
        
        $result = $this->cacheService->cacheQuery($key, function() use ($expectedValue) {
            return $expectedValue;
        });
        
        $this->assertEquals($expectedValue, $result);
    }

    public function test_it_can_cache_dashboard_data()
    {
        $key = 'test_dashboard';
        $expectedValue = ['stats' => ['users' => 100, 'orders' => 50]];
        
        $result = $this->cacheService->cacheDashboard($key, function() use ($expectedValue) {
            return $expectedValue;
        });
        
        $this->assertEquals($expectedValue, $result);
    }

    public function test_it_can_forget_cache_by_key()
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

    public function test_it_can_get_cache_statistics()
    {
        $stats = $this->cacheService->getStats();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('enabled', $stats);
        $this->assertArrayHasKey('driver', $stats);
        $this->assertArrayHasKey('tags', $stats);
        $this->assertArrayHasKey('prefixes', $stats);
        $this->assertArrayHasKey('ttl_config', $stats);
    }

    public function test_it_uses_correct_cache_prefixes()
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

    public function test_it_handles_cache_failures_gracefully()
    {
        // Mock cache failure
        Cache::shouldReceive('remember')
            ->once()
            ->andThrow(new \Exception('Cache failure'));
        
        $key = 'test_failure';
        $expectedValue = 'fallback_value';
        
        $result = $this->cacheService->cacheModelQuery($key, function() use ($expectedValue) {
            return $expectedValue;
        });
        
        $this->assertEquals($expectedValue, $result);
    }
}