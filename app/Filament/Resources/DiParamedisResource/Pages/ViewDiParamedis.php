<?php

namespace App\Filament\Resources\DiParamedisResource\Pages;

use App\Filament\Resources\DiParamedisResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDiParamedis extends ViewRecord
{
    protected static string $resource = DiParamedisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn () => $this->record->is_editable),
        ];
    }
}