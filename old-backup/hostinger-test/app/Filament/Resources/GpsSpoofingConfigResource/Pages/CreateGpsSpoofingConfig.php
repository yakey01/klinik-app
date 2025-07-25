<?php

namespace App\Filament\Resources\GpsSpoofingConfigResource\Pages;

use App\Filament\Resources\GpsSpoofingConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateGpsSpoofingConfig extends CreateRecord
{
    protected static string $resource = GpsSpoofingConfigResource::class;
    
    public function getTitle(): string
    {
        return '🛡️ Buat Konfigurasi GPS Security';
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();
        
        return $data;
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
