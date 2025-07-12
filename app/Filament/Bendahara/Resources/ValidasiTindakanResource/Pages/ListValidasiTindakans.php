<?php

namespace App\Filament\Bendahara\Resources\ValidasiTindakanResource\Pages;

use App\Filament\Bendahara\Resources\ValidasiTindakanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListValidasiTindakans extends ListRecords
{
    protected static string $resource = ValidasiTindakanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}