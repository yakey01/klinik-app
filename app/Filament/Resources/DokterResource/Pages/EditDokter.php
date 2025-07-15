<?php

namespace App\Filament\Resources\DokterResource\Pages;

use App\Filament\Resources\DokterResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;

class EditDokter extends EditRecord
{
    protected static string $resource = DokterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // DEBUG: Log data yang diterima dengan detail username
        \Log::info('EditDokter: Form data received', [
            'data_keys' => array_keys($data),
            'username_received' => $data['username'] ?? 'NOT_SET',
            'username_length' => isset($data['username']) ? strlen($data['username']) : 0,
            'username_bytes' => isset($data['username']) ? bin2hex($data['username']) : 'N/A',
            'has_password' => isset($data['password']) && !empty($data['password']) ? 'YES' : 'NO',
            'user_role' => auth()->user()?->role?->name,
            'is_admin' => auth()->user()?->hasRole('admin') ? 'YES' : 'NO',
            'dokter_id' => $this->getRecord()->id,
            'current_username_in_db' => $this->getRecord()->username ?? 'NULL_IN_DB',
            'form_validation_test' => preg_match('/^[a-zA-Z0-9\s.,-]+$/', $data['username'] ?? '') ? 'PASS' : 'FAIL',
            'full_data' => $data
        ]);
        
        // Always remove password_confirmation field (it's not a database field)
        unset($data['password_confirmation']);
        
        // Only process auth fields if user is admin
        if (auth()->user()?->hasRole('admin')) {
            // Hash password if provided and not empty
            if (!empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
                $data['password_changed_at'] = now();
                $data['password_reset_by'] = auth()->id();
                
                \Log::info('EditDokter: Password will be updated', [
                    'dokter_id' => $this->getRecord()->id,
                    'password_hashed' => 'YES'
                ]);
            } else {
                // Remove password field if empty to avoid overwriting existing password
                unset($data['password']);
                
                \Log::info('EditDokter: Password field removed (empty)', [
                    'dokter_id' => $this->getRecord()->id
                ]);
            }
            
            // Keep username field even if empty (to allow clearing username)
            \Log::info('EditDokter: Username will be saved', [
                'dokter_id' => $this->getRecord()->id,
                'username' => $data['username'] ?? 'NULL'
            ]);
        } else {
            // Remove auth fields if not admin
            unset($data['username'], $data['password'], $data['status_akun']);
            
            \Log::info('EditDokter: Auth fields removed (non-admin user)', [
                'dokter_id' => $this->getRecord()->id,
                'user_role' => auth()->user()?->role?->name
            ]);
        }
        
        // DEBUG: Log final data yang akan disimpan
        \Log::info('EditDokter: Final data to save', [
            'data_keys' => array_keys($data),
            'username' => $data['username'] ?? 'NOT_SET',
            'has_password' => isset($data['password']) ? 'YES' : 'NO',
            'dokter_id' => $this->getRecord()->id
        ]);
        
        return $data;
    }
    
    protected function afterSave(): void
    {
        // Refresh the record to get latest data
        $this->record = $this->record->fresh();
        $record = $this->getRecord();
        
        // SYNC username, password, and other data to related User record if exists
        if ($record->user_id && $record->user) {
            $syncData = [
                'username' => $record->username,
                'name' => $record->nama_lengkap,
                'email' => $record->email
            ];
            
            // Sync password if dokter has password
            if (!empty($record->password)) {
                $syncData['password'] = $record->password;
            }
            
            $record->user->update($syncData);
            
            \Log::info('EditDokter: User record synced', [
                'user_id' => $record->user_id,
                'synced_username' => $record->username,
                'synced_name' => $record->nama_lengkap,
                'synced_email' => $record->email,
                'synced_password' => !empty($record->password) ? 'YES' : 'NO'
            ]);
        }
        
        // DEBUG: Log hasil setelah data disimpan
        \Log::info('EditDokter: Data saved successfully', [
            'dokter_id' => $record->id,
            'saved_username' => $record->username ?? 'NULL',
            'has_password' => !empty($record->password) ? 'YES' : 'NO',
            'status_akun' => $record->status_akun ?? 'NULL',
            'nama_lengkap' => $record->nama_lengkap,
            'updated_at' => $record->updated_at,
            'user_synced' => $record->user_id ? 'YES' : 'NO'
        ]);
        
        // Force refresh form with new data
        $this->fillForm();
        
        \Log::info('EditDokter: Form refreshed with latest data');
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }}
