<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulkOperation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'operation_type',
        'model_type',
        'operation_data',
        'filters',
        'status',
        'total_records',
        'processed_records',
        'successful_records',
        'failed_records',
        'error_details',
        'started_at',
        'completed_at',
        'estimated_duration',
        'progress_percentage',
    ];

    protected $casts = [
        'operation_data' => 'array',
        'filters' => 'array',
        'error_details' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'progress_percentage' => 'float',
    ];

    // Operation types
    const TYPE_UPDATE = 'update';
    const TYPE_DELETE = 'delete';
    const TYPE_EXPORT = 'export';
    const TYPE_IMPORT = 'import';
    const TYPE_SYNC = 'sync';
    const TYPE_BACKUP = 'backup';
    const TYPE_RESTORE = 'restore';
    const TYPE_CLEANUP = 'cleanup';

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_PAUSED = 'paused';

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('operation_type', $type);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_PROCESSING, self::STATUS_PAUSED]);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    // Methods
    public function start(): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'started_at' => now(),
        ]);
    }

    public function complete(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'progress_percentage' => 100,
        ]);
    }

    public function fail(array $errorDetails = []): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'completed_at' => now(),
            'error_details' => $errorDetails,
        ]);
    }

    public function cancel(): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'completed_at' => now(),
        ]);
    }

    public function pause(): void
    {
        $this->update([
            'status' => self::STATUS_PAUSED,
        ]);
    }

    public function resume(): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
        ]);
    }

    public function updateProgress(int $processed, ?int $successful = null, ?int $failed = null): void
    {
        $successful = $successful ?? $this->successful_records;
        $failed = $failed ?? $this->failed_records;
        
        $progressPercentage = $this->total_records > 0 
            ? ($processed / $this->total_records) * 100 
            : 0;

        $this->update([
            'processed_records' => $processed,
            'successful_records' => $successful,
            'failed_records' => $failed,
            'progress_percentage' => round($progressPercentage, 2),
        ]);
    }

    public function incrementProgress(int $successful = 0, int $failed = 0): void
    {
        $newProcessed = $this->processed_records + $successful + $failed;
        $newSuccessful = $this->successful_records + $successful;
        $newFailed = $this->failed_records + $failed;

        $this->updateProgress($newProcessed, $newSuccessful, $newFailed);
    }

    public function addError(string $record, string $error): void
    {
        $errorDetails = $this->error_details ?? [];
        $errorDetails[] = [
            'record' => $record,
            'error' => $error,
            'timestamp' => now()->toISOString(),
        ];

        $this->update(['error_details' => $errorDetails]);
    }

    // Getters
    public function getStatusColor(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'gray',
            self::STATUS_PROCESSING => 'primary',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_CANCELLED => 'warning',
            self::STATUS_PAUSED => 'secondary',
            default => 'gray',
        };
    }

    public function getStatusIcon(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'heroicon-o-clock',
            self::STATUS_PROCESSING => 'heroicon-o-arrow-path',
            self::STATUS_COMPLETED => 'heroicon-o-check-circle',
            self::STATUS_FAILED => 'heroicon-o-x-circle',
            self::STATUS_CANCELLED => 'heroicon-o-stop-circle',
            self::STATUS_PAUSED => 'heroicon-o-pause-circle',
            default => 'heroicon-o-question-mark-circle',
        };
    }

    public function getTypeIcon(): string
    {
        return match($this->operation_type) {
            self::TYPE_UPDATE => 'heroicon-o-pencil',
            self::TYPE_DELETE => 'heroicon-o-trash',
            self::TYPE_EXPORT => 'heroicon-o-arrow-down-tray',
            self::TYPE_IMPORT => 'heroicon-o-arrow-up-tray',
            self::TYPE_SYNC => 'heroicon-o-arrow-path',
            self::TYPE_BACKUP => 'heroicon-o-archive-box',
            self::TYPE_RESTORE => 'heroicon-o-arrow-uturn-left',
            self::TYPE_CLEANUP => 'heroicon-o-trash',
            default => 'heroicon-o-cog',
        };
    }

    public function getEstimatedTimeRemaining(): ?string
    {
        if ($this->status !== self::STATUS_PROCESSING || !$this->started_at || $this->progress_percentage <= 0) {
            return null;
        }

        $elapsedSeconds = $this->started_at->diffInSeconds(now());
        $estimatedTotalSeconds = ($elapsedSeconds / $this->progress_percentage) * 100;
        $remainingSeconds = $estimatedTotalSeconds - $elapsedSeconds;

        if ($remainingSeconds <= 0) {
            return 'Almost done';
        }

        return $this->formatDuration($remainingSeconds);
    }

    public function getDuration(): ?string
    {
        if (!$this->started_at) {
            return null;
        }

        $endTime = $this->completed_at ?? now();
        $seconds = $this->started_at->diffInSeconds($endTime);

        return $this->formatDuration($seconds);
    }

    private function formatDuration(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;

        $parts = [];
        if ($hours > 0) $parts[] = "{$hours}h";
        if ($minutes > 0) $parts[] = "{$minutes}m";
        if ($seconds > 0 || empty($parts)) $parts[] = "{$seconds}s";

        return implode(' ', $parts);
    }

    public function getSuccessRate(): float
    {
        if ($this->processed_records === 0) {
            return 0;
        }

        return round(($this->successful_records / $this->processed_records) * 100, 2);
    }

    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING, self::STATUS_PAUSED]);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function canCancel(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING, self::STATUS_PAUSED]);
    }

    public function canPause(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function canResume(): bool
    {
        return $this->status === self::STATUS_PAUSED;
    }

    // Static methods
    public static function createOperation(
        User $user,
        string $type,
        string $modelType,
        array $operationData,
        array $filters = [],
        int $totalRecords = 0
    ): self {
        return self::create([
            'user_id' => $user->id,
            'operation_type' => $type,
            'model_type' => $modelType,
            'operation_data' => $operationData,
            'filters' => $filters,
            'status' => self::STATUS_PENDING,
            'total_records' => $totalRecords,
            'processed_records' => 0,
            'successful_records' => 0,
            'failed_records' => 0,
            'progress_percentage' => 0,
        ]);
    }

    public static function cleanup(int $days = 30): int
    {
        return self::where('created_at', '<', now()->subDays($days))
            ->whereIn('status', [self::STATUS_COMPLETED, self::STATUS_FAILED, self::STATUS_CANCELLED])
            ->delete();
    }
}