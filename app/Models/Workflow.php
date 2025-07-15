<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'user_id',
        'trigger_type',
        'trigger_config',
        'steps',
        'conditions',
        'status',
        'category',
        'tags',
        'priority',
        'timeout',
        'is_public',
        'is_template',
        'max_retries',
        'retry_config',
        'notification_config',
        'error_handling',
        'last_executed_at',
        'execution_count',
        'success_count',
        'failure_count',
        'avg_execution_time',
        'performance_stats',
        'next_run_at',
        'schedule_frequency',
        'is_enabled',
    ];

    protected $casts = [
        'trigger_config' => 'array',
        'steps' => 'array',
        'conditions' => 'array',
        'tags' => 'array',
        'retry_config' => 'array',
        'notification_config' => 'array',
        'error_handling' => 'array',
        'performance_stats' => 'array',
        'last_executed_at' => 'datetime',
        'next_run_at' => 'datetime',
        'is_public' => 'boolean',
        'is_template' => 'boolean',
        'is_enabled' => 'boolean',
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_ACTIVE = 'active';
    const STATUS_PAUSED = 'paused';
    const STATUS_ARCHIVED = 'archived';

    // Trigger type constants
    const TRIGGER_MANUAL = 'manual';
    const TRIGGER_SCHEDULED = 'scheduled';
    const TRIGGER_EVENT = 'event';
    const TRIGGER_WEBHOOK = 'webhook';

    // Category constants
    const CATEGORY_GENERAL = 'general';
    const CATEGORY_DATA = 'data';
    const CATEGORY_NOTIFICATIONS = 'notifications';
    const CATEGORY_REPORTS = 'reports';

    // Priority constants
    const PRIORITY_LOW = 1;
    const PRIORITY_NORMAL = 2;
    const PRIORITY_HIGH = 3;
    const PRIORITY_URGENT = 4;
    const PRIORITY_CRITICAL = 5;

    /**
     * Get the user that owns the workflow
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the workflow executions
     */
    public function executions(): HasMany
    {
        return $this->hasMany(WorkflowExecution::class);
    }

    /**
     * Get recent executions
     */
    public function recentExecutions(): HasMany
    {
        return $this->hasMany(WorkflowExecution::class)->latest()->limit(10);
    }

    /**
     * Get successful executions
     */
    public function successfulExecutions(): HasMany
    {
        return $this->hasMany(WorkflowExecution::class)->where('status', 'completed');
    }

    /**
     * Get failed executions
     */
    public function failedExecutions(): HasMany
    {
        return $this->hasMany(WorkflowExecution::class)->where('status', 'failed');
    }

    /**
     * Check if workflow is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE && $this->is_enabled;
    }

    /**
     * Check if workflow is scheduled
     */
    public function isScheduled(): bool
    {
        return $this->trigger_type === self::TRIGGER_SCHEDULED;
    }

    /**
     * Get success rate
     */
    public function getSuccessRate(): float
    {
        if ($this->execution_count === 0) {
            return 0;
        }

        return round(($this->success_count / $this->execution_count) * 100, 2);
    }

    /**
     * Get formatted execution time
     */
    public function getFormattedExecutionTime(): string
    {
        if (!$this->avg_execution_time) {
            return 'N/A';
        }

        $seconds = $this->avg_execution_time / 1000;
        
        if ($seconds < 60) {
            return number_format($seconds, 2) . 's';
        } elseif ($seconds < 3600) {
            return gmdate('i:s', $seconds);
        } else {
            return gmdate('H:i:s', $seconds);
        }
    }

    /**
     * Get status color
     */
    public function getStatusColor(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'gray',
            self::STATUS_ACTIVE => 'success',
            self::STATUS_PAUSED => 'warning',
            self::STATUS_ARCHIVED => 'danger',
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
            self::PRIORITY_CRITICAL => 'red',
            default => 'primary',
        };
    }

    /**
     * Get priority label
     */
    public function getPriorityLabel(): string
    {
        return match($this->priority) {
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_NORMAL => 'Normal',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_URGENT => 'Urgent',
            self::PRIORITY_CRITICAL => 'Critical',
            default => 'Normal',
        };
    }

    /**
     * Get trigger type icon
     */
    public function getTriggerTypeIcon(): string
    {
        return match($this->trigger_type) {
            self::TRIGGER_MANUAL => 'heroicon-o-hand-raised',
            self::TRIGGER_SCHEDULED => 'heroicon-o-clock',
            self::TRIGGER_EVENT => 'heroicon-o-bolt',
            self::TRIGGER_WEBHOOK => 'heroicon-o-globe-alt',
            default => 'heroicon-o-cog',
        };
    }

    /**
     * Get category icon
     */
    public function getCategoryIcon(): string
    {
        return match($this->category) {
            self::CATEGORY_GENERAL => 'heroicon-o-cog',
            self::CATEGORY_DATA => 'heroicon-o-circle-stack',
            self::CATEGORY_NOTIFICATIONS => 'heroicon-o-bell',
            self::CATEGORY_REPORTS => 'heroicon-o-chart-bar',
            default => 'heroicon-o-cog',
        };
    }

    /**
     * Get available trigger types
     */
    public static function getTriggerTypes(): array
    {
        return [
            self::TRIGGER_MANUAL => 'Manual',
            self::TRIGGER_SCHEDULED => 'Scheduled',
            self::TRIGGER_EVENT => 'Event-based',
            self::TRIGGER_WEBHOOK => 'Webhook',
        ];
    }

    /**
     * Get available categories
     */
    public static function getCategories(): array
    {
        return [
            self::CATEGORY_GENERAL => 'General',
            self::CATEGORY_DATA => 'Data Processing',
            self::CATEGORY_NOTIFICATIONS => 'Notifications',
            self::CATEGORY_REPORTS => 'Reports',
        ];
    }

    /**
     * Get available priorities
     */
    public static function getPriorities(): array
    {
        return [
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_NORMAL => 'Normal',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_URGENT => 'Urgent',
            self::PRIORITY_CRITICAL => 'Critical',
        ];
    }

    /**
     * Get workflow steps count
     */
    public function getStepsCount(): int
    {
        return count($this->steps ?? []);
    }

    /**
     * Update execution statistics
     */
    public function updateExecutionStats(int $executionTime, bool $success): void
    {
        $this->increment('execution_count');
        
        if ($success) {
            $this->increment('success_count');
        } else {
            $this->increment('failure_count');
        }

        // Update average execution time
        $currentAvg = $this->avg_execution_time ?? 0;
        $newAvg = (($currentAvg * ($this->execution_count - 1)) + $executionTime) / $this->execution_count;
        
        $this->update([
            'avg_execution_time' => $newAvg,
            'last_executed_at' => now(),
        ]);
    }

    /**
     * Schedule next run
     */
    public function scheduleNextRun(): void
    {
        if (!$this->isScheduled() || !$this->schedule_frequency) {
            return;
        }

        // Parse cron expression and calculate next run time
        $cron = \Cron\CronExpression::factory($this->schedule_frequency);
        $nextRun = $cron->getNextRunDate();
        
        $this->update(['next_run_at' => $nextRun]);
    }

    /**
     * Scope for active workflows
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)->where('is_enabled', true);
    }

    /**
     * Scope for scheduled workflows
     */
    public function scopeScheduled($query)
    {
        return $query->where('trigger_type', self::TRIGGER_SCHEDULED);
    }

    /**
     * Scope for templates
     */
    public function scopeTemplates($query)
    {
        return $query->where('is_template', true);
    }

    /**
     * Scope for public workflows
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope for workflows ready to run
     */
    public function scopeReadyToRun($query)
    {
        return $query->active()
            ->scheduled()
            ->where('next_run_at', '<=', now());
    }
}