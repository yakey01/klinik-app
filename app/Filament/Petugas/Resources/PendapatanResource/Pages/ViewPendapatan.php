<?php

namespace App\Filament\Petugas\Resources\PendapatanResource\Pages;

use App\Filament\Petugas\Resources\PendapatanResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPendapatan extends ViewRecord
{
    protected static string $resource = PendapatanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn (): bool => $this->record->status_validasi === 'pending'),
        ];
    }
}