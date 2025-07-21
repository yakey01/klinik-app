<?php

namespace App\Filament\Bendahara\Resources\ValidasiPendapatanResource\Pages;

use App\Filament\Bendahara\Resources\ValidasiPendapatanResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewValidasiPendapatan extends ViewRecord
{
    protected static string $resource = ValidasiPendapatanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn (): bool => $this->record->status_validasi === 'pending'),
        ];
    }
}