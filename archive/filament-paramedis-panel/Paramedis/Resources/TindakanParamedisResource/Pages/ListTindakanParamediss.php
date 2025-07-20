<?php

namespace App\Filament\Paramedis\Resources\TindakanParamedisResource\Pages;

use App\Filament\Paramedis\Resources\TindakanParamedisResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTindakanParamediss extends ListRecords
{
    protected static string $resource = TindakanParamedisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action since paramedis cannot create tindakan
        ];
    }
}