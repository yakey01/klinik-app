<?php

namespace App\Filament\Bendahara\Resources\ValidasiPengeluaranHarianResource\Pages;

use App\Filament\Bendahara\Resources\ValidasiPengeluaranHarianResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewValidasiPengeluaranHarian extends ViewRecord
{
    protected static string $resource = ValidasiPengeluaranHarianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}