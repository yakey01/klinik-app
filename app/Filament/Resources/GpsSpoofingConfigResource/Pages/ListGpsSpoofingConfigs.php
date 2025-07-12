<?php

namespace App\Filament\Resources\GpsSpoofingConfigResource\Pages;

use App\Filament\Resources\GpsSpoofingConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGpsSpoofingConfigs extends ListRecords
{
    protected static string $resource = GpsSpoofingConfigResource::class;
    
    public function getTitle(): string
    {
        return '🛡️ Konfigurasi GPS Security';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Buat Konfigurasi Baru')
                ->icon('heroicon-m-plus')
                ->color('primary'),
        ];
    }
}
