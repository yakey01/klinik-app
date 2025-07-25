<?php

namespace App\Filament\Resources\JenisTindakanResource\Pages;

use App\Filament\Resources\JenisTindakanResource;
use Filament\Resources\Pages\CreateRecord;

class CreateJenisTindakan extends CreateRecord
{
    protected static string $resource = JenisTindakanResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['kode'] = strtoupper($data['kode']);
        
        return $data;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Jenis tindakan berhasil ditambahkan!';
    }
}