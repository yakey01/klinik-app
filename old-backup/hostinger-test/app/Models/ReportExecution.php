<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportExecution extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_id',
        'user_id',
        'parameters',
        'status',
        'result_data',
        'result_count',
        'execution_time',
        'memory_usage',
        'started_at',
        'completed_at',
        'error_message',
        'cache_key',
        'expires_at',
    ];

    protected $casts = [
        'parameters' => 'array',
        'result_data' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_RUNNING = 'running';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    // Relationships
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByReport($query, $reportId)
    {
        return $query->where('report_id', $reportId);
    }

    // Methods
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }

    public function getDuration(): ?string
    {
        if (!$this->started_at) {
            return null;
        }

        $endTime = $this->completed_at ?? now();
        $seconds = $this->started_at->diffInSeconds($endTime);

        if ($seconds < 60) {
            return "{$seconds}s";
        } elseif ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            $remainingSeconds = $seconds % 60;
            return "{$minutes}m {$remainingSeconds}s";
        } else {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            return "{$hours}h {$minutes}m";
        }
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'gray',
            self::STATUS_RUNNING => 'primary',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_CANCELLED => 'warning',
            default => 'gray',
        };
    }

    public function getStatusIcon(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'heroicon-o-clock',
            self::STATUS_RUNNING => 'heroicon-o-arrow-path',
            self::STATUS_COMPLETED => 'heroicon-o-check-circle',
            self::STATUS_FAILED => 'heroicon-o-x-circle',
            self::STATUS_CANCELLED => 'heroicon-o-stop-circle',
            default => 'heroicon-o-question-mark-circle',
        };
    }

    public function getFormattedExecutionTime(): string
    {
        if (!$this->execution_time) {
            return 'N/A';
        }

        if ($this->execution_time < 1000) {
            return $this->execution_time . 'ms';
        } elseif ($this->execution_time < 60000) {
            return round($this->execution_time / 1000, 2) . 's';
        } else {
            $seconds = $this->execution_time / 1000;
            $minutes = floor($seconds / 60);
            $remainingSeconds = $seconds % 60;
            return "{$minutes}m " . round($remainingSeconds, 2) . 's';
        }
    }

    public function getFormattedMemoryUsage(): string
    {
        if (!$this->memory_usage) {
            return 'N/A';
        }

        $bytes = $this->memory_usage;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function canCancel(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_RUNNING]);
    }

    public function cancel(): void
    {
        if ($this->canCancel()) {
            $this->update([
                'status' => self::STATUS_CANCELLED,
                'completed_at' => now(),
            ]);
        }
    }

    public function hasExpired(): bool
    {
        return $this->expires_at && $this->expires_at < now();
    }

    public function getResultSummary(): array
    {
        if (!$this->isCompleted() || !$this->result_data) {
            return [];
        }

        $data = $this->result_data;
        
        if (isset($data['labels']) && isset($data['datasets'])) {
            // Chart data
            return [
                'type' => 'chart',
                'chart_type' => $data['type'] ?? 'bar',
                'labels_count' => count($data['labels']),
                'datasets_count' => count($data['datasets']),
                'data_points' => array_sum(array_map(fn($dataset) => count($dataset['data'] ?? []), $data['datasets'])),
            ];
        } else {
            // Table data
            return [
                'type' => 'table',
                'rows' => count($data),
                'columns' => $data ? count(array_keys($data[0])) : 0,
            ];
        }
    }

    // Static methods
    public static function cleanup($days = 30): int
    {
        return self::where('created_at', '<', now()->subDays($days))
            ->whereIn('status', [self::STATUS_COMPLETED, self::STATUS_FAILED, self::STATUS_CANCELLED])
            ->delete();
    }

    public static function getExecutionStats(): array
    {
        $recent = self::where('created_at', '>=', now()->subDays(30));
        
        return [
            'total_executions' => $recent->count(),
            'successful_executions' => $recent->where('status', self::STATUS_COMPLETED)->count(),
            'failed_executions' => $recent->where('status', self::STATUS_FAILED)->count(),
            'avg_execution_time' => $recent->where('status', self::STATUS_COMPLETED)->avg('execution_time'),
            'active_executions' => self::whereIn('status', [self::STATUS_PENDING, self::STATUS_RUNNING])->count(),
        ];
    }
}