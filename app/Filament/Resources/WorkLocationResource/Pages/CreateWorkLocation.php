<?php

namespace App\Filament\Resources\WorkLocationResource\Pages;

use App\Filament\Resources\WorkLocationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateWorkLocation extends CreateRecord
{
    protected static string $resource = WorkLocationResource::class;

    public function getTitle(): string
    {
        return '➕ Tambah Lokasi Kerja';
    }

    public function getHeading(): string
    {
        return '➕ Tambah Lokasi Kerja';
    }

    public function getSubheading(): ?string
    {
        return 'Konfigurasi lokasi kerja baru dengan geofencing GPS';
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('✅ Lokasi Kerja Berhasil Ditambahkan!')
            ->body('Lokasi kerja telah dikonfigurasi dan siap digunakan untuk validasi absensi.')
            ->duration(5000);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}