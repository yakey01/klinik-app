<?php

namespace App\Filament\Resources\KalenderKerjaResource\Pages;

use App\Filament\Resources\KalenderKerjaResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewKalenderKerja extends ViewRecord
{
    protected static string $resource = KalenderKerjaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}