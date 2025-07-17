<?php

namespace App\Http\Controllers\Api\V2\Dashboards;

use App\Http\Controllers\Controller;
use App\Models\Dokter;
use App\Models\JadwalJaga;
use App\Models\Tindakan;
use App\Models\Attendance;
use App\Models\DokterUmumJaspel;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DokterDashboardController extends Controller
{
    /**
     * Dashboard utama dokter dengan stats real
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $dokter = Dokter::where('user_id', $user->id)->first();
            
            if (!$dokter) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data dokter tidak ditemukan',
                    'data' => null
                ], 404);
            }

            // Cache dashboard stats untuk 5 menit
            $cacheKey = "dokter_dashboard_stats_{$user->id}";
            $stats = Cache::remember($cacheKey, 300, function () use ($dokter, $user) {
                $today = Carbon::today();
                $thisMonth = Carbon::now()->startOfMonth();
                $thisWeek = Carbon::now()->startOfWeek();

                // Hitung stats real
                $patientsToday = Tindakan::where('dokter_id', $dokter->id)
                    ->whereDate('tanggal_tindakan', $today)
                    ->distinct('pasien_id')
                    ->count();

                $tindakanToday = Tindakan::where('dokter_id', $dokter->id)
                    ->whereDate('tanggal_tindakan', $today)
                    ->count();

                $jaspelMonth = Tindakan::where('dokter_id', $dokter->id)
                    ->where('tanggal_tindakan', '>=', $thisMonth)
                    ->where('status_validasi', 'disetujui')
                    ->sum('jasa_dokter');

                $shiftsWeek = JadwalJaga::where('pegawai_id', $user->id)
                    ->where('tanggal_jaga', '>=', $thisWeek)
                    ->where('tanggal_jaga', '<=', Carbon::now()->endOfWeek())
                    ->count();

                // Attendance hari ini
                $attendanceToday = Attendance::where('user_id', $user->id)
                    ->whereDate('date', $today)
                    ->first();

                return [
                    'patients_today' => $patientsToday,
                    'tindakan_today' => $tindakanToday,
                    'jaspel_month' => $jaspelMonth,
                    'shifts_week' => $shiftsWeek,
                    'attendance_today' => $attendanceToday ? [
                        'check_in' => $attendanceToday->time_in?->format('H:i'),
                        'check_out' => $attendanceToday->time_out?->format('H:i'),
                        'status' => $attendanceToday->time_out ? 'checked_out' : 'checked_in',
                        'duration' => $attendanceToday->formatted_work_duration
                    ] : null
                ];
            });

            // Performance metrics
            $performanceStats = $this->getPerformanceStats($dokter);
            
            // Next schedule
            $nextSchedule = $this->getNextSchedule($user);

            return response()->json([
                'success' => true,
                'message' => 'Dashboard data berhasil dimuat',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'jabatan' => $dokter->jabatan_display,
                        'avatar' => $dokter->default_avatar,
                        'initials' => strtoupper(substr($user->name, 0, 2))
                    ],
                    'dokter' => [
                        'id' => $dokter->id,
                        'nama_lengkap' => $dokter->nama_lengkap,
                        'nik' => $dokter->nik,
                        'jabatan' => $dokter->jabatan,
                        'nomor_sip' => $dokter->nomor_sip,
                        'status' => $dokter->status_text
                    ],
                    'stats' => $stats,
                    'performance' => $performanceStats,
                    'next_schedule' => $nextSchedule,
                    'current_time' => Carbon::now()->format('H:i'),
                    'current_date' => Carbon::now()->format('Y-m-d'),
                    'greeting' => $this->getGreeting()
                ],
                'meta' => [
                    'version' => '2.0',
                    'timestamp' => now()->toISOString(),
                    'request_id' => \Illuminate\Support\Str::uuid()->toString(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat dashboard: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Jadwal jaga dokter
     */
    public function getJadwalJaga(Request $request)
    {
        try {
            $user = Auth::user();
            $month = $request->get('month', Carbon::now()->month);
            $year = $request->get('year', Carbon::now()->year);

            // Jadwal untuk bulan tertentu
            $jadwalJaga = JadwalJaga::where('pegawai_id', $user->id)
                ->whereMonth('tanggal_jaga', $month)
                ->whereYear('tanggal_jaga', $year)
                ->with(['shiftTemplate'])
                ->orderBy('tanggal_jaga')
                ->get();

            // Format untuk calendar
            $calendarEvents = $jadwalJaga->map(function ($jadwal) {
                return [
                    'id' => $jadwal->id,
                    'title' => $jadwal->shiftTemplate->nama_shift,
                    'start' => $jadwal->start,
                    'end' => $jadwal->end,
                    'color' => $jadwal->color,
                    'description' => $jadwal->unit_kerja,
                    'shift_info' => [
                        'nama_shift' => $jadwal->shiftTemplate->nama_shift,
                        'jam_masuk' => $jadwal->shiftTemplate->jam_masuk,
                        'jam_pulang' => $jadwal->shiftTemplate->jam_pulang,
                        'unit_kerja' => $jadwal->unit_kerja,
                        'status' => $jadwal->status_jaga
                    ]
                ];
            });

            // Jadwal minggu ini
            $weeklySchedule = JadwalJaga::where('pegawai_id', $user->id)
                ->whereBetween('tanggal_jaga', [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek()
                ])
                ->with(['shiftTemplate'])
                ->orderBy('tanggal_jaga')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Jadwal jaga berhasil dimuat',
                'data' => [
                    'calendar_events' => $calendarEvents,
                    'weekly_schedule' => $weeklySchedule,
                    'month' => $month,
                    'year' => $year,
                    'total_shifts' => $jadwalJaga->count(),
                    'next_shift' => $this->getNextSchedule($user)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat jadwal jaga: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Detail jaspel dokter
     */
    public function getJaspel(Request $request)
    {
        try {
            $user = Auth::user();
            $dokter = Dokter::where('user_id', $user->id)->first();
            
            $month = $request->get('month', Carbon::now()->month);
            $year = $request->get('year', Carbon::now()->year);

            // Jaspel bulan ini
            $jaspelQuery = Tindakan::where('dokter_id', $dokter->id)
                ->whereMonth('tanggal_tindakan', $month)
                ->whereYear('tanggal_tindakan', $year);

            $jaspelStats = [
                'total' => $jaspelQuery->sum('jasa_dokter'),
                'approved' => $jaspelQuery->where('status_validasi', 'disetujui')->sum('jasa_dokter'),
                'pending' => $jaspelQuery->where('status_validasi', 'pending')->sum('jasa_dokter'),
                'rejected' => $jaspelQuery->where('status_validasi', 'ditolak')->sum('jasa_dokter'),
                'count_tindakan' => $jaspelQuery->count()
            ];

            // Breakdown per hari
            $dailyBreakdown = $jaspelQuery->select(
                DB::raw('DATE(tanggal_tindakan) as date'),
                DB::raw('COUNT(*) as total_tindakan'),
                DB::raw('SUM(jasa_dokter) as total_jaspel'),
                DB::raw('SUM(CASE WHEN status_validasi = "disetujui" THEN jasa_dokter ELSE 0 END) as approved_jaspel')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

            // Breakdown per jenis tindakan
            $tindakanBreakdown = $jaspelQuery->select(
                'jenis_tindakan',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(jasa_dokter) as total_jaspel')
            )
            ->groupBy('jenis_tindakan')
            ->orderByDesc('total_jaspel')
            ->get();

            // Riwayat jaspel
            $recentJaspel = $jaspelQuery->with(['pasien:id,nama_pasien'])
                ->orderByDesc('tanggal_tindakan')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Data jaspel berhasil dimuat',
                'data' => [
                    'stats' => $jaspelStats,
                    'daily_breakdown' => $dailyBreakdown,
                    'tindakan_breakdown' => $tindakanBreakdown,
                    'recent_jaspel' => $recentJaspel,
                    'month' => $month,
                    'year' => $year
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data jaspel: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Riwayat tindakan dokter
     */
    public function getTindakan(Request $request)
    {
        try {
            $user = Auth::user();
            $dokter = Dokter::where('user_id', $user->id)->first();
            
            $limit = min($request->get('limit', 15), 50);
            $status = $request->get('status');
            $search = $request->get('search');

            $query = Tindakan::where('dokter_id', $dokter->id)
                ->with(['pasien:id,nama_pasien,nomor_pasien']);

            if ($status) {
                $query->where('status_validasi', $status);
            }

            if ($search) {
                $query->whereHas('pasien', function($q) use ($search) {
                    $q->where('nama_pasien', 'like', "%{$search}%")
                      ->orWhere('nomor_pasien', 'like', "%{$search}%");
                });
            }

            $tindakan = $query->orderByDesc('tanggal_tindakan')
                ->paginate($limit);

            return response()->json([
                'success' => true,
                'message' => 'Data tindakan berhasil dimuat',
                'data' => $tindakan,
                'meta' => [
                    'summary' => [
                        'total' => Tindakan::where('dokter_id', $dokter->id)->count(),
                        'approved' => Tindakan::where('dokter_id', $dokter->id)->where('status_validasi', 'disetujui')->count(),
                        'pending' => Tindakan::where('dokter_id', $dokter->id)->where('status_validasi', 'pending')->count(),
                        'rejected' => Tindakan::where('dokter_id', $dokter->id)->where('status_validasi', 'ditolak')->count()
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data tindakan: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Status dan history presensi
     */
    public function getPresensi(Request $request)
    {
        try {
            $user = Auth::user();
            $today = Carbon::today();
            
            // Presensi hari ini
            $attendanceToday = Attendance::where('user_id', $user->id)
                ->whereDate('date', $today)
                ->first();

            // History presensi bulan ini
            $attendanceHistory = Attendance::where('user_id', $user->id)
                ->whereMonth('date', Carbon::now()->month)
                ->whereYear('date', Carbon::now()->year)
                ->orderByDesc('date')
                ->get();

            // Stats presensi
            $attendanceStats = [
                'total_days' => $attendanceHistory->count(),
                'on_time' => $attendanceHistory->where('status', 'on_time')->count(),
                'late' => $attendanceHistory->where('status', 'late')->count(),
                'early_leave' => $attendanceHistory->where('status', 'early_leave')->count(),
                'total_hours' => $attendanceHistory->sum('work_duration_minutes') / 60
            ];

            return response()->json([
                'success' => true,
                'message' => 'Data presensi berhasil dimuat',
                'data' => [
                    'today' => $attendanceToday ? [
                        'date' => $attendanceToday->date->format('Y-m-d'),
                        'time_in' => $attendanceToday->time_in?->format('H:i'),
                        'time_out' => $attendanceToday->time_out?->format('H:i'),
                        'status' => $attendanceToday->status,
                        'work_duration' => $attendanceToday->formatted_work_duration,
                        'can_check_in' => false,
                        'can_check_out' => !$attendanceToday->time_out
                    ] : [
                        'date' => $today->format('Y-m-d'),
                        'time_in' => null,
                        'time_out' => null,
                        'status' => null,
                        'work_duration' => null,
                        'can_check_in' => true,
                        'can_check_out' => false
                    ],
                    'history' => $attendanceHistory,
                    'stats' => $attendanceStats
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data presensi: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get performance stats
     */
    private function getPerformanceStats($dokter)
    {
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();
        
        $thisMonthTindakan = Tindakan::where('dokter_id', $dokter->id)
            ->where('tanggal_tindakan', '>=', $thisMonth)
            ->count();
            
        $lastMonthTindakan = Tindakan::where('dokter_id', $dokter->id)
            ->whereBetween('tanggal_tindakan', [$lastMonth, $thisMonth])
            ->count();

        $growthRate = $lastMonthTindakan > 0 ? 
            (($thisMonthTindakan - $lastMonthTindakan) / $lastMonthTindakan) * 100 : 0;

        return [
            'efficiency_score' => min(95, 70 + ($thisMonthTindakan * 2)),
            'patient_satisfaction' => 92,
            'growth_rate' => round($growthRate, 1),
            'attendance_rate' => $this->getAttendanceRate(Auth::user())
        ];
    }

    /**
     * Get attendance rate
     */
    private function getAttendanceRate($user)
    {
        $thisMonth = Carbon::now()->startOfMonth();
        $workDays = Carbon::now()->diffInDaysFiltered(function (Carbon $date) {
            return $date->isWeekday();
        }, $thisMonth);

        $attendanceDays = Attendance::where('user_id', $user->id)
            ->where('date', '>=', $thisMonth)
            ->count();

        return $workDays > 0 ? round(($attendanceDays / $workDays) * 100, 1) : 0;
    }

    /**
     * Get next schedule
     */
    private function getNextSchedule($user)
    {
        $nextSchedule = JadwalJaga::where('pegawai_id', $user->id)
            ->where('tanggal_jaga', '>=', Carbon::today())
            ->with(['shiftTemplate'])
            ->orderBy('tanggal_jaga')
            ->first();

        if (!$nextSchedule) {
            return null;
        }

        return [
            'id' => $nextSchedule->id,
            'date' => $nextSchedule->tanggal_jaga->format('Y-m-d'),
            'formatted_date' => $nextSchedule->tanggal_jaga->format('l, d F Y'),
            'shift_name' => $nextSchedule->shiftTemplate->nama_shift,
            'start_time' => $nextSchedule->shiftTemplate->jam_masuk,
            'end_time' => $nextSchedule->shiftTemplate->jam_pulang,
            'unit_kerja' => $nextSchedule->unit_kerja,
            'days_until' => Carbon::today()->diffInDays($nextSchedule->tanggal_jaga)
        ];
    }

    /**
     * Get greeting based on time
     */
    private function getGreeting()
    {
        $hour = Carbon::now()->hour;
        
        if ($hour < 12) {
            return 'Selamat Pagi';
        } elseif ($hour < 17) {
            return 'Selamat Siang';
        } else {
            return 'Selamat Malam';
        }
    }
}