<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimiter
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $limit = '100:1'): Response
    {
        // Parse limit (requests:minutes)
        [$maxAttempts, $decayMinutes] = explode(':', $limit);
        $maxAttempts = (int) $maxAttempts;
        $decayMinutes = (int) $decayMinutes;

        // Generate unique key for rate limiting
        $key = $this->resolveRequestSignature($request);

        // Check if rate limit is exceeded
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return $this->buildResponse($key, $maxAttempts, $decayMinutes);
        }

        // Increment the number of attempts
        RateLimiter::hit($key, $decayMinutes * 60);

        $response = $next($request);

        // Add rate limit headers to response
        return $this->addHeaders(
            $response,
            $maxAttempts,
            RateLimiter::attempts($key),
            RateLimiter::availableIn($key)
        );
    }

    /**
     * Resolve request signature for rate limiting
     */
    protected function resolveRequestSignature(Request $request): string
    {
        $user = Auth::user();
        
        if ($user) {
            // Rate limit by user ID and role
            return 'api:user:' . $user->id . ':' . ($user->role?->name ?? 'unknown');
        }

        // Rate limit by IP address for unauthenticated requests
        return 'api:ip:' . $request->ip();
    }

    /**
     * Create a 'too many attempts' response
     */
    protected function buildResponse(string $key, int $maxAttempts, int $decayMinutes): Response
    {
        $retryAfter = RateLimiter::availableIn($key);
        
        $response = response()->json([
            'success' => false,
            'message' => 'Too many requests. Please try again later.',
            'error_code' => 'RATE_LIMIT_EXCEEDED',
            'retry_after' => $retryAfter,
            'limit' => $maxAttempts,
            'window' => $decayMinutes . ' minutes',
            'timestamp' => now()->toISOString(),
        ], 429);

        return $this->addHeaders($response, $maxAttempts, $maxAttempts, $retryAfter);
    }

    /**
     * Add rate limit headers to response
     */
    protected function addHeaders(Response $response, int $maxAttempts, int $attempts, int $retryAfter): Response
    {
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => max(0, $maxAttempts - $attempts),
            'X-RateLimit-Reset' => now()->addSeconds($retryAfter)->timestamp,
            'X-RateLimit-Retry-After' => $retryAfter,
        ]);

        return $response;
    }
}