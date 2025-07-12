<?php

namespace App\Filament\Manajer\Resources\LaporanKeuanganResource\Pages;

use App\Filament\Manajer\Resources\LaporanKeuanganResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListLaporanKeuangans extends ListRecords
{
    protected static string $resource = LaporanKeuanganResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refresh')
                ->label('🔄 Refresh Data')
                ->icon('heroicon-m-arrow-path')
                ->action(fn () => $this->refreshPage()),
                
            Actions\Action::make('export_all')
                ->label('📊 Export Report')
                ->icon('heroicon-m-document-arrow-down')
                ->color('success')
                ->action(function () {
                    \Filament\Notifications\Notification::make()
                        ->title('📊 Export Started')
                        ->body('Financial report export is being processed...')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('📊 Semua Data')
                ->badge(\App\Models\PendapatanHarian::count()),
                
            'approved' => Tab::make('✅ Disetujui')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status_validasi', 'disetujui'))
                ->badge(\App\Models\PendapatanHarian::where('status_validasi', 'disetujui')->count()),
                
            'pending' => Tab::make('⏳ Menunggu')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status_validasi', 'pending'))
                ->badge(\App\Models\PendapatanHarian::where('status_validasi', 'pending')->count()),
                
            'this_month' => Tab::make('📆 Bulan Ini')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereBetween('tanggal_input', [now()->startOfMonth(), now()->endOfMonth()]))
                ->badge(\App\Models\PendapatanHarian::whereBetween('tanggal_input', [now()->startOfMonth(), now()->endOfMonth()])->count()),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Add summary widgets here if needed
        ];
    }
}