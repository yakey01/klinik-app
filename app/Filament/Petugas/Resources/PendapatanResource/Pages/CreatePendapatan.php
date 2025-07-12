<?php

namespace App\Filament\Petugas\Resources\PendapatanResource\Pages;

use App\Filament\Petugas\Resources\PendapatanResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreatePendapatan extends CreateRecord
{
    protected static string $resource = PendapatanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['input_by'] = Auth::id();
        $data['status_validasi'] = 'pending';
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}