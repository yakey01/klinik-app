<?php

namespace App\Http\Controllers\NonParamedis;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Display the main non-paramedis dashboard.
     */
    public function index()
    {
        $user = Auth::user();
        
        return view('nonparamedis.dashboard', [
            'user' => $user,
            'title' => 'Dashboard Non-Paramedis'
        ]);
    }
    
    /**
     * Display the presensi page.
     */
    public function presensi()
    {
        $user = Auth::user();
        
        return view('nonparamedis.presensi', [
            'user' => $user,
            'title' => 'Presensi Non-Paramedis'
        ]);
    }
    
    /**
     * Display the jadwal page.
     */
    public function jadwal()
    {
        $user = Auth::user();
        
        return view('nonparamedis.jadwal', [
            'user' => $user,
            'title' => 'Jadwal Non-Paramedis'
        ]);
    }
}