<?php

namespace App\Filament\Resources\KalenderKerjaResource\Pages;

use App\Filament\Resources\KalenderKerjaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKalenderKerjas extends ListRecords
{
    protected static string $resource = KalenderKerjaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
