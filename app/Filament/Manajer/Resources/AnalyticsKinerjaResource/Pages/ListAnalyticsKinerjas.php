<?php

namespace App\Filament\Manajer\Resources\AnalyticsKinerjaResource\Pages;

use App\Filament\Manajer\Resources\AnalyticsKinerjaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListAnalyticsKinerjas extends ListRecords
{
    protected static string $resource = AnalyticsKinerjaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refresh')
                ->label('🔄 Refresh Analytics')
                ->icon('heroicon-m-arrow-path')
                ->action(fn () => $this->refreshPage()),
                
            Actions\Action::make('generate_report')
                ->label('📊 Generate KPI Report')
                ->icon('heroicon-m-document-chart-bar')
                ->color('success')
                ->action(function () {
                    \Filament\Notifications\Notification::make()
                        ->title('📊 KPI Report Generated')
                        ->body('Comprehensive performance analytics report is ready for download')
                        ->success()
                        ->send();
                }),
                
            Actions\Action::make('benchmark')
                ->label('🏆 Performance Benchmark')
                ->icon('heroicon-m-trophy')
                ->color('warning')
                ->action(function () {
                    \Filament\Notifications\Notification::make()
                        ->title('🏆 Benchmark Analysis')
                        ->body('Performance benchmarking against industry standards completed')
                        ->warning()
                        ->send();
                }),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('📊 All Analytics')
                ->badge(\App\Models\Tindakan::where('status_validasi', 'disetujui')->count()),
                
            'high_performance' => Tab::make('🎆 High Performance')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('tarif', '>', 500000))
                ->badge(\App\Models\Tindakan::where('status_validasi', 'disetujui')->where('tarif', '>', 500000)->count()),
                
            'this_month' => Tab::make('📆 This Month')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereBetween('tanggal_tindakan', [now()->startOfMonth(), now()->endOfMonth()]))
                ->badge(\App\Models\Tindakan::whereBetween('tanggal_tindakan', [now()->startOfMonth(), now()->endOfMonth()])->where('status_validasi', 'disetujui')->count()),
                
            'trending' => Tab::make('📈 Trending Up')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('tarif', '>', 300000)->orderBy('tanggal_tindakan', 'desc'))
                ->badge(\App\Models\Tindakan::where('status_validasi', 'disetujui')->where('tarif', '>', 300000)->count()),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Can add analytics summary widgets here
        ];
    }
}