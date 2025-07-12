<?php

namespace App\Filament\Manajer\Resources\ManajemenKaryawanResource\Pages;

use App\Filament\Manajer\Resources\ManajemenKaryawanResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewManajemenKaryawan extends ViewRecord
{
    protected static string $resource = ManajemenKaryawanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('← Kembali ke Daftar')
                ->url($this->getResource()::getUrl('index'))
                ->color('gray'),
                
            Actions\Action::make('detailed_report')
                ->label('📊 Detailed Report')
                ->icon('heroicon-m-chart-bar-square')
                ->color('success')
                ->action(function () {
                    \Filament\Notifications\Notification::make()
                        ->title('📊 Detailed Performance Report')
                        ->body('Generating comprehensive performance analysis...')
                        ->success()
                        ->send();
                }),
        ];
    }
}