<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RefreshCsrfToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // DISABLED: This middleware was causing refresh loops on admin panel
        // Only perform minimal session management for login pages
        if ($request->is('*/login') && $request->isMethod('GET')) {
            // Only regenerate token for GET requests to login pages, not sessions
            session()->regenerateToken();
        }
        
        $response = $next($request);

        // Minimal CSRF token injection - only for non-admin routes to prevent loops
        if (\!$request->is('admin*') && ($request->is('paramedis*') || $request->is('bendahara*') || $request->is('manajer*') || $request->is('petugas*') || $request->is('dokter*') || $request->is('dokter-gigi*'))) {
            $token = csrf_token();
            
            // Add CSRF token to response headers for AJAX requests only
            if ($request->expectsJson()) {
                $response->headers->set('X-CSRF-TOKEN', $token);
            }
        }

        return $response;
    }
}
