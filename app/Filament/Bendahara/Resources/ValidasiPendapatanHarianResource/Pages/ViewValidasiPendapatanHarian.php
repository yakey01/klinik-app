<?php

namespace App\Filament\Bendahara\Resources\ValidasiPendapatanHarianResource\Pages;

use App\Filament\Bendahara\Resources\ValidasiPendapatanHarianResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewValidasiPendapatanHarian extends ViewRecord
{
    protected static string $resource = ValidasiPendapatanHarianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}