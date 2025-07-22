<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class DokterMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('filament.dokter.auth.login');
        }

        // Check if user has dokter role
        if (!$user->hasRole('dokter')) {
            abort(403, 'Akses ditolak. Hanya untuk pengguna dengan role dokter.');
        }

        return $next($request);
    }
}