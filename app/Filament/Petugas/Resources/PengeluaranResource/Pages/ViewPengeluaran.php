<?php

namespace App\Filament\Petugas\Resources\PengeluaranResource\Pages;

use App\Filament\Petugas\Resources\PengeluaranResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPengeluaran extends ViewRecord
{
    protected static string $resource = PengeluaranResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn (): bool => $this->record->status === 'pending'),
        ];
    }
}