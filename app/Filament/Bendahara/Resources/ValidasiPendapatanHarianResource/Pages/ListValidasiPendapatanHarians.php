<?php

namespace App\Filament\Bendahara\Resources\ValidasiPendapatanHarianResource\Pages;

use App\Filament\Bendahara\Resources\ValidasiPendapatanHarianResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListValidasiPendapatanHarians extends ListRecords
{
    protected static string $resource = ValidasiPendapatanHarianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action since this is validation only
        ];
    }
}