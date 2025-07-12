<?php

namespace App\Filament\Resources\GpsSpoofingDetectionResource\Pages;

use App\Filament\Resources\GpsSpoofingDetectionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGpsSpoofingDetection extends EditRecord
{
    protected static string $resource = GpsSpoofingDetectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
