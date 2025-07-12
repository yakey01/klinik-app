<?php

namespace App\Filament\Resources\DokterUmumJaspelResource\Pages;

use App\Filament\Resources\DokterUmumJaspelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDokterUmumJaspels extends ListRecords
{
    protected static string $resource = DokterUmumJaspelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
