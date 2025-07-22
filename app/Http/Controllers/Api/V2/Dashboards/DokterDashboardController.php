<?php

namespace App\Http\Controllers\Api\V2\Dashboards;

use App\Http\Controllers\Controller;
use App\Models\Dokter;
use App\Models\JadwalJaga;
use App\Models\Tindakan;
use App\Models\Attendance;
use App\Models\DokterPresensi;
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

                // Attendance hari ini - use DokterPresensi table
                $attendanceToday = DokterPresensi::where('dokter_id', $dokter->id)
                    ->whereDate('tanggal', $today)
                    ->first();

                return [
                    'patients_today' => $patientsToday,
                    'tindakan_today' => $tindakanToday,
                    'jaspel_month' => $jaspelMonth,
                    'shifts_week' => $shiftsWeek,
                    'attendance_today' => $attendanceToday ? [
                        'check_in' => $attendanceToday->jam_masuk?->format('H:i'),
                        'check_out' => $attendanceToday->jam_pulang?->format('H:i'),
                        'status' => $attendanceToday->jam_pulang ? 'checked_out' : 'checked_in',
                        'duration' => $attendanceToday->durasi
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
            $dokter = Dokter::where('user_id', $user->id)->first();
            $today = Carbon::today();
            
            if (!$dokter) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data dokter tidak ditemukan',
                    'data' => null
                ], 404);
            }
            
            // Presensi hari ini - use DokterPresensi table
            $attendanceToday = DokterPresensi::where('dokter_id', $dokter->id)
                ->whereDate('tanggal', $today)
                ->first();

            // History presensi bulan ini - use DokterPresensi table
            $attendanceHistory = DokterPresensi::where('dokter_id', $dokter->id)
                ->whereMonth('tanggal', Carbon::now()->month)
                ->whereYear('tanggal', Carbon::now()->year)
                ->orderByDesc('tanggal')
                ->get();

            // Stats presensi - adapted for DokterPresensi model
            $attendanceStats = [
                'total_days' => $attendanceHistory->count(),
                'complete_days' => $attendanceHistory->whereNotNull('jam_pulang')->count(),
                'incomplete_days' => $attendanceHistory->whereNull('jam_pulang')->count(),
                'total_hours' => $attendanceHistory->sum(function($record) {
                    if ($record->jam_masuk && $record->jam_pulang) {
                        $masuk = \Carbon\Carbon::parse($record->jam_masuk);
                        $pulang = \Carbon\Carbon::parse($record->jam_pulang);
                        return $pulang->diffInHours($masuk);
                    }
                    return 0;
                })
            ];

            return response()->json([
                'success' => true,
                'message' => 'Data presensi berhasil dimuat',
                'data' => [
                    'today' => $attendanceToday ? [
                        'date' => $attendanceToday->tanggal->format('Y-m-d'),
                        'time_in' => $attendanceToday->jam_masuk?->format('H:i'),
                        'time_out' => $attendanceToday->jam_pulang?->format('H:i'),
                        'status' => $attendanceToday->status,
                        'work_duration' => $attendanceToday->durasi,
                        'can_check_in' => false,
                        'can_check_out' => !$attendanceToday->jam_pulang
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
     * Get performance stats - Enhanced with attendance ranking like Paramedis
     */
    private function getPerformanceStats($dokter)
    {
        $month = Carbon::now()->month;
        $year = Carbon::now()->year;
        $user = Auth::user();
        
        // Get attendance ranking from AttendanceRecap (copied from ParamedisDashboardController)
        $attendanceData = \App\Models\AttendanceRecap::getRecapData($month, $year, 'Dokter');
        
        // Find current user's ranking
        $currentUserRank = null;
        $totalDokter = $attendanceData->count();
        
        foreach ($attendanceData as $staff) {
            if ($staff['staff_id'] == $user->id) {
                $currentUserRank = $staff['rank'];
                break;
            }
        }
        
        // Calculate attendance rate using enhanced method
        $attendanceRate = $this->getAttendanceRateEnhanced($user);
        
        // Debug logging
        \Log::info('🔍 DEBUG: Dokter getPerformanceStats', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'month' => $month,
            'year' => $year,
            'attendance_data_count' => $attendanceData->count(),
            'current_user_rank' => $currentUserRank,
            'total_dokter' => $totalDokter,
            'attendance_rate' => $attendanceRate,
        ]);
        
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
            'attendance_rank' => $currentUserRank ?? $totalDokter + 1,
            'total_staff' => $totalDokter,
            'attendance_percentage' => round($attendanceRate, 1),
            'patient_satisfaction' => min(100, 85 + ($thisMonthTindakan * 1.5)), // Dynamic calculation
            'attendance_rate' => $attendanceRate,
            'efficiency_score' => min(95, 70 + ($thisMonthTindakan * 2)),
            'growth_rate' => round($growthRate, 1)
        ];
    }

    /**
     * Get attendance rate using AttendanceRecap calculation method (copied from ParamedisDashboardController)
     */
    private function getAttendanceRateEnhanced($user)
    {
        $month = Carbon::now()->month;
        $year = Carbon::now()->year;
        
        \Log::info('🔍 DEBUG: Dokter getAttendanceRateEnhanced start', [
            'user_id' => $user->id,
            'month' => $month,
            'year' => $year,
        ]);
        
        // Get attendance data from AttendanceRecap for current user
        $attendanceData = \App\Models\AttendanceRecap::getRecapData($month, $year, 'Dokter');
        
        \Log::info('🔍 DEBUG: Dokter AttendanceRecap data', [
            'count' => $attendanceData->count(),
            'data' => $attendanceData->toArray(),
        ]);
        
        // Find current user's attendance percentage
        foreach ($attendanceData as $staff) {
            \Log::info('🔍 DEBUG: Checking dokter staff', [
                'staff_id' => $staff['staff_id'],
                'user_id' => $user->id,
                'attendance_percentage' => $staff['attendance_percentage'] ?? 'not set',
            ]);
            
            if ($staff['staff_id'] == $user->id) {
                \Log::info('✅ Found dokter user attendance', [
                    'attendance_percentage' => $staff['attendance_percentage'],
                ]);
                return $staff['attendance_percentage'];
            }
        }
        
        \Log::info('🔄 Using fallback calculation for dokter');
        
        // Fallback: calculate manually using same method as AttendanceRecap
        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();
        
        // Count working days (Monday to Saturday, exclude Sunday)
        $workingDays = 0;
        $tempDate = $startDate->copy();
        while ($tempDate->lte($endDate)) {
            if ($tempDate->dayOfWeek !== Carbon::SUNDAY) {
                $workingDays++;
            }
            $tempDate->addDay();
        }
        
        // Count attendance days for the full month using DokterPresensi
        $dokter = Dokter::where('user_id', $user->id)->first();
        $attendanceDays = $dokter ? DokterPresensi::where('dokter_id', $dokter->id)
            ->whereBetween('tanggal', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->distinct('tanggal')
            ->count() : 0;
        
        $fallbackRate = $workingDays > 0 ? round(($attendanceDays / $workingDays) * 100, 2) : 0;
        
        \Log::info('🔍 DEBUG: Dokter Fallback calculation', [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'working_days' => $workingDays,
            'attendance_days' => $attendanceDays,
            'fallback_rate' => $fallbackRate,
        ]);
        
        return $fallbackRate;
    }

    /**
     * Get attendance rate - Legacy method for backward compatibility
     */
    private function getAttendanceRate($user)
    {
        return $this->getAttendanceRateEnhanced($user);
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

        // Ensure tanggal_jaga is properly cast to Carbon
        $tanggalJaga = $nextSchedule->tanggal_jaga instanceof Carbon 
            ? $nextSchedule->tanggal_jaga 
            : Carbon::parse($nextSchedule->tanggal_jaga);

        return [
            'id' => $nextSchedule->id,
            'date' => $tanggalJaga->format('Y-m-d'),
            'formatted_date' => $tanggalJaga->format('l, d F Y'),
            'shift_name' => $nextSchedule->shiftTemplate->nama_shift ?? 'Shift',
            'start_time' => $nextSchedule->shiftTemplate->jam_masuk ?? '08:00',
            'end_time' => $nextSchedule->shiftTemplate->jam_pulang ?? '16:00',
            'unit_kerja' => $nextSchedule->unit_kerja ?? 'Unit Kerja',
            'days_until' => Carbon::today()->diffInDays($tanggalJaga)
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

    /**
     * Get attendance data for dokter
     */
    public function getAttendance(Request $request)
    {
        try {
            $user = Auth::user();
            $dokter = Dokter::where('user_id', $user->id)->first();
            $today = Carbon::today();
            
            if (!$dokter) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data dokter tidak ditemukan',
                    'data' => null
                ], 404);
            }
            
            // Get today's attendance using DokterPresensi
            $attendance = DokterPresensi::where('dokter_id', $dokter->id)
                ->whereDate('tanggal', $today)
                ->first();
            
            return response()->json([
                'success' => true,
                'message' => 'Attendance data retrieved successfully',
                'data' => [
                    'today' => [
                        'has_checked_in' => $attendance ? true : false,
                        'has_checked_out' => $attendance && $attendance->jam_pulang ? true : false,
                        'check_in_time' => $attendance?->jam_masuk?->format('H:i'),
                        'check_out_time' => $attendance?->jam_pulang?->format('H:i'),
                        'work_duration' => $attendance?->durasi ?? '0 jam',
                        'status' => $attendance ? 
                            ($attendance->jam_pulang ? 'checked_out' : 'checked_in') : 
                            'not_checked_in'
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve attendance data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check-in/Check-out methods (copied from ParamedisDashboardController)
     */
    public function checkIn(Request $request)
    {
        try {
            $user = Auth::user();
            $dokter = Dokter::where('user_id', $user->id)->first();
            $today = Carbon::today();
            
            if (!$dokter) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data dokter tidak ditemukan'
                ], 404);
            }
            
            // Cek apakah sudah check-in hari ini
            $attendance = DokterPresensi::where('dokter_id', $dokter->id)
                ->whereDate('tanggal', $today)
                ->first();

            if ($attendance && $attendance->jam_masuk) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah check-in hari ini'
                ], 422);
            }

            // Buat record attendance
            $attendance = DokterPresensi::updateOrCreate([
                'dokter_id' => $dokter->id,
                'tanggal' => $today
            ], [
                'jam_masuk' => Carbon::now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Check-in berhasil',
                'data' => $attendance
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal check-in: ' . $e->getMessage()
            ], 500);
        }
    }

    public function checkOut(Request $request)
    {
        try {
            $user = Auth::user();
            $dokter = Dokter::where('user_id', $user->id)->first();
            $today = Carbon::today();
            
            if (!$dokter) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data dokter tidak ditemukan'
                ], 404);
            }
            
            $attendance = DokterPresensi::where('dokter_id', $dokter->id)
                ->whereDate('tanggal', $today)
                ->whereNotNull('jam_masuk')
                ->whereNull('jam_pulang')
                ->first();

            if (!$attendance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Belum check-in atau sudah check-out'
                ], 422);
            }

            $attendance->update([
                'jam_pulang' => Carbon::now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Check-out berhasil',
                'data' => $attendance
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal check-out: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current week schedule for mobile dashboard
     */
    public function getWeeklySchedule(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Get current week schedules (Monday to Sunday)
            $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY);
            $endOfWeek = Carbon::now()->endOfWeek(Carbon::SUNDAY);
            
            $weeklySchedule = JadwalJaga::where('pegawai_id', $user->id)
                ->whereBetween('tanggal_jaga', [
                    $startOfWeek->format('Y-m-d'),
                    $endOfWeek->format('Y-m-d')
                ])
                ->with(['shiftTemplate'])
                ->orderBy('tanggal_jaga')
                ->orderBy('shift_template_id') // Sort by shift time for same day
                ->get()
                ->map(function ($jadwal) {
                    $tanggalJaga = $jadwal->tanggal_jaga instanceof Carbon 
                        ? $jadwal->tanggal_jaga 
                        : Carbon::parse($jadwal->tanggal_jaga);
                        
                    return [
                        'id' => $jadwal->id,
                        'tanggal' => $tanggalJaga->format('Y-m-d'),
                        'waktu' => $jadwal->shiftTemplate ? 
                            ($jadwal->shiftTemplate->jam_masuk . ' - ' . $jadwal->shiftTemplate->jam_pulang) : 
                            '08:00 - 16:00',
                        'lokasi' => $jadwal->unit_kerja ?? 'Unit Kerja',
                        'jenis' => $this->getShiftType($jadwal->shiftTemplate),
                        'status' => $tanggalJaga->isPast() ? 'completed' : 'scheduled',
                        'shift_nama' => $jadwal->shiftTemplate->nama_shift ?? 'Shift',
                        'status_jaga' => $jadwal->status_jaga ?? 'Aktif',
                        'keterangan' => $jadwal->keterangan
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Jadwal minggu ini berhasil dimuat',
                'data' => $weeklySchedule,
                'meta' => [
                    'week_period' => [
                        'start' => $startOfWeek->format('Y-m-d'),
                        'end' => $endOfWeek->format('Y-m-d'),
                        'start_formatted' => $startOfWeek->format('d M Y'),
                        'end_formatted' => $endOfWeek->format('d M Y')
                    ],
                    'total_schedules' => $weeklySchedule->count()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat jadwal minggu ini: ' . $e->getMessage(),
                'data' => [],
                'meta' => null
            ], 500);
        }
    }

    /**
     * Endpoint untuk schedule API (untuk mobile app) - copied from ParamedisDashboardController
     */
    public function schedules(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Get upcoming schedules for mobile app
            $schedules = JadwalJaga::where('pegawai_id', $user->id)
                ->where('tanggal_jaga', '>=', Carbon::today())
                ->with(['shiftTemplate'])
                ->orderBy('tanggal_jaga')
                ->orderBy('shift_template_id') // Sort by shift time for same day
                ->limit(10)
                ->get()
                ->map(function ($jadwal) {
                    // Ensure tanggal_jaga is properly cast to Carbon
                    $tanggalJaga = $jadwal->tanggal_jaga instanceof Carbon 
                        ? $jadwal->tanggal_jaga 
                        : Carbon::parse($jadwal->tanggal_jaga);
                        
                    return [
                        'id' => $jadwal->id,
                        'tanggal' => $tanggalJaga->format('Y-m-d'),
                        'waktu' => $jadwal->shiftTemplate ? 
                            ($jadwal->shiftTemplate->jam_masuk . ' - ' . $jadwal->shiftTemplate->jam_pulang) : 
                            '08:00 - 16:00', // Default fallback
                        'lokasi' => $jadwal->unit_kerja ?? 'Unit Kerja',
                        'jenis' => $this->getShiftType($jadwal->shiftTemplate),
                        'status' => 'scheduled',
                        'shift_nama' => $jadwal->shiftTemplate->nama_shift ?? 'Shift',
                        'status_jaga' => $jadwal->status_jaga ?? 'Aktif',
                        'keterangan' => $jadwal->keterangan
                    ];
                });

            return response()->json($schedules);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat jadwal: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Get IGD schedules by location categories
     */
    public function getIgdSchedules(Request $request)
    {
        try {
            $user = Auth::user();
            $category = $request->get('category', 'all'); // all, pendaftaran, pelayanan, dokter_jaga
            
            // Map category to unit_kerja values (matching admin form options)
            $unitKerjaMap = [
                'all' => ['Pendaftaran', 'Pelayanan', 'Dokter Jaga'],
                'pendaftaran' => ['Pendaftaran'],
                'pelayanan' => ['Pelayanan'],
                'dokter_jaga' => ['Dokter Jaga']
            ];
            
            $targetUnits = $unitKerjaMap[$category] ?? $unitKerjaMap['all'];
            
            // Get current week schedules for unit locations
            $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY);
            $endOfWeek = Carbon::now()->endOfWeek(Carbon::SUNDAY);
            
            $schedules = JadwalJaga::whereIn('unit_kerja', $targetUnits)
                ->whereBetween('tanggal_jaga', [
                    $startOfWeek->format('Y-m-d'),
                    $endOfWeek->format('Y-m-d')
                ])
                ->with(['shiftTemplate', 'pegawai'])
                ->orderBy('tanggal_jaga')
                ->orderByRaw("
                    CASE 
                        WHEN shift_templates.jam_masuk >= '06:00' AND shift_templates.jam_masuk < '14:00' THEN 1
                        WHEN shift_templates.jam_masuk >= '14:00' AND shift_templates.jam_masuk < '22:00' THEN 2
                        ELSE 3
                    END
                ")
                ->leftJoin('shift_templates', 'jadwal_jagas.shift_template_id', '=', 'shift_templates.id')
                ->select('jadwal_jagas.*')
                ->get()
                ->groupBy('unit_kerja')
                ->map(function ($schedules, $unitKerja) {
                    return [
                        'unit_kerja' => $unitKerja,
                        'schedules' => $schedules->map(function ($jadwal) {
                            $tanggalJaga = $jadwal->tanggal_jaga instanceof Carbon 
                                ? $jadwal->tanggal_jaga 
                                : Carbon::parse($jadwal->tanggal_jaga);
                                
                            return [
                                'id' => $jadwal->id,
                                'tanggal' => $tanggalJaga->format('Y-m-d'),
                                'waktu' => $jadwal->shiftTemplate ? 
                                    ($jadwal->shiftTemplate->jam_masuk . ' - ' . $jadwal->shiftTemplate->jam_pulang) : 
                                    '08:00 - 16:00',
                                'lokasi' => $jadwal->unit_kerja,
                                'pegawai_nama' => $jadwal->pegawai->name ?? 'Staff',
                                'jenis' => $this->getShiftType($jadwal->shiftTemplate),
                                'status' => $tanggalJaga->isPast() ? 'completed' : 'scheduled',
                                'shift_nama' => $jadwal->shiftTemplate->nama_shift ?? 'Shift',
                                'status_jaga' => $jadwal->status_jaga ?? 'Aktif',
                                'keterangan' => $jadwal->keterangan
                            ];
                        })
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Data jadwal unit kerja berhasil dimuat',
                'data' => $schedules->values(),
                'meta' => [
                    'category' => $category,
                    'week_period' => [
                        'start' => $startOfWeek->format('Y-m-d'),
                        'end' => $endOfWeek->format('Y-m-d'),
                        'start_formatted' => $startOfWeek->format('d M Y'),
                        'end_formatted' => $endOfWeek->format('d M Y')
                    ],
                    'total_locations' => $schedules->count(),
                    'total_schedules' => $schedules->sum(function($location) {
                        return $location['schedules']->count();
                    })
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data jadwal unit kerja: ' . $e->getMessage(),
                'data' => [],
                'meta' => null
            ], 500);
        }
    }

    /**
     * Helper method to determine shift type based on time
     */
    private function getShiftType($shiftTemplate)
    {
        if (!$shiftTemplate || !$shiftTemplate->jam_masuk) {
            return 'pagi'; // Default fallback
        }
        
        $startHour = (int) substr($shiftTemplate->jam_masuk, 0, 2);
        
        if ($startHour >= 6 && $startHour < 14) {
            return 'pagi';
        } elseif ($startHour >= 14 && $startHour < 22) {
            return 'siang';
        } else {
            return 'malam';
        }
    }
}