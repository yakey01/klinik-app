<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JaspelRekap extends Model
{
    protected $table = 'jaspel_rekaps';

    protected $fillable = [
        'dokter_id',
        'bulan',
        'tahun',
        'total_umum',
        'total_bpjs',
        'total_tindakan',
        'status_pembayaran'
    ];

    protected $casts = [
        'bulan' => 'integer',
        'tahun' => 'integer',
        'total_umum' => 'decimal:2',
        'total_bpjs' => 'decimal:2',
        'total_tindakan' => 'integer'
    ];

    public function dokter(): BelongsTo
    {
        return $this->belongsTo(Dokter::class);
    }

    public function getTotalJaspelAttribute(): float
    {
        return $this->total_umum + $this->total_bpjs;
    }

    public function getNamaBulanAttribute(): string
    {
        $bulanNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        return $bulanNames[$this->bulan] ?? '';
    }

    public function getPeriodeAttribute(): string
    {
        return $this->nama_bulan . ' ' . $this->tahun;
    }

    public function scopeByDokter($query, $dokterId)
    {
        return $query->where('dokter_id', $dokterId);
    }

    public function scopeByPeriode($query, $bulan, $tahun)
    {
        return $query->where('bulan', $bulan)->where('tahun', $tahun);
    }

    public function scopeDibayar($query)
    {
        return $query->where('status_pembayaran', 'dibayar');
    }

    public function scopePending($query)
    {
        return $query->where('status_pembayaran', 'pending');
    }

    // Helper to calculate rekap from tindakan
    public static function calculateFromTindakan($dokterId, $bulan, $tahun)
    {
        $startDate = \Carbon\Carbon::create($tahun, $bulan, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $tindakans = Tindakan::where('dokter_id', $dokterId)
            ->whereBetween('tanggal_tindakan', [$startDate, $endDate])
            ->where('status', 'selesai')
            ->where('status_validasi', 'disetujui')
            ->get();

        $totalUmum = 0;
        $totalBpjs = 0;
        $totalTindakan = 0;

        foreach ($tindakans as $tindakan) {
            $totalTindakan++;
            
            // Assuming we have jenis_pasien in pasien or tindakan
            $jenisPasien = $tindakan->pasien->jenis_pasien ?? 'umum';
            
            if ($jenisPasien === 'bpjs') {
                $totalBpjs += $tindakan->jasa_dokter;
            } else {
                $totalUmum += $tindakan->jasa_dokter;
            }
        }

        return [
            'total_umum' => $totalUmum,
            'total_bpjs' => $totalBpjs,
            'total_tindakan' => $totalTindakan
        ];
    }
}