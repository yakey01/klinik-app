<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Dokter extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'nama_lengkap',
        'nik',
        'tanggal_lahir',
        'jenis_kelamin',
        'jabatan',
        'nomor_sip',
        'email',
        'aktif',
        'foto',
        'keterangan',
        'input_by',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'aktif' => 'boolean',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function inputBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'input_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('aktif', true);
    }

    public function scopeByJabatan($query, $jabatan)
    {
        return $query->where('jabatan', $jabatan);
    }

    // Accessors & Mutators
    public function getDefaultAvatarAttribute(): string
    {
        return 'https://ui-avatars.com/api/?name='.urlencode($this->nama_lengkap).'&background=3b82f6&color=fff';
    }

    public function getJabatanDisplayAttribute(): string
    {
        return match ($this->jabatan) {
            'dokter_umum' => 'Dokter Umum',
            'dokter_gigi' => 'Dokter Gigi', 
            'dokter_spesialis' => 'Dokter Spesialis',
            default => ucfirst(str_replace('_', ' ', $this->jabatan)),
        };
    }

    public function getJabatanBadgeColorAttribute(): string
    {
        return match ($this->jabatan) {
            'dokter_umum' => 'primary',
            'dokter_gigi' => 'success',
            'dokter_spesialis' => 'info',
            default => 'gray',
        };
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return $this->aktif ? 'success' : 'danger';
    }

    public function getStatusTextAttribute(): string
    {
        return $this->aktif ? 'Aktif' : 'Nonaktif';
    }

    // Helper methods
    public static function generateNik(): string
    {
        do {
            $nik = 'DOK' . now()->format('Y') . str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (static::where('nik', $nik)->exists());
        
        return $nik;
    }

    public function getAgeAttribute(): ?int
    {
        return $this->tanggal_lahir ? $this->tanggal_lahir->age : null;
    }

    // Boot
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (auth()->check()) {
                $model->input_by = auth()->id();
            }
            
            // Auto-generate NIK if not provided
            if (empty($model->nik)) {
                $model->nik = static::generateNik();
            }
        });
    }
}
