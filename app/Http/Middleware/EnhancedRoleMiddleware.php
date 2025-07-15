<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Services\TokenService;
use App\Models\UserSession;

class EnhancedRoleMiddleware
{
    /**
     * Handle an incoming request for role-based access control
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            Log::warning('EnhancedRoleMiddleware: Unauthenticated access attempt', [
                'path' => $request->path(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            
            return $this->unauthorizedResponse($request);
        }

        $user = Auth::user();
        
        Log::info('EnhancedRoleMiddleware: Access attempt', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_role' => $user->role?->name,
            'required_roles' => $roles,
            'path' => $request->path(),
            'method' => $request->method(),
        ]);

        // Load role relationship if not loaded
        if (!$user->relationLoaded('role')) {
            $user->load('role');
        }

        // Check if user is active
        if (!$user->is_active) {
            Log::warning('EnhancedRoleMiddleware: Inactive user access attempt', [
                'user_id' => $user->id,
                'user_email' => $user->email,
            ]);
            
            return $this->forbiddenResponse($request, 'Account is inactive');
        }

        // Check role requirements
        if (!empty($roles)) {
            $hasValidRole = false;
            $userRole = $user->role?->name;
            
            // Enhanced role checking with detailed logging
            foreach ($roles as $requiredRole) {
                if ($user->hasRole($requiredRole)) {
                    $hasValidRole = true;
                    
                    Log::info('EnhancedRoleMiddleware: Role match found', [
                        'user_id' => $user->id,
                        'user_role' => $userRole,
                        'matched_role' => $requiredRole,
                        'path' => $request->path(),
                    ]);
                    break;
                }
            }

            if (!$hasValidRole) {
                Log::warning('EnhancedRoleMiddleware: Insufficient role privileges', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'user_role' => $userRole,
                    'required_roles' => $roles,
                    'path' => $request->path(),
                    'method' => $request->method(),
                ]);
                
                return $this->forbiddenResponse($request, 'Insufficient role privileges');
            }
        }

        // Role validation passed - enhance session security and check for expiration
        $this->enhanceSessionSecurity($request, $user);
        $this->checkAndRefreshSession($request, $user);

        Log::info('EnhancedRoleMiddleware: Access granted', [
            'user_id' => $user->id,
            'user_role' => $user->role?->name,
            'path' => $request->path(),
        ]);

        return $next($request);
    }

    /**
     * Check session expiration and refresh if needed
     */
    private function checkAndRefreshSession(Request $request, $user): void
    {
        try {
            // Check if user has active session
            $currentSession = UserSession::where('user_id', $user->id)
                ->where('is_active', true)
                ->latest()
                ->first();

            if ($currentSession) {
                // Extend session if about to expire
                if ($currentSession->isAboutToExpire()) {
                    $currentSession->extendExpiration();
                    
                    Log::info('Session extended due to upcoming expiration', [
                        'user_id' => $user->id,
                        'session_id' => $currentSession->session_id,
                        'new_expires_at' => $currentSession->expires_at,
                    ]);
                }

                // Update activity timestamp
                $currentSession->updateActivity();
            }

            // Check token expiration and auto-refresh if needed
            $currentToken = $user->currentAccessToken();
            if ($currentToken) {
                $tokenService = app(TokenService::class);
                
                if ($tokenService->needsRefresh($currentToken)) {
                    $refreshedToken = $tokenService->autoRefreshIfNeeded($currentToken);
                    
                    if ($refreshedToken) {
                        Log::info('Token auto-refreshed', [
                            'user_id' => $user->id,
                            'new_expires_at' => $refreshedToken['expires_at'],
                        ]);
                        
                        // Update request with new token info
                        $request->headers->set('Authorization', 'Bearer ' . $refreshedToken['access_token']);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Session refresh check failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Enhance session security with additional checks
     */
    private function enhanceSessionSecurity(Request $request, $user): void
    {
        try {
            // Check for session hijacking indicators
            $currentIp = $request->ip();
            $currentUserAgent = $request->userAgent();
            
            // Get current session data (simplified - can be enhanced)
            $sessionData = [
                'ip_address' => $currentIp,
                'user_agent' => $currentUserAgent,
                'timestamp' => now()->toISOString(),
            ];

            // Log session activity for security monitoring
            Log::info('Session security check', [
                'user_id' => $user->id,
                'ip_address' => $currentIp,
                'path' => $request->path(),
                'timestamp' => now()->toISOString(),
            ]);

            // Additional security checks can be implemented here:
            // - IP change detection
            // - Device fingerprint validation
            // - Geographic anomaly detection
            // - Rate limiting per user
            
        } catch (\Exception $e) {
            Log::error('Session security enhancement failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Return unauthorized response
     */
    private function unauthorizedResponse(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
                'error_code' => 'UNAUTHORIZED',
                'meta' => [
                    'version' => '2.0',
                    'timestamp' => now()->toISOString(),
                    'request_id' => \Illuminate\Support\Str::uuid()->toString(),
                ]
            ], 401);
        }

        return redirect()->route('login');
    }

    /**
     * Return forbidden response
     */
    private function forbiddenResponse(Request $request, string $message = 'Access denied'): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'error_code' => 'FORBIDDEN',
                'meta' => [
                    'version' => '2.0',
                    'timestamp' => now()->toISOString(),
                    'request_id' => \Illuminate\Support\Str::uuid()->toString(),
                ]
            ], 403);
        }

        abort(403, $message);
    }
}