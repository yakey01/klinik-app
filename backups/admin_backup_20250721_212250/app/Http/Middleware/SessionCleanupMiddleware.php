<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class SessionCleanupMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Clean up expired sessions before processing request
        $this->cleanupExpiredSessions();
        
        $response = $next($request);
        
        // If session is about to expire, refresh it for admin users
        if ($request->is('admin*') && auth()->check()) {
            $this->refreshSessionIfNeeded();
        }
        
        return $response;
    }
    
    /**
     * Clean up expired sessions from database
     */
    private function cleanupExpiredSessions(): void
    {
        try {
            // Only run cleanup 1% of the time to avoid performance issues
            if (random_int(1, 100) === 1) {
                $lifetime = config('session.lifetime', 1440);
                $expiredTime = now()->subMinutes($lifetime);
                
                DB::table('sessions')
                    ->where('last_activity', '<', $expiredTime->timestamp)
                    ->delete();
            }
        } catch (\Exception $e) {
            // Log error but don't break the request
            \Log::warning('Session cleanup failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Refresh session if it's about to expire
     */
    private function refreshSessionIfNeeded(): void
    {
        try {
            $session = session();
            $lastActivity = $session->get('_token_timestamp', time());
            $lifetime = config('session.lifetime', 1440) * 60; // Convert to seconds
            
            // If session is older than 80% of lifetime, regenerate token
            if ((time() - $lastActivity) > ($lifetime * 0.8)) {
                $session->regenerateToken();
                $session->put('_token_timestamp', time());
            }
        } catch (\Exception $e) {
            // Log error but don't break the request
            \Log::warning('Session refresh failed: ' . $e->getMessage());
        }
    }
}