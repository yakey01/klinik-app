<?php

namespace App\Filament\Manajer\Resources\AnalyticsKinerjaResource\Pages;

use App\Filament\Manajer\Resources\AnalyticsKinerjaResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAnalyticsKinerja extends ViewRecord
{
    protected static string $resource = AnalyticsKinerjaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('← Kembali ke Analytics')
                ->url($this->getResource()::getUrl('index'))
                ->color('gray'),
                
            Actions\Action::make('deep_analysis')
                ->label('🔍 Deep Analysis')
                ->icon('heroicon-m-magnifying-glass-plus')
                ->color('success')
                ->action(function () {
                    \Filament\Notifications\Notification::make()
                        ->title('🔍 Deep Performance Analysis')
                        ->body('Advanced analytics processing initiated for detailed insights')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('compare')
                ->label('⚖️ Compare Performance')
                ->icon('heroicon-m-scale')
                ->color('warning')
                ->action(function () {
                    \Filament\Notifications\Notification::make()
                        ->title('⚖️ Performance Comparison')
                        ->body('Comparative analysis with peer performance metrics')
                        ->warning()
                        ->send();
                }),
        ];
    }
}