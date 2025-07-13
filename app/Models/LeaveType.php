<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    protected $fillable = [
        'nama',
        'alokasi_hari',
        'active',
        'description'
    ];

    protected $casts = [
        'active' => 'boolean',
        'alokasi_hari' => 'integer'
    ];

    /**
     * Relationship with leave requests
     */
    public function permohonanCutis(): HasMany
    {
        return $this->hasMany(PermohonanCuti::class, 'jenis_cuti', 'nama');
    }

    /**
     * Scope for active leave types
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Get formatted allocation display
     */
    public function getAllocationDisplayAttribute(): string
    {
        if ($this->alokasi_hari === null) {
            return 'Tidak Terbatas';
        }
        
        return $this->alokasi_hari . ' hari/tahun';
    }

    /**
     * Get status badge for display
     */
    public function getStatusBadgeAttribute(): string
    {
        return $this->active ? 'success' : 'danger';
    }

    /**
     * Get status text
     */
    public function getStatusTextAttribute(): string
    {
        return $this->active ? 'Aktif' : 'Nonaktif';
    }
}