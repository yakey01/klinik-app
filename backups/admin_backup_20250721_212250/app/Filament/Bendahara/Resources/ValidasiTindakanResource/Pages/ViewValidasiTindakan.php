<?php

namespace App\Filament\Bendahara\Resources\ValidasiTindakanResource\Pages;

use App\Filament\Bendahara\Resources\ValidasiTindakanResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewValidasiTindakan extends ViewRecord
{
    protected static string $resource = ValidasiTindakanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}