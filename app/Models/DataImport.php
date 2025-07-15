<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class DataImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'user_id',
        'source_type',
        'target_model',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'source_config',
        'mapping_config',
        'validation_rules',
        'status',
        'total_rows',
        'processed_rows',
        'successful_rows',
        'failed_rows',
        'skipped_rows',
        'error_details',
        'validation_errors',
        'progress_percentage',
        'started_at',
        'completed_at',
        'execution_time',
        'memory_usage',
        'is_scheduled',
        'schedule_frequency',
        'next_run_at',
        'notification_settings',
        'backup_before_import',
        'backup_file_path',
        'preview_data',
    ];

    protected $casts = [
        'source_config' => 'array',
        'mapping_config' => 'array',
        'validation_rules' => 'array',
        'error_details' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'next_run_at' => 'datetime',
        'notification_settings' => 'array',
        'backup_before_import' => 'boolean',
        'preview_data' => 'array',
        'is_scheduled' => 'boolean',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    // Source type constants
    const SOURCE_FILE = 'file';
    const SOURCE_API = 'api';
    const SOURCE_DATABASE = 'database';
    const SOURCE_CSV = 'csv';
    const SOURCE_EXCEL = 'excel';
    const SOURCE_JSON = 'json';
    const SOURCE_XML = 'xml';

    // Schedule frequency constants
    const SCHEDULE_DAILY = 'daily';
    const SCHEDULE_WEEKLY = 'weekly';
    const SCHEDULE_MONTHLY = 'monthly';

    /**
     * Get the user that owns the import
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Start the import process
     */
    public function start(): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'started_at' => now(),
            'progress_percentage' => 0,
        ]);
    }

    /**
     * Complete the import process
     */
    public function complete(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'progress_percentage' => 100,
            'execution_time' => $this->started_at ? now()->diffInMilliseconds($this->started_at) : null,
        ]);
    }

    /**
     * Fail the import process
     */
    public function fail(string $error = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'completed_at' => now(),
            'error_details' => $error ? ['error' => $error] : $this->error_details,
            'execution_time' => $this->started_at ? now()->diffInMilliseconds($this->started_at) : null,
        ]);
    }

    /**
     * Cancel the import process
     */
    public function cancel(): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'completed_at' => now(),
        ]);
    }

    /**
     * Update progress
     */
    public function updateProgress(int $processedRows, int $successfulRows = null, int $failedRows = null, int $skippedRows = null): void
    {
        $data = [
            'processed_rows' => $processedRows,
            'progress_percentage' => $this->total_rows ? min(100, round(($processedRows / $this->total_rows) * 100)) : 0,
        ];

        if ($successfulRows !== null) {
            $data['successful_rows'] = $successfulRows;
        }

        if ($failedRows !== null) {
            $data['failed_rows'] = $failedRows;
        }

        if ($skippedRows !== null) {
            $data['skipped_rows'] = $skippedRows;
        }

        $this->update($data);
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
     * Get formatted file size
     */
    public function getFormattedFileSize(): string
    {
        if (!$this->file_size) {
            return 'N/A';
        }

        $bytes = (int) $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get success rate percentage
     */
    public function getSuccessRate(): float
    {
        if (!$this->processed_rows) {
            return 0;
        }

        return round(($this->successful_rows / $this->processed_rows) * 100, 2);
    }

    /**
     * Check if import is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if import is failed
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if import is processing
     */
    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    /**
     * Get status color for UI
     */
    public function getStatusColor(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'gray',
            self::STATUS_PROCESSING => 'warning',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_CANCELLED => 'secondary',
            default => 'gray',
        };
    }

    /**
     * Get source type icon
     */
    public function getSourceTypeIcon(): string
    {
        return match($this->source_type) {
            self::SOURCE_FILE => 'heroicon-o-document',
            self::SOURCE_API => 'heroicon-o-globe-alt',
            self::SOURCE_DATABASE => 'heroicon-o-circle-stack',
            self::SOURCE_CSV => 'heroicon-o-table-cells',
            self::SOURCE_EXCEL => 'heroicon-o-document-text',
            self::SOURCE_JSON => 'heroicon-o-code-bracket',
            self::SOURCE_XML => 'heroicon-o-document-text',
            default => 'heroicon-o-document',
        };
    }

    /**
     * Get available source types
     */
    public static function getSourceTypes(): array
    {
        return [
            self::SOURCE_FILE => 'File Upload',
            self::SOURCE_API => 'API Integration',
            self::SOURCE_DATABASE => 'Database Connection',
            self::SOURCE_CSV => 'CSV File',
            self::SOURCE_EXCEL => 'Excel File',
            self::SOURCE_JSON => 'JSON File',
            self::SOURCE_XML => 'XML File',
        ];
    }

    /**
     * Get available schedule frequencies
     */
    public static function getScheduleFrequencies(): array
    {
        return [
            self::SCHEDULE_DAILY => 'Daily',
            self::SCHEDULE_WEEKLY => 'Weekly',
            self::SCHEDULE_MONTHLY => 'Monthly',
        ];
    }

    /**
     * Get available target models
     */
    public static function getTargetModels(): array
    {
        return [
            'App\\Models\\User' => 'Users',
            'App\\Models\\NonParamedisAttendance' => 'Non-Paramedis Attendance',
            'App\\Models\\WorkLocation' => 'Work Locations',
            'App\\Models\\Schedule' => 'Schedules',
            'App\\Models\\Shift' => 'Shifts',
            'App\\Models\\Notification' => 'Notifications',
            'App\\Models\\BulkOperation' => 'Bulk Operations',
            'App\\Models\\Report' => 'Reports',
        ];
    }

    /**
     * Schedule next run
     */
    public function scheduleNextRun(): void
    {
        if (!$this->is_scheduled || !$this->schedule_frequency) {
            return;
        }

        $nextRun = match($this->schedule_frequency) {
            self::SCHEDULE_DAILY => now()->addDay(),
            self::SCHEDULE_WEEKLY => now()->addWeek(),
            self::SCHEDULE_MONTHLY => now()->addMonth(),
            default => null,
        };

        if ($nextRun) {
            $this->update(['next_run_at' => $nextRun]);
        }
    }

    /**
     * Scope for scheduled imports
     */
    public function scopeScheduled($query)
    {
        return $query->where('is_scheduled', true);
    }

    /**
     * Scope for pending imports
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for processing imports
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', self::STATUS_PROCESSING);
    }

    /**
     * Scope for completed imports
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for failed imports
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }
}