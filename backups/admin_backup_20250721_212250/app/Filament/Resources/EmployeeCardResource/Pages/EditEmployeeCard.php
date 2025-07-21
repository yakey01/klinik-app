<?php

namespace App\Filament\Resources\EmployeeCardResource\Pages;

use App\Filament\Resources\EmployeeCardResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditEmployeeCard extends EditRecord
{
    protected static string $resource = EmployeeCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = Auth::id();
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Kartu pegawai berhasil diperbarui!';
    }
}
