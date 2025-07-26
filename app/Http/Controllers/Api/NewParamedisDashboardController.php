<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Jaspel;
use App\Models\Tindakan;
use App\Models\Pegawai;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class NewParamedisDashboardController extends Controller
{
    /**
     * Get dashboard data for paramedis - CLEAN VERSION
     */
    public function getDashboardData(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user || !$user->hasRole('paramedis')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized - Only paramedis can access this data'
                ], 403);
            }

            // Get current month and year
            $currentMonth = now()->month;
            $currentYear = now()->year;
            $lastMonth = now()->subMonth()->month;
            $lastMonthYear = now()->subMonth()->year;

            Log::info('NEW PARAMEDIS DASHBOARD REQUEST', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'current_month' => $currentMonth,
                'current_year' => $currentYear,
                'timestamp' => now()->toISOString()
            ]);

            // Calculate Jaspel for current month
            $currentMonthJaspel = $this->calculateJaspelForMonth($user, $currentMonth, $currentYear);
            $lastMonthJaspel = $this->calculateJaspelForMonth($user, $lastMonth, $lastMonthYear);

            // Calculate growth
            $growth = 0;
            if ($lastMonthJaspel > 0) {
                $growth = round((($currentMonthJaspel - $lastMonthJaspel) / $lastMonthJaspel) * 100, 1);
            } elseif ($currentMonthJaspel > 0) {
                $growth = 100; // If no previous data but current data exists
            }

            $dashboardData = [
                'jaspel_bulan_ini' => [
                    'nominal' => $currentMonthJaspel,
                    'formatted' => 'Rp ' . number_format($currentMonthJaspel, 0, ',', '.')
                ],
                'jaspel_bulan_lalu' => [
                    'nominal' => $lastMonthJaspel,
                    'formatted' => 'Rp ' . number_format($lastMonthJaspel, 0, ',', '.')
                ],
                'growth' => [
                    'percentage' => $growth,
                    'formatted' => ($growth >= 0 ? '+' : '') . $growth . '%',
                    'direction' => $growth >= 0 ? 'up' : 'down'
                ]
            ];

            Log::info('NEW PARAMEDIS DASHBOARD RESPONSE', [
                'user_id' => $user->id,
                'current_month_jaspel' => $currentMonthJaspel,
                'last_month_jaspel' => $lastMonthJaspel,
                'growth' => $growth,
                'timestamp' => now()->toISOString()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'NEW CLEAN: Dashboard data retrieved successfully',
                'data' => $dashboardData,
                'meta' => [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'current_month' => $currentMonth,
                    'current_year' => $currentYear,
                    'controller' => 'NewParamedisDashboardController',
                    'calculation_method' => 'direct_database_query',
                    'timestamp' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('NEW PARAMEDIS DASHBOARD ERROR', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'timestamp' => now()->toISOString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate Jaspel for specific month - SIMPLE AND CORRECT
     */
    private function calculateJaspelForMonth($user, $month, $year)
    {
        // Get Pegawai record for this user
        $pegawai = Pegawai::where('user_id', $user->id)
            ->where('jenis_pegawai', 'Paramedis')
            ->first();

        if (!$pegawai) {
            return 0; // No paramedis record = no Jaspel
        }

        // Method 1: Check existing Jaspel records
        $existingJaspel = Jaspel::where('user_id', $user->id)
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->where('status_validasi', 'disetujui')
            ->sum('nominal');

        // Method 2: Calculate from approved Tindakan
        $approvedTindakan = Tindakan::where('paramedis_id', $pegawai->id)
            ->whereMonth('tanggal_tindakan', $month)
            ->whereYear('tanggal_tindakan', $year)
            ->where('status_validasi', 'disetujui')
            ->with('jenisTindakan')
            ->get();

        $calculatedJaspel = 0;
        foreach ($approvedTindakan as $tindakan) {
            if ($tindakan->jenisTindakan && $tindakan->jenisTindakan->persentase_jaspel > 0) {
                // Use percentage from JenisTindakan
                $jaspelAmount = $tindakan->tarif * ($tindakan->jenisTindakan->persentase_jaspel / 100);
                $calculatedJaspel += $jaspelAmount;
            } else {
                // Fallback: Use standard 15% for paramedis
                $calculatedJaspel += $tindakan->tarif * 0.15;
            }
        }

        // Return the higher value (either existing records or calculated)
        return max($existingJaspel, $calculatedJaspel);
    }
}