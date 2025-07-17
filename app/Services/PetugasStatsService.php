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
     * Get statistics for specific date with optimized caching and real data
     */
    protected function getStatsForDate(int $userId, Carbon $date): array
    {
        try {
            $cacheKey = "petugas_daily_stats_{$userId}_{$date->format('Y-m-d')}";
            
            return Cache::remember($cacheKey, now()->addMinutes($this->dailyStatsCacheMinutes), function () use ($userId, $date) {
                // Enhanced query with better performance and more accurate calculations
                $results = DB::select("
                    SELECT 
                        COALESCE(p.pasien_count, 0) as pasien_count,
                        COALESCE(pd.pendapatan_sum, 0) as pendapatan_sum,
                        COALESCE(pd.pendapatan_count, 0) as pendapatan_count,
                        COALESCE(pg.pengeluaran_sum, 0) as pengeluaran_sum,
                        COALESCE(pg.pengeluaran_count, 0) as pengeluaran_count,
                        COALESCE(t.tindakan_count, 0) as tindakan_count,
                        COALESCE(t.tindakan_sum, 0) as tindakan_sum,
                        COALESCE(t.avg_tarif, 0) as avg_tindakan_tarif,
                        (COALESCE(pd.pendapatan_sum, 0) - COALESCE(pg.pengeluaran_sum, 0)) as net_income,
                        COALESCE(jp.jumlah_pasien, 0) as reported_patient_count,
                        COALESCE(jp.status_validasi, 'pending') as validation_status
                    FROM (
                        SELECT 
                            COUNT(*) as pasien_count,
                            COUNT(CASE WHEN jenis_kelamin = 'L' THEN 1 END) as male_count,
                            COUNT(CASE WHEN jenis_kelamin = 'P' THEN 1 END) as female_count
                        FROM pasien 
                        WHERE DATE(created_at) = ? AND input_by = ?
                    ) p
                    LEFT JOIN (
                        SELECT 
                            SUM(nominal) as pendapatan_sum,
                            COUNT(*) as pendapatan_count,
                            AVG(nominal) as avg_pendapatan
                        FROM pendapatan_harian 
                        WHERE tanggal_input = ? AND user_id = ?
                    ) pd ON 1=1
                    LEFT JOIN (
                        SELECT 
                            SUM(nominal) as pengeluaran_sum,
                            COUNT(*) as pengeluaran_count,
                            AVG(nominal) as avg_pengeluaran
                        FROM pengeluaran_harian 
                        WHERE tanggal_input = ? AND user_id = ?
                    ) pg ON 1=1
                    LEFT JOIN (
                        SELECT 
                            COUNT(*) as tindakan_count, 
                            SUM(tarif) as tindakan_sum,
                            AVG(tarif) as avg_tarif,
                            COUNT(DISTINCT pasien_id) as unique_patients,
                            COUNT(CASE WHEN status_validasi = 'approved' THEN 1 END) as approved_count
                        FROM tindakan 
                        WHERE DATE(tanggal_tindakan) = ? AND input_by = ?
                    ) t ON 1=1
                    LEFT JOIN (
                        SELECT 
                            jumlah_pasien,
                            status_validasi
                        FROM jumlah_pasien_harian
                        WHERE tanggal = ? AND user_id = ?
                        LIMIT 1
                    ) jp ON 1=1
                ", [
                    $date->format('Y-m-d'), $userId,
                    $date->format('Y-m-d'), $userId,
                    $date->format('Y-m-d'), $userId,
                    $date->format('Y-m-d'), $userId,
                    $date->format('Y-m-d'), $userId
                ]);
                
                $result = $results[0] ?? null;
                
                if (!$result) {
                    return $this->getEmptyDayStats();
                }
                
                // Calculate efficiency metrics
                $efficiency = $this->calculateEfficiency($result);
                
                return [
                    'pasien_count' => (int)$result->pasien_count,
                    'pendapatan_sum' => (float)$result->pendapatan_sum,
                    'pendapatan_count' => (int)$result->pendapatan_count,
                    'pengeluaran_sum' => (float)$result->pengeluaran_sum,
                    'pengeluaran_count' => (int)$result->pengeluaran_count,
                    'tindakan_count' => (int)$result->tindakan_count,
                    'tindakan_sum' => (float)$result->tindakan_sum,
                    'avg_tindakan_tarif' => (float)$result->avg_tindakan_tarif,
                    'net_income' => (float)$result->net_income,
                    'reported_patient_count' => (int)$result->reported_patient_count,
                    'validation_status' => $result->validation_status,
                    'efficiency' => $efficiency,
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
     * Calculate efficiency metrics based on data
     */
    protected function calculateEfficiency($data): array
    {
        try {
            $patientEfficiency = 0;
            $revenueEfficiency = 0;
            $validationEfficiency = 0;
            
            // Patient efficiency (actual vs reported)
            if ($data->reported_patient_count > 0) {
                $patientEfficiency = min(100, ($data->pasien_count / $data->reported_patient_count) * 100);
            } elseif ($data->pasien_count > 0) {
                $patientEfficiency = 75; // Partial efficiency if no report but has patients
            }
            
            // Revenue efficiency (income vs expenses ratio)
            if ($data->pengeluaran_sum > 0) {
                $revenueEfficiency = min(100, (($data->pendapatan_sum - $data->pengeluaran_sum) / $data->pendapatan_sum) * 100);
            } elseif ($data->pendapatan_sum > 0) {
                $revenueEfficiency = 100; // Perfect if no expenses
            }
            
            // Validation efficiency
            if ($data->validation_status === 'approved') {
                $validationEfficiency = 100;
            } elseif ($data->validation_status === 'pending') {
                $validationEfficiency = 50;
            }
            
            $overallEfficiency = ($patientEfficiency + $revenueEfficiency + $validationEfficiency) / 3;
            
            return [
                'overall' => round($overallEfficiency, 2),
                'patient' => round($patientEfficiency, 2),
                'revenue' => round($revenueEfficiency, 2),
                'validation' => round($validationEfficiency, 2),
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to calculate efficiency', ['error' => $e->getMessage()]);
            return [
                'overall' => 0,
                'patient' => 0,
                'revenue' => 0,
                'validation' => 0,
            ];
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
     * Get real-time insights for enhanced dashboard
     */
    public function getRealTimeInsights(int $userId = null): array
    {
        try {
            $userId = $userId ?? Auth::id();
            $cacheKey = "petugas_realtime_insights_{$userId}";
            
            return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($userId) {
                $today = Carbon::today();
                $yesterday = Carbon::yesterday();
                $thisWeek = Carbon::now()->startOfWeek();
                $lastWeek = Carbon::now()->subWeek()->startOfWeek();
                
                return [
                    'current_shift_stats' => $this->getCurrentShiftStats($userId),
                    'weekly_performance' => $this->getWeeklyPerformance($userId, $thisWeek, $lastWeek),
                    'trending_procedures' => $this->getTrendingProcedures($userId),
                    'validation_alerts' => $this->getValidationAlerts($userId),
                    'efficiency_score' => $this->getEfficiencyScore($userId),
                    'revenue_forecast' => $this->getRevenueForecast($userId),
                ];
            });
            
        } catch (Exception $e) {
            Log::error('Failed to get real-time insights', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }
    
    /**
     * Get current shift statistics
     */
    protected function getCurrentShiftStats(int $userId): array
    {
        try {
            $now = Carbon::now();
            $shiftStart = $now->copy()->hour(7)->minute(0)->second(0); // 7 AM start
            
            if ($now->hour < 7) {
                $shiftStart = $shiftStart->subDay();
            }
            
            $stats = DB::select("
                SELECT 
                    COUNT(DISTINCT p.id) as patients_this_shift,
                    COUNT(DISTINCT t.id) as procedures_this_shift,
                    SUM(CASE WHEN t.created_at >= ? THEN t.tarif ELSE 0 END) as revenue_this_shift,
                    AVG(TIMESTAMPDIFF(MINUTE, p.created_at, t.created_at)) as avg_processing_time
                FROM pasien p
                LEFT JOIN tindakan t ON p.id = t.pasien_id
                WHERE p.input_by = ? AND p.created_at >= ?
            ", [$shiftStart->format('Y-m-d H:i:s'), $userId, $shiftStart->format('Y-m-d H:i:s')]);
            
            $result = $stats[0] ?? null;
            
            return [
                'patients_count' => (int)($result->patients_this_shift ?? 0),
                'procedures_count' => (int)($result->procedures_this_shift ?? 0),
                'revenue' => (float)($result->revenue_this_shift ?? 0),
                'avg_processing_time' => (float)($result->avg_processing_time ?? 0),
                'shift_start' => $shiftStart->format('H:i'),
                'hours_worked' => $now->diffInHours($shiftStart),
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to get current shift stats', ['error' => $e->getMessage()]);
            return [];
        }
    }
    
    /**
     * Get weekly performance comparison
     */
    protected function getWeeklyPerformance(int $userId, Carbon $thisWeek, Carbon $lastWeek): array
    {
        try {
            $thisWeekEnd = $thisWeek->copy()->endOfWeek();
            $lastWeekEnd = $lastWeek->copy()->endOfWeek();
            
            $thisWeekStats = $this->getWeekStats($userId, $thisWeek, $thisWeekEnd);
            $lastWeekStats = $this->getWeekStats($userId, $lastWeek, $lastWeekEnd);
            
            return [
                'this_week' => $thisWeekStats,
                'last_week' => $lastWeekStats,
                'growth' => $this->calculateWeeklyGrowth($thisWeekStats, $lastWeekStats),
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to get weekly performance', ['error' => $e->getMessage()]);
            return [];
        }
    }
    
    /**
     * Get stats for a week period
     */
    protected function getWeekStats(int $userId, Carbon $start, Carbon $end): array
    {
        $stats = DB::select("
            SELECT 
                COUNT(DISTINCT p.id) as patients,
                COUNT(DISTINCT t.id) as procedures,
                SUM(ph.nominal) as revenue,
                SUM(pg.nominal) as expenses,
                COUNT(DISTINCT DATE(p.created_at)) as active_days
            FROM pasien p
            LEFT JOIN tindakan t ON p.id = t.pasien_id
            LEFT JOIN pendapatan_harian ph ON ph.user_id = ? AND ph.tanggal_input BETWEEN ? AND ?
            LEFT JOIN pengeluaran_harian pg ON pg.user_id = ? AND pg.tanggal_input BETWEEN ? AND ?
            WHERE p.input_by = ? AND p.created_at BETWEEN ? AND ?
        ", [
            $userId, $start->format('Y-m-d'), $end->format('Y-m-d'),
            $userId, $start->format('Y-m-d'), $end->format('Y-m-d'),
            $userId, $start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')
        ]);
        
        $result = $stats[0] ?? null;
        
        return [
            'patients' => (int)($result->patients ?? 0),
            'procedures' => (int)($result->procedures ?? 0),
            'revenue' => (float)($result->revenue ?? 0),
            'expenses' => (float)($result->expenses ?? 0),
            'net_revenue' => (float)(($result->revenue ?? 0) - ($result->expenses ?? 0)),
            'active_days' => (int)($result->active_days ?? 0),
        ];
    }
    
    /**
     * Calculate weekly growth percentages
     */
    protected function calculateWeeklyGrowth(array $thisWeek, array $lastWeek): array
    {
        $growth = [];
        $metrics = ['patients', 'procedures', 'revenue', 'net_revenue'];
        
        foreach ($metrics as $metric) {
            $current = $thisWeek[$metric] ?? 0;
            $previous = $lastWeek[$metric] ?? 0;
            
            if ($previous > 0) {
                $growth[$metric] = round((($current - $previous) / $previous) * 100, 2);
            } else {
                $growth[$metric] = $current > 0 ? 100 : 0;
            }
        }
        
        return $growth;
    }
    
    /**
     * Get trending procedures for the week
     */
    protected function getTrendingProcedures(int $userId): array
    {
        try {
            $weekStart = Carbon::now()->startOfWeek();
            
            $procedures = DB::select("
                SELECT 
                    jt.nama_tindakan,
                    COUNT(t.id) as frequency,
                    SUM(t.tarif) as total_revenue,
                    AVG(t.tarif) as avg_tarif,
                    COUNT(DISTINCT t.pasien_id) as unique_patients
                FROM tindakan t
                JOIN jenis_tindakan jt ON t.jenis_tindakan_id = jt.id
                WHERE t.input_by = ? 
                AND t.tanggal_tindakan >= ?
                GROUP BY jt.id, jt.nama_tindakan
                ORDER BY frequency DESC, total_revenue DESC
                LIMIT 5
            ", [$userId, $weekStart->format('Y-m-d')]);
            
            return array_map(function($proc) {
                return [
                    'name' => $proc->nama_tindakan,
                    'frequency' => (int)$proc->frequency,
                    'total_revenue' => (float)$proc->total_revenue,
                    'avg_tarif' => (float)$proc->avg_tarif,
                    'unique_patients' => (int)$proc->unique_patients,
                ];
            }, $procedures);
            
        } catch (Exception $e) {
            Log::error('Failed to get trending procedures', ['error' => $e->getMessage()]);
            return [];
        }
    }
    
    /**
     * Get validation alerts
     */
    protected function getValidationAlerts(int $userId): array
    {
        try {
            $alerts = [];
            
            // Check pending validations
            $pendingCount = Tindakan::where('input_by', $userId)
                ->where('status_validasi', 'pending')
                ->where('created_at', '>=', Carbon::now()->subDays(3))
                ->count();
            
            if ($pendingCount > 0) {
                $alerts[] = [
                    'type' => 'warning',
                    'message' => "Anda memiliki {$pendingCount} tindakan yang menunggu validasi",
                    'action_url' => '/petugas/tindakans?filter[status_validasi]=pending',
                    'priority' => 'medium'
                ];
            }
            
            // Check rejected items
            $rejectedCount = Tindakan::where('input_by', $userId)
                ->where('status_validasi', 'rejected')
                ->whereDate('updated_at', Carbon::today())
                ->count();
            
            if ($rejectedCount > 0) {
                $alerts[] = [
                    'type' => 'error',
                    'message' => "{$rejectedCount} tindakan ditolak hari ini. Perlu tindak lanjut.",
                    'action_url' => '/petugas/tindakans?filter[status_validasi]=rejected',
                    'priority' => 'high'
                ];
            }
            
            return $alerts;
            
        } catch (Exception $e) {
            Log::error('Failed to get validation alerts', ['error' => $e->getMessage()]);
            return [];
        }
    }
    
    /**
     * Get overall efficiency score
     */
    protected function getEfficiencyScore(int $userId): array
    {
        try {
            $today = Carbon::today();
            $todayStats = $this->getStatsForDate($userId, $today);
            
            $efficiency = $todayStats['efficiency'] ?? ['overall' => 0];
            
            // Get efficiency trend for last 7 days
            $weeklyEfficiency = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::today()->subDays($i);
                $dayStats = $this->getStatsForDate($userId, $date);
                $weeklyEfficiency[] = $dayStats['efficiency']['overall'] ?? 0;
            }
            
            $avgWeeklyEfficiency = array_sum($weeklyEfficiency) / count($weeklyEfficiency);
            
            return [
                'current' => $efficiency['overall'],
                'weekly_average' => round($avgWeeklyEfficiency, 2),
                'trend' => $weeklyEfficiency,
                'breakdown' => $efficiency,
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to get efficiency score', ['error' => $e->getMessage()]);
            return ['current' => 0, 'weekly_average' => 0, 'trend' => [], 'breakdown' => []];
        }
    }
    
    /**
     * Get revenue forecast based on trends
     */
    protected function getRevenueForecast(int $userId): array
    {
        try {
            // Get last 30 days revenue data
            $revenueData = [];
            for ($i = 29; $i >= 0; $i--) {
                $date = Carbon::today()->subDays($i);
                $dayStats = $this->getStatsForDate($userId, $date);
                $revenueData[] = $dayStats['net_income'] ?? 0;
            }
            
            // Simple linear regression for next 7 days forecast
            $forecast = $this->calculateRevenueForecast($revenueData);
            
            return [
                'next_7_days' => $forecast,
                'projected_monthly' => array_sum($forecast) * 4.3, // Approximate monthly
                'confidence' => $this->calculateForecastConfidence($revenueData),
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to get revenue forecast', ['error' => $e->getMessage()]);
            return ['next_7_days' => [], 'projected_monthly' => 0, 'confidence' => 0];
        }
    }
    
    /**
     * Calculate simple revenue forecast using linear trend
     */
    protected function calculateRevenueForecast(array $data): array
    {
        $n = count($data);
        if ($n < 7) return array_fill(0, 7, 0);
        
        // Calculate trend (simple moving average slope)
        $recent = array_slice($data, -7);
        $older = array_slice($data, -14, 7);
        
        $recentAvg = array_sum($recent) / 7;
        $olderAvg = array_sum($older) / 7;
        
        $trend = ($recentAvg - $olderAvg) / 7; // Daily trend
        
        $forecast = [];
        $lastValue = end($data);
        
        for ($i = 1; $i <= 7; $i++) {
            $forecast[] = max(0, $lastValue + ($trend * $i));
        }
        
        return $forecast;
    }
    
    /**
     * Calculate forecast confidence based on data stability
     */
    protected function calculateForecastConfidence(array $data): int
    {
        if (count($data) < 7) return 0;
        
        $recent = array_slice($data, -7);
        $mean = array_sum($recent) / count($recent);
        
        // Calculate coefficient of variation
        $variance = 0;
        foreach ($recent as $value) {
            $variance += pow($value - $mean, 2);
        }
        $variance /= count($recent);
        $stdDev = sqrt($variance);
        
        $cv = $mean > 0 ? ($stdDev / $mean) : 1;
        
        // Convert to confidence percentage (lower variation = higher confidence)
        $confidence = max(0, min(100, (1 - $cv) * 100));
        
        return (int)round($confidence);
    }
    
    /**
     * Clear cache for specific user
     */
    public function clearStatsCache(int $userId): void
    {
        $cacheKeys = [
            "petugas_stats_{$userId}",
            "petugas_realtime_insights_{$userId}",
            "petugas_trend_analysis_{$userId}"
        ];
        
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
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