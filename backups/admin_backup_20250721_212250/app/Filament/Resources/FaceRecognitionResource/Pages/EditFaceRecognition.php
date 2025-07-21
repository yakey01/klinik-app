<?php

namespace App\Filament\Resources\FaceRecognitionResource\Pages;

use App\Filament\Resources\FaceRecognitionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFaceRecognition extends EditRecord
{
    protected static string $resource = FaceRecognitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
