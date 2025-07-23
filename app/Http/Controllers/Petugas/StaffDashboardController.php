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
    
    // Enhanced method removed - using standard dashboard instead
}
