<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ClearStaleSessionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Clear stale sessions and cookies when accessing login pages
        if ($this->isLoginPage($request)) {
            $this->clearStaleSession($request);
        }
        
        return $next($request);
    }
    
    /**
     * Check if this is a login page request
     */
    private function isLoginPage(Request $request): bool
    {
        return $request->is('*/login') || 
               $request->routeIs('*.auth.login') ||
               str_contains($request->path(), '/login');
    }
    
    /**
     * Clear stale session data aggressively
     */
    private function clearStaleSession(Request $request): void
    {
        try {
            // Only clear if user is not authenticated to avoid disrupting active sessions
            if (!auth()->check()) {
                // Force regenerate session ID and token
                session()->invalidate();
                session()->regenerateToken();
                session()->migrate(true);
                
                // Clear any stale CSRF tokens from cookies
                if ($request->hasCookie('XSRF-TOKEN')) {
                    cookie()->queue(cookie()->forget('XSRF-TOKEN'));
                }
                
                // Start fresh session
                session()->start();
            }
        } catch (\Exception $e) {
            // Log error but don't break the request
            \Log::warning('Session cleanup failed: ' . $e->getMessage());
        }
    }
}