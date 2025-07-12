<?php

namespace App\Filament\Manajer\Resources\LaporanKeuanganResource\Pages;

use App\Filament\Manajer\Resources\LaporanKeuanganResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewLaporanKeuangan extends ViewRecord
{
    protected static string $resource = LaporanKeuanganResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('← Kembali ke Daftar')
                ->url($this->getResource()::getUrl('index'))
                ->color('gray'),
                
            Actions\Action::make('print')
                ->label('🖨️ Print')
                ->icon('heroicon-m-printer')
                ->color('info')
                ->action(function () {
                    \Filament\Notifications\Notification::make()
                        ->title('🖨️ Print Ready')
                        ->body('Report siap untuk dicetak')
                        ->info()
                        ->send();
                }),
        ];
    }
}