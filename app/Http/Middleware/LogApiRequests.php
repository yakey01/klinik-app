<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequests
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Log incoming request for Jaspel-related endpoints
        if ($this->shouldLogRequest($request)) {
            Log::info('API Request - JASPEL DEBUGGING', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'path' => $request->path(),
                'user_id' => auth()->id(),
                'user_name' => auth()->user()?->name,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'headers' => $request->headers->all(),
                'params' => $request->all(),
                'timestamp' => now()->toISOString()
            ]);
        }

        $response = $next($request);

        // Log response for Jaspel-related endpoints
        if ($this->shouldLogRequest($request)) {
            $responseData = null;
            $content = $response->getContent();
            
            // Try to decode JSON response
            if ($content && is_string($content)) {
                $decoded = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $responseData = $decoded;
                }
            }

            Log::info('API Response - JASPEL DEBUGGING', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'path' => $request->path(),
                'status' => $response->getStatusCode(),
                'user_id' => auth()->id(),
                'response_data' => $responseData,
                'response_size' => strlen($content),
                'timestamp' => now()->toISOString()
            ]);
        }

        return $response;
    }

    /**
     * Determine if we should log this request
     */
    private function shouldLogRequest(Request $request): bool
    {
        $path = $request->path();
        
        // Log all requests that contain these keywords
        $keywords = [
            'jaspel',
            'dashboard',
            'paramedis',
            'dokter'
        ];

        foreach ($keywords as $keyword) {
            if (str_contains(strtolower($path), $keyword)) {
                return true;
            }
        }

        return false;
    }
}