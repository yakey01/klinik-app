<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class EmployeeCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'pegawai_id',
        'user_id',
        'card_number',
        'card_type',
        'design_template',
        'employee_name',
        'employee_id',
        'position',
        'department',
        'role_name',
        'join_date',
        'photo_path',
        'issued_date',
        'valid_until',
        'is_active',
        'pdf_path',
        'image_path',
        'card_data',
        'created_by',
        'updated_by',
        'generated_at',
        'printed_at',
        'print_count',
    ];

    protected $casts = [
        'join_date' => 'date',
        'issued_date' => 'date',
        'valid_until' => 'date',
        'is_active' => 'boolean',
        'card_data' => 'array',
        'generated_at' => 'datetime',
        'printed_at' => 'datetime',
    ];

    /**
     * Get the employee (pegawai) that owns this card
     */
    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class);
    }

    /**
     * Get the user associated with this card
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who created this card
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this card
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope for active cards
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for valid cards (not expired)
     */
    public function scopeValid(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('valid_until')
              ->orWhere('valid_until', '>=', now()->toDateString());
        });
    }

    /**
     * Scope for expired cards
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('valid_until')
                    ->where('valid_until', '<', now()->toDateString());
    }

    /**
     * Scope by card type
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('card_type', $type);
    }

    /**
     * Generate unique card number
     */
    public static function generateCardNumber(): string
    {
        $year = date('Y');
        $prefix = "CARD-{$year}-";
        
        $lastCard = static::where('card_number', 'like', $prefix . '%')
                          ->orderBy('card_number', 'desc')
                          ->first();

        if ($lastCard) {
            $lastNumber = (int) substr($lastCard->card_number, strlen($prefix));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Check if card is expired
     */
    public function isExpired(): bool
    {
        return $this->valid_until && $this->valid_until->isPast();
    }

    /**
     * Check if card is valid
     */
    public function isValid(): bool
    {
        return $this->is_active && !$this->isExpired();
    }

    /**
     * Get card status badge color
     */
    public function getStatusBadgeColorAttribute(): string
    {
        if (!$this->is_active) {
            return 'danger';
        }
        
        if ($this->isExpired()) {
            return 'warning';
        }
        
        return 'success';
    }

    /**
     * Get card status text
     */
    public function getStatusTextAttribute(): string
    {
        if (!$this->is_active) {
            return '🔴 Nonaktif';
        }
        
        if ($this->isExpired()) {
            return '⚠️ Expired';
        }
        
        return '🟢 Aktif';
    }

    /**
     * Get card type badge color
     */
    public function getTypeBadgeColorAttribute(): string
    {
        return match($this->card_type) {
            'standard' => 'primary',
            'visitor' => 'info',
            'temporary' => 'warning',
            default => 'gray',
        };
    }

    /**
     * Get formatted card type
     */
    public function getFormattedTypeAttribute(): string
    {
        return match($this->card_type) {
            'standard' => '🆔 Standard',
            'visitor' => '👥 Visitor',
            'temporary' => '⏰ Temporary',
            default => ucfirst($this->card_type),
        };
    }

    /**
     * Get days until expiry
     */
    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (!$this->valid_until) {
            return null;
        }
        
        return now()->diffInDays($this->valid_until, false);
    }

    /**
     * Get expiry status
     */
    public function getExpiryStatusAttribute(): string
    {
        if (!$this->valid_until) {
            return 'No expiry';
        }
        
        $days = $this->days_until_expiry;
        
        if ($days < 0) {
            return 'Expired ' . abs($days) . ' days ago';
        }
        
        if ($days == 0) {
            return 'Expires today';
        }
        
        if ($days <= 30) {
            return "Expires in {$days} days";
        }
        
        return $this->valid_until->format('M d, Y');
    }

    /**
     * Mark card as printed
     */
    public function markAsPrinted(): void
    {
        $this->update([
            'printed_at' => now(),
            'print_count' => $this->print_count + 1,
        ]);
    }

    /**
     * Get photo URL for card
     */
    public function getPhotoUrlAttribute(): string
    {
        if ($this->photo_path && file_exists(storage_path('app/public/' . $this->photo_path))) {
            return asset('storage/' . $this->photo_path);
        }
        
        // Use default avatar from pegawai model
        return $this->pegawai->default_avatar ?? "https://ui-avatars.com/api/?name=" . urlencode($this->employee_name) . "&color=7F9CF5&background=EBF4FF";
    }

    /**
     * Get available card templates
     */
    public static function getCardTemplates(): array
    {
        return [
            'default' => '🆔 Default Template',
            'modern' => '✨ Modern Template',
            'classic' => '📋 Classic Template',
            'minimalist' => '🎯 Minimalist Template',
        ];
    }

    /**
     * Get available card types
     */
    public static function getCardTypes(): array
    {
        return [
            'standard' => '🆔 Standard',
            'visitor' => '👥 Visitor',
            'temporary' => '⏰ Temporary',
        ];
    }
}
