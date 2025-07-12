<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class PermohonanCuti extends Model
{
    protected $fillable = [
        'pegawai_id',
        'tanggal_mulai',
        'tanggal_selesai',
        'jenis_cuti',
        'keterangan',
        'status',
        'disetujui_oleh',
        'tanggal_keputusan',
        'catatan_approval',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'tanggal_pengajuan' => 'datetime',
        'tanggal_keputusan' => 'datetime',
    ];

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pegawai_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }

    public function getDurasicutiAttribute(): int
    {
        return $this->tanggal_mulai->diffInDays($this->tanggal_selesai) + 1;
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'Menunggu' => 'warning',
            'Disetujui' => 'success',
            'Ditolak' => 'danger',
            default => 'secondary'
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'Menunggu' => '#f59e0b',  // amber
            'Disetujui' => '#10b981', // green
            'Ditolak' => '#ef4444',   // red
            default => '#6b7280'      // gray
        };
    }

    // Validation methods
    public function isOverlapping(): bool
    {
        return static::where('pegawai_id', $this->pegawai_id)
            ->where('status', 'Disetujui')
            ->where('id', '!=', $this->id ?? 0)
            ->where(function ($query) {
                $query->whereBetween('tanggal_mulai', [$this->tanggal_mulai, $this->tanggal_selesai])
                      ->orWhereBetween('tanggal_selesai', [$this->tanggal_mulai, $this->tanggal_selesai])
                      ->orWhere(function ($q) {
                          $q->where('tanggal_mulai', '<=', $this->tanggal_mulai)
                            ->where('tanggal_selesai', '>=', $this->tanggal_selesai);
                      });
            })
            ->exists();
    }

    public function approve($approver_id, $catatan = null): bool
    {
        return $this->update([
            'status' => 'Disetujui',
            'disetujui_oleh' => $approver_id,
            'tanggal_keputusan' => now(),
            'catatan_approval' => $catatan,
        ]);
    }

    public function reject($approver_id, $catatan): bool
    {
        return $this->update([
            'status' => 'Ditolak',
            'disetujui_oleh' => $approver_id,
            'tanggal_keputusan' => now(),
            'catatan_approval' => $catatan,
        ]);
    }
}
