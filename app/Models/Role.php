<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use HasFactory;
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'permissions',
        'is_active',
        'guard_name',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_active' => 'boolean',
    ];

    public function clinicUsers(): HasMany
    {
        return $this->hasMany(User::class, 'role_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
