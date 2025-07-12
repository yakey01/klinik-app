<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pasien extends Model
{
    use SoftDeletes;

    protected $table = 'pasien';

    protected $fillable = [
        'no_rekam_medis',
        'nama',
        'tanggal_lahir',
        'jenis_kelamin',
        'alamat',
        'no_telepon',
        'email',
        'pekerjaan',
        'status_pernikahan',
        'kontak_darurat_nama',
        'kontak_darurat_telepon',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
    ];

    public function tindakan(): HasMany
    {
        return $this->hasMany(Tindakan::class);
    }

    public function scopeByGender($query, $gender)
    {
        return $query->where('jenis_kelamin', $gender);
    }

    public function getUmurAttribute()
    {
        return $this->tanggal_lahir?->age;
    }
}
