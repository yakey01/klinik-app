<?php

namespace App\Filament\Bendahara\Resources\RiwayatValidasiTindakanResource\Pages;

use App\Filament\Bendahara\Resources\RiwayatValidasiTindakanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;

class ListRiwayatValidasiTindakan extends ListRecords
{
    protected static string $resource = RiwayatValidasiTindakanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('🔄 Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(fn () => $this->redirect(request()->header('Referer'))),
            
            Action::make('export_all')
                ->label('📤 Export Semua')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    \Filament\Notifications\Notification::make()
                        ->title('📤 Export')
                        ->body('Export functionality coming soon')
                        ->info()
                        ->send();
                }),
        ];
    }

    public function getTitle(): string
    {
        return 'Riwayat Validasi Tindakan';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Add widgets here if needed
        ];
    }
}