<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StaffDashboardController extends Controller
{
    public function index()
    {
        return view('staff.dashboard');
    }
    
    /**
     * Enhanced Petugas Dashboard with TailwindCSS and ApexCharts
     * Completely isolated from Filament components
     */
    public function enhanced()
    {
        // Check if user has petugas role
        if (!auth()->user()->hasRole('petugas')) {
            abort(403, 'Access denied. Petugas role required.');
        }
        
        return view('petugas.dashboard');
    }
}
