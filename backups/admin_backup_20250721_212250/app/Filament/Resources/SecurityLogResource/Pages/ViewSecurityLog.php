<?php

namespace App\Filament\Resources\SecurityLogResource\Pages;

use App\Filament\Resources\SecurityLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSecurityLog extends ViewRecord
{
    protected static string $resource = SecurityLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}