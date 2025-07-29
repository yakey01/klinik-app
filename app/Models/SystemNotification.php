<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SystemNotification extends Model
{
    use HasFactory;

    protected $table = 'system_notifications';

    protected $fillable = [
        'type',
        'title',
        'message',
        'metadata',
        'from_user_id',
        'from_role',
        'to_user_id',
        'to_role',
        'is_read',
        'read_at',
        'priority',
        'related_type',
        'related_id',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('to_user_id', $userId);
    }

    public function scopeForRole($query, $role)
    {
        return $query->where('to_role', $role);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    // Methods
    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'low' => 'gray',
            'normal' => 'blue',
            'high' => 'orange',
            'urgent' => 'red',
            default => 'gray'
        };
    }

    public function getTypeColorAttribute(): string
    {
        return match($this->type) {
            'master_data_updated' => 'info',
            'validation_required' => 'warning',
            'validation_approved' => 'success',
            'validation_rejected' => 'danger',
            'system_alert' => 'gray',
            default => 'gray'
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'master_data_updated' => 'Master Data Updated',
            'validation_required' => 'Validation Required',
            'validation_approved' => 'Validation Approved',
            'validation_rejected' => 'Validation Rejected',
            'system_alert' => 'System Alert',
            default => ucfirst(str_replace('_', ' ', $this->type))
        };
    }

    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    // Static methods
    public static function createForValidation(
        string $type,
        string $title,
        string $message,
        Model $related,
        ?User $fromUser = null,
        ?User $toUser = null,
        ?string $toRole = null,
        string $priority = 'normal',
        array $metadata = []
    ): self {
        return static::create([
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'metadata' => $metadata,
            'from_user_id' => $fromUser?->id,
            'from_role' => $fromUser ? $fromUser->roles->first()?->name : 'system',
            'to_user_id' => $toUser?->id,
            'to_role' => $toRole,
            'priority' => $priority,
            'related_type' => get_class($related),
            'related_id' => $related->id,
        ]);
    }

    public static function notifyBendahara(
        string $title,
        string $message,
        Model $related,
        ?User $fromUser = null,
        string $priority = 'normal',
        array $metadata = []
    ): void {
        $bendaharaUsers = User::role('bendahara')->get();

        foreach ($bendaharaUsers as $bendahara) {
            static::createForValidation(
                'validation_required',
                $title,
                $message,
                $related,
                $fromUser,
                $bendahara,
                'bendahara',
                $priority,
                $metadata
            );
        }
    }

    public static function notifyPetugas(
        string $type,
        string $title,
        string $message,
        Model $related,
        User $toUser,
        ?User $fromUser = null,
        string $priority = 'normal',
        array $metadata = []
    ): void {
        static::createForValidation(
            $type,
            $title,
            $message,
            $related,
            $fromUser,
            $toUser,
            'petugas',
            $priority,
            $metadata
        );
    }
}