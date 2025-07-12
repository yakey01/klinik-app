<?php

namespace App\Filament\Resources\GpsSpoofingConfigResource\Pages;

use App\Filament\Resources\GpsSpoofingConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditGpsSpoofingConfig extends EditRecord
{
    protected static string $resource = GpsSpoofingConfigResource::class;
    
    public function getTitle(): string
    {
        return '🛡️ Edit Konfigurasi GPS Security';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => !$this->record->is_active),
        ];
    }
    
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = Auth::id();
        
        return $data;
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
