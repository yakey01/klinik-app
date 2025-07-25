<?php

namespace App\Http\Controllers\Api\V2\Dashboards;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Pegawai;
use App\Models\Dokter;
use App\Models\Tindakan;
use App\Models\Jaspel;
use App\Models\Attendance;
use App\Models\JadwalJaga;
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
            $dokter = Dokter::where('user_id', $user->id)
                ->where('aktif', true)
                ->first();
            
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

                // WORLD-CLASS: Use Jaspel model for consistent calculation with Jaspel page
                $jaspelMonth = \App\Models\Jaspel::where('user_id', $user->id)
                    ->whereMonth('tanggal', $thisMonth->month)
                    ->whereYear('tanggal', $thisMonth->year)
                    ->whereIn('status_validasi', ['disetujui', 'approved'])
                    ->sum('nominal');

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
                        'jenis_pegawai' => $dokter->jenis_pegawai,
                        'unit_kerja' => $dokter->unit_kerja ?? 'Tidak ditentukan',
                        'avatar' => null,
                        'initials' => strtoupper(substr($user->name, 0, 2))
                    ],
                    'dokter' => [
                        'id' => $dokter->id,
                        'nama_lengkap' => $dokter->nama_lengkap ?? $user->name,
                        'nik' => $dokter->nik,
                        'jenis_pegawai' => $dokter->jenis_pegawai,
                        'unit_kerja' => $dokter->unit_kerja,
                        'status' => 'Aktif'
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
                ->orderBy('tanggal_jaga')
                ->get();

            // Format untuk calendar dengan fallback
            $calendarEvents = $jadwalJaga->map(function ($jadwal) {
                return [
                    'id' => $jadwal->id,
                    'title' => 'Shift Jaga',
                    'start' => $jadwal->tanggal_jaga->format('Y-m-d'),
                    'end' => $jadwal->tanggal_jaga->format('Y-m-d'),
                    'color' => '#10b981',
                    'description' => $jadwal->unit_kerja ?? 'Unit Kerja',
                    'shift_info' => [
                        'nama_shift' => 'Shift Pagi',
                        'jam_masuk' => '08:00',
                        'jam_pulang' => '16:00',
                        'unit_kerja' => $jadwal->unit_kerja ?? 'Unit Kerja',
                        'status' => 'aktif'
                    ]
                ];
            });

            // Jadwal minggu ini
            $weeklySchedule = JadwalJaga::where('pegawai_id', $user->id)
                ->whereBetween('tanggal_jaga', [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek()
                ])
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
     * Detail jaspel dokter - WORLD-CLASS implementation with Jaspel model integration
     */
    public function getJaspel(Request $request)
    {
        try {
            $user = Auth::user();
            $dokter = Dokter::where('user_id', $user->id)
                ->where('aktif', true)
                ->first();
            
            if (!$dokter) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data dokter tidak ditemukan',
                    'data' => null
                ], 404);
            }
            
            $month = $request->get('month', Carbon::now()->month);
            $year = $request->get('year', Carbon::now()->year);

            // WORLD-CLASS: Use Jaspel model with multi-status support
            $jaspelQuery = Jaspel::where('user_id', $user->id)
                ->whereMonth('tanggal', $month)
                ->whereYear('tanggal', $year)
                ->whereHas('tindakan', function($query) {
                    $query->whereIn('status_validasi', ['disetujui', 'approved']);
                });

            // WORLD-CLASS: Enhanced pending calculation including bendahara validation queue
            $pendingJaspelRecords = (clone $jaspelQuery)->where('status_validasi', 'pending')->sum('nominal');
            
            // Add approved Tindakan awaiting Jaspel generation (dokter portion)
            $pendingFromTindakan = \App\Models\Tindakan::where('dokter_id', $dokter->id)
                ->whereMonth('tanggal_tindakan', $month)
                ->whereYear('tanggal_tindakan', $year)
                ->whereIn('status_validasi', ['approved', 'disetujui'])
                ->whereDoesntHave('jaspel', function($query) use ($user) {
                    $query->where('user_id', $user->id)
                          ->whereIn('jenis_jaspel', ['dokter_umum', 'dokter_spesialis']);
                })
                ->where('jasa_dokter', '>', 0)
                ->sum('jasa_dokter');
                
            $totalPending = $pendingJaspelRecords + $pendingFromTindakan; // Dokter gets 100% of jasa_dokter
            
            // Clone queries to avoid interference with multi-status support
            $jaspelStats = [
                'total' => (clone $jaspelQuery)->sum('nominal'),
                'disetujui' => (clone $jaspelQuery)->whereIn('status_validasi', ['disetujui', 'approved'])->sum('nominal'),
                'pending' => $totalPending,
                'pending_breakdown' => [
                    'jaspel_records' => $pendingJaspelRecords,
                    'tindakan_awaiting_jaspel' => $pendingFromTindakan,
                    'total' => $totalPending
                ],
                'rejected' => (clone $jaspelQuery)->whereIn('status_validasi', ['ditolak', 'rejected'])->sum('nominal'),
                'count_jaspel' => (clone $jaspelQuery)->count()
            ];

            // WORLD-CLASS: Enhanced breakdown with proper Jaspel relations
            $dailyBreakdown = (clone $jaspelQuery)->select(
                DB::raw('DATE(tanggal) as date'),
                DB::raw('COUNT(*) as total_jaspel'),
                DB::raw('SUM(nominal) as total_amount'),
                DB::raw('SUM(CASE WHEN status_validasi = "disetujui" THEN nominal ELSE 0 END) as disetujui_amount')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

            // WORLD-CLASS: Breakdown by Jaspel type with enhanced data
            $jaspelTypeBreakdown = (clone $jaspelQuery)->select(
                'jenis_jaspel',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(nominal) as total_amount'),
                DB::raw('AVG(nominal) as avg_amount')
            )
            ->groupBy('jenis_jaspel')
            ->orderByDesc('total_amount')
            ->get();

            // WORLD-CLASS: Recent Jaspel with full relation data
            $recentJaspel = (clone $jaspelQuery)->with([
                'tindakan.jenisTindakan:id,nama',
                'tindakan.pasien:id,nama',
                'validasiBy:id,name'
            ])
                ->orderByDesc('tanggal')
                ->limit(10)
                ->get()
                ->map(function($jaspel) {
                    $tindakan = $jaspel->tindakan;
                    return [
                        'id' => $jaspel->id,
                        'tanggal' => $jaspel->tanggal->format('Y-m-d'),
                        'nominal' => $jaspel->nominal,
                        'jenis_jaspel' => $jaspel->jenis_jaspel,
                        'status_validasi' => $jaspel->status_validasi,
                        'jenis_tindakan' => $tindakan && $tindakan->jenisTindakan ? $tindakan->jenisTindakan->nama : null,
                        'pasien_nama' => $tindakan && $tindakan->pasien ? $tindakan->pasien->nama : null,
                        'validator' => $jaspel->validasiBy ? $jaspel->validasiBy->name : null,
                        'validated_at' => $jaspel->validasi_at ? $jaspel->validasi_at->format('Y-m-d H:i') : null
                    ];
                });

            // WORLD-CLASS: Additional statistics
            $performanceMetrics = [
                'avg_jaspel_per_day' => $jaspelStats['count_jaspel'] > 0 ? round($jaspelStats['total'] / max(1, Carbon::now()->day), 0) : 0,
                'highest_daily_earning' => $dailyBreakdown->max('disetujui_amount') ?? 0,
                'most_profitable_type' => $jaspelTypeBreakdown->first()->jenis_jaspel ?? null,
                'total_validated_tindakan' => Jaspel::where('user_id', $user->id)
                    ->whereHas('tindakan', function($q) { $q->where('status_validasi', 'disetujui'); })
                    ->count()
            ];

            // Transform data for mobile app compatibility
            $jaspelItems = $recentJaspel->map(function($item) {
                return [
                    'id' => (string)$item['id'],
                    'tanggal' => $item['tanggal'],
                    'jenis' => $item['jenis_jaspel'] ?? 'Jaspel Dokter',
                    'jumlah' => $item['nominal'],
                    'status' => $item['status_validasi'] === 'disetujui' ? 'paid' : 
                               ($item['status_validasi'] === 'pending' ? 'pending' : 'rejected'),
                    'keterangan' => $item['jenis_tindakan'] ?? 'Tindakan Medis',
                    'validated_by' => $item['validator'],
                    'validated_at' => $item['validated_at']
                ];
            });

            $summary = [
                'total_paid' => $jaspelStats['disetujui'],
                'total_pending' => $jaspelStats['pending'],
                'total_rejected' => $jaspelStats['rejected'],
                'count_paid' => (clone $jaspelQuery)->where('status_validasi', 'disetujui')->count(),
                'count_pending' => (clone $jaspelQuery)->where('status_validasi', 'pending')->count(),
                'count_rejected' => (clone $jaspelQuery)->where('status_validasi', 'ditolak')->count()
            ];

            return response()->json([
                'success' => true,
                'message' => 'Data jaspel dokter berhasil dimuat',
                'data' => [
                    'jaspel_items' => $jaspelItems,
                    'summary' => $summary,
                    'stats' => $jaspelStats,
                    'daily_breakdown' => $dailyBreakdown,
                    'jaspel_type_breakdown' => $jaspelTypeBreakdown,
                    'performance_metrics' => $performanceMetrics
                ],
                'meta' => [
                    'month' => $month,
                    'year' => $year,
                    'user_name' => $user->name,
                    'dokter_id' => $dokter->id,
                    'dokter_nama' => $dokter->nama_lengkap ?? $user->name,
                    'specialization' => $dokter->spesialisasi ?? 'Umum',
                    'version' => '2.0',
                    'timestamp' => now()->toISOString()
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
            $dokter = Dokter::where('user_id', $user->id)
                ->where('aktif', true)
                ->first();
            
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
                        'disetujui' => Tindakan::where('dokter_id', $dokter->id)->where('status_validasi', 'disetujui')->count(),
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
     * Check-in/Check-out
     */
    public function checkIn(Request $request)
    {
        try {
            $user = Auth::user();
            $today = Carbon::today();
            
            // Cek apakah sudah check-in hari ini
            $attendance = Attendance::where('user_id', $user->id)
                ->whereDate('date', $today)
                ->first();

            if ($attendance && $attendance->time_in) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah check-in hari ini'
                ], 422);
            }

            // Buat record attendance
            $attendance = Attendance::updateOrCreate([
                'user_id' => $user->id,
                'date' => $today
            ], [
                'time_in' => Carbon::now(),
                'location_in' => $request->get('location'),
                'latitude_in' => $request->get('latitude'),
                'longitude_in' => $request->get('longitude'),
                'status' => 'on_time'
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
            $today = Carbon::today();
            
            $attendance = Attendance::where('user_id', $user->id)
                ->whereDate('date', $today)
                ->whereNotNull('time_in')
                ->whereNull('time_out')
                ->first();

            if (!$attendance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Belum check-in atau sudah check-out'
                ], 422);
            }

            $attendance->update([
                'time_out' => Carbon::now(),
                'location_out' => $request->get('location'),
                'latitude_out' => $request->get('latitude'),
                'longitude_out' => $request->get('longitude')
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
     * Endpoint untuk schedule API (untuk mobile app)
     */
    public function schedules(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Get upcoming schedules for mobile app
            $schedules = JadwalJaga::where('pegawai_id', $user->id)
                ->where('tanggal_jaga', '>=', Carbon::today())
                ->orderBy('tanggal_jaga')
                ->limit(10)
                ->get()
                ->map(function ($jadwal) {
                    return [
                        'id' => $jadwal->id,
                        'tanggal' => $jadwal->tanggal_jaga->format('Y-m-d'),
                        'waktu' => '08:00 - 16:00', // Default fallback
                        'lokasi' => $jadwal->unit_kerja ?? 'Unit Kerja',
                        'jenis' => 'pagi', // Default fallback
                        'status' => 'scheduled'
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
     * Get performance stats
     */
    private function getPerformanceStats($dokter)
    {
        try {
            $month = Carbon::now()->month;
            $year = Carbon::now()->year;
            $user = Auth::user();
            
            // Get attendance ranking from AttendanceRecap with error handling
            $attendanceData = collect(); // Default empty collection
            $attendanceRate = 0; // Default rate
            
            try {
                $attendanceData = \App\Models\AttendanceRecap::getRecapData($month, $year, 'Dokter');
                $attendanceRate = $this->getAttendanceRate($user);
            } catch (\Exception $e) {
                \Log::warning('AttendanceRecap error in getPerformanceStats', [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id,
                    'month' => $month,
                    'year' => $year
                ]);
                // Continue with default values
            }
            
            // Find current user's ranking
            $currentUserRank = null;
            $totalDokter = $attendanceData->count();
            
            foreach ($attendanceData as $staff) {
                if ($staff['staff_id'] == $user->id) {
                    $currentUserRank = $staff['rank'];
                    break;
                }
            }
            
            // Debug logging
            \Log::info('🔍 DEBUG: getPerformanceStats', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'month' => $month,
                'year' => $year,
                'attendance_data_count' => $attendanceData->count(),
                'current_user_rank' => $currentUserRank,
                'total_dokter' => $totalDokter,
                'attendance_rate' => $attendanceRate,
            ]);
            
            return [
                'attendance_rank' => $currentUserRank ?? max($totalDokter + 1, 1),
                'total_staff' => max($totalDokter, 1),
                'attendance_percentage' => round($attendanceRate, 1),
                'patient_satisfaction' => 92,
                'attendance_rate' => $attendanceRate
            ];
        } catch (\Exception $e) {
            \Log::error('getPerformanceStats complete failure', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return safe defaults
            return [
                'attendance_rank' => 1,
                'total_staff' => 1,
                'attendance_percentage' => 0,
                'patient_satisfaction' => 92,
                'attendance_rate' => 0
            ];
        }
    }

    /**
     * Get attendance rate using AttendanceRecap calculation method
     */
    private function getAttendanceRate($user)
    {
        try {
            $month = Carbon::now()->month;
            $year = Carbon::now()->year;
            
            \Log::info('🔍 DEBUG: getAttendanceRate start', [
                'user_id' => $user->id,
                'month' => $month,
                'year' => $year,
            ]);
            
            // Try to get attendance data from AttendanceRecap for current user
            try {
                $attendanceData = \App\Models\AttendanceRecap::getRecapData($month, $year, 'Dokter');
                
                \Log::info('🔍 DEBUG: AttendanceRecap data', [
                    'count' => $attendanceData->count(),
                ]);
                
                // Find current user's attendance percentage
                foreach ($attendanceData as $staff) {
                    if ($staff['staff_id'] == $user->id) {
                        \Log::info('✅ Found user attendance', [
                            'attendance_percentage' => $staff['attendance_percentage'],
                        ]);
                        return $staff['attendance_percentage'];
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('AttendanceRecap query failed in getAttendanceRate', [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id
                ]);
                // Continue to fallback calculation
            }
            
            \Log::info('🔄 Using fallback calculation');
            
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
            
            // Count attendance days for the full month with error handling
            $attendanceDays = 0;
            try {
                $attendanceDays = Attendance::where('user_id', $user->id)
                    ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                    ->distinct('date')
                    ->count();
            } catch (\Exception $e) {
                \Log::warning('Attendance table query failed', [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id
                ]);
                // Return 0 as safe default
                return 0;
            }
            
            $fallbackRate = $workingDays > 0 ? round(($attendanceDays / $workingDays) * 100, 2) : 0;
            
            \Log::info('🔍 DEBUG: Fallback calculation', [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'working_days' => $workingDays,
                'attendance_days' => $attendanceDays,
                'fallback_rate' => $fallbackRate,
            ]);
            
            return $fallbackRate;
        } catch (\Exception $e) {
            \Log::error('getAttendanceRate complete failure', [
                'error' => $e->getMessage(),
                'user_id' => $user->id ?? 'unknown'
            ]);
            
            // Return safe default
            return 0;
        }
    }

    /**
     * Get next schedule
     */
    private function getNextSchedule($user)
    {
        try {
            $nextSchedule = JadwalJaga::where('pegawai_id', $user->id)
                ->where('tanggal_jaga', '>=', Carbon::today())
                ->orderBy('tanggal_jaga')
                ->first();

            if (!$nextSchedule) {
                return null;
            }

            return [
                'id' => $nextSchedule->id,
                'date' => $nextSchedule->tanggal_jaga->format('Y-m-d'),
                'formatted_date' => $nextSchedule->tanggal_jaga->format('l, d F Y'),
                'shift_name' => 'Shift Pagi', // Fallback
                'start_time' => '08:00',
                'end_time' => '16:00',
                'unit_kerja' => $nextSchedule->unit_kerja ?? 'Unit Kerja',
                'days_until' => Carbon::today()->diffInDays($nextSchedule->tanggal_jaga)
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get IGD schedules with dynamic unit kerja data
     * Same implementation as DokterDashboardController for consistency
     */
    public function getIgdSchedules(Request $request)
    {
        try {
            $user = Auth::user();
            $category = $request->get('category', 'all');
            $date = $request->get('date', now()->format('Y-m-d'));
            
            // Map category to unit_kerja values - same as dokter implementation
            $unitKerjaMap = [
                'all' => ['Pendaftaran', 'Pelayanan', 'Dokter Jaga'],
                'pendaftaran' => ['Pendaftaran'],
                'pelayanan' => ['Pelayanan'],
                'dokter_jaga' => ['Dokter Jaga']
            ];
            
            $unitKerjaFilter = $unitKerjaMap[$category] ?? $unitKerjaMap['all'];
            
            // For dokter, only include "Dokter Jaga" unit
            $unitKerjaFilter = ['Dokter Jaga'];
            
            // SECURITY FIX: Only show schedules for the logged-in user
            $query = JadwalJaga::with(['pegawai', 'shiftTemplate'])
                ->join('pegawais', 'jadwal_jagas.pegawai_id', '=', 'pegawais.user_id')
                ->join('users', 'pegawais.user_id', '=', 'users.id')
                ->leftJoin('shift_templates', 'jadwal_jagas.shift_template_id', '=', 'shift_templates.id')
                ->where('jadwal_jagas.pegawai_id', $user->id)
                ->where('pegawais.jenis_pegawai', 'Dokter')
                ->whereIn('jadwal_jagas.unit_kerja', $unitKerjaFilter)
                ->whereDate('jadwal_jagas.tanggal_jaga', $date)
                ->select([
                    'jadwal_jagas.*',
                    'users.name as nama_dokter',
                    'shift_templates.nama_shift as shift_name',
                    'shift_templates.jam_masuk',
                    'shift_templates.jam_pulang'
                ])
                ->orderByRaw("
                    FIELD(jadwal_jagas.unit_kerja, 'Pendaftaran', 'Pelayanan', 'Dokter Jaga'),
                    CASE 
                        WHEN shift_templates.nama_shift = 'Pagi' THEN 1
                        WHEN shift_templates.nama_shift = 'Siang' THEN 2
                        WHEN shift_templates.nama_shift = 'Malam' THEN 3
                        ELSE 4
                    END,
                    users.name ASC
                ");

            $schedules = $query->get()->map(function($schedule) {
                // Format time display
                $timeDisplay = 'TBA';
                if ($schedule->jam_masuk && $schedule->jam_pulang) {
                    $timeDisplay = Carbon::parse($schedule->jam_masuk)->format('H:i') . 
                                  ' - ' . 
                                  Carbon::parse($schedule->jam_pulang)->format('H:i');
                }

                return [
                    'id' => $schedule->id,
                    'tanggal' => Carbon::parse($schedule->tanggal_jaga)->format('Y-m-d'),
                    'tanggal_formatted' => Carbon::parse($schedule->tanggal_jaga)->format('l, d F Y'),
                    'unit_kerja' => $schedule->unit_kerja ?: 'Unit Kerja',
                    'dokter_name' => $schedule->nama_dokter ?: 'Unknown',
                    'shift_name' => $schedule->shift_name ?: 'Shift',
                    'jam_masuk' => $schedule->jam_masuk,
                    'jam_keluar' => $schedule->jam_pulang,
                    'waktu_display' => $timeDisplay,
                    'status' => $schedule->status_jaga ?? 'scheduled',
                    'created_at' => $schedule->created_at
                ];
            });

            // Group by unit_kerja for better organization
            $groupedSchedules = $schedules->groupBy('unit_kerja');

            return response()->json([
                'success' => true,
                'message' => 'Jadwal dokter berhasil dimuat',
                'data' => [
                    'schedules' => $schedules,
                    'grouped_schedules' => $groupedSchedules,
                    'category' => $category,
                    'date' => $date,
                    'total_count' => $schedules->count(),
                    'units_available' => $schedules->pluck('unit_kerja')->unique()->values(),
                    'filters_applied' => [
                        'unit_kerja' => $unitKerjaFilter,
                        'date' => $date,
                        'staff_type' => 'Dokter'
                    ]
                ],
                'meta' => [
                    'version' => '2.0',
                    'timestamp' => now()->toISOString(),
                    'request_id' => \Illuminate\Support\Str::uuid()->toString(),
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('DokterDashboardController::getIgdSchedules error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat jadwal dokter: ' . $e->getMessage(),
                'data' => [
                    'schedules' => [],
                    'grouped_schedules' => [],
                    'total_count' => 0
                ]
            ], 500);
        }
    }

    /**
     * Get weekly schedules with dynamic data
     * Same pattern as dokter implementation
     */
    public function getWeeklySchedule(Request $request)
    {
        try {
            $user = Auth::user();
            $startDate = Carbon::now()->startOfWeek();
            $endDate = Carbon::now()->endOfWeek();
            
            // SECURITY FIX: Only show schedules for the logged-in user
            $schedules = JadwalJaga::with(['shiftTemplate'])
                ->join('pegawais', 'jadwal_jagas.pegawai_id', '=', 'pegawais.user_id')
                ->where('jadwal_jagas.pegawai_id', $user->id)
                ->where('pegawais.jenis_pegawai', 'Dokter')
                ->where('jadwal_jagas.unit_kerja', 'Dokter Jaga') // INCLUDE only Dokter Jaga
                ->whereBetween('jadwal_jagas.tanggal_jaga', [
                    $startDate->format('Y-m-d'),
                    $endDate->format('Y-m-d')
                ])
                ->select([
                    'jadwal_jagas.*',
                    'pegawais.nama_lengkap as nama_dokter'
                ])
                ->orderBy('jadwal_jagas.tanggal_jaga')
                ->orderByRaw("FIELD(jadwal_jagas.unit_kerja, 'Pendaftaran', 'Pelayanan', 'Dokter Jaga')")
                ->get()
                ->map(function($schedule) {
                    return [
                        'id' => $schedule->id,
                        'tanggal' => Carbon::parse($schedule->tanggal_jaga)->format('Y-m-d'),
                        'unit_kerja' => $schedule->unit_kerja ?: 'Unit Kerja',
                        'dokter_name' => $schedule->nama_dokter ?: 'Unknown',
                        'shift_name' => $schedule->shiftTemplate ? $schedule->shiftTemplate->nama_shift : 'Shift',
                        'jam_masuk' => $schedule->shiftTemplate ? $schedule->shiftTemplate->jam_masuk : null,
                        'jam_keluar' => $schedule->shiftTemplate ? $schedule->shiftTemplate->jam_pulang : null,
                        'status' => $schedule->status_jaga ?? 'scheduled'
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Jadwal minggu ini berhasil dimuat',
                'data' => [
                    'schedules' => $schedules,
                    'week_range' => [
                        'start' => $startDate->format('Y-m-d'),
                        'end' => $endDate->format('Y-m-d'),
                        'start_formatted' => $startDate->format('d M Y'),
                        'end_formatted' => $endDate->format('d M Y')
                    ],
                    'total_count' => $schedules->count()
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('DokterDashboardController::getWeeklySchedule error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat jadwal minggu ini: ' . $e->getMessage(),
                'data' => [
                    'schedules' => [],
                    'total_count' => 0
                ]
            ], 500);
        }
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
     * Force refresh work location data by clearing relevant caches
     */
    public function refreshWorkLocation(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Clear relevant caches
            $cacheKeys = [
                "dokter_dashboard_stats_{$user->id}",
                "user_work_location_{$user->id}",
                "attendance_status_{$user->id}"
            ];
            
            foreach ($cacheKeys as $key) {
                Cache::forget($key);
            }
            
            // Force reload user work location
            $user->load('workLocation');
            
            return response()->json([
                'success' => true,
                'message' => 'Work location data refreshed successfully',
                'data' => [
                    'work_location' => $user->workLocation ? [
                        'id' => $user->workLocation->id,
                        'name' => $user->workLocation->name,
                        'address' => $user->workLocation->address,
                        'coordinates' => [
                            'latitude' => (float) $user->workLocation->latitude,
                            'longitude' => (float) $user->workLocation->longitude,
                        ],
                        'radius_meters' => $user->workLocation->radius_meters,
                        'is_active' => $user->workLocation->is_active,
                        'updated_at' => $user->workLocation->updated_at->toISOString(),
                    ] : null,
                    'cache_cleared' => $cacheKeys,
                    'timestamp' => now()->toISOString()
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh work location: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get work location status only (lightweight endpoint for polling)
     */
    public function getWorkLocationStatus(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Get fresh work location data
            $user->load('workLocation');
            $workLocation = $user->workLocation?->fresh();

            return response()->json([
                'success' => true,
                'message' => 'Work location status retrieved',
                'data' => [
                    'work_location' => $workLocation ? [
                        'id' => $workLocation->id,
                        'name' => $workLocation->name,
                        'address' => $workLocation->address,
                        'coordinates' => [
                            'latitude' => (float) $workLocation->latitude,
                            'longitude' => (float) $workLocation->longitude,
                        ],
                        'radius_meters' => $workLocation->radius_meters,
                        'is_active' => $workLocation->is_active,
                        'updated_at' => $workLocation->updated_at?->toISOString(),
                    ] : null,
                    'user_id' => $user->id,
                    'timestamp' => now()->toISOString(),
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching work location status', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch work location status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check and auto-assign work location if available
     */
    public function checkAndAssignWorkLocation(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Check if user already has work location
            if ($user->work_location_id) {
                $workLocation = \App\Models\WorkLocation::find($user->work_location_id);
                if ($workLocation && $workLocation->is_active) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Work location already assigned',
                        'data' => [
                            'work_location' => [
                                'id' => $workLocation->id,
                                'name' => $workLocation->name,
                                'address' => $workLocation->address,
                                'is_active' => $workLocation->is_active
                            ]
                        ]
                    ]);
                }
            }
            
            // Try to find a suitable work location based on user's dokter data
            $dokter = $user->dokter;
            if (!$dokter) {
                // Try pegawai relation if dokter not found
                $pegawai = $user->pegawai;
                if (!$pegawai) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No dokter or pegawai data found for user',
                        'data' => null
                    ], 404);
                }
                
                // Use pegawai data for matching
                $workLocation = \App\Models\WorkLocation::where('is_active', true)
                    ->where(function($query) use ($pegawai) {
                        if ($pegawai->unit_kerja) {
                            $query->where('name', 'LIKE', '%' . $pegawai->unit_kerja . '%')
                                  ->orWhere('unit_kerja', $pegawai->unit_kerja);
                        }
                        if ($pegawai->jenis_pegawai) {
                            $query->orWhere('location_type', $pegawai->jenis_pegawai);
                        }
                    })
                    ->first();
            } else {
                // Look for work location based on dokter's unit_kerja or specialization
                $workLocation = \App\Models\WorkLocation::where('is_active', true)
                    ->where(function($query) use ($dokter) {
                        // Try to match by unit kerja
                        if ($dokter->unit_kerja) {
                            $query->where('name', 'LIKE', '%' . $dokter->unit_kerja . '%')
                                  ->orWhere('unit_kerja', $dokter->unit_kerja);
                        }
                        // Try to match by location type
                        $query->orWhere('location_type', 'Dokter')
                              ->orWhere('location_type', 'dokter');
                    })
                    ->first();
            }
            
            // If no match found, get the first active work location (default)
            if (!$workLocation) {
                $workLocation = \App\Models\WorkLocation::where('is_active', true)
                    ->orderBy('created_at', 'asc')
                    ->first();
            }
            
            if ($workLocation) {
                // Assign work location to user
                $user->work_location_id = $workLocation->id;
                $user->save();
                
                // Clear caches
                $cacheKeys = [
                    "dokter_dashboard_stats_{$user->id}",
                    "user_work_location_{$user->id}",
                    "attendance_status_{$user->id}"
                ];
                
                foreach ($cacheKeys as $key) {
                    Cache::forget($key);
                }
                
                return response()->json([
                    'success' => true,
                    'message' => 'Work location assigned successfully',
                    'data' => [
                        'work_location' => [
                            'id' => $workLocation->id,
                            'name' => $workLocation->name,
                            'address' => $workLocation->address,
                            'coordinates' => [
                                'latitude' => (float) $workLocation->latitude,
                                'longitude' => (float) $workLocation->longitude,
                            ],
                            'radius_meters' => $workLocation->radius_meters,
                            'is_active' => $workLocation->is_active
                        ],
                        'assignment_reason' => $dokter && $dokter->unit_kerja ? 
                            "Matched by unit kerja: {$dokter->unit_kerja}" : 
                            'Assigned default active location'
                    ]
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'No active work location found in the system',
                'data' => null
            ], 404);
            
        } catch (\Exception $e) {
            \Log::error('Error in checkAndAssignWorkLocation', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to check/assign work location: ' . $e->getMessage()
            ], 500);
        }
    }
}