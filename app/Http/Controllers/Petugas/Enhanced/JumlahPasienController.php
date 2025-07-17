<?php

namespace App\Http\Controllers\Petugas\Enhanced;

use App\Http\Controllers\Controller;
use App\Models\Pasien;
use App\Models\Tindakan;
use App\Services\PetugasDataService;
use App\Services\PetugasStatsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class JumlahPasienController extends Controller
{
    protected $dataService;
    protected $statsService;

    public function __construct(PetugasDataService $dataService, PetugasStatsService $statsService)
    {
        $this->dataService = $dataService;
        $this->statsService = $statsService;
        
        // Apply role-based middleware
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!auth()->check() || !auth()->user()->hasRole('petugas')) {
                abort(403, 'Access denied. Petugas role required.');
            }
            return $next($request);
        });
    }

    /**
     * Display enhanced patient reporting dashboard with calendar view
     */
    public function index(): View
    {
        // Get patient statistics
        $stats = $this->getPatientStats();
        
        // Get monthly trends
        $monthlyTrends = $this->getMonthlyTrends();
        
        // Get age distribution
        $ageDistribution = $this->getAgeDistribution();
        
        // Get recent activity
        $recentActivity = $this->getRecentActivity();

        return view('petugas.enhanced.jumlah-pasien.index', compact(
            'stats', 'monthlyTrends', 'ageDistribution', 'recentActivity'
        ));
    }

    /**
     * Get calendar data for patients
     */
    public function getCalendarData(Request $request): JsonResponse
    {
        try {
            $month = $request->get('month', Carbon::now()->month);
            $year = $request->get('year', Carbon::now()->year);
            $view = $request->get('view', 'registration'); // registration, visits, procedures

            $calendarData = [];
            $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();

            switch ($view) {
                case 'registration':
                    $calendarData = $this->getRegistrationCalendar($startDate, $endDate);
                    break;
                case 'visits':
                    $calendarData = $this->getVisitsCalendar($startDate, $endDate);
                    break;
                case 'procedures':
                    $calendarData = $this->getProceduresCalendar($startDate, $endDate);
                    break;
            }

            return response()->json([
                'success' => true,
                'data' => $calendarData,
                'meta' => [
                    'month' => $month,
                    'year' => $year,
                    'view' => $view,
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Enhanced JumlahPasien calendar data error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data kalender: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detailed stats for specific date
     */
    public function getDateStats(Request $request): JsonResponse
    {
        try {
            $date = $request->get('date');
            $type = $request->get('type', 'all'); // all, new, returning, procedures

            if (!$date) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tanggal harus diisi'
                ], 422);
            }

            $carbonDate = Carbon::parse($date);
            $stats = $this->getDetailedDateStats($carbonDate, $type);

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Enhanced JumlahPasien date stats error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat statistik tanggal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get analytics data for charts
     */
    public function getAnalytics(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', 'month'); // week, month, quarter, year
            $type = $request->get('type', 'overview'); // overview, age, gender, location

            $analytics = $this->generateAnalytics($period, $type);

            return response()->json([
                'success' => true,
                'data' => $analytics
            ]);

        } catch (\Exception $e) {
            Log::error('Enhanced JumlahPasien analytics error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat analytics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export patient report
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $format = $request->get('format', 'excel'); // excel, pdf, csv
            $period = $request->get('period', 'month');
            $includeCharts = $request->get('include_charts', false);

            // This would typically generate an actual file
            // For now, return success message
            return response()->json([
                'success' => true,
                'message' => 'Export akan segera tersedia',
                'download_url' => '#'
            ]);

        } catch (\Exception $e) {
            Log::error('Enhanced JumlahPasien export error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengekspor laporan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get patient statistics
     */
    private function getPatientStats(): array
    {
        return Cache::remember('enhanced_patient_stats', 300, function () {
            $today = Carbon::today();
            $thisMonth = Carbon::now()->startOfMonth();
            $lastMonth = Carbon::now()->subMonth()->startOfMonth();
            $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

            // Daily stats
            $todayNew = Pasien::whereDate('created_at', $today)->count();
            $todayVisits = Tindakan::whereDate('tanggal_tindakan', $today)->distinct('pasien_id')->count();

            // Monthly stats
            $monthNew = Pasien::where('created_at', '>=', $thisMonth)->count();
            $monthTotal = Pasien::count();
            $lastMonthNew = Pasien::whereBetween('created_at', [$lastMonth, $lastMonthEnd])->count();

            // Growth calculation
            $growth = $lastMonthNew > 0 ? (($monthNew - $lastMonthNew) / $lastMonthNew) * 100 : 0;

            // Active patients (had procedures in last 30 days)
            $activePatients = Tindakan::where('tanggal_tindakan', '>=', Carbon::now()->subDays(30))
                                   ->distinct('pasien_id')
                                   ->count();

            // Average age
            $avgAge = Pasien::whereNotNull('tanggal_lahir')
                          ->get()
                          ->avg(function ($pasien) {
                              return Carbon::parse($pasien->tanggal_lahir)->age;
                          });

            return [
                'today_new' => $todayNew,
                'today_visits' => $todayVisits,
                'month_new' => $monthNew,
                'month_total' => $monthTotal,
                'growth_percentage' => round($growth, 2),
                'active_patients' => $activePatients,
                'avg_age' => round($avgAge ?? 0, 1),
                'retention_rate' => $monthTotal > 0 ? round(($activePatients / $monthTotal) * 100, 1) : 0,
                'gender_stats' => $this->getGenderStats(),
                'top_procedures' => $this->getTopProcedures(),
            ];
        });
    }

    /**
     * Get monthly patient trends
     */
    private function getMonthlyTrends(): array
    {
        return Cache::remember('monthly_patient_trends', 900, function () {
            $trends = [];
            for ($i = 11; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $startMonth = $date->copy()->startOfMonth();
                $endMonth = $date->copy()->endOfMonth();

                $newPatients = Pasien::whereBetween('created_at', [$startMonth, $endMonth])->count();
                $visits = Tindakan::whereBetween('tanggal_tindakan', [$startMonth, $endMonth])
                                ->distinct('pasien_id')
                                ->count();

                $trends[] = [
                    'month' => $date->format('Y-m'),
                    'formatted_month' => $date->format('M Y'),
                    'new_patients' => $newPatients,
                    'total_visits' => $visits,
                    'unique_visitors' => $visits,
                ];
            }
            return $trends;
        });
    }

    /**
     * Get age distribution
     */
    private function getAgeDistribution(): array
    {
        return Cache::remember('patient_age_distribution', 1800, function () {
            $ageGroups = [
                '0-10' => 0,
                '11-20' => 0,
                '21-30' => 0,
                '31-40' => 0,
                '41-50' => 0,
                '51-60' => 0,
                '60+' => 0,
            ];

            $patients = Pasien::whereNotNull('tanggal_lahir')->get();

            foreach ($patients as $patient) {
                $age = Carbon::parse($patient->tanggal_lahir)->age;
                
                if ($age <= 10) {
                    $ageGroups['0-10']++;
                } elseif ($age <= 20) {
                    $ageGroups['11-20']++;
                } elseif ($age <= 30) {
                    $ageGroups['21-30']++;
                } elseif ($age <= 40) {
                    $ageGroups['31-40']++;
                } elseif ($age <= 50) {
                    $ageGroups['41-50']++;
                } elseif ($age <= 60) {
                    $ageGroups['51-60']++;
                } else {
                    $ageGroups['60+']++;
                }
            }

            return $ageGroups;
        });
    }

    /**
     * Get recent patient activity
     */
    private function getRecentActivity(): array
    {
        return Tindakan::with(['pasien:id,nama_pasien,nomor_pasien', 'jenisTindakan:id,nama_tindakan'])
                      ->orderByDesc('tanggal_tindakan')
                      ->limit(10)
                      ->get()
                      ->map(function ($tindakan) {
                          return [
                              'id' => $tindakan->id,
                              'pasien_nama' => $tindakan->pasien?->nama_pasien,
                              'pasien_nomor' => $tindakan->pasien?->nomor_pasien,
                              'tindakan' => $tindakan->jenisTindakan?->nama_tindakan,
                              'tanggal' => $tindakan->tanggal_tindakan,
                              'formatted_date' => Carbon::parse($tindakan->tanggal_tindakan)->format('d M Y'),
                              'time_ago' => Carbon::parse($tindakan->tanggal_tindakan)->diffForHumans(),
                          ];
                      })
                      ->toArray();
    }

    /**
     * Get registration calendar data
     */
    private function getRegistrationCalendar(Carbon $startDate, Carbon $endDate): array
    {
        $registrations = Pasien::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                             ->whereBetween('created_at', [$startDate, $endDate])
                             ->groupBy('date')
                             ->pluck('count', 'date')
                             ->toArray();

        $calendarData = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $count = $registrations[$dateStr] ?? 0;
            
            $calendarData[] = [
                'date' => $dateStr,
                'count' => $count,
                'display_date' => $currentDate->format('j'),
                'full_date' => $currentDate->format('d M Y'),
                'weekday' => $currentDate->format('l'),
                'intensity' => $this->getIntensityLevel($count, 'registration'),
                'details' => [
                    'new_registrations' => $count,
                    'type' => 'registration'
                ]
            ];
            
            $currentDate->addDay();
        }

        return $calendarData;
    }

    /**
     * Get visits calendar data
     */
    private function getVisitsCalendar(Carbon $startDate, Carbon $endDate): array
    {
        $visits = Tindakan::selectRaw('DATE(tanggal_tindakan) as date, COUNT(DISTINCT pasien_id) as count')
                         ->whereBetween('tanggal_tindakan', [$startDate, $endDate])
                         ->groupBy('date')
                         ->pluck('count', 'date')
                         ->toArray();

        $calendarData = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $count = $visits[$dateStr] ?? 0;
            
            $calendarData[] = [
                'date' => $dateStr,
                'count' => $count,
                'display_date' => $currentDate->format('j'),
                'full_date' => $currentDate->format('d M Y'),
                'weekday' => $currentDate->format('l'),
                'intensity' => $this->getIntensityLevel($count, 'visits'),
                'details' => [
                    'unique_visitors' => $count,
                    'type' => 'visits'
                ]
            ];
            
            $currentDate->addDay();
        }

        return $calendarData;
    }

    /**
     * Get procedures calendar data
     */
    private function getProceduresCalendar(Carbon $startDate, Carbon $endDate): array
    {
        $procedures = Tindakan::selectRaw('DATE(tanggal_tindakan) as date, COUNT(*) as count')
                            ->whereBetween('tanggal_tindakan', [$startDate, $endDate])
                            ->groupBy('date')
                            ->pluck('count', 'date')
                            ->toArray();

        $calendarData = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $count = $procedures[$dateStr] ?? 0;
            
            $calendarData[] = [
                'date' => $dateStr,
                'count' => $count,
                'display_date' => $currentDate->format('j'),
                'full_date' => $currentDate->format('d M Y'),
                'weekday' => $currentDate->format('l'),
                'intensity' => $this->getIntensityLevel($count, 'procedures'),
                'details' => [
                    'total_procedures' => $count,
                    'type' => 'procedures'
                ]
            ];
            
            $currentDate->addDay();
        }

        return $calendarData;
    }

    /**
     * Get detailed stats for specific date
     */
    private function getDetailedDateStats(Carbon $date, string $type): array
    {
        $stats = [
            'date' => $date->format('Y-m-d'),
            'formatted_date' => $date->format('d F Y'),
            'weekday' => $date->format('l'),
        ];

        // New registrations
        $newRegistrations = Pasien::whereDate('created_at', $date)->count();
        $stats['new_registrations'] = $newRegistrations;

        if ($newRegistrations > 0) {
            $stats['new_patients'] = Pasien::whereDate('created_at', $date)
                                          ->take(5)
                                          ->get(['id', 'nama_pasien', 'nomor_pasien', 'created_at'])
                                          ->toArray();
        }

        // Visits/Procedures
        $procedures = Tindakan::with(['pasien:id,nama_pasien,nomor_pasien', 'jenisTindakan:id,nama_tindakan'])
                            ->whereDate('tanggal_tindakan', $date)
                            ->get();

        $stats['total_procedures'] = $procedures->count();
        $stats['unique_visitors'] = $procedures->unique('pasien_id')->count();

        if ($procedures->count() > 0) {
            $stats['procedures'] = $procedures->take(10)->map(function ($tindakan) {
                return [
                    'id' => $tindakan->id,
                    'pasien_nama' => $tindakan->pasien?->nama_pasien,
                    'pasien_nomor' => $tindakan->pasien?->nomor_pasien,
                    'tindakan' => $tindakan->jenisTindakan?->nama_tindakan,
                    'waktu' => $tindakan->created_at->format('H:i'),
                ];
            })->toArray();

            // Procedure breakdown
            $procedureBreakdown = $procedures->groupBy(function ($item) {
                return $item->jenisTindakan?->nama_tindakan ?? 'Lainnya';
            })->map(function ($group) {
                return $group->count();
            })->toArray();

            $stats['procedure_breakdown'] = $procedureBreakdown;
        }

        return $stats;
    }

    /**
     * Get gender statistics
     */
    private function getGenderStats(): array
    {
        $genderStats = Pasien::selectRaw('jenis_kelamin, COUNT(*) as count')
                           ->whereNotNull('jenis_kelamin')
                           ->groupBy('jenis_kelamin')
                           ->pluck('count', 'jenis_kelamin')
                           ->toArray();

        return [
            'male' => $genderStats['L'] ?? 0,
            'female' => $genderStats['P'] ?? 0,
            'total' => array_sum($genderStats),
        ];
    }

    /**
     * Get top procedures
     */
    private function getTopProcedures(): array
    {
        return Tindakan::with('jenisTindakan:id,nama_tindakan')
                      ->where('tanggal_tindakan', '>=', Carbon::now()->subDays(30))
                      ->get()
                      ->groupBy(function ($item) {
                          return $item->jenisTindakan?->nama_tindakan ?? 'Lainnya';
                      })
                      ->map(function ($group) {
                          return $group->count();
                      })
                      ->sortDesc()
                      ->take(5)
                      ->toArray();
    }

    /**
     * Get intensity level for calendar visualization
     */
    private function getIntensityLevel(int $count, string $type): string
    {
        if ($count == 0) return 'none';

        $thresholds = [
            'registration' => [1, 3, 6, 10],
            'visits' => [1, 5, 15, 25],
            'procedures' => [1, 5, 15, 30],
        ];

        $limits = $thresholds[$type] ?? $thresholds['registration'];

        if ($count >= $limits[3]) return 'very-high';
        if ($count >= $limits[2]) return 'high';
        if ($count >= $limits[1]) return 'medium';
        if ($count >= $limits[0]) return 'low';

        return 'none';
    }

    /**
     * Generate analytics data
     */
    private function generateAnalytics(string $period, string $type): array
    {
        // This would generate comprehensive analytics
        // For now, return basic structure
        return [
            'period' => $period,
            'type' => $type,
            'data' => [],
            'summary' => [
                'total_patients' => Pasien::count(),
                'active_patients' => 0,
                'growth_rate' => 0,
            ],
        ];
    }
}