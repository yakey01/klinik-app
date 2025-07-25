<?php

namespace App\Filament\Resources\AbsenceRequestResource\Pages;

use App\Filament\Resources\AbsenceRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAbsenceRequest extends ViewRecord
{
    protected static string $resource = AbsenceRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}