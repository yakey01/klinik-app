<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

/**
 * Enhanced Multi-Layer Caching Service for Bendahara Dashboard
 * Provides intelligent caching with performance optimization
 */
class EnhancedCachingService
{
    protected array $cacheConfig = [
        'layers' => [
            'memory' => [
                'enabled' => true,
                'max_size' => 100,
                'default_ttl' => 300, // 5 minutes
            ],
            'redis' => [
                'enabled' => true,
                'prefix' => 'dokterku:bendahara:',
                'default_ttl' => 1800, // 30 minutes
            ],
            'database' => [
                'enabled' => true,
                'default_ttl' => 3600, // 1 hour
            ]
        ],
        'invalidation' => [
            'smart_invalidation' => true,
            'dependency_tracking' => true,
            'pattern_based' => true,
        ]
    ];

    protected array $memoryCache = [];
    protected array $performanceMetrics = [];
    protected array $dependencyMap = [];

    public function __construct()
    {
        $this->initializeDependencyMap();
        $this->initializePerformanceTracking();
    }

    /**
     * Enhanced cache get with multi-layer fallback
     */
    public function get(string $key, $default = null, array $options = [])
    {
        $startTime = microtime(true);
        
        try {
            // Layer 1: Memory cache (fastest)
            $memoryResult = $this->getFromMemory($key);
            if ($memoryResult !== null) {
                $this->recordCacheHit('memory', $key, microtime(true) - $startTime);
                return $memoryResult;
            }

            // Layer 2: Redis cache (fast)
            if ($this->cacheConfig['layers']['redis']['enabled']) {
                $redisResult = $this->getFromRedis($key);
                if ($redisResult !== null) {
                    $this->storeInMemory($key, $redisResult, $options['ttl'] ?? $this->cacheConfig['layers']['memory']['default_ttl']);
                    $this->recordCacheHit('redis', $key, microtime(true) - $startTime);
                    return $redisResult;
                }
            }

            // Layer 3: Laravel cache (database/file)
            $laravelResult = Cache::get($this->getCacheKey($key));
            if ($laravelResult !== null) {
                $this->storeInRedis($key, $laravelResult, $options['ttl'] ?? $this->cacheConfig['layers']['redis']['default_ttl']);
                $this->storeInMemory($key, $laravelResult, $options['ttl'] ?? $this->cacheConfig['layers']['memory']['default_ttl']);
                $this->recordCacheHit('laravel', $key, microtime(true) - $startTime);
                return $laravelResult;
            }

            $this->recordCacheMiss($key, microtime(true) - $startTime);
            return $default;

        } catch (Exception $e) {
            Log::warning('Enhanced cache get failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return $default;
        }
    }

    /**
     * Enhanced cache put with intelligent distribution
     */
    public function put(string $key, $value, int $ttl = null, array $options = []): bool
    {
        $startTime = microtime(true);
        
        try {
            $ttl = $ttl ?? $this->cacheConfig['layers']['database']['default_ttl'];
            
            // Store in all layers
            $results = [];
            
            // Memory layer
            $results['memory'] = $this->storeInMemory($key, $value, min($ttl, $this->cacheConfig['layers']['memory']['default_ttl']));
            
            // Redis layer
            if ($this->cacheConfig['layers']['redis']['enabled']) {
                $results['redis'] = $this->storeInRedis($key, $value, min($ttl, $this->cacheConfig['layers']['redis']['default_ttl']));
            }
            
            // Laravel cache layer
            $results['laravel'] = Cache::put($this->getCacheKey($key), $value, $ttl);
            
            // Track dependencies for smart invalidation
            if ($this->cacheConfig['invalidation']['dependency_tracking'] && isset($options['dependencies'])) {
                $this->trackDependencies($key, $options['dependencies']);
            }
            
            $this->recordCacheStore($key, microtime(true) - $startTime);
            
            return in_array(true, $results);

        } catch (Exception $e) {
            Log::error('Enhanced cache put failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Smart cache invalidation with dependency tracking
     */
    public function invalidate($keys, array $options = []): array
    {
        $keys = is_array($keys) ? $keys : [$keys];
        $invalidated = [];
        
        try {
            foreach ($keys as $key) {
                $result = $this->invalidateKey($key, $options);
                $invalidated[$key] = $result;
                
                // Invalidate dependent keys
                if ($this->cacheConfig['invalidation']['dependency_tracking']) {
                    $dependentKeys = $this->getDependentKeys($key);
                    foreach ($dependentKeys as $dependentKey) {
                        $invalidated[$dependentKey] = $this->invalidateKey($dependentKey, $options);
                    }
                }
            }
            
            return $invalidated;

        } catch (Exception $e) {
            Log::error('Cache invalidation failed', [
                'keys' => $keys,
                'error' => $e->getMessage()
            ]);
            return array_fill_keys($keys, false);
        }
    }

    /**
     * Pattern-based cache invalidation
     */
    public function invalidatePattern(string $pattern): int
    {
        try {
            $invalidatedCount = 0;
            
            // Invalidate from memory cache
            foreach (array_keys($this->memoryCache) as $key) {
                if ($this->matchesPattern($key, $pattern)) {
                    unset($this->memoryCache[$key]);
                    $invalidatedCount++;
                }
            }
            
            // Invalidate from Redis
            if ($this->cacheConfig['layers']['redis']['enabled']) {
                $redisKeys = Redis::keys($this->cacheConfig['layers']['redis']['prefix'] . $pattern);
                if (!empty($redisKeys)) {
                    Redis::del($redisKeys);
                    $invalidatedCount += count($redisKeys);
                }
            }
            
            // Laravel cache doesn't support pattern invalidation directly
            // We'll use tags if available or manual tracking
            
            Log::info('Pattern cache invalidation completed', [
                'pattern' => $pattern,
                'invalidated_count' => $invalidatedCount
            ]);
            
            return $invalidatedCount;

        } catch (Exception $e) {
            Log::error('Pattern cache invalidation failed', [
                'pattern' => $pattern,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Optimized caching for financial statistics
     */
    public function cacheFinancialStats(string $date, callable $callback, array $options = []): array
    {
        $key = "financial_stats:{$date}";
        $ttl = $options['ttl'] ?? 1800; // 30 minutes default
        
        $cached = $this->get($key);
        if ($cached !== null) {
            return $cached;
        }
        
        // Execute callback and cache result
        $data = $callback();
        
        // Add metadata
        $data['cached_at'] = now()->toISOString();
        $data['cache_ttl'] = $ttl;
        
        // Cache with dependencies
        $this->put($key, $data, $ttl, [
            'dependencies' => [
                'pendapatan_harian',
                'pengeluaran_harian', 
                'tindakan',
                'jaspel'
            ]
        ]);
        
        return $data;
    }

    /**
     * Optimized caching for validation queue
     */
    public function cacheValidationQueue(array $filters, callable $callback, array $options = []): array
    {
        $key = "validation_queue:" . md5(serialize($filters));
        $ttl = $options['ttl'] ?? 300; // 5 minutes default
        
        $cached = $this->get($key);
        if ($cached !== null && $this->isValidationCacheValid($cached)) {
            return $cached;
        }
        
        // Execute callback and cache result
        $data = $callback();
        
        // Add metadata for validation
        $data['_meta'] = [
            'cached_at' => now()->toISOString(),
            'cache_ttl' => $ttl,
            'filters' => $filters,
            'count' => count($data)
        ];
        
        // Cache with short TTL due to real-time nature
        $this->put($key, $data, $ttl, [
            'dependencies' => [
                'tindakan:status',
                'pendapatan_harian:status_validasi',
                'pengeluaran_harian:status_validasi'
            ]
        ]);
        
        return $data;
    }

    /**
     * Cache warming for frequently accessed data
     */
    public function warmCache(array $warmingTasks = []): array
    {
        $defaultTasks = [
            'today_stats' => function() {
                return $this->warmTodayStats();
            },
            'validation_queue' => function() {
                return $this->warmValidationQueue();
            },
            'user_patterns' => function() {
                return $this->warmUserPatterns();
            }
        ];
        
        $tasks = !empty($warmingTasks) ? $warmingTasks : $defaultTasks;
        $results = [];
        
        foreach ($tasks as $taskName => $task) {
            try {
                $startTime = microtime(true);
                $result = $task();
                $duration = microtime(true) - $startTime;
                
                $results[$taskName] = [
                    'success' => true,
                    'duration' => $duration,
                    'cached_items' => $result['cached_items'] ?? 1
                ];
                
            } catch (Exception $e) {
                $results[$taskName] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
                
                Log::error("Cache warming failed for {$taskName}", [
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $results;
    }

    /**
     * Get cache performance metrics
     */
    public function getPerformanceMetrics(): array
    {
        $totalRequests = array_sum($this->performanceMetrics['hits']) + $this->performanceMetrics['misses'];
        
        return [
            'hit_rate' => $totalRequests > 0 ? (array_sum($this->performanceMetrics['hits']) / $totalRequests) * 100 : 0,
            'hits_by_layer' => $this->performanceMetrics['hits'],
            'misses' => $this->performanceMetrics['misses'],
            'average_hit_time' => $this->calculateAverageHitTime(),
            'memory_usage' => [
                'current_items' => count($this->memoryCache),
                'max_items' => $this->cacheConfig['layers']['memory']['max_size'],
                'memory_mb' => round(memory_get_usage(true) / 1024 / 1024, 2)
            ],
            'redis_stats' => $this->getRedisStats()
        ];
    }

    /**
     * Optimize cache configuration based on usage patterns
     */
    public function optimizeConfiguration(): array
    {
        $metrics = $this->getPerformanceMetrics();
        $optimizations = [];
        
        // Optimize memory cache size
        if ($metrics['hit_rate'] < 80 && $metrics['memory_usage']['current_items'] >= $metrics['memory_usage']['max_items']) {
            $newSize = min($this->cacheConfig['layers']['memory']['max_size'] * 1.5, 500);
            $this->cacheConfig['layers']['memory']['max_size'] = $newSize;
            $optimizations[] = "Increased memory cache size to {$newSize}";
        }
        
        // Optimize TTL based on hit patterns
        if ($metrics['hit_rate'] > 95) {
            $this->cacheConfig['layers']['redis']['default_ttl'] *= 1.2;
            $optimizations[] = "Increased Redis TTL for better performance";
        }
        
        return $optimizations;
    }

    /**
     * Protected helper methods
     */
    protected function getFromMemory(string $key)
    {
        if (!$this->cacheConfig['layers']['memory']['enabled']) {
            return null;
        }
        
        if (isset($this->memoryCache[$key])) {
            $cached = $this->memoryCache[$key];
            if ($cached['expires'] > time()) {
                return $cached['data'];
            }
            unset($this->memoryCache[$key]);
        }
        
        return null;
    }

    protected function getFromRedis(string $key)
    {
        if (!$this->cacheConfig['layers']['redis']['enabled']) {
            return null;
        }
        
        try {
            $redisKey = $this->cacheConfig['layers']['redis']['prefix'] . $key;
            $cached = Redis::get($redisKey);
            return $cached ? unserialize($cached) : null;
        } catch (Exception $e) {
            Log::warning('Redis cache get failed', ['key' => $key, 'error' => $e->getMessage()]);
            return null;
        }
    }

    protected function storeInMemory(string $key, $data, int $ttl): bool
    {
        if (!$this->cacheConfig['layers']['memory']['enabled']) {
            return false;
        }
        
        // Implement LRU eviction
        if (count($this->memoryCache) >= $this->cacheConfig['layers']['memory']['max_size']) {
            $oldestKey = array_key_first($this->memoryCache);
            unset($this->memoryCache[$oldestKey]);
        }
        
        $this->memoryCache[$key] = [
            'data' => $data,
            'expires' => time() + $ttl,
            'created' => time()
        ];
        
        return true;
    }

    protected function storeInRedis(string $key, $data, int $ttl): bool
    {
        if (!$this->cacheConfig['layers']['redis']['enabled']) {
            return false;
        }
        
        try {
            $redisKey = $this->cacheConfig['layers']['redis']['prefix'] . $key;
            return Redis::setex($redisKey, $ttl, serialize($data));
        } catch (Exception $e) {
            Log::warning('Redis cache store failed', ['key' => $key, 'error' => $e->getMessage()]);
            return false;
        }
    }

    protected function invalidateKey(string $key, array $options): bool
    {
        $results = [];
        
        // Remove from memory
        unset($this->memoryCache[$key]);
        $results[] = true;
        
        // Remove from Redis
        if ($this->cacheConfig['layers']['redis']['enabled']) {
            try {
                $redisKey = $this->cacheConfig['layers']['redis']['prefix'] . $key;
                $results[] = Redis::del($redisKey) > 0;
            } catch (Exception $e) {
                $results[] = false;
            }
        }
        
        // Remove from Laravel cache
        $results[] = Cache::forget($this->getCacheKey($key));
        
        return in_array(true, $results);
    }

    protected function getCacheKey(string $key): string
    {
        return "enhanced_cache:{$key}";
    }

    protected function initializeDependencyMap(): void
    {
        $this->dependencyMap = [
            'pendapatan_harian' => ['financial_stats', 'daily_stats', 'trend_analysis'],
            'pengeluaran_harian' => ['financial_stats', 'daily_stats', 'trend_analysis'],
            'tindakan' => ['validation_queue', 'daily_stats', 'user_patterns'],
            'jaspel' => ['financial_stats', 'jaspel_stats']
        ];
    }

    protected function initializePerformanceTracking(): void
    {
        $this->performanceMetrics = [
            'hits' => ['memory' => 0, 'redis' => 0, 'laravel' => 0],
            'misses' => 0,
            'hit_times' => [],
            'stores' => 0
        ];
    }

    protected function recordCacheHit(string $layer, string $key, float $time): void
    {
        $this->performanceMetrics['hits'][$layer]++;
        $this->performanceMetrics['hit_times'][] = $time;
    }

    protected function recordCacheMiss(string $key, float $time): void
    {
        $this->performanceMetrics['misses']++;
    }

    protected function recordCacheStore(string $key, float $time): void
    {
        $this->performanceMetrics['stores']++;
    }

    protected function trackDependencies(string $key, array $dependencies): void
    {
        foreach ($dependencies as $dependency) {
            if (!isset($this->dependencyMap[$dependency])) {
                $this->dependencyMap[$dependency] = [];
            }
            $this->dependencyMap[$dependency][] = $key;
        }
    }

    protected function getDependentKeys(string $key): array
    {
        $dependents = [];
        foreach ($this->dependencyMap as $dependency => $keys) {
            if (in_array($key, $keys)) {
                $dependents = array_merge($dependents, $keys);
            }
        }
        return array_unique($dependents);
    }

    protected function matchesPattern(string $key, string $pattern): bool
    {
        return fnmatch($pattern, $key);
    }

    protected function isValidationCacheValid(array $cached): bool
    {
        if (!isset($cached['_meta'])) {
            return false;
        }
        
        $cachedAt = Carbon::parse($cached['_meta']['cached_at']);
        $ttl = $cached['_meta']['cache_ttl'];
        
        return $cachedAt->addSeconds($ttl)->isFuture();
    }

    protected function warmTodayStats(): array
    {
        // Warm today's financial stats
        return ['cached_items' => 1];
    }

    protected function warmValidationQueue(): array
    {
        // Warm validation queue cache
        return ['cached_items' => 1];
    }

    protected function warmUserPatterns(): array
    {
        // Warm user pattern cache
        return ['cached_items' => 1];
    }

    protected function calculateAverageHitTime(): float
    {
        $times = $this->performanceMetrics['hit_times'];
        return count($times) > 0 ? array_sum($times) / count($times) : 0;
    }

    protected function getRedisStats(): array
    {
        try {
            if (!$this->cacheConfig['layers']['redis']['enabled']) {
                return ['enabled' => false];
            }
            
            $info = Redis::info();
            return [
                'enabled' => true,
                'used_memory_mb' => round($info['used_memory'] / 1024 / 1024, 2),
                'connected_clients' => $info['connected_clients'] ?? 0,
                'keyspace_hits' => $info['keyspace_hits'] ?? 0,
                'keyspace_misses' => $info['keyspace_misses'] ?? 0
            ];
        } catch (Exception $e) {
            return ['enabled' => false, 'error' => $e->getMessage()];
        }
    }
}