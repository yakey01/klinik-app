<?php

namespace App\Filament\Paramedis\Resources\JaspelResource\Pages;

use App\Filament\Paramedis\Resources\JaspelResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewJaspel extends ViewRecord
{
    protected static string $resource = JaspelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No actions for view page - paramedis can only view their own jaspel
        ];
    }
}