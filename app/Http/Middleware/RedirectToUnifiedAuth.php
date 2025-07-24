<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectToUnifiedAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check() && $request->expectsJson() === false) {
            // Store intended URL for redirect after login
            session(['url.intended' => $request->url()]);
            
            return redirect()->route('login')->with('info', 'Please login to access the dashboard');
        }

        return $next($request);
    }
}