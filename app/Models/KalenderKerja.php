<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KalenderKerja extends Model
{
    protected $table = 'kalender_kerjas';
    
    protected $fillable = [
        'pegawai_id',
        'tanggal',
        'shift',
        'unit',
        'keterangan',
        'created_by',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pegawai_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getShiftColorAttribute(): string
    {
        return match($this->shift) {
            'Pagi' => 'blue',
            'Sore' => 'yellow', 
            'Malam' => 'purple',
            'Off' => 'gray',
            default => 'gray'
        };
    }
}
