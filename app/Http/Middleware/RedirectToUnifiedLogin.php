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
        // If this is a request to a Filament login page and user is not authenticated
        if (!Auth::check() && str_contains($request->path(), '/login')) {
            // Redirect to unified login
            return redirect()->route('login');
        }

        return $next($request);
    }
}