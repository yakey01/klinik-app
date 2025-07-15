<?php

namespace App\Services;

use App\Models\Report;
use App\Models\ReportExecution;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ReportService
{
    /**
     * Create a new report
     */
    public function createReport(User $user, array $data): Report
    {
        $report = Report::create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'report_type' => $data['report_type'],
            'category' => $data['category'],
            'query_config' => $data['query_config'],
            'chart_config' => $data['chart_config'] ?? null,
            'filters' => $data['filters'] ?? [],
            'columns' => $data['columns'] ?? [],
            'is_public' => $data['is_public'] ?? false,
            'is_scheduled' => $data['is_scheduled'] ?? false,
            'schedule_config' => $data['schedule_config'] ?? null,
            'cache_duration' => $data['cache_duration'] ?? 300,
            'status' => Report::STATUS_ACTIVE,
            'tags' => $data['tags'] ?? [],
        ]);

        // Log report creation
        AuditLog::logSystem(
            'report_created',
            $user,
            "Report '{$report->name}' created",
            [
                'report_id' => $report->id,
                'report_type' => $report->report_type,
                'category' => $report->category,
            ]
        );

        return $report;
    }

    /**
     * Execute a report
     */
    public function executeReport(Report $report, User $user, array $parameters = []): ReportExecution
    {
        // Check cache first
        $cacheKey = $this->getCacheKey($report, $parameters);
        $cachedResult = Cache::get($cacheKey);

        if ($cachedResult && $report->cache_duration > 0) {
            // Return cached execution
            return ReportExecution::create([
                'report_id' => $report->id,
                'user_id' => $user->id,
                'parameters' => $parameters,
                'status' => ReportExecution::STATUS_COMPLETED,
                'result_data' => $cachedResult['data'],
                'result_count' => $cachedResult['count'],
                'execution_time' => $cachedResult['execution_time'],
                'started_at' => now(),
                'completed_at' => now(),
                'cache_key' => $cacheKey,
            ]);
        }

        // Execute report
        $execution = $report->execute($parameters);

        // Cache result if successful
        if ($execution->isCompleted() && $report->cache_duration > 0) {
            Cache::put($cacheKey, [
                'data' => $execution->result_data,
                'count' => $execution->result_count,
                'execution_time' => $execution->execution_time,
            ], $report->cache_duration);
        }

        // Log execution
        AuditLog::logSystem(
            'report_executed',
            $user,
            "Report '{$report->name}' executed",
            [
                'report_id' => $report->id,
                'execution_id' => $execution->id,
                'status' => $execution->status,
                'execution_time' => $execution->execution_time,
                'result_count' => $execution->result_count,
            ]
        );

        return $execution;
    }

    /**
     * Create report from template
     */
    public function createFromTemplate(User $user, string $templateKey, array $customizations = []): Report
    {
        $templates = Report::getReportTemplates();
        $template = $templates[$templateKey] ?? null;

        if (!$template) {
            throw new \Exception("Template '{$templateKey}' not found");
        }

        $config = $this->getTemplateConfig($templateKey);
        
        // Merge customizations
        $data = array_merge($template, $config, $customizations);
        
        return $this->createReport($user, $data);
    }

    /**
     * Get template configuration
     */
    private function getTemplateConfig(string $templateKey): array
    {
        return match($templateKey) {
            'financial_summary' => [
                'query_config' => [
                    'model' => 'App\\Models\\Pendapatan',
                    'aggregations' => [
                        ['function' => 'sum', 'field' => 'jumlah', 'alias' => 'total_revenue'],
                        ['function' => 'count', 'field' => '*', 'alias' => 'transaction_count'],
                    ],
                    'group_by' => 'DATE_FORMAT(tanggal, "%Y-%m")',
                    'order_by' => 'tanggal',
                    'order_direction' => 'desc',
                    'limit' => 12,
                ],
                'chart_config' => [
                    'type' => Report::CHART_LINE,
                    'label_field' => 'month',
                    'data_fields' => ['total_revenue', 'transaction_count'],
                    'options' => [
                        'responsive' => true,
                        'scales' => [
                            'y' => ['beginAtZero' => true],
                        ],
                    ],
                ],
                'filters' => [
                    [
                        'field' => 'tanggal',
                        'operator' => 'date_range',
                        'parameter' => 'date_range',
                        'value' => [now()->subYear()->format('Y-m-d'), now()->format('Y-m-d')],
                    ],
                ],
            ],
            'patient_growth' => [
                'query_config' => [
                    'model' => 'App\\Models\\Pasien',
                    'aggregations' => [
                        ['function' => 'count', 'field' => '*', 'alias' => 'patient_count'],
                    ],
                    'group_by' => 'DATE_FORMAT(created_at, "%Y-%m")',
                    'order_by' => 'created_at',
                    'order_direction' => 'asc',
                    'limit' => 12,
                ],
                'chart_config' => [
                    'type' => Report::CHART_AREA,
                    'label_field' => 'month',
                    'data_fields' => ['patient_count'],
                    'options' => [
                        'responsive' => true,
                        'plugins' => [
                            'title' => [
                                'display' => true,
                                'text' => 'Patient Growth Over Time',
                            ],
                        ],
                    ],
                ],
                'filters' => [
                    [
                        'field' => 'created_at',
                        'operator' => 'date_range',
                        'parameter' => 'date_range',
                        'value' => [now()->subYear()->format('Y-m-d'), now()->format('Y-m-d')],
                    ],
                ],
            ],
            'user_activity' => [
                'query_config' => [
                    'model' => 'App\\Models\\User',
                    'order_by' => 'last_login_at',
                    'order_direction' => 'desc',
                    'limit' => 50,
                ],
                'columns' => ['name', 'email', 'last_login_at', 'is_active', 'created_at'],
                'filters' => [
                    [
                        'field' => 'is_active',
                        'operator' => '=',
                        'parameter' => 'is_active',
                        'value' => true,
                    ],
                ],
            ],
            'treatment_analytics' => [
                'query_config' => [
                    'model' => 'App\\Models\\Tindakan',
                    'aggregations' => [
                        ['function' => 'count', 'field' => '*', 'alias' => 'treatment_count'],
                        ['function' => 'avg', 'field' => 'harga', 'alias' => 'avg_price'],
                    ],
                    'group_by' => 'nama_tindakan',
                    'order_by' => 'treatment_count',
                    'order_direction' => 'desc',
                    'limit' => 10,
                ],
                'chart_config' => [
                    'type' => Report::CHART_BAR,
                    'label_field' => 'nama_tindakan',
                    'data_fields' => ['treatment_count', 'avg_price'],
                    'options' => [
                        'responsive' => true,
                        'plugins' => [
                            'legend' => ['display' => true],
                        ],
                    ],
                ],
            ],
            default => throw new \Exception("Unknown template: {$templateKey}"),
        };
    }

    /**
     * Share report with user
     */
    public function shareReport(Report $report, User $targetUser, User $sharedBy, array $permissions = [], ?\DateTime $expiresAt = null): void
    {
        $report->share($targetUser, [
            'permissions' => $permissions,
            'expires_at' => $expiresAt,
        ]);

        // Log share action
        AuditLog::logSystem(
            'report_shared',
            $sharedBy,
            "Report '{$report->name}' shared with {$targetUser->name}",
            [
                'report_id' => $report->id,
                'target_user_id' => $targetUser->id,
                'permissions' => $permissions,
                'expires_at' => $expiresAt?->format('Y-m-d H:i:s'),
            ]
        );
    }

    /**
     * Get report analytics
     */
    public function getReportAnalytics(Report $report): array
    {
        $executions = $report->reportExecutions()->recent(30)->get();
        $shares = $report->reportShares()->get();
        
        return [
            'total_executions' => $executions->count(),
            'successful_executions' => $executions->where('status', ReportExecution::STATUS_COMPLETED)->count(),
            'failed_executions' => $executions->where('status', ReportExecution::STATUS_FAILED)->count(),
            'avg_execution_time' => $executions->where('status', ReportExecution::STATUS_COMPLETED)->avg('execution_time'),
            'unique_users' => $executions->unique('user_id')->count(),
            'total_shares' => $shares->count(),
            'active_shares' => $shares->filter(fn($share) => $share->isActive())->count(),
            'last_execution' => $executions->sortByDesc('created_at')->first(),
            'most_frequent_user' => $executions->groupBy('user_id')
                ->map(fn($group) => $group->count())
                ->sortDesc()
                ->keys()
                ->first(),
            'execution_trend' => $this->getExecutionTrend($executions),
        ];
    }

    /**
     * Get execution trend data
     */
    private function getExecutionTrend($executions): array
    {
        $trend = [];
        
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $count = $executions->filter(fn($execution) => $execution->created_at->format('Y-m-d') === $date)->count();
            $trend[] = ['date' => $date, 'count' => $count];
        }
        
        return $trend;
    }

    /**
     * Get dashboard data
     */
    public function getDashboardData(): array
    {
        $reports = Report::active()->get();
        $executions = ReportExecution::recent(30)->get();
        
        return [
            'total_reports' => $reports->count(),
            'public_reports' => $reports->where('is_public', true)->count(),
            'scheduled_reports' => $reports->where('is_scheduled', true)->count(),
            'reports_by_category' => $reports->groupBy('category')->map->count(),
            'reports_by_type' => $reports->groupBy('report_type')->map->count(),
            'total_executions' => $executions->count(),
            'successful_executions' => $executions->where('status', ReportExecution::STATUS_COMPLETED)->count(),
            'failed_executions' => $executions->where('status', ReportExecution::STATUS_FAILED)->count(),
            'avg_execution_time' => $executions->where('status', ReportExecution::STATUS_COMPLETED)->avg('execution_time'),
            'most_executed_reports' => $executions->groupBy('report_id')
                ->map->count()
                ->sortDesc()
                ->take(5),
            'recent_executions' => $executions->sortByDesc('created_at')->take(10),
        ];
    }

    /**
     * Generate cache key for report execution
     */
    private function getCacheKey(Report $report, array $parameters): string
    {
        $parameterHash = md5(json_encode($parameters));
        return "report_{$report->id}_{$parameterHash}";
    }

    /**
     * Clear report cache
     */
    public function clearReportCache(Report $report): void
    {
        // Clear all cache keys for this report
        $pattern = "report_{$report->id}_*";
        // In a real implementation, you'd use a more sophisticated cache clearing mechanism
        Cache::flush(); // This clears all cache, in production use a more targeted approach
    }

    /**
     * Validate report configuration
     */
    public function validateReportConfig(array $config): array
    {
        $errors = [];
        
        // Validate query config
        if (!isset($config['query_config']['model'])) {
            $errors[] = 'Model is required in query configuration';
        } elseif (!class_exists($config['query_config']['model'])) {
            $errors[] = 'Invalid model class specified';
        }
        
        // Validate chart config for chart reports
        if ($config['report_type'] === Report::TYPE_CHART) {
            if (!isset($config['chart_config']['type'])) {
                $errors[] = 'Chart type is required for chart reports';
            }
        }
        
        // Validate filters
        if (isset($config['filters'])) {
            foreach ($config['filters'] as $index => $filter) {
                if (!isset($filter['field'])) {
                    $errors[] = "Filter #{$index}: field is required";
                }
                if (!isset($filter['operator'])) {
                    $errors[] = "Filter #{$index}: operator is required";
                }
            }
        }
        
        return $errors;
    }

    /**
     * Get system report statistics
     */
    public function getSystemStats(): array
    {
        return [
            'total_reports' => Report::count(),
            'active_reports' => Report::active()->count(),
            'public_reports' => Report::where('is_public', true)->count(),
            'scheduled_reports' => Report::where('is_scheduled', true)->count(),
            'total_executions' => ReportExecution::count(),
            'executions_today' => ReportExecution::whereDate('created_at', today())->count(),
            'avg_execution_time' => ReportExecution::completed()->avg('execution_time'),
            'total_shares' => \App\Models\ReportShare::count(),
            'active_shares' => \App\Models\ReportShare::active()->count(),
        ];
    }
}