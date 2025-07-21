<?php

namespace App\Filament\Resources\WorkLocationResource\Pages;

use App\Filament\Resources\WorkLocationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWorkLocations extends ListRecords
{
    protected static string $resource = WorkLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('➕ Tambah Lokasi Kerja')
                ->icon('heroicon-o-plus-circle')
                ->color('success'),
        ];
    }

    public function getTitle(): string
    {
        return '📍 Validasi Lokasi (Geofencing)';
    }

    public function getHeading(): string
    {
        return '📍 Validasi Lokasi (Geofencing)';
    }

    public function getSubheading(): ?string
    {
        return 'Kelola lokasi kerja yang diizinkan untuk absensi dengan teknologi geofencing GPS';
    }
}