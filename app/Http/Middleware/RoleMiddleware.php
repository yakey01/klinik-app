<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Check if user has any of the specified roles
        if (!empty($roles)) {
            $hasRole = false;
            foreach ($roles as $role) {
                if ($user->hasRole($role)) {
                    $hasRole = true;
                    break;
                }
            }

            if (!$hasRole) {
                // For mobile app routes, provide better error handling
                if ($request->expectsJson() || str_contains($request->path(), 'mobile-app')) {
                    return response()->json([
                        'error' => 'Akses ditolak',
                        'message' => 'Anda tidak memiliki akses ke halaman ini. Role yang diperlukan: ' . implode(', ', $roles),
                        'redirect_url' => $this->getRedirectUrl($user, $roles)
                    ], 403);
                }
                
                // For web routes, redirect based on user role
                $redirectUrl = $this->getRedirectUrl($user, $roles);
                if ($redirectUrl) {
                    return redirect($redirectUrl)->with('error', 'Akses ditolak. Anda tidak memiliki role yang diperlukan.');
                }
                
                abort(403, 'Unauthorized access. You do not have the required role.');
            }
        }

        return $next($request);
    }
    
    /**
     * Get appropriate redirect URL based on user's actual role
     */
    private function getRedirectUrl($user, $requiredRoles): ?string
    {
        if (!$user || !$user->role) {
            return '/login';
        }
        
        // Redirect to user's actual role dashboard
        switch ($user->role->name) {
            case 'admin':
            case 'manajer': 
            case 'bendahara':
                return '/admin';
            case 'petugas':
                return '/petugas';
            case 'dokter':
                return '/dokter';
            case 'paramedis':
                return '/paramedis';
            case 'non_paramedis':
                return route('nonparamedis.dashboard');
            default:
                return '/';
        }
    }
}