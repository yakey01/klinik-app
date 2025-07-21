<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Pasien;
use App\Models\Tindakan;
use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{

    public function index()
    {
        $stats = [
            'users' => User::count(),
            'roles' => Role::count(),
            'patients' => Pasien::count(),
            'procedures' => Tindakan::count(),
            'total_income' => Pendapatan::sum('jumlah'),
            'total_expenses' => Pengeluaran::sum('jumlah'),
            'pending_approvals' => Pendapatan::where('status', 'pending')->count() + 
                                  Pengeluaran::where('status', 'pending')->count(),
        ];

        $recent_users = User::with('role')->latest()->take(5)->get();
        $recent_procedures = Tindakan::with(['pasien', 'jenisTindakan'])->latest()->take(5)->get();

        return view('admin.dashboard', compact('stats', 'recent_users', 'recent_procedures'));
    }
}