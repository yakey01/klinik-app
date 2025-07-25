<?php

namespace App\Filament\Petugas\Resources\PendapatanHarianResource\Pages;

use App\Filament\Petugas\Resources\PendapatanHarianResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPendapatanHarians extends ListRecords
{
    protected static string $resource = PendapatanHarianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Removed duplicate CreateAction - using custom styled button from table headerActions instead
        ];
    }
}
