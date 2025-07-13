<?php

namespace App\Filament\Resources\PegawaiResource\Pages;

use App\Filament\Resources\PegawaiResource;
use App\Models\Pegawai;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;

class CreatePegawai extends CreateRecord
{
    protected static string $resource = PegawaiResource::class;
    
    public function getTitle(): string
    {
        return 'Tambah Karyawan Baru';
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Karyawan berhasil ditambahkan';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        \Log::info('CreatePegawai: Form data received', [
            'data_keys' => array_keys($data),
            'username' => $data['username'] ?? 'NOT_SET',
            'has_password' => isset($data['password']) && !empty($data['password']) ? 'YES' : 'NO',
            'user_role' => auth()->user()?->role?->name,
            'is_admin' => auth()->user()?->hasRole('admin') ? 'YES' : 'NO',
            'full_data' => $data
        ]);

        // Always remove password_confirmation field (it's not a database field)
        unset($data['password_confirmation']);

        // Only process auth fields if user is admin
        if (auth()->user()?->hasRole('admin')) {
            // Auto-generate username if not provided but password is provided
            if (empty($data['username']) && !empty($data['password'])) {
                $data['username'] = Pegawai::generateUsername($data['nama_lengkap']);
                \Log::info('CreatePegawai: Auto-generated username', [
                    'generated_username' => $data['username']
                ]);
            }
            
            // Auto-generate password if not provided but username is provided
            if (empty($data['password']) && !empty($data['username'])) {
                $data['password'] = Pegawai::generateRandomPassword();
                \Log::info('CreatePegawai: Auto-generated password', [
                    'password_length' => strlen($data['password'])
                ]);
            }
            
            // Hash password if provided
            if (!empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
                $data['password_changed_at'] = now();
                $data['password_reset_by'] = auth()->id();
                
                \Log::info('CreatePegawai: Password hashed and metadata set', [
                    'password_changed_at' => $data['password_changed_at'],
                    'password_reset_by' => $data['password_reset_by']
                ]);
            }
        } else {
            // Remove auth fields if not admin
            unset($data['username'], $data['password'], $data['status_akun']);
            
            \Log::info('CreatePegawai: Auth fields removed (non-admin user)', [
                'user_role' => auth()->user()?->role?->name
            ]);
        }
        
        \Log::info('CreatePegawai: Final data to create', [
            'data_keys' => array_keys($data),
            'username' => $data['username'] ?? 'NOT_SET',
            'has_password' => isset($data['password']) ? 'YES' : 'NO',
            'status_akun' => $data['status_akun'] ?? 'NOT_SET'
        ]);
        
        return $data;
    }
}
