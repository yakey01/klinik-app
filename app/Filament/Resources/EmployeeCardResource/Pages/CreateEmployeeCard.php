<?php

namespace App\Filament\Resources\EmployeeCardResource\Pages;

use App\Filament\Resources\EmployeeCardResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateEmployeeCard extends CreateRecord
{
    protected static string $resource = EmployeeCardResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Kartu pegawai berhasil dibuat!';
    }
}
