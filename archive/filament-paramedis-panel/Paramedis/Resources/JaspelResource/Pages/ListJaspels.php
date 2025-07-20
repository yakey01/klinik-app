<?php

namespace App\Filament\Paramedis\Resources\JaspelResource\Pages;

use App\Filament\Paramedis\Resources\JaspelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJaspels extends ListRecords
{
    protected static string $resource = JaspelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
