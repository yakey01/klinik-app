<?php

namespace App\Filament\Dokter\Resources\TindakanResource\Pages;

use App\Filament\Dokter\Resources\TindakanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Models\Dokter;
use Illuminate\Support\Facades\Auth;

class ListTindakans extends ListRecords
{
    protected static string $resource = TindakanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export')
                ->label('Export Data')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    // Implement export functionality
                    $this->notify('success', 'Export akan dimulai dalam beberapa saat');
                }),
                
            Actions\Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    $user = Auth::user();
                    cache()->forget("dokter_dashboard_stats_{$user->id}");
                    cache()->forget("dokter_today_stats_{$user->id}");
                    $this->redirect(request()->header('Referer'));
                }),
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            TindakanResource\Widgets\TindakanStatsWidget::class,
        ];
    }
}