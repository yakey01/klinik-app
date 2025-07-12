<?php

namespace App\Filament\Petugas\Resources\TindakanResource\Pages;

use App\Filament\Petugas\Resources\TindakanResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateTindakan extends CreateRecord
{
    protected static string $resource = TindakanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['input_by'] = Auth::id();
        $data['status'] = 'pending';
        
        // Set current user as dokter if not specified
        if (!isset($data['dokter_id'])) {
            $data['dokter_id'] = Auth::id();
        }
        
        // Set default shift to current shift (assuming shift 1 for now)
        if (!isset($data['shift_id'])) {
            $data['shift_id'] = 1;
        }
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}