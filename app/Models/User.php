<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use App\Traits\Auditable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, HasRoles, Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'role_id',
        'name',
        'email',
        'password',
        'nip',
        'no_telepon',
        'tanggal_bergabung',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'tanggal_bergabung' => 'date',
        'is_active' => 'boolean',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function tindakanAsDokter(): HasMany
    {
        return $this->hasMany(Tindakan::class, 'dokter_id');
    }

    public function tindakanAsParamedis(): HasMany
    {
        return $this->hasMany(Tindakan::class, 'paramedis_id');
    }

    public function tindakanAsNonParamedis(): HasMany
    {
        return $this->hasMany(Tindakan::class, 'non_paramedis_id');
    }

    public function jaspel(): HasMany
    {
        return $this->hasMany(Jaspel::class);
    }

    public function uangDuduk(): HasMany
    {
        return $this->hasMany(UangDuduk::class);
    }

    public function pendapatanInput(): HasMany
    {
        return $this->hasMany(Pendapatan::class, 'input_by');
    }

    public function pengeluaranInput(): HasMany
    {
        return $this->hasMany(Pengeluaran::class, 'input_by');
    }

    public function locationValidations(): HasMany
    {
        return $this->hasMany(LocationValidation::class);
    }

    public function gpsSpoofingDetections(): HasMany
    {
        return $this->hasMany(GpsSpoofingDetection::class);
    }
    
    // Relationship to dokter if user is a dokter
    public function dokter()
    {
        return $this->hasOne(Dokter::class, 'user_id');
    }
    
    // Relationship to pegawai if user is a pegawai
    public function pegawai()
    {
        return $this->hasOne(Pegawai::class, 'user_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByRole($query, $roleName)
    {
        return $query->whereHas('role', function ($q) use ($roleName) {
            $q->where('name', $roleName);
        });
    }

    /**
     * Check if user has a specific role (custom implementation for legacy compatibility)
     */
    public function hasLegacyRole($roleName)
    {
        // Support both single role string and array of roles
        if (is_array($roleName)) {
            return $this->role && in_array($this->role->name, $roleName);
        }
        
        return $this->role && $this->role->name === $roleName;
    }

    /**
     * Check if user has any of the given roles (legacy compatibility)
     */
    public function hasAnyLegacyRole($roles)
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }
        
        return $this->hasLegacyRole($roles);
    }

    /**
     * Check if user has a specific permission through their role
     */
    public function hasPermission(string $permission): bool
    {
        if (!$this->role || !$this->role->permissions) {
            return false;
        }
        
        return in_array($permission, $this->role->permissions);
    }

    /**
     * Override the can method to use custom role permissions
     */
    public function can($abilities, $arguments = []): bool
    {
        // First check custom role permissions
        if (is_string($abilities) && $this->hasPermission($abilities)) {
            return true;
        }
        
        // Fall back to Spatie's can method
        return parent::can($abilities, $arguments);
    }

    /**
     * Determine if the user can access the given Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Check panel ID and user role using custom role relationship
        if ($panel->getId() === 'admin') {
            return $this->role && $this->role->name === 'admin';
        }
        
        if ($panel->getId() === 'bendahara') {
            return $this->role && $this->role->name === 'bendahara';
        }
        
        if ($panel->getId() === 'petugas') {
            return $this->role && $this->role->name === 'petugas';
        }
        
        if ($panel->getId() === 'paramedis') {
            return $this->role && $this->role->name === 'paramedis';
        }
        
        if ($panel->getId() === 'dokter') {
            return $this->role && $this->role->name === 'dokter';
        }
        
        if ($panel->getId() === 'manajer') {
            return $this->role && $this->role->name === 'manajer';
        }
        
        return false;
    }
}
