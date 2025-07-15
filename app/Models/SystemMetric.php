<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SystemMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'metric_type',
        'metric_name',
        'metric_value',
        'metric_data',
        'alert_threshold',
        'status',
        'recorded_at',
    ];

    protected $casts = [
        'metric_data' => 'array',
        'metric_value' => 'decimal:2',
        'alert_threshold' => 'decimal:2',
        'recorded_at' => 'datetime',
    ];

    // Metric types
    const TYPE_SYSTEM = 'system';
    const TYPE_DATABASE = 'database';
    const TYPE_CACHE = 'cache';
    const TYPE_QUEUE = 'queue';
    const TYPE_STORAGE = 'storage';
    const TYPE_PERFORMANCE = 'performance';
    const TYPE_SECURITY = 'security';
    const TYPE_APPLICATION = 'application';

    // Status constants
    const STATUS_HEALTHY = 'healthy';
    const STATUS_WARNING = 'warning';
    const STATUS_CRITICAL = 'critical';
    const STATUS_UNKNOWN = 'unknown';

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('metric_type', $type);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('recorded_at', '>=', now()->subHours($hours));
    }

    public function scopeCritical($query)
    {
        return $query->where('status', self::STATUS_CRITICAL);
    }

    public function scopeWarning($query)
    {
        return $query->where('status', self::STATUS_WARNING);
    }

    // Static methods for recording metrics
    public static function recordSystemMetric($name, $value, $data = null, $threshold = null)
    {
        return self::recordMetric(self::TYPE_SYSTEM, $name, $value, $data, $threshold);
    }

    public static function recordDatabaseMetric($name, $value, $data = null, $threshold = null)
    {
        return self::recordMetric(self::TYPE_DATABASE, $name, $value, $data, $threshold);
    }

    public static function recordCacheMetric($name, $value, $data = null, $threshold = null)
    {
        return self::recordMetric(self::TYPE_CACHE, $name, $value, $data, $threshold);
    }

    public static function recordQueueMetric($name, $value, $data = null, $threshold = null)
    {
        return self::recordMetric(self::TYPE_QUEUE, $name, $value, $data, $threshold);
    }

    public static function recordStorageMetric($name, $value, $data = null, $threshold = null)
    {
        return self::recordMetric(self::TYPE_STORAGE, $name, $value, $data, $threshold);
    }

    public static function recordPerformanceMetric($name, $value, $data = null, $threshold = null)
    {
        return self::recordMetric(self::TYPE_PERFORMANCE, $name, $value, $data, $threshold);
    }

    public static function recordSecurityMetric($name, $value, $data = null, $threshold = null)
    {
        return self::recordMetric(self::TYPE_SECURITY, $name, $value, $data, $threshold);
    }

    public static function recordApplicationMetric($name, $value, $data = null, $threshold = null)
    {
        return self::recordMetric(self::TYPE_APPLICATION, $name, $value, $data, $threshold);
    }

    // Core recording method
    private static function recordMetric($type, $name, $value, $data = null, $threshold = null)
    {
        $status = self::calculateStatus($value, $threshold);
        
        return self::create([
            'metric_type' => $type,
            'metric_name' => $name,
            'metric_value' => $value,
            'metric_data' => $data,
            'alert_threshold' => $threshold,
            'status' => $status,
            'recorded_at' => now(),
        ]);
    }

    // Calculate status based on value and threshold
    private static function calculateStatus($value, $threshold = null)
    {
        if ($threshold === null) {
            return self::STATUS_HEALTHY;
        }

        if ($value >= $threshold) {
            return self::STATUS_CRITICAL;
        } elseif ($value >= ($threshold * 0.8)) {
            return self::STATUS_WARNING;
        }

        return self::STATUS_HEALTHY;
    }

    // Get current system health summary
    public static function getHealthSummary()
    {
        $cacheKey = 'system_health_summary';
        
        return Cache::remember($cacheKey, 300, function () {
            $recent = self::recent(1)->get();
            
            return [
                'total_metrics' => $recent->count(),
                'healthy' => $recent->where('status', self::STATUS_HEALTHY)->count(),
                'warning' => $recent->where('status', self::STATUS_WARNING)->count(),
                'critical' => $recent->where('status', self::STATUS_CRITICAL)->count(),
                'last_update' => $recent->max('recorded_at'),
                'overall_status' => self::calculateOverallStatus($recent),
            ];
        });
    }

    // Calculate overall system status
    private static function calculateOverallStatus($metrics)
    {
        $criticalCount = $metrics->where('status', self::STATUS_CRITICAL)->count();
        $warningCount = $metrics->where('status', self::STATUS_WARNING)->count();
        
        if ($criticalCount > 0) {
            return self::STATUS_CRITICAL;
        } elseif ($warningCount > 0) {
            return self::STATUS_WARNING;
        }
        
        return self::STATUS_HEALTHY;
    }

    // Get metrics by type with recent data
    public static function getMetricsByType($type, $hours = 24)
    {
        return self::byType($type)
            ->recent($hours)
            ->orderBy('recorded_at', 'desc')
            ->get()
            ->groupBy('metric_name');
    }

    // Get performance trends
    public static function getPerformanceTrends($hours = 24)
    {
        return self::byType(self::TYPE_PERFORMANCE)
            ->recent($hours)
            ->select('metric_name', 'metric_value', 'recorded_at')
            ->orderBy('recorded_at')
            ->get()
            ->groupBy('metric_name');
    }

    // Cleanup old metrics
    public static function cleanup($days = 30)
    {
        $cutoff = now()->subDays($days);
        
        $deleted = self::where('recorded_at', '<', $cutoff)->delete();
        
        // Clear related cache
        Cache::forget('system_health_summary');
        
        return $deleted;
    }

    // Get critical alerts
    public static function getCriticalAlerts()
    {
        return self::critical()
            ->recent(24)
            ->orderBy('recorded_at', 'desc')
            ->get();
    }

    // Get metric statistics
    public static function getMetricStats($type, $name, $hours = 24)
    {
        $metrics = self::byType($type)
            ->where('metric_name', $name)
            ->recent($hours)
            ->pluck('metric_value');

        if ($metrics->isEmpty()) {
            return null;
        }

        return [
            'current' => $metrics->first(),
            'average' => $metrics->average(),
            'min' => $metrics->min(),
            'max' => $metrics->max(),
            'trend' => $metrics->count() > 1 ? 
                ($metrics->first() > $metrics->last() ? 'up' : 'down') : 'stable',
        ];
    }
}