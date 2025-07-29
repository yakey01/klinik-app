<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PreventPageExpired
{
    public function handle(Request $request, Closure $next)
    {
        // Auto-refresh CSRF token if expired
        if ($request->isMethod('POST') && !$request->ajax()) {
            if (!$request->session()->has('_token') || 
                $request->input('_token') !== $request->session()->token()) {
                // Regenerate token
                $request->session()->regenerateToken();
                
                // For form submissions, merge the new token
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'CSRF token refreshed',
                        'token' => csrf_token()
                    ], 200);
                }
            }
        }
        
        // Add headers to prevent caching of sensitive pages
        $response = $next($request);
        
        if (method_exists($response, 'header')) {
            $response->header('Cache-Control', 'no-cache, no-store, must-revalidate');
            $response->header('Pragma', 'no-cache');
            $response->header('Expires', '0');
        }
        
        return $response;
    }
}