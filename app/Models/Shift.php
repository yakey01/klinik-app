<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'description',
        'is_active',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'is_active' => 'boolean',
    ];

    public function tindakan(): HasMany
    {
        return $this->hasMany(Tindakan::class);
    }

    public function jaspel(): HasMany
    {
        return $this->hasMany(Jaspel::class);
    }

    public function uangDuduk(): HasMany
    {
        return $this->hasMany(UangDuduk::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
