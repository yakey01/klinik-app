<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $user = Auth::user();
        
        // Redirect based on user's primary role
        if ($user->hasRole('admin')) {
            return redirect('/admin'); // Redirect to Filament admin panel
        } elseif ($user->hasRole('manajer')) {
            return redirect('/manajer');
        } elseif ($user->hasRole('bendahara')) {
            return redirect('/bendahara');
        } elseif ($user->hasRole('petugas')) {
            return redirect('/petugas'); // Redirect to Filament petugas panel
        } elseif ($user->hasRole('dokter')) {
            return redirect('/dokter'); // Redirect to Filament doctor panel
        } elseif ($user->hasRole('paramedis')) {
            return redirect()->route('paramedis.mobile-app'); // Redirect directly to mobile app
        } elseif ($user->hasRole('non_paramedis')) {
            return redirect()->route('nonparamedis.app');
        } elseif ($user->hasRole('dokter_gigi')) {
            return redirect()->route('dokter-gigi.dashboard');
        }
        
        // Default dashboard if no specific role match
        return view('dashboard');
    }
}