<?php

namespace App\Filament\Resources\PengeluaranResource\Pages;

use App\Filament\Resources\PengeluaranResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPengeluaran extends ListRecords
{
    protected static string $resource = PengeluaranResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Pengeluaran')
                ->icon('heroicon-o-plus'),
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            // PengeluaranStatsWidget::class,
        ];
    }
}
