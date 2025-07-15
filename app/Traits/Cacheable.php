<?php

namespace App\Traits;

use App\Services\CacheService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

trait Cacheable
{
    /**
     * Cache service instance
     */
    protected static ?CacheService $cacheService = null;
    
    /**
     * Get cache TTL for this model
     */
    protected function getCacheTtl(): int
    {
        return property_exists($this, 'customCacheTtl') ? $this->customCacheTtl : 1800; // 30 minutes default
    }
    
    /**
     * Boot the cacheable trait
     */
    public static function bootCacheable(): void
    {
        static::created(function (Model $model) {
            $model->invalidateModelCache();
        });
        
        static::updated(function (Model $model) {
            $model->invalidateModelCache();
        });
        
        static::deleted(function (Model $model) {
            $model->invalidateModelCache();
        });
    }
    
    /**
     * Get cache service instance
     */
    protected function getCacheService(): CacheService
    {
        if (!static::$cacheService) {
            static::$cacheService = app(CacheService::class);
        }
        
        return static::$cacheService;
    }
    
    /**
     * Cache a query result
     */
    public function cacheQuery(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $cacheKey = $this->getCacheKey($key);
        
        return $this->getCacheService()->cacheModelQuery(
            $cacheKey,
            $callback,
            $ttl ?? $this->getCacheTtl()
        );
    }
    
    /**
     * Cache model attributes
     */
    public function cacheAttribute(string $attribute, callable $callback, ?int $ttl = null): mixed
    {
        $cacheKey = $this->getCacheKey("attribute:{$attribute}");
        
        return $this->getCacheService()->cacheModelQuery(
            $cacheKey,
            $callback,
            $ttl ?? $this->getCacheTtl()
        );
    }
    
    /**
     * Cache model relationships
     */
    public function cacheRelation(string $relation, ?int $ttl = null): mixed
    {
        $cacheKey = $this->getCacheKey("relation:{$relation}");
        
        return $this->getCacheService()->cacheModelQuery(
            $cacheKey,
            function() use ($relation) {
                return $this->{$relation}();
            },
            $ttl ?? $this->getCacheTtl()
        );
    }
    
    /**
     * Cache count queries
     */
    public function cacheCount(string $key, callable $callback, ?int $ttl = null): int
    {
        $cacheKey = $this->getCacheKey("count:{$key}");
        
        return $this->getCacheService()->cacheModelQuery(
            $cacheKey,
            $callback,
            $ttl ?? $this->getCacheTtl()
        );
    }
    
    /**
     * Cache aggregation queries
     */
    public function cacheAggregate(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $cacheKey = $this->getCacheKey("aggregate:{$key}");
        
        return $this->getCacheService()->cacheModelQuery(
            $cacheKey,
            $callback,
            $ttl ?? $this->getCacheTtl()
        );
    }
    
    /**
     * Cache model with dependencies
     */
    public function cacheWithDependencies(string $key, array $dependencies, callable $callback, ?int $ttl = null): mixed
    {
        return $this->getCacheService()->cacheWithDependencies(
            $this->getCacheKey($key),
            $dependencies,
            $callback,
            $ttl ?? $this->getCacheTtl()
        );
    }
    
    /**
     * Get cache key for this model
     */
    protected function getCacheKey(string $suffix = ''): string
    {
        $modelClass = get_class($this);
        $key = class_basename($modelClass);
        
        if ($this->exists) {
            $key .= ":{$this->getKey()}";
        }
        
        if ($suffix) {
            $key .= ":{$suffix}";
        }
        
        return $key;
    }
    
    /**
     * Invalidate cache for this model
     */
    public function invalidateModelCache(): bool
    {
        return $this->getCacheService()->invalidateModelCache($this);
    }
    
    /**
     * Invalidate specific cache key
     */
    public function invalidateCache(string $key): bool
    {
        $cacheKey = $this->getCacheKey($key);
        return $this->getCacheService()->forget($cacheKey);
    }
    
    /**
     * Cache model collection
     */
    public static function cacheCollection(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $cacheService = app(CacheService::class);
        $modelClass = get_called_class();
        $cacheKey = class_basename($modelClass) . ":collection:{$key}";
        
        return $cacheService->cacheModelQuery(
            $cacheKey,
            $callback,
            $ttl ?? 3600
        );
    }
    
    /**
     * Cache model statistics
     */
    public static function cacheStatistics(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $cacheService = app(CacheService::class);
        $modelClass = get_called_class();
        $cacheKey = class_basename($modelClass) . ":stats:{$key}";
        
        return $cacheService->cacheStatistics(
            $cacheKey,
            $callback,
            $ttl ?? 86400 // 24 hours for statistics
        );
    }
    
    /**
     * Get cached model by key
     */
    public static function getCached(mixed $key, ?int $ttl = null): ?static
    {
        $cacheService = app(CacheService::class);
        $modelClass = get_called_class();
        $cacheKey = class_basename($modelClass) . ":model:{$key}";
        
        return $cacheService->cacheModelQuery(
            $cacheKey,
            function() use ($key) {
                return static::find($key);
            },
            $ttl ?? 3600
        );
    }
    
    /**
     * Cache paginated results
     */
    public static function cachePaginated(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $cacheService = app(CacheService::class);
        $modelClass = get_called_class();
        $cacheKey = class_basename($modelClass) . ":paginated:{$key}";
        
        return $cacheService->cacheQuery(
            $cacheKey,
            $callback,
            $ttl ?? 1800 // 30 minutes for paginated results
        );
    }
    
    /**
     * Cache search results
     */
    public static function cacheSearch(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $cacheService = app(CacheService::class);
        $modelClass = get_called_class();
        $cacheKey = class_basename($modelClass) . ":search:{$key}";
        
        return $cacheService->cacheQuery(
            $cacheKey,
            $callback,
            $ttl ?? 900 // 15 minutes for search results
        );
    }
    
    /**
     * Cache filtered results
     */
    public static function cacheFiltered(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $cacheService = app(CacheService::class);
        $modelClass = get_called_class();
        $cacheKey = class_basename($modelClass) . ":filtered:{$key}";
        
        return $cacheService->cacheQuery(
            $cacheKey,
            $callback,
            $ttl ?? 1800 // 30 minutes for filtered results
        );
    }
    
    /**
     * Cache related models
     */
    public function cacheRelated(string $relation, string $key, callable $callback, ?int $ttl = null): mixed
    {
        $cacheKey = $this->getCacheKey("related:{$relation}:{$key}");
        
        return $this->getCacheService()->cacheModelQuery(
            $cacheKey,
            $callback,
            $ttl ?? $this->getCacheTtl()
        );
    }
    
    /**
     * Cache model summary
     */
    public function cacheSummary(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $cacheKey = $this->getCacheKey("summary:{$key}");
        
        return $this->getCacheService()->cacheModelQuery(
            $cacheKey,
            $callback,
            $ttl ?? $this->getCacheTtl()
        );
    }
    
    /**
     * Cache model validation result
     */
    public function cacheValidation(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $cacheKey = $this->getCacheKey("validation:{$key}");
        
        return $this->getCacheService()->cacheModelQuery(
            $cacheKey,
            $callback,
            $ttl ?? 300 // 5 minutes for validation results
        );
    }
    
    /**
     * Warm up model cache
     */
    public static function warmUpCache(): array
    {
        $warmed = [];
        $modelClass = get_called_class();
        
        // Cache total count
        $warmed['total_count'] = static::cacheStatistics('total_count', function() {
            return static::count();
        });
        
        // Cache recent records
        $warmed['recent'] = static::cacheCollection('recent', function() {
            return static::latest()->take(10)->get();
        });
        
        // Cache active records (if applicable)
        if (method_exists($modelClass, 'active')) {
            $warmed['active_count'] = static::cacheStatistics('active_count', function() {
                return static::active()->count();
            });
        }
        
        return $warmed;
    }
    
    /**
     * Get cache statistics for this model
     */
    public static function getCacheStats(): array
    {
        $modelClass = get_called_class();
        $cacheService = app(CacheService::class);
        
        return [
            'model' => class_basename($modelClass),
            'cache_enabled' => config('cache.enabled', true),
            'default_ttl' => (new static())->cacheTtl,
            'cache_service' => get_class($cacheService),
        ];
    }
    
    /**
     * Clear all cache for this model
     */
    public static function clearModelCache(): bool
    {
        $cacheService = app(CacheService::class);
        $modelClass = get_called_class();
        
        return $cacheService->flushTag('model');
    }
    
    /**
     * Cache model list with sorting
     */
    public static function cacheSorted(string $key, string $column, string $direction = 'asc', ?int $ttl = null): mixed
    {
        $cacheService = app(CacheService::class);
        $modelClass = get_called_class();
        $cacheKey = class_basename($modelClass) . ":sorted:{$key}:{$column}:{$direction}";
        
        return $cacheService->cacheModelQuery(
            $cacheKey,
            function() use ($column, $direction) {
                return static::orderBy($column, $direction)->get();
            },
            $ttl ?? 3600
        );
    }
    
    /**
     * Cache model grouped results
     */
    public static function cacheGrouped(string $key, string $column, callable $callback, ?int $ttl = null): mixed
    {
        $cacheService = app(CacheService::class);
        $modelClass = get_called_class();
        $cacheKey = class_basename($modelClass) . ":grouped:{$key}:{$column}";
        
        return $cacheService->cacheModelQuery(
            $cacheKey,
            $callback,
            $ttl ?? 3600
        );
    }
}