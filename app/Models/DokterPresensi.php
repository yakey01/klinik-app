<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DokterPresensi extends Model
{
    protected $table = 'dokter_presensis';

    protected $fillable = [
        'dokter_id',
        'tanggal',
        'jam_masuk',
        'jam_pulang'
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jam_masuk' => 'datetime:H:i:s',
        'jam_pulang' => 'datetime:H:i:s'
    ];

    public function dokter(): BelongsTo
    {
        return $this->belongsTo(Dokter::class);
    }

    public function getDurasiAttribute(): ?string
    {
        if (!$this->jam_masuk || !$this->jam_pulang) {
            return null;
        }

        $masuk = \Carbon\Carbon::parse($this->jam_masuk);
        $pulang = \Carbon\Carbon::parse($this->jam_pulang);
        
        $diff = $pulang->diff($masuk);
        
        return sprintf('%02d:%02d:%02d', $diff->h, $diff->i, $diff->s);
    }

    public function getStatusAttribute(): string
    {
        if (!$this->jam_masuk) {
            return 'Belum Hadir';
        }

        if (!$this->jam_pulang) {
            return 'Sedang Bertugas';
        }

        return 'Selesai';
    }

    public function scopeHariIni($query)
    {
        return $query->whereDate('tanggal', today());
    }

    public function scopeByDokter($query, $dokterId)
    {
        return $query->where('dokter_id', $dokterId);
    }

    public function scopeHistori($query, $days = 7)
    {
        return $query->where('tanggal', '>=', now()->subDays($days))
                    ->orderBy('tanggal', 'desc');
    }
}