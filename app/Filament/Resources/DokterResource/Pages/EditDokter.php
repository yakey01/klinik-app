<?php

namespace App\Filament\Resources\DokterResource\Pages;

use App\Filament\Resources\DokterResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

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
        $dokter = $this->getRecord();
        
        // Validate NIK uniqueness if provided
        if (!empty($data['nik'])) {
            $existing = \App\Models\Dokter::where('nik', $data['nik'])
                ->where('id', '!=', $dokter->id)
                ->first();
            
            if ($existing) {
                throw ValidationException::withMessages([
                    'nik' => "NIK '{$data['nik']}' sudah digunakan oleh dokter '{$existing->nama_lengkap}'. Silakan gunakan NIK yang berbeda."
                ]);
            }
        }
        
        // Validate email uniqueness if provided
        if (!empty($data['email'])) {
            $existing = \App\Models\Dokter::where('email', $data['email'])
                ->where('id', '!=', $dokter->id)
                ->first();
            
            if ($existing) {
                throw ValidationException::withMessages([
                    'email' => "Email '{$data['email']}' sudah digunakan oleh dokter '{$existing->nama_lengkap}'. Silakan gunakan email yang berbeda."
                ]);
            }
        }
        
        // Validate nomor_sip uniqueness if provided
        if (!empty($data['nomor_sip'])) {
            $existing = \App\Models\Dokter::where('nomor_sip', $data['nomor_sip'])
                ->where('id', '!=', $dokter->id)
                ->first();
            
            if ($existing) {
                throw ValidationException::withMessages([
                    'nomor_sip' => "Nomor SIP '{$data['nomor_sip']}' sudah digunakan oleh dokter '{$existing->nama_lengkap}'. Silakan gunakan nomor SIP yang berbeda."
                ]);
            }
        }
        
        // Validate username uniqueness if provided
        if (!empty($data['username'])) {
            $existing = \App\Models\Dokter::where('username', $data['username'])
                ->where('id', '!=', $dokter->id)
                ->first();
            
            if ($existing) {
                throw ValidationException::withMessages([
                    'username' => "Username '{$data['username']}' sudah digunakan oleh dokter '{$existing->nama_lengkap}'. Silakan gunakan username yang berbeda."
                ]);
            }
        }
        
        // DEBUG: Log data yang diterima dengan detail username
        \Log::info('EditDokter: Form data received and validated', [
            'dokter_id' => $dokter->id,
            'username_received' => $data['username'] ?? 'NOT_SET',
            'nik' => $data['nik'] ?? 'NOT_SET',
            'email' => $data['email'] ?? 'NOT_SET',
            'nomor_sip' => $data['nomor_sip'] ?? 'NOT_SET',
            'user_role' => auth()->user()?->role?->name,
            'is_admin' => auth()->user()?->hasRole('admin') ? 'YES' : 'NO'
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
    
    /**
     * Handle save with proper error handling for constraint violations
     */
    protected function handleRecordUpdate($record, array $data): Model
    {
        try {
            return parent::handleRecordUpdate($record, $data);
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23000') {
                // Handle unique constraint violations with user-friendly messages
                $message = $this->getConstraintViolationMessage($e);
                
                \Filament\Notifications\Notification::make()
                    ->title('❌ Gagal Menyimpan Perubahan')
                    ->body($message)
                    ->danger()
                    ->persistent()
                    ->send();
                    
                // Stop the save process
                $this->halt();
            }
            
            throw $e;
        }
    }
    
    /**
     * Get user-friendly constraint violation messages for dokter data
     */
    private function getConstraintViolationMessage(\Exception $e): string
    {
        $message = $e->getMessage();
        
        if (str_contains($message, 'dokters.nik') || str_contains($message, 'UNIQUE constraint failed: dokters.nik')) {
            return 'NIK sudah digunakan oleh dokter lain. Silakan gunakan NIK yang berbeda.';
        } elseif (str_contains($message, 'dokters.username') || str_contains($message, 'UNIQUE constraint failed: dokters.username')) {
            return 'Username sudah digunakan oleh dokter lain. Silakan gunakan username yang berbeda.';
        } elseif (str_contains($message, 'dokters.email') || str_contains($message, 'UNIQUE constraint failed: dokters.email')) {
            return 'Email sudah digunakan oleh dokter lain. Silakan gunakan email yang berbeda.';
        } elseif (str_contains($message, 'dokters.nomor_sip') || str_contains($message, 'UNIQUE constraint failed: dokters.nomor_sip')) {
            return 'Nomor SIP sudah digunakan oleh dokter lain. Silakan gunakan nomor SIP yang berbeda.';
        } elseif (str_contains($message, 'UNIQUE constraint') || str_contains($message, 'Integrity constraint violation')) {
            return 'Data yang dimasukkan sudah ada di sistem. Periksa NIK, username, email, atau nomor SIP yang Anda masukkan.';
        }
        
        return 'Terjadi kesalahan saat menyimpan data dokter. Silakan coba lagi.';
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
            ];
            
            // Only sync email if it's not empty/null
            if (!empty($record->email)) {
                $syncData['email'] = $record->email;
            } else {
                // Keep existing user email if dokter email is empty
                \Log::warning('EditDokter: Dokter email is empty, keeping existing user email', [
                    'dokter_id' => $record->id,
                    'user_email' => $record->user->email
                ]);
            }
            
            // Sync password if dokter has password
            if (!empty($record->password)) {
                $syncData['password'] = $record->password;
            }
            
            $record->user->update($syncData);
            
            \Log::info('EditDokter: User record synced', [
                'user_id' => $record->user_id,
                'synced_username' => $record->username,
                'synced_name' => $record->nama_lengkap,
                'synced_email' => !empty($record->email) ? $record->email : 'KEPT_EXISTING',
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
