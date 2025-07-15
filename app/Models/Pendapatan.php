<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Cacheable;
use App\Traits\LogsActivity;

class Pendapatan extends Model
{
    use SoftDeletes, Cacheable, LogsActivity;

    protected $table = 'pendapatan';

    protected $fillable = [
        'kode_pendapatan',
        'nama_pendapatan',
        'sumber_pendapatan',
        'is_aktif',
        'tanggal',
        'keterangan',
        'nominal',
        'kategori',
        'tindakan_id',
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
        'is_aktif' => 'boolean',
    ];
    
    // Cache TTL for this model (in seconds)
    protected int $cacheTtl = 1800; // 30 minutes

    public function tindakan(): BelongsTo
    {
        return $this->cacheRelation('tindakan', function() {
            return $this->belongsTo(Tindakan::class);
        });
    }

    public function inputBy(): BelongsTo
    {
        return $this->cacheRelation('inputBy', function() {
            return $this->belongsTo(User::class, 'input_by');
        });
    }

    public function validasiBy(): BelongsTo
    {
        return $this->cacheRelation('validasiBy', function() {
            return $this->belongsTo(User::class, 'validasi_by');
        });
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

    public function scopeAktif($query)
    {
        return $query->where('is_aktif', true);
    }

    public function scopeNonAktif($query)
    {
        return $query->where('is_aktif', false);
    }
    
    // Cache commonly used statistics
    public static function getCachedStats(): array
    {
        return static::cacheStatistics('pendapatan_stats', function() {
            return [
                'total_count' => static::count(),
                'pending_count' => static::where('status_validasi', 'pending')->count(),
                'approved_count' => static::where('status_validasi', 'disetujui')->count(),
                'active_count' => static::where('is_aktif', true)->count(),
                'today_count' => static::whereDate('tanggal', today())->count(),
                'this_month_count' => static::whereMonth('tanggal', now()->month)
                    ->whereYear('tanggal', now()->year)
                    ->count(),
                'total_amount' => static::where('status_validasi', 'disetujui')
                    ->where('is_aktif', true)
                    ->sum('nominal') ?? 0,
                'avg_amount' => static::where('status_validasi', 'disetujui')
                    ->where('is_aktif', true)
                    ->avg('nominal') ?? 0,
                'monthly_total' => static::whereMonth('tanggal', now()->month)
                    ->whereYear('tanggal', now()->year)
                    ->where('status_validasi', 'disetujui')
                    ->where('is_aktif', true)
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
    
    // Cache if this pendapatan is from tindakan
    public function getIsFromTindakanAttribute(): bool
    {
        return $this->cacheAttribute('is_from_tindakan', function() {
            return !is_null($this->tindakan_id);
        });
    }
}
