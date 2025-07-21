<?php

namespace App\Filament\Bendahara\Resources\ValidasiPendapatanResource\Pages;

use App\Filament\Bendahara\Resources\ValidasiPendapatanResource;
use Filament\Resources\Pages\ListRecords;

class ListValidasiPendapatan extends ListRecords
{
    protected static string $resource = ValidasiPendapatanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Bendahara only validates, doesn't create
        ];
    }
}