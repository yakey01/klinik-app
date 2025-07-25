<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PetugasMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        $user = Auth::user();
        
        // Check if user has petugas role
        if (!$user->hasRole('petugas')) {
            // Don't logout, just deny access and redirect to appropriate panel
            if ($user->hasRole('admin')) {
                return redirect('/admin')->with('info', 'Anda telah diarahkan ke panel admin.');
            }
            
            // Role-based redirect for unauthorized access
            if ($user->hasRole('dokter')) {
                return redirect('/dokter')->with('info', 'Anda telah diarahkan ke panel dokter.');
            } elseif ($user->hasRole('paramedis')) {
                return redirect('/paramedis')->with('info', 'Anda telah diarahkan ke panel paramedis.');
            } elseif ($user->hasRole('bendahara')) {
                return redirect('/bendahara')->with('info', 'Anda telah diarahkan ke panel bendahara.');
            } elseif ($user->hasRole('manajer')) {
                return redirect('/manajer')->with('info', 'Anda telah diarahkan ke panel manajer.');
            } elseif ($user->hasRole('non_paramedis')) {
                return redirect()->route('nonparamedis.dashboard')->with('info', 'Anda telah diarahkan ke panel non-paramedis.');
            }
            
            return redirect('/dashboard')
                ->with('error', 'Anda tidak memiliki akses ke panel petugas.');
        }

        return $next($request);
    }
}