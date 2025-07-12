<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tindakan extends Model
{
    use SoftDeletes;

    protected $table = 'tindakan';

    protected $fillable = [
        'pasien_id',
        'jenis_tindakan_id',
        'dokter_id',
        'paramedis_id',
        'non_paramedis_id',
        'shift_id',
        'tanggal_tindakan',
        'tarif',
        'jasa_dokter',
        'jasa_paramedis',
        'jasa_non_paramedis',
        'catatan',
        'status',
        'input_by',
    ];

    protected $casts = [
        'tanggal_tindakan' => 'datetime',
        'tarif' => 'decimal:2',
        'jasa_dokter' => 'decimal:2',
        'jasa_paramedis' => 'decimal:2',
        'jasa_non_paramedis' => 'decimal:2',
    ];

    public function pasien(): BelongsTo
    {
        return $this->belongsTo(Pasien::class);
    }

    public function jenisTindakan(): BelongsTo
    {
        return $this->belongsTo(JenisTindakan::class);
    }

    public function dokter(): BelongsTo
    {
        return $this->belongsTo(Dokter::class, 'dokter_id');
    }

    public function paramedis(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'paramedis_id');
    }

    public function nonParamedis(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'non_paramedis_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function inputBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'input_by');
    }

    public function jaspel(): HasMany
    {
        return $this->hasMany(Jaspel::class);
    }

    public function pendapatan(): HasMany
    {
        return $this->hasMany(Pendapatan::class);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('tanggal_tindakan', [$startDate, $endDate]);
    }

    public function scopeByDokter($query, $dokterId)
    {
        return $query->where('dokter_id', $dokterId);
    }
}
