<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendapatanHarian extends Model
{
    use HasFactory;
    
    protected $table = 'pendapatan_harians';
    
    protected $fillable = [
        'tanggal_input',
        'shift',
        'pendapatan_id',
        'nominal',
        'deskripsi',
        'user_id',
        'status_validasi',
        'validasi_by',
        'validasi_at',
        'catatan_validasi',
    ];

    protected $casts = [
        'tanggal_input' => 'date',
        'nominal' => 'decimal:2',
        'validasi_at' => 'datetime',
    ];

    public function pendapatan(): BelongsTo
    {
        return $this->belongsTo(Pendapatan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function inputBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function validasiBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validasi_by');
    }
}
