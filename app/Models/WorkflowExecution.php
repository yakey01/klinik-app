<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class WorkflowExecution extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'user_id',
        'execution_id',
        'trigger_source',
        'trigger_data',
        'status',
        'current_step',
        'step_results',
        'context_data',
        'execution_log',
        'error_message',
        'error_details',
        'started_at',
        'completed_at',
        'execution_time',
        'memory_usage',
        'steps_completed',
        'total_steps',
        'retry_count',
        'warnings_count',
        'performance_metrics',
        'output_data',
        'priority',
    ];

    protected $casts = [
        'trigger_data' => 'array',
        'current_step' => 'array',
        'step_results' => 'array',
        'context_data' => 'array',
        'error_details' => 'array',
        'performance_metrics' => 'array',
        'output_data' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_RUNNING = 'running';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_TIMEOUT = 'timeout';

    // Priority constants
    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    /**
     * Boot method to auto-generate execution ID
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->execution_id) {
                $model->execution_id = Str::uuid()->toString();
            }
        });
    }

    /**
     * Get the workflow that owns the execution
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    /**
     * Get the user that triggered the execution
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Start the execution
     */
    public function start(): void
    {
        $this->update([
            'status' => self::STATUS_RUNNING,
            'started_at' => now(),
        ]);
    }

    /**
     * Complete the execution
     */
    public function complete(array $outputData = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'execution_time' => $this->started_at ? now()->diffInMilliseconds($this->started_at) : null,
            'output_data' => $outputData,
        ]);

        // Update workflow statistics
        $this->workflow->updateExecutionStats($this->execution_time, true);
    }

    /**
     * Fail the execution
     */
    public function fail(string $errorMessage, array $errorDetails = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'completed_at' => now(),
            'error_message' => $errorMessage,
            'error_details' => $errorDetails,
            'execution_time' => $this->started_at ? now()->diffInMilliseconds($this->started_at) : null,
        ]);

        // Update workflow statistics
        $this->workflow->updateExecutionStats($this->execution_time, false);
    }

    /**
     * Cancel the execution
     */
    public function cancel(): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark as timeout
     */
    public function timeout(): void
    {
        $this->update([
            'status' => self::STATUS_TIMEOUT,
            'completed_at' => now(),
            'error_message' => 'Execution exceeded timeout limit',
        ]);

        // Update workflow statistics
        $this->workflow->updateExecutionStats($this->execution_time, false);
    }

    /**
     * Update current step
     */
    public function updateCurrentStep(array $stepData): void
    {
        $this->update([
            'current_step' => $stepData,
        ]);
    }

    /**
     * Update step results
     */
    public function updateStepResults(array $results): void
    {
        $this->update([
            'step_results' => $results,
        ]);
    }

    /**
     * Update context data
     */
    public function updateContextData(array $contextData): void
    {
        $this->update([
            'context_data' => $contextData,
        ]);
    }

    /**
     * Append to execution log
     */
    public function appendLog(string $message): void
    {
        $currentLog = $this->execution_log ?? '';
        $timestamp = now()->format('Y-m-d H:i:s');
        $newLog = $currentLog . "\n[{$timestamp}] {$message}";
        
        $this->update([
            'execution_log' => $newLog,
        ]);
    }

    /**
     * Update progress
     */
    public function updateProgress(int $stepsCompleted, int $totalSteps = null): void
    {
        $data = [
            'steps_completed' => $stepsCompleted,
        ];

        if ($totalSteps !== null) {
            $data['total_steps'] = $totalSteps;
        }

        $this->update($data);
    }

    /**
     * Get progress percentage
     */
    public function getProgressPercentage(): int
    {
        if ($this->total_steps === 0) {
            return 0;
        }

        return min(100, round(($this->steps_completed / $this->total_steps) * 100));
    }

    /**
     * Get formatted execution time
     */
    public function getFormattedExecutionTime(): string
    {
        if (!$this->execution_time) {
            return 'N/A';
        }

        $seconds = $this->execution_time / 1000;
        
        if ($seconds < 60) {
            return number_format($seconds, 2) . 's';
        } elseif ($seconds < 3600) {
            return gmdate('i:s', $seconds);
        } else {
            return gmdate('H:i:s', $seconds);
        }
    }

    /**
     * Get formatted memory usage
     */
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

    /**
     * Check if execution is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if execution is running
     */
    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }

    /**
     * Check if execution is failed
     */
    public function isFailed(): bool
    {
        return in_array($this->status, [self::STATUS_FAILED, self::STATUS_TIMEOUT]);
    }

    /**
     * Check if execution is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Get status color
     */
    public function getStatusColor(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'gray',
            self::STATUS_RUNNING => 'warning',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_CANCELLED => 'secondary',
            self::STATUS_TIMEOUT => 'danger',
            default => 'gray',
        };
    }

    /**
     * Get priority color
     */
    public function getPriorityColor(): string
    {
        return match($this->priority) {
            self::PRIORITY_LOW => 'gray',
            self::PRIORITY_NORMAL => 'primary',
            self::PRIORITY_HIGH => 'warning',
            self::PRIORITY_URGENT => 'danger',
            default => 'primary',
        };
    }

    /**
     * Get duration in seconds
     */
    public function getDuration(): int
    {
        if (!$this->started_at) {
            return 0;
        }

        $endTime = $this->completed_at ?? now();
        return $this->started_at->diffInSeconds($endTime);
    }

    /**
     * Scope for pending executions
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for running executions
     */
    public function scopeRunning($query)
    {
        return $query->where('status', self::STATUS_RUNNING);
    }

    /**
     * Scope for completed executions
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for failed executions
     */
    public function scopeFailed($query)
    {
        return $query->whereIn('status', [self::STATUS_FAILED, self::STATUS_TIMEOUT]);
    }

    /**
     * Scope for recent executions
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}