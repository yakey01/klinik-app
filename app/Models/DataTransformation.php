<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataTransformation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'user_id',
        'transformation_type',
        'source_model',
        'target_model',
        'transformation_rules',
        'field_mappings',
        'validation_rules',
        'cleanup_rules',
        'status',
        'total_records',
        'processed_records',
        'transformed_records',
        'failed_records',
        'skipped_records',
        'transformation_stats',
        'error_log',
        'progress_percentage',
        'started_at',
        'completed_at',
        'execution_time',
        'memory_usage',
        'is_scheduled',
        'schedule_frequency',
        'next_run_at',
        'notification_settings',
        'backup_before_transform',
        'backup_file_path',
        'dry_run',
        'dry_run_results',
    ];

    protected $casts = [
        'transformation_rules' => 'array',
        'field_mappings' => 'array',
        'validation_rules' => 'array',
        'cleanup_rules' => 'array',
        'transformation_stats' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'next_run_at' => 'datetime',
        'notification_settings' => 'array',
        'backup_before_transform' => 'boolean',
        'dry_run' => 'boolean',
        'dry_run_results' => 'array',
        'is_scheduled' => 'boolean',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    // Transformation type constants
    const TYPE_CLEANUP = 'cleanup';
    const TYPE_VALIDATION = 'validation';
    const TYPE_ENRICHMENT = 'enrichment';
    const TYPE_AGGREGATION = 'aggregation';

    // Schedule frequency constants
    const SCHEDULE_DAILY = 'daily';
    const SCHEDULE_WEEKLY = 'weekly';
    const SCHEDULE_MONTHLY = 'monthly';

    /**
     * Get the user that owns the transformation
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Start the transformation process
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
     * Complete the transformation process
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
     * Fail the transformation process
     */
    public function fail(string $error = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'completed_at' => now(),
            'error_log' => $error ? $this->error_log . "\n" . $error : $this->error_log,
            'execution_time' => $this->started_at ? now()->diffInMilliseconds($this->started_at) : null,
        ]);
    }

    /**
     * Cancel the transformation process
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
    public function updateProgress(int $processedRecords, int $transformedRecords = null, int $failedRecords = null, int $skippedRecords = null): void
    {
        $data = [
            'processed_records' => $processedRecords,
            'progress_percentage' => $this->total_records ? min(100, round(($processedRecords / $this->total_records) * 100)) : 0,
        ];

        if ($transformedRecords !== null) {
            $data['transformed_records'] = $transformedRecords;
        }

        if ($failedRecords !== null) {
            $data['failed_records'] = $failedRecords;
        }

        if ($skippedRecords !== null) {
            $data['skipped_records'] = $skippedRecords;
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
     * Get success rate percentage
     */
    public function getSuccessRate(): float
    {
        if (!$this->processed_records) {
            return 0;
        }

        return round(($this->transformed_records / $this->processed_records) * 100, 2);
    }

    /**
     * Check if transformation is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if transformation is failed
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if transformation is processing
     */
    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    /**
     * Check if transformation is dry run
     */
    public function isDryRun(): bool
    {
        return $this->dry_run;
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
     * Get transformation type icon
     */
    public function getTransformationTypeIcon(): string
    {
        return match($this->transformation_type) {
            self::TYPE_CLEANUP => 'heroicon-o-sparkles',
            self::TYPE_VALIDATION => 'heroicon-o-shield-check',
            self::TYPE_ENRICHMENT => 'heroicon-o-plus-circle',
            self::TYPE_AGGREGATION => 'heroicon-o-chart-bar',
            default => 'heroicon-o-cog',
        };
    }

    /**
     * Get transformation type color
     */
    public function getTransformationTypeColor(): string
    {
        return match($this->transformation_type) {
            self::TYPE_CLEANUP => 'info',
            self::TYPE_VALIDATION => 'success',
            self::TYPE_ENRICHMENT => 'primary',
            self::TYPE_AGGREGATION => 'warning',
            default => 'gray',
        };
    }

    /**
     * Get available transformation types
     */
    public static function getTransformationTypes(): array
    {
        return [
            self::TYPE_CLEANUP => 'Data Cleanup',
            self::TYPE_VALIDATION => 'Data Validation',
            self::TYPE_ENRICHMENT => 'Data Enrichment',
            self::TYPE_AGGREGATION => 'Data Aggregation',
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
     * Get available source models
     */
    public static function getSourceModels(): array
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
            'App\\Models\\DataImport' => 'Data Imports',
            'App\\Models\\DataExport' => 'Data Exports',
        ];
    }

    /**
     * Get available cleanup rules
     */
    public static function getCleanupRules(): array
    {
        return [
            'trim_whitespace' => 'Trim Whitespace',
            'remove_duplicates' => 'Remove Duplicates',
            'standardize_case' => 'Standardize Case',
            'remove_empty_records' => 'Remove Empty Records',
            'format_dates' => 'Format Dates',
            'format_numbers' => 'Format Numbers',
            'remove_special_chars' => 'Remove Special Characters',
            'validate_emails' => 'Validate Email Addresses',
            'validate_phones' => 'Validate Phone Numbers',
        ];
    }

    /**
     * Get available validation rules
     */
    public static function getValidationRules(): array
    {
        return [
            'required' => 'Required Fields',
            'email' => 'Email Validation',
            'numeric' => 'Numeric Validation',
            'date' => 'Date Validation',
            'unique' => 'Unique Values',
            'min_length' => 'Minimum Length',
            'max_length' => 'Maximum Length',
            'regex' => 'Regular Expression',
            'in_list' => 'Value in List',
            'range' => 'Value Range',
        ];
    }

    /**
     * Get available enrichment rules
     */
    public static function getEnrichmentRules(): array
    {
        return [
            'add_timestamps' => 'Add Timestamps',
            'add_user_info' => 'Add User Information',
            'geocode_addresses' => 'Geocode Addresses',
            'lookup_references' => 'Lookup References',
            'calculate_fields' => 'Calculate Fields',
            'merge_data' => 'Merge Data Sources',
            'normalize_data' => 'Normalize Data',
            'categorize_data' => 'Categorize Data',
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
     * Scope for scheduled transformations
     */
    public function scopeScheduled($query)
    {
        return $query->where('is_scheduled', true);
    }

    /**
     * Scope for pending transformations
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for processing transformations
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', self::STATUS_PROCESSING);
    }

    /**
     * Scope for completed transformations
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for failed transformations
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope for dry run transformations
     */
    public function scopeDryRun($query)
    {
        return $query->where('dry_run', true);
    }

    /**
     * Scope for live transformations
     */
    public function scopeLive($query)
    {
        return $query->where('dry_run', false);
    }
}