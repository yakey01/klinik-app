<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JenisTransaksi extends Model
{
    protected $table = 'jenis_transaksis';
    
    protected $fillable = [
        'nama',
        'kategori',
        'is_aktif',
        'deskripsi',
    ];

    protected $casts = [
        'is_aktif' => 'boolean',
    ];

    public function pendapatanHarians(): HasMany
    {
        return $this->hasMany(PendapatanHarian::class);
    }

    public function scopeAktif($query)
    {
        return $query->where('is_aktif', true);
    }

    public function scopePendapatan($query)
    {
        return $query->where('kategori', 'Pendapatan');
    }
}
