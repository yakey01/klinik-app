<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

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
     * Log form data yang diterima saat edit
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // DEBUG: Log form data yang diterima untuk edit
        \Log::info('EditUser: Form data received for edit', [
            'user_id' => $this->getRecord()->id,
            'username_before' => $this->getRecord()->username,
            'username_after' => $data['username'] ?? 'NOT_SET',
            'data_keys' => array_keys($data),
            'full_data' => $data
        ]);
        
        return $data;
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
        ]);
        
        return parent::getSavedNotification();
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
