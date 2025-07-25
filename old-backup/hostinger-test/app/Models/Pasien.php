<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Cacheable;
use App\Traits\LogsActivity;

class Pasien extends Model
{
    use HasFactory, SoftDeletes, Cacheable, LogsActivity;

    protected $table = 'pasien';

    protected $fillable = [
        'no_rekam_medis',
        'nama',
        'tanggal_lahir',
        'jenis_kelamin',
        'alamat',
        'no_telepon',
        'email',
        'pekerjaan',
        'status_pernikahan',
        'kontak_darurat_nama',
        'kontak_darurat_telepon',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
    ];
    
    // Custom cache TTL for this model
    protected int $customCacheTtl = 3600; // 1 hour

    public function tindakan(): HasMany
    {
        return $this->hasMany(Tindakan::class);
    }

    public function scopeByGender($query, $gender)
    {
        return $query->where('jenis_kelamin', $gender);
    }

    public function getUmurAttribute()
    {
        return $this->cacheAttribute('umur', function() {
            return $this->tanggal_lahir?->age;
        });
    }
    
    // Cache commonly used statistics
    public static function getCachedStats(): array
    {
        return static::cacheStatistics('patient_stats', function() {
            return [
                'total_count' => static::count(),
                'male_count' => static::where('jenis_kelamin', 'L')->count(),
                'female_count' => static::where('jenis_kelamin', 'P')->count(),
                'recent_count' => static::whereDate('created_at', today())->count(),
                'avg_age' => static::whereNotNull('tanggal_lahir')
                    ->selectRaw('AVG(YEAR(CURDATE()) - YEAR(tanggal_lahir)) as avg_age')
                    ->first()
                    ->avg_age ?? 0,
            ];
        });
    }
    
    // Cache tindakan count for this patient
    public function getTindakanCountAttribute(): int
    {
        return $this->cacheCount('tindakan_count', function() {
            return $this->tindakan()->count();
        });
    }
    
    // Cache last tindakan for this patient
    public function getLastTindakanAttribute(): ?Tindakan
    {
        return $this->cacheAttribute('last_tindakan', function() {
            return $this->tindakan()->latest()->first();
        });
    }
    
    /**
     * Get validation rules for bulk operations
     */
    public function getBulkValidationRules(): array
    {
        return [
            'nama' => 'required|string|max:255',
            'no_rekam_medis' => 'required|string|max:255|unique:pasien,no_rekam_medis',
            'tanggal_lahir' => 'required|date',
            'jenis_kelamin' => 'required|in:L,P',
        ];
    }
}
