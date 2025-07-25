<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectToUnifiedLogin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If user is not authenticated and trying to access a Filament panel login page
        if (!Auth::check() && $this->isFilamentLoginRoute($request)) {
            return redirect('/login')->with('intended', $request->fullUrl());
        }

        // Handle logout redirects - if user is trying to access authenticated routes but is logged out
        if (!Auth::check() && $this->isAuthenticatedRoute($request)) {
            return redirect('/login')->with('intended', $request->fullUrl());
        }

        return $next($request);
    }

    /**
     * Check if the current route is a Filament panel login route
     */
    private function isFilamentLoginRoute(Request $request): bool
    {
        $path = $request->path();
        
        return in_array($path, [
            'admin/login',
            'petugas/login', 
            'bendahara/login',
            'manajer/login',
            'paramedis/login',
            'dokter/login'
        ]) || preg_match('#^(admin|petugas|bendahara|manajer|paramedis|dokter)/auth/login$#', $path);
    }

    /**
     * Check if the current route requires authentication
     */
    private function isAuthenticatedRoute(Request $request): bool
    {
        $path = $request->path();
        
        // Routes that require authentication but don't have login redirects
        return preg_match('#^(admin|petugas|bendahara|manajer|paramedis|dokter)(/.*)?$#', $path) &&
               !str_starts_with($path, 'login') &&
               !str_starts_with($path, 'register');
    }
}