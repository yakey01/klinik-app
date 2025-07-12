<?php

namespace App\Filament\Petugas\Resources\PengeluaranHarianResource\Pages;

use App\Filament\Petugas\Resources\PengeluaranHarianResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreatePengeluaranHarian extends CreateRecord
{
    protected static string $resource = PengeluaranHarianResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
        $data['status_validasi'] = 'pending';
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}