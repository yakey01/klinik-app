<?php

namespace App\Filament\Paramedis\Resources\TindakanParamedisResource\Pages;

use App\Filament\Paramedis\Resources\TindakanParamedisResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTindakanParamedis extends ViewRecord
{
    protected static string $resource = TindakanParamedisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No edit or delete actions
        ];
    }
}