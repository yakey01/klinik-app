<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\CacheService;
use App\Services\LoggingService;

class CacheResponseMiddleware
{
    private CacheService $cacheService;
    private LoggingService $loggingService;
    
    // Default cache TTL in seconds
    private const DEFAULT_TTL = 300; // 5 minutes
    
    // Cache control patterns
    private const CACHE_PATTERNS = [
        'api' => [
            'pattern' => '/^\/api\//',
            'ttl' => 300, // 5 minutes
            'vary' => ['User-Agent', 'Accept'],
            'methods' => ['GET'],
        ],
        'dashboard' => [
            'pattern' => '/dashboard/',
            'ttl' => 900, // 15 minutes
            'vary' => ['User-Agent'],
            'methods' => ['GET'],
        ],
        'reports' => [
            'pattern' => '/reports/',
            'ttl' => 1800, // 30 minutes
            'vary' => ['User-Agent'],
            'methods' => ['GET'],
        ],
        'static' => [
            'pattern' => '/\.(css|js|jpg|jpeg|png|gif|svg|ico|woff|woff2|ttf|eot)$/',
            'ttl' => 86400, // 24 hours
            'vary' => [],
            'methods' => ['GET', 'HEAD'],
        ],
    ];
    
    public function __construct(CacheService $cacheService, LoggingService $loggingService)
    {
        $this->cacheService = $cacheService;
        $this->loggingService = $loggingService;
    }
    
    /**
     * Handle an incoming request
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip caching for non-GET requests unless specifically configured
        if (!$this->shouldCache($request)) {
            return $next($request);
        }
        
        $startTime = microtime(true);
        $cacheKey = $this->getCacheKey($request);
        $config = $this->getCacheConfig($request);
        
        // Try to get cached response
        $cachedResponse = $this->getCachedResponse($cacheKey, $config);
        
        if ($cachedResponse) {
            $duration = microtime(true) - $startTime;
            
            $this->loggingService->logPerformance(
                'response_cache_hit',
                $duration,
                [
                    'cache_key' => $cacheKey,
                    'url' => $request->url(),
                    'method' => $request->method(),
                    'user_id' => auth()->id(),
                ],
                'info'
            );
            
            return $this->addCacheHeaders($cachedResponse, true);
        }
        
        // Process request
        $response = $next($request);
        
        // Cache the response if it's successful
        if ($this->shouldCacheResponse($response)) {
            $this->cacheResponse($cacheKey, $response, $config);
        }
        
        $duration = microtime(true) - $startTime;
        
        $this->loggingService->logPerformance(
            'response_cache_miss',
            $duration,
            [
                'cache_key' => $cacheKey,
                'url' => $request->url(),
                'method' => $request->method(),
                'status_code' => $response->getStatusCode(),
                'user_id' => auth()->id(),
            ],
            $duration > 1.0 ? 'warning' : 'info'
        );
        
        return $this->addCacheHeaders($response, false);
    }
    
    /**
     * Check if request should be cached
     */
    private function shouldCache(Request $request): bool
    {
        // Skip if caching is disabled
        if (!config('cache.enabled', true)) {
            return false;
        }
        
        // Skip if user is authenticated and no-cache header is present
        if (auth()->check() && $request->header('Cache-Control') === 'no-cache') {
            return false;
        }
        
        // Skip for requests with query parameters that indicate dynamic content
        $skipParams = ['_token', 'csrf_token', 'timestamp', 'nocache'];
        foreach ($skipParams as $param) {
            if ($request->has($param)) {
                return false;
            }
        }
        
        // Check against cache patterns
        foreach (self::CACHE_PATTERNS as $config) {
            if (preg_match($config['pattern'], $request->path()) && 
                in_array($request->method(), $config['methods'])) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get cache configuration for request
     */
    private function getCacheConfig(Request $request): array
    {
        foreach (self::CACHE_PATTERNS as $name => $config) {
            if (preg_match($config['pattern'], $request->path()) && 
                in_array($request->method(), $config['methods'])) {
                return array_merge($config, ['name' => $name]);
            }
        }
        
        return [
            'pattern' => '/./',
            'ttl' => self::DEFAULT_TTL,
            'vary' => ['User-Agent'],
            'methods' => ['GET'],
            'name' => 'default',
        ];
    }
    
    /**
     * Generate cache key for request
     */
    private function getCacheKey(Request $request): string
    {
        $key = 'response:' . $request->method() . ':' . $request->path();
        
        // Include query parameters (sorted for consistency)
        $params = $request->query();
        if (!empty($params)) {
            ksort($params);
            $key .= ':' . http_build_query($params);
        }
        
        // Include user context for authenticated requests
        if (auth()->check()) {
            $key .= ':user:' . auth()->id();
            
            // Include user role for role-based caching
            if (auth()->user()->role) {
                $key .= ':role:' . auth()->user()->role;
            }
        }
        
        // Include vary headers
        $config = $this->getCacheConfig($request);
        foreach ($config['vary'] as $header) {
            if ($request->hasHeader($header)) {
                $key .= ':' . $header . ':' . md5($request->header($header));
            }
        }
        
        return md5($key);
    }
    
    /**
     * Get cached response
     */
    private function getCachedResponse(string $cacheKey, array $config): ?Response
    {
        try {
            return $this->cacheService->cacheApiResponse(
                $cacheKey,
                function() {
                    return null; // Return null if not cached
                },
                $config['ttl']
            );
            
        } catch (\Exception $e) {
            $this->loggingService->logError(
                'Response cache retrieval failed',
                $e,
                ['cache_key' => $cacheKey],
                'error'
            );
            
            return null;
        }
    }
    
    /**
     * Cache the response
     */
    private function cacheResponse(string $cacheKey, Response $response, array $config): void
    {
        try {
            $this->cacheService->cacheApiResponse(
                $cacheKey,
                function() use ($response) {
                    return $response;
                },
                $config['ttl']
            );
            
        } catch (\Exception $e) {
            $this->loggingService->logError(
                'Response caching failed',
                $e,
                [
                    'cache_key' => $cacheKey,
                    'status_code' => $response->getStatusCode(),
                ],
                'error'
            );
        }
    }
    
    /**
     * Check if response should be cached
     */
    private function shouldCacheResponse(Response $response): bool
    {
        // Only cache successful responses
        if ($response->getStatusCode() >= 400) {
            return false;
        }
        
        // Don't cache responses with no-cache header
        if ($response->headers->get('Cache-Control') === 'no-cache') {
            return false;
        }
        
        // Don't cache responses with Set-Cookie header
        if ($response->headers->has('Set-Cookie')) {
            return false;
        }
        
        // Don't cache empty responses
        if (empty($response->getContent())) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Add cache headers to response
     */
    private function addCacheHeaders(Response $response, bool $fromCache): Response
    {
        if ($fromCache) {
            $response->headers->set('X-Cache', 'HIT');
            $response->headers->set('X-Cache-TTL', config('cache.ttl', self::DEFAULT_TTL));
        } else {
            $response->headers->set('X-Cache', 'MISS');
        }
        
        // Add cache control headers
        $maxAge = self::DEFAULT_TTL;
        $response->headers->set('Cache-Control', "public, max-age={$maxAge}");
        $response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s T'));
        $response->headers->set('ETag', md5($response->getContent()));
        
        return $response;
    }
    
    /**
     * Clear cache for specific patterns
     */
    public function clearCache(string $pattern = null): bool
    {
        try {
            if ($pattern) {
                // Clear specific pattern
                $this->cacheService->flushTag($pattern);
            } else {
                // Clear all response cache
                $this->cacheService->flushTag('api');
            }
            
            $this->loggingService->logActivity(
                'response_cache_cleared',
                null,
                ['pattern' => $pattern],
                'Response cache cleared: ' . ($pattern ?: 'all')
            );
            
            return true;
            
        } catch (\Exception $e) {
            $this->loggingService->logError(
                'Response cache clearing failed',
                $e,
                ['pattern' => $pattern],
                'error'
            );
            
            return false;
        }
    }
    
    /**
     * Warm up response cache
     */
    public function warmUpCache(array $urls): array
    {
        $warmed = [];
        
        foreach ($urls as $url) {
            try {
                $request = Request::create($url, 'GET');
                $cacheKey = $this->getCacheKey($request);
                $config = $this->getCacheConfig($request);
                
                // Pre-cache the response
                $warmed[$url] = $this->cacheService->cacheApiResponse(
                    $cacheKey,
                    function() use ($url) {
                        // This would typically make an internal request
                        // For now, we'll return a placeholder
                        return response()->json(['url' => $url, 'warmed' => true]);
                    },
                    $config['ttl']
                );
                
            } catch (\Exception $e) {
                $warmed[$url] = ['error' => $e->getMessage()];
            }
        }
        
        return $warmed;
    }
    
    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        return [
            'patterns' => self::CACHE_PATTERNS,
            'default_ttl' => self::DEFAULT_TTL,
            'cache_service' => get_class($this->cacheService),
            'cache_enabled' => config('cache.enabled', true),
        ];
    }
}