<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'report_type',
        'category',
        'query_config',
        'chart_config',
        'filters',
        'columns',
        'is_public',
        'is_scheduled',
        'schedule_config',
        'last_generated_at',
        'cache_duration',
        'status',
        'tags',
    ];

    protected $casts = [
        'query_config' => 'array',
        'chart_config' => 'array',
        'filters' => 'array',
        'columns' => 'array',
        'schedule_config' => 'array',
        'tags' => 'array',
        'is_public' => 'boolean',
        'is_scheduled' => 'boolean',
        'last_generated_at' => 'datetime',
    ];

    // Report types
    const TYPE_TABLE = 'table';
    const TYPE_CHART = 'chart';
    const TYPE_DASHBOARD = 'dashboard';
    const TYPE_EXPORT = 'export';
    const TYPE_KPI = 'kpi';

    // Report categories
    const CATEGORY_FINANCIAL = 'financial';
    const CATEGORY_OPERATIONAL = 'operational';
    const CATEGORY_MEDICAL = 'medical';
    const CATEGORY_ADMINISTRATIVE = 'administrative';
    const CATEGORY_SECURITY = 'security';
    const CATEGORY_PERFORMANCE = 'performance';
    const CATEGORY_CUSTOM = 'custom';

    // Chart types
    const CHART_LINE = 'line';
    const CHART_BAR = 'bar';
    const CHART_PIE = 'pie';
    const CHART_DOUGHNUT = 'doughnut';
    const CHART_AREA = 'area';
    const CHART_SCATTER = 'scatter';
    const CHART_RADAR = 'radar';

    // Status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_DRAFT = 'draft';
    const STATUS_ARCHIVED = 'archived';

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reportExecutions(): HasMany
    {
        return $this->hasMany(ReportExecution::class);
    }

    public function executions(): HasMany
    {
        return $this->hasMany(ReportExecution::class);
    }

    public function reportShares(): HasMany
    {
        return $this->hasMany(ReportShare::class);
    }

    public function shares(): HasMany
    {
        return $this->hasMany(ReportShare::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeScheduled($query)
    {
        return $query->where('is_scheduled', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('report_type', $type);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Methods
    public function execute(array $parameters = []): ReportExecution
    {
        $execution = ReportExecution::create([
            'report_id' => $this->id,
            'user_id' => auth()->id(),
            'parameters' => $parameters,
            'status' => ReportExecution::STATUS_RUNNING,
            'started_at' => now(),
        ]);

        try {
            $data = $this->generateData($parameters);
            
            $execution->update([
                'status' => ReportExecution::STATUS_COMPLETED,
                'completed_at' => now(),
                'result_data' => $data,
                'execution_time' => $execution->started_at->diffInMilliseconds(now()),
            ]);

            $this->update(['last_generated_at' => now()]);

        } catch (\Exception $e) {
            $execution->update([
                'status' => ReportExecution::STATUS_FAILED,
                'completed_at' => now(),
                'error_message' => $e->getMessage(),
                'execution_time' => $execution->started_at->diffInMilliseconds(now()),
            ]);
        }

        return $execution;
    }

    public function generateData(array $parameters = []): array
    {
        $queryConfig = $this->query_config;
        $modelClass = $queryConfig['model'] ?? null;

        if (!$modelClass || !class_exists($modelClass)) {
            throw new \Exception('Invalid model class specified');
        }

        $query = $modelClass::query();

        // Apply filters
        $this->applyFilters($query, $parameters);

        // Apply grouping
        if (isset($queryConfig['group_by'])) {
            $query->groupBy($queryConfig['group_by']);
        }

        // Apply aggregations
        if (isset($queryConfig['aggregations'])) {
            $this->applyAggregations($query, $queryConfig['aggregations']);
        }

        // Apply ordering
        if (isset($queryConfig['order_by'])) {
            $query->orderBy($queryConfig['order_by'], $queryConfig['order_direction'] ?? 'asc');
        }

        // Apply limit
        if (isset($queryConfig['limit'])) {
            $query->limit($queryConfig['limit']);
        }

        // Execute query
        $results = $query->get();

        // Format results for chart if needed
        if ($this->report_type === self::TYPE_CHART) {
            return $this->formatChartData($results);
        }

        return $results->toArray();
    }

    private function applyFilters($query, array $parameters)
    {
        $filters = $this->filters ?? [];
        
        foreach ($filters as $filter) {
            $field = $filter['field'] ?? null;
            $operator = $filter['operator'] ?? '=';
            $value = $parameters[$filter['parameter'] ?? $field] ?? $filter['value'] ?? null;

            if (!$field || $value === null) {
                continue;
            }

            switch ($operator) {
                case '=':
                case '!=':
                case '>':
                case '>=':
                case '<':
                case '<=':
                    $query->where($field, $operator, $value);
                    break;
                case 'like':
                    $query->where($field, 'LIKE', "%{$value}%");
                    break;
                case 'in':
                    $query->whereIn($field, is_array($value) ? $value : explode(',', $value));
                    break;
                case 'between':
                    if (is_array($value) && count($value) === 2) {
                        $query->whereBetween($field, $value);
                    }
                    break;
                case 'date_range':
                    if (is_array($value) && count($value) === 2) {
                        $query->whereBetween($field, $value);
                    }
                    break;
            }
        }
    }

    private function applyAggregations($query, array $aggregations)
    {
        foreach ($aggregations as $aggregation) {
            $function = $aggregation['function'] ?? 'count';
            $field = $aggregation['field'] ?? '*';
            $alias = $aggregation['alias'] ?? $function . '_' . $field;

            switch ($function) {
                case 'count':
                    $query->selectRaw("COUNT({$field}) as {$alias}");
                    break;
                case 'sum':
                    $query->selectRaw("SUM({$field}) as {$alias}");
                    break;
                case 'avg':
                    $query->selectRaw("AVG({$field}) as {$alias}");
                    break;
                case 'min':
                    $query->selectRaw("MIN({$field}) as {$alias}");
                    break;
                case 'max':
                    $query->selectRaw("MAX({$field}) as {$alias}");
                    break;
            }
        }
    }

    private function formatChartData($results): array
    {
        $chartConfig = $this->chart_config ?? [];
        $chartType = $chartConfig['type'] ?? self::CHART_BAR;
        
        $labels = [];
        $datasets = [];
        $data = [];

        foreach ($results as $result) {
            $resultArray = is_array($result) ? $result : $result->toArray();
            
            // Extract label (usually first field or specified label field)
            $labelField = $chartConfig['label_field'] ?? array_keys($resultArray)[0];
            $labels[] = $resultArray[$labelField] ?? '';
            
            // Extract data values
            $dataFields = $chartConfig['data_fields'] ?? array_slice(array_keys($resultArray), 1);
            foreach ($dataFields as $field) {
                if (!isset($data[$field])) {
                    $data[$field] = [];
                }
                $data[$field][] = $resultArray[$field] ?? 0;
            }
        }

        // Format datasets
        foreach ($data as $field => $values) {
            $datasets[] = [
                'label' => ucwords(str_replace('_', ' ', $field)),
                'data' => $values,
                'backgroundColor' => $this->getChartColor($field),
                'borderColor' => $this->getChartColor($field, 0.8),
                'borderWidth' => 2,
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets,
            'type' => $chartType,
            'options' => $chartConfig['options'] ?? [],
        ];
    }

    private function getChartColor($field, $alpha = 0.6): string
    {
        $colors = [
            'rgba(54, 162, 235, ' . $alpha . ')',  // Blue
            'rgba(255, 99, 132, ' . $alpha . ')',  // Red
            'rgba(255, 205, 86, ' . $alpha . ')',  // Yellow
            'rgba(75, 192, 192, ' . $alpha . ')',  // Green
            'rgba(153, 102, 255, ' . $alpha . ')', // Purple
            'rgba(255, 159, 64, ' . $alpha . ')',  // Orange
        ];

        $index = crc32($field) % count($colors);
        return $colors[$index];
    }

    public function share(User $user, array $permissions = []): ReportShare
    {
        return ReportShare::create([
            'report_id' => $this->id,
            'user_id' => $user->id,
            'shared_by' => auth()->id(),
            'permissions' => $permissions,
            'expires_at' => $permissions['expires_at'] ?? null,
        ]);
    }

    public function canView(User $user): bool
    {
        if ($this->user_id === $user->id || $this->is_public) {
            return true;
        }

        return $this->reportShares()
            ->where('user_id', $user->id)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    public function canEdit(User $user): bool
    {
        if ($this->user_id === $user->id) {
            return true;
        }

        return $this->reportShares()
            ->where('user_id', $user->id)
            ->whereJsonContains('permissions', 'edit')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    public function getTypeIcon(): string
    {
        return match($this->report_type) {
            self::TYPE_TABLE => 'heroicon-o-table-cells',
            self::TYPE_CHART => 'heroicon-o-chart-bar',
            self::TYPE_DASHBOARD => 'heroicon-o-squares-2x2',
            self::TYPE_EXPORT => 'heroicon-o-arrow-down-tray',
            self::TYPE_KPI => 'heroicon-o-chart-pie',
            default => 'heroicon-o-document-text',
        };
    }

    public function getCategoryColor(): string
    {
        return match($this->category) {
            self::CATEGORY_FINANCIAL => 'success',
            self::CATEGORY_OPERATIONAL => 'primary',
            self::CATEGORY_MEDICAL => 'info',
            self::CATEGORY_ADMINISTRATIVE => 'warning',
            self::CATEGORY_SECURITY => 'danger',
            self::CATEGORY_PERFORMANCE => 'secondary',
            default => 'gray',
        };
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            self::STATUS_ACTIVE => 'success',
            self::STATUS_INACTIVE => 'warning',
            self::STATUS_DRAFT => 'gray',
            self::STATUS_ARCHIVED => 'secondary',
            default => 'gray',
        };
    }

    public function getExecutionStats(): array
    {
        $executions = $this->reportExecutions()->recent(30)->get();
        
        return [
            'total_executions' => $executions->count(),
            'successful_executions' => $executions->where('status', ReportExecution::STATUS_COMPLETED)->count(),
            'failed_executions' => $executions->where('status', ReportExecution::STATUS_FAILED)->count(),
            'avg_execution_time' => $executions->where('status', ReportExecution::STATUS_COMPLETED)->avg('execution_time'),
            'last_execution' => $executions->sortByDesc('created_at')->first(),
        ];
    }

    // Static methods
    public static function getAvailableModels(): array
    {
        return [
            'App\\Models\\User' => [
                'name' => 'Users',
                'fields' => ['id', 'name', 'email', 'created_at', 'is_active'],
                'aggregatable' => ['id'],
            ],
            'App\\Models\\Pasien' => [
                'name' => 'Patients',
                'fields' => ['id', 'nama', 'alamat', 'no_telepon', 'tanggal_lahir', 'created_at'],
                'aggregatable' => ['id'],
            ],
            'App\\Models\\Tindakan' => [
                'name' => 'Treatments',
                'fields' => ['id', 'nama_tindakan', 'harga', 'deskripsi', 'created_at'],
                'aggregatable' => ['id', 'harga'],
            ],
            'App\\Models\\Pendapatan' => [
                'name' => 'Revenue',
                'fields' => ['id', 'jumlah', 'tanggal', 'deskripsi', 'created_at'],
                'aggregatable' => ['id', 'jumlah'],
            ],
            'App\\Models\\Pengeluaran' => [
                'name' => 'Expenses',
                'fields' => ['id', 'jumlah', 'tanggal', 'deskripsi', 'created_at'],
                'aggregatable' => ['id', 'jumlah'],
            ],
        ];
    }

    public static function getReportTemplates(): array
    {
        return [
            'financial_summary' => [
                'name' => 'Financial Summary',
                'category' => self::CATEGORY_FINANCIAL,
                'type' => self::TYPE_CHART,
                'description' => 'Monthly revenue vs expenses comparison',
            ],
            'patient_growth' => [
                'name' => 'Patient Growth',
                'category' => self::CATEGORY_MEDICAL,
                'type' => self::TYPE_CHART,
                'description' => 'Patient registration trends over time',
            ],
            'user_activity' => [
                'name' => 'User Activity',
                'category' => self::CATEGORY_ADMINISTRATIVE,
                'type' => self::TYPE_TABLE,
                'description' => 'User login and activity statistics',
            ],
            'treatment_analytics' => [
                'name' => 'Treatment Analytics',
                'category' => self::CATEGORY_MEDICAL,
                'type' => self::TYPE_CHART,
                'description' => 'Most popular treatments and pricing analysis',
            ],
        ];
    }
}