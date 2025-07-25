<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();
                
                // Role-based redirect for authenticated users
                if ($user->hasRole('admin')) {
                    return redirect('/admin');
                } elseif ($user->hasRole('dokter')) {
                    return redirect('/dokter');
                } elseif ($user->hasRole('paramedis')) {
                    return redirect('/paramedis');
                } elseif ($user->hasRole('petugas')) {
                    return redirect('/petugas');
                } elseif ($user->hasRole('manajer')) {
                    return redirect('/manajer');
                } elseif ($user->hasRole('bendahara')) {
                    return redirect('/bendahara');
                } elseif ($user->hasRole('non_paramedis')) {
                    return redirect()->route('nonparamedis.dashboard');
                }
                
                // Default fallback
                return redirect('/dashboard');
            }
        }

        return $next($request);
    }
}