<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimitMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $limitType = 'general_api'): Response
    {
        $limits = config("api.rate_limits.{$limitType}", [
            'requests' => 100,
            'per_minutes' => 1,
        ]);

        $key = $this->resolveRequestSignature($request, $limitType);

        if (RateLimiter::tooManyAttempts($key, $limits['requests'])) {
            return response()->json([
                'success' => false,
                'message' => 'Rate limit exceeded. Please try again later.',
                'error_code' => 'RATE_LIMIT_EXCEEDED',
                'meta' => [
                    'version' => '2.0',
                    'timestamp' => now()->toISOString(),
                    'request_id' => \Illuminate\Support\Str::uuid()->toString(),
                    'retry_after' => RateLimiter::availableIn($key),
                ]
            ], 429);
        }

        RateLimiter::hit($key, $limits['per_minutes'] * 60);

        $response = $next($request);

        // Add rate limit headers
        $response->headers->set('X-RateLimit-Limit', $limits['requests']);
        $response->headers->set('X-RateLimit-Remaining', RateLimiter::remaining($key, $limits['requests']));
        $response->headers->set('X-RateLimit-Reset', now()->addSeconds(RateLimiter::availableIn($key))->timestamp);

        return $response;
    }

    /**
     * Resolve the rate limit signature for the request.
     */
    protected function resolveRequestSignature(Request $request, string $limitType): string
    {
        $user = $request->user();
        
        if ($user) {
            return sprintf(
                'api_rate_limit:%s:%s:%s',
                $limitType,
                $user->id,
                $request->ip()
            );
        }

        return sprintf(
            'api_rate_limit:%s:guest:%s',
            $limitType,
            $request->ip()
        );
    }
}