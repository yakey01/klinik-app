<?php

namespace App\Filament\Petugas\Resources\JumlahPasienHarianResource\Pages;

use App\Filament\Petugas\Resources\JumlahPasienHarianResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateJumlahPasienHarian extends CreateRecord
{
    protected static string $resource = JumlahPasienHarianResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Data Pasien Berhasil Disimpan')
            ->body('Data jumlah pasien harian telah berhasil ditambahkan.');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['input_by'] = auth()->id();
        
        return $data;
    }
}