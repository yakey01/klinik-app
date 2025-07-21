<?php

namespace App\Filament\Petugas\Resources\ValidasiPendapatanResource\Pages;

use App\Filament\Petugas\Resources\ValidasiPendapatanResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPendapatan extends ViewRecord
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