<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Dokter extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'nama_lengkap',
        'nik',
        'tanggal_lahir',
        'jenis_kelamin',
        'jabatan',
        'nomor_sip',
        'email',
        'aktif',
        'foto',
        'keterangan',
        'input_by',
        // Auth management fields
        'username',
        'password',
        'status_akun',
        'password_changed_at',
        'last_login_at',
        'password_reset_by',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'aktif' => 'boolean',
        'password_changed_at' => 'datetime',
        'last_login_at' => 'datetime',
    ];

    protected $hidden = [
        'password',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function inputBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'input_by');
    }

    public function passwordResetBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'password_reset_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('aktif', true);
    }

    public function scopeByJabatan($query, $jabatan)
    {
        return $query->where('jabatan', $jabatan);
    }

    // Accessors & Mutators
    public function getDefaultAvatarAttribute(): string
    {
        return 'https://ui-avatars.com/api/?name='.urlencode($this->nama_lengkap).'&background=3b82f6&color=fff';
    }

    public function getJabatanDisplayAttribute(): string
    {
        return match ($this->jabatan) {
            'dokter_umum' => 'Dokter Umum',
            'dokter_gigi' => 'Dokter Gigi', 
            'dokter_spesialis' => 'Dokter Spesialis',
            default => ucfirst(str_replace('_', ' ', $this->jabatan)),
        };
    }

    public function getJabatanBadgeColorAttribute(): string
    {
        return match ($this->jabatan) {
            'dokter_umum' => 'primary',
            'dokter_gigi' => 'success',
            'dokter_spesialis' => 'info',
            default => 'gray',
        };
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return $this->aktif ? 'success' : 'danger';
    }

    public function getStatusTextAttribute(): string
    {
        return $this->aktif ? 'Aktif' : 'Nonaktif';
    }

    public function getStatusAkunBadgeColorAttribute(): string
    {
        return match ($this->status_akun) {
            'Aktif' => 'success',
            'Suspend' => 'danger',
            default => 'gray',
        };
    }

    public function getHasLoginAccountAttribute(): bool
    {
        return !empty($this->username) && !empty($this->password);
    }

    public function getAccountStatusTextAttribute(): string
    {
        if (!$this->has_login_account) {
            return 'Belum Punya Akun';
        }
        
        return $this->status_akun === 'Aktif' ? 'Login Aktif' : 'Login Suspend';
    }

    public function getAccountStatusBadgeColorAttribute(): string
    {
        if (!$this->has_login_account) {
            return 'warning';
        }
        
        return $this->status_akun === 'Aktif' ? 'success' : 'danger';
    }

    // Helper methods
    public static function generateNik(): string
    {
        do {
            $nik = 'DOK' . now()->format('Y') . str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (static::where('nik', $nik)->exists());
        
        return $nik;
    }

    public function getAgeAttribute(): ?int
    {
        return $this->tanggal_lahir ? $this->tanggal_lahir->age : null;
    }

    /**
     * Generate random password for dokter
     */
    public static function generateRandomPassword(): string
    {
        return Str::random(8);
    }

    /**
     * Generate unique username for dokter
     */
    public static function generateUsername(string $namaLengkap): string
    {
        // Create base username from name
        $baseUsername = strtolower(str_replace([' ', '.', ','], '', $namaLengkap));
        $baseUsername = Str::ascii($baseUsername); // Remove accents
        $baseUsername = preg_replace('/[^a-z0-9]/', '', $baseUsername); // Only alphanumeric
        $baseUsername = substr($baseUsername, 0, 20); // Max 20 chars
        
        $username = $baseUsername;
        $counter = 1;
        
        // Ensure uniqueness
        while (static::where('username', $username)->exists()) {
            $username = $baseUsername . $counter;
            $counter++;
        }
        
        return $username;
    }

    /**
     * Create login account for dokter
     */
    public function createLoginAccount(?string $username = null, ?string $password = null): array
    {
        if ($this->has_login_account) {
            return [
                'success' => false,
                'message' => 'Dokter sudah memiliki akun login',
            ];
        }

        $generatedUsername = $username ?: static::generateUsername($this->nama_lengkap);
        $generatedPassword = $password ?: static::generateRandomPassword();

        $this->update([
            'username' => $generatedUsername,
            'password' => bcrypt($generatedPassword),
            'status_akun' => 'Aktif',
            'password_changed_at' => now(),
            'password_reset_by' => auth()->id(),
        ]);

        return [
            'success' => true,
            'username' => $generatedUsername,
            'password' => $generatedPassword,
            'message' => 'Akun login berhasil dibuat',
        ];
    }

    /**
     * Reset password dokter
     */
    public function resetPassword(?string $newPassword = null): array
    {
        if (!$this->has_login_account) {
            return [
                'success' => false,
                'message' => 'Dokter belum memiliki akun login',
            ];
        }

        $generatedPassword = $newPassword ?: static::generateRandomPassword();

        $this->update([
            'password' => bcrypt($generatedPassword),
            'password_changed_at' => now(),
            'password_reset_by' => auth()->id(),
        ]);

        return [
            'success' => true,
            'password' => $generatedPassword,
            'message' => 'Password berhasil direset',
        ];
    }

    /**
     * Toggle account status
     */
    public function toggleAccountStatus(): array
    {
        if (!$this->has_login_account) {
            return [
                'success' => false,
                'message' => 'Dokter belum memiliki akun login',
            ];
        }

        $newStatus = $this->status_akun === 'Aktif' ? 'Suspend' : 'Aktif';
        
        $this->update([
            'status_akun' => $newStatus,
        ]);

        return [
            'success' => true,
            'new_status' => $newStatus,
            'message' => "Status akun berhasil diubah menjadi {$newStatus}",
        ];
    }

    // Boot
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (auth()->check()) {
                $model->input_by = auth()->id();
            }
            
            // Auto-generate NIK if not provided
            if (empty($model->nik)) {
                $model->nik = static::generateNik();
            }
        });
    }
}
