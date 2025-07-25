<?php

namespace App\Filament\Bendahara\Resources\JaspelManagementResource\Pages;

use App\Filament\Bendahara\Resources\JaspelManagementResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewJaspelManagement extends ViewRecord
{
    protected static string $resource = JaspelManagementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}