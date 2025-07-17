<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Pasien;
use App\Models\Tindakan;
use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AnalyticsController extends BaseApiController
{
    /**
     * Get dashboard overview analytics
     */
    public function dashboard(Request $request): JsonResponse
    {
        try {
            // Validate permissions
            $permissionCheck = $this->validateApiPermissions(['petugas', 'admin', 'bendahara', 'manajer']);
            if ($permissionCheck !== true) {
                return $permissionCheck;
            }

            $period = $request->get('period', 'month'); // day, week, month, quarter, year
            $cacheKey = "dashboard_analytics_{$period}_" . now()->format('Y-m-d-H');

            $analytics = Cache::remember($cacheKey, 900, function () use ($period) {
                return $this->generateDashboardAnalytics($period);
            });

            $this->logApiActivity('analytics.dashboard', ['period' => $period]);

            return $this->successResponse($analytics, 'Dashboard analytics berhasil dimuat');

        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Error fetching dashboard analytics');
        }
    }

    /**
     * Get patient analytics
     */
    public function patients(Request $request): JsonResponse
    {
        try {
            // Validate permissions
            $permissionCheck = $this->validateApiPermissions(['petugas', 'admin', 'bendahara', 'manajer']);
            if ($permissionCheck !== true) {
                return $permissionCheck;
            }

            $period = $request->get('period', 'month');
            $analytics = $this->generatePatientAnalytics($period);

            $this->logApiActivity('analytics.patients', ['period' => $period]);

            return $this->successResponse($analytics, 'Patient analytics berhasil dimuat');

        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Error fetching patient analytics');
        }
    }

    /**
     * Get financial analytics
     */
    public function financial(Request $request): JsonResponse
    {
        try {
            // Validate permissions
            $permissionCheck = $this->validateApiPermissions(['petugas', 'admin', 'bendahara', 'manajer']);
            if ($permissionCheck !== true) {
                return $permissionCheck;
            }

            $period = $request->get('period', 'month');
            $analytics = $this->generateFinancialAnalytics($period);

            $this->logApiActivity('analytics.financial', ['period' => $period]);

            return $this->successResponse($analytics, 'Financial analytics berhasil dimuat');

        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Error fetching financial analytics');
        }
    }

    /**
     * Get procedure analytics
     */
    public function procedures(Request $request): JsonResponse
    {
        try {
            // Validate permissions
            $permissionCheck = $this->validateApiPermissions(['petugas', 'admin', 'dokter', 'manajer']);
            if ($permissionCheck !== true) {
                return $permissionCheck;
            }

            $period = $request->get('period', 'month');
            $analytics = $this->generateProcedureAnalytics($period);

            $this->logApiActivity('analytics.procedures', ['period' => $period]);

            return $this->successResponse($analytics, 'Procedure analytics berhasil dimuat');

        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Error fetching procedure analytics');
        }
    }

    /**
     * Get trends analytics
     */
    public function trends(Request $request): JsonResponse
    {
        try {
            // Validate permissions
            $permissionCheck = $this->validateApiPermissions(['petugas', 'admin', 'bendahara', 'manajer']);
            if ($permissionCheck !== true) {
                return $permissionCheck;
            }

            $type = $request->get('type', 'revenue'); // revenue, expenses, patients, procedures
            $period = $request->get('period', 'month');
            $granularity = $request->get('granularity', 'day'); // hour, day, week, month

            $trends = $this->generateTrendsAnalytics($type, $period, $granularity);

            $this->logApiActivity('analytics.trends', [
                'type' => $type,
                'period' => $period,
                'granularity' => $granularity
            ]);

            return $this->successResponse($trends, 'Trends analytics berhasil dimuat');

        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Error fetching trends analytics');
        }
    }

    /**
     * Get comparative analytics
     */
    public function comparative(Request $request): JsonResponse
    {
        try {
            // Validate permissions
            $permissionCheck = $this->validateApiPermissions(['admin', 'bendahara', 'manajer']);
            if ($permissionCheck !== true) {
                return $permissionCheck;
            }

            $type = $request->get('type', 'period'); // period, year_over_year, quarter_over_quarter
            $analytics = $this->generateComparativeAnalytics($type);

            $this->logApiActivity('analytics.comparative', ['type' => $type]);

            return $this->successResponse($analytics, 'Comparative analytics berhasil dimuat');

        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Error fetching comparative analytics');
        }
    }

    /**
     * Get performance metrics
     */
    public function performance(Request $request): JsonResponse
    {
        try {
            // Validate permissions
            $permissionCheck = $this->validateApiPermissions(['admin', 'manajer']);
            if ($permissionCheck !== true) {
                return $permissionCheck;
            }

            $period = $request->get('period', 'month');
            $metrics = $this->generatePerformanceMetrics($period);

            $this->logApiActivity('analytics.performance', ['period' => $period]);

            return $this->successResponse($metrics, 'Performance metrics berhasil dimuat');

        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Error fetching performance metrics');
        }
    }

    /**
     * Get custom report
     */
    public function customReport(Request $request): JsonResponse
    {
        try {
            // Validate permissions
            $permissionCheck = $this->validateApiPermissions(['admin', 'manajer']);
            if ($permissionCheck !== true) {
                return $permissionCheck;
            }

            // Validate request
            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'metrics' => 'required|array',
                'metrics.*' => 'in:patients,procedures,revenue,expenses,growth,efficiency',
                'group_by' => 'nullable|in:day,week,month,category,doctor',
                'filters' => 'nullable|array',
            ]);

            $report = $this->generateCustomReport($validated);

            $this->logApiActivity('analytics.customReport', [
                'date_range' => $validated['start_date'] . ' to ' . $validated['end_date'],
                'metrics' => $validated['metrics']
            ]);

            return $this->successResponse($report, 'Custom report berhasil dimuat');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('Data tidak valid', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Error generating custom report');
        }
    }

    /**
     * Generate dashboard analytics
     */
    private function generateDashboardAnalytics(string $period): array
    {
        [$startDate, $endDate] = $this->getPeriodDates($period);

        // Key metrics
        $totalPatients = Pasien::count();
        $newPatients = Pasien::whereBetween('created_at', [$startDate, $endDate])->count();
        $totalProcedures = Tindakan::whereBetween('tanggal_tindakan', [$startDate, $endDate])->count();
        $approvedProcedures = Tindakan::where('status_validasi', 'approved')
                                    ->whereBetween('tanggal_tindakan', [$startDate, $endDate])
                                    ->count();

        $totalRevenue = Pendapatan::whereBetween('tanggal_pendapatan', [$startDate, $endDate])->sum('jumlah');
        $totalExpenses = Pengeluaran::whereBetween('tanggal_pengeluaran', [$startDate, $endDate])->sum('jumlah');
        $netIncome = $totalRevenue - $totalExpenses;

        // Growth calculations
        $previousPeriod = $this->getPreviousPeriod($period);
        [$prevStartDate, $prevEndDate] = $this->getPeriodDates($previousPeriod);

        $prevPatients = Pasien::whereBetween('created_at', [$prevStartDate, $prevEndDate])->count();
        $prevRevenue = Pendapatan::whereBetween('tanggal_pendapatan', [$prevStartDate, $prevEndDate])->sum('jumlah');

        $patientGrowth = $prevPatients > 0 ? (($newPatients - $prevPatients) / $prevPatients) * 100 : 0;
        $revenueGrowth = $prevRevenue > 0 ? (($totalRevenue - $prevRevenue) / $prevRevenue) * 100 : 0;

        // Top procedures
        $topProcedures = Tindakan::with('jenisTindakan:id,nama_tindakan')
                               ->whereBetween('tanggal_tindakan', [$startDate, $endDate])
                               ->where('status_validasi', 'approved')
                               ->get()
                               ->groupBy('jenis_tindakan_id')
                               ->map(function ($group) {
                                   return [
                                       'name' => $group->first()->jenisTindakan?->nama_tindakan ?? 'Unknown',
                                       'count' => $group->count(),
                                       'revenue' => $group->sum('tarif')
                                   ];
                               })
                               ->sortByDesc('count')
                               ->take(5)
                               ->values()
                               ->toArray();

        // Recent activity
        $recentActivity = $this->getRecentActivity();

        return [
            'period' => $period,
            'period_dates' => [
                'start' => $startDate->toISOString(),
                'end' => $endDate->toISOString(),
            ],
            'summary' => [
                'total_patients' => $totalPatients,
                'new_patients' => $newPatients,
                'total_procedures' => $totalProcedures,
                'approved_procedures' => $approvedProcedures,
                'total_revenue' => $totalRevenue,
                'total_expenses' => $totalExpenses,
                'net_income' => $netIncome,
                'approval_rate' => $totalProcedures > 0 ? ($approvedProcedures / $totalProcedures) * 100 : 0,
            ],
            'growth' => [
                'patient_growth' => round($patientGrowth, 2),
                'revenue_growth' => round($revenueGrowth, 2),
            ],
            'top_procedures' => $topProcedures,
            'recent_activity' => $recentActivity,
        ];
    }

    /**
     * Generate patient analytics
     */
    private function generatePatientAnalytics(string $period): array
    {
        [$startDate, $endDate] = $this->getPeriodDates($period);

        // Demographics
        $genderDistribution = Pasien::selectRaw('jenis_kelamin, COUNT(*) as count')
                                  ->groupBy('jenis_kelamin')
                                  ->pluck('count', 'jenis_kelamin')
                                  ->toArray();

        $ageDistribution = $this->calculateAgeDistribution();

        // Registration trends
        $registrationTrends = $this->getRegistrationTrends($startDate, $endDate);

        // Most active patients
        $activePatients = Tindakan::with('pasien:id,nama_pasien,nomor_pasien')
                                ->whereBetween('tanggal_tindakan', [$startDate, $endDate])
                                ->get()
                                ->groupBy('pasien_id')
                                ->map(function ($group) {
                                    return [
                                        'patient' => $group->first()->pasien,
                                        'visit_count' => $group->count(),
                                        'total_cost' => $group->where('status_validasi', 'approved')->sum('tarif'),
                                        'last_visit' => $group->max('tanggal_tindakan'),
                                    ];
                                })
                                ->sortByDesc('visit_count')
                                ->take(10)
                                ->values()
                                ->toArray();

        return [
            'period' => $period,
            'demographics' => [
                'gender_distribution' => [
                    'male' => $genderDistribution['L'] ?? 0,
                    'female' => $genderDistribution['P'] ?? 0,
                ],
                'age_distribution' => $ageDistribution,
            ],
            'registration_trends' => $registrationTrends,
            'active_patients' => $activePatients,
        ];
    }

    /**
     * Generate financial analytics
     */
    private function generateFinancialAnalytics(string $period): array
    {
        [$startDate, $endDate] = $this->getPeriodDates($period);

        // Revenue analysis
        $revenueByCategory = Pendapatan::whereBetween('tanggal_pendapatan', [$startDate, $endDate])
                                     ->get()
                                     ->groupBy(function ($item) {
                                         return $this->categorizeRevenue($item->sumber_pendapatan);
                                     })
                                     ->map(function ($group) {
                                         return [
                                             'total' => $group->sum('jumlah'),
                                             'count' => $group->count(),
                                             'average' => $group->avg('jumlah'),
                                         ];
                                     });

        // Expense analysis
        $expensesByCategory = Pengeluaran::whereBetween('tanggal_pengeluaran', [$startDate, $endDate])
                                       ->selectRaw('kategori, SUM(jumlah) as total, COUNT(*) as count')
                                       ->groupBy('kategori')
                                       ->get()
                                       ->keyBy('kategori')
                                       ->toArray();

        // Profit margins
        $totalRevenue = Pendapatan::whereBetween('tanggal_pendapatan', [$startDate, $endDate])->sum('jumlah');
        $totalExpenses = Pengeluaran::whereBetween('tanggal_pengeluaran', [$startDate, $endDate])->sum('jumlah');
        $profitMargin = $totalRevenue > 0 ? (($totalRevenue - $totalExpenses) / $totalRevenue) * 100 : 0;

        // Cash flow trends
        $cashFlowTrends = $this->getCashFlowTrends($startDate, $endDate);

        return [
            'period' => $period,
            'revenue' => [
                'total' => $totalRevenue,
                'by_category' => $revenueByCategory,
            ],
            'expenses' => [
                'total' => $totalExpenses,
                'by_category' => $expensesByCategory,
            ],
            'profitability' => [
                'net_income' => $totalRevenue - $totalExpenses,
                'profit_margin' => round($profitMargin, 2),
            ],
            'cash_flow_trends' => $cashFlowTrends,
        ];
    }

    /**
     * Generate procedure analytics
     */
    private function generateProcedureAnalytics(string $period): array
    {
        [$startDate, $endDate] = $this->getPeriodDates($period);

        // Procedure volume
        $procedureVolume = Tindakan::whereBetween('tanggal_tindakan', [$startDate, $endDate])
                                 ->selectRaw('status_validasi, COUNT(*) as count')
                                 ->groupBy('status_validasi')
                                 ->pluck('count', 'status_validasi')
                                 ->toArray();

        // Most performed procedures
        $topProcedures = Tindakan::with('jenisTindakan:id,nama_tindakan')
                               ->whereBetween('tanggal_tindakan', [$startDate, $endDate])
                               ->get()
                               ->groupBy('jenis_tindakan_id')
                               ->map(function ($group) {
                                   return [
                                       'name' => $group->first()->jenisTindakan?->nama_tindakan ?? 'Unknown',
                                       'total_count' => $group->count(),
                                       'approved_count' => $group->where('status_validasi', 'approved')->count(),
                                       'total_revenue' => $group->where('status_validasi', 'approved')->sum('tarif'),
                                       'average_tarif' => $group->where('status_validasi', 'approved')->avg('tarif'),
                                   ];
                               })
                               ->sortByDesc('total_count')
                               ->take(10)
                               ->values()
                               ->toArray();

        // Doctor performance
        $doctorPerformance = Tindakan::with('dokter:id,nama_dokter')
                                   ->whereBetween('tanggal_tindakan', [$startDate, $endDate])
                                   ->whereNotNull('dokter_id')
                                   ->get()
                                   ->groupBy('dokter_id')
                                   ->map(function ($group) {
                                       return [
                                           'doctor' => $group->first()->dokter,
                                           'total_procedures' => $group->count(),
                                           'approved_procedures' => $group->where('status_validasi', 'approved')->count(),
                                           'total_revenue' => $group->where('status_validasi', 'approved')->sum('tarif'),
                                           'approval_rate' => $group->count() > 0 ? ($group->where('status_validasi', 'approved')->count() / $group->count()) * 100 : 0,
                                       ];
                                   })
                                   ->sortByDesc('total_procedures')
                                   ->values()
                                   ->toArray();

        return [
            'period' => $period,
            'volume' => $procedureVolume,
            'top_procedures' => $topProcedures,
            'doctor_performance' => $doctorPerformance,
        ];
    }

    /**
     * Generate trends analytics
     */
    private function generateTrendsAnalytics(string $type, string $period, string $granularity): array
    {
        [$startDate, $endDate] = $this->getPeriodDates($period);
        
        $trends = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $intervalStart = $currentDate->copy();
            $intervalEnd = match ($granularity) {
                'hour' => $currentDate->copy()->endOfHour(),
                'day' => $currentDate->copy()->endOfDay(),
                'week' => $currentDate->copy()->endOfWeek(),
                'month' => $currentDate->copy()->endOfMonth(),
                default => $currentDate->copy()->endOfDay(),
            };

            $value = match ($type) {
                'revenue' => Pendapatan::whereBetween('tanggal_pendapatan', [$intervalStart, $intervalEnd])->sum('jumlah'),
                'expenses' => Pengeluaran::whereBetween('tanggal_pengeluaran', [$intervalStart, $intervalEnd])->sum('jumlah'),
                'patients' => Pasien::whereBetween('created_at', [$intervalStart, $intervalEnd])->count(),
                'procedures' => Tindakan::whereBetween('tanggal_tindakan', [$intervalStart, $intervalEnd])->count(),
                default => 0,
            };

            $trends[] = [
                'date' => $intervalStart->toISOString(),
                'formatted_date' => $intervalStart->format('d M Y'),
                'value' => $value,
            ];

            $currentDate = match ($granularity) {
                'hour' => $currentDate->addHour(),
                'day' => $currentDate->addDay(),
                'week' => $currentDate->addWeek(),
                'month' => $currentDate->addMonth(),
                default => $currentDate->addDay(),
            };
        }

        return [
            'type' => $type,
            'period' => $period,
            'granularity' => $granularity,
            'trends' => $trends,
        ];
    }

    /**
     * Generate comparative analytics
     */
    private function generateComparativeAnalytics(string $type): array
    {
        // Implementation would depend on the specific comparison type
        return [
            'type' => $type,
            'comparison' => [],
        ];
    }

    /**
     * Generate performance metrics
     */
    private function generatePerformanceMetrics(string $period): array
    {
        [$startDate, $endDate] = $this->getPeriodDates($period);

        // Calculate various KPIs
        $totalRevenue = Pendapatan::whereBetween('tanggal_pendapatan', [$startDate, $endDate])->sum('jumlah');
        $totalExpenses = Pengeluaran::whereBetween('tanggal_pengeluaran', [$startDate, $endDate])->sum('jumlah');
        $totalProcedures = Tindakan::whereBetween('tanggal_tindakan', [$startDate, $endDate])->count();
        $newPatients = Pasien::whereBetween('created_at', [$startDate, $endDate])->count();

        return [
            'period' => $period,
            'kpis' => [
                'revenue_per_procedure' => $totalProcedures > 0 ? $totalRevenue / $totalProcedures : 0,
                'cost_per_patient' => $newPatients > 0 ? $totalExpenses / $newPatients : 0,
                'profit_margin' => $totalRevenue > 0 ? (($totalRevenue - $totalExpenses) / $totalRevenue) * 100 : 0,
                'patient_acquisition_rate' => $newPatients,
            ],
        ];
    }

    /**
     * Generate custom report
     */
    private function generateCustomReport(array $params): array
    {
        $startDate = Carbon::parse($params['start_date']);
        $endDate = Carbon::parse($params['end_date']);
        $metrics = $params['metrics'];
        $groupBy = $params['group_by'] ?? null;

        $report = [];

        foreach ($metrics as $metric) {
            $report[$metric] = match ($metric) {
                'patients' => $this->getCustomPatientMetrics($startDate, $endDate, $groupBy),
                'procedures' => $this->getCustomProcedureMetrics($startDate, $endDate, $groupBy),
                'revenue' => $this->getCustomRevenueMetrics($startDate, $endDate, $groupBy),
                'expenses' => $this->getCustomExpenseMetrics($startDate, $endDate, $groupBy),
                default => [],
            };
        }

        return [
            'date_range' => [
                'start' => $startDate->toISOString(),
                'end' => $endDate->toISOString(),
            ],
            'metrics' => $report,
        ];
    }

    /**
     * Helper methods
     */
    private function getPeriodDates(string $period): array
    {
        $now = now();
        
        return match ($period) {
            'day' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'quarter' => [$now->copy()->startOfQuarter(), $now->copy()->endOfQuarter()],
            'year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };
    }

    private function getPreviousPeriod(string $period): string
    {
        return match ($period) {
            'day' => 'yesterday',
            'week' => 'last_week',
            'quarter' => 'last_quarter',
            'year' => 'last_year',
            default => 'last_month',
        };
    }

    private function calculateAgeDistribution(): array
    {
        $ageGroups = [
            '0-10' => 0, '11-20' => 0, '21-30' => 0, '31-40' => 0,
            '41-50' => 0, '51-60' => 0, '60+' => 0,
        ];

        $patients = Pasien::whereNotNull('tanggal_lahir')->get(['tanggal_lahir']);

        foreach ($patients as $patient) {
            $age = Carbon::parse($patient->tanggal_lahir)->age;
            
            if ($age <= 10) $ageGroups['0-10']++;
            elseif ($age <= 20) $ageGroups['11-20']++;
            elseif ($age <= 30) $ageGroups['21-30']++;
            elseif ($age <= 40) $ageGroups['31-40']++;
            elseif ($age <= 50) $ageGroups['41-50']++;
            elseif ($age <= 60) $ageGroups['51-60']++;
            else $ageGroups['60+']++;
        }

        return $ageGroups;
    }

    private function categorizeRevenue(string $source): string
    {
        $source = strtolower($source);
        
        if (str_contains($source, 'konsultasi')) return 'consultation';
        if (str_contains($source, 'obat')) return 'medication';
        if (str_contains($source, 'alat')) return 'equipment';
        if (str_contains($source, 'tindakan') || str_contains($source, 'medis')) return 'procedure';
        
        return 'other';
    }

    private function getRecentActivity(): array
    {
        return Tindakan::with(['pasien:id,nama_pasien', 'jenisTindakan:id,nama_tindakan'])
                      ->orderByDesc('created_at')
                      ->limit(5)
                      ->get()
                      ->map(function ($tindakan) {
                          return [
                              'type' => 'procedure',
                              'description' => "{$tindakan->jenisTindakan?->nama_tindakan} - {$tindakan->pasien?->nama_pasien}",
                              'date' => $tindakan->created_at->toISOString(),
                              'formatted_date' => $tindakan->created_at->diffForHumans(),
                          ];
                      })
                      ->toArray();
    }

    private function getRegistrationTrends(Carbon $startDate, Carbon $endDate): array
    {
        $trends = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $count = Pasien::whereDate('created_at', $currentDate)->count();
            $trends[] = [
                'date' => $currentDate->format('Y-m-d'),
                'formatted_date' => $currentDate->format('d M'),
                'count' => $count,
            ];
            $currentDate->addDay();
        }

        return $trends;
    }

    private function getCashFlowTrends(Carbon $startDate, Carbon $endDate): array
    {
        $trends = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $revenue = Pendapatan::whereDate('tanggal_pendapatan', $currentDate)->sum('jumlah');
            $expenses = Pengeluaran::whereDate('tanggal_pengeluaran', $currentDate)->sum('jumlah');
            
            $trends[] = [
                'date' => $currentDate->format('Y-m-d'),
                'formatted_date' => $currentDate->format('d M'),
                'revenue' => $revenue,
                'expenses' => $expenses,
                'net_flow' => $revenue - $expenses,
            ];
            $currentDate->addDay();
        }

        return $trends;
    }

    // Additional helper methods for custom metrics
    private function getCustomPatientMetrics(Carbon $startDate, Carbon $endDate, ?string $groupBy): array
    {
        return ['total' => Pasien::whereBetween('created_at', [$startDate, $endDate])->count()];
    }

    private function getCustomProcedureMetrics(Carbon $startDate, Carbon $endDate, ?string $groupBy): array
    {
        return ['total' => Tindakan::whereBetween('tanggal_tindakan', [$startDate, $endDate])->count()];
    }

    private function getCustomRevenueMetrics(Carbon $startDate, Carbon $endDate, ?string $groupBy): array
    {
        return ['total' => Pendapatan::whereBetween('tanggal_pendapatan', [$startDate, $endDate])->sum('jumlah')];
    }

    private function getCustomExpenseMetrics(Carbon $startDate, Carbon $endDate, ?string $groupBy): array
    {
        return ['total' => Pengeluaran::whereBetween('tanggal_pengeluaran', [$startDate, $endDate])->sum('jumlah')];
    }
}