<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class CutiPegawai extends Model
{
    protected $table = 'cuti_pegawais';
    
    protected $fillable = [
        'pegawai_id',
        'tanggal_awal',
        'tanggal_akhir',
        'jumlah_hari',
        'alasan',
        'status',
        'komentar_admin',
        'approved_by',
        'approved_at',
        'created_by',
    ];

    protected $casts = [
        'tanggal_awal' => 'date',
        'tanggal_akhir' => 'date',
        'approved_at' => 'datetime',
    ];

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pegawai_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function calculateJumlahHari(): int
    {
        if ($this->tanggal_awal && $this->tanggal_akhir) {
            return $this->tanggal_awal->diffInDays($this->tanggal_akhir) + 1;
        }
        return 0;
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match($this->status) {
            'menunggu' => 'warning',
            'disetujui' => 'success',
            'ditolak' => 'danger',
            default => 'gray'
        };
    }

    // Scope untuk filter status
    public function scopeMenunggu($query)
    {
        return $query->where('status', 'menunggu');
    }

    public function scopeDisetujui($query)
    {
        return $query->where('status', 'disetujui');
    }

    public function scopeDitolak($query)
    {
        return $query->where('status', 'ditolak');
    }
}
