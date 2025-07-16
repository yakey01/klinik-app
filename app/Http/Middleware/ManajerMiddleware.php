<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ManajerMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Check if user is authenticated
        if (!$user) {
            Log::warning('ManajerMiddleware: Unauthorized access attempt', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
            ]);
            
            return redirect()->route('filament.manajer.auth.login');
        }

        // Check if user has manajer role
        if (!$user->hasRole('manajer')) {
            Log::warning('ManajerMiddleware: Access denied - insufficient permissions', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'roles' => $user->getRoleNames()->toArray(),
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
            ]);

            abort(403, 'Akses ditolak. Anda tidak memiliki izin sebagai manajer.');
        }

        // Check if user account is active
        if (method_exists($user, 'isActive') && !$user->isActive()) {
            Log::warning('ManajerMiddleware: Access denied - inactive account', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'ip' => $request->ip(),
            ]);

            Auth::logout();
            return redirect()->route('filament.manajer.auth.login')
                ->with('error', 'Akun Anda tidak aktif. Hubungi administrator.');
        }

        // Additional security checks for sensitive operations
        if ($this->isSensitiveOperation($request)) {
            // Log sensitive operations
            Log::info('ManajerMiddleware: Sensitive operation accessed', [
                'user_id' => $user->id,
                'operation' => $request->route()?->getName(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
                'user_agent' => $request->userAgent(),
            ]);

            // Check session age for sensitive operations
            $sessionAge = time() - $request->session()->get('login_time', 0);
            $maxSessionAge = config('session.sensitive_operation_timeout', 3600); // 1 hour

            if ($sessionAge > $maxSessionAge) {
                Log::warning('ManajerMiddleware: Session expired for sensitive operation', [
                    'user_id' => $user->id,
                    'session_age' => $sessionAge,
                    'max_age' => $maxSessionAge,
                ]);

                $request->session()->flush();
                return redirect()->route('filament.manajer.auth.login')
                    ->with('error', 'Sesi Anda telah berakhir untuk operasi sensitif. Silakan login kembali.');
            }

            // Rate limiting for executive operations
            if ($this->isExecutiveOperation($request)) {
                $rateLimitKey = 'executive_operations:' . $user->id;
                $attempts = cache()->increment($rateLimitKey);
                
                if ($attempts === 1) {
                    cache()->put($rateLimitKey, 1, now()->addMinutes(5));
                }

                $maxAttempts = config('security.executive_operation_limit', 15);
                if ($attempts > $maxAttempts) {
                    Log::warning('ManajerMiddleware: Rate limit exceeded for executive operations', [
                        'user_id' => $user->id,
                        'attempts' => $attempts,
                        'max_attempts' => $maxAttempts,
                    ]);

                    abort(429, 'Terlalu banyak operasi eksekutif. Coba lagi dalam beberapa menit.');
                }
            }
        }

        // Update last activity timestamp
        $user->update(['last_activity' => now()]);

        // Log successful access for audit trail
        if (config('app.debug') || $this->shouldLogAccess($request)) {
            Log::debug('ManajerMiddleware: Access granted', [
                'user_id' => $user->id,
                'route' => $request->route()?->getName(),
                'ip' => $request->ip(),
            ]);
        }

        return $next($request);
    }

    /**
     * Check if the request is for a sensitive operation
     */
    private function isSensitiveOperation(Request $request): bool
    {
        $sensitiveRoutes = [
            'filament.manajer.resources.employee-performance.bulk-action',
            'filament.manajer.resources.leave-approval.approve',
            'filament.manajer.resources.leave-approval.reject',
            'filament.manajer.resources.financial-oversight.bulk-action',
            'filament.manajer.resources.approval-workflow.approve',
        ];

        $routeName = $request->route()?->getName();
        
        return in_array($routeName, $sensitiveRoutes) ||
               str_contains($routeName ?? '', 'approve') ||
               str_contains($routeName ?? '', 'reject') ||
               str_contains($routeName ?? '', 'export') ||
               str_contains($routeName ?? '', 'bulk') ||
               str_contains($routeName ?? '', 'strategic') ||
               str_contains($routeName ?? '', 'analytics');
    }

    /**
     * Check if the request is for an executive operation
     */
    private function isExecutiveOperation(Request $request): bool
    {
        $routeName = $request->route()?->getName() ?? '';
        
        return str_contains($routeName, 'strategic') ||
               str_contains($routeName, 'executive') ||
               str_contains($routeName, 'approve') ||
               str_contains($routeName, 'analytics') ||
               (is_array($request->input('records')) && count($request->input('records')) > 5);
    }

    /**
     * Determine if access should be logged
     */
    private function shouldLogAccess(Request $request): bool
    {
        // Log access to critical manager resources
        $criticalPaths = [
            'manajer/strategic',
            'manajer/analytics',
            'manajer/approval',
            'manajer/financial',
            'manajer/export',
        ];

        $path = $request->path();
        
        foreach ($criticalPaths as $criticalPath) {
            if (str_contains($path, $criticalPath)) {
                return true;
            }
        }

        return false;
    }
}