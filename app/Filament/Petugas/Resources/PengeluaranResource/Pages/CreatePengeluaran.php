<?php

namespace App\Filament\Petugas\Resources\PengeluaranResource\Pages;

use App\Filament\Petugas\Resources\PengeluaranResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreatePengeluaran extends CreateRecord
{
    protected static string $resource = PengeluaranResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['input_by'] = Auth::id();
        $data['status'] = 'pending';
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}