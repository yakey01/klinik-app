<?php

namespace App\Filament\Resources\DiParamedisResource\Pages;

use App\Filament\Resources\DiParamedisResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDiParamedis extends ListRecords
{
    protected static string $resource = DiParamedisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
