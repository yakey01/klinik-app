<?php

namespace App\Filament\Resources\WorkLocationResource\Pages;

use App\Filament\Resources\WorkLocationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewWorkLocation extends ViewRecord
{
    protected static string $resource = WorkLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('✏️ Edit Lokasi')
                ->color('warning'),
            Actions\DeleteAction::make()
                ->label('🗑️ Hapus Lokasi')
                ->color('danger'),
        ];
    }

    public function getTitle(): string
    {
        return '👁️ Detail Lokasi Kerja';
    }

    public function getHeading(): string
    {
        return '👁️ Detail Lokasi Kerja';
    }

    public function getSubheading(): ?string
    {
        return 'Informasi lengkap lokasi kerja dan pengaturan geofencing';
    }
}