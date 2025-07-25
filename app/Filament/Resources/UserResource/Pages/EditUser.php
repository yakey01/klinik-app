<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
    /**
     * Handle form data mutation and constraint validation before save
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = $this->getRecord();
        
        // Clean up NIP - make it null if empty for Pegawai/Dokter
        if (isset($data['nip']) && empty(trim($data['nip']))) {
            $data['nip'] = null;
        }
        
        // For Pegawai and Dokter, NIP should be optional/nullable
        $roleId = $data['role_id'] ?? $user->role_id;
        if ($roleId) {
            $role = \App\Models\Role::find($roleId);
            if ($role && in_array($role->name, ['dokter', 'pegawai'])) {
                // For Dokter and Pegawai, NIP can be null
                if (empty($data['nip'])) {
                    $data['nip'] = null;
                }
            }
        }
        
        // Validate NIP uniqueness if provided
        if (!empty($data['nip'])) {
            $nipCheck = \App\Models\User::checkNipAvailability($data['nip'], $user->id);
            if (!$nipCheck['available']) {
                throw new \Filament\Notifications\DatabaseNotification([
                    'title' => '⚠️ NIP Sudah Digunakan',
                    'body' => $nipCheck['message'],
                    'status' => 'danger'
                ]);
            }
        }
        
        // DEBUG: Log form data yang diterima untuk edit
        \Log::info('EditUser: Form data processed for edit', [
            'user_id' => $user->id,
            'nip_before' => $user->nip,
            'nip_after' => $data['nip'] ?? 'NULL',
            'role' => $role->name ?? 'unknown',
            'data_keys' => array_keys($data)
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
                $message = \App\Models\User::getConstraintViolationMessage($e);
                
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
     * Log hasil setelah save
     */
    protected function getSavedNotification(): ?\Filament\Notifications\Notification
    {
        $user = $this->getRecord();
        
        // DEBUG: Log user setelah disimpan
        \Log::info('EditUser: User updated successfully', [
            'user_id' => $user->id,
            'username' => $user->username ?: 'EMPTY',
            'name' => $user->name,
            'email' => $user->email,
            'nip' => $user->nip ?: 'NULL',
        ]);
        
        return \Filament\Notifications\Notification::make()
            ->title('✅ User Berhasil Diperbarui')
            ->body('Data user telah berhasil disimpan dengan aman.')
            ->success();
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
