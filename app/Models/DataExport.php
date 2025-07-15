<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class DataExport extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'user_id',
        'source_model',
        'export_format',
        'file_path',
        'file_name',
        'file_size',
        'query_config',
        'column_config',
        'format_config',
        'status',
        'total_rows',
        'exported_rows',
        'progress_percentage',
        'started_at',
        'completed_at',
        'execution_time',
        'memory_usage',
        'error_details',
        'is_scheduled',
        'schedule_frequency',
        'next_run_at',
        'notification_settings',
        'compress_output',
        'compression_format',
        'encrypt_output',
        'encryption_key',
        'expires_at',
        'download_count',
        'last_downloaded_at',
        'access_permissions',
    ];

    protected $casts = [
        'query_config' => 'array',
        'column_config' => 'array',
        'format_config' => 'array',
        'error_details' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'next_run_at' => 'datetime',
        'notification_settings' => 'array',
        'compress_output' => 'boolean',
        'encrypt_output' => 'boolean',
        'expires_at' => 'datetime',
        'last_downloaded_at' => 'datetime',
        'access_permissions' => 'array',
        'is_scheduled' => 'boolean',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    // Export format constants
    const FORMAT_CSV = 'csv';
    const FORMAT_EXCEL = 'excel';
    const FORMAT_JSON = 'json';
    const FORMAT_XML = 'xml';
    const FORMAT_PDF = 'pdf';

    // Schedule frequency constants
    const SCHEDULE_DAILY = 'daily';
    const SCHEDULE_WEEKLY = 'weekly';
    const SCHEDULE_MONTHLY = 'monthly';

    // Compression format constants
    const COMPRESSION_ZIP = 'zip';
    const COMPRESSION_GZIP = 'gzip';

    /**
     * Get the user that owns the export
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Start the export process
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
     * Complete the export process
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
     * Fail the export process
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
     * Cancel the export process
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
    public function updateProgress(int $exportedRows): void
    {
        $this->update([
            'exported_rows' => $exportedRows,
            'progress_percentage' => $this->total_rows ? min(100, round(($exportedRows / $this->total_rows) * 100)) : 0,
        ]);
    }

    /**
     * Record download
     */
    public function recordDownload(): void
    {
        $this->increment('download_count');
        $this->update(['last_downloaded_at' => now()]);
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
     * Check if export is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if export is failed
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if export is processing
     */
    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    /**
     * Check if export is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if export is downloadable
     */
    public function isDownloadable(): bool
    {
        return $this->isCompleted() && !$this->isExpired() && $this->file_path && file_exists($this->file_path);
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
     * Get export format icon
     */
    public function getExportFormatIcon(): string
    {
        return match($this->export_format) {
            self::FORMAT_CSV => 'heroicon-o-table-cells',
            self::FORMAT_EXCEL => 'heroicon-o-document-text',
            self::FORMAT_JSON => 'heroicon-o-code-bracket',
            self::FORMAT_XML => 'heroicon-o-document-text',
            self::FORMAT_PDF => 'heroicon-o-document',
            default => 'heroicon-o-document',
        };
    }

    /**
     * Get available export formats
     */
    public static function getExportFormats(): array
    {
        return [
            self::FORMAT_CSV => 'CSV',
            self::FORMAT_EXCEL => 'Excel',
            self::FORMAT_JSON => 'JSON',
            self::FORMAT_XML => 'XML',
            self::FORMAT_PDF => 'PDF',
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
     * Get available compression formats
     */
    public static function getCompressionFormats(): array
    {
        return [
            self::COMPRESSION_ZIP => 'ZIP',
            self::COMPRESSION_GZIP => 'GZIP',
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
     * Scope for scheduled exports
     */
    public function scopeScheduled($query)
    {
        return $query->where('is_scheduled', true);
    }

    /**
     * Scope for pending exports
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for processing exports
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', self::STATUS_PROCESSING);
    }

    /**
     * Scope for completed exports
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for failed exports
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope for non-expired exports
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($query) {
            $query->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope for downloadable exports
     */
    public function scopeDownloadable($query)
    {
        return $query->completed()->notExpired();
    }
}