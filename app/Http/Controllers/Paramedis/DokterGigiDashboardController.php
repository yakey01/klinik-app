<?php

namespace App\Http\Controllers\Paramedis;

use App\Http\Controllers\Controller;
use App\Models\Jaspel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DokterGigiDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Check if user has paramedis_gigi role
        if (!$user->hasRole('paramedis_gigi')) {
            abort(403, 'Unauthorized access');
        }
        
        // Get current month's jaspel data for the logged-in user
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        $jaspelData = Jaspel::where('user_id', $user->id)
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->orderBy('tanggal', 'desc')
            ->get();
        
        // Calculate statistics
        $totalJaspel = $jaspelData->sum('nominal');
        $totalTransactions = $jaspelData->count();
        $averagePerTransaction = $totalTransactions > 0 ? $totalJaspel / $totalTransactions : 0;
        
        // Get recent transactions (last 5)
        $recentJaspel = $jaspelData->take(5);
        
        return view('paramedis-gigi.dashboard', compact(
            'user',
            'jaspelData',
            'totalJaspel',
            'totalTransactions',
            'averagePerTransaction',
            'recentJaspel',
            'startOfMonth'
        ));
    }
    
    public function jaspel(Request $request)
    {
        $user = Auth::user();
        
        // Check if user has paramedis_gigi role
        if (!$user->hasRole('paramedis_gigi')) {
            abort(403, 'Unauthorized access');
        }
        
        // Build query for jaspel data
        $query = Jaspel::where('user_id', $user->id);
        
        // Apply filters
        if ($request->filled('start_date')) {
            $query->where('tanggal', '>=', $request->start_date);
        }
        
        if ($request->filled('end_date')) {
            $query->where('tanggal', '<=', $request->end_date);
        }
        
        if ($request->filled('status')) {
            switch ($request->status) {
                case 'approved':
                    $query->where('status_validasi', 'disetujui');
                    break;
                case 'rejected':
                    $query->where('status_validasi', 'ditolak');
                    break;
                case 'pending':
                    $query->where('status_validasi', 'pending');
                    break;
            }
        }
        
        // Get paginated results
        $jaspelData = $query->orderBy('tanggal', 'desc')->paginate(20);
        $jaspelData->appends($request->query());
        
        return view('paramedis-gigi.jaspel', compact('user', 'jaspelData'));
    }
}