<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifikatorMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect('/login');
        }

        $user = auth()->user();
        
        if (!$user->hasRole('verifikator')) {
            if ($user->hasRole('admin')) {
                return redirect('/admin')->with('info', 'Anda telah diarahkan ke panel admin.');
            }
            
            if ($user->hasRole('dokter')) {
                return redirect('/dokter')->with('info', 'Anda telah diarahkan ke panel dokter.');
            } elseif ($user->hasRole('paramedis')) {
                return redirect('/paramedis')->with('info', 'Anda telah diarahkan ke panel paramedis.');
            } elseif ($user->hasRole('petugas')) {
                return redirect('/petugas')->with('info', 'Anda telah diarahkan ke panel petugas.');
            } elseif ($user->hasRole('bendahara')) {
                return redirect('/bendahara')->with('info', 'Anda telah diarahkan ke panel bendahara.');
            } elseif ($user->hasRole('manajer')) {
                return redirect('/manajer')->with('info', 'Anda telah diarahkan ke panel manajer.');
            }
            
            return redirect('/dashboard')
                ->with('error', 'Anda tidak memiliki akses ke panel verifikator.');
        }

        return $next($request);
    }
}
