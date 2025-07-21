<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class CustomSessionStart
{
    public function handle(Request $request, Closure $next)
    {
        // Force cookie configuration for better compatibility
        Config::set('session.domain', null);
        Config::set('session.secure', false);
        Config::set('session.http_only', true);
        Config::set('session.same_site', 'lax');
        
        // Set cookie before session starts
        ini_set('session.cookie_domain', '');
        ini_set('session.cookie_secure', '0');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Lax');
        
        return $next($request);
    }
}
