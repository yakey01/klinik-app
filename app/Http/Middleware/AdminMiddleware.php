<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        
        // Check if user has admin role
        if (!$user->hasRole('admin')) {
            // Don't logout, just deny access and redirect to appropriate panel
            if ($user->hasRole('petugas')) {
                return redirect('/petugas')->with('info', 'Anda telah diarahkan ke panel petugas.');
            }
            
            return redirect('/dashboard')
                ->with('error', 'Anda tidak memiliki akses ke panel admin.');
        }

        return $next($request);
    }
}