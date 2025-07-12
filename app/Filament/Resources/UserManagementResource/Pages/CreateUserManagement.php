<?php

namespace App\Filament\Resources\UserManagementResource\Pages;

use App\Filament\Resources\UserManagementResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateUserManagement extends CreateRecord
{
    protected static string $resource = UserManagementResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-generate NIP if not provided
        if (empty($data['nip'])) {
            $data['nip'] = $this->generateNIP();
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        \Filament\Notifications\Notification::make()
            ->title('User Created Successfully')
            ->body("User '{$this->record->name}' has been created with NIP: {$this->record->nip}")
            ->success()
            ->send();
    }

    private function generateNIP(): string
    {
        do {
            $nip = 'USR' . now()->format('Y') . str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (\App\Models\User::where('nip', $nip)->exists());
        
        return $nip;
    }
}