<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pegawai extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nik',
        'nama_lengkap',
        'tanggal_lahir',
        'jenis_kelamin',
        'jabatan',
        'jenis_pegawai',
        'aktif',
        'foto',
        'input_by',
        'user_id',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'aktif' => 'boolean',
    ];

    public function inputBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'input_by');
    }

    public function employeeCard(): HasOne
    {
        return $this->hasOne(EmployeeCard::class, 'pegawai_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getDefaultAvatarAttribute(): string
    {
        return 'https://ui-avatars.com/api/?name='.urlencode($this->nama_lengkap).'&background=3b82f6&color=fff';
    }

    public function getJenisPegawaiBadgeColorAttribute(): string
    {
        return match ($this->jenis_pegawai) {
            'Paramedis' => 'primary',
            'Non-Paramedis' => 'success',
            default => 'gray',
        };
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return $this->aktif ? 'success' : 'danger';
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (auth()->check()) {
                $model->input_by = auth()->id();
            }
        });
    }
}
