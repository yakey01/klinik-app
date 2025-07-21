<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class ParamedisMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Check if user has paramedis role using hasRole method (more robust)
        try {
            if (!$user || !$user->hasRole('paramedis')) {
            // For non-paramedis users, deny access
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Akses ditolak. Hanya paramedis yang dapat mengakses panel ini.'
                ], 403);
            }

            // Redirect based on user role - with proper null safety
            if ($user && $user->role && $user->role->name) {
                switch ($user->role->name) {
                    case 'admin':
                    case 'manajer': 
                    case 'bendahara':
                        return redirect()->route('filament.admin.pages.dashboard');
                    case 'petugas':
                        return redirect()->route('filament.petugas.pages.dashboard');
                    case 'dokter':
                        return redirect('/dokter');
                    case 'non_paramedis':
                        return redirect()->route('nonparamedis.dashboard');
                    default:
                        return redirect('/');
                }
            }
            
            return redirect('/')->with('error', 'Akses ditolak. Anda tidak memiliki akses ke panel paramedis.');
            }

            return $next($request);
        } catch (\Exception $e) {
            // If there's any error checking roles, log it and deny access
            \Log::error('ParamedisMiddleware error: ' . $e->getMessage(), [
                'user_id' => $user?->id,
                'user_email' => $user?->email,
                'error' => $e->getTraceAsString()
            ]);
            
            return redirect('/')->with('error', 'Terjadi kesalahan sistem. Silakan coba lagi.');
        }
    }
}
