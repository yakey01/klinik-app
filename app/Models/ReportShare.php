<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_id',
        'user_id',
        'shared_by',
        'permissions',
        'expires_at',
        'access_count',
        'last_accessed_at',
    ];

    protected $casts = [
        'permissions' => 'array',
        'expires_at' => 'datetime',
        'last_accessed_at' => 'datetime',
    ];

    // Relationships
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sharedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where(function ($query) {
            $query->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
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
    public function isActive(): bool
    {
        return !$this->expires_at || $this->expires_at > now();
    }

    public function hasExpired(): bool
    {
        return $this->expires_at && $this->expires_at <= now();
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }

    public function canView(): bool
    {
        return $this->hasPermission('view') || $this->hasPermission('edit');
    }

    public function canEdit(): bool
    {
        return $this->hasPermission('edit');
    }

    public function canExecute(): bool
    {
        return $this->hasPermission('execute') || $this->hasPermission('edit');
    }

    public function recordAccess(): void
    {
        $this->increment('access_count');
        $this->update(['last_accessed_at' => now()]);
    }

    public function revoke(): void
    {
        $this->delete();
    }

    public function getPermissionsList(): array
    {
        $permissions = $this->permissions ?? [];
        
        return [
            'view' => in_array('view', $permissions),
            'edit' => in_array('edit', $permissions),
            'execute' => in_array('execute', $permissions),
            'share' => in_array('share', $permissions),
        ];
    }

    public function getExpirationStatus(): string
    {
        if (!$this->expires_at) {
            return 'never';
        }

        if ($this->hasExpired()) {
            return 'expired';
        }

        $daysUntilExpiry = now()->diffInDays($this->expires_at);
        
        if ($daysUntilExpiry <= 1) {
            return 'expires_soon';
        } elseif ($daysUntilExpiry <= 7) {
            return 'expires_week';
        } else {
            return 'active';
        }
    }

    public function getExpirationColor(): string
    {
        return match($this->getExpirationStatus()) {
            'expired' => 'danger',
            'expires_soon' => 'danger',
            'expires_week' => 'warning',
            'active' => 'success',
            'never' => 'primary',
            default => 'gray',
        };
    }

    public function getFormattedExpiration(): string
    {
        if (!$this->expires_at) {
            return 'Never expires';
        }

        if ($this->hasExpired()) {
            return 'Expired ' . $this->expires_at->diffForHumans();
        }

        return 'Expires ' . $this->expires_at->diffForHumans();
    }

    // Static methods
    public static function cleanupExpired(): int
    {
        return self::expired()->delete();
    }

    public static function getShareStats(): array
    {
        return [
            'total_shares' => self::count(),
            'active_shares' => self::active()->count(),
            'expired_shares' => self::expired()->count(),
            'most_shared_report' => self::selectRaw('report_id, COUNT(*) as share_count')
                ->groupBy('report_id')
                ->orderBy('share_count', 'desc')
                ->first(),
        ];
    }
}