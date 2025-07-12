<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DokterUmumJaspel extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'jenis_shift',
        'ambang_pasien',
        'fee_pasien_umum',
        'fee_pasien_bpjs',
        'status_aktif',
        'keterangan',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'ambang_pasien' => 'integer',
        'fee_pasien_umum' => 'decimal:2',
        'fee_pasien_bpjs' => 'decimal:2',
        'status_aktif' => 'boolean',
    ];

    // Relationships
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status_aktif', true);
    }

    public function scopeByShift($query, $shift)
    {
        return $query->where('jenis_shift', $shift);
    }

    // Accessors & Mutators
    public function getShiftDisplayAttribute(): string
    {
        return match ($this->jenis_shift) {
            'Pagi' => '🌅 Pagi',
            'Sore' => '🌇 Sore',
            'Hari Libur Besar' => '🏖️ Hari Libur Besar',
            default => $this->jenis_shift,
        };
    }

    public function getShiftBadgeColorAttribute(): string
    {
        return match ($this->jenis_shift) {
            'Pagi' => 'info',
            'Sore' => 'warning',
            'Hari Libur Besar' => 'success',
            default => 'gray',
        };
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return $this->status_aktif ? 'success' : 'danger';
    }

    public function getStatusTextAttribute(): string
    {
        return $this->status_aktif ? 'Aktif' : 'Nonaktif';
    }

    public function getFeeUmumFormattedAttribute(): string
    {
        return 'Rp ' . number_format($this->fee_pasien_umum, 0, ',', '.');
    }

    public function getFeeBpjsFormattedAttribute(): string
    {
        return 'Rp ' . number_format($this->fee_pasien_bpjs, 0, ',', '.');
    }

    // Helper methods
    public function calculateFee(int $jumlahPasien, string $jenisPasien = 'umum'): float
    {
        if ($jumlahPasien <= $this->ambang_pasien) {
            return 0; // Belum mencapai threshold
        }

        $pasienDihitung = $jumlahPasien - $this->ambang_pasien;
        $feePerPasien = $jenisPasien === 'bpjs' ? $this->fee_pasien_bpjs : $this->fee_pasien_umum;
        
        return $pasienDihitung * $feePerPasien;
    }

    public static function getShiftOptions(): array
    {
        return [
            'Pagi' => '🌅 Pagi',
            'Sore' => '🌇 Sore', 
            'Hari Libur Besar' => '🏖️ Hari Libur Besar',
        ];
    }

    // Boot
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = auth()->id();
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }
}
