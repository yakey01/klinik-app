<?php

namespace App\Filament\Resources\CutiPegawaiResource\Pages;

use App\Filament\Resources\CutiPegawaiResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCutiPegawai extends ViewRecord
{
    protected static string $resource = CutiPegawaiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}