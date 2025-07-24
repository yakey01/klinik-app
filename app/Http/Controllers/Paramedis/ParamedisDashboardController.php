<?php

namespace App\Http\Controllers\Paramedis;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ParamedisDashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'error' => 'Not authenticated',
                    'message' => 'Please login first'
                ], 401);
            }
            
            // Log user info for debugging
            \Log::info('ParamedisDashboard API - User Info', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_role' => $user->role?->name ?? 'no_role',
            ]);
            
            // Get paramedis data for dynamic calculations
            $paramedis = \App\Models\Pegawai::where('user_id', $user->id)
                ->where('jenis_pegawai', 'Paramedis')
                ->first();
            
            if (!$paramedis) {
                return response()->json([
                    'error' => 'Paramedis data not found',
                    'message' => 'Unable to find paramedis record for user'
                ], 404);
            }
            
            // Calculate dynamic Jaspel data from validated Tindakan (same as bendahara validation)
            $today = Carbon::today();
            $thisMonth = Carbon::now()->startOfMonth();
            $thisWeek = Carbon::now()->startOfWeek();
            
            // Monthly Jaspel from Jaspel model (consistent with Jaspel page)
            $jaspelMonthly = \App\Models\Jaspel::where('user_id', $user->id)
                ->whereMonth('tanggal', $thisMonth->month)
                ->whereYear('tanggal', $thisMonth->year)
                ->whereIn('status_validasi', ['disetujui', 'approved'])
                ->sum('nominal');
            
            // Weekly Jaspel from Jaspel model (consistent calculation)
            $jaspelWeekly = \App\Models\Jaspel::where('user_id', $user->id)
                ->where('tanggal', '>=', $thisWeek)
                ->whereIn('status_validasi', ['disetujui', 'approved'])
                ->sum('nominal');
            
            // Approved vs Pending breakdown using Jaspel model
            $approvedJaspel = \App\Models\Jaspel::where('user_id', $user->id)
                ->whereMonth('tanggal', $thisMonth->month)
                ->whereYear('tanggal', $thisMonth->year)
                ->whereIn('status_validasi', ['disetujui', 'approved'])
                ->sum('nominal');
                
            // WORLD-CLASS: Calculate comprehensive pending Jaspel from multiple sources
            // 1. Existing Jaspel records with pending status
            $pendingJaspelRecords = \App\Models\Jaspel::where('user_id', $user->id)
                ->whereMonth('tanggal', $thisMonth->month)
                ->whereYear('tanggal', $thisMonth->year)
                ->where('status_validasi', 'pending')
                ->sum('nominal');
                
            // 2. Approved Tindakan that haven't generated Jaspel records yet (paramedis portion)
            $pendingFromTindakan = \App\Models\Tindakan::where('paramedis_id', $paramedis->id)
                ->whereMonth('tanggal_tindakan', $thisMonth->month)
                ->whereYear('tanggal_tindakan', $thisMonth->year)
                ->whereIn('status_validasi', ['approved', 'disetujui'])
                ->whereDoesntHave('jaspel', function($query) use ($user) {
                    $query->where('user_id', $user->id)
                          ->where('jenis_jaspel', 'paramedis');
                })
                ->where('jasa_paramedis', '>', 0)
                ->sum('jasa_paramedis');
                
            // Total pending = existing pending Jaspel + paramedis portion of approved Tindakan without Jaspel
            $pendingJaspel = $pendingJaspelRecords + ($pendingFromTindakan * 0.15); // 15% paramedis calculation
            
            // Shifts and attendance data
            $shiftsThisMonth = \App\Models\JadwalJaga::where('pegawai_id', $user->id)
                ->whereMonth('tanggal_jaga', Carbon::now()->month)
                ->count();
                
            $todayAttendance = \App\Models\Attendance::where('user_id', $user->id)
                ->whereDate('date', $today)
                ->first();
            
            // Recent validated Jaspel records using Jaspel model
            $recentJaspel = \App\Models\Jaspel::where('user_id', $user->id)
                ->whereIn('status_validasi', ['disetujui', 'approved'])
                ->with(['tindakan.pasien:id,nama_pasien'])
                ->orderByDesc('tanggal')
                ->limit(5)
                ->get()
                ->map(function($jaspel) {
                    $tindakan = $jaspel->tindakan;
                    return [
                        'id' => $jaspel->id,
                        'tanggal' => $jaspel->tanggal->format('Y-m-d'),
                        'nominal' => $jaspel->nominal,
                        'status_validasi' => $jaspel->status_validasi,
                        'jenis_tindakan' => $jaspel->jenis_jaspel,
                        'pasien' => $tindakan && $tindakan->pasien ? $tindakan->pasien->nama_pasien : 'Unknown Patient',
                    ];
                });
            
            // Return dynamic data based on validated bendahara data with enhanced metadata
            $dashboardData = [
                'jaspel_monthly' => $jaspelMonthly,
                'jaspel_weekly' => $jaspelWeekly,
                'approved_jaspel' => $approvedJaspel,
                'pending_jaspel' => $pendingJaspel,
                'pending_breakdown' => [
                    'jaspel_pending' => $pendingJaspelRecords,
                    'tindakan_awaiting_jaspel' => $pendingFromTindakan * 0.15,
                    'tindakan_raw_amount' => $pendingFromTindakan,
                    'calculation_rate' => '15%',
                    'needs_bendahara_action' => $pendingFromTindakan > 0
                ],
                'minutes_worked' => $todayAttendance ? $todayAttendance->work_duration_minutes ?? 0 : 0,
                'shifts_this_month' => $shiftsThisMonth,
                'paramedis_name' => $user->name,
                'paramedis_specialty' => $this->getUserSpecialty($user),
                'today_attendance' => $todayAttendance ? [
                    'type' => 'real',
                    'check_in' => $todayAttendance->time_in?->format('H:i:s'),
                    'check_out' => $todayAttendance->time_out?->format('H:i:s'),
                    'status' => $todayAttendance->time_out ? 'checked_out' : 'checked_in',
                ] : null,
                'recent_jaspel' => $recentJaspel->toArray(),
            ];

            return response()->json($dashboardData);
            
        } catch (\Exception $e) {
            \Log::error('ParamedisDashboard API error', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            
            return response()->json([
                'error' => 'Failed to fetch dashboard data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function getUserSpecialty($user)
    {
        $specialtyMap = [
            'paramedis' => 'Perawat',
            'dokter' => 'Dokter Umum',
            'perawat' => 'Perawat',
            'bidan' => 'Bidan',
            'admin' => 'Administrator',
            'manajer' => 'Manajer',
            'bendahara' => 'Bendahara',
            'petugas' => 'Petugas',
        ];

        $roleName = $user->role?->name ?? 'paramedis';
        return $specialtyMap[$roleName] ?? 'Tenaga Kesehatan';
    }

    // Legacy methods for compatibility
    public function schedule(Request $request)
    {
        return response()->json(['message' => 'Schedule endpoint - under development']);
    }

    public function performance(Request $request)
    {
        return response()->json(['message' => 'Performance endpoint - under development']);
    }

    public function notifications(Request $request)
    {
        return response()->json(['message' => 'Notifications endpoint - under development']);
    }

    public function markNotificationRead(Request $request, $id)
    {
        return response()->json(['message' => 'Mark notification read - under development']);
    }
}