<?php

namespace App\Filament\Resources\JenisTindakanResource\Pages;

use App\Filament\Resources\JenisTindakanResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewJenisTindakan extends ViewRecord
{
    protected static string $resource = JenisTindakanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edit'),
        ];
    }
}