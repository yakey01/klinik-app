<?php

namespace App\Filament\Petugas\Resources\PasienResource\Pages;

use App\Filament\Petugas\Resources\PasienResource;
use App\Models\Pasien;
use Filament\Resources\Pages\CreateRecord;

class CreatePasien extends CreateRecord
{
    protected static string $resource = PasienResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-generate no_rekam_medis if not provided
        if (empty($data['no_rekam_medis'])) {
            $data['no_rekam_medis'] = 'RM-' . date('Y') . '-' . str_pad(Pasien::count() + 1, 3, '0', STR_PAD_LEFT);
        }
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}