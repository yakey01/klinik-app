<?php

namespace App\Filament\Bendahara\Resources\DailyFinancialValidationResource\Pages;

use App\Filament\Bendahara\Resources\DailyFinancialValidationResource;
use App\Models\PendapatanHarian;
use App\Models\PengeluaranHarian;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListDailyFinancialValidations extends ListRecords
{
    protected static string $resource = DailyFinancialValidationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(fn () => $this->redirect(static::getResource()::getUrl('index'))),
        ];
    }

    public function getTabs(): array
    {
        return [
            'pendapatan' => Tab::make('Pendapatan Harian')
                ->badge(PendapatanHarian::where('status_validasi', 'pending')->count())
                ->modifyQueryUsing(function (Builder $query) {
                    // Store the active tab in session
                    session(['daily_financial_validation_tab' => 'pendapatan']);
                    
                    // Ensure we're querying the right model
                    if ($query->getModel() instanceof PengeluaranHarian) {
                        // If the query is for PengeluaranHarian but we want PendapatanHarian,
                        // we need to return a new query for the correct model
                        return PendapatanHarian::query();
                    }
                    
                    return $query;
                }),
                
            'pengeluaran' => Tab::make('Pengeluaran Harian')
                ->badge(PengeluaranHarian::where('status_validasi', 'pending')->count())
                ->modifyQueryUsing(function (Builder $query) {
                    // Store the active tab in session
                    session(['daily_financial_validation_tab' => 'pengeluaran']);
                    
                    // Ensure we're querying the right model
                    if ($query->getModel() instanceof PendapatanHarian) {
                        // If the query is for PendapatanHarian but we want PengeluaranHarian,
                        // we need to return a new query for the correct model
                        return PengeluaranHarian::query();
                    }
                    
                    return $query;
                }),
                
            'all_pending' => Tab::make('Semua Pending')
                ->badge(function () {
                    $activeTab = session('daily_financial_validation_tab', 'pendapatan');
                    if ($activeTab === 'pengeluaran') {
                        return PengeluaranHarian::where('status_validasi', 'pending')->count();
                    }
                    return PendapatanHarian::where('status_validasi', 'pending')->count();
                })
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status_validasi', 'pending')),
                
            'approved' => Tab::make('Disetujui')
                ->badge(function () {
                    $activeTab = session('daily_financial_validation_tab', 'pendapatan');
                    if ($activeTab === 'pengeluaran') {
                        return PengeluaranHarian::where('status_validasi', 'approved')->count();
                    }
                    return PendapatanHarian::where('status_validasi', 'approved')->count();
                })
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status_validasi', 'approved')),
                
            'rejected' => Tab::make('Ditolak')
                ->badge(function () {
                    $activeTab = session('daily_financial_validation_tab', 'pendapatan');
                    if ($activeTab === 'pengeluaran') {
                        return PengeluaranHarian::where('status_validasi', 'rejected')->count();
                    }
                    return PendapatanHarian::where('status_validasi', 'rejected')->count();
                })
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status_validasi', 'rejected')),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // You can add widgets here for summary statistics if needed
        ];
    }

    public function getTitle(): string
    {
        return 'Validasi Transaksi Harian';
    }

    public function getHeading(): string
    {
        return 'Pusat Validasi Transaksi Harian';
    }

    public function getSubheading(): ?string
    {
        $pendingCount = PendapatanHarian::where('status_validasi', 'pending')->count() +
                       PengeluaranHarian::where('status_validasi', 'pending')->count();
        
        if ($pendingCount > 0) {
            return "Terdapat {$pendingCount} transaksi yang menunggu validasi";
        }
        
        return 'Semua transaksi telah divalidasi';
    }
}