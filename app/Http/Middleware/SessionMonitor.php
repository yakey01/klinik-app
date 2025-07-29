<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SessionMonitor
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Pre-request session monitoring
        $this->logSessionState($request, 'before');
        
        // Check for session expiry warning (80% of lifetime)
        $this->checkSessionExpiry($request);
        
        $response = $next($request);
        
        // Post-request session monitoring
        $this->logSessionState($request, 'after');
        
        // Add session info to response headers for debugging
        if (config('app.debug')) {
            $response->headers->set('X-Session-ID', Session::getId());
            $response->headers->set('X-Session-Lifetime', config('session.lifetime'));
            $response->headers->set('X-CSRF-Token', csrf_token());
        }
        
        return $response;
    }
    
    private function logSessionState(Request $request, string $phase): void
    {
        // Only log for critical routes to avoid spam
        if (!$this->shouldLogRoute($request->path())) {
            return;
        }
        
        $sessionFile = storage_path('framework/sessions/' . Session::getId());
        $sessionAge = file_exists($sessionFile) ? time() - filemtime($sessionFile) : 0;
        $maxAge = config('session.lifetime') * 60;
        
        Log::debug("Session monitor ({$phase})", [
            'session_id' => Session::getId(),
            'route' => $request->path(),
            'method' => $request->method(),
            'has_csrf_token' => Session::has('_token'),
            'csrf_token_preview' => substr(csrf_token(), 0, 8) . '...',
            'session_age_seconds' => $sessionAge,
            'session_max_age_seconds' => $maxAge,
            'session_remaining_percent' => round((1 - $sessionAge / $maxAge) * 100, 1),
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
        ]);
    }
    
    private function checkSessionExpiry(Request $request): void
    {
        $sessionFile = storage_path('framework/sessions/' . Session::getId());
        if (!file_exists($sessionFile)) {
            return;
        }
        
        $sessionAge = time() - filemtime($sessionFile);
        $maxAge = config('session.lifetime') * 60;
        $remainingPercent = (1 - $sessionAge / $maxAge) * 100;
        
        // Warn when session has 20% or less time remaining
        if ($remainingPercent <= 20 && $remainingPercent > 0) {
            Log::warning('Session expiry warning', [
                'session_id' => Session::getId(),
                'remaining_percent' => round($remainingPercent, 1),
                'remaining_minutes' => round(($maxAge - $sessionAge) / 60, 1),
                'user_id' => auth()->id(),
                'route' => $request->path(),
                'ip' => $request->ip(),
            ]);
        }
    }
    
    private function shouldLogRoute(string $path): bool
    {
        $criticalRoutes = [
            'login',
            'unified-login',
            'admin',
            'paramedis',
            'dokter',
            'bendahara',
        ];
        
        foreach ($criticalRoutes as $route) {
            if (str_starts_with($path, $route)) {
                return true;
            }
        }
        
        return false;
    }
}