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
            
            // Return static demo data to avoid any database/model issues
            $dashboardData = [
                'jaspel_monthly' => 15200000,
                'jaspel_weekly' => 3800000,
                'approved_jaspel' => 12800000,
                'pending_jaspel' => 2400000,
                'minutes_worked' => 720, // 12 hours
                'shifts_this_month' => 22,
                'paramedis_name' => $user->name,
                'paramedis_specialty' => $this->getUserSpecialty($user),
                'today_attendance' => [
                    'type' => 'demo',
                    'check_in' => '08:00:00',
                    'check_out' => null,
                    'status' => 'present',
                ],
                'recent_jaspel' => [
                    [
                        'id' => 1,
                        'tanggal' => Carbon::now()->subDays(1)->format('Y-m-d'),
                        'nominal' => 500000,
                        'status_validasi' => 'disetujui',
                        'jenis_tindakan' => 'Konsultasi Umum',
                        'pasien' => 'Pasien A',
                    ],
                    [
                        'id' => 2,
                        'tanggal' => Carbon::now()->subDays(2)->format('Y-m-d'),
                        'nominal' => 750000,
                        'status_validasi' => 'pending',
                        'jenis_tindakan' => 'Pemeriksaan Khusus',
                        'pasien' => 'Pasien B',
                    ],
                ],
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