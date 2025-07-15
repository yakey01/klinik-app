<?php

namespace App\Services;

use App\Models\User;
use App\Models\Pasien;
use App\Models\Tindakan;
use App\Models\PendapatanHarian;
use App\Models\PengeluaranHarian;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Exception;

class PetugasStatsService
{
    protected int $cacheMinutes = 15; // Cache for 15 minutes
    protected int $dailyStatsCacheMinutes = 360; // Cache daily stats for 6 hours
    
    /**
     * Get comprehensive stats for petugas dashboard
     */
    public function getDashboardStats(int $userId = null): array
    {
        try {
            $userId = $userId ?? Auth::id();
            $cacheKey = "petugas_stats_{$userId}";
            
            return Cache::remember($cacheKey, now()->addMinutes($this->cacheMinutes), function () use ($userId) {
                $today = Carbon::today();
                $yesterday = Carbon::yesterday();
                $thisMonth = Carbon::now()->startOfMonth();
                $lastMonth = Carbon::now()->subMonth()->startOfMonth();
                
                return [
                    'daily' => $this->getDailyStats($userId, $today, $yesterday),
                    'monthly' => $this->getMonthlyStats($userId, $thisMonth, $lastMonth),
                    'trends' => $this->getTrendAnalysis($userId),
                    'validation_summary' => $this->getValidationSummary($userId),
                    'performance_metrics' => $this->getPerformanceMetrics($userId),
                ];
            });
            
        } catch (Exception $e) {
            Log::error('Failed to get dashboard stats', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->getEmptyStats();
        }
    }
    
    /**
     * Get daily statistics
     */
    protected function getDailyStats(int $userId, Carbon $today, Carbon $yesterday): array
    {
        try {
            $todayStats = $this->getStatsForDate($userId, $today);
            $yesterdayStats = $this->getStatsForDate($userId, $yesterday);
            
            return [
                'today' => $todayStats,
                'yesterday' => $yesterdayStats,
                'trends' => $this->calculateTrends($todayStats, $yesterdayStats),
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to get daily stats', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'today' => $this->getEmptyDayStats(),
                'yesterday' => $this->getEmptyDayStats(),
                'trends' => $this->getEmptyTrends(),
            ];
        }
    }
    
    /**
     * Get statistics for specific date with optimized caching
     */
    protected function getStatsForDate(int $userId, Carbon $date): array
    {
        try {
            $cacheKey = "petugas_daily_stats_{$userId}_{$date->format('Y-m-d')}";
            
            return Cache::remember($cacheKey, now()->addMinutes($this->dailyStatsCacheMinutes), function () use ($userId, $date) {
                // Optimized single query using raw SQL with subqueries
                $results = DB::select("
                    SELECT 
                        COALESCE(p.pasien_count, 0) as pasien_count,
                        COALESCE(pd.pendapatan_sum, 0) as pendapatan_sum,
                        COALESCE(pg.pengeluaran_sum, 0) as pengeluaran_sum,
                        COALESCE(t.tindakan_count, 0) as tindakan_count,
                        COALESCE(t.tindakan_sum, 0) as tindakan_sum,
                        (COALESCE(pd.pendapatan_sum, 0) - COALESCE(pg.pengeluaran_sum, 0)) as net_income
                    FROM (
                        SELECT COUNT(*) as pasien_count 
                        FROM pasien 
                        WHERE DATE(created_at) = ? AND input_by = ?
                    ) p
                    LEFT JOIN (
                        SELECT SUM(nominal) as pendapatan_sum 
                        FROM pendapatan_harian 
                        WHERE tanggal_input = ? AND user_id = ?
                    ) pd ON 1=1
                    LEFT JOIN (
                        SELECT SUM(nominal) as pengeluaran_sum 
                        FROM pengeluaran_harian 
                        WHERE tanggal_input = ? AND user_id = ?
                    ) pg ON 1=1
                    LEFT JOIN (
                        SELECT COUNT(*) as tindakan_count, SUM(tarif) as tindakan_sum 
                        FROM tindakan 
                        WHERE DATE(tanggal_tindakan) = ? AND input_by = ?
                    ) t ON 1=1
                ", [
                    $date->format('Y-m-d'), $userId,
                    $date->format('Y-m-d'), $userId,
                    $date->format('Y-m-d'), $userId,
                    $date->format('Y-m-d'), $userId
                ]);
                
                $result = $results[0] ?? null;
                
                if (!$result) {
                    return $this->getEmptyDayStats();
                }
                
                return [
                    'pasien_count' => (int)$result->pasien_count,
                    'pendapatan_sum' => (float)$result->pendapatan_sum,
                    'pengeluaran_sum' => (float)$result->pengeluaran_sum,
                    'tindakan_count' => (int)$result->tindakan_count,
                    'tindakan_sum' => (float)$result->tindakan_sum,
                    'net_income' => (float)$result->net_income,
                    'date' => $date->format('Y-m-d'),
                ];
            });
            
        } catch (Exception $e) {
            Log::error('Failed to get stats for date', [
                'user_id' => $userId,
                'date' => $date->format('Y-m-d'),
                'error' => $e->getMessage()
            ]);
            
            return $this->getEmptyDayStats();
        }
    }
    
    /**
     * Calculate trends between two periods
     */
    protected function calculateTrends(array $current, array $previous): array
    {
        try {
            $trends = [];
            
            $metrics = ['pasien_count', 'pendapatan_sum', 'pengeluaran_sum', 'tindakan_count', 'net_income'];
            
            foreach ($metrics as $metric) {
                $currentValue = $current[$metric] ?? 0;
                $previousValue = $previous[$metric] ?? 0;
                
                if ($previousValue > 0) {
                    $percentage = (($currentValue - $previousValue) / $previousValue) * 100;
                } else {
                    $percentage = $currentValue > 0 ? 100 : 0;
                }
                
                $trends[$metric] = [
                    'current' => $currentValue,
                    'previous' => $previousValue,
                    'percentage' => round($percentage, 2),
                    'direction' => $percentage > 0 ? 'up' : ($percentage < 0 ? 'down' : 'stable'),
                    'change' => $currentValue - $previousValue,
                ];
            }
            
            return $trends;
            
        } catch (Exception $e) {
            Log::error('Failed to calculate trends', [
                'error' => $e->getMessage()
            ]);
            
            return $this->getEmptyTrends();
        }
    }
    
    /**
     * Get monthly statistics
     */
    protected function getMonthlyStats(int $userId, Carbon $thisMonth, Carbon $lastMonth): array
    {
        try {
            $thisMonthStats = $this->getStatsForMonth($userId, $thisMonth);
            $lastMonthStats = $this->getStatsForMonth($userId, $lastMonth);
            
            return [
                'this_month' => $thisMonthStats,
                'last_month' => $lastMonthStats,
                'trends' => $this->calculateTrends($thisMonthStats, $lastMonthStats),
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to get monthly stats', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'this_month' => $this->getEmptyDayStats(),
                'last_month' => $this->getEmptyDayStats(),
                'trends' => $this->getEmptyTrends(),
            ];
        }
    }
    
    /**
     * Get statistics for specific month
     */
    protected function getStatsForMonth(int $userId, Carbon $month): array
    {
        try {
            $endOfMonth = $month->copy()->endOfMonth();
            
            // Patient stats
            $pasienCount = Pasien::whereBetween('created_at', [$month, $endOfMonth])
                ->where('input_by', $userId)
                ->count();
            
            // Income stats
            $pendapatanSum = PendapatanHarian::whereBetween('tanggal_input', [$month->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
                ->where('user_id', $userId)
                ->sum('nominal');
            
            // Expense stats
            $pengeluaranSum = PengeluaranHarian::whereBetween('tanggal_input', [$month->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
                ->where('user_id', $userId)
                ->sum('nominal');
            
            // Treatment stats
            $tindakanCount = Tindakan::whereBetween('tanggal_tindakan', [$month, $endOfMonth])
                ->where('input_by', $userId)
                ->count();
            
            $tindakanSum = Tindakan::whereBetween('tanggal_tindakan', [$month, $endOfMonth])
                ->where('input_by', $userId)
                ->sum('tarif');
            
            // Net income
            $netIncome = $pendapatanSum - $pengeluaranSum;
            
            return [
                'pasien_count' => $pasienCount,
                'pendapatan_sum' => $pendapatanSum,
                'pengeluaran_sum' => $pengeluaranSum,
                'tindakan_count' => $tindakanCount,
                'tindakan_sum' => $tindakanSum,
                'net_income' => $netIncome,
                'month' => $month->format('Y-m'),
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to get stats for month', [
                'user_id' => $userId,
                'month' => $month->format('Y-m'),
                'error' => $e->getMessage()
            ]);
            
            return $this->getEmptyDayStats();
        }
    }
    
    /**
     * Get trend analysis for charts with optimized bulk queries
     */
    protected function getTrendAnalysis(int $userId): array
    {
        try {
            $cacheKey = "petugas_trend_analysis_{$userId}";
            
            return Cache::remember($cacheKey, now()->addMinutes($this->cacheMinutes), function () use ($userId) {
                $last7Days = $this->getBulkStatsForDateRange($userId, 7);
                $last30Days = $this->getBulkStatsForDateRange($userId, 30);
                
                return [
                    'last_7_days' => $last7Days->toArray(),
                    'last_30_days' => $last30Days->toArray(),
                    'charts' => [
                        'daily_income' => $last7Days->pluck('net_income')->toArray(),
                        'daily_patients' => $last7Days->pluck('pasien_count')->toArray(),
                        'daily_treatments' => $last7Days->pluck('tindakan_count')->toArray(),
                        'monthly_trend' => $last30Days->pluck('net_income')->toArray(),
                    ],
                ];
            });
            
        } catch (Exception $e) {
            Log::error('Failed to get trend analysis', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'last_7_days' => [],
                'last_30_days' => [],
                'charts' => [
                    'daily_income' => [0],
                    'daily_patients' => [0],
                    'daily_treatments' => [0],
                    'monthly_trend' => [0],
                ],
            ];
        }
    }
    
    /**
     * Get bulk statistics for date range with single query
     */
    protected function getBulkStatsForDateRange(int $userId, int $days): Collection
    {
        try {
            $startDate = Carbon::today()->subDays($days - 1);
            $endDate = Carbon::today();
            
            // Generate date range
            $dateRange = collect();
            $current = $startDate->copy();
            while ($current->lte($endDate)) {
                $dateRange->push($current->format('Y-m-d'));
                $current->addDay();
            }
            
            // Get all data in bulk queries
            $pasienStats = DB::table('pasien')
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('input_by', $userId)
                ->whereBetween(DB::raw('DATE(created_at)'), [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->groupBy(DB::raw('DATE(created_at)'))
                ->get()
                ->keyBy('date');
            
            $pendapatanStats = DB::table('pendapatan_harian')
                ->selectRaw('tanggal_input as date, SUM(nominal) as sum')
                ->where('user_id', $userId)
                ->whereBetween('tanggal_input', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->groupBy('tanggal_input')
                ->get()
                ->keyBy('date');
            
            $pengeluaranStats = DB::table('pengeluaran_harian')
                ->selectRaw('tanggal_input as date, SUM(nominal) as sum')
                ->where('user_id', $userId)
                ->whereBetween('tanggal_input', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->groupBy('tanggal_input')
                ->get()
                ->keyBy('date');
            
            $tindakanStats = DB::table('tindakan')
                ->selectRaw('DATE(tanggal_tindakan) as date, COUNT(*) as count, SUM(tarif) as sum')
                ->where('input_by', $userId)
                ->whereBetween(DB::raw('DATE(tanggal_tindakan)'), [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->groupBy(DB::raw('DATE(tanggal_tindakan)'))
                ->get()
                ->keyBy('date');
            
            // Combine data for each date
            return $dateRange->map(function ($date) use ($pasienStats, $pendapatanStats, $pengeluaranStats, $tindakanStats) {
                $pasienCount = $pasienStats->get($date)?->count ?? 0;
                $pendapatanSum = $pendapatanStats->get($date)?->sum ?? 0;
                $pengeluaranSum = $pengeluaranStats->get($date)?->sum ?? 0;
                $tindakanCount = $tindakanStats->get($date)?->count ?? 0;
                $tindakanSum = $tindakanStats->get($date)?->sum ?? 0;
                
                return [
                    'pasien_count' => (int)$pasienCount,
                    'pendapatan_sum' => (float)$pendapatanSum,
                    'pengeluaran_sum' => (float)$pengeluaranSum,
                    'tindakan_count' => (int)$tindakanCount,
                    'tindakan_sum' => (float)$tindakanSum,
                    'net_income' => (float)($pendapatanSum - $pengeluaranSum),
                    'date' => $date,
                ];
            });
            
        } catch (Exception $e) {
            Log::error('Failed to get bulk stats for date range', [
                'user_id' => $userId,
                'days' => $days,
                'error' => $e->getMessage()
            ]);
            
            return collect();
        }
    }
    
    /**
     * Get validation summary
     */
    protected function getValidationSummary(int $userId): array
    {
        try {
            $pendingValidations = 0;
            $approvedToday = 0;
            $rejectedToday = 0;
            
            $models = [Tindakan::class, PendapatanHarian::class, PengeluaranHarian::class];
            
            foreach ($models as $model) {
                $pendingValidations += $model::where('input_by', $userId)
                    ->where('status_validasi', 'pending')
                    ->count();
                
                $approvedToday += $model::where('input_by', $userId)
                    ->where('status_validasi', 'approved')
                    ->whereDate('approved_at', Carbon::today())
                    ->count();
                
                $rejectedToday += $model::where('input_by', $userId)
                    ->where('status_validasi', 'rejected')
                    ->whereDate('rejected_at', Carbon::today())
                    ->count();
            }
            
            return [
                'pending_validations' => $pendingValidations,
                'approved_today' => $approvedToday,
                'rejected_today' => $rejectedToday,
                'total_today' => $approvedToday + $rejectedToday,
                'approval_rate' => ($approvedToday + $rejectedToday) > 0 ? 
                    round(($approvedToday / ($approvedToday + $rejectedToday)) * 100, 2) : 0,
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to get validation summary', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'pending_validations' => 0,
                'approved_today' => 0,
                'rejected_today' => 0,
                'total_today' => 0,
                'approval_rate' => 0,
            ];
        }
    }
    
    /**
     * Get performance metrics
     */
    protected function getPerformanceMetrics(int $userId): array
    {
        try {
            $thisMonth = Carbon::now()->startOfMonth();
            $lastMonth = Carbon::now()->subMonth()->startOfMonth();
            
            $monthlyTarget = 100; // This could be configurable
            $currentMonthStats = $this->getStatsForMonth($userId, $thisMonth);
            
            $completionRate = $monthlyTarget > 0 ? 
                round(($currentMonthStats['pasien_count'] / $monthlyTarget) * 100, 2) : 0;
            
            return [
                'monthly_target' => $monthlyTarget,
                'current_achievement' => $currentMonthStats['pasien_count'],
                'completion_rate' => $completionRate,
                'days_remaining' => Carbon::now()->endOfMonth()->diffInDays(Carbon::now()),
                'daily_average_needed' => $monthlyTarget > $currentMonthStats['pasien_count'] ? 
                    round(($monthlyTarget - $currentMonthStats['pasien_count']) / max(1, Carbon::now()->endOfMonth()->diffInDays(Carbon::now())), 2) : 0,
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to get performance metrics', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'monthly_target' => 0,
                'current_achievement' => 0,
                'completion_rate' => 0,
                'days_remaining' => 0,
                'daily_average_needed' => 0,
            ];
        }
    }
    
    /**
     * Clear cache for specific user
     */
    public function clearStatsCache(int $userId): void
    {
        Cache::forget("petugas_stats_{$userId}");
    }
    
    /**
     * Get empty stats structure
     */
    protected function getEmptyStats(): array
    {
        return [
            'daily' => [
                'today' => $this->getEmptyDayStats(),
                'yesterday' => $this->getEmptyDayStats(),
                'trends' => $this->getEmptyTrends(),
            ],
            'monthly' => [
                'this_month' => $this->getEmptyDayStats(),
                'last_month' => $this->getEmptyDayStats(),
                'trends' => $this->getEmptyTrends(),
            ],
            'trends' => [
                'last_7_days' => [],
                'last_30_days' => [],
                'charts' => [
                    'daily_income' => [0],
                    'daily_patients' => [0],
                    'daily_treatments' => [0],
                    'monthly_trend' => [0],
                ],
            ],
            'validation_summary' => [
                'pending_validations' => 0,
                'approved_today' => 0,
                'rejected_today' => 0,
                'total_today' => 0,
                'approval_rate' => 0,
            ],
            'performance_metrics' => [
                'monthly_target' => 0,
                'current_achievement' => 0,
                'completion_rate' => 0,
                'days_remaining' => 0,
                'daily_average_needed' => 0,
            ],
        ];
    }
    
    /**
     * Get empty day stats structure
     */
    protected function getEmptyDayStats(): array
    {
        return [
            'pasien_count' => 0,
            'pendapatan_sum' => 0,
            'pengeluaran_sum' => 0,
            'tindakan_count' => 0,
            'tindakan_sum' => 0,
            'net_income' => 0,
        ];
    }
    
    /**
     * Get empty trends structure
     */
    protected function getEmptyTrends(): array
    {
        $metrics = ['pasien_count', 'pendapatan_sum', 'pengeluaran_sum', 'tindakan_count', 'net_income'];
        $trends = [];
        
        foreach ($metrics as $metric) {
            $trends[$metric] = [
                'current' => 0,
                'previous' => 0,
                'percentage' => 0,
                'direction' => 'stable',
                'change' => 0,
            ];
        }
        
        return $trends;
    }
}