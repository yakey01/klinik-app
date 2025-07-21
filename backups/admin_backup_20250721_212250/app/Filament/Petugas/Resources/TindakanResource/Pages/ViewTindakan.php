<?php

namespace App\Filament\Petugas\Resources\TindakanResource\Pages;

use App\Filament\Petugas\Resources\TindakanResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTindakan extends ViewRecord
{
    protected static string $resource = TindakanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn (): bool => $this->record->status === 'pending'),
        ];
    }
}