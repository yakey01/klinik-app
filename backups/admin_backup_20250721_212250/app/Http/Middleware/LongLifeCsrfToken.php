<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LongLifeCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'livewire/update',
        'livewire/upload-file',
        'livewire/message/*',
        'api/*',
        'webhooks/*',
    ];

    /**
     * Handle an incoming request with extended CSRF token lifetime.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     * @throws \Illuminate\Session\TokenMismatchException
     */
    public function handle($request, \Closure $next): Response
    {
        try {
            return parent::handle($request, $next);
        } catch (TokenMismatchException $e) {
            // Enhanced logging for long-life CSRF debugging
            Log::warning('Long-Life CSRF Token Mismatch', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'session_id' => $request->session()->getId(),
                'session_lifetime' => config('session.lifetime'),
                'token_from_request' => $request->input('_token') ?: $request->header('X-CSRF-TOKEN'),
                'token_from_session' => $request->session()->token(),
                'session_last_activity' => $request->session()->get('_token_created_at'),
                'current_time' => now()->toDateTimeString(),
                'referer' => $request->headers->get('referer'),
                'user_id' => $request->user()?->id,
            ]);

            // Try to regenerate token and allow request if session is still valid
            if ($this->sessionIsStillValid($request)) {
                Log::info('Regenerating CSRF token for valid session', [
                    'session_id' => $request->session()->getId(),
                    'user_id' => $request->user()?->id,
                ]);
                
                $request->session()->regenerateToken();
                
                // For AJAX requests, return new token
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'CSRF token refreshed.',
                        'new_token' => csrf_token(),
                        'action' => 'token_refreshed'
                    ], 200);
                }
                
                // For regular requests, redirect back with new token
                return redirect()->back()
                    ->withInput($request->except('_token', 'password', 'password_confirmation'))
                    ->with('info', 'Session refreshed. Please try again.');
            }

            // For AJAX requests, return JSON response
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'CSRF token mismatch. Please refresh the page.',
                    'error' => 'csrf_token_mismatch',
                    'redirect' => route('login'),
                    'new_token' => csrf_token()
                ], 419);
            }

            // For regular requests, redirect with error message
            return redirect()->back()
                ->withInput($request->except('_token', 'password', 'password_confirmation'))
                ->withErrors([
                    'csrf' => 'Your session has expired. The page has been refreshed with a new security token.',
                ])
                ->with('warning', 'Security token refreshed. Please try your action again.');
        }
    }

    /**
     * Check if the session is still valid based on activity and user authentication.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function sessionIsStillValid($request)
    {
        // Check if user is still authenticated
        if (!$request->user()) {
            return false;
        }

        // Check session activity timestamp
        $lastActivity = $request->session()->get('last_activity', 0);
        $sessionLifetime = config('session.lifetime') * 60; // Convert minutes to seconds
        $currentTime = time();

        // Allow regeneration if session is within extended lifetime (24 hours)
        return ($currentTime - $lastActivity) < $sessionLifetime;
    }

    /**
     * Add the CSRF token to the response with extended lifetime.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function addCookieToResponse($request, $response)
    {
        $config = config('session');

        if ($response instanceof \Illuminate\Http\Response && $config['http_only']) {
            // Create CSRF cookie with same lifetime as session
            $response->headers->setCookie(
                $this->newCookie($request, $config)
            );
        }

        return $response;
    }

    /**
     * Create a new CSRF cookie with extended lifetime.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $config
     * @return \Symfony\Component\HttpFoundation\Cookie
     */
    protected function newCookie($request, $config)
    {
        // Set cookie lifetime to match session lifetime (24 hours)
        $lifetime = $this->availableAt(60 * $config['lifetime']);
        
        return cookie(
            'XSRF-TOKEN',
            $request->session()->token(),
            $lifetime,
            $config['path'],
            $config['domain'],
            $config['secure'] ?? $request->isSecure(),
            false, // Must be false for JavaScript access
            false,
            $config['same_site'] ?? 'lax'
        );
    }

    /**
     * Determine if the session and input CSRF tokens match with fallback logic.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function tokensMatch($request)
    {
        $token = $this->getTokenFromRequest($request);
        $sessionToken = $request->session()->token();

        // Basic token comparison
        if (is_string($sessionToken) && is_string($token) && hash_equals($sessionToken, $token)) {
            // Update last activity timestamp
            $request->session()->put('last_activity', time());
            return true;
        }

        // Fallback: Check if this is a valid user session with recent activity
        if ($this->sessionIsStillValid($request)) {
            Log::info('CSRF token mismatch but session is valid, allowing with regeneration');
            return true;
        }

        return false;
    }

    /**
     * Get the CSRF token from the request with multiple fallback methods.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function getTokenFromRequest($request)
    {
        // Try standard Laravel token sources
        $token = $request->input('_token') ?: $request->header('X-CSRF-TOKEN');

        // Try XSRF-TOKEN header (for SPA/AJAX)
        if (!$token && $header = $request->header('X-XSRF-TOKEN')) {
            try {
                $token = \Illuminate\Cookie\CookieValuePrefix::remove(
                    decrypt($header, false)
                );
            } catch (\Exception $e) {
                Log::debug('Failed to decrypt XSRF-TOKEN', [
                    'header' => substr($header, 0, 20) . '...',
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Try Livewire token sources
        if (!$token) {
            $token = $request->header('X-Livewire-Token') ?: 
                     $request->input('livewire_token');
        }

        return $token;
    }
}