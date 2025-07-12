<?php

namespace App\Filament\Resources\WorkLocationResource\Pages;

use App\Filament\Resources\WorkLocationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditWorkLocation extends EditRecord
{
    protected static string $resource = WorkLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('👁️ Lihat Detail')
                ->color('info'),
            Actions\DeleteAction::make()
                ->label('🗑️ Hapus Lokasi')
                ->color('danger'),
        ];
    }

    public function getTitle(): string
    {
        return '✏️ Edit Lokasi Kerja';
    }

    public function getHeading(): string
    {
        return '✏️ Edit Lokasi Kerja';
    }

    public function getSubheading(): ?string
    {
        return 'Perbarui konfigurasi lokasi kerja dan pengaturan geofencing';
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('✅ Lokasi Kerja Berhasil Diperbarui!')
            ->body('Perubahan konfigurasi lokasi kerja telah disimpan.')
            ->duration(4000);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}