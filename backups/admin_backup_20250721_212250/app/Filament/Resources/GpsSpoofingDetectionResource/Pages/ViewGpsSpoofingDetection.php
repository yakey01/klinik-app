<?php

namespace App\Filament\Resources\GpsSpoofingDetectionResource\Pages;

use App\Filament\Resources\GpsSpoofingDetectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewGpsSpoofingDetection extends ViewRecord
{
    protected static string $resource = GpsSpoofingDetectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}