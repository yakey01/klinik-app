<?php

namespace App\Filament\Resources\FaceRecognitionResource\Pages;

use App\Filament\Resources\FaceRecognitionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateFaceRecognition extends CreateRecord
{
    protected static string $resource = FaceRecognitionResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
