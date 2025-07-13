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
        
        // Ensure username uniqueness
        while (static::where('username', $username)->exists()) {
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
