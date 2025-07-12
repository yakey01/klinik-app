<?php

namespace App\Filament\Dokter\Resources\DokterPresensiResource\Pages;

use App\Filament\Dokter\Resources\DokterPresensiResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDokterPresensi extends ViewRecord
{
    protected static string $resource = DokterPresensiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return 'Detail Presensi';
    }
}