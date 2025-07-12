<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendapatanHarian extends Model
{
    protected $table = 'pendapatan_harians';
    
    protected $fillable = [
        'tanggal_input',
        'shift',
        'pendapatan_id',
        'nominal',
        'deskripsi',
        'user_id',
    ];

    protected $casts = [
        'tanggal_input' => 'date',
        'nominal' => 'decimal:2',
    ];

    public function pendapatan(): BelongsTo
    {
        return $this->belongsTo(Pendapatan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
