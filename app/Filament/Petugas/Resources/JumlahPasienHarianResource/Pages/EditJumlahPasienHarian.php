<?php

namespace App\Filament\Petugas\Resources\JumlahPasienHarianResource\Pages;

use App\Filament\Petugas\Resources\JumlahPasienHarianResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditJumlahPasienHarian extends EditRecord
{
    protected static string $resource = JumlahPasienHarianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Data Pasien Berhasil Diupdate')
            ->body('Data jumlah pasien harian telah berhasil diperbarui.');
    }
}