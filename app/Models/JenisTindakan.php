<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JenisTindakan extends Model
{
    protected $table = 'jenis_tindakan';

    protected $fillable = [
        'kode',
        'nama',
        'deskripsi',
        'tarif',
        'jasa_dokter',
        'jasa_paramedis',
        'jasa_non_paramedis',
        'kategori',
        'is_active',
    ];

    protected $casts = [
        'tarif' => 'decimal:2',
        'jasa_dokter' => 'decimal:2',
        'jasa_paramedis' => 'decimal:2',
        'jasa_non_paramedis' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function tindakan(): HasMany
    {
        return $this->hasMany(Tindakan::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByKategori($query, $kategori)
    {
        return $query->where('kategori', $kategori);
    }
}
