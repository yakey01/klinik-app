<?php

namespace App\Filament\Resources\PermohonanCutiResource\Pages;

use App\Filament\Resources\PermohonanCutiResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPermohonanCutis extends ListRecords
{
    protected static string $resource = PermohonanCutiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
