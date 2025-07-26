<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pegawai extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pegawais';

    protected $fillable = [
        'nik',
        'nama_lengkap',
        'email',
        'tanggal_lahir',
        'jenis_kelamin',
        'jabatan',
        'jenis_pegawai',
        'aktif',
        'foto',
        'input_by',
        'user_id',
        'username',
        'password',
        'status_akun',
        'password_changed_at',
        'password_reset_by',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'aktif' => 'boolean',
        'password_changed_at' => 'datetime',
    ];

    protected $hidden = [
        'password',
    ];

    public function inputBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'input_by');
    }

    public function employeeCard(): HasOne
    {
        return $this->hasOne(EmployeeCard::class, 'pegawai_id');
    }

    /**
     * Legacy relationship - single user per pegawai (being phased out)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tindakanAsParamedis(): HasMany
    {
        return $this->hasMany(Tindakan::class, 'paramedis_id');
    }

    public function tindakanAsNonParamedis(): HasMany
    {
        return $this->hasMany(Tindakan::class, 'non_paramedis_id');
    }

    /**
     * New relationship - multiple users per pegawai (different roles)
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'pegawai_id');
    }

    /**
     * Get all user accounts for this pegawai with their roles
     */
    public function getUserAccountsAttribute()
    {
        return $this->users()->with('role')->get();
    }

    /**
     * Check if pegawai has any user accounts
     */
    public function getHasUserAccountsAttribute(): bool
    {
        return $this->users()->count() > 0;
    }

    /**
     * Get roles this pegawai has
     */
    public function getRolesAttribute()
    {
        return $this->users()->with('role')->get()->pluck('role.display_name')->unique();
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

    // Login account management methods (similar to Dokter model)
    public function getHasLoginAccountAttribute(): bool
    {
        return !empty($this->username) && !empty($this->password);
    }

    public function getAccountStatusTextAttribute(): string
    {
        if (!$this->has_login_account) {
            return 'Belum Ada Akun';
        }
        
        return $this->status_akun === 'Aktif' ? 'Akun Aktif' : 'Akun Suspend';
    }

    public function getAccountStatusBadgeColorAttribute(): string
    {
        if (!$this->has_login_account) {
            return 'gray';
        }
        
        return $this->status_akun === 'Aktif' ? 'success' : 'danger';
    }

    public static function generateUsername(string $namaLengkap): string
    {
        // Clean and generate base username
        $baseUsername = strtolower(str_replace([' ', '.', ',', '-'], '', $namaLengkap));
        $baseUsername = \Illuminate\Support\Str::ascii($baseUsername);
        $baseUsername = preg_replace('/[^a-z0-9]/', '', $baseUsername);
        $baseUsername = substr($baseUsername, 0, 20);
        
        $username = $baseUsername;
        $counter = 1;
        
        // Ensure username uniqueness - ONLY check active (non-soft-deleted) records
        // This allows reuse of usernames from soft-deleted pegawai
        while (static::where('username', $username)->whereNull('deleted_at')->exists()) {
            $username = $baseUsername . $counter;
            $counter++;
        }
        
        return $username;
    }

    public static function generateRandomPassword(): string
    {
        return \Illuminate\Support\Str::random(8);
    }

    public function createLoginAccount(): array
    {
        if ($this->has_login_account) {
            return [
                'success' => false,
                'message' => 'Pegawai sudah memiliki akun login'
            ];
        }

        try {
            $username = static::generateUsername($this->nama_lengkap);
            $password = static::generateRandomPassword();

            $this->update([
                'username' => $username,
                'password' => \Illuminate\Support\Facades\Hash::make($password),
                'status_akun' => 'Aktif',
                'password_changed_at' => now(),
                'password_reset_by' => auth()->id(),
            ]);

            return [
                'success' => true,
                'username' => $username,
                'password' => $password,
                'message' => 'Akun login berhasil dibuat'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Gagal membuat akun: ' . $e->getMessage()
            ];
        }
    }

    public function resetPassword(): array
    {
        if (!$this->has_login_account) {
            return [
                'success' => false,
                'message' => 'Pegawai belum memiliki akun login'
            ];
        }

        try {
            $password = static::generateRandomPassword();

            $this->update([
                'password' => \Illuminate\Support\Facades\Hash::make($password),
                'password_changed_at' => now(),
                'password_reset_by' => auth()->id(),
            ]);

            return [
                'success' => true,
                'password' => $password,
                'message' => 'Password berhasil direset'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Gagal reset password: ' . $e->getMessage()
            ];
        }
    }

    public function toggleAccountStatus(): array
    {
        if (!$this->has_login_account) {
            return [
                'success' => false,
                'message' => 'Pegawai belum memiliki akun login'
            ];
        }

        try {
            $newStatus = $this->status_akun === 'Aktif' ? 'Suspend' : 'Aktif';
            
            $this->update([
                'status_akun' => $newStatus
            ]);

            $message = $newStatus === 'Aktif' 
                ? 'Akun berhasil diaktifkan'
                : 'Akun berhasil di-suspend';

            return [
                'success' => true,
                'message' => $message
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Gagal mengubah status: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check if username is available (excluding soft deleted records)
     * 
     * @param string $username
     * @param int|null $excludeId
     * @return array
     */
    public static function checkUsernameAvailability(string $username, ?int $excludeId = null): array
    {
        $query = static::where('username', $username)->whereNull('deleted_at');
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        $existingPegawai = $query->first();
        
        if ($existingPegawai) {
            return [
                'available' => false,
                'message' => "Username '{$username}' sudah digunakan oleh pegawai '{$existingPegawai->nama_lengkap}' (NIK: {$existingPegawai->nik}). Silakan gunakan username yang berbeda.",
                'existing_pegawai' => [
                    'id' => $existingPegawai->id,
                    'nama_lengkap' => $existingPegawai->nama_lengkap,
                    'nik' => $existingPegawai->nik,
                    'jabatan' => $existingPegawai->jabatan
                ]
            ];
        }
        
        // Check if username was used by soft-deleted pegawai (for info only)
        $deletedPegawai = static::onlyTrashed()->where('username', $username)->first();
        if ($deletedPegawai) {
            return [
                'available' => true,
                'message' => "Username '{$username}' tersedia untuk digunakan. (Previously used by deleted employee: {$deletedPegawai->nama_lengkap})",
                'reused_from_deleted' => true,
                'deleted_pegawai' => [
                    'nama_lengkap' => $deletedPegawai->nama_lengkap,
                    'deleted_at' => $deletedPegawai->deleted_at
                ]
            ];
        }
        
        return [
            'available' => true,
            'message' => "Username '{$username}' tersedia untuk digunakan.",
            'reused_from_deleted' => false
        ];
    }

    /**
     * Soft delete override to handle username cleanup
     */
    public function delete()
    {
        // Perform soft delete
        $result = parent::delete();
        
        if ($result) {
            // Optional: Clear sensitive data but keep username for constraint reference
            // We don't null the username because we want to track usage history
            // The migration removes the unique constraint so usernames can be reused
            
            \Log::info('Pegawai soft deleted', [
                'id' => $this->id,
                'nama_lengkap' => $this->nama_lengkap,
                'username' => $this->username,
                'deleted_at' => $this->deleted_at
            ]);
        }
        
        return $result;
    }

    /**
     * Force delete override to handle complete cleanup
     */
    public function forceDelete()
    {
        $username = $this->username;
        $nama = $this->nama_lengkap;
        
        // Perform force delete
        $result = parent::forceDelete();
        
        if ($result && $username) {
            \Log::info('Pegawai permanently deleted, username now available for reuse', [
                'username' => $username,
                'nama_lengkap' => $nama
            ]);
        }
        
        return $result;
    }

    /**
     * Restore override to handle username conflicts
     */
    public function restore()
    {
        // Check if username is now taken by another active pegawai
        if ($this->username) {
            $conflict = static::where('username', $this->username)
                             ->whereNull('deleted_at')
                             ->where('id', '!=', $this->id)
                             ->first();
                             
            if ($conflict) {
                // Generate new username for restore
                $oldUsername = $this->username;
                $newUsername = static::generateUsername($this->nama_lengkap);
                $this->username = $newUsername;
                
                \Log::warning('Username conflict during restore, assigned new username', [
                    'pegawai_id' => $this->id,
                    'nama_lengkap' => $this->nama_lengkap,
                    'old_username' => $oldUsername,
                    'new_username' => $newUsername,
                    'conflict_with_pegawai' => $conflict->nama_lengkap
                ]);
            }
        }
        
        return parent::restore();
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (auth()->check()) {
                $model->input_by = auth()->id();
            }
        });

        static::updated(function ($model) {
            // If email was changed, update all associated user accounts
            if ($model->wasChanged('email') && $model->email) {
                $model->users()->update(['email' => $model->email]);
            }
        });

        // Hook into soft deleting event
        static::deleting(function ($model) {
            // Soft delete associated user accounts
            if ($model->users()->exists()) {
                $model->users()->each(function ($user) {
                    $user->delete(); // Soft delete user accounts too
                });
                
                \Log::info('Associated user accounts soft deleted', [
                    'pegawai_id' => $model->id,
                    'user_count' => $model->users()->count()
                ]);
            }
        });

        // Hook into force deleting event
        static::forceDeleting(function ($model) {
            // Force delete associated user accounts
            if ($model->users()->withTrashed()->exists()) {
                $model->users()->withTrashed()->forceDelete();
                
                \Log::info('Associated user accounts permanently deleted', [
                    'pegawai_id' => $model->id
                ]);
            }
        });

        // Hook into restoring event
        static::restoring(function ($model) {
            // Restore associated user accounts if they exist
            $trashedUsers = $model->users()->onlyTrashed()->get();
            if ($trashedUsers->count() > 0) {
                foreach ($trashedUsers as $user) {
                    $user->restore();
                }
                
                \Log::info('Associated user accounts restored', [
                    'pegawai_id' => $model->id,
                    'restored_user_count' => $trashedUsers->count()
                ]);
            }
        });
    }
}
