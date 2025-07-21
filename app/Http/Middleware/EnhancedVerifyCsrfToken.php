<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnhancedVerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'api/*',
        'webhooks/*',
    ];

    /**
     * Handle an incoming request.
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
            // Enhanced logging for debugging
            Log::warning('CSRF Token Mismatch', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'session_id' => $request->session()->getId(),
                'token_from_request' => $request->input('_token') ?: $request->header('X-CSRF-TOKEN'),
                'token_from_session' => $request->session()->token(),
                'referer' => $request->headers->get('referer'),
                'session_lifetime' => config('session.lifetime'),
                'session_driver' => config('session.driver'),
            ]);

            // For AJAX requests, return JSON response
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'CSRF token mismatch.',
                    'error' => 'csrf_token_mismatch',
                    'redirect' => route('login')
                ], 419);
            }

            // For regular requests, redirect with error message
            return redirect()->back()
                ->withInput($request->except('_token', 'password', 'password_confirmation'))
                ->withErrors([
                    'csrf' => 'Your session has expired. Please refresh the page and try again.',
                ]);
        }
    }

    /**
     * Determine if the request has a URI that should pass through CSRF verification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function inExceptArray($request)
    {
        foreach ($this->except as $except) {
            if ($except !== '/') {
                $except = trim($except, '/');
            }

            if ($request->fullUrlIs($except) || $request->is($except)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add the CSRF token to the response cookies.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function addCookieToResponse($request, $response)
    {
        $config = config('session');

        if ($response instanceof \Illuminate\Http\Response && $config['http_only']) {
            $response->headers->setCookie(
                $this->newCookie($request, $config)
            );
        }

        return $response;
    }

    /**
     * Create a new cookie with enhanced security settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $config
     * @return \Symfony\Component\HttpFoundation\Cookie
     */
    protected function newCookie($request, $config)
    {
        return cookie(
            'XSRF-TOKEN',
            $request->session()->token(),
            $this->availableAt(60 * $config['lifetime']),
            $config['path'],
            $config['domain'],
            $config['secure'] ?? $request->isSecure(),
            false, // Must be false for JavaScript access
            false,
            $config['same_site'] ?? 'lax'
        );
    }

    /**
     * Determine if the session and input CSRF tokens match.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function tokensMatch($request)
    {
        $token = $this->getTokenFromRequest($request);

        return is_string($request->session()->token()) &&
               is_string($token) &&
               hash_equals($request->session()->token(), $token);
    }

    /**
     * Get the CSRF token from the request with fallback methods.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function getTokenFromRequest($request)
    {
        $token = $request->input('_token') ?: $request->header('X-CSRF-TOKEN');

        if (!$token && $header = $request->header('X-XSRF-TOKEN')) {
            try {
                $token = \Illuminate\Cookie\CookieValuePrefix::remove(
                    decrypt($header, false)
                );
            } catch (\Exception $e) {
                Log::warning('Failed to decrypt XSRF-TOKEN', [
                    'header' => $header,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $token;
    }
}