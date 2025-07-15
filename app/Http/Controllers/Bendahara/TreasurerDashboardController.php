<?php

namespace App\Http\Controllers\Bendahara;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TreasurerDashboardController extends Controller
{
    public function index()
    {
        return view('treasurer.dashboard');
    }
}
