<?php

namespace App\Filament\Resources\PegawaiResource\Pages;

use App\Filament\Resources\PegawaiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;

class EditPegawai extends EditRecord
{
    protected static string $resource = PegawaiResource::class;
    
    // Disable auto-refresh/polling for this page
    protected $listeners = [];
    
    // Extend session timeout for long form editing
    public function mount(int | string $record): void
    {
        parent::mount($record);
        
        // Extend session lifetime specifically for edit pages
        config(['session.lifetime' => 1440]); // 24 hours
        
        // Prevent any automatic refreshes
        $this->dispatch('disable-auto-refresh');
    }

    protected function getHeaderActions(): array
    {
        return [
            // Action to create user account for staff management
            Actions\Action::make('create_user_account')
                ->label('Buat Akun User')
                ->icon('heroicon-m-user-plus')
                ->color('success')
                ->url(fn() => url('/admin/users/create?source=staff_management'))
                ->tooltip('Buat akun login untuk Petugas, Bendahara, atau Pegawai')
                ->openUrlInNewTab(false)
                ->visible(fn() => auth()->user()?->hasPermissionTo('create_user')),
                
            Actions\DeleteAction::make()
                ->label('Hapus Karyawan')
                ->requiresConfirmation(),
        ];
    }
    
    public function getTitle(): string
    {
        return 'Edit Karyawan: ' . $this->record->nama_lengkap;
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function getSavedNotificationTitle(): ?string
    {
        return 'Data karyawan berhasil diperbarui';
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // DEBUG: Log data yang diterima dengan detail username
        \Log::info('EditPegawai: Form data received', [
            'data_keys' => array_keys($data),
            'username_received' => $data['username'] ?? 'NOT_SET',
            'username_length' => isset($data['username']) ? strlen($data['username']) : 0,
            'username_bytes' => isset($data['username']) ? bin2hex($data['username']) : 'N/A',
            'has_password' => isset($data['password']) && !empty($data['password']) ? 'YES' : 'NO',
            'user_role' => auth()->user()?->role?->name,
            'is_admin' => auth()->user()?->hasRole('admin') ? 'YES' : 'NO',
            'pegawai_id' => $this->getRecord()->id,
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
                
                \Log::info('EditPegawai: Password will be updated', [
                    'pegawai_id' => $this->getRecord()->id,
                    'password_hashed' => 'YES'
                ]);
            } else {
                // Remove password field if empty to avoid overwriting existing password
                unset($data['password']);
                
                \Log::info('EditPegawai: Password field removed (empty)', [
                    'pegawai_id' => $this->getRecord()->id
                ]);
            }
            
            // Keep username field even if empty (to allow clearing username)
            \Log::info('EditPegawai: Username will be saved', [
                'pegawai_id' => $this->getRecord()->id,
                'username' => $data['username'] ?? 'NULL'
            ]);
        } else {
            // Remove auth fields if not admin
            unset($data['username'], $data['password'], $data['status_akun']);
            
            \Log::info('EditPegawai: Auth fields removed (non-admin user)', [
                'pegawai_id' => $this->getRecord()->id,
                'user_role' => auth()->user()?->role?->name
            ]);
        }
        
        // DEBUG: Log final data yang akan disimpan
        \Log::info('EditPegawai: Final data to save', [
            'data_keys' => array_keys($data),
            'username' => $data['username'] ?? 'NOT_SET',
            'has_password' => isset($data['password']) ? 'YES' : 'NO',
            'pegawai_id' => $this->getRecord()->id
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
                'nip' => $record->nik
            ];
            
            // Sync password if pegawai has password
            if (!empty($record->password)) {
                $syncData['password'] = $record->password;
            }
            
            $record->user->update($syncData);
            
            \Log::info('EditPegawai: User record synced', [
                'user_id' => $record->user_id,
                'synced_username' => $record->username,
                'synced_name' => $record->nama_lengkap,
                'synced_nip' => $record->nik,
                'synced_password' => !empty($record->password) ? 'YES' : 'NO'
            ]);
        }
        
        // DEBUG: Log hasil setelah data disimpan
        \Log::info('EditPegawai: Data saved successfully', [
            'pegawai_id' => $record->id,
            'saved_username' => $record->username ?? 'NULL',
            'has_password' => !empty($record->password) ? 'YES' : 'NO',
            'status_akun' => $record->status_akun ?? 'NULL',
            'nama_lengkap' => $record->nama_lengkap,
            'updated_at' => $record->updated_at,
            'user_synced' => $record->user_id ? 'YES' : 'NO'
        ]);
        
        // Force refresh form with new data
        $this->fillForm();
        
        \Log::info('EditPegawai: Form refreshed with latest data');
    }
}
