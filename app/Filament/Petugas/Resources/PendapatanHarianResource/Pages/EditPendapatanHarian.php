<?php

namespace App\Filament\Petugas\Resources\PendapatanHarianResource\Pages;

use App\Filament\Petugas\Resources\PendapatanHarianResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditPendapatanHarian extends EditRecord
{
    protected static string $resource = PendapatanHarianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('✅ Pendapatan berhasil diperbarui')
            ->body('Data pendapatan harian telah berhasil diubah.');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
