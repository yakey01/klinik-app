<?php

namespace App\Filament\Bendahara\Resources\ValidasiPengeluaranHarianResource\Pages;

use App\Filament\Bendahara\Resources\ValidasiPengeluaranHarianResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListValidasiPengeluaranHarians extends ListRecords
{
    protected static string $resource = ValidasiPengeluaranHarianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action since this is validation only
        ];
    }
}