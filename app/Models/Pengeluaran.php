<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Cacheable;
use App\Traits\LogsActivity;

class Pengeluaran extends Model
{
    use SoftDeletes, Cacheable, LogsActivity;

    protected $table = 'pengeluaran';

    protected $fillable = [
        'kode_pengeluaran',
        'nama_pengeluaran',
        'tanggal',
        'keterangan',
        'nominal',
        'kategori',
        'bukti_transaksi',
        'input_by',
        'status_validasi',
        'validasi_by',
        'validasi_at',
        'catatan_validasi',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'nominal' => 'decimal:2',
        'validasi_at' => 'datetime',
    ];
    
    // Cache TTL for this model (in seconds)
    protected int $cacheTtl = 1800; // 30 minutes

    public function inputBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'input_by');
    }

    public function validasiBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validasi_by');
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status_validasi', $status);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('tanggal', [$startDate, $endDate]);
    }

    public function scopeByKategori($query, $kategori)
    {
        return $query->where('kategori', $kategori);
    }

    public function scopePending($query)
    {
        return $query->where('status_validasi', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status_validasi', 'disetujui');
    }
    
    // Cache commonly used statistics
    public static function getCachedStats(): array
    {
        return static::cacheStatistics('pengeluaran_stats', function() {
            return [
                'total_count' => static::count(),
                'pending_count' => static::where('status_validasi', 'pending')->count(),
                'approved_count' => static::where('status_validasi', 'disetujui')->count(),
                'rejected_count' => static::where('status_validasi', 'ditolak')->count(),
                'today_count' => static::whereDate('tanggal', today())->count(),
                'this_month_count' => static::whereMonth('tanggal', now()->month)
                    ->whereYear('tanggal', now()->year)
                    ->count(),
                'total_amount' => static::where('status_validasi', 'disetujui')
                    ->sum('nominal') ?? 0,
                'avg_amount' => static::where('status_validasi', 'disetujui')
                    ->avg('nominal') ?? 0,
                'monthly_total' => static::whereMonth('tanggal', now()->month)
                    ->whereYear('tanggal', now()->year)
                    ->where('status_validasi', 'disetujui')
                    ->sum('nominal') ?? 0,
            ];
        });
    }
    
    // Cache formatted status
    public function getStatusFormattedAttribute(): string
    {
        return $this->cacheAttribute('status_formatted', function() {
            return match($this->status_validasi) {
                'pending' => 'Menunggu Validasi',
                'disetujui' => 'Disetujui',
                'ditolak' => 'Ditolak',
                default => ucfirst($this->status_validasi)
            };
        });
    }
    
    // Cache formatted nominal
    public function getNominalFormattedAttribute(): string
    {
        return $this->cacheAttribute('nominal_formatted', function() {
            return 'Rp ' . number_format($this->nominal, 0, ',', '.');
        });
    }
    
    // Cache kategori formatted
    public function getKategoriFormattedAttribute(): string
    {
        return $this->cacheAttribute('kategori_formatted', function() {
            return ucwords(str_replace('_', ' ', $this->kategori));
        });
    }
    
    // Cache if this pengeluaran has bukti transaksi
    public function getHasBuktiTransaksiAttribute(): bool
    {
        return $this->cacheAttribute('has_bukti_transaksi', function() {
            return !is_null($this->bukti_transaksi) && !empty($this->bukti_transaksi);
        });
    }
}
