<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Pasien;
use App\Models\Tindakan;
use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use App\Models\Jaspel;
use Illuminate\Http\Request;

class ManagerDashboardController extends Controller
{
    public function __construct()
    {
        // Middleware now handled in routes
    }

    public function index()
    {
        $stats = [
            'patients' => Pasien::count(),
            'procedures' => Tindakan::count(),
            'total_income' => Pendapatan::sum('jumlah'),
            'total_expenses' => Pengeluaran::sum('jumlah'),
            'pending_approvals' => Pendapatan::where('status', 'pending')->count() + 
                                  Pengeluaran::where('status', 'pending')->count(),
            'monthly_jaspel' => Jaspel::whereMonth('created_at', now()->month)->sum('jumlah'),
        ];

        $monthly_income = Pendapatan::selectRaw('MONTH(created_at) as month, SUM(jumlah) as total')
            ->whereYear('created_at', now()->year)
            ->groupBy('month')
            ->get();

        $procedures_by_type = Tindakan::with('jenisTindakan')
            ->selectRaw('jenis_tindakan_id, COUNT(*) as count')
            ->groupBy('jenis_tindakan_id')
            ->orderBy('count', 'desc')
            ->take(10)
            ->get();

        return view('manager.dashboard', compact('stats', 'monthly_income', 'procedures_by_type'));
    }
}
