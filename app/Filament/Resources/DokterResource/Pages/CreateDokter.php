<?php

namespace App\Filament\Resources\DokterResource\Pages;

use App\Filament\Resources\DokterResource;
use App\Models\Dokter;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;

class CreateDokter extends CreateRecord
{
    protected static string $resource = DokterResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Only process auth fields if user is admin
        if (auth()->user()?->hasRole('admin')) {
            // Auto-generate username if not provided but password is provided
            if (empty($data['username']) && !empty($data['password'])) {
                $data['username'] = Dokter::generateUsername($data['nama_lengkap']);
            }
            
            // Auto-generate password if not provided but username is provided
            if (empty($data['password']) && !empty($data['username'])) {
                $data['password'] = Dokter::generateRandomPassword();
            }
            
            // Hash password if provided
            if (!empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
                $data['password_changed_at'] = now();
                $data['password_reset_by'] = auth()->id();
            }
        } else {
            // Remove auth fields if not admin
            unset($data['username'], $data['password'], $data['status_akun']);
        }
        
        return $data;
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
