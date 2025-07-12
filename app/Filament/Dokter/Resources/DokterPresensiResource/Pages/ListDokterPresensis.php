<?php

namespace App\Filament\Dokter\Resources\DokterPresensiResource\Pages;

use App\Filament\Dokter\Resources\DokterPresensiResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDokterPresensis extends ListRecords
{
    protected static string $resource = DokterPresensiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions are handled by the resource table headerActions
        ];
    }

    public function getTitle(): string
    {
        return 'Data Presensi Saya';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Add widgets here if needed
        ];
    }
}