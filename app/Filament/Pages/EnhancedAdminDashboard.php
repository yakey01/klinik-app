<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Models\SystemMetric;
use App\Models\AuditLog;
use App\Models\UserDevice;
use App\Models\PendapatanHarian;
use App\Models\PengeluaranHarian;
use App\Models\JumlahPasienHarian;
use App\Models\Tindakan;
use Filament\Pages\Page;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Contracts\View\View;
use Filament\Actions\Action;
use Filament\Support\Enums\ActionSize;
use Carbon\Carbon;

class EnhancedAdminDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    
    protected static string $view = 'filament.pages.enhanced-admin-dashboard';
    
    protected static ?string $title = 'Admin Dashboard';
    
    protected static ?string $navigationLabel = 'Dashboard';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $navigationGroup = '📊 DASHBOARD';
    
    public function mount(): void
    {
        // Initialize dashboard data
    }
    
    public function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh Data')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->size(ActionSize::Small)
                ->action(fn () => $this->refreshCache()),
                
            Action::make('system_health')
                ->label('System Monitoring')
                ->icon('heroicon-o-cpu-chip')
                ->color('success')
                ->size(ActionSize::Small)
                ->url(\App\Filament\Pages\SystemMonitoring::getUrl()),
                
            Action::make('security_logs')
                ->label('Security Logs')
                ->icon('heroicon-o-shield-check')
                ->color('warning')
                ->size(ActionSize::Small)
                ->url(\App\Filament\Resources\AuditLogResource::getUrl('index')),
        ];
    }
    
    public function refreshCache(): void
    {
        Cache::flush();
        $this->dispatch('refreshed');
    }
    
    /**
     * Get System Health Overview - Card 1
     */
    public function getSystemHealthOverview(): array
    {
        return Cache::remember('admin_system_health', now()->addMinutes(5), function () {
            $latestMetrics = SystemMetric::latest('created_at')->first();
            
            if (!$latestMetrics) {
                return [
                    'status' => 'unknown',
                    'memory_usage' => 0,
                    'cpu_usage' => 0,
                    'disk_usage' => 0,
                    'database_status' => 'unknown',
                    'active_alerts' => 0,
                    'last_check' => now()->format('H:i'),
                ];
            }
            
            $memoryUsage = $latestMetrics->memory_usage ?? 0;
            $cpuUsage = $latestMetrics->cpu_usage ?? 0;
            $diskUsage = $latestMetrics->disk_usage ?? 0;
            
            // Determine overall system status
            $status = 'healthy';
            if ($memoryUsage > 85 || $cpuUsage > 90 || $diskUsage > 90) {
                $status = 'critical';
            } elseif ($memoryUsage > 75 || $cpuUsage > 80 || $diskUsage > 80) {
                $status = 'warning';
            }
            
            return [
                'status' => $status,
                'memory_usage' => round($memoryUsage, 1),
                'cpu_usage' => round($cpuUsage, 1),
                'disk_usage' => round($diskUsage, 1),
                'database_status' => $latestMetrics->database_status ?? 'healthy',
                'active_alerts' => $this->getActiveAlertsCount(),
                'last_check' => $latestMetrics->created_at->format('H:i'),
            ];
        });
    }
    
    /**
     * Get Security Dashboard - Card 2
     */
    public function getSecurityDashboard(): array
    {
        return Cache::remember('admin_security_dashboard', now()->addMinutes(10), function () {
            $last24Hours = now()->subHours(24);
            
            // Recent security events
            $securityEvents = AuditLog::where('created_at', '>=', $last24Hours)
                ->whereIn('action', ['login_failed', 'login_success', 'logout', 'account_locked'])
                ->count();
            
            // Failed login attempts
            $failedLogins = AuditLog::where('created_at', '>=', $last24Hours)
                ->where('action', 'login_failed')
                ->count();
            
            // Suspicious activities (multiple failed attempts, unusual access patterns)
            $suspiciousActivities = AuditLog::where('created_at', '>=', $last24Hours)
                ->where('risk_level', 'high')
                ->count();
            
            // Active user sessions
            $activeSessions = UserDevice::where('is_active', true)
                ->where('last_active', '>=', now()->subMinutes(30))
                ->count();
            
            return [
                'security_events' => $securityEvents,
                'failed_logins' => $failedLogins,
                'suspicious_activities' => $suspiciousActivities,
                'active_sessions' => $activeSessions,
                'trend_direction' => $this->getSecurityTrend(),
                'last_incident' => $this->getLastSecurityIncident(),
            ];
        });
    }
    
    /**
     * Get User Management Summary - Card 3
     */
    public function getUserManagementSummary(): array
    {
        return Cache::remember('admin_user_management', now()->addMinutes(15), function () {
            $totalUsers = User::count();
            $activeUsers = User::where('is_active', true)->count();
            $inactiveUsers = $totalUsers - $activeUsers;
            
            // New users this month
            $newUsersThisMonth = User::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();
            
            // Users by role
            $usersByRole = User::with('roles')
                ->get()
                ->groupBy(function ($user) {
                    return $user->roles->first()->name ?? 'no_role';
                })
                ->map(function ($users) {
                    return $users->count();
                });
            
            // Pending approvals
            $pendingApprovals = User::where('is_active', false)
                ->whereNotNull('email_verified_at')
                ->count();
            
            return [
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'inactive_users' => $inactiveUsers,
                'new_users_this_month' => $newUsersThisMonth,
                'users_by_role' => $usersByRole->toArray(),
                'pending_approvals' => $pendingApprovals,
                'activity_percentage' => $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 1) : 0,
            ];
        });
    }
    
    /**
     * Get System Performance - Card 4
     */
    public function getSystemPerformance(): array
    {
        return Cache::remember('admin_system_performance', now()->addMinutes(5), function () {
            $metrics = SystemMetric::orderBy('created_at', 'desc')->take(10)->get();
            
            if ($metrics->isEmpty()) {
                return [
                    'response_time' => 0,
                    'database_queries' => 0,
                    'cache_hit_rate' => 0,
                    'queue_jobs' => 0,
                    'performance_score' => 0,
                    'trends' => [],
                ];
            }
            
            $latest = $metrics->first();
            $responseTime = $latest->response_time ?? 0;
            $databaseQueries = $latest->database_queries ?? 0;
            $cacheHitRate = $latest->cache_hit_rate ?? 0;
            $queueJobs = $latest->queue_jobs ?? 0;
            
            // Calculate performance score
            $performanceScore = $this->calculatePerformanceScore($responseTime, $cacheHitRate, $queueJobs);
            
            // Get trends data for charts
            $trends = $metrics->reverse()->map(function ($metric) {
                return [
                    'timestamp' => $metric->created_at->format('H:i'),
                    'response_time' => $metric->response_time ?? 0,
                    'memory_usage' => $metric->memory_usage ?? 0,
                    'cpu_usage' => $metric->cpu_usage ?? 0,
                ];
            })->toArray();
            
            return [
                'response_time' => round($responseTime, 2),
                'database_queries' => $databaseQueries,
                'cache_hit_rate' => round($cacheHitRate, 1),
                'queue_jobs' => $queueJobs,
                'performance_score' => $performanceScore,
                'trends' => $trends,
            ];
        });
    }
    
    /**
     * Get Financial Overview - Card 5
     */
    public function getFinancialOverview(): array
    {
        return Cache::remember('admin_financial_overview', now()->addMinutes(30), function () {
            $currentMonth = now();
            $lastMonth = now()->subMonth();
            
            // Current month financial data
            $currentRevenue = PendapatanHarian::whereMonth('tanggal_input', $currentMonth->month)
                ->whereYear('tanggal_input', $currentMonth->year)
                ->sum('nominal');
            
            $currentExpenses = PengeluaranHarian::whereMonth('tanggal_input', $currentMonth->month)
                ->whereYear('tanggal_input', $currentMonth->year)
                ->sum('nominal');
            
            // Last month for comparison
            $lastRevenue = PendapatanHarian::whereMonth('tanggal_input', $lastMonth->month)
                ->whereYear('tanggal_input', $lastMonth->year)
                ->sum('nominal');
            
            $lastExpenses = PengeluaranHarian::whereMonth('tanggal_input', $lastMonth->month)
                ->whereYear('tanggal_input', $lastMonth->year)
                ->sum('nominal');
            
            // Pending approvals
            $pendingApprovals = PendapatanHarian::where('status', 'pending')->count() +
                               PengeluaranHarian::where('status', 'pending')->count();
            
            return [
                'current_revenue' => $currentRevenue,
                'current_expenses' => $currentExpenses,
                'net_income' => $currentRevenue - $currentExpenses,
                'revenue_trend' => $this->calculatePercentageChange($currentRevenue, $lastRevenue),
                'expense_trend' => $this->calculatePercentageChange($currentExpenses, $lastExpenses),
                'pending_approvals' => $pendingApprovals,
                'profit_margin' => $currentRevenue > 0 ? round((($currentRevenue - $currentExpenses) / $currentRevenue) * 100, 1) : 0,
            ];
        });
    }
    
    /**
     * Get Medical Operations - Card 6
     */
    public function getMedicalOperations(): array
    {
        return Cache::remember('admin_medical_operations', now()->addMinutes(20), function () {
            $currentMonth = now();
            
            // Patient statistics
            $totalPatients = JumlahPasienHarian::whereMonth('tanggal', $currentMonth->month)
                ->whereYear('tanggal', $currentMonth->year)
                ->sum(DB::raw('jumlah_pasien_umum + jumlah_pasien_bpjs'));
            
            // Procedures completed
            $proceduresCompleted = Tindakan::whereMonth('tanggal_tindakan', $currentMonth->month)
                ->whereYear('tanggal_tindakan', $currentMonth->year)
                ->count();
            
            // Staff efficiency (average procedures per staff)
            $activeStaff = User::whereHas('roles', function ($q) {
                $q->whereIn('name', ['petugas', 'paramedis']);
            })->where('is_active', true)->count();
            
            $staffEfficiency = $activeStaff > 0 ? round($proceduresCompleted / $activeStaff, 1) : 0;
            
            // Data entry completion rate
            $expectedEntries = now()->day * 5; // 5 entries per day expected
            $actualEntries = PendapatanHarian::whereMonth('tanggal_input', $currentMonth->month)
                ->whereYear('tanggal_input', $currentMonth->year)
                ->count();
            
            $completionRate = $expectedEntries > 0 ? round(($actualEntries / $expectedEntries) * 100, 1) : 0;
            
            return [
                'total_patients' => $totalPatients,
                'procedures_completed' => $proceduresCompleted,
                'staff_efficiency' => $staffEfficiency,
                'completion_rate' => $completionRate,
                'active_staff' => $activeStaff,
                'patient_growth' => $this->getPatientGrowthTrend(),
            ];
        });
    }
    
    /**
     * Get Recent Activity Feed
     */
    public function getRecentAdminActivities(): array
    {
        return Cache::remember('admin_recent_activities', now()->addMinutes(10), function () {
            return AuditLog::with('user')
                ->whereIn('action', ['user_created', 'user_updated', 'user_deleted', 'role_assigned', 'permission_granted'])
                ->latest('created_at')
                ->limit(10)
                ->get()
                ->map(function ($log) {
                    return [
                        'type' => $this->getActivityType($log->action),
                        'description' => $this->getActivityDescription($log),
                        'user' => $log->user->name ?? 'System',
                        'timestamp' => $log->created_at->diffForHumans(),
                        'risk_level' => $log->risk_level,
                    ];
                })
                ->toArray();
        });
    }
    
    /**
     * Get 6-month trends for charts
     */
    public function getSixMonthTrends(): array
    {
        return Cache::remember('admin_six_month_trends', now()->addMinutes(60), function () {
            $months = [];
            $users = [];
            $revenue = [];
            $patients = [];
            $security_events = [];
            
            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $months[] = $date->format('M Y');
                
                // Users created
                $monthlyUsers = User::whereMonth('created_at', $date->month)
                    ->whereYear('created_at', $date->year)
                    ->count();
                
                // Revenue
                $monthlyRevenue = PendapatanHarian::whereMonth('tanggal_input', $date->month)
                    ->whereYear('tanggal_input', $date->year)
                    ->sum('nominal');
                
                // Patients
                $monthlyPatients = JumlahPasienHarian::whereMonth('tanggal', $date->month)
                    ->whereYear('tanggal', $date->year)
                    ->sum(DB::raw('jumlah_pasien_umum + jumlah_pasien_bpjs'));
                
                // Security events
                $monthlySecurityEvents = AuditLog::whereMonth('created_at', $date->month)
                    ->whereYear('created_at', $date->year)
                    ->whereIn('action', ['login_failed', 'account_locked', 'suspicious_activity'])
                    ->count();
                
                $users[] = $monthlyUsers;
                $revenue[] = $monthlyRevenue;
                $patients[] = $monthlyPatients;
                $security_events[] = $monthlySecurityEvents;
            }
            
            return [
                'months' => $months,
                'users' => $users,
                'revenue' => $revenue,
                'patients' => $patients,
                'security_events' => $security_events,
            ];
        });
    }
    
    // Helper methods
    private function getActiveAlertsCount(): int
    {
        return SystemMetric::where('created_at', '>=', now()->subHours(24))
            ->where(function ($query) {
                $query->where('memory_usage', '>', 85)
                      ->orWhere('cpu_usage', '>', 90)
                      ->orWhere('disk_usage', '>', 90);
            })
            ->count();
    }
    
    private function getSecurityTrend(): string
    {
        $today = AuditLog::whereDate('created_at', now())->where('action', 'login_failed')->count();
        $yesterday = AuditLog::whereDate('created_at', now()->subDay())->where('action', 'login_failed')->count();
        
        return $today > $yesterday ? 'up' : ($today < $yesterday ? 'down' : 'stable');
    }
    
    private function getLastSecurityIncident(): ?string
    {
        $lastIncident = AuditLog::where('risk_level', 'high')
            ->latest('created_at')
            ->first();
        
        return $lastIncident ? $lastIncident->created_at->diffForHumans() : null;
    }
    
    private function calculatePerformanceScore(float $responseTime, float $cacheHitRate, int $queueJobs): int
    {
        $score = 100;
        
        // Deduct points for slow response time
        if ($responseTime > 2) $score -= 20;
        elseif ($responseTime > 1) $score -= 10;
        
        // Deduct points for low cache hit rate
        if ($cacheHitRate < 70) $score -= 15;
        elseif ($cacheHitRate < 85) $score -= 5;
        
        // Deduct points for high queue jobs
        if ($queueJobs > 100) $score -= 15;
        elseif ($queueJobs > 50) $score -= 5;
        
        return max(0, $score);
    }
    
    private function getPatientGrowthTrend(): float
    {
        $currentMonth = JumlahPasienHarian::whereMonth('tanggal', now()->month)
            ->whereYear('tanggal', now()->year)
            ->sum(DB::raw('jumlah_pasien_umum + jumlah_pasien_bpjs'));
        
        $lastMonth = JumlahPasienHarian::whereMonth('tanggal', now()->subMonth()->month)
            ->whereYear('tanggal', now()->subMonth()->year)
            ->sum(DB::raw('jumlah_pasien_umum + jumlah_pasien_bpjs'));
        
        return $this->calculatePercentageChange($currentMonth, $lastMonth);
    }
    
    private function getActivityType(string $action): string
    {
        $types = [
            'user_created' => 'user',
            'user_updated' => 'user',
            'user_deleted' => 'user',
            'role_assigned' => 'security',
            'permission_granted' => 'security',
        ];
        
        return $types[$action] ?? 'system';
    }
    
    private function getActivityDescription(AuditLog $log): string
    {
        $descriptions = [
            'user_created' => 'New user created',
            'user_updated' => 'User information updated',
            'user_deleted' => 'User deleted',
            'role_assigned' => 'Role assigned to user',
            'permission_granted' => 'Permission granted',
        ];
        
        return $descriptions[$log->action] ?? 'System activity';
    }
    
    private function calculatePercentageChange($current, $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        
        return round((($current - $previous) / $previous) * 100, 2);
    }
}