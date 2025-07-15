<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiResponseHeadersMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Add API version header
        $response->headers->set('X-API-Version', '2.0');
        
        // Add request ID for tracking
        $response->headers->set('X-Request-ID', \Illuminate\Support\Str::uuid()->toString());
        
        // Add response time header
        if ($request->hasHeader('X-Request-Start')) {
            $startTime = $request->header('X-Request-Start');
            $responseTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
            $response->headers->set('X-Response-Time', round($responseTime, 2) . 'ms');
        }

        // Security headers for API
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // CORS headers for mobile apps
        if ($request->hasHeader('Origin')) {
            $allowedOrigins = config('api.allowed_origins', ['*']);
            $origin = $request->header('Origin');
            
            if (in_array('*', $allowedOrigins) || in_array($origin, $allowedOrigins)) {
                $response->headers->set('Access-Control-Allow-Origin', $origin);
                $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
                $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-Request-ID');
                $response->headers->set('Access-Control-Allow-Credentials', 'true');
                $response->headers->set('Access-Control-Max-Age', '86400'); // 24 hours
            }
        }

        return $response;
    }
}